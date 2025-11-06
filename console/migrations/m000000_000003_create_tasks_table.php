<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%tasks}}`.
 */
class m000000_000003_create_tasks_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%tasks}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'priority' => $this->string(20)->notNull()->defaultValue('medium')->comment('low, medium, high, critical'),
            'status' => $this->string(20)->notNull()->defaultValue('pending')->comment('pending, in_progress, completed, cancelled'),
            'deadline' => $this->integer()->notNull()->comment('Unix timestamp'),
            'completed_at' => $this->integer()->comment('Unix timestamp'),
            'assigned_to' => $this->integer()->comment('User ID'),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Create indexes
        $this->createIndex(
            'idx-tasks-category_id',
            '{{%tasks}}',
            'category_id'
        );

        $this->createIndex(
            'idx-tasks-deadline',
            '{{%tasks}}',
            'deadline'
        );

        $this->createIndex(
            'idx-tasks-status',
            '{{%tasks}}',
            'status'
        );

        $this->createIndex(
            'idx-tasks-priority',
            '{{%tasks}}',
            'priority'
        );

        // Add foreign key for category
        $this->addForeignKey(
            'fk-tasks-category_id',
            '{{%tasks}}',
            'category_id',
            '{{%task_categories}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-tasks-category_id', '{{%tasks}}');
        $this->dropTable('{{%tasks}}');
    }
}
