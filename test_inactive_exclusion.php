<?php

// Test exclusion of inactive tasks from overdue statistics
echo "Testing Inactive Tasks Exclusion from Overdue Statistics\n";
echo "======================================================\n\n";

// Simulate task data for testing
$testTasks = [
    [
        'id' => 1, 
        'title' => 'Active Overdue Task', 
        'status' => 'pending', 
        'deadline' => strtotime('-2 days'),  // 2 days overdue
        'target_start_date' => strtotime('yesterday'),
        'target_end_date' => strtotime('today')
    ],
    [
        'id' => 2, 
        'title' => 'Inactive Overdue Task', 
        'status' => 'inactive', 
        'deadline' => strtotime('-5 days'),  // 5 days overdue
        'target_start_date' => strtotime('-1 week'),
        'target_end_date' => strtotime('today')
    ],
    [
        'id' => 3, 
        'title' => 'Completed Overdue Task', 
        'status' => 'completed', 
        'deadline' => strtotime('-1 day'),  // 1 day overdue
        'target_start_date' => strtotime('-2 days'),
        'target_end_date' => strtotime('yesterday')
    ],
    [
        'id' => 4, 
        'title' => 'Active Future Task', 
        'status' => 'ongoing', 
        'deadline' => strtotime('+3 days'),  // Future deadline
        'target_start_date' => strtotime('today'),
        'target_end_date' => strtotime('+1 week')
    ],
    [
        'id' => 5, 
        'title' => 'Inactive Future Task', 
        'status' => 'inactive', 
        'deadline' => strtotime('+1 day'),  // Future deadline
        'target_start_date' => strtotime('today'),
        'target_end_date' => strtotime('tomorrow')
    ]
];

$today = time();
$todayStart = strtotime('today');
$todayEnd = strtotime('tomorrow') - 1;

echo "Current Time: " . date('Y-m-d H:i:s', $today) . "\n";
echo "Today Range: " . date('Y-m-d H:i:s', $todayStart) . " to " . date('Y-m-d H:i:s', $todayEnd) . "\n\n";

// Test overdue logic
function isTaskOverdue($task) {
    return $task['deadline'] < time() && 
           $task['status'] !== 'completed' && 
           $task['status'] !== 'inactive';
}

// Test targeted today logic  
function isTaskTargetedToday($task, $todayStart, $todayEnd) {
    return $task['target_start_date'] !== null &&
           $task['target_end_date'] !== null &&
           $task['target_start_date'] <= $todayEnd &&
           $task['target_end_date'] >= $todayStart &&
           $task['status'] !== 'completed' && 
           $task['status'] !== 'inactive';
}

echo "Task Analysis:\n";
echo "ID | Title                | Status    | Deadline   | Overdue? | Targeted Today? | Include?\n";
echo "---|----------------------|-----------|------------|----------|-----------------|----------\n";

$overdueCount = 0;
$targetedTodayCount = 0;
$totalOverdueIncludingInactive = 0;
$totalTargetedIncludingInactive = 0;

foreach ($testTasks as $task) {
    $isOverdue = isTaskOverdue($task);
    $isTargetedToday = isTaskTargetedToday($task, $todayStart, $todayEnd);
    $wouldBeOverdueIfActive = ($task['deadline'] < time() && $task['status'] !== 'completed');
    $wouldBeTargetedIfActive = ($task['target_start_date'] !== null &&
                               $task['target_end_date'] !== null &&
                               $task['target_start_date'] <= $todayEnd &&
                               $task['target_end_date'] >= $todayStart &&
                               $task['status'] !== 'completed');
    
    if ($isOverdue) $overdueCount++;
    if ($isTargetedToday) $targetedTodayCount++;
    if ($wouldBeOverdueIfActive) $totalOverdueIncludingInactive++;
    if ($wouldBeTargetedIfActive) $totalTargetedIncludingInactive++;
    
    $includeInStats = ($isOverdue || $isTargetedToday) ? 'Yes' : 'No';
    
    echo sprintf(
        "%2d | %-20s | %-9s | %s | %-8s | %-15s | %s\n",
        $task['id'],
        $task['title'],
        $task['status'],
        date('Y-m-d', $task['deadline']),
        $isOverdue ? 'Yes' : 'No',
        $isTargetedToday ? 'Yes' : 'No',
        $includeInStats
    );
}

echo "\nStatistics Summary:\n";
echo "==================\n";
echo "Overdue Tasks (excluding inactive): $overdueCount\n";
echo "Overdue Tasks (including inactive): $totalOverdueIncludingInactive\n";
echo "Targeted Today Tasks (excluding inactive): $targetedTodayCount\n";
echo "Targeted Today Tasks (including inactive): $totalTargetedIncludingInactive\n\n";

echo "Expected Behavior:\n";
echo "- Task #1 (Active Overdue): ✅ Should be counted as overdue\n";
echo "- Task #2 (Inactive Overdue): ❌ Should NOT be counted as overdue\n";
echo "- Task #3 (Completed Overdue): ❌ Should NOT be counted as overdue\n";
echo "- Task #4 (Active Future): ✅ Should be counted if targeted today\n";
echo "- Task #5 (Inactive Future): ❌ Should NOT be counted even if targeted today\n\n";

echo "Code Changes Applied:\n";
echo "1. KanbanBoard::getStatistics() - Added ->andWhere(['!=', 'status', 'inactive'])\n";
echo "2. Task::isOverdue() - Added && \$this->status !== 'inactive'\n";
echo "3. Task::getTasksTargetedForTodayCount() - Added ->andWhere(['!=', 'status', 'inactive'])\n";
echo "4. KanbanBoard::getTasksTargetedForToday() - Added ->andWhere(['!=', 'status', 'inactive'])\n";
echo "5. KanbanBoard::getTasksByDeadlineCategory() - Added ->andWhere(['!=', 'status', 'inactive'])\n";

?>