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
    .tasks-table .table td {
        padding: 12px 8px;
        vertical-align: top;
    }
    .tasks-table .table td:nth-child(3) {
        word-wrap: break-word;
        white-space: normal;
        max-width: 350px;
    }
    .modal-url {
        color: #007bff;
        text-decoration: none;
        word-break: break-all;
        display: inline-block;
    }
    .modal-url:hover {
        color: #0056b3;
        text-decoration: underline;
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
        
        // Load task details via AJAX - fix the URL to use correct path
        $.get('" . Url::to(['/kanban/board/get-task-details']) . "', {id: taskId}, function(response) {
            if (response.success) {
                var task = response.task;
                var html = buildTaskDetailsHTML(task);
                $('#taskDetailsModal .modal-body').html(html);
                $('#taskDetailsModal .modal-title').text('Task: ' + task.title);
                $('#taskDetailsModal').modal('show');
            } else {
                alert('Error: ' + response.message);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            alert('Error loading task details: ' + error);
        });
    });
    
    function buildTaskDetailsHTML(task) {
        var statusClass = 'badge-secondary';
        switch(task.status) {
            case 'pending': statusClass = 'badge-warning'; break;
            case 'in_progress': statusClass = 'badge-info'; break;
            case 'completed': statusClass = 'badge-success'; break;
            case 'cancelled': statusClass = 'badge-danger'; break;
        }
        
        var priorityClass = 'badge-secondary';
        switch(task.priority) {
            case 'low': priorityClass = 'badge-light'; break;
            case 'medium': priorityClass = 'badge-warning'; break;
            case 'high': priorityClass = 'badge-danger'; break;
            case 'critical': priorityClass = 'badge-dark'; break;
        }
        
        var html = '<div class=\"task-details\">';
        html += '<div class=\"row\">';
        html += '<div class=\"col-md-6\">';
        html += '<h6><i class=\"fas fa-info-circle\"></i> Status</h6>';
        html += '<span class=\"badge ' + statusClass + ' mb-3\">' + (task.status_label || task.status || 'Unknown') + '</span>';
        html += '</div>';
        html += '<div class=\"col-md-6\">';
        html += '<h6><i class=\"fas fa-exclamation-triangle\"></i> Priority</h6>';
        html += '<span class=\"badge ' + priorityClass + ' mb-3\">' + (task.priority_label || task.priority || 'Normal') + '</span>';
        html += '</div>';
        html += '</div>';
        
        html += '<div class=\"mb-3\">';
        html += '<h6><i class=\"fas fa-align-left\"></i> Description</h6>';
        var description = task.description || 'No description provided';
        // Convert URLs to clickable links
        description = description.replace(/(https?:\/\/[^\s]+)/g, '<a href=\"$1\" target=\"_blank\" rel=\"noopener\" class=\"modal-url\">$1</a>');
        html += '<div class=\"border p-3 bg-light\" style=\"word-wrap: break-word; overflow-wrap: break-word;\">' + description + '</div>';
        html += '</div>';
        
        html += '<div class=\"row\">';
        html += '<div class=\"col-md-6\">';
        html += '<h6><i class=\"fas fa-calendar-plus\"></i> Created</h6>';
        html += '<p>' + (task.created_at || 'Unknown') + '</p>';
        html += '</div>';
        html += '<div class=\"col-md-6\">';
        html += '<h6><i class=\"fas fa-calendar-check\"></i> Last Updated</h6>';
        html += '<p>' + (task.updated_at || 'Unknown') + '</p>';
        html += '</div>';
        html += '</div>';
        
        if (task.deadline) {
            html += '<div class=\"mb-3\">';
            html += '<h6><i class=\"fas fa-clock\"></i> Deadline</h6>';
            html += '<p class=\"' + (task.isOverdue ? 'text-danger' : 'text-info') + '\">';
            html += task.deadline;
            if (task.isOverdue) {
                html += ' <span class=\"badge badge-danger\">OVERDUE</span>';
            }
            html += '</p>';
            html += '</div>';
        }
        
        if (task.completedAt) {
            html += '<div class=\"mb-3\">';
            html += '<h6><i class=\"fas fa-check-circle\"></i> Completed</h6>';
            html += '<p class=\"text-success\">' + task.completedAt + '</p>';
            html += '</div>';
        }
        
        if (task.images && task.images.length > 0) {
            html += '<div class=\"mb-3\">';
            html += '<h6><i class=\"fas fa-images\"></i> Attachments</h6>';
            html += '<div class=\"row\">';
            for (var i = 0; i < task.images.length; i++) {
                var img = task.images[i];
                html += '<div class=\"col-md-4 mb-2\">';
                html += '<div class=\"card\">';
                html += '<img src=\"' + img.url + '\" class=\"card-img-top\" style=\"height: 100px; object-fit: cover;\" alt=\"' + img.name + '\">';
                html += '<div class=\"card-body p-2\">';
                html += '<small class=\"text-muted\">' + img.name + ' (' + img.size + ')</small>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            }
            html += '</div>';
            html += '</div>';
        }
        
        html += '</div>';
        return html;
    }
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
                        'format' => 'text',
                        'headerOptions' => ['style' => 'width: 200px;'],
                    ],
                    [
                        'attribute' => 'description',
                        'label' => 'Description',
                        'format' => 'text',
                        'headerOptions' => ['style' => 'width: 350px;'],
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
                            // First try Task model status options
                            $statusOptions = Task::getStatusOptions();
                            $status = isset($statusOptions[$model->status]) ? $statusOptions[$model->status] : null;
                            
                            // If not found, check if it's a kanban column status_key
                            if (!$status) {
                                $kanbanColumnClass = '\common\modules\kanban\models\KanbanColumn';
                                if (class_exists($kanbanColumnClass)) {
                                    $column = $kanbanColumnClass::find()->where(['status_key' => $model->status])->one();
                                    if ($column) {
                                        $status = $column->name;
                                    }
                                }
                            }
                            
                            // Final fallback
                            if (!$status) {
                                $status = ucfirst(str_replace('_', ' ', $model->status));
                            }
                            
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