<?php

namespace common\modules\taskmonitor\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use common\models\User;

/**
 * This is the model class for table "task_history".
 *
 * @property int $id
 * @property int $task_id
 * @property int|null $user_id
 * @property string $action_type
 * @property string|null $field_name
 * @property string|null $old_value
 * @property string|null $new_value
 * @property string $description
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $old_values
 * @property int $created_at
 *
 * @property Task $task
 * @property User $user
 */
class TaskHistory extends ActiveRecord
{
    // Action types constants
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_STATUS_CHANGED = 'status_changed';
    const ACTION_POSITION_CHANGED = 'position_changed';
    const ACTION_PRIORITY_CHANGED = 'priority_changed';
    const ACTION_CATEGORY_CHANGED = 'category_changed';
    const ACTION_DEADLINE_CHANGED = 'deadline_changed';
    const ACTION_COMPLETED = 'completed';
    const ACTION_DELETED = 'deleted';
    const ACTION_RESTORED = 'restored';
    const ACTION_ASSIGNED = 'assigned';
    const ACTION_UNASSIGNED = 'unassigned';
    const ACTION_CHECKLIST_UPDATED = 'checklist_updated';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_history';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false, // Only need created_at
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['task_id', 'action_type', 'description'], 'required'],
            [['task_id', 'user_id', 'created_at'], 'integer'],
            [['old_value', 'new_value', 'description', 'user_agent', 'old_values'], 'string'],
            [['action_type'], 'string', 'max' => 50],
            [['field_name'], 'string', 'max' => 100],
            [['ip_address'], 'string', 'max' => 45],
            [['action_type'], 'in', 'range' => [
                self::ACTION_CREATED,
                self::ACTION_UPDATED,
                self::ACTION_STATUS_CHANGED,
                self::ACTION_POSITION_CHANGED,
                self::ACTION_PRIORITY_CHANGED,
                self::ACTION_CATEGORY_CHANGED,
                self::ACTION_DEADLINE_CHANGED,
                self::ACTION_COMPLETED,
                self::ACTION_DELETED,
                self::ACTION_RESTORED,
                self::ACTION_ASSIGNED,
                self::ACTION_UNASSIGNED,
                self::ACTION_CHECKLIST_UPDATED,
            ]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'Task ID',
            'user_id' => 'User ID',
            'action_type' => 'Action Type',
            'field_name' => 'Field Name',
            'old_value' => 'Old Value',
            'new_value' => 'New Value',
            'description' => 'Description',
            'ip_address' => 'IP Address',
            'user_agent' => 'User Agent',
            'old_values' => 'Old Values',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Task]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Get formatted action type for display
     * @return string
     */
    public function getActionTypeLabel()
    {
        $labels = [
            self::ACTION_CREATED => 'Created',
            self::ACTION_UPDATED => 'Updated',
            self::ACTION_STATUS_CHANGED => 'Status Changed',
            self::ACTION_POSITION_CHANGED => 'Position Changed',
            self::ACTION_PRIORITY_CHANGED => 'Priority Changed',
            self::ACTION_CATEGORY_CHANGED => 'Category Changed',
            self::ACTION_DEADLINE_CHANGED => 'Deadline Changed',
            self::ACTION_COMPLETED => 'Completed',
            self::ACTION_DELETED => 'Deleted',
            self::ACTION_RESTORED => 'Restored',
            self::ACTION_ASSIGNED => 'Assigned',
            self::ACTION_UNASSIGNED => 'Unassigned',
            self::ACTION_CHECKLIST_UPDATED => 'Checklist Updated',
        ];

        return isset($labels[$this->action_type]) ? $labels[$this->action_type] : $this->action_type;
    }

    /**
     * Get formatted timestamp
     * @return string
     */
    public function getFormattedDate()
    {
        return date('M j, Y \a\t g:i A', $this->created_at);
    }

    /**
     * Get relative time (e.g., "2 hours ago")
     * @return string
     */
    public function getRelativeTime()
    {
        $diff = time() - $this->created_at;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return $this->getFormattedDate();
        }
    }

    /**
     * Get user display name
     * @return string
     */
    public function getUserDisplayName()
    {
        if ($this->user) {
            return $this->user->username ? $this->user->username : 'Unknown User';
        }
        return 'System';
    }

    /**
     * Get CSS class for action type
     * @return string
     */
    public function getActionCssClass()
    {
        $classes = [
            self::ACTION_CREATED => 'text-success',
            self::ACTION_UPDATED => 'text-info',
            self::ACTION_STATUS_CHANGED => 'text-primary',
            self::ACTION_POSITION_CHANGED => 'text-secondary',
            self::ACTION_PRIORITY_CHANGED => 'text-warning',
            self::ACTION_CATEGORY_CHANGED => 'text-info',
            self::ACTION_DEADLINE_CHANGED => 'text-warning',
            self::ACTION_COMPLETED => 'text-success',
            self::ACTION_DELETED => 'text-danger',
            self::ACTION_RESTORED => 'text-success',
            self::ACTION_ASSIGNED => 'text-primary',
            self::ACTION_UNASSIGNED => 'text-secondary',
        ];

        return isset($classes[$this->action_type]) ? $classes[$this->action_type] : 'text-dark';
    }

    /**
     * Get icon for action type
     * @return string
     */
    public function getActionIcon()
    {
        $icons = [
            self::ACTION_CREATED => 'fa fa-plus-circle',
            self::ACTION_UPDATED => 'fa fa-edit',
            self::ACTION_STATUS_CHANGED => 'fa fa-exchange-alt',
            self::ACTION_POSITION_CHANGED => 'fa fa-arrows-alt',
            self::ACTION_PRIORITY_CHANGED => 'fa fa-flag',
            self::ACTION_CATEGORY_CHANGED => 'fa fa-tag',
            self::ACTION_DEADLINE_CHANGED => 'fa fa-calendar',
            self::ACTION_COMPLETED => 'fa fa-check-circle',
            self::ACTION_DELETED => 'fa fa-trash',
            self::ACTION_RESTORED => 'fa fa-undo',
            self::ACTION_ASSIGNED => 'fa fa-user-plus',
            self::ACTION_UNASSIGNED => 'fa fa-user-minus',
            self::ACTION_CHECKLIST_UPDATED => 'fa fa-list-ul',
        ];

        return isset($icons[$this->action_type]) ? $icons[$this->action_type] : 'fa fa-info-circle';
    }

    /**
     * Create a history entry
     * @param int $taskId
     * @param string $actionType
     * @param string $description
     * @param string|null $fieldName
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param int|null $userId
     * @return TaskHistory
     */
    public static function log($taskId, $actionType, $description, $fieldName = null, $oldValue = null, $newValue = null, $userId = null)
    {
        $history = new self();
        $history->task_id = $taskId;
        $history->action_type = $actionType;
        $history->description = $description;
        $history->field_name = $fieldName;
        $history->old_value = is_array($oldValue) ? json_encode($oldValue) : (string)$oldValue;
        $history->new_value = is_array($newValue) ? json_encode($newValue) : (string)$newValue;
        $history->user_id = $userId !== null ? $userId : (Yii::$app->has('user') && !Yii::$app->user->isGuest ? Yii::$app->user->id : null);
        
        // Capture request information if available (only for web requests)
        if (Yii::$app->has('request') && Yii::$app->request && Yii::$app->request instanceof \yii\web\Request) {
            $history->ip_address = Yii::$app->request->userIP;
            $history->user_agent = Yii::$app->request->userAgent;
        }
        
        $history->save();
        
        return $history;
    }

    /**
     * Get task history ordered by date
     * @param int $taskId
     * @param int $limit
     * @return TaskHistory[]
     */
    public static function getTaskHistory($taskId, $limit = 50)
    {
        return self::find()
            ->where(['task_id' => $taskId])
            ->with(['user'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }
    
    /**
     * Get all available action types with labels
     * @return array
     */
    public static function getActionTypes()
    {
        return [
            self::ACTION_CREATED => 'Task Created',
            self::ACTION_UPDATED => 'Task Updated',
            self::ACTION_STATUS_CHANGED => 'Status Changed',
            self::ACTION_POSITION_CHANGED => 'Position Changed',
            self::ACTION_PRIORITY_CHANGED => 'Priority Changed',
            self::ACTION_CATEGORY_CHANGED => 'Category Changed',
            self::ACTION_DEADLINE_CHANGED => 'Deadline Changed',
            self::ACTION_DELETED => 'Task Deleted',
            self::ACTION_ASSIGNED => 'Task Assigned',
            self::ACTION_UNASSIGNED => 'Task Unassigned',
            self::ACTION_COMPLETED => 'Task Completed',
            self::ACTION_RESTORED => 'Task Restored',
        ];
    }
}