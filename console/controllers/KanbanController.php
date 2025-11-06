<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\modules\taskmonitor\models\Task;
use common\modules\taskmonitor\models\TaskCategory;

/**
 * Kanban sample data controller
 */
class KanbanController extends Controller
{
    /**
     * Create sample data for the Kanban board
     */
    public function actionCreateSampleData()
    {
        echo "Setting up sample data for Kanban board...\n";

        // Check if we have categories
        $categories = TaskCategory::find()->all();
        if (empty($categories)) {
            echo "No categories found. Creating sample categories...\n";
            
            $sampleCategories = [
                ['name' => 'Development', 'color' => '#007bff'],
                ['name' => 'Design', 'color' => '#28a745'],
                ['name' => 'Testing', 'color' => '#ffc107'],
                ['name' => 'Documentation', 'color' => '#17a2b8'],
            ];
            
            foreach ($sampleCategories as $catData) {
                $category = new TaskCategory();
                $category->name = $catData['name'];
                $category->color = $catData['color'];
                if ($category->save()) {
                    echo "Created category: {$category->name}\n";
                }
            }
            
            $categories = TaskCategory::find()->all();
        } else {
            echo "Found " . count($categories) . " categories.\n";
        }

        // Check existing tasks
        $taskCount = Task::find()->count();
        echo "Found {$taskCount} existing tasks.\n";

        if ($taskCount == 0) {
            echo "Creating sample tasks...\n";
            
            // Create sample tasks
            $sampleTasks = [
                ['title' => 'Design Homepage Layout', 'status' => 'pending', 'priority' => 'high', 'category' => 'Design'],
                ['title' => 'Implement User Authentication', 'status' => 'in_progress', 'priority' => 'critical', 'category' => 'Development'],
                ['title' => 'Write API Documentation', 'status' => 'pending', 'priority' => 'medium', 'category' => 'Documentation'],
                ['title' => 'Setup Database Schema', 'status' => 'completed', 'priority' => 'high', 'category' => 'Development'],
                ['title' => 'Create REST API Endpoints', 'status' => 'in_progress', 'priority' => 'high', 'category' => 'Development'],
                ['title' => 'Test Payment Integration', 'status' => 'pending', 'priority' => 'critical', 'category' => 'Testing'],
                ['title' => 'Deploy to Production', 'status' => 'completed', 'priority' => 'high', 'category' => 'Development'],
                ['title' => 'Create User Interface Mockups', 'status' => 'pending', 'priority' => 'medium', 'category' => 'Design'],
                ['title' => 'Performance Testing', 'status' => 'in_progress', 'priority' => 'medium', 'category' => 'Testing'],
                ['title' => 'Security Audit', 'status' => 'pending', 'priority' => 'critical', 'category' => 'Testing'],
            ];
            
            foreach ($sampleTasks as $index => $taskData) {
                // Find category by name
                $category = null;
                foreach ($categories as $cat) {
                    if ($cat->name === $taskData['category']) {
                        $category = $cat;
                        break;
                    }
                }
                
                if (!$category) {
                    $category = $categories[0]; // Use first category as fallback
                }
                
                $task = new Task();
                $task->category_id = $category->id;
                $task->title = $taskData['title'];
                $task->description = 'Sample task for testing the Kanban board functionality. This task involves: ' . $taskData['title'];
                $task->status = $taskData['status'];
                $task->priority = $taskData['priority'];
                $task->deadline = time() + (rand(1, 30) * 24 * 60 * 60); // Random deadline in next 30 days
                $task->position = $index;
                
                if ($task->save()) {
                    echo "Created: {$task->title} (Status: {$task->status}, Priority: {$task->priority}, Category: {$category->name})\n";
                } else {
                    echo "Failed to create task: {$taskData['title']}\n";
                    print_r($task->errors);
                }
            }
        } else {
            echo "Tasks already exist. Skipping creation.\n";
        }

        echo "\nSample data setup complete!\n";
        echo "You can now access the Kanban board at: http://your-domain/backend/web/kanban/board\n";
        echo "Make sure to start your web server and navigate to the backend application.\n";
    }
}