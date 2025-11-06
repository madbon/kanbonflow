<?php
use yii\helpers\Html;
use yii\helpers\Url;
use common\modules\taskmonitor\models\Task;

$this->title = 'Task Monitor';
?>

<div class="task-monitor-index">
    <div class="task-monitor-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <div class="header-actions">
            <?= Html::a('<i class="fa fa-cog"></i> Manage Categories', ['/taskmonitor/category/index'], ['class' => 'btn btn-secondary']) ?>
            <?= Html::a('<i class="fa fa-palette"></i> Color Settings', ['/taskmonitor/color-setting/index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="task-monitor-container">
        <!-- 1st Column: Main Categories -->
        <div class="column column-categories">
            <div class="column-header">
                <h3>Main Categories</h3>
                <div>
                    <?= Html::a('<i class="fa fa-plus"></i>', ['/taskmonitor/category/create'], ['class' => 'btn btn-sm btn-success', 'title' => 'New Category']) ?>
                </div>
            </div>
            <div class="categories-list" id="main-categories-list">
                <?php foreach ($rootCategories as $category): ?>
                    <?php
                    $activeTasksCount = $category->getTotalActiveTasksCount();
                    $categoryColor = $category->getCategoryColor();
                    $isActive = $selectedCategory && $selectedCategory->id == $category->id ? 'active' : '';
                    $hasChildren = $category->hasChildren();
                    ?>
                    <div class="category-item <?= $isActive ?>"
                         data-category-id="<?= $category->id ?>"
                         data-has-children="<?= $hasChildren ? '1' : '0' ?>"
                         style="border-left: 4px solid <?= Html::encode($categoryColor) ?>">
                        <div class="category-icon">
                            <i class="fa <?= Html::encode($category->icon ?: 'fa-folder') ?>"></i>
                        </div>
                        <div class="category-info">
                            <div class="category-name"><?= Html::encode($category->name) ?></div>
                            <div class="category-stats">
                                <span class="task-count"><?= $activeTasksCount ?> tasks</span>
                                <?php if ($hasChildren): ?>
                                    <span class="subcategory-count"><?= count($category->children) ?> sub</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($hasChildren): ?>
                            <div class="has-children-indicator">
                                <i class="fa fa-chevron-right"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 2nd Column: Subcategories -->
        <div class="column column-subcategories" id="subcategories-column" style="display: none;">
            <div class="column-header">
                <h3 id="subcategories-column-title">Subcategories</h3>
                <button class="btn btn-sm btn-primary" id="new-subcategory-btn" style="display: none;">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
            <div class="subcategories-list" id="subcategories-list">
                <div class="empty-state">
                    <i class="fa fa-folder-open"></i>
                    <p>Select a main category</p>
                </div>
            </div>
        </div>

        <!-- 3rd Column: Tasks List -->
        <div class="column column-tasks" id="tasks-column" style="display: none;">
            <div class="column-header">
                <h3 id="tasks-column-title">Tasks</h3>
                <button class="btn btn-sm btn-primary" id="new-task-btn" style="display: none;">
                    <i class="fa fa-plus"></i> New
                </button>
            </div>
            <div class="tasks-list" id="tasks-list">
                <div class="empty-state">
                    <i class="fa fa-tasks"></i>
                    <p>Select a subcategory</p>
                </div>
            </div>
        </div>

        <!-- 4th Column: Task Detail -->
        <div class="column column-detail" id="detail-column" style="display: none;">
            <div class="column-header">
                <h3 id="detail-column-title">Task Details</h3>
                <div class="detail-actions" id="detail-actions" style="display: none;">
                    <button class="btn btn-sm btn-success" id="save-task-btn" style="display:none;">
                        <i class="fa fa-save"></i>
                    </button>
                    <button class="btn btn-sm btn-primary" id="edit-task-btn" style="display:none;">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" id="delete-task-btn" style="display:none;">
                        <i class="fa fa-trash"></i>
                    </button>
                    <button class="btn btn-sm btn-secondary" id="close-detail-btn">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="task-detail-content" id="task-detail-content">
                <div class="empty-state">
                    <i class="fa fa-file-alt"></i>
                    <p>Select a task</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerCss("
/* Reset and Base Styles */
* { box-sizing: border-box; }

/* Main Container */
.task-monitor-index { 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background-color: #f5f7fa;
    min-height: 100vh;
    padding: 20px;
}

/* Header */
.task-monitor-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 30px;
    background: white;
    padding: 20px 30px;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.task-monitor-header h1 {
    color: #2c3e50;
    font-size: 28px;
    font-weight: 700;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.header-actions .btn {
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border: none;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

/* 4-Column Layout */
.task-monitor-container { 
    display: flex; 
    gap: 20px; 
    height: 75vh;
    min-height: 600px;
}

.column { 
    flex: 1; 
    display: flex; 
    flex-direction: column; 
    background: white;
    border-radius: 12px; 
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: box-shadow 0.2s;
}

.column:hover {
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.column-header { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center;
}

.column-header h3 { 
    margin: 0; 
    font-size: 18px; 
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.column-header .btn {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.column-header .btn:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.05);
}

/* Lists */
.categories-list, .subcategories-list, .tasks-list, .task-detail-content { 
    flex: 1; 
    overflow-y: auto;
    padding: 0;
}

/* List Items */
.category-item, .subcategory-item, .task-item { 
    padding: 16px 20px; 
    border-bottom: 1px solid #f0f2f5; 
    cursor: pointer; 
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 12px;
}

.category-item:hover, .subcategory-item:hover, .task-item:hover { 
    background: #f8f9fa;
    transform: translateX(2px);
}

.category-item.active, .subcategory-item.active, .task-item.active { 
    background: linear-gradient(90deg, #e3f2fd 0%, #bbdefb 100%);
    border-left: 4px solid #2196f3;
    font-weight: 600;
}

.category-icon, .subcategory-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #f8f9fa;
    color: #666;
    font-size: 18px;
}

.category-info, .subcategory-info {
    flex: 1;
}

.category-name, .subcategory-name {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 4px;
}

.category-stats, .subcategory-stats {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: #666;
}

.task-count, .subcategory-count, .urgent-count {
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.urgent-count {
    background: #ffebee;
    color: #c62828;
}

.has-children-indicator {
    color: #bbb;
    font-size: 14px;
}

/* Task Items */
.task-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 6px;
}

.task-meta {
    display: flex;
    gap: 10px;
    font-size: 12px;
}

.task-status {
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 500;
    text-transform: capitalize;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-in_progress { background: #cce5ff; color: #0056b3; }
.status-completed { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.task-deadline {
    color: #666;
}

.task-deadline.overdue {
    color: #dc3545;
    font-weight: 600;
}

/* Empty States */
.empty-state { 
    padding: 60px 20px; 
    text-align: center; 
    color: #95a5a6;
}

.empty-state i { 
    font-size: 64px; 
    margin-bottom: 20px; 
    opacity: 0.3;
    display: block;
}

.empty-state p {
    font-size: 16px;
    margin-bottom: 8px;
}

.empty-state small {
    font-size: 14px;
    opacity: 0.7;
}

/* Task Detail Panel */
.task-detail-content {
    padding: 20px;
}

.detail-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.detail-actions .btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-success { background: #28a745; color: white; }
.btn-success:hover { background: #218838; transform: translateY(-1px); }

.btn-primary { background: #007bff; color: white; }
.btn-primary:hover { background: #0056b3; transform: translateY(-1px); }

.btn-danger { background: #dc3545; color: white; }
.btn-danger:hover { background: #c82333; transform: translateY(-1px); }

.btn-secondary { background: #6c757d; color: white; }
.btn-secondary:hover { background: #545b62; transform: translateY(-1px); }

/* Task Detail Form */
.task-detail-form .form-group { 
    margin-bottom: 20px; 
}

.task-detail-form label { 
    display: block; 
    margin-bottom: 8px; 
    font-weight: 600;
    color: #2c3e50;
}

.task-detail-form .form-control { 
    width: 100%; 
    padding: 12px 16px; 
    border: 2px solid #e9ecef; 
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.task-detail-form .form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

/* Task Detail View */
.task-detail-view h4 { 
    margin-bottom: 20px; 
    color: #2c3e50;
    font-size: 24px;
    font-weight: 700;
}

.task-detail-view p { 
    margin-bottom: 15px;
    line-height: 1.6;
}

.task-detail-view strong {
    color: #495057;
    font-weight: 600;
}

/* Scrollbar Styling */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .task-monitor-container {
        gap: 15px;
    }
    
    .column-header {
        padding: 15px;
    }
    
    .column-header h3 {
        font-size: 16px;
    }
}

@media (max-width: 768px) {
    .task-monitor-container {
        flex-direction: column;
        height: auto;
        gap: 20px;
    }
    
    .column {
        min-height: 300px;
    }
    
    .task-monitor-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}
");

$getSubcategoriesUrl = Url::to(['/taskmonitor/default/get-subcategories']);
$getTasksUrl = Url::to(['/taskmonitor/default/get-tasks']);
$getTaskUrl = Url::to(['/taskmonitor/default/get-task']);
$saveTaskUrl = Url::to(['/taskmonitor/default/save-task']);
$deleteTaskUrl = Url::to(['/taskmonitor/default/delete-task']);
$createCategoryUrl = Url::to(['/taskmonitor/category/create']);

$this->registerJs(<<<JS
var currentMainCategoryId = null;
var currentSubcategoryId = null;
var currentTaskId = null;
var editor = null;
var lastLoadedTask = null;

function initEditor() {
    if (typeof CKEDITOR !== 'undefined') {
        if (editor) {
            try { editor.destroy(true); } catch(e) {}
        }
        editor = CKEDITOR.replace('task-description', { height: 250, removeButtons: 'Source' });
    }
}

function destroyEditor() {
    if (editor) {
        try { editor.destroy(true); } catch(e) {}
        editor = null;
    }
}

function loadSubcategories(categoryId) {
    $.ajax({ url: '{$getSubcategoriesUrl}', data: { category_id: categoryId }, success: function(subcategories) {
        var html = '';
        if (!subcategories || subcategories.length === 0) {
            html = '<div class="empty-state"><i class="fa fa-inbox"></i><p>No subcategories</p><p><small>You can add tasks directly to this category</small></p></div>';
            $('#tasks-column').show(); // Show tasks column if no subcategories
            loadTasks(categoryId);
        } else {
            subcategories.forEach(function(sub) {
                html += '<div class="subcategory-item" data-subcategory-id="' + sub.id + '" style="border-left-color: ' + sub.color + '">';
                html += '  <div class="category-icon"><i class="fa ' + (sub.icon||'fa-folder') + '"></i></div>';
                html += '  <div class="category-info">';
                html += '    <div class="category-name">' + sub.name + '</div>';
                html += '    <div class="category-stats">';
                html += '      <span class="task-count">' + (sub.activeTasksCount||0) + ' tasks</span>';
                html += '    </div>';
                html += '  </div>';
                html += '</div>';
            });
            $('#new-subcategory-btn').show();
        }
        $('#subcategories-list').html(html);
    }});
}

function loadTasks(categoryId) {
    $.ajax({ url: '{$getTasksUrl}', data: { category_id: categoryId }, success: function(tasks) {
        var html = '';
        if (!tasks || tasks.length === 0) {
            html = '<div class="empty-state"><i class="fa fa-inbox"></i><p>No tasks</p></div>';
        } else {
            tasks.forEach(function(task) {
                var statusClass = 'status-' + (task.status||'');
                var deadlineClass = task.isOverdue ? 'overdue' : '';
                var daysText = typeof task.daysUntil !== 'undefined' ? (task.daysUntil >= 0 ? task.daysUntil + ' days' : 'Overdue') : '';
                html += '<div class="task-item" data-task-id="' + task.id + '" style="border-left-color: ' + (task.color||'#ddd') + '">';
                html += '  <div class="task-title">' + (task.title||'') + '</div>';
                html += '  <div class="task-meta">';
                html += '    <span class="task-status ' + statusClass + '">' + (task.status||'') + '</span>';
                html += '    <span class="task-deadline ' + deadlineClass + '">' + daysText + '</span>';
                html += '  </div>';
                html += '</div>';
            });
        }
        $('#tasks-list').html(html);
    }});
}

function fetchTask(taskId, callback) {
    $.ajax({ url: '{$getTaskUrl}', data: { id: taskId }, success: function(task) {
        lastLoadedTask = task || null;
        if (typeof callback === 'function') callback(task);
    }});
}

function showTaskView(task) {
    task = task || lastLoadedTask || {};
    var html = '<div class="task-detail-view">';
    html += '<h4>' + (task.title || 'No Title') + '</h4>';
    html += '<p><strong>Status:</strong> ' + (task.status || 'N/A') + '</p>';
    html += '<p><strong>Priority:</strong> ' + (task.priority || 'N/A') + '</p>';
    html += '<p><strong>Deadline:</strong> ' + (task.deadline || 'N/A') + '</p>';
    html += '<div><strong>Description:</strong><div>' + (task.description || '') + '</div></div>';
    html += '</div>';
    destroyEditor();
    $('#task-detail-content').html(html);
    $('#detail-actions').show();
    $('#edit-task-btn').show();
    $('#save-task-btn').hide();
    $('#delete-task-btn').toggle(!!task.id);
}

function showTaskForm(task) {
    task = task || lastLoadedTask || {};
    var html = '<div class="task-detail-form">';
    html += '<input type="hidden" id="task-id" value="' + (task.id || '') + '">';
    html += '<div class="form-group"><label>Title</label><input type="text" class="form-control" id="task-title" value="' + (task.title || '') + '"></div>';
    html += '<div class="form-group"><label>Status</label><select class="form-control" id="task-status">';
    html += '<option value="pending"' + (task.status === 'pending' ? ' selected' : '') + '>Pending</option>';
    html += '<option value="in_progress"' + (task.status === 'in_progress' ? ' selected' : '') + '>In Progress</option>';
    html += '<option value="completed"' + (task.status === 'completed' ? ' selected' : '') + '>Completed</option>';
    html += '<option value="cancelled"' + (task.status === 'cancelled' ? ' selected' : '') + '>Cancelled</option>';
    html += '</select></div>';
    html += '<div class="form-group"><label>Priority</label><select class="form-control" id="task-priority">';
    html += '<option value="low"' + (task.priority === 'low' ? ' selected' : '') + '>Low</option>';
    html += '<option value="medium"' + (task.priority === 'medium' ? ' selected' : '') + '>Medium</option>';
    html += '<option value="high"' + (task.priority === 'high' ? ' selected' : '') + '>High</option>';
    html += '<option value="critical"' + (task.priority === 'critical' ? ' selected' : '') + '>Critical</option>';
    html += '</select></div>';
    html += '<div class="form-group"><label>Deadline</label><input type="datetime-local" class="form-control" id="task-deadline" value="' + (task.deadline || '') + '"></div>';
    html += '<div class="form-group"><label>Description</label><textarea class="form-control" id="task-description" rows="10">' + (task.description || '') + '</textarea></div>';
    html += '</div>';
    $('#task-detail-content').html(html);
    $('#detail-actions').show();
    $('#edit-task-btn').hide();
    $('#save-task-btn').show();
    $('#delete-task-btn').toggle(!!task.id);
    setTimeout(initEditor, 100);
}

// Handlers
$(document).on('click', '.category-item', function() {
    var categoryId = $(this).data('category-id');
    var hasChildren = $(this).data('has-children') == '1';
    $('.category-item').removeClass('active'); $(this).addClass('active');
    currentMainCategoryId = categoryId; currentSubcategoryId = null; currentTaskId = null;
    
    // Show subcategories column when a category is selected
    $('#subcategories-column').show();
    
    if (hasChildren) {
        loadSubcategories(categoryId);
        $('#tasks-column').hide(); // Hide tasks until subcategory is selected
        $('#detail-column').hide(); // Hide details until task is selected
        $('#tasks-list').html('<div class="empty-state"><i class="fa fa-tasks"></i><p>Select a subcategory</p></div>');
    } else {
        // No subcategories - show tasks column and load tasks directly
        $('#subcategories-list').html('<div class="empty-state"><i class="fa fa-info-circle"></i><p>No subcategories</p></div>');
        $('#tasks-column').show();
        $('#detail-column').hide(); // Hide details until task is selected
        loadTasks(categoryId);
    }
    
    $('#task-detail-content').html('<div class="empty-state"><i class="fa fa-file-alt"></i><p>Select a task</p></div>');
    $('#detail-actions').hide();
});

$(document).on('click', '.subcategory-item', function() {
    $('.subcategory-item').removeClass('active'); $(this).addClass('active');
    currentSubcategoryId = $(this).data('subcategory-id'); currentTaskId = null; 
    
    // Show tasks column when subcategory is selected
    $('#tasks-column').show();
    $('#detail-column').hide(); // Hide details until task is selected
    
    loadTasks(currentSubcategoryId);
    $('#task-detail-content').html('<div class="empty-state"><i class="fa fa-file-alt"></i><p>Select a task</p></div>');
    $('#detail-actions').hide(); $('#new-task-btn').show();
});

$(document).on('click', '.task-item', function() {
    $('.task-item').removeClass('active'); $(this).addClass('active');
    currentTaskId = $(this).data('task-id'); 
    
    // Show detail column when task is selected
    $('#detail-column').show();
    
    fetchTask(currentTaskId, function(task){ showTaskView(task); });
});

$('#new-task-btn').click(function(){ if (!currentSubcategoryId && !currentMainCategoryId) { alert('Please select a category first'); return; } currentTaskId = null; lastLoadedTask = null; $('#detail-column').show(); showTaskForm(); });

$('#save-task-btn').click(function(){ if (editor) { $('#task-description').val(editor.getData()); }
    var categoryId = currentSubcategoryId || currentMainCategoryId;
    var data = { id: $('#task-id').val(), category_id: categoryId, title: $('#task-title').val(), description: $('#task-description').val(), status: $('#task-status').val(), priority: $('#task-priority').val(), deadline: $('#task-deadline').val() };
    $.post('{$saveTaskUrl}', data, function(response){ if (response.success) { currentTaskId = response.task_id; loadTasks(categoryId); fetchTask(currentTaskId, function(task){ showTaskView(task); }); } else { alert('Error saving task: ' + JSON.stringify(response.errors)); } }, 'json');
});

$('#delete-task-btn').click(function(){ if (!confirm('Are you sure you want to delete this task?')) return; $.post('{$deleteTaskUrl}', { id: currentTaskId }, function(resp){ if (resp.success) { var categoryId = currentSubcategoryId || currentMainCategoryId; loadTasks(categoryId); $('#task-detail-content').html('<div class="empty-state"><i class="fa fa-file-alt"></i><p>Select a task</p></div>'); $('#detail-actions').hide(); } else { alert('Error deleting task: ' + resp.message); } }, 'json'); });

$('#edit-task-btn').click(function(){ if (lastLoadedTask) showTaskForm(lastLoadedTask); else if (currentTaskId) fetchTask(currentTaskId, showTaskForm); else showTaskForm(); });

$('#close-detail-btn').click(function(){ $('#task-detail-content').html('<div class="empty-state"><i class="fa fa-file-alt"></i><p>Select a task</p></div>'); $('#detail-actions').hide(); $('.task-item').removeClass('active'); destroyEditor(); });

$('#new-subcategory-btn').click(function(){ if (currentMainCategoryId) { window.location.href = '{$createCategoryUrl}?parent_id=' + currentMainCategoryId; } });
JS
);
?>
?>
