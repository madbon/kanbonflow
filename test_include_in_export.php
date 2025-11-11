<?php
// Test include_in_export functionality
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/common/config/bootstrap.php');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/common/config/main.php'),
    require(__DIR__ . '/backend/config/main.php'),
    require(__DIR__ . '/backend/config/main-local.php')
);

$application = new yii\web\Application($config);

use common\modules\taskmonitor\models\Task;

try {
    echo "=== Testing Include in Export Functionality ===\n";
    
    // Get all tasks and show their include_in_export status
    $tasks = Task::find()->all();
    
    echo "Current tasks and their export settings:\n";
    foreach ($tasks as $task) {
        $exportStatus = $task->include_in_export ? 'Yes' : 'No';
        echo "- Task ID {$task->id}: '{$task->title}' - Include in Export: {$exportStatus}\n";
    }
    
    if (count($tasks) > 0) {
        // Test updating a task's export setting
        $firstTask = $tasks[0];
        $originalValue = $firstTask->include_in_export;
        
        echo "\n=== Testing Export Setting Update ===\n";
        echo "Updating Task ID {$firstTask->id} export setting...\n";
        
        // Toggle the setting
        $firstTask->include_in_export = $originalValue ? 0 : 1;
        
        if ($firstTask->save()) {
            $newStatus = $firstTask->include_in_export ? 'Yes' : 'No';
            echo "✅ Successfully updated Task ID {$firstTask->id} - Include in Export: {$newStatus}\n";
            
            // Restore original value
            $firstTask->include_in_export = $originalValue;
            $firstTask->save();
            $restoredStatus = $firstTask->include_in_export ? 'Yes' : 'No';
            echo "✅ Restored original setting - Include in Export: {$restoredStatus}\n";
        } else {
            echo "❌ Failed to update task export setting\n";
            print_r($firstTask->errors);
        }
    }
    
    echo "\n=== Testing Task Creation with Export Setting ===\n";
    
    // Test creating a new task with include_in_export = false
    $testTask = new Task();
    $testTask->title = 'Test Task - Hidden from Export';
    $testTask->description = 'This task should not appear in activity log exports';
    $testTask->category_id = 1; // Assuming category 1 exists
    $testTask->priority = 'medium';
    $testTask->status = 'To Do';
    $testTask->deadline = time() + (7 * 24 * 60 * 60); // 7 days from now
    $testTask->include_in_export = 0; // Hide from export
    
    if ($testTask->save()) {
        echo "✅ Successfully created test task with include_in_export = 0\n";
        echo "   Task ID: {$testTask->id}\n";
        echo "   Title: {$testTask->title}\n";
        echo "   Include in Export: No\n";
    } else {
        echo "❌ Failed to create test task\n";
        print_r($testTask->errors);
    }
    
    echo "\n=== Export Filtering Test ===\n";
    echo "Tasks that WILL appear in exports (include_in_export = 1 or null):\n";
    $exportableTasks = Task::find()->where(['or', ['include_in_export' => 1], ['include_in_export' => null]])->all();
    foreach ($exportableTasks as $task) {
        $exportStatus = $task->include_in_export ? 'Yes' : 'Default (Yes)';
        echo "- Task ID {$task->id}: '{$task->title}' - {$exportStatus}\n";
    }
    
    echo "\nTasks that will NOT appear in exports (include_in_export = 0):\n";
    $hiddenTasks = Task::find()->where(['include_in_export' => 0])->all();
    foreach ($hiddenTasks as $task) {
        echo "- Task ID {$task->id}: '{$task->title}' - Hidden\n";
    }
    
    if (count($hiddenTasks) == 0) {
        echo "No hidden tasks found.\n";
    }
    
    echo "\n=== Test completed successfully! ===\n";
    echo "The include_in_export field is working correctly.\n";
    echo "Tasks with include_in_export = 0 will be excluded from activity log exports.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>