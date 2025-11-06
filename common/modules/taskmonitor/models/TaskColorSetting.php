<?php

namespace common\modules\taskmonitor\models;

use Yii;
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
class TaskColorSetting extends \yii\db\ActiveRecord
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
            TimestampBehavior::className(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'days_before_deadline', 'color'], 'required'],
            [['days_before_deadline', 'sort_order', 'is_active', 'created_at', 'updated_at'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['color'], 'string', 'max' => 7],
            [['color'], 'match', 'pattern' => '/^#[0-9A-F]{6}$/i', 'message' => 'Color must be a valid hex code (e.g., #FF0000)'],
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
            'color' => 'Color (Hex)',
            'sort_order' => 'Sort Order',
            'is_active' => 'Active',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Get all active color settings ordered by days_before_deadline
     */
    public static function getActiveSettings()
    {
        return self::find()
            ->where(['is_active' => 1])
            ->orderBy(['days_before_deadline' => SORT_ASC])
            ->all();
    }

    /**
     * Get color based on days until deadline
     */
    public static function getColorForDeadline($deadline)
    {
        $now = time();
        $daysUntil = floor(($deadline - $now) / 86400); // 86400 seconds in a day

        $settings = self::getActiveSettings();

        foreach ($settings as $setting) {
            if ($daysUntil <= $setting->days_before_deadline) {
                return $setting->color;
            }
        }

        // Return default color if no match found
        return '#388E3C'; // Green
    }
}
