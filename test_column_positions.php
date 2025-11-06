<?php
/**
 * Test column position update endpoint
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
    echo "=== COLUMN POSITION TEST ===\n\n";
    
    // 1. Show current column positions
    echo "1. Current column positions:\n";
    $columns = \common\modules\kanban\models\KanbanColumn::getActiveColumns();
    foreach ($columns as $index => $column) {
        echo "   [{$index}] Column {$column->id}: {$column->name} ({$column->status_key}) - Position: {$column->position}\n";
    }
    
    // 2. Test moving column 3 (Done) to position 0 (first position)
    echo "\n2. Testing: Move column 3 (Done) to position 0\n";
    
    // Mock request data
    Yii::$app->request->setBodyParams([
        'columnId' => 3,
        'position' => 0
    ]);
    
    $controller = new \common\modules\kanban\controllers\BoardController('board', new \common\modules\kanban\Module('kanban'));
    $result = $controller->actionUpdateColumnPosition();
    
    echo "   Controller response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    // 3. Check positions after the move
    echo "\n3. Column positions after move:\n";
    $columns = \common\modules\kanban\models\KanbanColumn::getActiveColumns();
    foreach ($columns as $index => $column) {
        echo "   [{$index}] Column {$column->id}: {$column->name} ({$column->status_key}) - Position: {$column->position}\n";
    }
    
    echo "\n=== TEST COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}