<?php

namespace common\modules\kanban\models;

use Yii;
use common\modules\taskmonitor\models\Task;
use common\modules\taskmonitor\models\TaskCategory;
use common\modules\taskmonitor\models\TaskColorSettings;
use common\modules\kanban\models\KanbanColumn;

/**
 * KanbanBoard model for managing board operations
 */
class KanbanBoard
{
    /**
     * Get all tasks grouped by status columns
     * @return array
     */
    public static function getTasksByColumns()
    {
        $columns = KanbanColumn::getActiveColumns();
        $tasks = [];
        
        // Calculate timestamp for 7 days ago
        $sevenDaysAgo = time() - (7 * 24 * 60 * 60);
        
        foreach ($columns as $column) {
            $query = Task::find()
                ->where(['status' => $column->status_key])
                ->with(['category', 'images']);
            
            // For completed tasks, only show those completed within the last 7 days
            if ($column->status_key === Task::STATUS_COMPLETED) {
                $query->andWhere(['>=', 'completed_at', $sevenDaysAgo]);
            }
            
            $tasks[$column->status_key] = $query
                ->orderBy(['position' => SORT_ASC, 'created_at' => SORT_DESC])
                ->all();
        }

        return $tasks;
    }

    /**
     * Get task statistics for the board based on task_color_settings
     * @return array
     */
    public static function getStatistics()
    {
        $now = time();
        $today = strtotime('today');
        $tomorrow = strtotime('tomorrow');
        
        // Get deadline ranges from task_color_settings
        $settings = TaskColorSettings::getActiveSettings();
        $statistics = [];
        
        // Base query for non-completed tasks
        $baseQuery = Task::find()->andWhere(['!=', 'status', Task::STATUS_COMPLETED]);
        
        // Add special "Due Today" category first
        $todayQuery = clone $baseQuery;
        $todayCount = $todayQuery->andWhere(['>=', 'deadline', $today])
            ->andWhere(['<', 'deadline', $tomorrow])
            ->count();
            
        $statistics['due_today'] = [
            'count' => $todayCount,
            'name' => 'Due Today',
            'color' => '#FF5722',
            'icon' => 'fa-calendar-day',
            'display_name' => 'Due Today',
            'days_before_deadline' => 0,
            'sort_order' => 0,
        ];
        
        foreach ($settings as $index => $setting) {
            $key = strtolower(str_replace(' ', '_', $setting->name));
            
            if ($setting->name === 'Overdue') {
                // Overdue tasks (deadline < today)
                $query = clone $baseQuery;
                $count = $query->andWhere(['<', 'deadline', $today])->count();
            } else {
                // Calculate date range for this setting
                $currentDays = $setting->days_before_deadline;
                $nextSetting = isset($settings[$index + 1]) ? $settings[$index + 1] : null;
                $nextDays = $nextSetting ? $nextSetting->days_before_deadline : 365;
                
                $fromDate = strtotime("+{$currentDays} days", $today);
                $toDate = strtotime("+{$nextDays} days", $today);
                
                $query = clone $baseQuery;
                $count = $query->andWhere(['>=', 'deadline', $fromDate])
                    ->andWhere(['<', 'deadline', $toDate])
                    ->count();
            }
            
            $statistics[$key] = [
                'count' => $count,
                'name' => $setting->name,
                'color' => $setting->color,
                'icon' => $setting->getIcon(),
                'display_name' => $setting->getDisplayName(),
                'days_before_deadline' => $setting->days_before_deadline,
                'sort_order' => $setting->sort_order,
            ];
        }
        
        // Add completion statistics
        $completionStats = self::getCompletionStatistics();
        $statistics = array_merge($statistics, $completionStats);
        
        // Sort statistics by sort_order
        uasort($statistics, function($a, $b) {
            if ($a['sort_order'] == $b['sort_order']) {
                return 0;
            }
            return ($a['sort_order'] < $b['sort_order']) ? -1 : 1;
        });
        
        return $statistics;
    }

    /**
     * Get completion statistics for the board
     * @return array
     */
    public static function getCompletionStatistics()
    {
        // Count completed tasks
        $completedCount = Task::find()
            ->where(['status' => Task::STATUS_COMPLETED])
            ->count();
            
        // Count not completed tasks
        $notCompletedCount = Task::find()
            ->where(['!=', 'status', Task::STATUS_COMPLETED])
            ->count();
            
        return [
            'completion_status' => [
                'count' => $completedCount + $notCompletedCount,
                'completed_count' => $completedCount,
                'not_completed_count' => $notCompletedCount,
                'name' => 'Task Completion',
                'color' => '#28a745',
                'icon' => 'fa-check-circle',
                'display_name' => 'Task Completion',
                'sort_order' => 999, // Put it at the end
            ]
        ];
    }

