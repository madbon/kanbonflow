<?php
/**
 * Test script for position update endpoint
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

try {
    // Test the position update method directly
    $controller = new \common\modules\kanban\controllers\BoardController('board', new \common\modules\kanban\Module('kanban'));
    
    // Mock the request data
    Yii::$app->request->setBodyParams([
        'taskId' => 1,
        'position' => 1,
        'status' => 'completed'
    ]);
    
    echo "Testing actionUpdateTaskPosition with:\n";
    echo "- taskId: 1\n";
    echo "- position: 1\n";
    echo "- status: completed\n\n";
    
    $result = $controller->actionUpdateTaskPosition();
    
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    // Check the task after update
    $task = \common\modules\taskmonitor\models\Task::findOne(1);
    if ($task) {
        echo "Task after update:\n";
        echo "- ID: {$task->id}\n";
        echo "- Title: {$task->title}\n";
        echo "- Status: {$task->status}\n";
        echo "- Position: {$task->position}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}