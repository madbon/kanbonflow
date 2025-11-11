<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task_checklist}}`.
 */
class m000000_000013_create_task_checklist_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_checklist}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull(),
            'step_text' => $this->text()->notNull(),
            'is_completed' => $this->boolean()->notNull()->defaultValue(0),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'completed_at' => $this->integer()->null(),
            'completed_by' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Add indexes for better query performance
        $this->createIndex(
            '{{%idx-task_checklist-task_id}}',
            '{{%task_checklist}}',
            'task_id'
        );

        $this->createIndex(
            '{{%idx-task_checklist-sort_order}}',
            '{{%task_checklist}}',
            'sort_order'
        );

        $this->createIndex(
            '{{%idx-task_checklist-is_completed}}',
            '{{%task_checklist}}',
            'is_completed'
        );

        $this->createIndex(
            '{{%idx-task_checklist-completed_by}}',
            '{{%task_checklist}}',
            'completed_by'
        );

        // Add foreign key for task_id if tasks table exists
        if ($this->db->schema->getTableSchema('tasks') !== null) {
            $this->addForeignKey(
                '{{%fk-task_checklist-task_id}}',
                '{{%task_checklist}}',
                'task_id',
                '{{%tasks}}',
                'id',
                'CASCADE'
            );
        }

        // Add foreign key for completed_by if user table exists
        if ($this->db->schema->getTableSchema('user') !== null) {
            $this->addForeignKey(
                '{{%fk-task_checklist-completed_by}}',
                '{{%task_checklist}}',
                'completed_by',
                '{{%user}}',
                'id',
                'SET NULL'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign key constraints first
        if ($this->db->schema->getTableSchema('user') !== null) {
            $this->dropForeignKey(
                '{{%fk-task_checklist-completed_by}}',
                '{{%task_checklist}}'
            );
        }

        if ($this->db->schema->getTableSchema('tasks') !== null) {
            $this->dropForeignKey(
                '{{%fk-task_checklist-task_id}}',
                '{{%task_checklist}}'
            );
        }

        // Drop indexes
        $this->dropIndex(
            '{{%idx-task_checklist-completed_by}}',
            '{{%task_checklist}}'
        );

        $this->dropIndex(
            '{{%idx-task_checklist-is_completed}}',
            '{{%task_checklist}}'
        );

        $this->dropIndex(
            '{{%idx-task_checklist-sort_order}}',
            '{{%task_checklist}}'
        );

        $this->dropIndex(
            '{{%idx-task_checklist-task_id}}',
            '{{%task_checklist}}'
        );

        $this->dropTable('{{%task_checklist}}');
    }
}