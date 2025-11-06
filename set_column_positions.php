<?php
/**
 * Set sequential positions for columns
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

use common\modules\kanban\models\KanbanColumn;

try {
    echo "=== SETTING SEQUENTIAL COLUMN POSITIONS ===\n\n";
    
    $columns = KanbanColumn::find()->where(['is_active' => 1])->orderBy('position ASC, id ASC')->all();
    
    $position = 0;
    foreach ($columns as $column) {
        $oldPosition = $column->position;
        $column->position = $position;
        $column->save();
        echo "Column '{$column->name}': Position {$oldPosition} → {$position}\n";
        $position++;
    }
    
    echo "\nUpdated column order:\n";
    $activeColumns = KanbanColumn::getActiveColumns();
    foreach ($activeColumns as $index => $column) {
        echo "[{$index}] {$column->name} ({$column->status_key}) - Position: {$column->position}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}