    /**
     * Get completion tasks by status
     * @return array
     */
    public static function getCompletionTasks()
    {
        // Get completed tasks
        $completedTasks = Task::find()
            ->where(['status' => Task::STATUS_COMPLETED])
            ->with(['category', 'images'])
            ->orderBy(['completed_at' => SORT_DESC, 'created_at' => SORT_DESC])
            ->all();
            
        // Get not completed tasks
        $notCompletedTasks = Task::find()
            ->where(['!=', 'status', Task::STATUS_COMPLETED])
            ->with(['category', 'images'])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
            
        return [
            'completed' => $completedTasks,
            'not_completed' => $notCompletedTasks
        ];
    }

    /**
     * Get category-based statistics for the board
     * @return array
     */
    public static function getCategoryStatistics()
    {
        $categories = TaskCategory::find()->all();
        $statistics = [];
        
        foreach ($categories as $category) {
            // Count completed tasks in this category
            $completedCount = Task::find()
                ->where(['category_id' => $category->id])
                ->andWhere(['status' => Task::STATUS_COMPLETED])
                ->count();
                
            // Count not completed tasks in this category
            $notCompletedCount = Task::find()
                ->where(['category_id' => $category->id])
                ->andWhere(['!=', 'status', Task::STATUS_COMPLETED])
                ->count();
                
            $totalCount = $completedCount + $notCompletedCount;
            
            if ($notCompletedCount > 0) { // Only include categories that have non-completed tasks
                $statistics['category_' . $category->id] = [
                    'count' => $notCompletedCount, // Show only non-completed count
                    'completed_count' => $completedCount,
                    'not_completed_count' => $notCompletedCount,
                    'category_id' => $category->id,
                    'name' => $category->name,
                    'color' => $category->color ?: '#6c757d',
                    'icon' => $category->icon ?: 'fa-folder',
                    'display_name' => $category->name . ' Tasks',
                    'sort_order' => 1000 + $category->id, // Put after completion stats
                ];
            }
        }
        
        // Add "No Category" tasks if they exist
        $noCategoryCompleted = Task::find()
            ->where(['category_id' => null])
            ->andWhere(['status' => Task::STATUS_COMPLETED])
            ->count();
            
        $noCategoryNotCompleted = Task::find()
            ->where(['category_id' => null])
            ->andWhere(['!=', 'status', Task::STATUS_COMPLETED])
            ->count();
            
        $noCategoryTotal = $noCategoryCompleted + $noCategoryNotCompleted;
        
        if ($noCategoryNotCompleted > 0) { // Only show if there are non-completed tasks
            $statistics['category_none'] = [
                'count' => $noCategoryNotCompleted, // Show only non-completed count
                'completed_count' => $noCategoryCompleted,
                'not_completed_count' => $noCategoryNotCompleted,
                'category_id' => null,
                'name' => 'No Category',
                'color' => '#6c757d',
                'icon' => 'fa-question-circle',
                'display_name' => 'Uncategorized Tasks',
                'sort_order' => 2000, // Put at the end
            ];
        }
        
        return $statistics;
    }

    /**
     * Get tasks by category and completion status
     * @param int|null $categoryId
     * @return array
     */
    public static function getCategoryCompletionTasks($categoryId = null)
    {
        $baseQuery = Task::find()->with(['category', 'images']);
        
        if ($categoryId === null) {
            $baseQuery->where(['category_id' => null]);
        } else {
            $baseQuery->where(['category_id' => $categoryId]);
        }
        
        // Get completed tasks
        $completedQuery = clone $baseQuery;
        $completedTasks = $completedQuery
            ->andWhere(['status' => Task::STATUS_COMPLETED])
            ->orderBy(['completed_at' => SORT_DESC, 'created_at' => SORT_DESC])
            ->all();
            
        // Get not completed tasks
        $notCompletedQuery = clone $baseQuery;
        $notCompletedTasks = $notCompletedQuery
            ->andWhere(['!=', 'status', Task::STATUS_COMPLETED])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
            
        return [
            'completed' => $completedTasks,
            'not_completed' => $notCompletedTasks
        ];
    }

