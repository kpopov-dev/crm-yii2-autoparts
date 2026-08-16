<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260101_000001_create_crm_schema extends Migration
{
    private const TABLE_OPTIONS = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

    public function safeUp(): void
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'email' => $this->string(190)->notNull(),
            'password_hash' => $this->string(255)->notNull(),
            'full_name' => $this->string(255)->notNull(),
            'role' => $this->string(20)->notNull()->defaultValue('manager'),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->null(),
        ], self::TABLE_OPTIONS);

        $this->createIndex('ux_user_email', '{{%user}}', 'email', true);
        $this->createIndex('ix_user_role_active', '{{%user}}', ['role', 'is_active']);

        $this->createTable('{{%client}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'email' => $this->string(190)->null(),
            'phone' => $this->string(32)->null(),
            'inn' => $this->string(12)->null(),
            'comment' => $this->text()->null(),
            'manager_id' => $this->integer()->notNull(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->null(),
        ], self::TABLE_OPTIONS);

        $this->createIndex('ux_client_email', '{{%client}}', 'email', true);
        $this->createIndex('ix_client_manager_created', '{{%client}}', ['manager_id', 'created_at']);
        $this->createIndex('ix_client_name', '{{%client}}', 'name');
        $this->createIndex('ix_client_inn', '{{%client}}', 'inn');
        $this->addForeignKey('fk_client_manager', '{{%client}}', 'manager_id', '{{%user}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%deal}}', [
            'id' => $this->primaryKey(),
            'number' => $this->string(32)->notNull(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'amount' => $this->decimal(14, 2)->notNull()->defaultValue(0),
            'currency' => $this->char(3)->notNull()->defaultValue('RUB'),
            'stage' => $this->string(30)->notNull()->defaultValue('new'),
            'client_id' => $this->integer()->notNull(),
            'responsible_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->null(),
            'closed_at' => $this->integer()->null(),
        ], self::TABLE_OPTIONS);

        $this->createIndex('ux_deal_number', '{{%deal}}', 'number', true);
        $this->createIndex('ix_deal_responsible_stage_created', '{{%deal}}', ['responsible_id', 'stage', 'created_at']);
        $this->createIndex('ix_deal_stage_updated', '{{%deal}}', ['stage', 'updated_at']);
        $this->createIndex('ix_deal_client_stage', '{{%deal}}', ['client_id', 'stage']);
        $this->createIndex('ix_deal_stage_closed', '{{%deal}}', ['stage', 'closed_at']);
        $this->createIndex('ix_deal_created', '{{%deal}}', 'created_at');
        $this->addForeignKey('fk_deal_client', '{{%deal}}', 'client_id', '{{%client}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_deal_responsible', '{{%deal}}', 'responsible_id', '{{%user}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%deal_stage_history}}', [
            'id' => $this->primaryKey(),
            'deal_id' => $this->integer()->notNull(),
            'stage_from' => $this->string(30)->null(),
            'stage_to' => $this->string(30)->notNull(),
            'comment' => $this->string(500)->null(),
            'user_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], self::TABLE_OPTIONS);

        $this->createIndex('ix_history_deal_created', '{{%deal_stage_history}}', ['deal_id', 'created_at']);
        $this->addForeignKey('fk_history_deal', '{{%deal_stage_history}}', 'deal_id', '{{%deal}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_history_user', '{{%deal_stage_history}}', 'user_id', '{{%user}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%task}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'status' => $this->string(20)->notNull()->defaultValue('open'),
            'deal_id' => $this->integer()->null(),
            'client_id' => $this->integer()->null(),
            'assignee_id' => $this->integer()->notNull(),
            'author_id' => $this->integer()->notNull(),
            'due_at' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->null(),
            'completed_at' => $this->integer()->null(),
        ], self::TABLE_OPTIONS);

        $this->createIndex('ix_task_assignee_status_due', '{{%task}}', ['assignee_id', 'status', 'due_at']);
        $this->createIndex('ix_task_deal_status', '{{%task}}', ['deal_id', 'status']);
        $this->createIndex('ix_task_status_due', '{{%task}}', ['status', 'due_at']);
        $this->addForeignKey('fk_task_deal', '{{%task}}', 'deal_id', '{{%deal}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_task_assignee', '{{%task}}', 'assignee_id', '{{%user}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%outbox_message}}', [
            'id' => $this->bigPrimaryKey(),
            'message_id' => $this->string(36)->notNull(),
            'event_name' => $this->string(64)->notNull(),
            'payload' => $this->text()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'attempts' => $this->smallInteger()->notNull()->defaultValue(0),
            'last_error' => $this->string(1000)->null(),
            'created_at' => $this->integer()->notNull(),
            'published_at' => $this->integer()->null(),
        ], self::TABLE_OPTIONS);

        $this->createIndex('ux_outbox_message_id', '{{%outbox_message}}', 'message_id', true);
        $this->createIndex('ix_outbox_status_id', '{{%outbox_message}}', ['status', 'id']);

        $this->createTable('{{%processed_message}}', [
            'id' => $this->bigPrimaryKey(),
            'message_id' => $this->string(36)->notNull(),
            'consumer' => $this->string(64)->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], self::TABLE_OPTIONS);

        $this->createIndex('ux_processed_message', '{{%processed_message}}', ['message_id', 'consumer'], true);

        $this->createTable('{{%notification}}', [
            'id' => $this->bigPrimaryKey(),
            'user_id' => $this->integer()->notNull(),
            'type' => $this->string(64)->notNull(),
            'title' => $this->string(255)->notNull(),
            'body' => $this->text()->null(),
            'entity_type' => $this->string(64)->null(),
            'entity_id' => $this->integer()->null(),
            'is_read' => $this->boolean()->notNull()->defaultValue(false),
            'created_at' => $this->integer()->notNull(),
            'read_at' => $this->integer()->null(),
        ], self::TABLE_OPTIONS);

        $this->createIndex('ix_notification_user_read_id', '{{%notification}}', ['user_id', 'is_read', 'id']);
        $this->addForeignKey('fk_notification_user', '{{%notification}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%daily_stat}}', [
            'id' => $this->primaryKey(),
            'stat_date' => $this->char(10)->notNull(),
            'manager_id' => $this->integer()->notNull(),
            'deals_created' => $this->integer()->notNull()->defaultValue(0),
            'deals_won' => $this->integer()->notNull()->defaultValue(0),
            'deals_lost' => $this->integer()->notNull()->defaultValue(0),
            'won_amount' => $this->decimal(14, 2)->notNull()->defaultValue(0),
            'updated_at' => $this->integer()->null(),
        ], self::TABLE_OPTIONS);

        $this->createIndex('ux_daily_stat', '{{%daily_stat}}', ['stat_date', 'manager_id'], true);
        $this->addForeignKey('fk_daily_stat_manager', '{{%daily_stat}}', 'manager_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%daily_stat}}');
        $this->dropTable('{{%notification}}');
        $this->dropTable('{{%processed_message}}');
        $this->dropTable('{{%outbox_message}}');
        $this->dropTable('{{%task}}');
        $this->dropTable('{{%deal_stage_history}}');
        $this->dropTable('{{%deal}}');
        $this->dropTable('{{%client}}');
        $this->dropTable('{{%user}}');
    }
}
