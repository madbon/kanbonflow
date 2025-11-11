<?php

namespace common\modules\kanban\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\modules\taskmonitor\models\Task;
use common\modules\taskmonitor\models\TaskCategory;
use common\modules\taskmonitor\models\TaskHistory;
use common\modules\taskmonitor\models\TaskChecklist;
use common\modules\kanban\models\KanbanBoard;
use common\modules\kanban\models\KanbanColumn;
use Exception;

/**
 * Board controller for the kanban module
 */
class BoardController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        // Disable CSRF validation for AJAX endpoints
        if (in_array($action->id, ['get-category-completion-tasks', 'get-completion-tasks', 'get-deadline-tasks'])) {
            $this->enableCsrfValidation = false;
        }
        
        return parent::beforeAction($action);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'update-task-status', 'update-task-position', 'update-column-position', 'add-column', 'edit-column', 'delete-column', 'add-task', 'get-task', 'edit-task', 'delete-task', 'get-task-details', 'get-task-history', 'get-deadline-tasks', 'get-targeted-today-tasks', 'get-completion-tasks', 'get-category-completion-tasks', 'get-task-checklist', 'add-checklist-item', 'update-checklist-item', 'delete-checklist-item', 'toggle-checklist-item', 'reorder-checklist-items'],
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'update-task-status' => ['POST'],
                    'update-task-position' => ['POST'],
                    'add-column' => ['POST'],
                    'edit-column' => ['POST'],
                    'delete-column' => ['POST'],
                    'add-task' => ['POST'],
                    'edit-task' => ['POST'],
                    'delete-task' => ['POST'],
                    'get-task' => ['GET'],
                    'get-task-details' => ['GET'],
                    'get-deadline-tasks' => ['GET'],
                    'get-targeted-today-tasks' => ['GET'],
                    'get-completion-tasks' => ['GET', 'POST'],
                    'get-category-completion-tasks' => ['GET', 'POST'],
                    'get-task-checklist' => ['GET'],
                    'add-checklist-item' => ['POST'],
                    'update-checklist-item' => ['POST'],
                    'delete-checklist-item' => ['POST'],
                    'toggle-checklist-item' => ['POST'],
                    'reorder-checklist-items' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Display the kanban board
     * @return string
     */
    public function actionIndex()
    {
        $categories = TaskCategory::find()->all();
        $columns = KanbanBoard::getActiveColumns();
        $tasks = KanbanBoard::getTasksByColumns();

        return $this->render('index', [
            'columns' => $columns,
            'tasks' => $tasks,
            'categories' => $categories,
        ]);
    }

    /**
     * Update task status via AJAX
     * @return array
     */
    public function actionUpdateTaskStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $taskId = Yii::$app->request->post('taskId');
        $newStatus = Yii::$app->request->post('status');
        
        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        // Validate status against existing kanban columns
        $validColumns = KanbanColumn::find()
            ->where(['is_active' => 1])
            ->select('status_key')
            ->column();
        
        if (!in_array($newStatus, $validColumns)) {
            return ['success' => false, 'message' => 'Invalid status - column not found'];
        }

        $task->status = $newStatus;
        
        // Special handling for completed status
        if ($newStatus === Task::STATUS_COMPLETED && !$task->completed_at) {
            $task->completed_at = time();
        } elseif ($newStatus !== Task::STATUS_COMPLETED && $task->completed_at) {
            $task->completed_at = null;
        }
        
        if ($task->save()) {
            return [
                'success' => true, 
                'message' => 'Task status updated successfully',
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status,
                    'completed_at' => $task->completed_at,
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to update task status'];
        }
    }

    /**
     * Update task position within a column (for future sorting feature)
     * @return array
     */
    public function actionUpdateTaskPosition()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $taskId = Yii::$app->request->post('taskId');
        $newPosition = Yii::$app->request->post('position');
        $status = Yii::$app->request->post('status');
        
        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        $oldStatus = $task->status;
        $oldPosition = $task->position;

        // Update task status if it changed
        if ($task->status !== $status) {
            $task->status = $status;
            
            // Special handling for completed status
            if ($status === Task::STATUS_COMPLETED && !$task->completed_at) {
                $task->completed_at = time();
            } elseif ($status !== Task::STATUS_COMPLETED && $task->completed_at) {
                $task->completed_at = null;
            }
        }

        // Update positions for all affected tasks
        $this->updateTaskPositions($task, $oldStatus, $newPosition, $status);

        if ($task->save()) {
            // Log the change to history
            $this->logTaskPositionChange($task, $oldStatus, $oldPosition, $status, $newPosition);
            
            return [
                'success' => true, 
                'message' => 'Task position updated successfully',
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status,
                    'position' => $task->position,
                    'completed_at' => $task->completed_at,
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to update task position'];
        }
    }

    /**
     * Update task positions when a task is moved
     */
    private function updateTaskPositions($movedTask, $oldStatus, $newPosition, $newStatus)
    {
        // If moving to a different status (column)
        if ($oldStatus !== $newStatus) {
            // Decrement positions in old column for tasks that were after the moved task
            Task::updateAllCounters(
                ['position' => -1],
                ['and', 
                    ['status' => $oldStatus],
                    ['>', 'position', $movedTask->position]
                ]
            );
            
            // Increment positions in new column for tasks at or after the new position
            Task::updateAllCounters(
                ['position' => 1],
                ['and',
                    ['status' => $newStatus],
                    ['>=', 'position', $newPosition]
                ]
            );
            
            // Set the moved task's new position
            $movedTask->position = $newPosition;
        } else {
            // Moving within the same column
            $oldPosition = $movedTask->position;
            
            if ($newPosition < $oldPosition) {
                // Moving up - increment positions between new and old position
                Task::updateAllCounters(
                    ['position' => 1],
                    ['and',
                        ['status' => $newStatus],
                        ['>=', 'position', $newPosition],
                        ['<', 'position', $oldPosition]
                    ]
                );
            } else if ($newPosition > $oldPosition) {
                // Moving down - decrement positions between old and new position
                Task::updateAllCounters(
                    ['position' => -1],
                    ['and',
                        ['status' => $newStatus],
                        ['>', 'position', $oldPosition],
                        ['<=', 'position', $newPosition]
                    ]
                );
            }
            
            // Set the moved task's new position
            $movedTask->position = $newPosition;
        }
    }

    /**
     * Add new column
     * @return array
     */
    public function actionAddColumn()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $name = Yii::$app->request->post('name');
        $color = Yii::$app->request->post('color');
        $icon = Yii::$app->request->post('icon');
        $isDeletable = Yii::$app->request->post('is_deletable', 1); // Default to 1 (deletable)
        

        
        if (empty($name)) {
            return ['success' => false, 'message' => 'Column name is required'];
        }

        $column = new KanbanColumn();
        $column->name = $name;
        $column->status_key = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
        $column->color = $color ?: '#6c757d';
        $column->icon = $icon ?: 'fa fa-list';
        $column->is_deletable = (int) $isDeletable;

        
        if ($column->save()) {
            return [
                'success' => true,
                'message' => 'Column added successfully',
                'column' => [
                    'id' => $column->id,
                    'name' => $column->name,
                    'status_key' => $column->status_key,
                    'color' => $column->color,
                    'icon' => $column->icon,
                    'is_deletable' => $column->is_deletable,
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to add column', 'errors' => $column->errors];
        }
    }

    /**
     * Edit existing column
     * @return array
     */
    public function actionEditColumn()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $id = Yii::$app->request->post('id');
        $name = Yii::$app->request->post('name');
        $color = Yii::$app->request->post('color');
        $icon = Yii::$app->request->post('icon');
        $isDeletable = Yii::$app->request->post('is_deletable');
        

        
        $column = KanbanColumn::findOne($id);
        if (!$column) {
            return ['success' => false, 'message' => 'Column not found'];
        }

        if (!empty($name)) {
            $column->name = $name;
        }
        if (!empty($color)) {
            $column->color = $color;
        }
        if (!empty($icon)) {
            $column->icon = $icon;
        }
        // Always set is_deletable since we handle it in JavaScript
        $column->is_deletable = (int) $isDeletable;
        
        if ($column->save()) {
            return [
                'success' => true,
                'message' => 'Column updated successfully',
                'column' => [
                    'id' => $column->id,
                    'name' => $column->name,
                    'status_key' => $column->status_key,
                    'color' => $column->color,
                    'icon' => $column->icon,
                    'is_deletable' => $column->is_deletable,
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to update column', 'errors' => $column->errors];
        }
    }

    /**
     * Delete column
     * @return array
     */
    public function actionDeleteColumn()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $id = Yii::$app->request->post('id');
        
        $column = KanbanColumn::findOne($id);
        if (!$column) {
            return ['success' => false, 'message' => 'Column not found'];
        }

        // Check if column is deletable
        if (!$column->is_deletable) {
            return ['success' => false, 'message' => 'This column cannot be deleted as it is marked as non-deletable.'];
        }

        // Check if column has tasks
        $taskCount = Task::find()->where(['status' => $column->status_key])->count();
        if ($taskCount > 0) {
            return ['success' => false, 'message' => "Cannot delete column with {$taskCount} tasks. Please move tasks to other columns first."];
        }

        if ($column->delete()) {
            return ['success' => true, 'message' => 'Column deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete column'];
        }
    }

    /**
     * Add new task
     * @return array
     */
    public function actionAddTask()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $title = Yii::$app->request->post('title');
        $description = Yii::$app->request->post('description');
        $categoryId = Yii::$app->request->post('category_id');
        $priority = Yii::$app->request->post('priority');
        $deadline = Yii::$app->request->post('deadline');
        $status = Yii::$app->request->post('status', 'pending'); // Default to pending
        $includeInExport = Yii::$app->request->post('include_in_export', 1); // Default to 1 (Yes)
        $targetStartDate = Yii::$app->request->post('target_start_date');
        $targetEndDate = Yii::$app->request->post('target_end_date');
        
        if (empty($title)) {
            return ['success' => false, 'message' => 'Task title is required'];
        }

        if (empty($categoryId)) {
            return ['success' => false, 'message' => 'Category is required'];
        }

        $task = new Task();
        $task->title = $title;
        $task->description = $description;
        $task->category_id = $categoryId;
        $task->priority = $priority ?: Task::PRIORITY_MEDIUM;
        $task->status = $status;
        $task->deadline = $deadline ? strtotime($deadline) : (time() + 7 * 24 * 60 * 60); // Default 7 days from now
        $task->include_in_export = (int) $includeInExport; // Ensure it's stored as integer
        $task->target_start_date = $targetStartDate ? strtotime($targetStartDate) : null;
        $task->target_end_date = $targetEndDate ? strtotime($targetEndDate) : null;
        
        // Set position to be at the end of the column
        $maxPosition = Task::find()
            ->where(['status' => $status])
            ->max('position');
        $task->position = $maxPosition !== null ? $maxPosition + 1 : 0;
        
        if ($task->save()) {
            return [
                'success' => true,
                'message' => 'Task added successfully',
                'task' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'category_name' => $task->category ? $task->category->name : 'No Category',
                    'deadline' => date('Y-m-d H:i', $task->deadline),
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to add task', 'errors' => $task->errors];
        }
    }

    /**
     * Get task details for editing
     * @return array
     */
    public function actionGetTask()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $id = Yii::$app->request->get('id');
        
        $task = Task::findOne($id);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        return [
            'success' => true,
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'category_id' => $task->category_id,
                'priority' => $task->priority,
                'status' => $task->status,
                'deadline' => $task->deadline ? date('Y-m-d\TH:i', $task->deadline) : '',
                'assigned_to' => $task->assigned_to,
                'include_in_export' => $task->include_in_export,
                'target_start_date' => $task->target_start_date ? date('Y-m-d', $task->target_start_date) : '',
                'target_end_date' => $task->target_end_date ? date('Y-m-d', $task->target_end_date) : '',
            ]
        ];
    }

    /**
     * Edit existing task
     * @return array
     */
    public function actionEditTask()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $id = Yii::$app->request->post('id');
        $title = Yii::$app->request->post('title');
        $description = Yii::$app->request->post('description');
        $categoryId = Yii::$app->request->post('category_id');
        $priority = Yii::$app->request->post('priority');
        $status = Yii::$app->request->post('status');
        $deadline = Yii::$app->request->post('deadline');
        $assignedTo = Yii::$app->request->post('assigned_to');
        $includeInExport = Yii::$app->request->post('include_in_export', 1); // Default to 1 if not provided
        $targetStartDate = Yii::$app->request->post('target_start_date');
        $targetEndDate = Yii::$app->request->post('target_end_date');
        
        $task = Task::findOne($id);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        // Validate status if provided
        if ($status) {
            $validColumn = KanbanColumn::find()
                ->where(['status_key' => $status, 'is_active' => 1])
                ->exists();
            if (!$validColumn) {
                return ['success' => false, 'message' => 'Invalid status: ' . $status];
            }
        }

        // Store original status for logging
        $originalStatus = $task->status;
        
        $task->title = $title;
        $task->description = $description;
        $task->category_id = $categoryId;
        $task->priority = $priority;
        $task->status = $status;
        $task->assigned_to = $assignedTo;
        $task->include_in_export = (int) $includeInExport; // Ensure it's stored as integer
        $task->target_start_date = $targetStartDate ? strtotime($targetStartDate) : null;
        $task->target_end_date = $targetEndDate ? strtotime($targetEndDate) : null;
        
        if ($deadline) {
            $task->deadline = strtotime($deadline);
        }

        // Log the status change for debugging
        \Yii::info("Task {$id} status update: '{$originalStatus}' -> '{$status}'", 'kanban');

        if ($task->save()) {
            return [
                'success' => true,
                'message' => 'Task updated successfully',
                'task' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'category_id' => $task->category_id,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'deadline' => $task->deadline ? date('Y-m-d\TH:i', $task->deadline) : '',
                    'assigned_to' => $task->assigned_to,
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to update task', 'errors' => $task->errors];
        }
    }

    /**
     * Delete task
     * @return array
     */
    public function actionDeleteTask()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $id = Yii::$app->request->post('id');
        
        $task = Task::findOne($id);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        if ($task->delete()) {
            return ['success' => true, 'message' => 'Task deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete task'];
        }
    }

    /**
     * Get full task details for the details modal
     * @return array
     */
    public function actionGetTaskDetails()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $id = Yii::$app->request->get('id');
        
        $task = Task::findOne($id);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        // Get task images
        $images = [];
        foreach ($task->images as $image) {
            $images[] = [
                'id' => $image->id,
                'url' => $image->getImageUrl(),
                'name' => $image->original_name,
                'size' => $image->getFormattedSize(),
            ];
        }

        // Format dates
        $createdAt = $task->created_at ? date('M j, Y g:i A', $task->created_at) : 'N/A';
        $updatedAt = $task->updated_at ? date('M j, Y g:i A', $task->updated_at) : 'N/A';
        $deadline = $task->deadline ? date('M j, Y g:i A', $task->deadline) : 'No deadline';
        $completedAt = $task->completed_at ? date('M j, Y g:i A', $task->completed_at) : null;
        $targetStartDate = $task->target_start_date ? date('M j, Y', $task->target_start_date) : null;
        $targetEndDate = $task->target_end_date ? date('M j, Y', $task->target_end_date) : null;

        // Get priority and status labels from the Task model to ensure consistency
        $priorityLabels = Task::getPriorityOptions();
        $statusLabels = Task::getStatusOptions();

        // Calculate days until deadline
        $daysUntilDeadline = null;
        $isOverdue = false;
        if ($task->deadline) {
            $now = time();
            $daysUntilDeadline = ceil(($task->deadline - $now) / (24 * 60 * 60));
            $isOverdue = $daysUntilDeadline < 0;
        }

        return [
            'success' => true,
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'status_label' => isset($statusLabels[$task->status]) ? $statusLabels[$task->status] : $task->status,
                'priority' => $task->priority,
                'priority_label' => isset($priorityLabels[$task->priority]) ? $priorityLabels[$task->priority] : $task->priority,
                'deadline' => $deadline,
                'deadline_timestamp' => $task->deadline,
                'days_until_deadline' => $daysUntilDeadline,
                'is_overdue' => $isOverdue,
                'completed_at' => $completedAt,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
                'target_start_date' => $targetStartDate,
                'target_end_date' => $targetEndDate,
                'target_start_timestamp' => $task->target_start_date,
                'target_end_timestamp' => $task->target_end_date,
                'assigned_to' => $task->assigned_to,
                'category' => $task->category ? [
                    'id' => $task->category->id,
                    'name' => $task->category->name,
                    'color' => $task->category->color,
                    'icon' => $task->category->icon,
                    'description' => $task->category->description,
                ] : null,
                'images' => $images,
                'checklist' => $this->getTaskChecklistForDetails($task->id),
            ]
        ];
    }

    /**
     * Test page for task details functionality
     */
    public function actionTest()
    {
        return $this->render('test');
    }

    /**
     * Create test data for debugging
     */
    public function actionCreateTestData()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            // Create test category
            $category = new TaskCategory();
            $category->name = 'Test Category';
            $category->color = '#3498db';
            $category->description = 'Test category for debugging';
            
            if (!$category->save()) {
                return ['success' => false, 'message' => 'Failed to create category', 'errors' => $category->getErrors()];
            }
            
            // Create test task
            $task = new Task();
            $task->title = 'Test Task for Details Modal';
            $task->description = "This is a test task created to verify the task details modal functionality.\n\nIt includes:\n- Multiple lines\n- Priority settings\n- Category assignment\n- Deadline settings";
            $task->status = 'pending';
            $task->priority = 'medium';
            $task->category_id = $category->id;
            $task->deadline = time() + (7 * 24 * 60 * 60); // 7 days from now
            $task->created_at = time();
            $task->updated_at = time();
            
            if (!$task->save()) {
                return ['success' => false, 'message' => 'Failed to create task', 'errors' => $task->getErrors()];
            }
            
            return [
                'success' => true, 
                'message' => 'Test data created successfully',
                'task_id' => $task->id,
                'category_id' => $category->id
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Update column position via AJAX
     */
    public function actionUpdateColumnPosition()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $columnId = Yii::$app->request->post('columnId');
        $newPosition = (int) Yii::$app->request->post('position');
        
        if (!$columnId || $newPosition < 0) {
            return ['success' => false, 'message' => 'Invalid column ID or position'];
        }
        
        $column = KanbanColumn::findOne($columnId);
        if (!$column) {
            return ['success' => false, 'message' => 'Column not found'];
        }
        
        $oldPosition = $column->position;
        
        // Update positions for all affected columns
        $this->updateColumnPositions($column, $oldPosition, $newPosition);
        
        $column->position = $newPosition;
        if ($column->save()) {
            return [
                'success' => true,
                'message' => 'Column position updated successfully',
                'column' => [
                    'id' => $column->id,
                    'name' => $column->name,
                    'position' => $column->position,
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to update column position'];
        }
    }
    
    /**
     * Update column positions when a column is moved
     */
    private function updateColumnPositions($movedColumn, $oldPosition, $newPosition)
    {
        if ($oldPosition === $newPosition) {
            return; // No change needed
        }
        
        if ($oldPosition < $newPosition) {
            // Moving column to the right - shift columns to the left
            KanbanColumn::updateAllCounters(
                ['position' => -1],
                [
                    'and',
                    ['>', 'position', $oldPosition],
                    ['<=', 'position', $newPosition],
                    ['!=', 'id', $movedColumn->id],
                    ['is_active' => 1]
                ]
            );
        } else {
            // Moving column to the left - shift columns to the right
            KanbanColumn::updateAllCounters(
                ['position' => 1],
                [
                    'and',
                    ['>=', 'position', $newPosition],
                    ['<', 'position', $oldPosition],
                    ['!=', 'id', $movedColumn->id],
                    ['is_active' => 1]
                ]
            );
        }
    }

    /**
     * Log task position/status changes to history
     */
    private function logTaskPositionChange($task, $oldStatus, $oldPosition, $newStatus, $newPosition)
    {
        $columnNames = $this->getColumnNames();
        
        // Log status change if different
        if ($oldStatus !== $newStatus) {
            $oldColumnName = isset($columnNames[$oldStatus]) ? $columnNames[$oldStatus] : ucfirst($oldStatus);
            $newColumnName = isset($columnNames[$newStatus]) ? $columnNames[$newStatus] : ucfirst($newStatus);
            
            TaskHistory::log(
                $task->id,
                TaskHistory::ACTION_STATUS_CHANGED,
                "Task moved from \"{$oldColumnName}\" to \"{$newColumnName}\"",
                'status',
                $oldStatus,
                $newStatus
            );
            
            // Special log for completion
            if ($newStatus === Task::STATUS_COMPLETED) {
                TaskHistory::log(
                    $task->id,
                    TaskHistory::ACTION_COMPLETED,
                    "Task was completed"
                );
            }
        }
        
        // Log position change if within same column
        if ($oldStatus === $newStatus && $oldPosition != $newPosition) {
            TaskHistory::log(
                $task->id,
                TaskHistory::ACTION_POSITION_CHANGED,
                "Task position changed from " . ($oldPosition + 1) . " to " . ($newPosition + 1) . " within \"{$columnNames[$newStatus]}\" column",
                'position',
                $oldPosition,
                $newPosition
            );
        }
    }
    
    /**
     * Get column names for history logging
     */
    private function getColumnNames()
    {
        static $columnNames = null;
        
        if ($columnNames === null) {
            $columnNames = [];
            $columns = KanbanColumn::getActiveColumns();
            foreach ($columns as $column) {
                $columnNames[$column->status_key] = $column->name;
            }
        }
        
        return $columnNames;
    }

    /**
     * Get task history via AJAX
     */
    public function actionGetTaskHistory()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $taskId = Yii::$app->request->get('taskId');
        \Yii::info("Getting task history for taskId: " . $taskId, 'kanban');
        
        if (!$taskId) {
            return ['success' => false, 'message' => 'Task ID is required'];
        }
        
        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }
        
        $history = TaskHistory::getTaskHistory($taskId, 50);
        \Yii::info("Found " . count($history) . " history entries for task " . $taskId, 'kanban');
        $historyData = [];
        
        foreach ($history as $entry) {
            $historyData[] = [
                'id' => $entry->id,
                'action_type' => $entry->action_type,
                'action_label' => $entry->getActionTypeLabel(),
                'description' => $entry->description,
                'field_name' => $entry->field_name,
                'old_value' => $entry->old_value,
                'new_value' => $entry->new_value,
                'user_name' => $entry->getUserDisplayName(),
                'formatted_date' => $entry->getFormattedDate(),
                'relative_time' => $entry->getRelativeTime(),
                'icon' => $entry->getActionIcon(),
                'css_class' => $entry->getActionCssClass(),
                'created_at' => $entry->created_at,
            ];
        }
        
        return [
            'success' => true,
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
            ],
            'history' => $historyData,
            'total_count' => count($historyData)
        ];
    }

    /**
     * Get tasks by deadline category via AJAX
     */
    public function actionGetDeadlineTasks()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $categoryKey = Yii::$app->request->get('category');
        
        if (!$categoryKey) {
            return ['success' => false, 'message' => 'Category is required'];
        }
        
        try {
            $tasks = KanbanBoard::getTasksByDeadlineCategory($categoryKey);
            $tasksData = [];
            $todayStart = strtotime('today');
            
            foreach ($tasks as $task) {
                // Calculate days until deadline
                $daysUntilDeadline = floor(($task->deadline - $todayStart) / 86400);
                
                $tasksData[] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'deadline' => $task->deadline,
                    'days_until_deadline' => $daysUntilDeadline,
                    'assigned_to_name' => null, // TODO: Add user relation if needed
                    'category_name' => $task->category ? $task->category->name : 'No Category',
                    'color' => $task->category ? $task->category->color : '#6c757d',
                    'icon' => $task->category ? $task->category->icon : 'fas fa-circle',
                ];
            }
            
            return [
                'success' => true,
                'tasks' => $tasksData,
                'category' => $categoryKey,
                'total_count' => count($tasksData)
            ];
            
        } catch (Exception $e) {
            Yii::error("Error getting deadline tasks: " . $e->getMessage(), 'kanban');
            return [
                'success' => false, 
                'message' => 'Error loading tasks: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get tasks targeted for today
     * @return array
     */
    public function actionGetTargetedTodayTasks()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $tasks = KanbanBoard::getTasksTargetedForToday();
            $tasksData = [];
            $todayStart = strtotime('today');
            
            foreach ($tasks as $task) {
                // Calculate days until deadline
                $daysUntilDeadline = floor(($task->deadline - $todayStart) / 86400);
                
                $tasksData[] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'deadline' => $task->deadline,
                    'target_start_date' => $task->target_start_date,
                    'target_end_date' => $task->target_end_date,
                    'days_until_deadline' => $daysUntilDeadline,
                    'assigned_to_name' => null, // TODO: Add user relation if needed
                    'category_name' => $task->category ? $task->category->name : 'No Category',
                    'color' => $task->category ? $task->category->color : '#6c757d',
                    'icon' => $task->category ? $task->category->icon : 'fas fa-circle',
                ];
            }
            
            return [
                'success' => true,
                'tasks' => $tasksData,
                'category' => 'targeted_today',
                'total_count' => count($tasksData)
            ];
            
        } catch (Exception $e) {
            Yii::error("Error getting targeted today tasks: " . $e->getMessage(), 'kanban');
            return [
                'success' => false, 
                'message' => 'Error loading tasks: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get tasks by completion status via AJAX
     */
    public function actionGetCompletionTasks()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $tasksData = KanbanBoard::getCompletionTasks();
            $formattedData = [
                'completed' => [],
                'not_completed' => []
            ];
            
            // Format completed tasks
            foreach ($tasksData['completed'] as $task) {
                $formattedData['completed'][] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'deadline' => $task->deadline,
                    'completed_at' => $task->completed_at,
                    'category_name' => $task->category ? $task->category->name : 'No Category',
                    'color' => $task->category ? $task->category->color : '#6c757d',
                    'icon' => $task->category ? $task->category->icon : 'fas fa-circle',
                ];
            }
            
            // Format not completed tasks  
            foreach ($tasksData['not_completed'] as $task) {
                $formattedData['not_completed'][] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'deadline' => $task->deadline,
                    'category_name' => $task->category ? $task->category->name : 'No Category',
                    'color' => $task->category ? $task->category->color : '#6c757d',
                    'icon' => $task->category ? $task->category->icon : 'fas fa-circle',
                ];
            }
            
            return [
                'success' => true,
                'tasks' => $formattedData,
                'completed_count' => count($formattedData['completed']),
                'not_completed_count' => count($formattedData['not_completed']),
                'total_count' => count($formattedData['completed']) + count($formattedData['not_completed'])
            ];
            
        } catch (Exception $e) {
            Yii::error("Error getting completion tasks: " . $e->getMessage(), 'kanban');
            return [
                'success' => false, 
                'message' => 'Error loading tasks: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get tasks by category and completion status via AJAX
     */
    public function actionGetCategoryCompletionTasks()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (!Yii::$app->request->isAjax) {
            return ['success' => false, 'message' => 'Invalid request'];
        }
        
        try {
            // Get categoryId from either GET or POST
            $categoryId = Yii::$app->request->get('categoryId') ?: Yii::$app->request->post('categoryId');
            
            // Convert 'null' string to actual null for uncategorized tasks
            if ($categoryId === 'null' || $categoryId === '') {
                $categoryId = null;
            } else {
                $categoryId = (int) $categoryId;
            }
            
            $tasksData = KanbanBoard::getCategoryCompletionTasks($categoryId);
            $formattedData = [
                'completed' => [],
                'not_completed' => []
            ];
            
            // Format completed tasks
            foreach ($tasksData['completed'] as $task) {
                $formattedData['completed'][] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'deadline' => $task->deadline,
                    'completed_at' => $task->completed_at,
                    'category_name' => $task->category ? $task->category->name : 'No Category',
                    'color' => $task->category ? $task->category->color : '#6c757d',
                    'icon' => $task->category ? $task->category->icon : 'fas fa-circle',
                ];
            }
            
            // Format not completed tasks  
            foreach ($tasksData['not_completed'] as $task) {
                $formattedData['not_completed'][] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'deadline' => $task->deadline,
                    'category_name' => $task->category ? $task->category->name : 'No Category',
                    'color' => $task->category ? $task->category->color : '#6c757d',
                    'icon' => $task->category ? $task->category->icon : 'fas fa-circle',
                ];
            }
            
            return [
                'success' => true,
                'tasks' => $formattedData,
                'completed_count' => count($formattedData['completed']),
                'not_completed_count' => count($formattedData['not_completed']),
                'total_count' => count($formattedData['completed']) + count($formattedData['not_completed'])
            ];
            
        } catch (Exception $e) {
            Yii::error("Error getting category completion tasks: " . $e->getMessage(), 'kanban');
            return [
                'success' => false, 
                'message' => 'Error loading category tasks: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get checklist items for a specific task
     */
    public function actionGetTaskChecklist()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $taskId = Yii::$app->request->get('taskId');
        
        if (!$taskId) {
            return ['success' => false, 'message' => 'Task ID is required'];
        }

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        $checklistItems = TaskChecklist::getTaskChecklistItems($taskId);
        $progress = TaskChecklist::getTaskChecklistProgress($taskId);

        $formattedItems = [];
        foreach ($checklistItems as $item) {
            $formattedItems[] = [
                'id' => $item->id,
                'step_text' => $item->step_text,
                'is_completed' => (bool)$item->is_completed,
                'sort_order' => $item->sort_order,
                'completed_at' => $item->completed_at ? date('M j, Y H:i', $item->completed_at) : null,
                'completed_by_name' => $item->getCompletedByName(),
            ];
        }

        return [
            'success' => true,
            'items' => $formattedItems,
            'progress' => $progress
        ];
    }

    /**
     * Add a new checklist item to a task
     */
    public function actionAddChecklistItem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $taskId = Yii::$app->request->post('taskId');
        $stepText = trim(Yii::$app->request->post('stepText'));
        
        if (!$taskId || !$stepText) {
            return ['success' => false, 'message' => 'Task ID and step text are required'];
        }

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        // Get the next sort order
        $maxSortOrder = TaskChecklist::find()
            ->where(['task_id' => $taskId])
            ->max('sort_order');
        
        $checklist = new TaskChecklist();
        $checklist->task_id = $taskId;
        $checklist->step_text = $stepText;
        $checklist->sort_order = $maxSortOrder !== null ? $maxSortOrder + 1 : 0;

        if ($checklist->save()) {
            // Log to task history
            TaskHistory::log(
                $taskId,
                TaskHistory::ACTION_CHECKLIST_UPDATED,
                'Checklist item added: "' . substr($stepText, 0, 50) . (strlen($stepText) > 50 ? '...' : '') . '"',
                'checklist_item',
                '',
                'added'
            );

            return [
                'success' => true,
                'message' => 'Checklist item added successfully',
                'item' => [
                    'id' => $checklist->id,
                    'step_text' => $checklist->step_text,
                    'is_completed' => false,
                    'sort_order' => $checklist->sort_order,
                    'completed_at' => null,
                    'completed_by_name' => null,
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to add checklist item', 'errors' => $checklist->errors];
        }
    }

    /**
     * Update text of an existing checklist item
     */
    public function actionUpdateChecklistItem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $itemId = Yii::$app->request->post('itemId');
        $stepText = trim(Yii::$app->request->post('stepText'));
        
        if (!$itemId || !$stepText) {
            return ['success' => false, 'message' => 'Item ID and step text are required'];
        }

        $item = TaskChecklist::findOne($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Checklist item not found'];
        }

        $oldText = $item->step_text;
        $item->step_text = $stepText;

        if ($item->save()) {
            // Log to task history
            TaskHistory::log(
                $item->task_id,
                TaskHistory::ACTION_CHECKLIST_UPDATED,
                'Checklist item updated: "' . substr($stepText, 0, 50) . (strlen($stepText) > 50 ? '...' : '') . '"',
                'checklist_item',
                substr($oldText, 0, 50),
                substr($stepText, 0, 50)
            );

            return [
                'success' => true,
                'message' => 'Checklist item updated successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to update checklist item', 'errors' => $item->errors];
        }
    }

    /**
     * Delete a checklist item
     */
    public function actionDeleteChecklistItem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $itemId = Yii::$app->request->post('itemId');
        
        if (!$itemId) {
            return ['success' => false, 'message' => 'Item ID is required'];
        }

        $item = TaskChecklist::findOne($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Checklist item not found'];
        }

        $taskId = $item->task_id;
        $stepText = $item->step_text;

        if ($item->delete()) {
            // Log to task history
            TaskHistory::log(
                $taskId,
                TaskHistory::ACTION_CHECKLIST_UPDATED,
                'Checklist item deleted: "' . substr($stepText, 0, 50) . (strlen($stepText) > 50 ? '...' : '') . '"',
                'checklist_item',
                'exists',
                'deleted'
            );

            return [
                'success' => true,
                'message' => 'Checklist item deleted successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to delete checklist item'];
        }
    }

    /**
     * Toggle completion status of a checklist item
     */
    public function actionToggleChecklistItem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $itemId = Yii::$app->request->post('itemId');
        
        if (!$itemId) {
            return ['success' => false, 'message' => 'Item ID is required'];
        }

        $item = TaskChecklist::findOne($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Checklist item not found'];
        }

        $userId = Yii::$app->has('user') && !Yii::$app->user->isGuest ? Yii::$app->user->id : null;
        
        if ($item->toggleCompletion($userId)) {
            $progress = TaskChecklist::getTaskChecklistProgress($item->task_id);
            
            return [
                'success' => true,
                'message' => 'Checklist item ' . ($item->is_completed ? 'completed' : 'marked incomplete'),
                'is_completed' => (bool)$item->is_completed,
                'completed_at' => $item->completed_at ? date('M j, Y H:i', $item->completed_at) : null,
                'completed_by_name' => $item->getCompletedByName(),
                'progress' => $progress
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to toggle checklist item'];
        }
    }

    /**
     * Reorder checklist items for a task
     */
    public function actionReorderChecklistItems()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $taskId = Yii::$app->request->post('taskId');
        $itemIds = Yii::$app->request->post('itemIds', []);
        
        if (!$taskId || !is_array($itemIds)) {
            return ['success' => false, 'message' => 'Task ID and item IDs array are required'];
        }

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        if (TaskChecklist::reorderItems($taskId, $itemIds)) {
            return [
                'success' => true,
                'message' => 'Checklist items reordered successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to reorder checklist items'];
        }
    }

    /**
     * Get formatted checklist data for task details
     */
    private function getTaskChecklistForDetails($taskId)
    {
        $items = TaskChecklist::getTaskChecklistItems($taskId);
        $progress = TaskChecklist::getTaskChecklistProgress($taskId);

        $formattedItems = [];
        foreach ($items as $item) {
            $formattedItems[] = [
                'id' => $item->id,
                'step_text' => $item->step_text,
                'is_completed' => (bool)$item->is_completed,
                'completed_at' => $item->getFormattedCompletionTime(),
                'completed_by_name' => $item->is_completed ? $item->getCompletedByName() : null,
            ];
        }

        return [
            'items' => $formattedItems,
            'progress' => $progress
        ];
    }
}