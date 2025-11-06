<?php
/**
 * Simple debug page to test task details functionality
 */
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Kanban Task Details Test';
?>

<div class="container-fluid">
    <h1>Task Details Modal Test</h1>
    <p>This page tests the task details modal functionality.</p>
    
    <div class="row">
        <div class="col-md-6">
            <h3>Test Data</h3>
            <div id="test-results"></div>
            
            <h3>Actions</h3>
            <button id="create-test-data" class="btn btn-primary">Create Test Data</button>
            <button id="test-task-details" class="btn btn-success">Test Task Details</button>
        </div>
        
        <div class="col-md-6">
            <h3>Debug Output</h3>
            <pre id="debug-output"></pre>
        </div>
    </div>
    
    <!-- Task Details Modal -->
    <div class="modal fade" id="taskDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Task Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
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
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var csrfToken = $('meta[name=csrf-token]').attr('content');
    var csrfParam = $('meta[name=csrf-param]').attr('content');
    
    function log(message) {
        var timestamp = new Date().toLocaleTimeString();
        $('#debug-output').append('[' + timestamp + '] ' + message + '\n');
    }
    
    function createTestData() {
        log('Creating test data...');
        $.ajax({
            url: '<?= Url::to(['create-test-data']) ?>',
            method: 'POST',
            data: {
                [csrfParam]: csrfToken
            },
            success: function(response) {
                log('Test data created: ' + JSON.stringify(response));
                $('#test-results').html('<div class="alert alert-success">Test data created successfully!</div>');
            },
            error: function(xhr, status, error) {
                log('Error creating test data: ' + error);
                $('#test-results').html('<div class="alert alert-danger">Error: ' + error + '</div>');
            }
        });
    }
    
    function testTaskDetails() {
        log('Testing task details...');
        var taskId = 1; // Assuming first task has ID 1
        
        $.ajax({
            url: '<?= Url::to(['get-task-details']) ?>',
            method: 'GET',
            data: {
                id: taskId,
                [csrfParam]: csrfToken
            },
            success: function(response) {
                log('Task details response: ' + JSON.stringify(response, null, 2));
                
                if (response.success) {
                    renderTaskDetails(response.task);
                    $('#taskDetailsModal').modal('show');
                } else {
                    log('Error: ' + response.message);
                    $('#test-results').html('<div class="alert alert-danger">Error: ' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                log('AJAX Error: ' + error);
                log('Response text: ' + xhr.responseText);
                $('#test-results').html('<div class="alert alert-danger">AJAX Error: ' + error + '</div>');
            }
        });
    }
    
    function renderTaskDetails(task) {
        var html = `
            <div class="task-details">
                <div class="task-details-meta">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Status:</strong> 
                            <span class="task-details-status status-${task.status}">${task.status_label}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Priority:</strong> 
                            <span class="task-details-priority priority-${task.priority}">${task.priority_label}</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Category:</strong> 
                            ${task.category ? `<span class="task-details-category-color" style="background-color: ${task.category.color}"></span>${task.category.name}` : 'No Category'}
                        </div>
                        <div class="col-md-6">
                            <strong>Deadline:</strong> ${task.deadline}
                        </div>
                    </div>
                </div>
                
                <h4>${task.title}</h4>
                
                <div class="task-details-description">
                    ${task.description || 'No description provided.'}
                </div>
                
                ${task.images.length > 0 ? '<h5>Attached Images</h5>' : ''}
                ${task.images.map(img => `<img src="${img.url}" alt="${img.name}" class="task-details-image">`).join('')}
            </div>
        `;
        
        $('#taskDetailsContent').html(html);
    }
    
    $('#create-test-data').click(createTestData);
    $('#test-task-details').click(testTaskDetails);
    
    log('Test page loaded successfully');
});
</script>

<style>
.task-details-priority, .task-details-status {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: bold;
    text-transform: uppercase;
}

.task-details-priority.priority-low {
    background-color: #d4edda;
    color: #155724;
}

.task-details-priority.priority-medium {
    background-color: #fff3cd;
    color: #856404;
}

.task-details-priority.priority-high {
    background-color: #f8d7da;
    color: #721c24;
}

.task-details-status.status-pending {
    background-color: #e2e3e5;
    color: #495057;
}

.task-details-status.status-in_progress {
    background-color: #cce5ff;
    color: #0056b3;
}

.task-details-status.status-completed {
    background-color: #d4edda;
    color: #155724;
}

.task-details-category-color {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    margin-right: 0.5rem;
    vertical-align: middle;
}

.task-details-meta {
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.task-details-description {
    background-color: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.task-details-image {
    max-width: 100%;
    height: auto;
    border-radius: 0.375rem;
    margin-bottom: 0.75rem;
}

#debug-output {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 10px;
    height: 300px;
    overflow-y: auto;
    font-size: 12px;
}
</style>