<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\Url;
use common\modules\taskmonitor\models\TaskCategory;
use common\modules\taskmonitor\models\Task;

/* @var $this yii\web\View */
/* @var $searchModel common\modules\taskmonitor\models\TaskSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $categories array */
/* @var $statuses array */
/* @var $totalTasks int */
/* @var $tasksByStatus array */
/* @var $filters array */

$this->title = 'All Tasks';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss("
    .task-filters {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #dee2e6;
    }
    .filter-row {
        margin-bottom: 15px;
    }
    .filter-row:last-child {
        margin-bottom: 0;
    }
    .tasks-table .table th {
        background-color: #343a40;
        color: white;
        border-color: #454d55;
        font-weight: 600;
    }
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
    }
    .status-pending { background-color: #ffeaa7; color: #2d3436; }
    .status-in-progress { background-color: #74b9ff; color: white; }
    .status-completed { background-color: #00b894; color: white; }
    .status-cancelled { background-color: #fd79a8; color: white; }
    .task-description {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
");

$this->registerJs("
    // Auto-submit form when filters change
    let submitTimeout;
    function autoSubmitForm() {
        clearTimeout(submitTimeout);
        submitTimeout = setTimeout(function() {
            $('#tasks-filter-form').submit();
        }, 500);
    }
    
    // Bind to filter inputs
    $('#tasks-filter-form input, #tasks-filter-form select').on('change keyup', autoSubmitForm);
    
    // Clear filters
    $('#clear-filters').on('click', function(e) {
        e.preventDefault();
        $('#tasks-filter-form')[0].reset();
        $('#tasks-filter-form').submit();
    });
    
    // Handle view task button
    $(document).on('click', '.view-task-btn', function(e) {
        e.preventDefault();
        var taskId = $(this).data('task-id');
        
        // Load task details via AJAX
        $.get('/kanban/board/get-task-details', {id: taskId}, function(data) {
            $('#taskDetailsModal .modal-body').html(data);
            $('#taskDetailsModal').modal('show');
        }).fail(function() {
            alert('Error loading task details');
        });
    });
");
?>

<div class="tasks-index">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('Activity Log', ['activity-log/index'], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('Kanban Board', ['site/index'], ['class' => 'btn btn-primary']) ?>
        </div>
    </div>

    <!-- Task Statistics -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h4 class="card-title"><?= $totalTasks ?></h4>
                    <p class="card-text">Total Tasks</p>
                </div>
            </div>
        </div>
        <?php foreach ($tasksByStatus as $statusKey => $count): ?>
            <?php if ($count > 0): ?>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h4 class="card-title"><?= $count ?></h4>
                            <p class="card-text"><?= Html::encode(isset($statuses[$statusKey]) ? $statuses[$statusKey] : $statusKey) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="task-filters">
        <form id="tasks-filter-form" method="get" action="<?= Url::to(['tasks']) ?>">
            <div class="row filter-row">
                <div class="col-md-3">
                    <label for="search_title" class="form-label"><strong>Task Title</strong></label>
                    <?= Html::textInput('search_title', $filters['search_title'], [
                        'class' => 'form-control',
                        'placeholder' => 'Search by title...',
                        'id' => 'search_title'
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <label for="category_id" class="form-label"><strong>Category</strong></label>
                    <?= Html::dropDownList('category_id', $filters['category_id'], 
                        ['' => 'All Categories'] + $categories, [
                        'class' => 'form-control',
                        'id' => 'category_id'
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <label for="search_description" class="form-label"><strong>Description</strong></label>
                    <?= Html::textInput('search_description', $filters['search_description'], [
                        'class' => 'form-control',
                        'placeholder' => 'Search in description...',
                        'id' => 'search_description'
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label"><strong>Status</strong></label>
                    <?= Html::dropDownList('status', $filters['status'], 
                        ['' => 'All Statuses'] + Task::getStatusOptions(), [
                        'class' => 'form-control',
                        'id' => 'status'
                    ]) ?>
                </div>
            </div>
            
            <div class="row filter-row">
                <div class="col-md-3">
                    <label for="date_from" class="form-label"><strong>Created From</strong></label>
                    <?= Html::input('date', 'date_from', $filters['date_from'], [
                        'class' => 'form-control',
                        'id' => 'date_from'
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label"><strong>Created To</strong></label>
                    <?= Html::input('date', 'date_to', $filters['date_to'], [
                        'class' => 'form-control',
                        'id' => 'date_to'
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <label for="last_update_from" class="form-label"><strong>Updated From</strong></label>
                    <?= Html::input('date', 'last_update_from', $filters['last_update_from'], [
                        'class' => 'form-control',
                        'id' => 'last_update_from'
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <label for="last_update_to" class="form-label"><strong>Updated To</strong></label>
                    <?= Html::input('date', 'last_update_to', $filters['last_update_to'], [
                        'class' => 'form-control',
                        'id' => 'last_update_to'
                    ]) ?>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <?= Html::submitButton('Apply Filters', ['class' => 'btn btn-primary']) ?>
                <?= Html::button('Clear Filters', ['class' => 'btn btn-outline-secondary', 'id' => 'clear-filters']) ?>
            </div>
        </form>
    </div>

    <?php Pjax::begin(['id' => 'tasks-pjax']); ?>
        <div class="tasks-table">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-striped table-bordered'],
                'columns' => [
                    [
                        'attribute' => 'category.name',
                        'label' => 'Category',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return $model->category ? 
                                '<span class="badge badge-secondary">' . Html::encode($model->category->name) . '</span>' : 
                                '<span class="text-muted">No Category</span>';
                        },
                        'headerOptions' => ['style' => 'width: 120px;'],
                    ],
                    [
                        'attribute' => 'title',
                        'label' => 'Task Title',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return '<strong>' . Html::encode($model->title) . '</strong>';
                        },
                        'headerOptions' => ['style' => 'width: 200px;'],
                    ],
                    [
                        'attribute' => 'description',
                        'label' => 'Description',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $description = Html::encode($model->description);
                            return '<div class="task-description" title="' . $description . '">' . $description . '</div>';
                        },
                        'headerOptions' => ['style' => 'width: 300px;'],
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Created',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return '<small>' . date('Y-m-d H:i', $model->created_at) . '</small>';
                        },
                        'headerOptions' => ['style' => 'width: 120px;'],
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Status',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $statusOptions = Task::getStatusOptions();
                            $status = isset($statusOptions[$model->status]) ? $statusOptions[$model->status] : 'Unknown';
                            $statusClass = 'status-' . strtolower(str_replace(' ', '-', $status));
                            return '<span class="status-badge ' . $statusClass . '">' . $status . '</span>';
                        },
                        'headerOptions' => ['style' => 'width: 100px;'],
                    ],
                    [
                        'attribute' => 'updated_at',
                        'label' => 'Last Update',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return '<small>' . date('Y-m-d H:i', $model->updated_at) . '</small>';
                        },
                        'headerOptions' => ['style' => 'width: 120px;'],
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Actions',
                        'template' => '{view}',
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                return Html::a('<i class="fas fa-eye"></i>', '#', [
                                    'class' => 'btn btn-sm btn-outline-primary view-task-btn',
                                    'title' => 'View Task',
                                    'data-task-id' => $model->id,
                                    'data-toggle' => 'modal',
                                    'data-target' => '#taskDetailsModal'
                                ]);
                            },
                        ],
                        'headerOptions' => ['style' => 'width: 80px; text-align: center;'],
                        'contentOptions' => ['style' => 'text-align: center;'],
                    ],
                ],
                'summary' => '<div class="summary"><strong>Showing {begin}-{count} of {totalCount} tasks</strong></div>',
                'emptyText' => '<div class="alert alert-info">No tasks found matching your criteria.</div>',
                'pager' => [
                    'class' => 'yii\bootstrap4\LinkPager',
                ],
            ]); ?>
        </div>
    <?php Pjax::end(); ?>
</div>

<!-- Task Details Modal -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" role="dialog" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taskDetailsModalLabel">Task Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Loading task details...</p>
                </div>
            </div>
        </div>
    </div>
</div>