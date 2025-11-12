<?php

// Test current system behavior
echo "=== SYSTEM TIME ANALYSIS ===\n";
echo "System date command: " . shell_exec('date') . "\n";
echo "PHP time(): " . time() . "\n";
echo "PHP date(): " . date('Y-m-d H:i:s') . "\n";
echo "PHP timezone: " . date_default_timezone_get() . "\n";

// Test the exact scenario you described
echo "\n=== YOUR SCENARIO TEST ===\n";

// Let's use the actual current timestamp from PHP
$currentTime = time();

// Create deadline for same day 4:00 PM
$today = date('Y-m-d');
$deadlineTime = strtotime($today . ' 16:00:00');

echo "Current time: " . date('Y-m-d H:i:s', $currentTime) . " (timestamp: $currentTime)\n";
echo "Deadline: " . date('Y-m-d H:i:s', $deadlineTime) . " (timestamp: $deadlineTime)\n";

$secondsUntilDeadline = $deadlineTime - $currentTime;
echo "Seconds difference: $secondsUntilDeadline\n";

if ($secondsUntilDeadline > 0) {
    $days = floor($secondsUntilDeadline / (24 * 60 * 60));
    $hours = floor(($secondsUntilDeadline % (24 * 60 * 60)) / (60 * 60));
    $minutes = floor(($secondsUntilDeadline % (60 * 60)) / 60);
    
    echo "Calculated - Days: $days, Hours: $hours, Minutes: $minutes\n";
    
    if ($days > 0) {
        echo "Display: $days day" . ($days > 1 ? 's' : '') . ", $hours hour" . ($hours != 1 ? 's' : '') . ", and $minutes minute" . ($minutes != 1 ? 's' : '') . " before deadline\n";
    } elseif ($hours > 0) {
        echo "Display: in $hours hour" . ($hours != 1 ? 's' : '') . " and $minutes minute" . ($minutes != 1 ? 's' : '') . " before deadline\n";
    } else {
        echo "Display: in $minutes minute" . ($minutes != 1 ? 's' : '') . " before deadline\n";
    }
} else {
    $overdue = abs($secondsUntilDeadline);
    $overdueHours = floor($overdue / 3600);
    $overdueMinutes = floor(($overdue % 3600) / 60);
    echo "Task is overdue by: {$overdueHours}h {$overdueMinutes}m\n";
}

// Test Windows timezone
echo "\n=== WINDOWS TIMEZONE INFO ===\n";
$winTimeZone = shell_exec('tzutil /g 2>nul');
echo "Windows timezone: " . trim($winTimeZone) . "\n";

// Test with a specific target date matching your example
echo "\n=== MANUAL TEST (Nov 12 8:09am to 4:00pm) ===\n";
$manualCurrent = strtotime('2025-11-12 08:09:00');
$manualDeadline = strtotime('2025-11-12 16:00:00');
$manualDiff = $manualDeadline - $manualCurrent;
$manualHours = floor($manualDiff / 3600);
$manualMinutes = floor(($manualDiff % 3600) / 60);

echo "Manual current: " . date('Y-m-d H:i:s', $manualCurrent) . "\n";
echo "Manual deadline: " . date('Y-m-d H:i:s', $manualDeadline) . "\n";
echo "Manual difference: {$manualHours}h {$manualMinutes}m\n";
echo "Expected: ~7h 51m\n";