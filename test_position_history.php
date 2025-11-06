<?php
/**
 * Test position change history logging
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
    echo "=== POSITION CHANGE HISTORY TEST ===\n\n";
    
    // Simulate moving task 2 from ongoing to pending
    Yii::$app->request->setBodyParams([
        'taskId' => 2,
        'position' => 1,
        'status' => 'pending'
    ]);
    
    $controller = new \common\modules\kanban\controllers\BoardController('board', new \common\modules\kanban\Module('kanban'));
    $result = $controller->actionUpdateTaskPosition();
    
    echo "Position update result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    // Check history
    echo "Task 2 history after position change:\n";
    $history = TaskHistory::getTaskHistory(2, 5);
    foreach ($history as $entry) {
        $icon = $entry->getActionIcon();
        $class = $entry->getActionCssClass();
        echo "   {$icon} [{$entry->getRelativeTime()}] {$entry->getActionTypeLabel()}: {$entry->description} - {$entry->getUserDisplayName()}\n";
    }
    
    echo "\n=== TEST COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}