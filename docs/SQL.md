# SQL: запросы, индексы, оптимизация

ActiveRecord в проекте используется только для записи и валидации. Все выборки для
списков и отчётов написаны вручную: так видно, какой именно запрос уходит в базу,
и можно управлять планом выполнения.

## Принципы

**Индекс проектируется под запрос, а не наоборот.** Порядок колонок в составном
индексе повторяет порядок условий: сначала колонки с равенством, затем диапазон,
затем сортировка.

**Функции не применяются к индексируемой колонке.** Даты хранятся как `INT` с
unix-временем, поэтому фильтр по периоду — это обычный диапазон
`created_at BETWEEN :from AND :to`, а не `DATE(FROM_UNIXTIME(created_at))`,
который бы отключил индекс.

**Поиск префиксный.** `LIKE 'Северный%'` использует индекс, `LIKE '%Северный%'` —
нет. Пользовательский ввод экранируется `addcslashes($value, '%_\\')`, чтобы символы
подстановки не приходили снаружи. Поиск контрагента по началу названия или по ИНН
покрывает подавляющее большинство обращений менеджера.

**Сортировка из белого списка.** Направление и колонка приходят из карты
`SORT_WHITELIST`; произвольная строка в `ORDER BY` попасть не может.

**Подсчёт общего количества отдельным запросом.** Вместо устаревшего `SQL_CALC_FOUND_ROWS`
выполняется отдельный `COUNT(*)` по тем же условиям — на InnoDB это быстрее и не
блокирует оптимизатор.

**Только нужные колонки.** `SELECT *` в списках не используется: лишние `TEXT`-поля
удорожают выборку и мешают покрывающим индексам.

---

## Список заказов

```sql
SELECT d.id, d.number, d.title, d.amount, d.currency, d.stage,
       d.client_id, d.responsible_id, d.created_at, d.updated_at, d.closed_at,
       c.name AS client_name,
       u.full_name AS responsible_name,
       (SELECT COUNT(*) FROM task t
         WHERE t.deal_id = d.id AND t.status IN ('open', 'in_progress')) AS open_tasks
  FROM deal d
  INNER JOIN client c ON c.id = d.client_id
  INNER JOIN user u ON u.id = d.responsible_id
 WHERE d.responsible_id = :responsibleId
   AND d.stage = :stage
   AND d.created_at >= :createdFrom
 ORDER BY d.id DESC
 LIMIT :limit OFFSET :offset
```

Работающий индекс:

```sql
ix_deal_responsible_stage_created (responsible_id, stage, created_at)
```

Два равенства и диапазон по третьей колонке — классический случай, когда составной
индекс отрабатывает целиком. `EXPLAIN` должен показать `type = range` и
`key = ix_deal_responsible_stage_created`.

Коррелированный подзапрос по задачам опирается на `ix_task_deal_status
(deal_id, status)` и выполняется для страницы из 20 строк, а не для всей таблицы.
Альтернатива через `LEFT JOIN ... GROUP BY` заставила бы группировать всю выборку
до применения `LIMIT`, поэтому здесь подзапрос дешевле.

Проверить план:

```bash
docker compose exec mysql mysql -ucrm -pcrm crm -e "
EXPLAIN SELECT d.id, d.number FROM deal d
 WHERE d.responsible_id = 2 AND d.stage = 'proposal' AND d.created_at >= 1750000000
 ORDER BY d.id DESC LIMIT 20\G"
```

---

## Канбан-доска

Задача: получить по 50 свежих заказов в каждой из шести стадий воронки. Наивное решение —
шесть отдельных запросов или выборка всех заказов с группировкой в PHP. Оконная
функция MySQL 8 решает это одним запросом:

```sql
SELECT d.*, c.name AS client_name, u.full_name AS responsible_name
  FROM (
        SELECT id,
               ROW_NUMBER() OVER (PARTITION BY stage ORDER BY updated_at DESC, id DESC) AS rn
          FROM deal d
         WHERE d.stage NOT IN ('won', 'lost')
       ) ranked
  INNER JOIN deal d ON d.id = ranked.id
  INNER JOIN client c ON c.id = d.client_id
  INNER JOIN user u ON u.id = d.responsible_id
 WHERE ranked.rn <= 50
 ORDER BY d.updated_at DESC, d.id DESC
```

