<?php
/**
 * Comprehensive test for position persistence issue
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
use common\modules\kanban\models\KanbanBoard;

try {
    echo "=== POSITION PERSISTENCE TEST ===\n\n";
    
    // 1. Show current state
    echo "1. Current task positions in database:\n";
    $tasks = Task::find()->orderBy('position ASC, id ASC')->all();
    foreach ($tasks as $task) {
        echo "   Task {$task->id}: Position {$task->position}, Status: {$task->status}\n";
    }
    
    // 2. Test how KanbanBoard loads tasks
    echo "\n2. How KanbanBoard.getTasksByColumns() loads tasks:\n";
    $tasksByColumns = KanbanBoard::getTasksByColumns();
    foreach ($tasksByColumns as $status => $columnTasks) {
        echo "   Status '{$status}':\n";
        foreach ($columnTasks as $index => $task) {
            echo "     [{$index}] Task {$task->id}: {$task->title} (Position: {$task->position})\n";
        }
    }
    
    // 3. Simulate moving task 2 to position 0 (before task 1)
    echo "\n3. Simulating move: Task 2 to position 0 in same column\n";
    
    // Mock request data - exactly like the AJAX call
    Yii::$app->request->setBodyParams([
        'taskId' => 2,
        'position' => 0,
        'status' => 'completed'
    ]);
    
    $controller = new \common\modules\kanban\controllers\BoardController('board', new \common\modules\kanban\Module('kanban'));
    $result = $controller->actionUpdateTaskPosition();
    
    echo "   Controller response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    // 4. Check positions after the move
    echo "\n4. Positions after move:\n";
    $tasks = Task::find()->orderBy('position ASC, id ASC')->all();
    foreach ($tasks as $task) {
        echo "   Task {$task->id}: Position {$task->position}, Status: {$task->status}\n";
    }
    
    // 5. Test how KanbanBoard loads tasks after the move
    echo "\n5. How KanbanBoard loads tasks after move:\n";
    $tasksByColumns = KanbanBoard::getTasksByColumns();
    foreach ($tasksByColumns as $status => $columnTasks) {
        echo "   Status '{$status}':\n";
        foreach ($columnTasks as $index => $task) {
            echo "     [{$index}] Task {$task->id}: {$task->title} (Position: {$task->position})\n";
        }
    }
    
    echo "\n=== TEST COMPLETE ===\n";
    echo "If positions change in step 4 and 5, the backend is working correctly.\n";
    echo "If the positions don't persist after page refresh, it's likely a frontend/AJAX issue.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}