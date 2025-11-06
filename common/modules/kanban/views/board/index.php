<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\modules\kanban\assets\KanbanAsset;
use common\modules\kanban\models\KanbanBoard;

/* @var $this yii\web\View */
/* @var $columns common\modules\kanban\models\KanbanColumn[] */
/* @var $tasks array */
/* @var $categories array */

$this->title = 'Kanban Board';
// $this->params['breadcrumbs'][] = $this->title;

KanbanAsset::register($this);

// Add custom CSS for full-width layout and focus effects
$this->registerCss('
    .container {
        max-width: none !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    body {
        overflow-x: auto;
    }
    
    /* Focus Task Styles - Small Indicator Version */
    .task-focused {
        position: relative;
    }
    
    .task-focused .focus-indicator {
        display: block !important;
    }
    
    .focus-indicator {
        display: none;
        position: absolute;
        top: 8px;
        right: 8px;
        width: 12px;
        height: 12px;
        background: #ff5100ff;
        border-radius: 50%;
        animation: focusBlink 1.5s infinite;
        z-index: 5;
        box-shadow: 0 0 8px rgba(255, 94, 0, 0.6);
    }
    
    .focus-indicator::before {
        content: "";
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 6px;
        height: 6px;
        background: white;
        border-radius: 50%;
    }
    
    @keyframes focusBlink {
        0% { 
            opacity: 1;
            transform: scale(1);
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.6);
        }
        50% { 
            opacity: 0.3;
            transform: scale(1.2);
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.9);
        }
        100% { 
            opacity: 1;
            transform: scale(1);
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.6);
        }
    }
    
    /* Focus button styling */
    .btn-focus {
        background: none;
        border: none;
        color: #6c757d;
        padding: 5px;
        cursor: pointer;
        transition: color 0.3s ease;
    }
    
    .btn-focus:hover {
        color: #007bff;
    }
    
    .btn-focus.focused {
        color: #007bff;
        font-weight: bold;
    }

    /* Image Upload Styles */
    .task-images-section {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #e9ecef;
    }

    .task-images-section h5 {
        margin-bottom: 15px;
        color: #495057;
        font-weight: 600;
    }

    .image-upload-controls {
        margin-bottom: 15px;
        position: relative;
    }

    .clipboard-hint {
        font-size: 0.85em;
        color: #6c757d;
        margin-left: 10px;
    }

    .upload-progress {
        display: inline-block;
        margin-left: 10px;
        color: #007bff;
        font-size: 0.9em;
    }

    .task-images-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .task-image-item {
        display: flex;
        align-items: center;
        padding: 10px;
        margin-bottom: 10px;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        background: #f8f9fa;
    }

    .task-image-thumb {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
        margin-right: 12px;
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    .task-image-thumb:hover {
        transform: scale(1.05);
    }

    .image-info {
        flex: 1;
    }

    .image-name {
        font-weight: 500;
        font-size: 0.9em;
        color: #495057;
        margin-bottom: 3px;
    }

    .image-size {
        font-size: 0.8em;
        color: #6c757d;
    }

    .image-delete-btn {
        margin-left: 10px;
        padding: 5px 8px;
    }

    /* Image fullscreen overlay */
    .image-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
        cursor: pointer;
    }

    .image-overlay img {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
    }

    /* Task description styling - Professional & Clean Design */
    .task-description {
        margin: 20px 0;
        padding: 0;
        background: transparent;
        border-radius: 8px;
        position: relative;
    }

    .task-description h6 {
        font-size: 14px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 8px;
        display: flex;
        align-items: center;
    }

    .task-description h6:before {
        content: "📝";
        margin-right: 8px;
        font-size: 16px;
    }

    .description-content {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 16px 20px;
        font-size: 14px;
        line-height: 1.6;
        color: #495057;
        white-space: pre-wrap;
        word-wrap: break-word;
        word-break: break-word;
        max-width: 100%;
        overflow-wrap: break-word;
        min-height: 60px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
        position: relative;
    }

    .description-content:hover {
        background: #ffffff;
        border-color: #dee2e6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .description-content em {
        color: #6c757d;
        font-style: italic;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 60px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 4px;
        border: 2px dashed #dee2e6;
    }

    .description-content em:before {
        content: "📄";
        margin-right: 8px;
        opacity: 0.6;
    }

    /* Enhanced typography for description content */
    .description-content p {
        margin-bottom: 12px;
        line-height: 1.6;
    }

    .description-content p:last-child {
        margin-bottom: 0;
    }

    /* Responsive design for description */
    @media (max-width: 768px) {
        .description-content {
            padding: 12px 16px;
            font-size: 13px;
        }
        
        .task-description h6 {
            font-size: 13px;
        }
    }

    /* Focus state for better accessibility */
    .description-content:focus-within {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }

    /* Enhanced Deadline Statistics Cards */
    .kanban-stats {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 16px 20px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        min-width: 140px;
        border-left: 4px solid #e9ecef;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }

    .stat-number {
        font-size: 24px;
        font-weight: 700;
        color: #495057;
        margin-bottom: 4px;
        line-height: 1;
    }

    .stat-label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }

    .stat-label i {
        font-size: 14px;
    }

    /* Dynamic styling based on task_color_settings */
    .stat-card[style*="border-left-color"] {
        position: relative;
        overflow: hidden;
    }

    .stat-card[style*="border-left-color"]:before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: currentColor;
        opacity: 0.03;
        pointer-events: none;
    }

    .stat-card:hover[style*="border-left-color"]:before {
        opacity: 0.06;
    }

    /* Responsive design for stats */
    @media (max-width: 768px) {
        .kanban-stats {
            justify-content: flex-start;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        
        .stat-card {
            flex-shrink: 0;
            min-width: 120px;
            padding: 12px 16px;
        }
        
        .stat-number {
            font-size: 20px;
        }
        
        .stat-label {
            font-size: 11px;
        }
    }

    /* Animation for overdue tasks */
    .stat-overdue:hover {
        animation: urgentPulse 1s ease-in-out;
    }

    @keyframes urgentPulse {
        0%, 100% { 
            transform: translateY(-2px) scale(1); 
        }
        50% { 
            transform: translateY(-2px) scale(1.05); 
        }
    }

    /* Enhanced hover effects for all stat cards */
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }

    /* Special styling for "Due Today" card */
    .stat-due_today {
        background: linear-gradient(135deg, #fff3e0 0%, #ffffff 100%);
        border-left-width: 5px !important;
        position: relative;
    }

    .stat-due_today:before {
        content: "📅";
        position: absolute;
        top: 8px;
        right: 8px;
        font-size: 16px;
        opacity: 0.3;
    }

    .stat-due_today .stat-number {
        font-size: 28px !important;
        font-weight: 800 !important;
    }

    .stat-due_today .stat-label {
        font-weight: 700 !important;
        text-transform: none !important;
        font-size: 13px !important;
    }

    .stat-due_today:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(255, 87, 34, 0.25);
    }

    /* Animation for due today card */
    .stat-due_today:hover .stat-number {
        animation: todayPulse 0.6s ease-in-out;
    }

    @keyframes todayPulse {
        0%, 100% { 
            transform: scale(1); 
        }
        50% { 
            transform: scale(1.1); 
        }
    }

    /* Clickable stats cards */
    .stat-card {
        cursor: pointer;
        user-select: none;
    }

    .stat-card:active {
        transform: translateY(-1px);
        box-shadow: 0 2px 12px rgba(0,0,0,0.2);
    }

    /* Deadline Tasks Modal Styles */
    .deadline-tasks-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 15px;
        max-height: 60vh;
        overflow-y: auto;
        padding: 10px 0;
    }

    .deadline-task-card {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 16px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
        cursor: pointer;
        position: relative;
    }

    .deadline-task-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-color: #007bff;
    }

    .deadline-task-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }

    .deadline-task-title {
        font-size: 16px;
        font-weight: 600;
        color: #495057;
        margin: 0;
        line-height: 1.3;
        flex: 1;
        margin-right: 10px;
    }

    .deadline-task-priority {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .priority-low { background: #d4edda; color: #155724; }
    .priority-medium { background: #fff3cd; color: #856404; }
    .priority-high { background: #f8d7da; color: #721c24; }
    .priority-critical { background: #d1ecf1; color: #0c5460; }

    .deadline-task-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 10px;
        font-size: 13px;
        color: #6c757d;
    }

    .deadline-task-meta-item {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .deadline-task-meta-item i {
        width: 14px;
        text-align: center;
    }

    .deadline-task-description {
        font-size: 14px;
        color: #6c757d;
        line-height: 1.4;
        margin-bottom: 12px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .deadline-task-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 8px;
        border-top: 1px solid #f1f3f4;
    }

    .deadline-task-category {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        color: white;
    }

    .deadline-task-status {
        font-size: 12px;
        font-weight: 500;
        color: #6c757d;
        text-transform: capitalize;
    }

    .deadline-tasks-summary {
        font-size: 14px;
        color: #6c757d;
        font-weight: 500;
    }

    /* Empty state for deadline tasks modal */
    .deadline-tasks-empty {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }

    .deadline-tasks-empty i {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .deadline-tasks-empty h5 {
        margin-bottom: 8px;
        color: #495057;
    }

    /* Responsive design for deadline tasks modal */
    @media (max-width: 768px) {
        .deadline-tasks-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        
        .deadline-task-card {
            padding: 12px;
        }
    }
');

$statistics = KanbanBoard::getStatistics();
?>

<div class="kanban-board">
    <div class="kanban-header">
        <div class="header-top">
            <h1><?= Html::encode($this->title) ?></h1>
            <div class="header-actions">
                <button class="btn btn-primary btn-add-task" data-toggle="modal" data-target="#addTaskModal">
                    <i class="fa fa-plus"></i> Add Task
                </button>
                <button class="btn btn-secondary btn-add-column" data-toggle="modal" data-target="#addColumnModal">
                    <i class="fa fa-columns"></i> Add Column
                </button>
                <button class="btn btn-info btn-debug-positions" onclick="debugPositions()">
                    <i class="fa fa-bug"></i> Debug Positions
                </button>
            </div>
        </div>
        
        <!-- Deadline Statistics Cards (Based on Task Color Settings) -->
        <div class="kanban-stats">
            <?php foreach ($statistics as $key => $stat): ?>
                <div class="stat-card stat-<?= $key ?>" 
                     data-category="<?= Html::encode($key) ?>"
                     style="border-left-color: <?= $stat['color'] ?>">
                    <div class="stat-number stat-value" style="color: <?= $stat['color'] ?>"><?= $stat['count'] ?></div>
                    <div class="stat-label stat-title" style="color: <?= $stat['color'] ?>">
                        <i class="fa <?= $stat['icon'] ?>"></i> 
                        <?= Html::encode($stat['display_name']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Kanban Board Columns -->
    <div class="kanban-board-container">
        <?php foreach ($columns as $column): ?>
            <div class="kanban-column kanban-column-<?= str_replace('_', '-', $column->status_key) ?>" 
                 data-status="<?= $column->status_key ?>" 
                 data-column-id="<?= $column->id ?>">
                <div class="kanban-column-header" draggable="true" style="border-bottom-color: <?= $column->color ?>">
                    <i class="<?= $column->icon ?>"></i>
                    <span class="column-title"><?= Html::encode($column->name) ?></span>
                    <span class="task-count"><?= count(isset($tasks[$column->status_key]) ? $tasks[$column->status_key] : []) ?></span>
                    
                    <!-- Column Actions -->
                    <div class="column-actions">
                        <button class="btn-column-edit" data-column-id="<?= $column->id ?>" title="Edit Column">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn-column-delete" data-column-id="<?= $column->id ?>" title="Delete Column">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="kanban-column-body" data-status="<?= $column->status_key ?>">
                    <!-- Add Task Button for first column (To Do) -->
                    <?php if ($column->status_key === 'pending'): ?>
                        <div class="add-task-button">
                            <button class="btn btn-outline-primary btn-add-task-quick" data-status="<?= $column->status_key ?>">
                                <i class="fa fa-plus"></i> Add Task
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php $columnTasks = isset($tasks[$column->status_key]) ? $tasks[$column->status_key] : []; ?>
                    <?php if (empty($columnTasks)): ?>
                        <div class="empty-column">
                            <p>No tasks in this column</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($columnTasks as $task): ?>
                            <?php 
                                $categoryColor = $task->category && $task->category->color ? $task->category->color : '#6c757d';
                                $rgbColor = sscanf($categoryColor, "#%02x%02x%02x");
                                $backgroundOpacity = 0.15; // Light background
                                $borderOpacity = 1.0; // Full border
                            ?>
                            <div class="kanban-task <?= KanbanBoard::getPriorityClass($task->priority) ?>" 
                                 data-task-id="<?= $task->id ?>" 
                                 data-status="<?= $task->status ?>"
                                 style="background-color: rgba(<?= $rgbColor[0] ?>, <?= $rgbColor[1] ?>, <?= $rgbColor[2] ?>, <?= $backgroundOpacity ?>); 
                                        border-left-color: rgba(<?= $rgbColor[0] ?>, <?= $rgbColor[1] ?>, <?= $rgbColor[2] ?>, <?= $borderOpacity ?>);">
                                
                                <!-- Focus Indicator -->
                                <div class="focus-indicator" title="This task is focused"></div>
                                
                                <div class="task-header">
                                    <div class="task-category" style="background-color: <?= $categoryColor ?>">
                                        <?= Html::encode($task->category ? $task->category->name : 'No Category') ?>
                                    </div>
                                    <div class="task-priority priority-<?= $task->priority ?>">
                                        <?= strtoupper($task->priority) ?>
                                    </div>
                                </div>
                                
                                <div class="task-content">
                                    <h4 class="task-title"><?= Html::encode($task->title) ?></h4>
                                    <?php if ($task->description): ?>
                                        <p class="task-description"><?= Html::encode(substr($task->description, 0, 100)) ?><?= strlen($task->description) > 100 ? '...' : '' ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="task-footer">
                                    <div class="task-deadline <?= $task->isOverdue() ? 'overdue' : '' ?>">
                                        <i class="fa fa-clock-o"></i>
                                        <?= date('M j, Y', $task->deadline) ?>
                                    </div>
                                    
                                    <?php if ($task->assigned_to): ?>
                                        <div class="task-assignee">
                                            <i class="fa fa-user"></i>
                                            User #<?= $task->assigned_to ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($task->images): ?>
                                        <div class="task-attachments">
                                            <i class="fa fa-paperclip"></i>
                                            <?= count($task->images) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $commentCount = $task->getCommentsCount();
                                    if ($commentCount > 0): ?>
                                        <div class="task-comments">
                                            <i class="fa fa-comments"></i>
                                            <?= $commentCount ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="task-actions">
                                    <button class="btn-focus" data-task-id="<?= $task->id ?>" title="Focus on this Task">
                                        <i class="fa fa-bullseye"></i>
                                    </button>
                                    <button class="btn-history" data-task-id="<?= $task->id ?>" title="View History">
                                        <i class="fa fa-history"></i>
                                    </button>
                                    <button class="btn-edit" data-task-id="<?= $task->id ?>" title="Edit Task">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn-delete" data-task-id="<?= $task->id ?>" title="Delete Task">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add Task Modal -->
<div id="addTaskModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add New Task</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addTaskForm">
                    <div class="form-group">
                        <label for="taskTitle">Title *</label>
                        <input type="text" class="form-control" id="taskTitle" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="taskDescription">Description</label>
                        <textarea class="form-control" id="taskDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="taskCategory">Category *</label>
                                <select class="form-control" id="taskCategory" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category->id ?>"><?= Html::encode($category->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="taskPriority">Priority</label>
                                <select class="form-control" id="taskPriority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="taskDeadline">Deadline</label>
                                <input type="datetime-local" class="form-control" id="taskDeadline" name="deadline">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="taskStatus">Status</label>
                                <select class="form-control" id="taskStatus" name="status">
                                    <?php foreach ($columns as $column): ?>
                                        <option value="<?= $column->status_key ?>" <?= $column->status_key === 'pending' ? 'selected' : '' ?>>
                                            <?= Html::encode($column->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="taskDefaultStatus" name="default_status" value="pending">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveTaskBtn">Add Task</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div id="editTaskModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Task</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editTaskForm">
                    <input type="hidden" id="editTaskId" name="id">
                    
                    <div class="form-group">
                        <label for="editTaskTitle">Title *</label>
                        <input type="text" class="form-control" id="editTaskTitle" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editTaskDescription">Description</label>
                        <textarea class="form-control" id="editTaskDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editTaskCategory">Category *</label>
                                <select class="form-control" id="editTaskCategory" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category->id ?>"><?= Html::encode($category->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editTaskPriority">Priority</label>
                                <select class="form-control" id="editTaskPriority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editTaskDeadline">Deadline</label>
                                <input type="datetime-local" class="form-control" id="editTaskDeadline" name="deadline">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editTaskStatus">Status</label>
                                <select class="form-control" id="editTaskStatus" name="status">
                                    <?php foreach ($columns as $column): ?>
                                        <option value="<?= $column->status_key ?>">
                                            <?= Html::encode($column->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="editTaskAssignedTo">Assigned To</label>
                                <input type="text" class="form-control" id="editTaskAssignedTo" name="assigned_to" placeholder="Assign to...">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updateTaskBtn">Update Task</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Column Modal -->
<div id="addColumnModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add New Column</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addColumnForm">
                    <div class="form-group">
                        <label for="columnName">Column Name *</label>
                        <input type="text" class="form-control" id="columnName" name="name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="columnColor">Color</label>
                                <input type="color" class="form-control" id="columnColor" name="color" value="#6c757d">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="columnIcon">Icon Class</label>
                                <input type="text" class="form-control" id="columnIcon" name="icon" placeholder="fa fa-list" value="fa fa-list">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveColumnBtn">Add Column</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Column Modal -->
<div id="editColumnModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Column</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editColumnForm">
                    <input type="hidden" id="editColumnId" name="id">
                    
                    <div class="form-group">
                        <label for="editColumnName">Column Name *</label>
                        <input type="text" class="form-control" id="editColumnName" name="name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editColumnColor">Color</label>
                                <input type="color" class="form-control" id="editColumnColor" name="color">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editColumnIcon">Icon Class</label>
                                <input type="text" class="form-control" id="editColumnIcon" name="icon" placeholder="fa fa-list">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updateColumnBtn">Update Column</button>
            </div>
        </div>
    </div>
</div>

<!-- Task Details Modal -->
<div id="taskDetailsModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="taskDetailsModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="taskDetailsModalLabel">Task Details</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="taskDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading task details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editTaskFromDetails" style="display: none;">
                    <i class="fa fa-edit"></i> Edit Task
                </button>
                <button type="button" class="btn btn-danger" id="deleteTaskFromDetails" style="display: none;">
                    <i class="fa fa-trash"></i> Delete Task
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Deadline Tasks Modal -->
<div id="deadlineTasksModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="deadlineTasksModalLabel">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="deadlineTasksModalLabel">
                    <i class="fa fa-calendar" id="deadlineTasksIcon"></i>
                    <span id="deadlineTasksTitle">Deadline Tasks</span>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="deadlineTasksContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading tasks...</p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="deadline-tasks-summary">
                    <span id="deadlineTasksCount">0 tasks found</span>
                </div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs("
    // Initialize Kanban Board - DEBUG VERSION
    console.log('About to initialize KanbanBoard...');
    try {
        KanbanBoard.init({
            updateTaskUrl: '" . Url::to(['update-task-status']) . "',
            updatePositionUrl: '" . Url::to(['update-task-position']) . "',
            updateColumnPositionUrl: '" . Url::to(['update-column-position']) . "',
            addTaskUrl: '" . Url::to(['add-task']) . "',
            addColumnUrl: '" . Url::to(['add-column']) . "',
            editColumnUrl: '" . Url::to(['edit-column']) . "',
            deleteColumnUrl: '" . Url::to(['delete-column']) . "',
            getTaskUrl: '" . Url::to(['get-task']) . "',
            getTaskDetailsUrl: '" . Url::to(['get-task-details']) . "',
            getTaskHistoryUrl: '" . Url::to(['get-task-history']) . "',
            editTaskUrl: '" . Url::to(['edit-task']) . "',
            deleteTaskUrl: '" . Url::to(['delete-task']) . "',
            getCommentsUrl: '" . Url::to(['/taskmonitor/comment/get-comments']) . "',
            addCommentUrl: '" . Url::to(['/taskmonitor/comment/add']) . "',
            editCommentUrl: '" . Url::to(['/taskmonitor/comment/edit']) . "',
            deleteCommentUrl: '" . Url::to(['/taskmonitor/comment/delete']) . "',
            imageUploadUrl: '" . Url::to(['/taskmonitor/image/upload']) . "',
            imageClipboardUrl: '" . Url::to(['/taskmonitor/image/upload-clipboard']) . "',
            imageListUrl: '" . Url::to(['/taskmonitor/image/list']) . "',
            imageDeleteUrl: '" . Url::to(['/taskmonitor/image/delete']) . "',
            getDeadlineTasks: '" . Url::to(['get-deadline-tasks']) . "',
            csrfToken: '" . Yii::$app->request->csrfToken . "',
            csrfParam: '" . Yii::$app->request->csrfParam . "'
        });
        console.log('KanbanBoard initialized successfully');
    } catch (error) {
        console.error('Error initializing KanbanBoard:', error);
        alert('KanbanBoard initialization error: ' + error.message);
    }

    // Debug function to test position updates
    window.debugPositions = function() {
        console.log('=== DEBUG POSITIONS ===');
        
        // Show current task order in DOM
        $('.kanban-column-body').each(function() {
            var column = $(this);
            var status = column.data('status') || column.closest('.kanban-column').data('status');
            console.log('Column:', status);
            
            column.find('.kanban-task').each(function(index) {
                var task = $(this);
                var taskId = task.data('task-id');
                console.log('  [' + index + '] Task ID:', taskId, 'DOM Position:', index);
            });
        });
        
        // Test moving task 2 to position 0
        console.log('Testing position update...');
        $.ajax({
            url: '" . Url::to(['update-task-position']) . "',
            method: 'POST',
            data: {
                taskId: 2,
                position: 0,
                status: 'completed',
                '" . Yii::$app->request->csrfParam . "': '" . Yii::$app->request->csrfToken . "'
            },
            success: function(response) {
                console.log('AJAX Success:', response);
                alert('Position update result: ' + JSON.stringify(response));
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                alert('AJAX Error: ' + error);
            }
        });
    };
");
?>

<?= $this->render('_task_history_modal') ?>