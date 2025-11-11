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
        
        /* Status badge colors matching kanban_columns table */
        .status-badge.pending { background-color: #6c757d; color: white; }
        .status-badge.ongoing { background-color: #fff700; color: #212529; }
        .status-badge.done { background-color: #28a745; color: white; }
        .status-badge.testcase { background-color: #000000; color: white; }
        .status-badge.staging { background-color: #000000; color: white; }
        .status-badge.debugging { background-color: #000000; color: white; }
        .status-badge.production { background-color: #000000; color: white; }
        .status-badge.verifying { background-color: #000000; color: white; }
        .status-badge.completed { background-color: #2eff62; color: #212529; }
        
        /* Legacy status support (if any tasks still use these) */
        .status-badge.in_progress { background-color: #fff700; color: #212529; }
        .status-badge.cancelled { background-color: #dc3545; color: white; }
        
        /* Default fallback for any unknown status */
        .status-badge:not(.pending):not(.ongoing):not(.done):not(.testcase):not(.staging):not(.debugging):not(.production):not(.verifying):not(.completed):not(.in_progress):not(.cancelled) {
            background-color: #6c757d;
            color: white;
        }
        
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
        
        .checklist-status .progress {
            height: 18px;
        }
        
        .checklist-status .progress-bar {
            font-size: 0.75rem;
            line-height: 18px;
        }
        
        .history-item {
            font-size: 0.85rem;
        }
        
        .history-item .history-content {
            margin-bottom: 0.25rem;
        }
        
        .history-item + .history-item {
            border-top: 1px solid #e9ecef;
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
                            <th style="width: 10%;">Task Category</th>
                            <th style="width: 15%;">Task Title</th>
                            <th style="width: 20%;">Task Description</th>
                            <th style="width: 15%;">History Descriptions</th>
                            <th style="width: 12%;">Steps Status</th>
                            <th style="width: 18%;">Last Comment</th>
                            <th style="width: 8%;">Last Updated</th>
                            <th style="width: 8%;">Current Status</th>
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
                                
                                <!-- History Description(s) Column -->
                                <td>
                                    <?php 
                                    $extraData = isset($taskExtraData[$task->id]) ? $taskExtraData[$task->id] : null;
                                    $historyDescriptions = $extraData ? $extraData['historyDescriptions'] : [];
                                    $hasDateFilter = $extraData ? $extraData['hasDateFilter'] : false;
                                    ?>
                                    <?php if (!empty($historyDescriptions)): ?>
                                        <?php if ($hasDateFilter && count($historyDescriptions) > 1): ?>
                                            <!-- Multiple descriptions within date range -->
                                            <div class="task-description">
                                                <small class="text-info mb-2 d-block">
                                                    <i class="fa fa-calendar"></i> <?= count($historyDescriptions) ?> descriptions in date range
                                                </small>
                                                <?php foreach ($historyDescriptions as $index => $historyItem): ?>
                                                    <div class="history-item <?= $index > 0 ? 'mt-2 pt-2 border-top' : '' ?>">
                                                        <div class="history-content">
                                                            <?php 
                                                            $historyDesc = Html::encode($historyItem['description']);
                                                            // Convert URLs to clickable links
                                                            $historyDesc = preg_replace(
                                                                '/(https?:\/\/[^\s<>"\'{}|\\\^\[\]`]+)/i',
                                                                '<a href="$1" target="_blank">$1</a>',
                                                                $historyDesc
                                                            );
                                                            // Limit description length for display
                                                            if (strlen($historyDesc) > 100) {
                                                                echo substr(nl2br($historyDesc), 0, 100) . '...';
                                                            } else {
                                                                echo nl2br($historyDesc);
                                                            }
                                                            ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="fa fa-user"></i> <?= $historyItem['user'] ?> 
                                                            <i class="fa fa-clock ml-1"></i> <?= date('M j, Y g:i A', $historyItem['created_at']) ?>
                                                        </small>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <!-- Single description (last one) -->
                                            <div class="task-description">
                                                <div class="history-content">
                                                    <?php 
                                                    $historyDesc = Html::encode($historyDescriptions[0]['description']);
                                                    // Convert URLs to clickable links
                                                    $historyDesc = preg_replace(
                                                        '/(https?:\/\/[^\s<>"\'{}|\\\^\[\]`]+)/i',
                                                        '<a href="$1" target="_blank">$1</a>',
                                                        $historyDesc
                                                    );
                                                    // Limit description length for display
                                                    if (strlen($historyDesc) > 120) {
                                                        echo substr(nl2br($historyDesc), 0, 120) . '...';
                                                    } else {
                                                        echo nl2br($historyDesc);
                                                    }
                                                    ?>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fa fa-user"></i> <?= $historyDescriptions[0]['user'] ?> 
                                                    <i class="fa fa-clock ml-1"></i> <?= date('M j, Y g:i A', $historyDescriptions[0]['created_at']) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-muted text-center">
                                            <i class="fa fa-history"></i><br>
                                            <small><?= $hasDateFilter ? 'No history in date range' : 'No history' ?></small>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Steps Status Column -->
                                <td>
                                    <?php 
                                    $checklistProgress = $extraData ? $extraData['checklistProgress'] : null;
                                    ?>
                                    <?php if ($checklistProgress && $checklistProgress['total'] > 0): ?>
                                        <div class="checklist-status">
                                            <div class="progress mb-2">
                                                <div class="progress-bar <?= $checklistProgress['percentage'] == 100 ? 'bg-success' : 'bg-info' ?>" 
                                                     style="width: <?= $checklistProgress['percentage'] ?>%">
                                                    <?= $checklistProgress['percentage'] ?>%
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fa fa-check-circle"></i> 
                                                <?= $checklistProgress['status'] ?>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted text-center">
                                            <i class="fa fa-list"></i><br>
                                            <small>No checklist</small>
                                        </div>
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
                                                if (strlen($commentText) > 100) {
                                                    echo substr(nl2br($commentText), 0, 100) . '...';
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