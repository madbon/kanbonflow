<?php
use common\modules\taskmonitor\models\Task;
use yii\helpers\Html;

$this->title = 'Tasks Export Table';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks List - Export Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        
        .export-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: #495057;
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
            padding: 1rem 0.75rem;
        }
        
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .table td {
            vertical-align: middle;
            padding: 0.75rem;
            border-top: 1px solid #e9ecef;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 4px;
            color: white;
        }
        
        .status-badge.pending { background-color: #ffc107; color: #212529; }
        .status-badge.in_progress { background-color: #17a2b8; }
        .status-badge.completed { background-color: #28a745; }
        .status-badge.cancelled { background-color: #dc3545; }
        
        .priority-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 4px;
            color: white;
        }
        
        .priority-badge.low { background-color: #28a745; }
        .priority-badge.medium { background-color: #ffc107; color: #212529; }
        .priority-badge.high { background-color: #fd7e14; }
        .priority-badge.critical { background-color: #dc3545; }
        
        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 4px;
            color: white;
        }
        
        .task-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.25rem;
        }
        
        .task-description {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .task-description a {
            color: #007bff;
            text-decoration: underline;
        }
        
        .date-column {
            white-space: nowrap;
            min-width: 150px;
        }
        
        .category-column {
            min-width: 120px;
        }
        
        .last-comment {
            font-size: 0.85rem;
        }
        
        .comment-text {
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            border-left: 3px solid #007bff;
            line-height: 1.3;
        }
        
        .comment-text a {
            color: #007bff;
            text-decoration: underline;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .filters-info {
            background: #e9ecef;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        @media print {
            .print-button, .filters-info {
                display: none !important;
            }
            
            body {
                background: white !important;
                padding: 0 !important;
            }
            
            .export-header {
                background: #495057 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .table thead th {
                background: #495057 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <button class="btn btn-primary print-button" onclick="window.print()">
        <i class="fa fa-print"></i> Print
    </button>
    
    <!-- Header -->
    <div class="export-header">
        <h1 class="mb-2">Tasks List Export</h1>
        <p class="mb-0">
            Generated on <?= date('F j, Y \a\t g:i A') ?>
            <?php if (count($tasks) > 0): ?>
                | <?= count($tasks) ?> tasks
            <?php endif; ?>
        </p>
    </div>
    
    <!-- Filter Information -->
    <?php if (array_filter($filters)): ?>
        <div class="filters-info">
            <h6 class="mb-2"><i class="fa fa-filter"></i> Applied Filters:</h6>
            <div class="row">
                <?php if ($filters['date_from']): ?>
                    <div class="col-md-3">
                        <strong>From:</strong> <?= date('M j, Y', strtotime($filters['date_from'])) ?>
                    </div>
                <?php endif; ?>
                <?php if ($filters['date_to']): ?>
                    <div class="col-md-3">
                        <strong>To:</strong> <?= date('M j, Y', strtotime($filters['date_to'])) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($filters['action_types']) && is_array($filters['action_types'])): ?>
                    <div class="col-md-6">
                        <strong>Action Types:</strong> <?= implode(', ', array_map('ucfirst', str_replace('_', ' ', $filters['action_types']))) ?>
                    </div>
                <?php endif; ?>
                <?php if ($filters['task_id']): ?>
                    <div class="col-md-3">
                        <strong>Task ID:</strong> <?= $filters['task_id'] ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Table -->
    <div class="table-container">
        <?php if (count($tasks) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 12%;">Task Category</th>
                            <th style="width: 18%;">Task Title</th>
                            <th style="width: 30%;">Task Description</th>
                            <th style="width: 25%;">Last Comment</th>
                            <th style="width: 10%;">Last Updated</th>
                            <th style="width: 10%;">Current Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <!-- Task Category Column -->
                                <td class="category-column">
                                    <?php if ($task->category): ?>
                                        <span class="category-badge" style="background-color: <?= Html::encode($task->category->color) ?>;">
                                            <?= Html::encode($task->category->name) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No Category</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Task Title Column -->
                                <td>
                                    <div class="task-title">
                                        <?= Html::encode($task->title) ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fa fa-hashtag"></i> <?= $task->id ?>
                                    </small>
                                    <?php if ($task->priority): ?>
                                        <br>
                                        <span class="priority-badge <?= $task->priority ?>">
                                            <?= Html::encode(ucfirst($task->priority)) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Task Description (Full Details) Column -->
                                <td>
                                    <div class="task-description">
                                        <?php 
                                        $description = Html::encode($task->description);
                                        // Convert URLs to clickable links
                                        $description = preg_replace(
                                            '/(https?:\/\/[^\s<>"\'{}|\\\^\[\]`]+)/i',
                                            '<a href="$1" target="_blank">$1</a>',
                                            $description
                                        );
                                        echo nl2br($description);
                                        ?>
                                    </div>
                                    <?php if ($task->deadline): ?>
                                        <small class="text-info">
                                            <i class="fa fa-calendar-alt"></i> 
                                            Deadline: <?= date('M j, Y', $task->deadline) ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if ($task->assignedTo): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fa fa-user"></i> 
                                            Assigned to: <?= Html::encode($task->assignedTo->username) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Last Comment Column -->
                                <td>
                                    <?php if ($task->latestComment): ?>
                                        <div class="last-comment">
                                            <div class="comment-text">
                                                <?php 
                                                $commentText = Html::encode($task->latestComment->comment);
                                                // Convert URLs to clickable links
                                                $commentText = preg_replace(
                                                    '/(https?:\/\/[^\s<>"\'{}|\\\^\[\]`]+)/i',
                                                    '<a href="$1" target="_blank">$1</a>',
                                                    $commentText
                                                );
                                                // Limit comment length for display
                                                if (strlen($commentText) > 150) {
                                                    echo substr(nl2br($commentText), 0, 150) . '...';
                                                } else {
                                                    echo nl2br($commentText);
                                                }
                                                ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fa fa-user"></i> 
                                                <?= $task->latestComment->user ? Html::encode($task->latestComment->user->username) : 'Unknown User' ?>
                                                <br>
                                                <i class="fa fa-clock"></i> 
                                                <?= date('M j, Y g:i A', $task->latestComment->created_at) ?>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted text-center">
                                            <i class="fa fa-comment-slash"></i><br>
                                            <small>No comments</small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Last Updated Column -->
                                <td class="date-column">
                                    <div>
                                        <strong><?= date('M j, Y', $task->updated_at) ?></strong>
                                    </div>
                                    <small class="text-muted">
                                        <?= date('g:i A', $task->updated_at) ?>
                                    </small>
                                    <br>
                                    <small class="text-info">
                                        <?= Yii::$app->formatter->asRelativeTime($task->updated_at) ?>
                                    </small>
                                </td>
                                
                                <!-- Current Status Column -->
                                <td>
                                    <span class="status-badge <?= $task->status ?>">
                                        <?= Html::encode(ucfirst(str_replace('_', ' ', $task->status))) ?>
                                    </span>
                                    <?php if ($task->status === Task::STATUS_COMPLETED && $task->completed_at): ?>
                                        <br>
                                        <small class="text-muted">
                                            Completed: <?= date('M j, Y', $task->completed_at) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Tasks Found</h5>
                <p class="text-muted">No tasks match the current filter criteria.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <div class="text-center mt-4">
        <small class="text-muted">
            Generated from KanbonFlow Task Management System
            <?php if (count($tasks) >= 500): ?>
                <br><strong>Note:</strong> Limited to 500 most recent tasks
            <?php endif; ?>
        </small>
    </div>

    <script>
        // Auto-focus for better printing experience
        window.onload = function() {
            document.body.focus();
        };
        
        // Keyboard shortcut for printing
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>