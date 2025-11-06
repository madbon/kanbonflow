<?php
/**
 * Debug script to check task positions in database
 */

// Initialize Yii framework
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

require __DIR__ . '/common/config/bootstrap.php';
require __DIR__ . '/frontend/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/common/config/main.php',
    require __DIR__ . '/common/config/main-local.php',
    require __DIR__ . '/frontend/config/main.php',
    require __DIR__ . '/frontend/config/main-local.php'
);

$application = new yii\web\Application($config);

// Check tasks and their positions
try {
    $tasks = \common\modules\taskmonitor\models\Task::find()
        ->select(['id', 'title', 'status', 'position', 'created_at'])
        ->orderBy(['status' => SORT_ASC, 'position' => SORT_ASC])
        ->all();
    
    echo "=== CURRENT TASKS IN DATABASE ===\n";
    echo "ID\tTitle\t\t\tStatus\t\tPosition\tCreated\n";
    echo "--------------------------------------------------------------------\n";
    
    foreach ($tasks as $task) {
        $title = substr($task->title, 0, 20);
        $createdAt = date('Y-m-d H:i', $task->created_at);
        echo "{$task->id}\t{$title}\t\t{$task->status}\t\t{$task->position}\t\t{$createdAt}\n";
    }
    
    echo "\n=== TASKS GROUPED BY STATUS ===\n";
    $tasksByStatus = [];
    foreach ($tasks as $task) {
        $tasksByStatus[$task->status][] = $task;
    }
    
    foreach ($tasksByStatus as $status => $statusTasks) {
        echo "\n{$status}:\n";
        foreach ($statusTasks as $task) {
            echo "  - [{$task->position}] {$task->title}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}