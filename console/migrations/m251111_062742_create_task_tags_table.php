<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task_tags}}`.
 */
class m251111_062742_create_task_tags_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_tags}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull(),
            'tag_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);

        // Create indexes
        $this->createIndex(
            'idx-task_tags-task_id',
            '{{%task_tags}}',
            'task_id'
        );

        $this->createIndex(
            'idx-task_tags-tag_id',
            '{{%task_tags}}',
            'tag_id'
        );

        // Create unique index to prevent duplicate task-tag relationships
        $this->createIndex(
            'idx-task_tags-unique',
            '{{%task_tags}}',
            ['task_id', 'tag_id'],
            true
        );

        // Add foreign keys
        $this->addForeignKey(
            'fk-task_tags-task_id',
            '{{%task_tags}}',
            'task_id',
            '{{%tasks}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-task_tags-tag_id',
            '{{%task_tags}}',
            'tag_id',
            '{{%tags}}',
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
        $this->dropForeignKey('fk-task_tags-task_id', '{{%task_tags}}');
        $this->dropForeignKey('fk-task_tags-tag_id', '{{%task_tags}}');
        $this->dropTable('{{%task_tags}}');
    }
}
