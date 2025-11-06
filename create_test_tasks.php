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
    $db = Yii::$app->db;
    
    echo "=== Creating Test Tasks ===\n";
    
    $today = strtotime('today');
    $todayPlusOne = strtotime('+1 day');
    $todayPlusThree = strtotime('+3 days');
    $todayPlusWeek = strtotime('+7 days');
    $yesterday = strtotime('-1 day');
    
    // Create test tasks with different deadlines
    $testTasks = [
        ['title' => 'Test Due Today 1', 'deadline' => $today, 'status' => 'pending'],
        ['title' => 'Test Due Today 2', 'deadline' => $today + 3600, 'status' => 'in_progress'], // Today + 1 hour
        ['title' => 'Test Due Tomorrow', 'deadline' => $todayPlusOne, 'status' => 'pending'],
        ['title' => 'Test Due in 3 Days', 'deadline' => $todayPlusThree, 'status' => 'pending'],
        ['title' => 'Test Due in 1 Week', 'deadline' => $todayPlusWeek, 'status' => 'pending'],
        ['title' => 'Test Overdue', 'deadline' => $yesterday, 'status' => 'pending'],
    ];
    
    foreach ($testTasks as $task) {
        // Check if task already exists
        $exists = $db->createCommand('SELECT COUNT(*) as count FROM tasks WHERE title = :title')
            ->bindParam(':title', $task['title'])
            ->queryScalar();
            
        if ($exists == 0) {
            // Get the first available category
            $category = $db->createCommand('SELECT id FROM task_categories ORDER BY id LIMIT 1')->queryScalar();
            
            $db->createCommand()->insert('tasks', [
                'title' => $task['title'],
                'description' => 'Test task for deadline testing',
                'status' => $task['status'],
                'priority' => 'medium',
                'deadline' => $task['deadline'],
                'category_id' => $category ?: null,
                'created_at' => time(),
                'updated_at' => time(),
                'created_by' => 1,
            ])->execute();
            
            echo "Created: {$task['title']} - Deadline: " . date('Y-m-d H:i:s', $task['deadline']) . "\n";
        } else {
            echo "Already exists: {$task['title']}\n";
        }
    }
    
    echo "\n=== Testing Due Today Query ===\n";
    
    // Test the new logic
    $today = strtotime('today');
    $tomorrow = strtotime('tomorrow');
    
    $dueTodayQuery = "SELECT id, title, deadline, status FROM tasks WHERE deadline >= $today AND deadline < $tomorrow AND status != 'completed'";
    $results = $db->createCommand($dueTodayQuery)->queryAll();
    
    echo "Due today tasks found: " . count($results) . "\n";
    foreach ($results as $task) {
        echo "- Task {$task['id']}: {$task['title']} (Deadline: " . date('Y-m-d H:i:s', $task['deadline']) . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}