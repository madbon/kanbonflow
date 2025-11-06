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
    echo "=== Testing getTasksByDeadlineCategory ===\n";
    
    // Test due_today category
    $tasks = \common\modules\kanban\models\KanbanBoard::getTasksByDeadlineCategory('due_today');
    
    echo "Due today tasks found: " . count($tasks) . "\n";
    
    foreach ($tasks as $task) {
        echo "Task {$task->id}: {$task->title}\n";
        echo "  - Status: {$task->status}\n";
        echo "  - Priority: {$task->priority}\n";
        echo "  - Deadline: " . date('Y-m-d H:i:s', $task->deadline) . "\n";
        $todayStart = strtotime('today');
        $daysUntil = floor(($task->deadline - $todayStart) / 86400);
        echo "  - Days until deadline: $daysUntil\n";
        echo "  - Category: " . ($task->category ? $task->category->name : 'No Category') . "\n";
        echo "\n";
    }
    
    // Test overdue category
    echo "=== Testing overdue category ===\n";
    $overdueTasks = \common\modules\kanban\models\KanbanBoard::getTasksByDeadlineCategory('overdue');
    echo "Overdue tasks found: " . count($overdueTasks) . "\n";
    
    foreach ($overdueTasks as $task) {
        echo "Task {$task->id}: {$task->title} - Deadline: " . date('Y-m-d H:i:s', $task->deadline) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}