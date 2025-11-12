<?php

// Set timezone to match your system
date_default_timezone_set('America/New_York'); // Adjust this to your actual timezone

// Current time (Nov 12, 2025 8:11am)
$currentTime = time();

// Deadline: Nov 12, 2025 4:00pm (same day, 8 hours later)
$deadlineTime = strtotime('2025-11-12 16:00:00');

echo "=== TIME CALCULATION DEBUG ===\n";
echo "Current time: " . date('Y-m-d H:i:s', $currentTime) . " (timestamp: $currentTime)\n";
echo "Deadline time: " . date('Y-m-d H:i:s', $deadlineTime) . " (timestamp: $deadlineTime)\n";

$secondsUntilDeadline = $deadlineTime - $currentTime;
echo "Seconds until deadline: $secondsUntilDeadline\n";

if ($secondsUntilDeadline > 0) {
    $days = floor($secondsUntilDeadline / (24 * 60 * 60));
    $hours = floor(($secondsUntilDeadline % (24 * 60 * 60)) / (60 * 60));
    $minutes = floor(($secondsUntilDeadline % (60 * 60)) / 60);
    
    echo "Days: $days\n";
    echo "Hours: $hours\n";
    echo "Minutes: $minutes\n";
    
    // Format display
    if ($days > 0) {
        $display = "$days day" . ($days > 1 ? 's' : '') . ", $hours hour" . ($hours != 1 ? 's' : '') . ", and $minutes minute" . ($minutes != 1 ? 's' : '') . " before deadline";
    } elseif ($hours > 0) {
        $display = "in $hours hour" . ($hours != 1 ? 's' : '') . " and $minutes minute" . ($minutes != 1 ? 's' : '') . " before deadline";
    } else {
        $display = "in $minutes minute" . ($minutes != 1 ? 's' : '') . " before deadline";
    }
    
    echo "Display text: $display\n";
} else {
    echo "Task is overdue!\n";
}

echo "PHP timezone: " . date_default_timezone_get() . "\n";
echo "Server time: " . date('Y-m-d H:i:s') . "\n";

// Test with different timezones
$timezones = ['UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'Europe/London'];
echo "\n=== TIMEZONE TESTING ===\n";
foreach ($timezones as $tz) {
    date_default_timezone_set($tz);
    $currentTz = time();
    $deadlineTz = strtotime('2025-11-12 16:00:00');
    $diffTz = $deadlineTz - $currentTz;
    $hoursTz = floor($diffTz / 3600);
    $minutesTz = floor(($diffTz % 3600) / 60);
    echo "$tz: {$hoursTz}h {$minutesTz}m (current: " . date('H:i:s') . ", deadline: " . date('H:i:s', $deadlineTz) . ")\n";
}