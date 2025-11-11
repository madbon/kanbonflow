<?php

use yii\db\Migration;

/**
 * Class m000000_000012_add_include_in_export_to_tasks_table
 */
class m000000_000012_add_include_in_export_to_tasks_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%tasks}}', 'include_in_export', $this->boolean()->defaultValue(1)->comment('Whether to include this task in activity log exports (1=Yes, 0=No)'));
        
        // Add index for better performance when filtering export data
        $this->createIndex('idx_tasks_include_in_export', '{{%tasks}}', 'include_in_export');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx_tasks_include_in_export', '{{%tasks}}');
        $this->dropColumn('{{%tasks}}', 'include_in_export');
    }
}