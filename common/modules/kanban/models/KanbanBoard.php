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
        
        // Get deadline ranges from task_color_settings
        $settings = TaskColorSettings::getActiveSettings();
        $statistics = [];
        
        // Base query for non-completed tasks
        $baseQuery = Task::find()->andWhere(['!=', 'status', Task::STATUS_COMPLETED]);
        
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
}