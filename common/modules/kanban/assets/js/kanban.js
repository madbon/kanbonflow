/**
 * Kanban Board JavaScript
 * Updated: 2025-11-06 with edit/delete functionality
 */
console.log('Kanban.js loaded - Version with edit functionality');
var KanbanBoard = {
    config: {
        updateTaskUrl: '',
        updatePositionUrl: '',
        addTaskUrl: '',
        addColumnUrl: '',
        editColumnUrl: '',
        deleteColumnUrl: '',
        getTaskUrl: '',
        editTaskUrl: '',
        deleteTaskUrl: ''
    },

    init: function(options) {
        this.config = $.extend(this.config, options);
        console.log('Kanban config initialized:', this.config);
        this.bindEvents();
        this.initDragAndDrop();
        this.loadTags();
        this.loadParentTasks();
    },

    bindEvents: function() {
        var self = this;

        // Task click events
        $(document).on('click', '.kanban-task', function(e) {
            if (!$(e.target).closest('.task-actions').length) {
                self.showTaskDetails($(this).data('task-id'));
            }
        });

        // Edit task button
        $(document).on('click', '.btn-edit', function(e) {
            e.stopPropagation();
            var taskId = $(this).data('task-id');
            self.editTask(taskId);
        });

        // Delete task button
        $(document).on('click', '.btn-delete', function(e) {
            e.stopPropagation();
            var taskId = $(this).data('task-id');
            self.deleteTask(taskId);
        });

        // Add task buttons
        $(document).on('click', '.btn-add-task, .btn-add-task-quick', function(e) {
            e.preventDefault();
            var status = $(this).data('status') || 'pending';
            self.showAddTaskModal(status);
        });

        // Add column button
        $(document).on('click', '.btn-add-column', function(e) {
            e.preventDefault();
            self.showAddColumnModal();
        });

        // Column management buttons
        $(document).on('click', '.btn-column-edit', function(e) {
            e.stopPropagation();
            var columnId = $(this).data('column-id');
            self.showEditColumnModal(columnId);
        });

        $(document).on('click', '.btn-column-delete', function(e) {
            e.stopPropagation();
            var columnId = $(this).data('column-id');
            self.deleteColumn(columnId);
        });

        // Modal save buttons
        $('#saveTaskBtn').on('click', function() {
            self.saveTask();
        });

        $('#saveColumnBtn').on('click', function() {
            self.saveColumn();
        });

        $('#updateColumnBtn').on('click', function() {
            self.updateColumn();
        });

        $('#updateTaskBtn').on('click', function() {
            self.updateTask();
        });
        
        // Handle parent task link clicks
        $(document).on('click', '.parent-task-link', function(e) {
            e.preventDefault();
            var parentId = $(this).data('parent-id');
            self.focusOnTask(parentId);
        });
    },

    initDragAndDrop: function() {
        var self = this;

        // Make tasks draggable using HTML5 drag and drop
        $('.kanban-task').each(function() {
            this.draggable = true;
            
            $(this).on('dragstart', function(e) {
                var taskElement = $(this);
                taskElement.addClass('dragging');
                
                // Store task data
                e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify({
                    taskId: taskElement.data('task-id'),
                    currentStatus: taskElement.data('status')
                }));
                
                e.originalEvent.dataTransfer.effectAllowed = 'move';
            });
            
            $(this).on('dragend', function(e) {
                $(this).removeClass('dragging');
            });
        });

        // Make columns accept drops
        $('.kanban-column-body').each(function() {
            var columnElement = $(this);
            
            this.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                columnElement.addClass('drag-over');
            });
            
            this.addEventListener('dragleave', function(e) {
                // Only remove class if we're actually leaving the column
                if (!columnElement[0].contains(e.relatedTarget)) {
                    columnElement.removeClass('drag-over');
                }
            });
            
            this.addEventListener('drop', function(e) {
                e.preventDefault();
                columnElement.removeClass('drag-over');
                
                try {
                    var data = JSON.parse(e.dataTransfer.getData('text/plain'));
                    var newStatus = columnElement.data('status');
                    var taskId = data.taskId;
                    var currentStatus = data.currentStatus;
                    
                    if (newStatus !== currentStatus) {
                        var taskElement = $('.kanban-task[data-task-id="' + taskId + '"]');
                        self.moveTask(taskElement, columnElement, newStatus);
                    }
                } catch (err) {
                    console.error('Error processing drop:', err);
                }
            });
        });
    },

    moveTask: function(taskElement, targetColumn, newStatus) {
        var self = this;
        var taskId = taskElement.data('task-id');

        // Show loading state
        taskElement.addClass('task-moving');

        $.ajax({
            url: this.config.updateTaskUrl,
            method: 'POST',
            data: {
                taskId: taskId,
                status: newStatus,
                _csrf: $('meta[name=csrf-token]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Move the task visually
                    taskElement.appendTo(targetColumn);
                    taskElement.data('status', newStatus);
                    taskElement.addClass('task-highlight');
                    
                    // Update task count in columns
                    self.updateTaskCounts();
                    
                    // Show success message
                    self.showNotification('Task moved successfully!', 'success');
                    
                    setTimeout(function() {
                        taskElement.removeClass('task-highlight');
                    }, 500);
                } else {
                    self.showNotification(response.message || 'Failed to move task', 'error');
                }
            },
            error: function() {
                self.showNotification('Error moving task', 'error');
            },
            complete: function() {
                taskElement.removeClass('task-moving');
            }
        });
    },

    updateTaskCounts: function() {
        $('.kanban-column').each(function() {
            var count = $(this).find('.kanban-task').length;
            $(this).find('.task-count').text(count);
        });
    },

    showTaskDetails: function(taskId) {
        // This would typically load task details via AJAX
        // For now, just show a placeholder modal
        $('#taskModal .modal-body').html('<p>Loading task details...</p>');
        $('#taskModal').modal('show');
        
        // TODO: Load actual task details via AJAX
    },

    editTask: function(taskId) {
        var self = this;
        
        console.log('Edit task called for ID:', taskId);
        console.log('Get task URL:', self.config.getTaskUrl);
        
        // Check if URLs are configured
        if (!self.config.getTaskUrl) {
            alert('Get task URL not configured. Please refresh the page.');
            return;
        }
        
        // Load task data
        $.ajax({
            url: self.config.getTaskUrl,
            method: 'GET',
            data: { id: taskId },
            success: function(response) {
                console.log('Get task response:', response);
                if (response.success && response.task) {
                    self.showEditTaskModal(response.task);
                } else {
                    self.showNotification(response.message || 'Failed to load task', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', xhr, status, error);
                self.showNotification('Error loading task', 'error');
            }
        });
    },

    deleteTask: function(taskId) {
        var self = this;
        
        if (confirm('Are you sure you want to delete this task?')) {
            $.ajax({
                url: self.config.deleteTaskUrl,
                method: 'POST',
                data: { id: taskId },
                success: function(response) {
                    if (response.success) {
                        // Remove task from DOM
                        $('.kanban-task[data-task-id="' + taskId + '"]').fadeOut(300, function() {
                            $(this).remove();
                            self.updateTaskCounts();
                        });
                        self.showNotification(response.message || 'Task deleted successfully', 'success');
                    } else {
                        self.showNotification(response.message || 'Failed to delete task', 'error');
                    }
                },
                error: function() {
                    self.showNotification('Error deleting task', 'error');
                }
            });
        }
    },

    showNotification: function(message, type) {
        // Create notification element
        var notification = $('<div class="kanban-notification ' + type + '">' + message + '</div>');
        
        // Add to page
        $('body').append(notification);
        
        // Show and hide after delay
        setTimeout(function() {
            notification.addClass('show');
        }, 100);
        
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    },

    showEditTaskModal: function(task) {
        // Populate form with task data
        $('#editTaskId').val(task.id);
        $('#editTaskTitle').val(task.title);
        $('#editTaskDescription').val(task.description);
        $('#editTaskCategory').val(task.category_id);
        $('#editTaskPriority').val(task.priority);
        $('#editTaskDeadline').val(task.deadline);
        $('#editTaskAssignee').val(task.assignee);
        // Explicitly handle the include_in_export value to ensure 0 is properly selected
        var exportValue = '1'; // default
        if (task.include_in_export !== undefined && task.include_in_export !== null) {
            exportValue = String(task.include_in_export);
        }
        $('#editTaskIncludeInExport').val(exportValue);
        
        // Set selected tags
        this.setSelectedTags(task.tag_ids);
        
        // Load parent tasks and set selected parent
        this.loadParentTasksForEdit(task.id);
        this.setSelectedParentTask(task.parent_task_id);
        
        // Show modal
        $('#editTaskModal').modal('show');
    },

    updateTask: function() {
        var self = this;
        var formData = $('#editTaskForm').serialize();
        
        $.ajax({
            url: self.config.editTaskUrl,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#editTaskModal').modal('hide');
                    self.showNotification(response.message || 'Task updated successfully', 'success');
                    self.refreshBoard();
                } else {
                    self.showNotification(response.message || 'Failed to update task', 'error');
                }
            },
            error: function() {
                self.showNotification('Error updating task', 'error');
            }
        });
    },

    refreshBoard: function() {
        // Reload the page or fetch updated data via AJAX
        location.reload();
    },

    showAddTaskModal: function(status) {
        $('#taskDefaultStatus').val(status);
        $('#taskStatus').val(status);
        $('#addTaskForm')[0].reset();
        $('#addTaskModal').modal('show');
    },

    saveTask: function() {
        var self = this;
        var formData = $('#addTaskForm').serialize();
        
        $.ajax({
            url: this.config.addTaskUrl,
            method: 'POST',
            data: formData + '&_csrf=' + $('meta[name=csrf-token]').attr('content'),
            success: function(response) {
                if (response.success) {
                    $('#addTaskModal').modal('hide');
                    self.showNotification('Task added successfully!', 'success');
                    self.refreshBoard();
                } else {
                    self.showNotification(response.message || 'Failed to add task', 'error');
                }
            },
            error: function() {
                self.showNotification('Error adding task', 'error');
            }
        });
    },

    showAddColumnModal: function() {
        $('#addColumnForm')[0].reset();
        // Set default values
        $('#columnDeletable').prop('checked', true);
        $('#addColumnModal').modal('show');
    },

    saveColumn: function() {
        var self = this;
        var formData = $('#addColumnForm').serialize();
        
        // Handle checkbox - if unchecked, add is_deletable=0
        if (!$('#columnDeletable').is(':checked')) {
            formData += '&is_deletable=0';
        }
        
        console.log('Add Column Form Data:', formData);
        
        $.ajax({
            url: this.config.addColumnUrl,
            method: 'POST',
            data: formData + '&_csrf=' + $('meta[name=csrf-token]').attr('content'),
            success: function(response) {
                if (response.success) {
                    $('#addColumnModal').modal('hide');
                    self.showNotification('Column added successfully!', 'success');
                    self.refreshBoard();
                } else {
                    self.showNotification(response.message || 'Failed to add column', 'error');
                }
            },
            error: function() {
                self.showNotification('Error adding column', 'error');
            }
        });
    },

    showEditColumnModal: function(columnId) {
        var self = this;
        var columnElement = $('.kanban-column[data-column-id="' + columnId + '"]');
        var columnName = columnElement.data('column-name');
        var columnColor = columnElement.data('column-color');
        var columnIcon = columnElement.data('column-icon');
        var isDeletable = columnElement.data('is-deletable');
        
        console.log('Edit Column Modal Data:', {
            columnId: columnId,
            columnName: columnName,
            columnColor: columnColor,
            columnIcon: columnIcon,
            isDeletable: isDeletable
        });
        
        $('#editColumnId').val(columnId);
        $('#editColumnName').val(columnName);
        $('#editColumnColor').val(columnColor);
        $('#editColumnIcon').val(columnIcon);
        $('#editColumnDeletable').prop('checked', isDeletable == 1);
        $('#editColumnModal').modal('show');
    },

    updateColumn: function() {
        var self = this;
        var formData = $('#editColumnForm').serialize();
        
        // Handle checkbox - if unchecked, add is_deletable=0
        if (!$('#editColumnDeletable').is(':checked')) {
            formData += '&is_deletable=0';
        }
        
        console.log('Edit Column Form Data:', formData);
        
        $.ajax({
            url: this.config.editColumnUrl,
            method: 'POST',
            data: formData + '&_csrf=' + $('meta[name=csrf-token]').attr('content'),
            success: function(response) {
                if (response.success) {
                    $('#editColumnModal').modal('hide');
                    self.showNotification('Column updated successfully!', 'success');
                    self.refreshBoard();
                } else {
                    self.showNotification(response.message || 'Failed to update column', 'error');
                }
            },
            error: function() {
                self.showNotification('Error updating column', 'error');
            }
        });
    },

    deleteColumn: function(columnId) {
        var self = this;
        
        if (!confirm('Are you sure you want to delete this column? This action cannot be undone.')) {
            return;
        }
        
        $.ajax({
            url: this.config.deleteColumnUrl,
            method: 'POST',
            data: {
                id: columnId,
                _csrf: $('meta[name=csrf-token]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    self.showNotification('Column deleted successfully!', 'success');
                    self.refreshBoard();
                } else {
                    self.showNotification(response.message || 'Failed to delete column', 'error');
                }
            },
            error: function() {
                self.showNotification('Error deleting column', 'error');
            }
        });
    },

    /**
     * Load available tags for task forms
     */
    loadTags: function() {
        var self = this;
        
        if (!this.config.getTagsUrl) {
            console.warn('getTagsUrl not configured');
            return;
        }
        
        $.ajax({
            url: this.config.getTagsUrl,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    self.populateTagSelects(response.tags);
                } else {
                    console.error('Failed to load tags:', response.message);
                }
            },
            error: function() {
                console.error('Error loading tags');
            }
        });
    },

    /**
     * Populate tag select elements with options
     */
    populateTagSelects: function(tags) {
        var tagOptions = '';
        
        $.each(tags, function(index, tag) {
            tagOptions += '<option value="' + tag.id + '" data-color="' + tag.color + '">' 
                        + $('<div>').text(tag.name).html() + '</option>';
        });
        
        // Populate both add and edit task tag selects
        $('#taskTags, #editTaskTags').html(tagOptions);
        
        // Store tags for later use
        this.availableTags = tags;
    },

    /**
     * Set selected tags in edit modal
     */
    setSelectedTags: function(tagIds) {
        $('#editTaskTags option').prop('selected', false);
        
        if (tagIds && tagIds.length > 0) {
            $.each(tagIds, function(index, tagId) {
                $('#editTaskTags option[value="' + tagId + '"]').prop('selected', true);
            });
        }
    },

    /**
     * Load available parent tasks for task forms
     */
    loadParentTasks: function() {
        var self = this;
        
        if (!this.config.getParentTasksUrl) {
            console.warn('getParentTasksUrl not configured');
            return;
        }
        
        $.ajax({
            url: this.config.getParentTasksUrl,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    self.populateParentTaskSelects(response.tasks);
                } else {
                    console.error('Failed to load parent tasks:', response.message);
                }
            },
            error: function() {
                console.error('Error loading parent tasks');
            }
        });
    },

    /**
     * Populate parent task select elements with options
     */
    populateParentTaskSelects: function(tasks) {
        var taskOptions = '<option value="">No Parent (Root Task)</option>';
        
        $.each(tasks, function(index, task) {
            taskOptions += '<option value="' + task.id + '" data-depth="' + task.depth + '">' 
                        + $('<div>').text(task.title).html() + '</option>';
        });
        
        // Populate both add and edit task parent selects
        $('#taskParent, #editTaskParent').html(taskOptions);
        
        // Store tasks for later use
        this.availableParentTasks = tasks;
    },

    /**
     * Load parent tasks for edit modal (excluding current task and its descendants)
     */
    loadParentTasksForEdit: function(currentTaskId) {
        var self = this;
        
        if (!this.config.getParentTasksUrl) {
            console.warn('getParentTasksUrl not configured');
            return;
        }
        
        $.ajax({
            url: this.config.getParentTasksUrl + '?exclude_task_id=' + currentTaskId,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var taskOptions = '<option value="">No Parent (Root Task)</option>';
                    
                    $.each(response.tasks, function(index, task) {
                        taskOptions += '<option value="' + task.id + '" data-depth="' + task.depth + '">' 
                                    + $('<div>').text(task.title).html() + '</option>';
                    });
                    
                    $('#editTaskParent').html(taskOptions);
                } else {
                    console.error('Failed to load parent tasks for edit:', response.message);
                }
            },
            error: function() {
                console.error('Error loading parent tasks for edit');
            }
        });
    },

    /**
     * Set selected parent task in edit modal
     */
    setSelectedParentTask: function(parentTaskId) {
        $('#editTaskParent').val(parentTaskId || '');
    },

    /**
     * Focus on a specific task by highlighting and scrolling to it
     */
    focusOnTask: function(taskId) {
        // Remove existing focus from all tasks
        $('.kanban-task').removeClass('task-focused');
        
        // Find and focus the target task
        var targetTask = $('.kanban-task[data-task-id="' + taskId + '"]');
        if (targetTask.length > 0) {
            // Add focus class
            targetTask.addClass('task-focused');
            
            // Scroll to the task
            $('html, body').animate({
                scrollTop: targetTask.offset().top - 100
            }, 500);
            
            // Remove focus after 3 seconds
            setTimeout(function() {
                targetTask.removeClass('task-focused');
            }, 3000);
        }
    }
};

// Add notification styles dynamically
$(document).ready(function() {
    var notificationStyles = `
        <style>
        .kanban-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 4px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        .kanban-notification.show {
            transform: translateX(0);
        }
        .kanban-notification.success {
            background-color: #28a745;
        }
        .kanban-notification.error {
            background-color: #dc3545;
        }
        .kanban-notification.info {
            background-color: #17a2b8;
        }
        .task-moving {
            opacity: 0.6;
            pointer-events: none;
        }
        .drag-helper {
            transform: rotate(3deg);
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
        }
        </style>
    `;
    
    $('head').append(notificationStyles);
});