<?php

// Bootstrap the application
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

require __DIR__ . '/common/config/bootstrap.php';
require __DIR__ . '/console/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/common/config/main.php',
    require __DIR__ . '/common/config/main-local.php',
    require __DIR__ . '/console/config/main.php',
    require __DIR__ . '/console/config/main-local.php'
);

$application = new yii\console\Application($config);

echo "=== Fixing Task Status Issues ===\n\n";

try {
    // Fix task 12 that has 'in_progress' but should be 'ongoing'
    $task12 = \common\modules\taskmonitor\models\Task::findOne(12);
    if ($task12) {
        echo "Task 12 current status: '{$task12->status}'\n";
        $task12->status = 'ongoing';
        if ($task12->save()) {
            echo "✅ Updated Task 12 status to 'ongoing'\n";
        } else {
            echo "❌ Failed to update Task 12: " . implode(', ', $task12->getFirstErrors()) . "\n";
        }
    }
    
    echo "\n=== Updated Tasks by Column ===\n";
    $tasksByColumns = \common\modules\kanban\models\KanbanBoard::getTasksByColumns();
    
    foreach ($tasksByColumns as $statusKey => $columnTasks) {
        if (count($columnTasks) > 0) {
            echo "Column '{$statusKey}': " . count($columnTasks) . " tasks\n";
            foreach ($columnTasks as $task) {
                echo "  - ID: {$task->id}, Title: {$task->title}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}