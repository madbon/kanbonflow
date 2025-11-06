<?php

namespace common\modules\taskmonitor\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "task_images".
 *
 * @property int $id
 * @property int $task_id
 * @property string $filename
 * @property string $original_name
 * @property string $file_path
 * @property int $file_size
 * @property string $mime_type
 * @property int $sort_order
 * @property int $created_at
 *
 * @property Task $task
 */
class TaskImage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_images';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'updatedAtAttribute' => false,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['task_id', 'filename', 'original_name', 'file_path', 'file_size'], 'required'],
            [['task_id', 'file_size', 'sort_order', 'created_at'], 'integer'],
            [['filename', 'original_name'], 'string', 'max' => 255],
            [['file_path'], 'string', 'max' => 500],
            [['mime_type'], 'string', 'max' => 50],
            [['task_id'], 'exist', 'skipOnError' => true, 'targetClass' => Task::className(), 'targetAttribute' => ['task_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'Task ID',
            'filename' => 'Filename',
            'original_name' => 'Original Name',
            'file_path' => 'File Path',
            'file_size' => 'File Size',
            'mime_type' => 'Mime Type',
            'sort_order' => 'Sort Order',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Task]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Task::className(), ['id' => 'task_id']);
    }

    /**
     * Get image URL
     */
    public function getImageUrl()
    {
        return Yii::getAlias('@web') . '/' . $this->file_path;
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSize()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    /**
     * Before delete - remove file
     */
    public function beforeDelete()
    {
        if (parent::beforeDelete()) {
            $filePath = Yii::getAlias('@webroot') . '/' . $this->file_path;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return true;
        }
        return false;
    }
}
