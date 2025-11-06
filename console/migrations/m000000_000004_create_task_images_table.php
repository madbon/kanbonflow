<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task_images}}`.
 */
class m000000_000004_create_task_images_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_images}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull(),
            'filename' => $this->string(255)->notNull(),
            'original_name' => $this->string(255)->notNull(),
            'file_path' => $this->string(500)->notNull(),
            'file_size' => $this->integer()->notNull(),
            'mime_type' => $this->string(50),
            'sort_order' => $this->integer()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
        ]);

        // Create indexes
        $this->createIndex(
            'idx-task_images-task_id',
            '{{%task_images}}',
            'task_id'
        );

        // Add foreign key
        $this->addForeignKey(
            'fk-task_images-task_id',
            '{{%task_images}}',
            'task_id',
            '{{%tasks}}',
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
        $this->dropForeignKey('fk-task_images-task_id', '{{%task_images}}');
        $this->dropTable('{{%task_images}}');
    }
}
