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

echo "=== Creating Test Completed Tasks ===\n\n";

try {
    // Move some tasks to completed status for testing
    $task17 = \common\modules\taskmonitor\models\Task::findOne(17);
    $task18 = \common\modules\taskmonitor\models\Task::findOne(18);
    
    if ($task17) {
        $task17->status = 'completed';
        $task17->completed_at = time();
        if ($task17->save()) {
            echo "✅ Task 17 moved to completed status\n";
        } else {
            echo "❌ Failed to update Task 17: " . implode(', ', $task17->getFirstErrors()) . "\n";
        }
    }
    
    if ($task18) {
        $task18->status = 'completed';
        $task18->completed_at = time();
        if ($task18->save()) {
            echo "✅ Task 18 moved to completed status\n";
        } else {
            echo "❌ Failed to update Task 18: " . implode(', ', $task18->getFirstErrors()) . "\n";
        }
    }
    
    echo "\n=== Updated Completion Statistics ===\n";
    $completionStats = \common\modules\kanban\models\KanbanBoard::getCompletionStatistics();
    
    $stats = $completionStats['completion_status'];
    echo "Total tasks: {$stats['count']}\n";
    echo "Completed tasks: {$stats['completed_count']}\n";
    echo "Not completed tasks: {$stats['not_completed_count']}\n";
    
    echo "\n=== Testing completion tasks data ===\n";
    $completionTasks = \common\modules\kanban\models\KanbanBoard::getCompletionTasks();
    echo "Completed tasks in data: " . count($completionTasks['completed']) . "\n";
    echo "Not completed tasks in data: " . count($completionTasks['not_completed']) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}