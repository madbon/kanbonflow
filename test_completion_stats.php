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

echo "=== Testing Completion Statistics ===\n\n";

try {
    // Test the completion statistics method
    $completionStats = \common\modules\kanban\models\KanbanBoard::getCompletionStatistics();
    echo "Completion Statistics:\n";
    print_r($completionStats);
    
    echo "\n=== Testing Full Statistics (including completion) ===\n";
    $allStatistics = \common\modules\kanban\models\KanbanBoard::getStatistics();
    
    foreach ($allStatistics as $key => $stat) {
        echo "- {$key}: {$stat['count']} ({$stat['display_name']})\n";
        if ($key === 'completion_status') {
            echo "  -> Completed: {$stat['completed_count']}\n";
            echo "  -> Not Completed: {$stat['not_completed_count']}\n";
        }
    }
    
    echo "\n=== Testing Completion Tasks Data ===\n";
    $completionTasks = \common\modules\kanban\models\KanbanBoard::getCompletionTasks();
    echo "Completed tasks: " . count($completionTasks['completed']) . "\n";
    echo "Not completed tasks: " . count($completionTasks['not_completed']) . "\n";
    
    if (!empty($completionTasks['completed'])) {
        echo "\nSample completed task:\n";
        $task = $completionTasks['completed'][0];
        echo "- ID: {$task->id}, Title: {$task->title}, Status: {$task->status}\n";
    }
    
    if (!empty($completionTasks['not_completed'])) {
        echo "\nSample not completed task:\n";
        $task = $completionTasks['not_completed'][0];
        echo "- ID: {$task->id}, Title: {$task->title}, Status: {$task->status}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}