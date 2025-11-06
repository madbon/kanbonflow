<?php
/* @var $this yii\web\View */
/* @var $task common\modules\taskmonitor\models\Task */
/* @var $history common\modules\taskmonitor\models\TaskHistory[] */

use yii\helpers\Html;

?>

<!-- Task History Modal -->
<div class="modal fade" id="taskHistoryModal" tabindex="-1" role="dialog" aria-labelledby="taskHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taskHistoryModalLabel">
                    <i class="fa fa-history"></i> Task History
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="taskHistoryContent">
                    <div class="text-center">
                        <i class="fa fa-spin fa-spinner"></i> Loading history...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.task-history-timeline {
    position: relative;
    padding: 0;
    list-style: none;
}

.task-history-timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 30px;
    width: 2px;
    background-color: #e9ecef;
}

.task-history-item {
    position: relative;
    margin-bottom: 20px;
    padding-left: 70px;
}

.task-history-item::before {
    content: '';
    position: absolute;
    top: 5px;
    left: 24px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #6c757d;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
}

.task-history-item.created::before { background-color: #28a745; }
.task-history-item.updated::before { background-color: #17a2b8; }
.task-history-item.status_changed::before { background-color: #007bff; }
.task-history-item.position_changed::before { background-color: #6f42c1; }
.task-history-item.priority_changed::before { background-color: #fd7e14; }
.task-history-item.completed::before { background-color: #28a745; }
.task-history-item.deleted::before { background-color: #dc3545; }

.task-history-icon {
    position: absolute;
    top: 2px;
    left: 18px;
    width: 24px;
    height: 24px;
    text-align: center;
    line-height: 20px;
    font-size: 12px;
    color: #fff;
    background-color: #6c757d;
    border-radius: 50%;
    z-index: 10;
}

.task-history-icon.created { background-color: #28a745; }
.task-history-icon.updated { background-color: #17a2b8; }
.task-history-icon.status_changed { background-color: #007bff; }
.task-history-icon.position_changed { background-color: #6f42c1; }
.task-history-icon.priority_changed { background-color: #fd7e14; }
.task-history-icon.completed { background-color: #28a745; }
.task-history-icon.deleted { background-color: #dc3545; }

.task-history-content {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.task-history-action {
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
}

.task-history-description {
    color: #6c757d;
    margin-bottom: 8px;
    line-height: 1.4;
}

.task-history-meta {
    font-size: 0.85em;
    color: #adb5bd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.task-history-user {
    font-weight: 500;
    color: #495057;
}

.task-history-time {
    font-style: italic;
}

.task-history-changes {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #dee2e6;
    font-size: 0.9em;
}

.task-history-change {
    display: flex;
    justify-content: space-between;
    margin: 2px 0;
}

.task-history-change-label {
    font-weight: 500;
    color: #6c757d;
}

.task-history-change-old {
    color: #dc3545;
    text-decoration: line-through;
}

.task-history-change-new {
    color: #28a745;
    font-weight: 500;
}

.empty-history {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-history i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}
</style>