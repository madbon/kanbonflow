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
    
    echo "=== Today's Date Info ===\n";
    $today = date('Y-m-d');
    $todayTimestamp = strtotime('today');
    $tomorrowTimestamp = strtotime('tomorrow');
    
    echo "Today: $today\n";
    echo "Today timestamp: $todayTimestamp (" . date('Y-m-d H:i:s', $todayTimestamp) . ")\n";
    echo "Tomorrow timestamp: $tomorrowTimestamp (" . date('Y-m-d H:i:s', $tomorrowTimestamp) . ")\n";
    
    echo "\n=== All Tasks with Deadlines ===\n";
    $command = $db->createCommand('SELECT id, title, deadline, status FROM tasks WHERE deadline IS NOT NULL ORDER BY deadline');
    $tasks = $command->queryAll();
    
    foreach ($tasks as $task) {
        $deadlineTs = strtotime($task['deadline']);
        $isToday = ($deadlineTs >= $todayTimestamp && $deadlineTs < $tomorrowTimestamp);
        echo "Task {$task['id']}: {$task['title']} - Deadline: {$task['deadline']} - Status: {$task['status']} - Due Today: " . ($isToday ? 'YES' : 'NO') . "\n";
    }
    
    echo "\n=== Due Today Query Test ===\n";
    $dueTodayQuery = "SELECT COUNT(*) as count FROM tasks WHERE deadline >= '$today 00:00:00' AND deadline < '$today 23:59:59' AND status != 'completed'";
    $result = $db->createCommand($dueTodayQuery)->queryOne();
    echo "Due today count (using date strings): " . $result['count'] . "\n";
    
    $dueTodayQuery2 = "SELECT COUNT(*) as count FROM tasks WHERE DATE(deadline) = '$today' AND status != 'completed'";
    $result2 = $db->createCommand($dueTodayQuery2)->queryOne();
    echo "Due today count (using DATE function): " . $result2['count'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}