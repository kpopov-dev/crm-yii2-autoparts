# REST API

Базовый адрес: `http://localhost:8080`
Формат: JSON, кодировка UTF-8.

Все эндпоинты, кроме `POST /api/auth/login` и `GET /api/health`, требуют заголовок:

```
Authorization: Bearer <token>
```

## Коды ответов

| Код | Когда возвращается |
| --- | --- |
| 200 | успешный запрос |
| 400 | нарушено бизнес-правило (например, закрытая задача) |
| 401 | неверные учётные данные, токен отсутствует, повреждён или истёк |
| 403 | у роли нет нужного разрешения |
| 404 | сущность не найдена |
| 409 | недопустимый переход стадии заказа |
| 422 | ошибка валидации входных данных |

Формат ошибки:

```json
{ "success": false, "message": "Заказ с идентификатором 42 не найден", "code": 404 }
```

Формат ошибки валидации:

```json
{
  "success": false,
  "message": "Данные не прошли валидацию",
  "errors": { "email": ["Контрагент с таким e-mail уже существует"] }
}
```

Формат списков:

```json
{
  "items": [],
  "meta": { "total": 120, "page": 1, "limit": 20, "pageCount": 6 }
}
```

Параметры постраничной навигации: `page` (по умолчанию 1) и `limit`
(по умолчанию 20, максимум 100).

---

## Аутентификация

### POST /api/auth/login

```json
{ "email": "admin@crm.local", "password": "Admin123!" }
```

