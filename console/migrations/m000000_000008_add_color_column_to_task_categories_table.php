<?php

use yii\db\Migration;

/**
 * Handles adding color column to table `{{%task_categories}}`.
 */
class m000000_000008_add_color_column_to_task_categories_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%task_categories}}', 'color', $this->string(7)->defaultValue('#007bff')->comment('Hex color code for category'));
        
        // Add index for better performance
        $this->createIndex(
            'idx-task_categories-color',
            '{{%task_categories}}',
            'color'
        );

        // Update existing categories with some default colors
        $defaultColors = [
            '#007bff', // Blue
            '#28a745', // Green  
            '#ffc107', // Yellow
            '#dc3545', // Red
            '#6f42c1', // Purple
            '#fd7e14', // Orange
            '#20c997', // Teal
            '#6c757d', // Gray
        ];

        $categories = $this->db->createCommand('SELECT id FROM {{%task_categories}} ORDER BY id')->queryAll();
        
        foreach ($categories as $index => $category) {
            $color = $defaultColors[$index % count($defaultColors)];
            $this->update('{{%task_categories}}', ['color' => $color], ['id' => $category['id']]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-task_categories-color', '{{%task_categories}}');
        $this->dropColumn('{{%task_categories}}', 'color');
    }
}