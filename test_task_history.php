<?php
/**
 * Test task history tracking
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
use common\modules\taskmonitor\models\TaskHistory;

try {
    echo "=== TASK HISTORY TRACKING TEST ===\n\n";
    
    // 1. Check current history for task 1
    echo "1. Current history for Task 1:\n";
    $history = TaskHistory::getTaskHistory(1);
    foreach ($history as $entry) {
        echo "   - {$entry->getFormattedDate()}: {$entry->getActionTypeLabel()} - {$entry->description}\n";
    }
    
    // 2. Update task 1 to test history tracking
    echo "\n2. Updating Task 1 title to test history tracking...\n";
    $task = Task::findOne(1);
    if ($task) {
        $oldTitle = $task->title;
        $task->title = 'Updated Task Title - ' . date('H:i:s');
        $task->save();
        echo "   Title changed from '{$oldTitle}' to '{$task->title}'\n";
    }
    
    // 3. Check history again
    echo "\n3. Updated history for Task 1:\n";
    $history = TaskHistory::getTaskHistory(1);
    foreach ($history as $entry) {
        echo "   - {$entry->getFormattedDate()}: {$entry->getActionTypeLabel()} - {$entry->description} [{$entry->getUserDisplayName()}]\n";
    }
    
    // 4. Test status change
    echo "\n4. Testing status change tracking...\n";
    if ($task) {
        $oldStatus = $task->status;
        $task->status = 'pending';
        $task->save();
        echo "   Status changed from '{$oldStatus}' to '{$task->status}'\n";
    }
    
    // 5. Final history check
    echo "\n5. Final history for Task 1:\n";
    $history = TaskHistory::getTaskHistory(1, 5); // Last 5 entries
    foreach ($history as $entry) {
        echo "   - {$entry->getRelativeTime()}: {$entry->getActionTypeLabel()} - {$entry->description}\n";
    }
    
    echo "\n=== TEST COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}