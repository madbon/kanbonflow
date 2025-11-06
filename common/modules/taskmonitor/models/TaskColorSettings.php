<?php

namespace common\modules\taskmonitor\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "task_color_settings".
 *
 * @property int $id
 * @property string $name
 * @property int $days_before_deadline
 * @property string $color
 * @property int $sort_order
 * @property int $is_active
 * @property int $created_at
 * @property int $updated_at
 */
class TaskColorSettings extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_color_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'days_before_deadline', 'color'], 'required'],
            [['days_before_deadline', 'sort_order', 'is_active'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['color'], 'string', 'max' => 7],
            [['color'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/', 'message' => 'Color must be a valid hex color code (e.g., #FF0000)'],
            [['days_before_deadline'], 'integer', 'min' => 0],
            [['sort_order'], 'default', 'value' => 0],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Setting Name',
            'days_before_deadline' => 'Days Before Deadline',
            'color' => 'Color',
            'sort_order' => 'Sort Order',
            'is_active' => 'Is Active',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Get active color settings ordered by sort_order
     */
    public static function getActiveSettings()
    {
        return self::find()
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();
    }

    /**
     * Get color for a specific number of days before deadline
     * @param int $daysBefore
     * @return string|null
     */
    public static function getColorForDays($daysBefore)
    {
        $setting = self::find()
            ->where(['is_active' => 1])
            ->andWhere(['<=', 'days_before_deadline', $daysBefore])
            ->orderBy(['days_before_deadline' => SORT_DESC])
            ->one();
        
        return $setting ? $setting->color : null;
    }

    /**
     * Get setting by days before deadline
     * @param int $daysBefore
     * @return TaskColorSettings|null
     */
    public static function getSettingForDays($daysBefore)
    {
        return self::find()
            ->where(['is_active' => 1])
            ->andWhere(['<=', 'days_before_deadline', $daysBefore])
            ->orderBy(['days_before_deadline' => SORT_DESC])
            ->one();
    }

    /**
     * Get all settings with their deadline ranges for statistics
     * @return array
     */
    public static function getDeadlineRanges()
    {
        $settings = self::getActiveSettings();
        $ranges = [];
        
        foreach ($settings as $index => $setting) {
            $nextSetting = isset($settings[$index + 1]) ? $settings[$index + 1] : null;
            
            $ranges[] = [
                'name' => $setting->name,
                'color' => $setting->color,
                'days_before_deadline' => $setting->days_before_deadline,
                'min_days' => $setting->days_before_deadline,
                'max_days' => $nextSetting ? $nextSetting->days_before_deadline - 1 : 365, // Assume max 1 year
                'sort_order' => $setting->sort_order,
            ];
        }
        
        return $ranges;
    }

    /**
     * Get icon for deadline setting based on name
     * @return string
     */
    public function getIcon()
    {
        $icons = [
            'Overdue' => 'fa-exclamation-triangle',
            'Critical' => 'fa-fire',
            'Warning' => 'fa-clock',
            'Upcoming' => 'fa-calendar-week',
            'Safe' => 'fa-calendar-check',
        ];
        
        return isset($icons[$this->name]) ? $icons[$this->name] : 'fa-calendar';
    }

    /**
     * Get formatted name for display
     * @return string
     */
    public function getDisplayName()
    {
        if ($this->name === 'Overdue') {
            return 'Overdue';
        }
        
        return $this->name . ' (' . $this->days_before_deadline . ' days)';
    }
}