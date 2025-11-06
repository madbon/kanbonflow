<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task_comments}}`.
 */
class m251106_053705_create_task_comments_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_comments}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'comment' => $this->text()->notNull(),
            'parent_id' => $this->integer()->null(), // For nested replies
            'is_internal' => $this->boolean()->defaultValue(false), // For internal team comments
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Add indexes
        $this->createIndex('idx-task-comments-task-id', '{{%task_comments}}', 'task_id');
        $this->createIndex('idx-task-comments-user-id', '{{%task_comments}}', 'user_id');
        $this->createIndex('idx-task-comments-parent-id', '{{%task_comments}}', 'parent_id');
        $this->createIndex('idx-task-comments-created-at', '{{%task_comments}}', 'created_at');

        // Add foreign keys if tables exist
        if ($this->db->schema->getTableSchema('tasks') !== null) {
            $this->addForeignKey(
                'fk-task-comments-task-id',
                '{{%task_comments}}',
                'task_id',
                '{{%tasks}}',
                'id',
                'CASCADE'
            );
        }

        if ($this->db->schema->getTableSchema('user') !== null) {
            $this->addForeignKey(
                'fk-task-comments-user-id',
                '{{%task_comments}}',
                'user_id',
                '{{%user}}',
                'id',
                'CASCADE'
            );
        }

        // Self-referencing foreign key for replies
        $this->addForeignKey(
            'fk-task-comments-parent-id',
            '{{%task_comments}}',
            'parent_id',
            '{{%task_comments}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign keys
        $this->dropForeignKey('fk-task-comments-parent-id', '{{%task_comments}}');
        
        if ($this->db->schema->getTableSchema('user') !== null) {
            $this->dropForeignKey('fk-task-comments-user-id', '{{%task_comments}}');
        }

        if ($this->db->schema->getTableSchema('tasks') !== null) {
            $this->dropForeignKey('fk-task-comments-task-id', '{{%task_comments}}');
        }

        // Drop indexes
        $this->dropIndex('idx-task-comments-created-at', '{{%task_comments}}');
        $this->dropIndex('idx-task-comments-parent-id', '{{%task_comments}}');
        $this->dropIndex('idx-task-comments-user-id', '{{%task_comments}}');
        $this->dropIndex('idx-task-comments-task-id', '{{%task_comments}}');

        $this->dropTable('{{%task_comments}}');
    }
}
