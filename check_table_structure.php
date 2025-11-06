<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require 'vendor/autoload.php';
require 'vendor/yiisoft/yii2/Yii.php';
require 'common/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require 'common/config/main.php',
    require 'common/config/main-local.php',
    require 'console/config/main.php',
    require 'console/config/main-local.php'
);

$app = new yii\console\Application($config);

try {
    $db = Yii::$app->db;
    
    echo "=== Task Color Settings Table Structure ===\n";
    $schema = $db->getSchema();
    $tableSchema = $schema->getTableSchema('task_color_settings');
    
    if ($tableSchema) {
        echo "Table exists!\n";
        echo "Columns:\n";
        foreach ($tableSchema->columns as $column) {
            echo "- {$column->name} ({$column->type}) " . ($column->allowNull ? 'NULL' : 'NOT NULL') . "\n";
        }
    } else {
        echo "Table does not exist!\n";
    }
    
    echo "\n=== Current data ===\n";
    $command = $db->createCommand('SELECT * FROM task_color_settings');
    $rows = $command->queryAll();
    
    if (empty($rows)) {
        echo "No data found in table\n";
    } else {
        foreach ($rows as $row) {
            print_r($row);
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}