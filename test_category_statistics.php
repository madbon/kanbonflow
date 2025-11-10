<?php
// Test category statistics functionality
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/common/config/bootstrap.php');

// Initialize Yii application
$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/common/config/main.php'),
    require(__DIR__ . '/backend/config/main.php'),
    require(__DIR__ . '/backend/config/main-local.php')
);

$application = new yii\web\Application($config);

use common\modules\kanban\models\KanbanBoard;
use common\modules\taskmonitor\models\TaskCategory;

try {
    echo "=== Testing Category Statistics ===\n";
    
    // Get all categories
    $categories = TaskCategory::find()->all();
    echo "Found " . count($categories) . " categories:\n";
    foreach ($categories as $category) {
        echo "- ID: {$category->id}, Name: {$category->name}, Color: {$category->color}\n";
    }
    
    echo "\n=== Category Statistics ===\n";
    $categoryStats = KanbanBoard::getCategoryStatistics();
    
    if (empty($categoryStats)) {
        echo "No category statistics found.\n";
    } else {
        foreach ($categoryStats as $key => $stat) {
            echo "Category: {$stat['name']}\n";
            echo "- Total: {$stat['count']} tasks\n";
            echo "- Completed: {$stat['completed_count']}\n";
            echo "- Not Completed: {$stat['not_completed_count']}\n";
            echo "- Color: {$stat['color']}\n";
            echo "- Icon: {$stat['icon']}\n";
            echo "- Key: {$key}\n";
            echo "---\n";
        }
    }
    
    echo "\n=== Testing getCategoryCompletionTasks() ===\n";
    
    // Test with first category if exists
    if (!empty($categories)) {
        $firstCategory = $categories[0];
        echo "Testing with category: {$firstCategory->name} (ID: {$firstCategory->id})\n";
        
        $categoryTasks = KanbanBoard::getCategoryCompletionTasks($firstCategory->id);
        echo "Results:\n";
        echo "- Completed tasks: " . count($categoryTasks['completed']) . "\n";
        echo "- Not completed tasks: " . count($categoryTasks['not_completed']) . "\n";
        
        if (count($categoryTasks['completed']) > 0) {
            echo "First completed task: " . $categoryTasks['completed'][0]->title . "\n";
        }
        if (count($categoryTasks['not_completed']) > 0) {
            echo "First not completed task: " . $categoryTasks['not_completed'][0]->title . "\n";
        }
    }
    
    // Test with null category (uncategorized)
    echo "\n=== Testing Uncategorized Tasks ===\n";
    $uncategorizedTasks = KanbanBoard::getCategoryCompletionTasks(null);
    echo "Uncategorized tasks:\n";
    echo "- Completed: " . count($uncategorizedTasks['completed']) . "\n";
    echo "- Not completed: " . count($uncategorizedTasks['not_completed']) . "\n";
    
    echo "\n=== Test completed successfully! ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>