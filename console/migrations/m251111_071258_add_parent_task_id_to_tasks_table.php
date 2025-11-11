<?php

use yii\db\Migration;

/**
 * Class m251111_071258_add_parent_task_id_to_tasks_table
 */
class m251111_071258_add_parent_task_id_to_tasks_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add parent_task_id column for hierarchical task relationships
        $this->addColumn('{{%tasks}}', 'parent_task_id', $this->integer()->null()->comment('Parent task ID for hierarchical relationships'));
        
        // Add foreign key constraint to ensure parent task exists
        $this->addForeignKey(
            'fk-tasks-parent_task_id',
            '{{%tasks}}',
            'parent_task_id',
            '{{%tasks}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        // Add index for better performance on parent task queries
        $this->createIndex('idx-tasks-parent_task_id', '{{%tasks}}', 'parent_task_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign key first
        $this->dropForeignKey('fk-tasks-parent_task_id', '{{%tasks}}');
        
        // Drop index
        $this->dropIndex('idx-tasks-parent_task_id', '{{%tasks}}');
        
        // Drop the column
        $this->dropColumn('{{%tasks}}', 'parent_task_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251111_071258_add_parent_task_id_to_tasks_table cannot be reverted.\n";

        return false;
    }
    */
}
