<?php
/**
 * Simple test for manual history logging
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

use common\modules\taskmonitor\models\TaskHistory;

try {
    echo "=== MANUAL HISTORY LOGGING TEST ===\n\n";
    
    // Manually log a history entry
    $history = TaskHistory::log(
        1, // task_id
        TaskHistory::ACTION_UPDATED, // action_type
        'Testing manual history logging', // description
        'title', // field_name
        'Old Title', // old_value
        'New Title' // new_value
    );
    
    echo "History entry created with ID: {$history->id}\n";
    
    // Check all history for task 1
    echo "\nAll history for Task 1:\n";
    $allHistory = TaskHistory::getTaskHistory(1);
    foreach ($allHistory as $entry) {
        echo "   - {$entry->getRelativeTime()}: {$entry->getActionTypeLabel()} - {$entry->description} [{$entry->getUserDisplayName()}]\n";
    }
    
    echo "\n=== TEST COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}