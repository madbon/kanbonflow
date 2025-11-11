<?php

namespace common\modules\taskmonitor\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "tags".
 *
 * @property int $id
 * @property string $name
 * @property string $color
 * @property string $description
 * @property int $created_by
 * @property int $created_at
 * @property int $updated_at
 *
 * @property TaskTag[] $taskTags
 * @property Task[] $tasks
 */
class Tag extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tags';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => false,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['description'], 'string'],
            [['created_by', 'created_at', 'updated_at'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['color'], 'string', 'max' => 7],
            [['color'], 'match', 'pattern' => '/^#[0-9a-fA-F]{6}$/', 'message' => 'Color must be a valid hex color code (e.g., #007bff)'],
            [['name'], 'unique'],
            [['color'], 'default', 'value' => '#007bff'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Tag Name',
            'color' => 'Color',
            'description' => 'Description',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[TaskTags]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTaskTags()
    {
        return $this->hasMany(TaskTag::class, ['tag_id' => 'id']);
    }

    /**
     * Gets query for [[Tasks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTasks()
    {
        return $this->hasMany(Task::class, ['id' => 'task_id'])
            ->viaTable('task_tags', ['tag_id' => 'id']);
    }

    /**
     * Get all tags for dropdown/select
     * @return array
     */
    public static function getTagOptions()
    {
        return static::find()
            ->select(['id', 'name', 'color'])
            ->orderBy('name ASC')
            ->asArray()
            ->all();
    }

    /**
     * Get tag display badge HTML
     * @return string
     */
    public function getBadgeHtml()
    {
        return '<span class="badge tag-badge" style="background-color: ' . $this->color . '">' 
               . \yii\helpers\Html::encode($this->name) . '</span>';
    }

    /**
     * Get task count for this tag
     * @return int
     */
    public function getTaskCount()
    {
        return $this->getTaskTags()->count();
    }
}