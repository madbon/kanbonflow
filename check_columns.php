<?php
/**
 * Check current column positions
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
    echo "=== CURRENT KANBAN COLUMNS ===\n\n";
    
    $columns = KanbanColumn::find()->orderBy('position ASC, id ASC')->all();
    
    if (empty($columns)) {
        echo "No columns found.\n";
    } else {
        echo "Current columns:\n";
        foreach ($columns as $column) {
            echo "ID: {$column->id}, Name: {$column->name}, Status: {$column->status_key}, Position: {$column->position}, Active: " . ($column->is_active ? 'Yes' : 'No') . "\n";
        }
        
        echo "\nActive columns (as loaded by getActiveColumns()):\n";
        $activeColumns = KanbanColumn::getActiveColumns();
        foreach ($activeColumns as $index => $column) {
            echo "[{$index}] {$column->name} ({$column->status_key}) - Position: {$column->position}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}