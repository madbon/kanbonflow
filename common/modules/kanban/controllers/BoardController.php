<?php

namespace common\modules\kanban\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\modules\taskmonitor\models\Task;
use common\modules\taskmonitor\models\TaskCategory;
use common\modules\kanban\models\KanbanBoard;
use common\modules\kanban\models\KanbanColumn;

/**
 * Board controller for the kanban module
 */
class BoardController extends Controller
{
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
                        'actions' => ['index', 'update-task-status', 'update-task-position', 'update-column-position', 'add-column', 'edit-column', 'delete-column', 'add-task', 'get-task', 'edit-task', 'delete-task', 'get-task-details'],
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
        
        if (empty($name)) {
            return ['success' => false, 'message' => 'Column name is required'];
        }

        $column = new KanbanColumn();
        $column->name = $name;
        $column->status_key = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
        $column->color = $color ?: '#6c757d';
        $column->icon = $icon ?: 'fa fa-list';
        
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
        $deadline = Yii::$app->request->post('deadline');
        $assignedTo = Yii::$app->request->post('assigned_to');
        
        $task = Task::findOne($id);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        $task->title = $title;
        $task->description = $description;
        $task->category_id = $categoryId;
        $task->priority = $priority;
        $task->assigned_to = $assignedTo;
        
        if ($deadline) {
            $task->deadline = strtotime($deadline);
        }

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

        // Get priority and status labels
        $priorityLabels = [
            'low' => 'Low',
            'medium' => 'Medium', 
            'high' => 'High',
            'critical' => 'Critical'
        ];

        $statusLabels = [
            'pending' => 'To Do',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ];

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
                'assigned_to' => $task->assigned_to,
                'category' => $task->category ? [
                    'id' => $task->category->id,
                    'name' => $task->category->name,
                    'color' => $task->category->color,
                    'icon' => $task->category->icon,
                    'description' => $task->category->description,
                ] : null,
                'images' => $images,
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
            $category = new \common\models\TaskCategory();
            $category->name = 'Test Category';
            $category->color = '#3498db';
            $category->description = 'Test category for debugging';
            
            if (!$category->save()) {
                return ['success' => false, 'message' => 'Failed to create category', 'errors' => $category->getErrors()];
            }
            
            // Create test task
            $task = new \common\models\Task();
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
}