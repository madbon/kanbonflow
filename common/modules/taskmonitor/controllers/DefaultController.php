<?php

namespace common\modules\taskmonitor\controllers;

use Yii;
use yii\web\Controller;
use common\modules\taskmonitor\models\TaskCategory;
use common\modules\taskmonitor\models\Task;
use common\modules\taskmonitor\models\TaskImage;
use yii\web\UploadedFile;
use yii\web\Response;
use yii\helpers\Json;

/**
 * Default controller for the `taskmonitor` module
 */
class DefaultController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex($category_id = null, $subcategory_id = null, $task_id = null)
    {
        // Get only root categories (no parent)
        $rootCategories = TaskCategory::getRootCategories();

        $subcategories = [];
        $tasks = [];
        $selectedCategory = null;
        $selectedSubcategory = null;
        $selectedTask = null;

        if ($category_id) {
            $selectedCategory = TaskCategory::findOne($category_id);
            if ($selectedCategory) {
                // Get subcategories of selected category
                $subcategories = $selectedCategory->children;
            }
        }

        if ($subcategory_id) {
            $selectedSubcategory = TaskCategory::findOne($subcategory_id);
            if ($selectedSubcategory) {
                $tasks = Task::find()
                    ->where(['category_id' => $subcategory_id])
                    ->orderBy(['deadline' => SORT_ASC])
                    ->all();
            }
        }

        if ($task_id) {
            $selectedTask = Task::findOne($task_id);
        }

        return $this->render('index', [
            'rootCategories' => $rootCategories,
            'subcategories' => $subcategories,
            'tasks' => $tasks,
            'selectedCategory' => $selectedCategory,
            'selectedSubcategory' => $selectedSubcategory,
            'selectedTask' => $selectedTask,
        ]);
    }

    /**
     * Get subcategories by parent category via AJAX
     */
    public function actionGetSubcategories($category_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $category = TaskCategory::findOne($category_id);
        if (!$category) {
            return ['error' => 'Category not found'];
        }

        $subcategories = $category->children;
        $result = [];

        foreach ($subcategories as $sub) {
            $result[] = [
                'id' => $sub->id,
                'name' => $sub->name,
                'icon' => $sub->icon ?: 'fa-folder',
                'description' => $sub->description,
                'activeTasksCount' => $sub->getTotalActiveTasksCount(),
                'urgentTasksCount' => $sub->getUrgentTasksCount(30),
                'color' => $sub->getCategoryColor(),
            ];
        }

        return $result;
    }

    /**
     * Get tasks by category via AJAX
     */
    public function actionGetTasks($category_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $tasks = Task::find()
            ->where(['category_id' => $category_id])
            ->orderBy(['deadline' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($tasks as $task) {
            $result[] = [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'deadline' => $task->getFormattedDeadline(),
                'color' => $task->getTaskColor(),
                'daysUntil' => $task->getDaysUntilDeadline(),
                'isOverdue' => $task->isOverdue(),
            ];
        }

        return $result;
    }

    /**
     * Get task details via AJAX
     */
    public function actionGetTask($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $task = Task::findOne($id);
        if (!$task) {
            return ['error' => 'Task not found'];
        }

        $images = [];
        foreach ($task->images as $image) {
            $images[] = [
                'id' => $image->id,
                'url' => $image->getImageUrl(),
                'name' => $image->original_name,
                'size' => $image->getFormattedSize(),
            ];
        }

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'deadline' => date('Y-m-d\TH:i', $task->deadline),
            'category_id' => $task->category_id,
            'color' => $task->getTaskColor(),
            'images' => $images,
        ];
    }

    /**
     * Create or update task
     */
    public function actionSaveTask()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $id = $request->post('id');

        if ($id) {
            $task = Task::findOne($id);
            if (!$task) {
                return ['success' => false, 'message' => 'Task not found'];
            }
        } else {
            $task = new Task();
        }

        $task->category_id = $request->post('category_id');
        $task->title = $request->post('title');
        $task->description = $request->post('description');
        $task->priority = $request->post('priority', Task::PRIORITY_MEDIUM);
        $task->status = $request->post('status', Task::STATUS_PENDING);
        $task->deadline = strtotime($request->post('deadline'));

        if ($task->save()) {
            return ['success' => true, 'task_id' => $task->id];
        }

        return ['success' => false, 'errors' => $task->errors];
    }

    /**
     * Delete task
     */
    public function actionDeleteTask($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $task = Task::findOne($id);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        if ($task->delete()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Failed to delete task'];
    }

    /**
     * Upload image
     */
    public function actionUploadImage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $taskId = $request->post('task_id');

        if (!$taskId) {
            return ['success' => false, 'message' => 'Task ID is required'];
        }

        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }

        $uploadDir = Yii::getAlias('@webroot') . '/uploads/task-images/' . $taskId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $imageFile = UploadedFile::getInstanceByName('image');
        if (!$imageFile) {
            return ['success' => false, 'message' => 'No image uploaded'];
        }

        $filename = uniqid() . '.' . $imageFile->extension;
        $filePath = $uploadDir . '/' . $filename;

        if ($imageFile->saveAs($filePath)) {
            $taskImage = new TaskImage();
            $taskImage->task_id = $taskId;
            $taskImage->filename = $filename;
            $taskImage->original_name = $imageFile->name;
            $taskImage->file_path = 'uploads/task-images/' . $taskId . '/' . $filename;
            $taskImage->file_size = $imageFile->size;
            $taskImage->mime_type = $imageFile->type;

            if ($taskImage->save()) {
                return [
                    'success' => true,
                    'image' => [
                        'id' => $taskImage->id,
                        'url' => $taskImage->getImageUrl(),
                        'name' => $taskImage->original_name,
                    ],
                ];
            }
        }

        return ['success' => false, 'message' => 'Failed to upload image'];
    }

    /**
     * Delete image
     */
    public function actionDeleteImage($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $image = TaskImage::findOne($id);
        if (!$image) {
            return ['success' => false, 'message' => 'Image not found'];
        }

        if ($image->delete()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Failed to delete image'];
    }
}
