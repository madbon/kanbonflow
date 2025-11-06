<?php

namespace common\modules\taskmonitor\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use common\models\User;

/**
 * This is the model class for table "task_comments".
 *
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property string $comment
 * @property int|null $parent_id
 * @property boolean $is_internal
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Task $task
 * @property User $user
 * @property TaskComment $parent
 * @property TaskComment[] $replies
 */
class TaskComment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_comments';
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
                'createdByAttribute' => 'user_id',
                'updatedByAttribute' => false,
                'value' => function () {
                    // Return actual user ID if logged in, otherwise null for guest users
                    return (Yii::$app->has('user') && !Yii::$app->user->isGuest) ? Yii::$app->user->id : null;
                },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['task_id', 'comment'], 'required'],
            [['task_id', 'user_id', 'parent_id', 'created_at', 'updated_at'], 'integer'],
            [['user_id'], 'default', 'value' => null], // Allow null user_id for guest comments
            [['comment'], 'string'],
            [['is_internal'], 'boolean'],
            [['is_internal'], 'default', 'value' => false],
            [['task_id'], 'exist', 'skipOnError' => true, 'targetClass' => Task::class, 'targetAttribute' => ['task_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id'], 'when' => function($model) { return !empty($model->user_id); }],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => TaskComment::class, 'targetAttribute' => ['parent_id' => 'id']],
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
            'comment' => 'Comment',
            'parent_id' => 'Parent ID',
            'is_internal' => 'Internal Comment',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
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
     * Gets query for [[Parent]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(TaskComment::class, ['id' => 'parent_id']);
    }

    /**
     * Gets query for [[Replies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getReplies()
    {
        return $this->hasMany(TaskComment::class, ['parent_id' => 'id'])
            ->orderBy(['created_at' => SORT_ASC]);
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
     * Get user avatar URL or initials
     * @return string
     */
    public function getUserAvatar()
    {
        if ($this->user && $this->user->username) {
            // Generate initials from username
            $words = explode(' ', trim($this->user->username));
            $initials = '';
            foreach ($words as $word) {
                $initials .= strtoupper(substr($word, 0, 1));
                if (strlen($initials) >= 2) break;
            }
            if (strlen($initials) < 2 && strlen($this->user->username) > 0) {
                $initials = strtoupper(substr($this->user->username, 0, 2));
            }
            return $initials ?: 'U';
        }
        return 'S'; // System
    }

    /**
     * Get comments for a specific task
     * @param int $taskId
     * @param bool $includeReplies
     * @return TaskComment[]
     */
    public static function getTaskComments($taskId, $includeReplies = true)
    {
        $query = self::find()
            ->where(['task_id' => $taskId])
            ->with(['user'])
            ->orderBy(['created_at' => SORT_DESC]);

        if (!$includeReplies) {
            $query->andWhere(['parent_id' => null]);
        }

        return $query->all();
    }

    /**
     * Get top-level comments (no parent) for a task
     * @param int $taskId
     * @return TaskComment[]
     */
    public static function getTopLevelComments($taskId)
    {
        return self::find()
            ->where(['task_id' => $taskId, 'parent_id' => null])
            ->with(['user', 'replies', 'replies.user'])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }

    /**
     * Create a new comment
     * @param int $taskId
     * @param string $comment
     * @param int|null $parentId
     * @param bool $isInternal
     * @return TaskComment|false
     */
    public static function addComment($taskId, $comment, $parentId = null, $isInternal = false)
    {
        $model = new self();
        $model->task_id = $taskId;
        $model->comment = $comment;
        $model->parent_id = $parentId;
        $model->is_internal = $isInternal;
        
        // Debug logging
        $userId = (Yii::$app->has('user') && !Yii::$app->user->isGuest) ? Yii::$app->user->id : 'guest';
        Yii::info("Creating comment - TaskID: $taskId, UserID: " . $userId . ", Comment length: " . strlen($comment), 'comment');
        
        // Debug: Check validation before save
        if (!$model->validate()) {
            Yii::error("Comment validation failed. Errors: " . json_encode($model->errors), 'comment');
            return false;
        }
        
        if ($model->save()) {
            Yii::info("Comment saved successfully - ID: " . $model->id, 'comment');
            
            // Log activity (only in web application context)
            if (class_exists('common\modules\taskmonitor\models\TaskHistory') && Yii::$app->has('user')) {
                TaskHistory::log(
                    $taskId,
                    TaskHistory::ACTION_UPDATED,
                    'Comment added: ' . substr($comment, 0, 100) . (strlen($comment) > 100 ? '...' : ''),
                    'comment',
                    null,
                    $comment
                );
            }
            
            return $model;
        }
        
        // Log validation errors
        Yii::error("Failed to save comment. Errors: " . json_encode($model->errors), 'comment');
        return false;
    }

    /**
     * Get comment count for a task
     * @param int $taskId
     * @return int
     */
    public static function getCommentCount($taskId)
    {
        return self::find()->where(['task_id' => $taskId])->count();
    }

    /**
     * Check if user can edit this comment
     * @param int|null $userId
     * @return bool
     */
    public function canEdit($userId = null)
    {
        if ($userId === null) {
            $userId = Yii::$app->user->id;
        }
        
        // User can edit their own comments within 1 hour
        return $this->user_id == $userId && (time() - $this->created_at) < 3600;
    }

    /**
     * Check if user can delete this comment
     * @param int|null $userId
     * @return bool
     */
    public function canDelete($userId = null)
    {
        if ($userId === null) {
            $userId = Yii::$app->user->id;
        }
        
        // User can delete their own comments, or admin can delete any
        return $this->user_id == $userId || Yii::$app->user->can('admin');
    }
}