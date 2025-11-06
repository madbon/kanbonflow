<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%kanban_columns}}`.
 */
class m000000_000007_create_kanban_columns_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%kanban_columns}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull(),
            'status_key' => $this->string(50)->notNull()->unique(),
            'color' => $this->string(7)->defaultValue('#6c757d'),
            'icon' => $this->string(50)->defaultValue('fa fa-list'),
            'position' => $this->integer()->defaultValue(0),
            'is_active' => $this->boolean()->defaultValue(true),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Create indexes
        $this->createIndex(
            'idx-kanban_columns-status_key',
            '{{%kanban_columns}}',
            'status_key'
        );

        $this->createIndex(
            'idx-kanban_columns-position',
            '{{%kanban_columns}}',
            'position'
        );

        $this->createIndex(
            'idx-kanban_columns-is_active',
            '{{%kanban_columns}}',
            'is_active'
        );

        // Insert default columns
        $this->batchInsert('{{%kanban_columns}}', 
            ['name', 'status_key', 'color', 'icon', 'position', 'created_at', 'updated_at'],
            [
                ['To Do', 'pending', '#6c757d', 'fa fa-list', 1, time(), time()],
                ['In Progress', 'in_progress', '#ffc107', 'fa fa-cog fa-spin', 2, time(), time()],
                ['Done', 'completed', '#28a745', 'fa fa-check', 3, time(), time()],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%kanban_columns}}');
    }
}