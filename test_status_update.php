<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require 'vendor/autoload.php';
require 'vendor/yiisoft/yii2/Yii.php';
require 'common/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require 'common/config/main.php',
    require 'common/config/main-local.php',
    require 'console/config/main.php',
    require 'console/config/main-local.php'
);

$app = new yii\console\Application($config);

try {
    // Get a task to test with
    $task = \common\modules\taskmonitor\models\Task::find()->one();
    
    if (!$task) {
        echo "No tasks found to test with\n";
        exit;
    }
    
    echo "=== Testing Task Status Update ===\n";
    echo "Task ID: {$task->id}\n";
    echo "Current Status: {$task->status}\n";
    echo "Current Title: {$task->title}\n";
    
    // Get available columns
    $columns = \common\modules\kanban\models\KanbanColumn::find()
        ->where(['is_active' => 1])
        ->all();
    
    echo "\nAvailable status options:\n";
    foreach ($columns as $column) {
        echo "- {$column->status_key} ({$column->name})\n";
    }
    
    // Try to change status to a different one
    $newStatus = null;
    foreach ($columns as $column) {
        if ($column->status_key !== $task->status) {
            $newStatus = $column->status_key;
            break;
        }
    }
    
    if ($newStatus) {
        echo "\nChanging status from '{$task->status}' to '{$newStatus}'\n";
        
        $originalStatus = $task->status;
        $task->status = $newStatus;
        
        if ($task->save()) {
            echo "✅ Successfully updated task status!\n";
            echo "New status: {$task->status}\n";
            
            // Change it back
            $task->status = $originalStatus;
            if ($task->save()) {
                echo "✅ Successfully reverted task status back to: {$task->status}\n";
            }
        } else {
            echo "❌ Failed to update task status\n";
            echo "Errors: " . print_r($task->errors, true) . "\n";
        }
    } else {
        echo "\nNo alternative status found for testing\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}