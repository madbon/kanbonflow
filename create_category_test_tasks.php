<?php
// Create test tasks with categories
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
use common\modules\taskmonitor\models\TaskCategory;

try {
    echo "=== Creating Test Tasks with Categories ===\n";
    
    // First, let's see what categories exist
    $categories = TaskCategory::find()->all();
    echo "Found " . count($categories) . " categories:\n";
    foreach ($categories as $category) {
        echo "- ID: {$category->id}, Name: {$category->name}\n";
    }
    
    if (count($categories) == 0) {
        echo "\nCreating test categories...\n";
        
        // Create Development category
        $devCategory = new TaskCategory();
        $devCategory->name = 'Development';
        $devCategory->color = '#007bff';
        $devCategory->icon = 'fas fa-code';
        $devCategory->save();
        echo "Created Development category (ID: {$devCategory->id})\n";
        
        // Create Testing category
        $testCategory = new TaskCategory();
        $testCategory->name = 'Testing';
        $testCategory->color = '#28a745';
        $testCategory->icon = 'fas fa-bug';
        $testCategory->save();
        echo "Created Testing category (ID: {$testCategory->id})\n";
        
        $categories = [$devCategory, $testCategory];
    }
    
    // Create some test tasks
    echo "\nCreating test tasks...\n";
    
    // Development tasks
    if (count($categories) > 0) {
        $devCategory = $categories[0];
        
        $task1 = new Task();
        $task1->title = 'Implement user authentication';
        $task1->description = 'Create login and registration system';
        $task1->category_id = $devCategory->id;
        $task1->status = 'To Do';
        $task1->priority = 'High';
        $task1->save();
        echo "Created task: {$task1->title} (Category: {$devCategory->name})\n";
        
        $task2 = new Task();
        $task2->title = 'Design database schema';
        $task2->description = 'Create database structure for the application';
        $task2->category_id = $devCategory->id;
        $task2->status = 'In Progress';
        $task2->priority = 'Medium';
        $task2->save();
        echo "Created task: {$task2->title} (Category: {$devCategory->name})\n";
        
        $task3 = new Task();
        $task3->title = 'Setup project structure';
        $task3->description = 'Initialize project with proper folder structure';
        $task3->category_id = $devCategory->id;
        $task3->status = 'Completed';
        $task3->priority = 'High';
        $task3->save();
        echo "Created task: {$task3->title} (Category: {$devCategory->name}, Status: Completed)\n";
    }
    
    // Testing tasks
    if (count($categories) > 1) {
        $testCategory = $categories[1];
        
        $task4 = new Task();
        $task4->title = 'Write unit tests';
        $task4->description = 'Create comprehensive unit test suite';
        $task4->category_id = $testCategory->id;
        $task4->status = 'To Do';
        $task4->priority = 'Medium';
        $task4->save();
        echo "Created task: {$task4->title} (Category: {$testCategory->name})\n";
        
        $task5 = new Task();
        $task5->title = 'Perform integration testing';
        $task5->description = 'Test system integration points';
        $task5->category_id = $testCategory->id;
        $task5->status = 'Completed';
        $task5->priority = 'High';
        $task5->save();
        echo "Created task: {$task5->title} (Category: {$testCategory->name}, Status: Completed)\n";
    }
    
    // Uncategorized task
    $task6 = new Task();
    $task6->title = 'Review project requirements';
    $task6->description = 'Go through all project requirements and validate';
    $task6->category_id = null;
    $task6->status = 'In Progress';
    $task6->priority = 'Low';
    $task6->save();
    echo "Created task: {$task6->title} (No Category)\n";
    
    echo "\n=== Test tasks created successfully! ===\n";
    echo "Now you can access the kanban board to see the new category statistics.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>