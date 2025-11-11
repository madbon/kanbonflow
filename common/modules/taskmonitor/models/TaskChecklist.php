<?php

namespace common\modules\taskmonitor\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use common\models\User;

/**
 * This is the model class for table "task_checklist".
 *
 * @property int $id
 * @property int $task_id
 * @property string $step_text
 * @property boolean $is_completed
 * @property int $sort_order
 * @property int|null $completed_at
 * @property int|null $completed_by
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Task $task
 * @property User $completedByUser
 */
class TaskChecklist extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_checklist';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['task_id', 'step_text'], 'required'],
            [['task_id', 'sort_order', 'completed_at', 'completed_by', 'created_at', 'updated_at'], 'integer'],
            [['step_text'], 'string'],
            [['is_completed'], 'boolean'],
            [['step_text'], 'string', 'max' => 1000],
            [['sort_order'], 'integer', 'min' => 0],
            [['task_id'], 'exist', 'skipOnError' => true, 'targetClass' => Task::class, 'targetAttribute' => ['task_id' => 'id']],
            [['completed_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['completed_by' => 'id']],
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
            'step_text' => 'Step Text',
            'is_completed' => 'Is Completed',
            'sort_order' => 'Sort Order',
            'completed_at' => 'Completed At',
            'completed_by' => 'Completed By',
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
     * Gets query for [[CompletedByUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompletedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'completed_by']);
    }

    /**
     * Mark this checklist item as completed
     * 
     * @param int|null $userId ID of user who completed the item
     * @return bool
     */
    public function markCompleted($userId = null)
    {
        if ($this->is_completed) {
            return true; // Already completed
        }

        $this->is_completed = true;
        $this->completed_at = time();
        $this->completed_by = $userId !== null ? $userId : (Yii::$app->has('user') && !Yii::$app->user->isGuest ? Yii::$app->user->id : null);
        
        if ($this->save()) {
            // Log to task history
            TaskHistory::log(
                $this->task_id,
                TaskHistory::ACTION_CHECKLIST_UPDATED,
                'Checklist item completed: "' . substr($this->step_text, 0, 50) . (strlen($this->step_text) > 50 ? '...' : '') . '"',
                'checklist_item',
                'incomplete',
                'complete'
            );
            
            return true;
        }
        
        return false;
    }

    /**
     * Mark this checklist item as incomplete
     * 
     * @return bool
     */
    public function markIncomplete()
    {
        if (!$this->is_completed) {
            return true; // Already incomplete
        }

        $this->is_completed = false;
        $this->completed_at = null;
        $this->completed_by = null;
        
        if ($this->save()) {
            // Log to task history
            TaskHistory::log(
                $this->task_id,
                TaskHistory::ACTION_CHECKLIST_UPDATED,
                'Checklist item marked incomplete: "' . substr($this->step_text, 0, 50) . (strlen($this->step_text) > 50 ? '...' : '') . '"',
                'checklist_item',
                'complete',
                'incomplete'
            );
            
            return true;
        }
        
        return false;
    }

    /**
     * Toggle completion status of this checklist item
     * 
     * @param int|null $userId ID of user who is toggling the item
     * @return bool
     */
    public function toggleCompletion($userId = null)
    {
        if ($this->is_completed) {
            return $this->markIncomplete();
        } else {
            return $this->markCompleted($userId);
        }
    }

    /**
     * Get checklist items for a specific task ordered by sort_order
     * 
     * @param int $taskId
     * @return TaskChecklist[]
     */
    public static function getTaskChecklistItems($taskId)
    {
        return self::find()
            ->where(['task_id' => $taskId])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }

    /**
     * Get checklist completion percentage for a task
     * 
     * @param int $taskId
     * @return array ['total' => int, 'completed' => int, 'percentage' => float]
     */
    public static function getTaskChecklistProgress($taskId)
    {
        $total = self::find()->where(['task_id' => $taskId])->count();
        $completed = self::find()->where(['task_id' => $taskId, 'is_completed' => true])->count();
        
        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $total > 0 ? round(($completed / $total) * 100, 1) : 0
        ];
    }

    /**
     * Reorder checklist items for a task
     * 
     * @param int $taskId
     * @param array $itemIds Array of checklist item IDs in new order
     * @return bool
     */
    public static function reorderItems($taskId, $itemIds)
    {
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            foreach ($itemIds as $index => $itemId) {
                $item = self::find()
                    ->where(['id' => $itemId, 'task_id' => $taskId])
                    ->one();
                
                if ($item) {
                    $item->sort_order = $index;
                    if (!$item->save(false)) {
                        throw new \Exception('Failed to save sort order for item ' . $itemId);
                    }
                }
            }
            
            $transaction->commit();
            
            // Log to task history
            TaskHistory::log(
                $taskId,
                TaskHistory::ACTION_CHECKLIST_UPDATED,
                'Checklist items reordered',
                'checklist_order',
                '',
                implode(',', $itemIds)
            );
            
            return true;
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Failed to reorder checklist items: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get formatted completion time
     * 
     * @return string|null
     */
    public function getFormattedCompletionTime()
    {
        if (!$this->completed_at) {
            return null;
        }
        
        return date('M j, Y H:i', $this->completed_at);
    }

    /**
     * Get completed by user name
     * 
     * @return string
     */
    public function getCompletedByName()
    {
        if (!$this->completed_by || !$this->completedByUser) {
            return 'Unknown User';
        }
        
        if ($this->completedByUser->username) {
            return $this->completedByUser->username;
        }
        
        if ($this->completedByUser->email) {
            return $this->completedByUser->email;
        }
        
        return 'User #' . $this->completed_by;
    }
}