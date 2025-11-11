<?php
/**
 * Simple test to add some checklist items to an existing task for testing the view functionality
 */

// Navigate to the project root and include Yii bootstrap
chdir(dirname(__FILE__));
require_once 'yii';

use common\modules\taskmonitor\models\Task;
use common\modules\taskmonitor\models\TaskChecklist;

try {
    echo "=== ADDING TEST CHECKLIST ITEMS ===\n\n";
    
    // Find the most recent task
    $task = Task::find()->orderBy(['id' => SORT_DESC])->one();
    
    if (!$task) {
        echo "No tasks found. Please create a task first through the web interface.\n";
        exit;
    }
    
    echo "Adding checklist items to Task ID: {$task->id} - '{$task->title}'\n\n";
    
    // Clear existing checklist items for this task
    TaskChecklist::deleteAll(['task_id' => $task->id]);
    echo "Cleared existing checklist items\n";
    
    // Add some sample checklist items
    $sampleItems = [
        'Review project requirements and scope',
        'Set up development environment',
        'Design database schema',
        'Implement core functionality',
        'Write comprehensive tests',
        'Create user documentation',
        'Deploy to staging environment',
        'Conduct user acceptance testing',
        'Fix any reported bugs',
        'Deploy to production'
    ];
    
    foreach ($sampleItems as $index => $stepText) {
        $checklist = new TaskChecklist();
        $checklist->task_id = $task->id;
        $checklist->step_text = $stepText;
        $checklist->sort_order = $index;
        $checklist->is_completed = false;
        
        if ($checklist->save()) {
            echo "✓ Added: '{$stepText}'\n";
        } else {
            echo "✗ Failed to add: '{$stepText}'\n";
        }
    }
    
    // Mark the first 3 items as completed for testing
    $items = TaskChecklist::getTaskChecklistItems($task->id);
    for ($i = 0; $i < min(3, count($items)); $i++) {
        if ($items[$i]->markCompleted(1)) {
            echo "✓ Marked as completed: '{$items[$i]->step_text}'\n";
        }
    }
    
    $progress = TaskChecklist::getTaskChecklistProgress($task->id);
    echo "\nProgress: {$progress['completed']}/{$progress['total']} ({$progress['percentage']}%)\n";
    
    echo "\n=== TEST DATA READY ===\n";
    echo "Now you can:\n";
    echo "1. Open the Kanban board in your browser\n";
    echo "2. Click on the task '{$task->title}' to view details\n";
    echo "3. You should see the interactive checklist with checkboxes\n";
    echo "4. Try checking/unchecking items to see real-time updates\n";
    echo "5. Click the 'Edit' button next to 'Checklist' to modify items\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}