Ответ:

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "expiresAt": 1770000000,
  "user": { "id": 1, "email": "admin@crm.local", "fullName": "Кирилл Попов", "role": "admin" }
}
```

### GET /api/auth/me

Профиль текущего пользователя.

---

## Здоровье сервиса

### GET /api/health

```json
{ "status": "ok", "database": "up", "outboxPending": 0, "time": "2026-08-16T12:00:00+03:00" }
```

Подходит для healthcheck в оркестраторе: `outboxPending`, который растёт, сигнализирует
о недоступности RabbitMQ или остановленном воркере.

---

## Пользователи

### GET /api/users

Список активных пользователей для выпадающих списков.

---

## Контрагенты

Требуемые разрешения: `client.view` для чтения, `client.manage` для изменения.

Контрагенты — оптовые покупатели: СТО, дилерские центры, таксопарки, автопарки,
розничные точки.

| Метод | Путь | Назначение |
| --- | --- | --- |
| GET | `/api/clients` | список с фильтрами |
| GET | `/api/clients/{id}` | карточка со статистикой по заказам |
| POST | `/api/clients` | создание |
| PUT / PATCH | `/api/clients/{id}` | изменение |
| DELETE | `/api/clients/{id}` | перевод в архив |

Параметры списка: `query` (префиксный поиск по названию, e-mail и ИНН),
`managerId`, `isActive`, `sort` из набора `id`, `-id`, `name`, `-name`,
`createdAt`, `-createdAt`.

Тело создания:

```json
{
  "name": "ООО «Автотехцентр Гаражъ»",
  "email": "zakaz@garazh.ru",
  "phone": "+7 (909) 123-45-67",
  "inn": "7701234567",
  "managerId": 2,
  "comment": "Сегмент: независимая СТО. Отсрочка платежа 14 календарных дней"
}
```

Ответ карточки содержит блок статистики:

```json
{
  "id": 1,
  "name": "ООО «Автотехцентр Гаражъ»",
  "managerName": "Анна Ковалёва",
  "stats": { "dealsTotal": 7, "dealsWon": 3, "wonAmount": 940000, "openAmount": 310000 }
}
```

---

## Заказы на поставку

Требуемые разрешения: `deal.view`, `deal.manage`.

| Метод | Путь | Назначение |
| --- | --- | --- |
| GET | `/api/deals` | список заказов с фильтрами и сортировкой |
| GET | `/api/deals/board` | канбан-доска по стадиям |
| GET | `/api/deals/stages` | справочник стадий |
| GET | `/api/deals/{id}` | карточка заказа с историей стадий |
| POST | `/api/deals` | создание |
| PUT / PATCH | `/api/deals/{id}` | изменение |
| POST | `/api/deals/{id}/stage` | смена стадии |

Параметры списка: `query`, `stage`, `clientId`, `responsibleId`, `amountFrom`,
`createdFrom`, `createdTo` в формате `Y-m-d`, `onlyOpen`, `sort`.

Смена стадии:

```json
{ "stage": "negotiation", "comment": "Клиент запросил отсрочку платежа на 30 дней" }
```

Карточка заказа возвращает поле `availableStages` — только те переходы, которые
разрешены политикой из текущей стадии. Интерфейс строит кнопки по этому списку,
поэтому логика воронки описана в одном месте на сервере.

Ответ доски:

```json
{
  "columns": [
    {
      "stage": "new",
      "label": "Заявка",
      "count": 14,
      "amount": 2870000,
      "items": []
    }
  ]
}
```

В `items` попадает не более 50 самых свежих заказов на стадию, при этом `count`
и `amount` считаются по всей выборке.

---

## Задачи

Требуемые разрешения: `task.view`, `task.manage`.

| Метод | Путь | Назначение |
| --- | --- | --- |
| GET | `/api/tasks` | список |
| GET | `/api/tasks/statuses` | справочник статусов |
| GET | `/api/tasks/{id}` | карточка |
| POST | `/api/tasks` | создание |
| POST | `/api/tasks/{id}/status` | смена статуса |
| POST | `/api/tasks/{id}/assignee` | переназначение исполнителя |

Параметры списка: `assigneeId`, `dealId`, `status`, `onlyActive`, `overdue`, `sort`.

Создание:

```json
{
  "title": "Проверить остаток на складе и в филиалах",
  "assigneeId": 2,
  "dealId": 15,
  "dueAt": "2026-09-01 12:00",
  "description": "Тормозные колодки TRW, 60 компл., позиция под заказ"
}
```

Закрытая задача повторно не меняется: попытка сменить статус выполненной или
отменённой задачи вернёт 400 с пояснением.

---

## Отчёты

Требуемое разрешение: `report.view`. Менеджер видит данные только по себе,
администратор может передать `managerId`.

| Метод | Путь | Назначение |
| --- | --- | --- |
| GET | `/api/reports/dashboard` | сводка для дашборда |
| GET | `/api/reports/funnel` | воронка по стадиям |
| GET | `/api/reports/managers` | рейтинг менеджеров |
| GET | `/api/reports/daily` | витрина дневной статистики |

Общие параметры: `from` и `to` в формате `Y-m-d`. Если не переданы — последние 90 дней.

Ответ дашборда:

```json
{
  "range": { "from": "2026-05-18", "to": "2026-08-16" },
  "summary": {
    "total": 120, "won": 31, "lost": 22, "active": 67,
    "wonAmount": 9840000, "conversion": 25.83, "averageCheck": 317419.35
  },
  "funnel": [],
  "revenueByMonth": [],
  "managers": [],
  "overdueTasks": []
}
```

---

## Уведомления

| Метод | Путь | Назначение |
| --- | --- | --- |
| GET | `/api/notifications` | список, параметр `onlyUnread` |
| GET | `/api/notifications/unread-count` | счётчик непрочитанных |
| POST | `/api/notifications/read` | пометить прочитанными по списку `ids` |
| POST | `/api/notifications/read-all` | пометить все прочитанными |

Уведомления создаёт консьюмер очереди `crm.notifications` после обработки события,
поэтому они появляются с небольшой задержкой после смены стадии заказа — это
ожидаемое поведение асинхронной обработки.

---

## Примеры запросов

```bash
TOKEN=$(curl -s -X POST http://localhost:8080/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@crm.local","password":"Admin123!"}' | jq -r .token)

curl -s http://localhost:8080/api/deals/board?onlyOpen=1 \
  -H "Authorization: Bearer $TOKEN" | jq

curl -s -X POST http://localhost:8080/api/deals/1/stage \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"stage":"qualification","comment":"Позиции подобраны, наличие подтверждено"}' | jq
```
