<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\modules\taskmonitor\models\TaskComment;
use common\modules\taskmonitor\models\Task;

class TestController extends Controller
{
    public function actionComment()
    {
        echo "Testing comment creation...\n\n";
        
        // Get a sample task
        $task = Task::find()->one();
        if (!$task) {
            echo "No tasks found in database!\n";
            return;
        }
        
        echo "Found task: ID {$task->id}, Title: {$task->title}\n";
        
        // Test comment creation
        $comment = new TaskComment();
        $comment->task_id = $task->id;
        $comment->user_id = 1; // Set a default user ID
        $comment->comment = "Test comment from console";
        $comment->is_internal = false;
        
        echo "\nAttempting to save comment...\n";
        echo "Task ID: {$comment->task_id}\n";
        echo "User ID: {$comment->user_id}\n";
        echo "Comment: {$comment->comment}\n";
        
        if ($comment->save()) {
            echo "SUCCESS: Comment saved with ID: {$comment->id}\n";
        } else {
            echo "FAILED: Could not save comment\n";
            echo "Validation errors:\n";
            foreach ($comment->errors as $attribute => $errors) {
                echo "  $attribute: " . implode(', ', $errors) . "\n";
            }
        }
        
        // Test the static method
        echo "\n\nTesting TaskComment::addComment() method...\n";
        $result = TaskComment::addComment($task->id, "Test comment via static method", null, false);
        
        if ($result) {
            echo "SUCCESS: Static method created comment with ID: {$result->id}\n";
        } else {
            echo "FAILED: Static method failed to create comment\n";
        }
        
        // Show total comments for this task
        $count = TaskComment::getCommentCount($task->id);
        echo "\nTotal comments for task {$task->id}: {$count}\n";
    }
}