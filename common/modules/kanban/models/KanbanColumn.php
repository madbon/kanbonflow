<?php

namespace common\modules\kanban\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use common\modules\taskmonitor\models\Task;

/**
 * This is the model class for table "kanban_columns".
 *
 * @property int $id
 * @property string $name
 * @property string $status_key
 * @property string|null $color
 * @property string|null $icon
 * @property int|null $position
 * @property int|null $is_active
 * @property int|null $is_deletable
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Task[] $tasks
 */
class KanbanColumn extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kanban_columns';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'status_key'], 'required'],
            [['position', 'is_active', 'is_deletable', 'created_at', 'updated_at'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['status_key'], 'string', 'max' => 50],
            [['color'], 'string', 'max' => 7],
            [['icon'], 'string', 'max' => 50],
            [['status_key'], 'unique'],
            [['color'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/', 'message' => 'Color must be a valid hex color (e.g., #ff0000)'],
            [['is_active', 'is_deletable'], 'boolean'],
            [['is_active'], 'default', 'value' => 1],
            [['is_deletable'], 'default', 'value' => 1],
            [['position'], 'default', 'value' => 0],
            [['color'], 'default', 'value' => '#6c757d'],
            [['icon'], 'default', 'value' => 'fa fa-list'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Column Name',
            'status_key' => 'Status Key',
            'color' => 'Color',
            'icon' => 'Icon',
            'position' => 'Position',
            'is_active' => 'Is Active',
            'is_deletable' => 'Allow Delete',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Tasks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTasks()
    {
        return $this->hasMany(Task::className(), ['status' => 'status_key'])
            ->orderBy(['position' => SORT_ASC, 'created_at' => SORT_DESC]);
    }

    /**
     * Get all active columns ordered by position
     * @return array
     */
    public static function getActiveColumns()
    {
        return static::find()
            ->where(['is_active' => 1])
            ->orderBy(['position' => SORT_ASC])
            ->all();
    }

    /**
     * Get column configuration array
     * @return array
     */
    public static function getColumnsConfig()
    {
        $columns = static::getActiveColumns();
        $config = [];
        
        foreach ($columns as $column) {
            $config[$column->status_key] = [
                'title' => $column->name,
                'class' => 'kanban-column-' . str_replace('_', '-', $column->status_key),
                'icon' => $column->icon,
                'color' => $column->color,
            ];
        }
        
        return $config;
    }

    /**
     * Get next position for new column
     * @return int
     */
    public static function getNextPosition()
    {
        $maxPosition = static::find()->max('position');
        return $maxPosition ? $maxPosition + 1 : 1;
    }

    /**
     * Before save
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert && !$this->position) {
                $this->position = static::getNextPosition();
            }
            
            // Ensure status_key is lowercase and underscored
            $this->status_key = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $this->status_key));
            
            return true;
        }
        return false;
    }
}