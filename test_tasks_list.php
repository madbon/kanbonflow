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

echo "=== Available Tasks ===\n\n";

try {
    $tasks = \common\modules\taskmonitor\models\Task::find()
        ->orderBy(['created_at' => SORT_DESC])
        ->limit(5)
        ->all();
    
    if (empty($tasks)) {
        echo "No tasks found. Creating test tasks...\n\n";
        
        // Create test tasks with different deadlines
        $testTasks = [
            [
                'title' => 'Test Task Due Today',
                'description' => 'This task is due today for testing emphasis feature',
                'deadline' => date('Y-m-d', strtotime('today')),
                'priority' => 'high',
                'status' => 'To Do'
            ],
            [
                'title' => 'Overdue Test Task',
                'description' => 'This task is overdue for testing emphasis feature',
                'deadline' => date('Y-m-d', strtotime('-2 days')),
                'priority' => 'critical',
                'status' => 'In Progress'
            ],
            [
                'title' => 'Upcoming Test Task',
                'description' => 'This task is due in a few days',
                'deadline' => date('Y-m-d', strtotime('+3 days')),
                'priority' => 'medium',
                'status' => 'To Do'
            ]
        ];
        
        foreach ($testTasks as $taskData) {
            $task = new \common\modules\taskmonitor\models\Task();
            $task->title = $taskData['title'];
            $task->description = $taskData['description'];
            $task->deadline = $taskData['deadline'];
            $task->priority = $taskData['priority'];
            $task->status = $taskData['status'];
            $task->created_by = 1; // Assume user ID 1 exists
            
            if ($task->save()) {
                echo "Created task: {$task->title} (ID: {$task->id})\n";
            } else {
                echo "Failed to save task: " . implode(', ', $task->getFirstErrors()) . "\n";
            }
        }
        
        echo "\nTest tasks created successfully!\n";
    } else {
        echo "Found " . count($tasks) . " tasks:\n\n";
        foreach ($tasks as $task) {
            echo "ID: {$task->id}\n";
            echo "Title: {$task->title}\n";
            echo "Status: {$task->status}\n";
            echo "Priority: {$task->priority}\n";
            echo "Deadline: {$task->deadline}\n";
            echo "Category ID: {$task->category_id}\n";
            echo "---\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}