<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task_history}}`.
 */
class m000000_000010_create_task_history_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_history}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->null(),
            'action_type' => $this->string(50)->notNull(),
            'field_name' => $this->string(100)->null(),
            'old_value' => $this->text()->null(),
            'new_value' => $this->text()->null(),
            'description' => $this->text()->notNull(),
            'ip_address' => $this->string(45)->null(),
            'user_agent' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
        ]);

        // Add indexes for better query performance
        $this->createIndex(
            '{{%idx-task_history-task_id}}',
            '{{%task_history}}',
            'task_id'
        );

        $this->createIndex(
            '{{%idx-task_history-user_id}}',
            '{{%task_history}}',
            'user_id'
        );

        $this->createIndex(
            '{{%idx-task_history-action_type}}',
            '{{%task_history}}',
            'action_type'
        );

        $this->createIndex(
            '{{%idx-task_history-created_at}}',
            '{{%task_history}}',
            'created_at'
        );

        // Add foreign key constraints if User table exists
        // Note: Adjust table names based on your actual schema
        if ($this->db->schema->getTableSchema('user') !== null) {
            $this->addForeignKey(
                '{{%fk-task_history-user_id}}',
                '{{%task_history}}',
                'user_id',
                '{{%user}}',
                'id',
                'SET NULL'
            );
        }

        // Add sample data for existing tasks
        $this->batchInsert('{{%task_history}}', [
            'task_id', 'action_type', 'description', 'created_at'
        ], [
            [1, 'created', 'Task was created', time() - 86400], // 1 day ago
            [2, 'created', 'Task was created', time() - 172800], // 2 days ago
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign key constraints first
        if ($this->db->schema->getTableSchema('user') !== null) {
            $this->dropForeignKey(
                '{{%fk-task_history-user_id}}',
                '{{%task_history}}'
            );
        }

        // Drop indexes
        $this->dropIndex(
            '{{%idx-task_history-created_at}}',
            '{{%task_history}}'
        );

        $this->dropIndex(
            '{{%idx-task_history-action_type}}',
            '{{%task_history}}'
        );

        $this->dropIndex(
            '{{%idx-task_history-user_id}}',
            '{{%task_history}}'
        );

        $this->dropIndex(
            '{{%idx-task_history-task_id}}',
            '{{%task_history}}'
        );

        $this->dropTable('{{%task_history}}');
    }
}