<?php

use yii\db\Migration;

/**
 * Handles adding parent_id to table `{{%task_categories}}`.
 */
class m000000_000005_add_parent_id_to_task_categories extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%task_categories}}', 'parent_id', $this->integer()->null()->after('id'));

        $this->createIndex(
            'idx-task_categories-parent_id',
            '{{%task_categories}}',
            'parent_id'
        );

        $this->addForeignKey(
            'fk-task_categories-parent_id',
            '{{%task_categories}}',
            'parent_id',
            '{{%task_categories}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-task_categories-parent_id', '{{%task_categories}}');
        $this->dropIndex('idx-task_categories-parent_id', '{{%task_categories}}');
        $this->dropColumn('{{%task_categories}}', 'parent_id');
    }
}
