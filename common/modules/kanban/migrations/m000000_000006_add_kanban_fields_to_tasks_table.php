<?php

use yii\db\Migration;

/**
 * Handles adding kanban-related fields to the tasks table.
 */
class m000000_000006_add_kanban_fields_to_tasks_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add position field for ordering tasks within columns
        $this->addColumn('{{%tasks}}', 'position', $this->integer()->defaultValue(0)->comment('Position within status column'));
        
        // Add index for position
        $this->createIndex(
            'idx-tasks-status-position',
            '{{%tasks}}',
            ['status', 'position']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-tasks-status-position', '{{%tasks}}');
        $this->dropColumn('{{%tasks}}', 'position');
    }
}