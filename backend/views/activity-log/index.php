<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\helpers\Url;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $actionTypes array */
/* @var $tasks array */
/* @var $statistics array */
/* @var $filters array */

$this->title = 'Activity Log & Transactions';
$this->params['breadcrumbs'][] = $this->title;

// Register CSS for activity log
$this->registerCss('
.activity-log-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid #007bff;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #007bff;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.filter-panel {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.activity-timeline {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.activity-item {
    display: flex;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #eee;
    align-items: flex-start;
    transition: background-color 0.2s;
}

.activity-item:hover {
    background-color: #f8f9fa;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.activity-icon.created { background: #28a745; color: white; }
.activity-icon.updated { background: #17a2b8; color: white; }
.activity-icon.status_changed { background: #ffc107; color: #212529; }
.activity-icon.position_changed { background: #6f42c1; color: white; }
.activity-icon.deleted { background: #dc3545; color: white; }
.activity-icon.completed { background: #20c997; color: white; }
.activity-icon.assigned { background: #fd7e14; color: white; }

.activity-content {
    flex: 1;
}

.activity-header {
    display: flex;
    justify-content: between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.activity-title {
    font-weight: 600;
    color: #495057;
    margin: 0;
}

.activity-time {
    font-size: 0.85rem;
    color: #6c757d;
    white-space: nowrap;
}

.activity-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.activity-task {
    display: inline-block;
    background: #e9ecef;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    color: #495057;
    text-decoration: none;
}

.activity-task:hover {
    background: #dee2e6;
    text-decoration: none;
    color: #495057;
}

.filter-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.daily-chart {
    height: 200px;
    background: #f8f9fa;
    border-radius: 4px;
    padding: 1rem;
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 4px;
}

.chart-bar {
    background: #007bff;
    min-height: 2px;
    border-radius: 2px 2px 0 0;
    flex: 1;
    display: flex;
    align-items: end;
    justify-content: center;
    color: white;
    font-size: 0.7rem;
    padding: 2px;
    position: relative;
}

.chart-bar::after {
    content: attr(data-date);
    position: absolute;
    bottom: -20px;
    font-size: 0.6rem;
    color: #6c757d;
    transform: rotate(-45deg);
    transform-origin: center;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .activity-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .filter-actions {
        flex-direction: column;
        align-items: stretch;
    }
}
');
?>

<div class="activity-log-index">
    <!-- Header -->
    <div class="activity-log-header">
        <h1 class="mb-3"><?= Html::encode($this->title) ?></h1>
        <p class="mb-0">Track all activities and changes made to your tasks. Filter by date range, action type, or specific tasks to find exactly what you\'re looking for.</p>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $statistics['total_activities'] ?></div>
            <div class="stat-label">Total Activities</div>
        </div>
        
        <?php foreach ($statistics['activities_by_type'] as $type => $data): ?>
            <?php if ($data['count'] > 0): ?>
                <div class="stat-card">
                    <div class="stat-number"><?= $data['count'] ?></div>
                    <div class="stat-label"><?= $data['label'] ?> (<?= $data['percentage'] ?>%)</div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Filters -->
    <div class="filter-panel">
        <h5 class="mb-3">Filter Activities</h5>
        
        <?php $form = ActiveForm::begin([
            'method' => 'get',
            'options' => ['class' => 'filter-form'],
        ]); ?>
        
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <?= Html::label('From Date', 'date_from', ['class' => 'form-label']) ?>
                    <?= Html::input('date', 'date_from', $filters['date_from'], [
                        'class' => 'form-control',
                        'id' => 'date_from'
                    ]) ?>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    <?= Html::label('To Date', 'date_to', ['class' => 'form-label']) ?>
                    <?= Html::input('date', 'date_to', $filters['date_to'], [
                        'class' => 'form-control',
                        'id' => 'date_to'
                    ]) ?>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    <?= Html::label('Action Type', 'action_type', ['class' => 'form-label']) ?>
                    <?= Html::dropDownList('action_type', $filters['action_type'], 
                        array_merge(['' => 'All Types'], $actionTypes), [
                        'class' => 'form-control',
                        'id' => 'action_type'
                    ]) ?>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    <?= Html::label('Task', 'task_id', ['class' => 'form-label']) ?>
                    <?= Html::dropDownList('task_id', $filters['task_id'], 
                        array_merge(['' => 'All Tasks'], $tasks), [
                        'class' => 'form-control',
                        'id' => 'task_id'
                    ]) ?>
                </div>
            </div>
        </div>
        
        <div class="filter-actions">
            <?= Html::submitButton('Filter', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Clear Filters', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('Export CSV', ['export'] + $filters, ['class' => 'btn btn-success']) ?>
            <?= Html::a('Generate Demo Data', ['demo'], ['class' => 'btn btn-warning']) ?>
        </div>
        
        <?php ActiveForm::end(); ?>
    </div>

    <!-- Daily Activity Chart -->
    <?php if (!empty($statistics['daily_activity'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Daily Activity Overview</h5>
            </div>
            <div class="card-body">
                <div class="daily-chart">
                    <?php 
                    $maxCount = max(array_column($statistics['daily_activity'], 'count')); 
                    $maxCount = $maxCount > 0 ? $maxCount : 1;
                    ?>
                    <?php foreach ($statistics['daily_activity'] as $day): ?>
                        <div class="chart-bar" 
                             style="height: <?= $day['count'] > 0 ? ($day['count'] / $maxCount * 100) : 2 ?>%"
                             data-date="<?= $day['date_formatted'] ?>"
                             title="<?= $day['date'] ?>: <?= $day['count'] ?> activities">
                            <?= $day['count'] > 0 ? $day['count'] : '' ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Activity Timeline -->
    <div class="activity-timeline">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Activity Timeline</h5>
            <small class="text-muted">
                Showing <?= $dataProvider->getCount() ?> of <?= $dataProvider->getTotalCount() ?> activities
            </small>
        </div>
        
        <div class="activity-list">
            <?php if ($dataProvider->getCount() > 0): ?>
                <?php foreach ($dataProvider->getModels() as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?= $activity->action_type ?>">
                            <?php
                            $icons = [
                                'created' => 'fa fa-plus',
                                'updated' => 'fa fa-edit',
                                'status_changed' => 'fa fa-exchange',
                                'position_changed' => 'fa fa-arrows',
                                'priority_changed' => 'fa fa-flag',
                                'category_changed' => 'fa fa-folder',
                                'deadline_changed' => 'fa fa-calendar',
                                'deleted' => 'fa fa-trash',
                                'completed' => 'fa fa-check',
                                'assigned' => 'fa fa-user',
                                'unassigned' => 'fa fa-user-times',
                                'restored' => 'fa fa-undo',
                            ];
                            $icon = isset($icons[$activity->action_type]) ? $icons[$activity->action_type] : 'fa fa-info';
                            ?>
                            <i class="<?= $icon ?>"></i>
                        </div>
                        
                        <div class="activity-content">
                            <div class="activity-header">
                                <h6 class="activity-title"><?= $activity->getActionTypeLabel() ?></h6>
                                <small class="activity-time">
                                    <?= Yii::$app->formatter->asRelativeTime($activity->created_at) ?>
                                    <br>
                                    <span class="text-muted"><?= Yii::$app->formatter->asDatetime($activity->created_at) ?></span>
                                </small>
                            </div>
                            
                            <div class="activity-description">
                                <?= Html::encode($activity->description) ?>
                            </div>
                            
                            <?php if ($activity->task): ?>
                                <a href="<?= Url::to(['/kanban/board/index']) ?>" class="activity-task">
                                    <i class="fa fa-tasks"></i> <?= Html::encode($activity->task->title) ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($activity->user): ?>
                                <small class="text-muted d-block mt-1">
                                    <i class="fa fa-user"></i> <?= Html::encode($activity->user->username) ?>
                                </small>
                            <?php endif; ?>
                            
                            <?php if ($activity->old_values): ?>
                                <details class="mt-2">
                                    <summary class="text-info" style="cursor: pointer;">
                                        <small>View Changes</small>
                                    </summary>
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            <?php 
                                            $changes = json_decode($activity->old_values, true);
                                            if ($changes && is_array($changes)):
                                                foreach ($changes as $field => $oldValue):
                                                    echo "<strong>" . ucfirst(str_replace('_', ' ', $field)) . ":</strong> ";
                                                    echo Html::encode($oldValue) . " → ";
                                                    echo Html::encode($activity->new_value) . "<br>";
                                                endforeach;
                                            endif;
                                            ?>
                                        </small>
                                    </div>
                                </details>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fa fa-history fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No activities found</h5>
                    <p class="text-muted">Try adjusting your filter criteria to see more results.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($dataProvider->getTotalCount() > $dataProvider->getCount()): ?>
            <div class="card-footer">
                <?= LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'pagination justify-content-center mb-0'],
                    'linkOptions' => ['class' => 'page-link'],
                    'pageCssClass' => 'page-item',
                    'prevPageCssClass' => 'page-item',
                    'nextPageCssClass' => 'page-item',
                    'firstPageCssClass' => 'page-item',
                    'lastPageCssClass' => 'page-item',
                    'disabledPageCssClass' => 'page-item disabled',
                    'activePageCssClass' => 'page-item active',
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Add some JavaScript for better UX
$this->registerJs('
    // Auto-submit form when dates change
    $("#date_from, #date_to").on("change", function() {
        if ($("#date_from").val() && $("#date_to").val()) {
            $(".filter-form").submit();
        }
    });
    
    // Quick date range buttons
    $(".filter-panel").prepend(`
        <div class="mb-3">
            <small class="text-muted">Quick ranges:</small>
            <div class="btn-group btn-group-sm ms-2" role="group">
                <button type="button" class="btn btn-outline-secondary quick-range" data-days="1">Today</button>
                <button type="button" class="btn btn-outline-secondary quick-range" data-days="7">Last 7 days</button>
                <button type="button" class="btn btn-outline-secondary quick-range" data-days="30">Last 30 days</button>
                <button type="button" class="btn btn-outline-secondary quick-range" data-days="90">Last 90 days</button>
            </div>
        </div>
    `);
    
    // Handle quick range buttons
    $(".quick-range").on("click", function() {
        var days = $(this).data("days");
        var today = new Date();
        var fromDate = new Date();
        
        if (days === 1) {
            fromDate = today;
        } else {
            fromDate.setDate(today.getDate() - days);
        }
        
        $("#date_from").val(fromDate.toISOString().split("T")[0]);
        $("#date_to").val(today.toISOString().split("T")[0]);
        
        $(".filter-form").submit();
    });
');
?>