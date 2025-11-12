<?php
// Debug time calculation
$host = 'localhost';
$dbname = 'taskviewer';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get a task to check
    $stmt = $pdo->prepare("SELECT id, title, deadline FROM tasks WHERE deadline IS NOT NULL LIMIT 1");
    $stmt->execute();
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($task) {
        $now = time();
        echo "Current time: " . date('Y-m-d H:i:s', $now) . " (timestamp: $now)\n";
        echo "Task deadline: " . date('Y-m-d H:i:s', $task['deadline']) . " (timestamp: {$task['deadline']})\n";
        
        $secondsUntilDeadline = $task['deadline'] - $now;
        echo "Seconds until deadline: $secondsUntilDeadline\n";
        
        $days = floor($secondsUntilDeadline / 86400);
        $hours = floor(($secondsUntilDeadline % 86400) / 3600);
        $minutes = floor(($secondsUntilDeadline % 3600) / 60);
        
        echo "Days: $days\n";
        echo "Hours: $hours\n";
        echo "Minutes: $minutes\n";
        
        // Check if there's a timezone issue
        echo "PHP timezone: " . date_default_timezone_get() . "\n";
        echo "Server time: " . date('Y-m-d H:i:s') . "\n";
    } else {
        echo "No tasks found with deadlines\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>