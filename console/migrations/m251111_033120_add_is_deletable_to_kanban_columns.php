<?php

use yii\db\Migration;

/**
 * Class m251111_033120_add_is_deletable_to_kanban_columns
 */
class m251111_033120_add_is_deletable_to_kanban_columns extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%kanban_columns}}', 'is_deletable', $this->boolean()->defaultValue(1)->notNull()->comment('Whether this column can be deleted'));
        
        // Create index for performance
        $this->createIndex('idx-kanban_columns-is_deletable', '{{%kanban_columns}}', 'is_deletable');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-kanban_columns-is_deletable', '{{%kanban_columns}}');
        $this->dropColumn('{{%kanban_columns}}', 'is_deletable');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251111_033120_add_is_deletable_to_kanban_columns cannot be reverted.\n";

        return false;
    }
    */
}
