<?php

namespace common\modules\taskmonitor\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "task_categories".
 *
 * @property int $id
 * @property int $parent_id
 * @property string $name
 * @property string $description
 * @property string $icon
 * @property string $color
 * @property int $sort_order
 * @property int $is_active
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Task[] $tasks
 * @property TaskCategory $parent
 * @property TaskCategory[] $children
 */
class TaskCategory extends \yii\db\ActiveRecord
{
    /**
     * Display level used when building hierarchical lists (not stored in DB)
     *
     * @var int
     */
    public $level = 0;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_categories';
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
            [['name'], 'required'],
            [['description'], 'string'],
            [['parent_id', 'sort_order', 'is_active', 'created_at', 'updated_at'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['icon'], 'string', 'max' => 50],
            [['color'], 'string', 'max' => 7],
            [['color'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/', 'message' => 'Color must be a valid hex color (e.g., #ff0000)'],
            [['color'], 'default', 'value' => '#007bff'],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => TaskCategory::className(), 'targetAttribute' => ['parent_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_id' => 'Parent Category',
            'name' => 'Category Name',
            'description' => 'Description',
            'icon' => 'Icon',
            'color' => 'Color',
            'sort_order' => 'Sort Order',
            'is_active' => 'Active',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Tasks]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTasks()
    {
        return $this->hasMany(Task::className(), ['category_id' => 'id']);
    }

    /**
     * Get active tasks count
     */
    public function getActiveTasksCount()
    {
        return $this->getTasks()
            ->where(['!=', 'status', 'completed'])
            ->count();
    }

    /**
     * Get tasks count by deadline urgency
     */
    public function getUrgentTasksCount($days = 30)
    {
        $deadline = time() + ($days * 86400);
        return $this->getTasks()
            ->where(['!=', 'status', 'completed'])
            ->andWhere(['<=', 'deadline', $deadline])
            ->count();
    }

    /**
     * Get category color based on urgency
     */
    public function getCategoryColor()
    {
        // Get the earliest deadline from tasks in this category
        $earliestTask = $this->getTasks()
            ->where(['!=', 'status', 'completed'])
            ->orderBy(['deadline' => SORT_ASC])
            ->one();

        if ($earliestTask) {
            return TaskColorSetting::getColorForDeadline($earliestTask->deadline);
        }

        return '#757575'; // Gray for categories with no tasks
    }

    /**
     * Gets query for [[Parent]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(TaskCategory::className(), ['id' => 'parent_id']);
    }

    /**
     * Gets query for [[Children]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(TaskCategory::className(), ['parent_id' => 'id'])->where(['is_active' => 1])->orderBy(['sort_order' => SORT_ASC]);
    }

    /**
     * Get all active categories
     */
    public static function getActiveCategories()
    {
        return self::find()
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();
    }

    /**
     * Get root categories (categories without parent)
     */
    public static function getRootCategories()
    {
        return self::find()
            ->where(['is_active' => 1, 'parent_id' => null])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();
    }

    /**
     * Get hierarchical categories
     */
    public static function getHierarchicalCategories()
    {
        $categories = [];
        $rootCategories = self::getRootCategories();

        foreach ($rootCategories as $root) {
            $categories[] = $root;
            // Add children with indentation
            self::addChildrenToList($categories, $root, 1);
        }

        return $categories;
    }

    /**
     * Recursively add children to category list
     */
    private static function addChildrenToList(&$list, $parent, $level)
    {
        foreach ($parent->children as $child) {
            $child->level = $level; // Store level for display purposes
            $list[] = $child;
            self::addChildrenToList($list, $child, $level + 1);
        }
    }

    /**
     * Get category dropdown list
     */
    public static function getCategoryDropdownList($excludeId = null)
    {
        $categories = self::getHierarchicalCategories();
        $list = [];

        foreach ($categories as $category) {
            if ($excludeId && $category->id == $excludeId) {
                continue; // Skip the current category to prevent self-parenting
            }
            $prefix = str_repeat('— ', isset($category->level) ? $category->level : 0);
            $list[$category->id] = $prefix . $category->name;
        }

        return $list;
    }

    /**
     * Get full category path (breadcrumb)
     */
    public function getFullPath($separator = ' > ')
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode($separator, $path);
    }

    /**
     * Check if this category has children
     */
    public function hasChildren()
    {
        return count($this->children) > 0;
    }

    /**
     * Get all tasks including from subcategories
     */
    public function getAllTasks()
    {
        $taskIds = [];
        $this->collectTaskIds($taskIds);

        return Task::find()->where(['id' => $taskIds])->all();
    }

    /**
     * Recursively collect task IDs from this category and all subcategories
     */
    private function collectTaskIds(&$taskIds)
    {
        foreach ($this->tasks as $task) {
            $taskIds[] = $task->id;
        }

        foreach ($this->children as $child) {
            $child->collectTaskIds($taskIds);
        }
    }

    /**
     * Get active tasks count including subcategories
     */
    public function getTotalActiveTasksCount()
    {
        $count = $this->getActiveTasksCount();

        foreach ($this->children as $child) {
            $count += $child->getTotalActiveTasksCount();
        }

        return $count;
    }
}
