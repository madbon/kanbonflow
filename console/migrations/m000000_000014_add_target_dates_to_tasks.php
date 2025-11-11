<?php

use yii\db\Migration;

/**
 * Add target start and end date fields to tasks table
 */
class m000000_000014_add_target_dates_to_tasks extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('tasks', 'target_start_date', $this->integer()->null()->comment('Target start date timestamp'));
        $this->addColumn('tasks', 'target_end_date', $this->integer()->null()->comment('Target end date timestamp'));
        
        // Add indexes for better performance when querying by date ranges
        $this->createIndex('idx_tasks_target_start_date', 'tasks', 'target_start_date');
        $this->createIndex('idx_tasks_target_end_date', 'tasks', 'target_end_date');
        $this->createIndex('idx_tasks_target_date_range', 'tasks', ['target_start_date', 'target_end_date']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx_tasks_target_date_range', 'tasks');
        $this->dropIndex('idx_tasks_target_end_date', 'tasks');
        $this->dropIndex('idx_tasks_target_start_date', 'tasks');
        
        $this->dropColumn('tasks', 'target_end_date');
        $this->dropColumn('tasks', 'target_start_date');
    }
}