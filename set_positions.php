<?php
/**
 * Test script to set different positions for both tasks
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

use common\modules\taskmonitor\models\Task;

try {
    // Set task 1 to position 0 (first)
    $task1 = Task::findOne(1);
    if ($task1) {
        $task1->position = 0;
        $task1->save();
        echo "Task 1 set to position 0\n";
    }
    
    // Set task 2 to position 1 (second)
    $task2 = Task::findOne(2);
    if ($task2) {
        $task2->position = 1;
        $task2->save();
        echo "Task 2 set to position 1\n";
    }
    
    echo "\nCurrent positions:\n";
    $tasks = Task::find()->orderBy('position ASC')->all();
    foreach ($tasks as $task) {
        echo "Task {$task->id}: {$task->title} - Position: {$task->position} - Status: {$task->status}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}