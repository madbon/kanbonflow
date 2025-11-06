<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task_color_settings}}`.
 */
class m000000_000001_create_task_color_settings_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_color_settings}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull()->comment('Setting name (e.g., Critical, Warning, Safe)'),
            'days_before_deadline' => $this->integer()->notNull()->comment('Number of days before deadline'),
            'color' => $this->string(7)->notNull()->comment('Hex color code (e.g., #FF0000)'),
            'sort_order' => $this->integer()->defaultValue(0)->comment('Display order'),
            'is_active' => $this->boolean()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Insert default color settings
        $time = time();
        $this->batchInsert('{{%task_color_settings}}',
            ['name', 'days_before_deadline', 'color', 'sort_order', 'is_active', 'created_at', 'updated_at'],
            [
                ['Overdue', 0, '#D32F2F', 1, 1, $time, $time],
                ['Critical', 3, '#F57C00', 2, 1, $time, $time],
                ['Warning', 7, '#FBC02D', 3, 1, $time, $time],
                ['Upcoming', 14, '#1976D2', 4, 1, $time, $time],
                ['Safe', 30, '#388E3C', 5, 1, $time, $time],
            ]
        );

        $this->createIndex(
            'idx-task_color_settings-days_before_deadline',
            '{{%task_color_settings}}',
            'days_before_deadline'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%task_color_settings}}');
    }
}
