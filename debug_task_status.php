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

echo "=== Debug Task Status vs Columns ===\n\n";

try {
    // Get all tasks
    $tasks = \common\modules\taskmonitor\models\Task::find()
        ->orderBy(['id' => SORT_ASC])
        ->all();
    
    echo "=== All Tasks and Their Status ===\n";
    foreach ($tasks as $task) {
        echo "ID: {$task->id}, Title: {$task->title}, Status: '{$task->status}'\n";
    }
    
    echo "\n=== Active Kanban Columns ===\n";
    $columns = \common\modules\kanban\models\KanbanColumn::find()
        ->where(['is_active' => 1])
        ->orderBy(['position' => SORT_ASC])
        ->all();
    
    foreach ($columns as $column) {
        echo "Column: {$column->name}, Status Key: '{$column->status_key}', Position: {$column->position}\n";
    }
    
    echo "\n=== Tasks by Column (as used by Kanban Board) ===\n";
    $tasksByColumns = \common\modules\kanban\models\KanbanBoard::getTasksByColumns();
    
    foreach ($tasksByColumns as $statusKey => $columnTasks) {
        echo "Column '{$statusKey}': " . count($columnTasks) . " tasks\n";
        foreach ($columnTasks as $task) {
            echo "  - ID: {$task->id}, Title: {$task->title}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}