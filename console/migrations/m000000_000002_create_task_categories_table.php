<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task_categories}}`.
 */
class m000000_000002_create_task_categories_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_categories}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'icon' => $this->string(50)->comment('Icon class (e.g., fa-folder)'),
            'sort_order' => $this->integer()->defaultValue(0),
            'is_active' => $this->boolean()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'idx-task_categories-is_active',
            '{{%task_categories}}',
            'is_active'
        );

        $this->createIndex(
            'idx-task_categories-sort_order',
            '{{%task_categories}}',
            'sort_order'
        );

        // Insert default categories
        $time = time();
        $this->batchInsert('{{%task_categories}}',
            ['name', 'description', 'icon', 'sort_order', 'is_active', 'created_at', 'updated_at'],
            [
                ['Development', 'Software development tasks', 'fa-code', 1, 1, $time, $time],
                ['Design', 'UI/UX design tasks', 'fa-paint-brush', 2, 1, $time, $time],
                ['Testing', 'QA and testing tasks', 'fa-bug', 3, 1, $time, $time],
                ['Documentation', 'Documentation tasks', 'fa-file-text', 4, 1, $time, $time],
                ['Meeting', 'Meeting and discussion tasks', 'fa-users', 5, 1, $time, $time],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%task_categories}}');
    }
}