Индекс `ix_deal_stage_updated (stage, updated_at)` совпадает с разбиением и
сортировкой окна, поэтому нумерация внутри секции идёт по индексу.

Внутренний запрос выбирает только `id`, а полные строки подтягиваются джойном по
первичному ключу уже после отсечения лишних записей. Итоги по стадиям (`count`,
`amount`) считаются вторым агрегирующим запросом по тем же условиям — доска
показывает реальные суммы, а не сумму видимых карточек.

---

## Воронка продаж

```sql
SELECT d.stage,
       COUNT(*) AS deals_count,
       COALESCE(SUM(d.amount), 0) AS deals_amount,
       COALESCE(AVG(d.amount), 0) AS avg_amount
  FROM deal d
 WHERE d.responsible_id = :managerId
   AND d.created_at BETWEEN :from AND :to
 GROUP BY d.stage
```

Индекс `ix_deal_responsible_stage_created` покрывает и фильтр, и группировку:
после отбора по `responsible_id` записи в индексе уже упорядочены по `stage`,
поэтому группировка обходится без временной таблицы и `filesort`.

Стадии, по которым в периоде нет ни одного заказа, база не вернёт. Полный набор
из шести колонок достраивается в PHP по `DealStage::all()` — воронка на дашборде
не «схлопывается» и не прыгает при смене периода.

---

## Рейтинг менеджеров

```sql
SELECT u.id AS manager_id,
       u.full_name AS manager_name,
       COUNT(d.id) AS deals_total,
       SUM(CASE WHEN d.stage = 'won' THEN 1 ELSE 0 END) AS deals_won,
       SUM(CASE WHEN d.stage = 'lost' THEN 1 ELSE 0 END) AS deals_lost,
       COALESCE(SUM(CASE WHEN d.stage = 'won' THEN d.amount ELSE 0 END), 0) AS won_amount,
       COALESCE(AVG(CASE WHEN d.stage = 'won' THEN d.closed_at - d.created_at END), 0) AS avg_cycle
  FROM user u
  LEFT JOIN deal d
         ON d.responsible_id = u.id
        AND d.created_at BETWEEN :from AND :to
 WHERE u.is_active = 1
 GROUP BY u.id, u.full_name
 ORDER BY won_amount DESC, deals_won DESC
```

Два момента, которые легко сделать неправильно:

1. Условие по периоду стоит **в `ON`, а не в `WHERE`**. В `WHERE` оно превратило бы
   `LEFT JOIN` во внутренний, и менеджеры без сделок за период исчезли бы из
   рейтинга вместо того, чтобы показать нули.
2. Все метрики считаются одним проходом через условную агрегацию. Отдельные запросы
   на «выиграно», «проиграно» и «сумма» дали бы три сканирования вместо одного.

Средний цикл заказа от заявки до отгрузки — `AVG(closed_at - created_at)`; `CASE` без ветки `ELSE` даёт
`NULL`, а `AVG` игнорирует `NULL`, поэтому незакрытые заказы не занижают среднее.

---

## Выручка по месяцам

```sql
SELECT DATE_FORMAT(FROM_UNIXTIME(d.closed_at), '%Y-%m') AS period,
       COUNT(*) AS deals_won,
       COALESCE(SUM(d.amount), 0) AS revenue
  FROM deal d
 WHERE d.stage = 'won'
   AND d.closed_at BETWEEN :from AND :to
 GROUP BY period
 ORDER BY period ASC
```

Функция применяется только к колонке в `SELECT` и `GROUP BY`, но не в `WHERE`.
Отбор строк идёт по `ix_deal_stage_closed (stage, closed_at)` обычным диапазоном,
а форматирование месяца выполняется уже на отобранных строках. Если бы условие было
записано как `WHERE YEAR(FROM_UNIXTIME(closed_at)) = 2026`, индекс не применился бы.

---

## Витрина дневной статистики

Отчёт за длинный период не должен каждый раз пересчитывать все заказы. Консьюмер
очереди `crm.analytics` инкрементально обновляет агрегат:

