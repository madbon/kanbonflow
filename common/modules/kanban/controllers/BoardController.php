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
                        'actions' => ['index', 'update-task-status', 'update-task-position', 'add-column', 'edit-column', 'delete-column', 'add-task', 'get-task', 'edit-task', 'delete-task'],
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

        // Validate status
        $validStatuses = [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_COMPLETED];
        if (!in_array($newStatus, $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        $task->status = $newStatus;
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
        $position = Yii::$app->request->post('position');
        $status = Yii::$app->request->post('status');
        
        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        // Update task status if it changed
        if ($task->status !== $status) {
            $task->status = $status;
        }

        if ($task->save()) {
            return [
                'success' => true, 
                'message' => 'Task position updated successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to update task position'];
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
        $task->position = 0;
        
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
}