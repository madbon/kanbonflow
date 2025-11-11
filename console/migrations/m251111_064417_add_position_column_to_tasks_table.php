<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%tasks}}`.
 */
class m251111_064417_add_position_column_to_tasks_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add position column to tasks table
        $this->addColumn('{{%tasks}}', 'position', $this->integer()->defaultValue(0)->comment('Position order within status column'));
        
        // Add index for better performance on ordering
        $this->createIndex('idx-tasks-position', '{{%tasks}}', 'position');
        
        // Initialize position values for existing tasks based on creation order
        $this->execute("
            SET @row_number = 0;
            UPDATE {{%tasks}} 
            SET position = (@row_number:=@row_number+1) - 1
            ORDER BY status, created_at ASC;
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop the index first
        $this->dropIndex('idx-tasks-position', '{{%tasks}}');
        
        // Drop the position column
        $this->dropColumn('{{%tasks}}', 'position');
    }
}