    /**
     * Get column configuration
     * @return array
     */
    public static function getColumns()
    {
        return KanbanColumn::getColumnsConfig();
    }

    /**
     * Get active columns
     * @return KanbanColumn[]
     */
    public static function getActiveColumns()
    {
        return KanbanColumn::getActiveColumns();
    }

    /**
     * Get priority color classes
     * @param string $priority
     * @return string
     */
    public static function getPriorityClass($priority)
    {
        $classes = [
            Task::PRIORITY_LOW => 'priority-low',
            Task::PRIORITY_MEDIUM => 'priority-medium',
            Task::PRIORITY_HIGH => 'priority-high',
            Task::PRIORITY_CRITICAL => 'priority-critical',
        ];

        return isset($classes[$priority]) ? $classes[$priority] : 'priority-medium';
    }

    /**
     * Convert hex color to RGB array
     * @param string $hex
     * @return array
     */
    public static function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
    }

    /**
     * Determine if a color is light or dark for text readability
     * @param string $hex
     * @return bool
     */
    public static function isLightColor($hex)
    {
        $rgb = self::hexToRgb($hex);
        $brightness = (($rgb[0] * 299) + ($rgb[1] * 587) + ($rgb[2] * 114)) / 1000;
        return $brightness > 155;
    }

    /**
     * Move task to different status
     * @param int $taskId
     * @param string $newStatus
     * @return bool
     */
    public static function moveTask($taskId, $newStatus)
    {
        $task = Task::findOne($taskId);
        if (!$task) {
            return false;
        }

        // Check if the status exists in active columns
        $validColumn = KanbanColumn::find()
            ->where(['status_key' => $newStatus, 'is_active' => 1])
            ->exists();
            
        if (!$validColumn) {
            return false;
        }

        $task->status = $newStatus;
        return $task->save();
    }

    /**
     * Get tasks by deadline category
     * @param string $categoryKey
     * @return Task[]
     */
    public static function getTasksByDeadlineCategory($categoryKey)
    {
        $today = strtotime('today');
        $tomorrow = strtotime('tomorrow');
        
        // Base query for non-completed tasks
        $query = Task::find()
            ->with(['category'])
            ->andWhere(['!=', 'status', Task::STATUS_COMPLETED]);
        
        switch ($categoryKey) {
            case 'overdue':
                $query->andWhere(['<', 'deadline', $today]);
                break;
                
            case 'due_today':
                $query->andWhere(['>=', 'deadline', $today])
                      ->andWhere(['<', 'deadline', $tomorrow]);
                break;
                
            default:
                // For configured deadline ranges, find the setting by name
                $settings = TaskColorSettings::find()
                    ->where(['is_active' => 1])
                    ->all();
                    
                $targetSetting = null;
                foreach ($settings as $setting) {
                    $key = strtolower(str_replace(' ', '_', $setting->name));
                    if ($key === $categoryKey) {
                        $targetSetting = $setting;
                        break;
                    }
                }
                    
                if ($targetSetting) {
                    if ($targetSetting->name === 'Overdue') {
                        $query->andWhere(['<', 'deadline', $today]);
                    } else {
                        // Get all settings to determine range
                        $allSettings = TaskColorSettings::getActiveSettings();
                        $currentIndex = null;
                        
                        foreach ($allSettings as $index => $setting) {
                            if ($setting->id === $targetSetting->id) {
                                $currentIndex = $index;
                                break;
                            }
                        }
                        
                        if ($currentIndex !== null) {
                            $currentDays = $targetSetting->days_before_deadline;
                            $nextSetting = isset($allSettings[$currentIndex + 1]) ? $allSettings[$currentIndex + 1] : null;
                            $nextDays = $nextSetting ? $nextSetting->days_before_deadline : 365;
                            
                            $fromDate = strtotime("+{$currentDays} days", $today);
                            $toDate = strtotime("+{$nextDays} days", $today);
                            
                            $query->andWhere(['>=', 'deadline', $fromDate])
                                  ->andWhere(['<', 'deadline', $toDate]);
                        }
                    }
                } else {
                    // Invalid category, return empty result
                    return [];
                }
                break;
        }
        
        return $query->orderBy([
            'deadline' => SORT_ASC,
            'priority' => SORT_DESC,
            'created_at' => SORT_DESC
        ])->all();
    }
}