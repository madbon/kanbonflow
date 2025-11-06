<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\modules\taskmonitor\models\TaskCategory;

$this->title = 'Manage Categories';
$this->params['breadcrumbs'][] = ['label' => 'Task Monitor', 'url' => ['/taskmonitor/default/index']];
$this->params['breadcrumbs'][] = $this->title;

// Get hierarchical categories
$hierarchicalCategories = TaskCategory::getHierarchicalCategories();
?>

<div class="task-category-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <div class="header-actions">
            <?= Html::a('<i class="fa fa-arrow-left"></i> Back to Tasks', ['/taskmonitor/default/index'], ['class' => 'btn btn-secondary']) ?>
            <?= Html::a('<i class="fa fa-plus"></i> Create Category', ['create'], ['class' => 'btn btn-success']) ?>
        </div>
    </div>

    <div class="categories-grid">
        <?php foreach ($hierarchicalCategories as $category): ?>
            <?php
            $level = isset($category->level) ? $category->level : 0;
            $marginLeft = $level * 20;
            ?>
            <div class="category-card" style="margin-left: <?= $marginLeft ?>px; <?= $level > 0 ? 'border-left: 3px solid #667eea;' : '' ?>">
                <div class="category-card-icon">
                    <i class="fa <?= Html::encode($category->icon ?: 'fa-folder') ?>"></i>
                </div>
                <div class="category-card-body">
                    <h3>
                        <?php if ($level > 0): ?>
                            <small class="text-muted"><?= str_repeat('└─ ', $level) ?></small>
                        <?php endif; ?>
                        <?= Html::encode($category->name) ?>
                    </h3>
                    <?php if ($category->parent): ?>
                        <p class="parent-path"><i class="fa fa-folder-open"></i> <?= Html::encode($category->getFullPath()) ?></p>
                    <?php endif; ?>
                    <p><?= Html::encode($category->description) ?></p>
                    <div class="category-stats">
                        <span><i class="fa fa-tasks"></i> <?= $category->getTotalActiveTasksCount() ?> tasks</span>
                        <?php if ($category->hasChildren()): ?>
                            <span class="text-info"><i class="fa fa-sitemap"></i> <?= count($category->children) ?> subcategories</span>
                        <?php endif; ?>
                        <span class="badge <?= $category->is_active ? 'badge-success' : 'badge-danger' ?>">
                            <?= $category->is_active ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                </div>
                <div class="category-card-actions">
                    <?= Html::a('<i class="fa fa-plus"></i> Sub', ['create', 'parent_id' => $category->id], ['class' => 'btn btn-sm btn-info', 'title' => 'Add Subcategory']) ?>
                    <?= Html::a('<i class="fa fa-edit"></i> Edit', ['update', 'id' => $category->id], ['class' => 'btn btn-sm btn-primary']) ?>
                    <?= Html::a('<i class="fa fa-trash"></i> Delete', ['delete', 'id' => $category->id], [
                        'class' => 'btn btn-sm btn-danger delete-category',
                        'data-id' => $category->id,
                    ]) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$this->registerCss("
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.page-header h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.category-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.category-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.category-card-icon {
    padding: 30px;
    text-align: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-size: 48px;
}

.category-card-body {
    padding: 20px;
}

.category-card-body h3 {
    margin: 0 0 10px 0;
    font-size: 20px;
    font-weight: 600;
    color: #2c3e50;
}

.category-card-body p {
    margin: 0 0 15px 0;
    color: #6c757d;
    font-size: 14px;
}

.category-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    color: #6c757d;
}

.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.badge-success {
    background: #4caf50;
    color: white;
}

.badge-danger {
    background: #dc3545;
    color: white;
}

.category-card-actions {
    padding: 15px 20px;
    background: #f8f9fa;
    display: flex;
    gap: 10px;
    border-top: 1px solid #e9ecef;
}

.parent-path {
    font-size: 12px;
    color: #6c757d;
    margin: 5px 0;
}

.text-info {
    color: #17a2b8;
}

.text-muted {
    color: #6c757d !important;
}
");

$baseDeleteUrl = Url::to(['/taskmonitor/category/delete']);
$csrfToken = Yii::$app->request->csrfToken;
$csrfParam = Yii::$app->request->csrfParam;
$this->registerJs("
$(document).on('click', '.delete-category', function(e) {
    e.preventDefault();
    var id = $(this).data('id');

    if (!confirm('Are you sure you want to delete this category?')) {
        return;
    }

    var deleteUrl = '$baseDeleteUrl' + '?id=' + id;
    var data = { 
        '$csrfParam': '$csrfToken'
    };

    $.ajax({
        url: deleteUrl,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            alert('Failed to delete category. Please try again.');
        }
    });
});
");
?>
