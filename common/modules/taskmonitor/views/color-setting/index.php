<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Color Settings';
$this->params['breadcrumbs'][] = ['label' => 'Task Monitor', 'url' => ['/taskmonitor/default/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="task-color-setting-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <div class="header-actions">
            <?= Html::a('<i class="fa fa-arrow-left"></i> Back to Tasks', ['/taskmonitor/default/index'], ['class' => 'btn btn-secondary']) ?>
            <?= Html::a('<i class="fa fa-plus"></i> Create Setting', ['create'], ['class' => 'btn btn-success']) ?>
        </div>
    </div>

    <div class="color-settings-info">
        <div class="info-card">
            <i class="fa fa-info-circle"></i>
            <div>
                <h4>How Color Settings Work</h4>
                <p>Color settings determine the visual urgency indicators for tasks and categories based on how close they are to their deadlines.
                The system automatically applies colors based on the number of days remaining until the deadline.</p>
            </div>
        </div>
    </div>

    <div class="color-settings-table">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Days Before Deadline</th>
                    <th>Color</th>
                    <th>Preview</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($settings as $setting): ?>
                    <tr>
                        <td><strong><?= Html::encode($setting->name) ?></strong></td>
                        <td>
                            <?php if ($setting->days_before_deadline == 0): ?>
                                Overdue
                            <?php else: ?>
                                ≤ <?= $setting->days_before_deadline ?> days
                            <?php endif; ?>
                        </td>
                        <td><code><?= Html::encode($setting->color) ?></code></td>
                        <td>
                            <div class="color-preview" style="background-color: <?= Html::encode($setting->color) ?>"></div>
                        </td>
                        <td>
                            <span class="badge <?= $setting->is_active ? 'badge-success' : 'badge-danger' ?>">
                                <?= $setting->is_active ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <?= Html::a('<i class="fa fa-edit"></i>', ['update', 'id' => $setting->id], [
                                'class' => 'btn btn-sm btn-primary',
                                'title' => 'Edit',
                            ]) ?>
                            <?= Html::a('<i class="fa fa-trash"></i>', ['delete', 'id' => $setting->id], [
                                'class' => 'btn btn-sm btn-danger delete-setting',
                                'data-id' => $setting->id,
                                'title' => 'Delete',
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="color-example">
        <h3>Example: Task Timeline</h3>
        <div class="timeline-demo">
            <?php foreach ($settings as $setting): ?>
                <div class="timeline-item" style="border-left-color: <?= Html::encode($setting->color) ?>">
                    <div class="timeline-label" style="background-color: <?= Html::encode($setting->color) ?>">
                        <?= Html::encode($setting->name) ?>
                    </div>
                    <div class="timeline-content">
                        <?php if ($setting->days_before_deadline == 0): ?>
                            Past deadline
                        <?php else: ?>
                            <?= $setting->days_before_deadline ?> days or less until deadline
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
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

.color-settings-info {
    margin-bottom: 30px;
}

.info-card {
    display: flex;
    gap: 15px;
    padding: 20px;
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
    border-radius: 4px;
}

.info-card i {
    font-size: 24px;
    color: #2196f3;
}

.info-card h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1976d2;
}

.info-card p {
    margin: 0;
    color: #555;
    line-height: 1.6;
}

.color-settings-table {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.color-preview {
    width: 60px;
    height: 30px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
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

.color-example {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.color-example h3 {
    margin: 0 0 20px 0;
    font-size: 18px;
    font-weight: 600;
}

.timeline-demo {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.timeline-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 4px solid;
}

.timeline-label {
    padding: 6px 12px;
    border-radius: 4px;
    color: white;
    font-weight: 600;
    font-size: 13px;
    min-width: 100px;
    text-align: center;
}

.timeline-content {
    flex: 1;
    font-size: 14px;
    color: #555;
}
");

$deleteUrl = Url::to(['/taskmonitor/color-setting/delete']);
$this->registerJs("
$(document).on('click', '.delete-setting', function(e) {
    e.preventDefault();
    var id = $(this).data('id');

    if (!confirm('Are you sure you want to delete this color setting?')) {
        return;
    }

    $.post('$deleteUrl', { id: id }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Error: ' + response.message);
        }
    });
});
");
?>