```sql
INSERT INTO daily_stat
    (stat_date, manager_id, deals_created, deals_won, deals_lost, won_amount, updated_at)
VALUES
    (:statDate, :managerId, :dealsCreated, :dealsWon, :dealsLost, :wonAmount, :updatedAt)
ON DUPLICATE KEY UPDATE
    deals_created = deals_created + VALUES(deals_created),
    deals_won     = deals_won + VALUES(deals_won),
    deals_lost    = deals_lost + VALUES(deals_lost),
    won_amount    = won_amount + VALUES(won_amount),
    updated_at    = VALUES(updated_at)
```

Работает за счёт уникального индекса `ux_daily_stat (stat_date, manager_id)`:
одна операция вместо связки `SELECT` + `INSERT`/`UPDATE`, без гонки между воркерами.

---

## Транзакции и блокировки

### Смена стадии заказа

```php
$transaction = $this->db->beginTransaction();

$row = $this->db->createCommand(
    'SELECT id, number, title, stage, amount, currency, responsible_id
       FROM deal WHERE id = :id FOR UPDATE',
    [':id' => $id]
)->queryOne();

$this->policy->assert((string)$row['stage'], $stageTo);

// UPDATE deal, INSERT deal_stage_history, INSERT outbox_message

$transaction->commit();
```

`SELECT ... FOR UPDATE` удерживает строку до конца транзакции. Без него два
одновременных перетаскивания карточки могли бы прочитать одну и ту же стадию и оба
пройти проверку перехода — в истории появились бы два взаимоисключающих события.

Изоляция по умолчанию в InnoDB — `REPEATABLE READ`, её достаточно: блокировка строки
делает проверку и запись атомарными относительно конкурирующих транзакций.

### Публикация событий

```sql
SELECT id, message_id, event_name, payload, attempts
  FROM outbox_message
 WHERE status = 'pending' AND attempts < 5
 ORDER BY id ASC
 LIMIT 100
 FOR UPDATE SKIP LOCKED
```

`SKIP LOCKED` пропускает строки, уже захваченные другим воркером. Это позволяет
запустить несколько экземпляров `outbox/relay` горизонтально: они разберут разные
пачки без дедлоков и без повторной публикации одних и тех же событий.

Отбор идёт по `ix_outbox_status_id (status, id)`, поэтому очередь остаётся дешёвой
даже когда таблица накопила миллионы опубликованных записей.

---

## Полный список индексов

| Таблица | Индекс | Под какой запрос |
| --- | --- | --- |
| `user` | `ux_user_email` | вход по e-mail |
| `user` | `ix_user_role_active` | выборка активных менеджеров |
| `client` | `ux_client_email` | контроль уникальности почты |
| `client` | `ix_client_manager_created` | список клиентов менеджера |
| `client` | `ix_client_name`, `ix_client_inn` | префиксный поиск |
| `deal` | `ux_deal_number` | уникальность номера |
| `deal` | `ix_deal_responsible_stage_created` | список и воронка |
| `deal` | `ix_deal_stage_updated` | канбан-доска |
| `deal` | `ix_deal_client_stage` | статистика по клиенту |
| `deal` | `ix_deal_stage_closed` | выручка по месяцам |
| `deal_stage_history` | `ix_history_deal_created` | история заказа |
| `task` | `ix_task_assignee_status_due` | задачи исполнителя, просрочка |
| `task` | `ix_task_deal_status` | счётчик открытых задач заказа |
| `task` | `ix_task_status_due` | отчёт по просроченным |
| `outbox_message` | `ux_outbox_message_id` | защита от дублей |
| `outbox_message` | `ix_outbox_status_id` | выборка pending |
| `processed_message` | `ux_processed_message` | идемпотентность консьюмеров |
| `notification` | `ix_notification_user_read_id` | лента и счётчик непрочитанных |
| `daily_stat` | `ux_daily_stat` | upsert витрины |

---

## Что стоит сделать при росте нагрузки

- Заменить `LIMIT ... OFFSET` на keyset-пагинацию (`WHERE id < :lastId`) — на
  глубоких страницах offset заставляет базу пропускать строки одну за одной.
- Перенести префиксный поиск на полнотекстовый индекс или отдельный поисковый
  движок, когда потребуется искать по подстроке в середине названия.
- Разделить `outbox_message` партиционированием по дате и чистить опубликованные
  записи по расписанию.
- Вынести отчёты на реплику для чтения: они уже изолированы в отдельном
  репозитории, поэтому потребуется поменять только соединение в контейнере.
