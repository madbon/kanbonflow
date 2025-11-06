<?php

namespace common\modules\taskmonitor\behaviors;

use Yii;
use yii\db\ActiveRecord;
use yii\base\Behavior;
use common\modules\taskmonitor\models\TaskHistory;

/**
 * TaskHistoryBehavior automatically logs changes to tasks
 */
class TaskHistoryBehavior extends Behavior
{
    /**
     * @var array fields to track for changes
     */
    public $trackedFields = [
        'title',
        'description', 
        'priority',
        'status',
        'deadline',
        'category_id',
        'assigned_to',
        'position'
    ];

    /**
     * @var array stores old values before update
     */
    private $_oldValues = [];

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     * Log task creation
     */
    public function afterInsert($event)
    {
        TaskHistory::log(
            $this->owner->id,
            TaskHistory::ACTION_CREATED,
            'Task "' . $this->owner->title . '" was created'
        );
    }

    /**
     * Store old values before update
     */
    public function beforeUpdate($event)
    {
        $this->_oldValues = [];
        foreach ($this->trackedFields as $field) {
            if (isset($this->owner->oldAttributes[$field])) {
                $this->_oldValues[$field] = $this->owner->oldAttributes[$field];
            }
        }
    }

    /**
     * Log changes after update
     */
    public function afterUpdate($event)
    {
        foreach ($this->trackedFields as $field) {
            $oldValue = isset($this->_oldValues[$field]) ? $this->_oldValues[$field] : null;
            $newValue = $this->owner->getAttribute($field);

            if ($oldValue != $newValue) {
                $this->logFieldChange($field, $oldValue, $newValue);
            }
        }
    }

    /**
     * Log task deletion
     */
    public function beforeDelete($event)
    {
        TaskHistory::log(
            $this->owner->id,
            TaskHistory::ACTION_DELETED,
            'Task "' . $this->owner->title . '" was deleted'
        );
    }

    /**
     * Log specific field changes
     */
    protected function logFieldChange($field, $oldValue, $newValue)
    {
        $actionType = TaskHistory::ACTION_UPDATED;
        $description = '';

        switch ($field) {
            case 'title':
                $description = 'Title changed from "' . $oldValue . '" to "' . $newValue . '"';
                break;
            
            case 'description':
                $description = 'Description was updated';
                break;
            
            case 'priority':
                $actionType = TaskHistory::ACTION_PRIORITY_CHANGED;
                $description = 'Priority changed from "' . ucfirst($oldValue) . '" to "' . ucfirst($newValue) . '"';
                break;
            
            case 'status':
                $actionType = TaskHistory::ACTION_STATUS_CHANGED;
                $statusLabels = $this->getStatusLabels();
                $oldLabel = isset($statusLabels[$oldValue]) ? $statusLabels[$oldValue] : $oldValue;
                $newLabel = isset($statusLabels[$newValue]) ? $statusLabels[$newValue] : $newValue;
                $description = 'Status changed from "' . $oldLabel . '" to "' . $newLabel . '"';
                
                // Special case for completion
                if ($newValue === 'completed') {
                    $actionType = TaskHistory::ACTION_COMPLETED;
                    $description = 'Task was completed';
                }
                break;
            
            case 'deadline':
                $actionType = TaskHistory::ACTION_DEADLINE_CHANGED;
                $oldDate = $oldValue ? date('M j, Y', $oldValue) : 'No deadline';
                $newDate = $newValue ? date('M j, Y', $newValue) : 'No deadline';
                $description = 'Deadline changed from "' . $oldDate . '" to "' . $newDate . '"';
                break;
            
            case 'category_id':
                $actionType = TaskHistory::ACTION_CATEGORY_CHANGED;
                $oldCategory = $this->getCategoryName($oldValue);
                $newCategory = $this->getCategoryName($newValue);
                $description = 'Category changed from "' . $oldCategory . '" to "' . $newCategory . '"';
                break;
            
            case 'assigned_to':
                if ($newValue && !$oldValue) {
                    $actionType = TaskHistory::ACTION_ASSIGNED;
                    $userName = $this->getUserName($newValue);
                    $description = 'Task assigned to ' . $userName;
                } elseif (!$newValue && $oldValue) {
                    $actionType = TaskHistory::ACTION_UNASSIGNED;
                    $userName = $this->getUserName($oldValue);
                    $description = 'Task unassigned from ' . $userName;
                } else {
                    $actionType = TaskHistory::ACTION_ASSIGNED;
                    $oldUser = $this->getUserName($oldValue);
                    $newUser = $this->getUserName($newValue);
                    $description = 'Task reassigned from ' . $oldUser . ' to ' . $newUser;
                }
                break;
            
            case 'position':
                $actionType = TaskHistory::ACTION_POSITION_CHANGED;
                $description = 'Position changed from ' . ($oldValue + 1) . ' to ' . ($newValue + 1);
                break;
            
            default:
                $description = ucfirst(str_replace('_', ' ', $field)) . ' changed';
                break;
        }

        TaskHistory::log(
            $this->owner->id,
            $actionType,
            $description,
            $field,
            $oldValue,
            $newValue
        );
    }

    /**
     * Get status labels
     */
    protected function getStatusLabels()
    {
        return [
            'pending' => 'To Do',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    /**
     * Get category name by ID
     */
    protected function getCategoryName($categoryId)
    {
        if (!$categoryId) {
            return 'No Category';
        }

        $category = \common\modules\taskmonitor\models\TaskCategory::findOne($categoryId);
        return $category ? $category->name : 'Unknown Category';
    }

    /**
     * Get user name by ID
     */
    protected function getUserName($userId)
    {
        if (!$userId) {
            return 'Unknown User';
        }

        $user = \common\models\User::findOne($userId);
        return $user ? $user->username : 'Unknown User';
    }

    /**
     * Log custom action (for manual logging from controllers)
     */
    public function logCustomAction($actionType, $description, $fieldName = null, $oldValue = null, $newValue = null)
    {
        TaskHistory::log(
            $this->owner->id,
            $actionType,
            $description,
            $fieldName,
            $oldValue,
            $newValue
        );
    }
}