<?php

namespace common\modules\taskmonitor\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\helpers\Json;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\modules\taskmonitor\models\Task;
use common\modules\taskmonitor\models\TaskImage;

/**
 * ImageController handles task image uploads and management
 */
class ImageController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['upload', 'upload-clipboard', 'delete', 'list'],
                'rules' => [
                    [
                        'actions' => ['upload', 'upload-clipboard', 'delete', 'list'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                    'upload' => ['POST'],
                    'upload-clipboard' => ['POST'],
                    'list' => ['GET'],
                ],
            ],
        ];
    }

    /**
     * Upload image file from form input
     */
    public function actionUpload()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $taskId = Yii::$app->request->post('taskId');
            if (!$taskId) {
                return ['success' => false, 'message' => 'Task ID is required'];
            }
            
            $task = Task::findOne($taskId);
            if (!$task) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            $uploadedFile = UploadedFile::getInstanceByName('image');
            if (!$uploadedFile) {
                return ['success' => false, 'message' => 'No file uploaded'];
            }
            
            // Validate file type
            if (!$this->isValidImageType($uploadedFile->type)) {
                return ['success' => false, 'message' => 'Invalid file type. Only images are allowed.'];
            }
            
            // Validate file size (5MB max)
            if ($uploadedFile->size > 5 * 1024 * 1024) {
                return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
            }
            
            $result = $this->saveUploadedImage($task, $uploadedFile);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Image uploaded successfully',
                    'image' => $result['image']
                ];
            } else {
                return ['success' => false, 'message' => $result['message']];
            }
            
        } catch (\Exception $e) {
            Yii::error('Image upload error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Upload image from clipboard (base64 data)
     */
    public function actionUploadClipboard()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $taskId = Yii::$app->request->post('taskId');
            $imageData = Yii::$app->request->post('imageData');
            
            if (!$taskId) {
                return ['success' => false, 'message' => 'Task ID is required'];
            }
            
            if (!$imageData) {
                return ['success' => false, 'message' => 'Image data is required'];
            }
            
            $task = Task::findOne($taskId);
            if (!$task) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            // Parse base64 data
            if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $imageData, $matches)) {
                $imageType = $matches[1];
                $base64Data = $matches[2];
                
                // Validate image type
                $allowedTypes = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
                if (!in_array(strtolower($imageType), $allowedTypes)) {
                    return ['success' => false, 'message' => 'Invalid image type'];
                }
                
                $imageContent = base64_decode($base64Data);
                if ($imageContent === false) {
                    return ['success' => false, 'message' => 'Invalid base64 data'];
                }
                
                // Check file size (5MB max)
                if (strlen($imageContent) > 5 * 1024 * 1024) {
                    return ['success' => false, 'message' => 'Image too large. Maximum size is 5MB.'];
                }
                
                $result = $this->saveBase64Image($task, $imageContent, $imageType);
                
                if ($result['success']) {
                    return [
                        'success' => true,
                        'message' => 'Image uploaded from clipboard successfully',
                        'image' => $result['image']
                    ];
                } else {
                    return ['success' => false, 'message' => $result['message']];
                }
                
            } else {
                return ['success' => false, 'message' => 'Invalid image data format'];
            }
            
        } catch (\Exception $e) {
            Yii::error('Clipboard image upload error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * List images for a task
     */
    public function actionList($taskId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $task = Task::findOne($taskId);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found'];
        }
        
        $images = [];
        foreach ($task->images as $image) {
            $images[] = [
                'id' => $image->id,
                'filename' => $image->filename,
                'original_name' => $image->original_name,
                'url' => $image->getImageUrl(),
                'size' => $image->getFormattedSize(),
                'created_at' => date('M j, Y H:i', $image->created_at)
            ];
        }
        
        return ['success' => true, 'images' => $images];
    }
    
    /**
     * Delete an image
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $imageId = Yii::$app->request->post('imageId');
        if (!$imageId) {
            return ['success' => false, 'message' => 'Image ID is required'];
        }
        
        $image = TaskImage::findOne($imageId);
        if (!$image) {
            return ['success' => false, 'message' => 'Image not found'];
        }
        
        // Check if user can delete this image (task owner or admin)
        $task = $image->task;
        if ($task->created_by != Yii::$app->user->id && !Yii::$app->user->can('admin')) {
            return ['success' => false, 'message' => 'Access denied'];
        }
        
        if ($image->delete()) {
            return ['success' => true, 'message' => 'Image deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete image'];
        }
    }
    
    /**
     * Save uploaded file image
     */
    private function saveUploadedImage($task, $uploadedFile)
    {
        try {
            $uploadDir = Yii::getAlias('@webroot/uploads/tasks/' . $task->id);
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $filename = $this->generateUniqueFilename($uploadedFile->extension);
            $filePath = $uploadDir . '/' . $filename;
            $relativeePath = 'uploads/tasks/' . $task->id . '/' . $filename;
            
            if ($uploadedFile->saveAs($filePath)) {
                $taskImage = new TaskImage();
                $taskImage->task_id = $task->id;
                $taskImage->filename = $filename;
                $taskImage->original_name = $uploadedFile->name;
                $taskImage->file_path = $relativeePath;
                $taskImage->file_size = $uploadedFile->size;
                $taskImage->mime_type = $uploadedFile->type;
                $taskImage->sort_order = $this->getNextSortOrder($task->id);
                
                if ($taskImage->save()) {
                    return [
                        'success' => true,
                        'image' => [
                            'id' => $taskImage->id,
                            'filename' => $taskImage->filename,
                            'original_name' => $taskImage->original_name,
                            'url' => $taskImage->getImageUrl(),
                            'size' => $taskImage->getFormattedSize()
                        ]
                    ];
                } else {
                    unlink($filePath); // Clean up file if database save failed
                    return ['success' => false, 'message' => 'Failed to save image info'];
                }
            } else {
                return ['success' => false, 'message' => 'Failed to save file'];
            }
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Save base64 image data
     */
    private function saveBase64Image($task, $imageContent, $imageType)
    {
        try {
            $uploadDir = Yii::getAlias('@webroot/uploads/tasks/' . $task->id);
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $filename = $this->generateUniqueFilename($imageType);
            $filePath = $uploadDir . '/' . $filename;
            $relativeePath = 'uploads/tasks/' . $task->id . '/' . $filename;
            
            if (file_put_contents($filePath, $imageContent)) {
                $taskImage = new TaskImage();
                $taskImage->task_id = $task->id;
                $taskImage->filename = $filename;
                $taskImage->original_name = 'clipboard_' . date('Y-m-d_H-i-s') . '.' . $imageType;
                $taskImage->file_path = $relativeePath;
                $taskImage->file_size = strlen($imageContent);
                $taskImage->mime_type = 'image/' . $imageType;
                $taskImage->sort_order = $this->getNextSortOrder($task->id);
                
                if ($taskImage->save()) {
                    return [
                        'success' => true,
                        'image' => [
                            'id' => $taskImage->id,
                            'filename' => $taskImage->filename,
                            'original_name' => $taskImage->original_name,
                            'url' => $taskImage->getImageUrl(),
                            'size' => $taskImage->getFormattedSize()
                        ]
                    ];
                } else {
                    unlink($filePath); // Clean up file if database save failed
                    return ['success' => false, 'message' => 'Failed to save image info'];
                }
            } else {
                return ['success' => false, 'message' => 'Failed to save file'];
            }
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($extension)
    {
        return uniqid('img_') . '_' . time() . '.' . $extension;
    }
    
    /**
     * Get next sort order for task images
     */
    private function getNextSortOrder($taskId)
    {
        $maxOrder = TaskImage::find()
            ->where(['task_id' => $taskId])
            ->max('sort_order');
        
        return $maxOrder ? $maxOrder + 1 : 1;
    }
    
    /**
     * Check if file type is valid image
     */
    private function isValidImageType($mimeType)
    {
        $allowedTypes = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp'
        ];
        
        return in_array($mimeType, $allowedTypes);
    }
}