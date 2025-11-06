<?php

use yii\db\Migration;

/**
 * Handles adding old_values column to table `{{%task_history}}`.
 */
class m000000_000011_add_old_values_column_to_task_history_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%task_history}}', 'old_values', $this->text()->null()->comment('JSON encoded old values for complex changes'));
        
        // Add index for better query performance when filtering
        $this->createIndex(
            '{{%idx-task_history-old_values}}',
            '{{%task_history}}',
            'old_values'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop index first
        $this->dropIndex(
            '{{%idx-task_history-old_values}}',
            '{{%task_history}}'
        );
        
        $this->dropColumn('{{%task_history}}', 'old_values');
    }
}