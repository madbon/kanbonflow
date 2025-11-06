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
        
        foreach ($columns as $column) {
            $tasks[$column->status_key] = Task::find()
                ->where(['status' => $column->status_key])
                ->with(['category', 'images'])
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