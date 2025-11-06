/**
 * Kanban Board JavaScript - Updated Version
 * Updated: 2025-11-06 with edit/delete functionality
 */
console.log('Kanban-updated.js loaded - NEW VERSION with edit functionality');

var KanbanBoard = {
    config: {
        updateTaskUrl: '',
        updatePositionUrl: '',
        addTaskUrl: '',
        addColumnUrl: '',
        editColumnUrl: '',
        deleteColumnUrl: '',
        getTaskUrl: '',
        getTaskDetailsUrl: '',
        editTaskUrl: '',
        deleteTaskUrl: '',
        csrfToken: '',
        csrfParam: '_csrf'
    },

    init: function(options) {
        this.config = $.extend(this.config, options);
        console.log('Kanban config initialized (NEW VERSION):', this.config);
        this.bindEvents();
        this.initDragAndDrop();
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
            console.log('NEW VERSION: Edit button clicked for task', taskId);
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

        // Task details modal buttons
        $('#editTaskFromDetails').on('click', function() {
            var taskId = $(this).data('task-id');
            $('#taskDetailsModal').modal('hide');
            self.editTask(taskId);
        });

        $('#deleteTaskFromDetails').on('click', function() {
            var taskId = $(this).data('task-id');
            $('#taskDetailsModal').modal('hide');
            self.deleteTask(taskId);
        });
    },

    initDragAndDrop: function() {
        var self = this;

        // Make tasks draggable using HTML5 drag and drop
        $('.kanban-task').each(function() {
            this.draggable = true;
            
            $(this).on('dragstart', function(e) {
                var taskElement = $(this);
                taskElement.addClass('task-dragging');
                
                // Store task data
                var taskData = {
                    id: taskElement.data('task-id'),
                    fromColumn: taskElement.closest('.kanban-column').data('status')
                };
                
                e.originalEvent.dataTransfer.setData('text/json', JSON.stringify(taskData));
                e.originalEvent.dataTransfer.effectAllowed = 'move';
            });

            $(this).on('dragend', function(e) {
                $(this).removeClass('task-dragging');
            });
        });

        // Make columns droppable
        $('.kanban-column').each(function() {
            var columnElement = $(this);
            
            columnElement.on('dragover', function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'move';
                $(this).addClass('column-drag-over');
            });

            columnElement.on('dragleave', function(e) {
                $(this).removeClass('column-drag-over');
            });

            columnElement.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('column-drag-over');
                
                var taskData = JSON.parse(e.originalEvent.dataTransfer.getData('text/json'));
                var newStatus = $(this).data('status');
                
                if (taskData.fromColumn !== newStatus) {
                    self.moveTask(taskData.id, newStatus);
                }
            });
        });
    },

    moveTask: function(taskId, newStatus) {
        var self = this;
        var taskElement = $('.kanban-task[data-task-id="' + taskId + '"]');
        
        // Add moving state
        taskElement.addClass('task-moving');

        var ajaxData = {
            taskId: taskId,
            status: newStatus
        };
        if (self.config.csrfParam && self.config.csrfToken) {
            ajaxData[self.config.csrfParam] = self.config.csrfToken;
        }
        
        $.ajax({
            url: self.config.updateTaskUrl,
            method: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    // Move task to new column
                    var targetColumn = $('.kanban-column[data-status="' + newStatus + '"] .kanban-tasks');
                    taskElement.appendTo(targetColumn);
                    
                    // Update task counts
                    self.updateTaskCounts();
                    
                    self.showNotification('Task moved successfully', 'success');
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
        var self = this;
        
        console.log('Loading task details for ID:', taskId);
        
        // Show loading state
        $('#taskDetailsContent').html(`
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading task details...</p>
            </div>
        `);
        
        // Hide action buttons initially
        $('#editTaskFromDetails, #deleteTaskFromDetails').hide();
        
        // Show modal
        $('#taskDetailsModal').modal('show');
        
        // Load task details
        var ajaxData = { id: taskId };
        if (self.config.csrfParam && self.config.csrfToken) {
            ajaxData[self.config.csrfParam] = self.config.csrfToken;
        }
        
        $.ajax({
            url: self.config.getTaskDetailsUrl,
            method: 'GET',
            data: ajaxData,
            success: function(response) {
                if (response.success && response.task) {
                    self.renderTaskDetails(response.task);
                    
                    // Show action buttons
                    $('#editTaskFromDetails, #deleteTaskFromDetails').show().data('task-id', taskId);
                } else {
                    self.showTaskDetailsError(response.message || 'Failed to load task details');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error loading task details:', xhr, status, error);
                self.showTaskDetailsError('Error loading task details');
            }
        });
    },

    renderTaskDetails: function(task) {
        var self = this;
        
        // Determine priority and status badge classes
        var priorityClass = {
            'low': 'badge-success',
            'medium': 'badge-warning', 
            'high': 'badge-danger',
            'critical': 'badge-dark'
        }[task.priority] || 'badge-secondary';
        
        var statusClass = {
            'pending': 'badge-secondary',
            'in_progress': 'badge-primary',
            'completed': 'badge-success',
            'cancelled': 'badge-danger'
        }[task.status] || 'badge-secondary';
        
        // Build images section
        var imagesHtml = '';
        if (task.images && task.images.length > 0) {
            imagesHtml = '<div class="task-images mt-3"><h6>Attachments:</h6><div class="row">';
            task.images.forEach(function(image) {
                imagesHtml += `
                    <div class="col-md-3 mb-2">
                        <div class="card">
                            <img src="${image.url}" class="card-img-top" alt="${image.name}" style="height: 100px; object-fit: cover;">
                            <div class="card-body p-2">
                                <small class="card-text">${image.name}</small>
                                <br><small class="text-muted">${image.size}</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            imagesHtml += '</div></div>';
        }
        
        // Build deadline section with color coding
        var deadlineHtml = '';
        if (task.deadline_timestamp) {
            var deadlineClass = task.is_overdue ? 'text-danger' : (task.days_until_deadline <= 3 ? 'text-warning' : 'text-info');
            var deadlineIcon = task.is_overdue ? 'fa-exclamation-triangle' : 'fa-calendar';
            deadlineHtml = `
                <div class="deadline-info ${deadlineClass}">
                    <i class="fa ${deadlineIcon}"></i> 
                    ${task.deadline}
                    ${task.is_overdue ? ' (OVERDUE)' : (task.days_until_deadline !== null ? ` (${task.days_until_deadline} days)` : '')}
                </div>
            `;
        } else {
            deadlineHtml = '<span class="text-muted">No deadline set</span>';
        }
        
        // Build category section
        var categoryHtml = '';
        if (task.category) {
            var categoryStyle = task.category.color ? `background-color: ${task.category.color}20; border-left: 4px solid ${task.category.color};` : '';
            categoryHtml = `
                <div class="category-info p-2 mb-3" style="${categoryStyle}">
                    <i class="${task.category.icon || 'fa-folder'}"></i>
                    <strong>${task.category.name}</strong>
                    ${task.category.description ? `<br><small class="text-muted">${task.category.description}</small>` : ''}
                </div>
            `;
        }
        
        var html = `
            <div class="task-details">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="task-title">${task.title}</h5>
                        <div class="task-badges mb-3">
                            <span class="badge ${priorityClass}">${task.priority_label} Priority</span>
                            <span class="badge ${statusClass}">${task.status_label}</span>
                        </div>
                        
                        ${categoryHtml}
                        
                        <div class="task-description">
                            <h6>Description:</h6>
                            <div class="description-content">${task.description || '<em class="text-muted">No description provided</em>'}</div>
                        </div>
                        
                        ${imagesHtml}
                    </div>
                    
                    <div class="col-md-4">
                        <div class="task-meta">
                            <h6>Task Information</h6>
                            
                            <div class="meta-item mb-2">
                                <strong>Deadline:</strong><br>
                                ${deadlineHtml}
                            </div>
                            
                            <div class="meta-item mb-2">
                                <strong>Created:</strong><br>
                                <small class="text-muted">${task.created_at}</small>
                            </div>
                            
                            <div class="meta-item mb-2">
                                <strong>Last Updated:</strong><br>
                                <small class="text-muted">${task.updated_at}</small>
                            </div>
                            
                            ${task.completed_at ? `
                                <div class="meta-item mb-2">
                                    <strong>Completed:</strong><br>
                                    <small class="text-success">${task.completed_at}</small>
                                </div>
                            ` : ''}
                            
                            ${task.assigned_to ? `
                                <div class="meta-item mb-2">
                                    <strong>Assigned To:</strong><br>
                                    <small>${task.assigned_to}</small>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#taskDetailsContent').html(html);
        $('#taskDetailsModalLabel').text(task.title);
    },

    showTaskDetailsError: function(message) {
        $('#taskDetailsContent').html(`
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>Error:</strong> ${message}
            </div>
        `);
        $('#editTaskFromDetails, #deleteTaskFromDetails').hide();
    },

    editTask: function(taskId) {
        var self = this;
        
        console.log('NEW VERSION: Edit task called for ID:', taskId);
        console.log('NEW VERSION: Get task URL:', self.config.getTaskUrl);
        
        // Check if URLs are configured
        if (!self.config.getTaskUrl) {
            alert('NEW VERSION: Get task URL not configured. Please refresh the page.');
            return;
        }
        
        // Load task data
        var ajaxData = { id: taskId };
        if (self.config.csrfParam && self.config.csrfToken) {
            ajaxData[self.config.csrfParam] = self.config.csrfToken;
        }
        
        $.ajax({
            url: self.config.getTaskUrl,
            method: 'GET',
            data: ajaxData,
            success: function(response) {
                console.log('NEW VERSION: Get task response:', response);
                if (response.success && response.task) {
                    self.showEditTaskModal(response.task);
                } else {
                    self.showNotification(response.message || 'Failed to load task', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.log('NEW VERSION: AJAX error:', xhr, status, error);
                self.showNotification('Error loading task', 'error');
            }
        });
    },

    deleteTask: function(taskId) {
        var self = this;
        
        if (confirm('Are you sure you want to delete this task?')) {
            var ajaxData = { id: taskId };
            if (self.config.csrfParam && self.config.csrfToken) {
                ajaxData[self.config.csrfParam] = self.config.csrfToken;
            }
            
            $.ajax({
                url: self.config.deleteTaskUrl,
                method: 'POST',
                data: ajaxData,
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
        console.log('NEW VERSION: Showing edit modal for task:', task);
        // Populate form with task data
        $('#editTaskId').val(task.id);
        $('#editTaskTitle').val(task.title);
        $('#editTaskDescription').val(task.description);
        $('#editTaskCategory').val(task.category_id);
        $('#editTaskPriority').val(task.priority);
        $('#editTaskDeadline').val(task.deadline);
        $('#editTaskAssignedTo').val(task.assigned_to);
        
        // Show modal
        $('#editTaskModal').modal('show');
    },

    updateTask: function() {
        var self = this;
        var formData = $('#editTaskForm').serialize();
        
        // Add CSRF token to form data
        if (self.config.csrfParam && self.config.csrfToken) {
            formData += '&' + self.config.csrfParam + '=' + encodeURIComponent(self.config.csrfToken);
        }
        
        console.log('NEW VERSION: Updating task with data:', formData);
        
        $.ajax({
            url: self.config.editTaskUrl,
            method: 'POST',
            data: formData,
            success: function(response) {
                console.log('NEW VERSION: Update response:', response);
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
        
        // Add CSRF token to form data
        if (self.config.csrfParam && self.config.csrfToken) {
            formData += '&' + self.config.csrfParam + '=' + encodeURIComponent(self.config.csrfToken);
        }
        
        $.ajax({
            url: self.config.addTaskUrl,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#addTaskModal').modal('hide');
                    self.showNotification(response.message || 'Task added successfully', 'success');
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
        $('#addColumnModal').modal('show');
    },

    saveColumn: function() {
        var self = this;
        var formData = $('#addColumnForm').serialize();
        
        // Add CSRF token to form data
        if (self.config.csrfParam && self.config.csrfToken) {
            formData += '&' + self.config.csrfParam + '=' + encodeURIComponent(self.config.csrfToken);
        }
        
        $.ajax({
            url: self.config.addColumnUrl,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#addColumnModal').modal('hide');
                    self.showNotification(response.message || 'Column added successfully', 'success');
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
        
        // Find column data from DOM
        var columnElement = $('.kanban-column[data-column-id="' + columnId + '"]');
        var columnName = columnElement.find('.column-title').text();
        var columnColor = columnElement.find('.kanban-column-header').css('border-bottom-color');
        
        // Populate form
        $('#editColumnId').val(columnId);
        $('#editColumnName').val(columnName);
        $('#editColumnColor').val(columnColor);
        
        // Show modal
        $('#editColumnModal').modal('show');
    },

    updateColumn: function() {
        var self = this;
        var formData = $('#editColumnForm').serialize();
        
        // Add CSRF token to form data
        if (self.config.csrfParam && self.config.csrfToken) {
            formData += '&' + self.config.csrfParam + '=' + encodeURIComponent(self.config.csrfToken);
        }
        
        $.ajax({
            url: self.config.editColumnUrl,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#editColumnModal').modal('hide');
                    self.showNotification(response.message || 'Column updated successfully', 'success');
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
        
        if (confirm('Are you sure you want to delete this column? All tasks in this column will need to be moved first.')) {
            var ajaxData = { id: columnId };
            if (self.config.csrfParam && self.config.csrfToken) {
                ajaxData[self.config.csrfParam] = self.config.csrfToken;
            }
            
            $.ajax({
                url: self.config.deleteColumnUrl,
                method: 'POST',
                data: ajaxData,
                success: function(response) {
                    if (response.success) {
                        self.showNotification(response.message || 'Column deleted successfully', 'success');
                        self.refreshBoard();
                    } else {
                        self.showNotification(response.message || 'Failed to delete column', 'error');
                    }
                },
                error: function() {
                    self.showNotification('Error deleting column', 'error');
                }
            });
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
            z-index: 10000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }
        
        .kanban-notification.show {
            opacity: 1;
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
        </style>
    `;
    
    $('head').append(notificationStyles);
});