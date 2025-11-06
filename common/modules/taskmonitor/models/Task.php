<?php

namespace common\modules\taskmonitor\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "tasks".
 *
 * @property int $id
 * @property int $category_id
 * @property string $title
 * @property string $description
 * @property string $priority
 * @property string $status
 * @property int $deadline
 * @property int $completed_at
 * @property int $assigned_to
 * @property int $created_by
 * @property int $created_at
 * @property int $updated_at
 * @property int $position
 *
 * @property TaskCategory $category
 * @property TaskImage[] $images
 */
class Task extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tasks';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                // If no user is logged in (guest), provide a fallback value so DB NOT NULL
                // constraint on `created_by` doesn't fail. We use 1 as a reasonable
                // system/admin fallback — adjust if your app uses a different id.
                'value' => function () {
                    return (Yii::$app->has('user') && !Yii::$app->user->isGuest) ? Yii::$app->user->id : 1;
                },
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
            [['category_id', 'title', 'deadline'], 'required'],
            [['category_id', 'deadline', 'completed_at', 'assigned_to', 'created_by', 'created_at', 'updated_at', 'position'], 'integer'],
            [['description'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['priority'], 'string', 'max' => 20],
            [['status'], 'string', 'max' => 20],
            [['priority'], 'in', 'range' => [self::PRIORITY_LOW, self::PRIORITY_MEDIUM, self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED, self::STATUS_CANCELLED]],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => TaskCategory::className(), 'targetAttribute' => ['category_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Category',
            'title' => 'Task Title',
            'description' => 'Description',
            'priority' => 'Priority',
            'status' => 'Status',
            'deadline' => 'Deadline',
            'completed_at' => 'Completed At',
            'assigned_to' => 'Assigned To',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'position' => 'Position',
        ];
    }

    /**
     * Gets query for [[Category]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(TaskCategory::className(), ['id' => 'category_id']);
    }

    /**
     * Gets query for [[Images]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getImages()
    {
        return $this->hasMany(TaskImage::className(), ['task_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
    }

    /**
     * Get status options
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get priority options
     */
    public static function getPriorityOptions()
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_CRITICAL => 'Critical',
        ];
    }

    /**
     * Get days until deadline
     */
    public function getDaysUntilDeadline()
    {
        $now = time();
        return floor(($this->deadline - $now) / 86400);
    }

    /**
     * Get task color based on deadline
     */
    public function getTaskColor()
    {
        return TaskColorSetting::getColorForDeadline($this->deadline);
    }

    /**
     * Get formatted deadline
     */
    public function getFormattedDeadline()
    {
        return date('Y-m-d H:i', $this->deadline);
    }

    /**
     * Check if task is overdue
     */
    public function isOverdue()
    {
        return $this->deadline < time() && $this->status !== self::STATUS_COMPLETED;
    }

    /**
     * Before save
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Set completed_at when status changes to completed
            if ($this->status === self::STATUS_COMPLETED && !$this->completed_at) {
                $this->completed_at = time();
            }

            // Clear completed_at if status changes from completed
            if ($this->status !== self::STATUS_COMPLETED && $this->completed_at) {
                $this->completed_at = null;
            }

            return true;
        }
        return false;
    }
}
