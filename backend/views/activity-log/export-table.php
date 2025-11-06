<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Table Export</title>
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
        
        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
            font-size: 0.8rem;
        }
        
        .activity-icon.created { background: #28a745; color: white; }
        .activity-icon.updated { background: #17a2b8; color: white; }
        .activity-icon.status_changed { background: #ffc107; color: #212529; }
        .activity-icon.position_changed { background: #6f42c1; color: white; }
        .activity-icon.priority_changed { background: #fd7e14; color: white; }
        .activity-icon.category_changed { background: #20c997; color: white; }
        .activity-icon.deadline_changed { background: #dc3545; color: white; }
        .activity-icon.deleted { background: #dc3545; color: white; }
        .activity-icon.completed { background: #20c997; color: white; }
        .activity-icon.assigned { background: #fd7e14; color: white; }
        .activity-icon.unassigned { background: #6c757d; color: white; }
        .activity-icon.restored { background: #28a745; color: white; }
        
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
        
        .activity-description {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .date-column {
            white-space: nowrap;
            min-width: 150px;
        }
        
        .category-column {
            min-width: 120px;
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
            
            .activity-icon {
                width: 24px;
                height: 24px;
                border-radius: 12px;
                font-size: 0.7rem;
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
        <h1 class="mb-2">Activity Log Export</h1>
        <p class="mb-0">
            Generated on <?= date('F j, Y \a\t g:i A') ?>
            <?php 
            // Count activities excluding position_changed
            $filteredCount = 0;
            foreach ($activities as $activity) {
                if ($activity->action_type !== 'position_changed') {
                    $filteredCount++;
                }
            }
            ?>
            <?php if ($filteredCount > 0): ?>
                | <?= $filteredCount ?> activities
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
                <?php if ($filters['action_type']): ?>
                    <div class="col-md-3">
                        <strong>Action:</strong> <?= ucfirst(str_replace('_', ' ', $filters['action_type'])) ?>
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
        <?php if ($filteredCount > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Activity Details</th>
                            <th style="width: 25%;">Date & Time</th>
                            <th style="width: 25%;">Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $activity): ?>
                            <?php if ($activity->action_type === 'position_changed') continue; ?>
                            <tr>
                                <!-- Activity Details Column -->
                                <td>
                                    <div class="d-flex align-items-start">
                                        <div class="activity-icon <?= $activity->action_type ?>">
                                            <?php
                                            $icons = [
                                                'created' => 'fa fa-plus',
                                                'updated' => 'fa fa-edit',
                                                'status_changed' => 'fa fa-exchange-alt',
                                                'position_changed' => 'fa fa-arrows-alt',
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
                                        <div>
                                            <div class="task-title">
                                                <?= $activity->getActionTypeLabel() ?>
                                                <?php if ($activity->task): ?>
                                                    - <?= htmlspecialchars($activity->task->title) ?>
                                                <?php else: ?>
                                                    <small class="text-muted">(Task no longer exists)</small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="activity-description">
                                                <?= htmlspecialchars($activity->description) ?>
                                            </div>
                                            <?php if ($activity->user): ?>
                                                <small class="text-muted">
                                                    <i class="fa fa-user"></i> <?= htmlspecialchars($activity->user->username) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Date & Time Column -->
                                <td class="date-column">
                                    <div>
                                        <strong><?= date('M j, Y', $activity->created_at) ?></strong>
                                    </div>
                                    <small class="text-muted">
                                        <?= date('g:i A', $activity->created_at) ?>
                                    </small>
                                    <br>
                                    <small class="text-info">
                                        <?= Yii::$app->formatter->asRelativeTime($activity->created_at) ?>
                                    </small>
                                </td>
                                
                                <!-- Category Column -->
                                <td class="category-column">
                                    <?php if ($activity->task && $activity->task->category): ?>
                                        <span class="category-badge" style="background-color: <?= $activity->task->category->color ?>;">
                                            <?= htmlspecialchars($activity->task->category->name) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No Category</span>
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
                <h5 class="text-muted">No Activities Found</h5>
                <p class="text-muted">No activities match the current filter criteria.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <div class="text-center mt-4">
        <small class="text-muted">
            Generated from KanbonFlow Activity Log System
            <?php if (count($activities) >= 500): ?>
                <br><strong>Note:</strong> Limited to 500 most recent activities
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