/**
 * Kanban Board JavaScript - Updated Version
 * Updated: 2025-11-06 with edit/delete functionality
 */
console.log('Kanban-updated.js loaded - NEW VERSION with edit functionality');

var KanbanBoard = {
    config: {
        updateTaskUrl: '',
        updatePositionUrl: '',
        updateColumnPositionUrl: '',
        addTaskUrl: '',
        addColumnUrl: '',
        editColumnUrl: '',
        deleteColumnUrl: '',
        getTaskUrl: '',
        getTaskDetailsUrl: '',
        getTaskHistoryUrl: '',
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
        this.initColumnDragAndDrop();
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

        // History button
        $(document).on('click', '.btn-history', function(e) {
            e.stopPropagation();
            var taskId = $(this).data('task-id');
            console.log('History button clicked for task', taskId);
            self.showTaskHistory(taskId);
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
                    fromColumn: taskElement.closest('.kanban-column-body').data('status')
                };
                
                e.originalEvent.dataTransfer.setData('text/json', JSON.stringify(taskData));
                e.originalEvent.dataTransfer.effectAllowed = 'move';
            });

            $(this).on('dragend', function(e) {
                $(this).removeClass('task-dragging');
                self.clearDropIndicator();
                $('.column-drag-over').removeClass('column-drag-over');
            });
        });

        // Make columns droppable with positional support
        $('.kanban-column').each(function() {
            var columnElement = $(this);
            
            columnElement.on('dragover', function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'move';
                $(this).addClass('column-drag-over');
                
                // Find drop position and show insertion indicator
                self.updateDropIndicator(e, $(this));
            });

            columnElement.on('dragleave', function(e) {
                // Only remove drag-over if we're actually leaving the column
                var rect = this.getBoundingClientRect();
                var x = e.originalEvent.clientX;
                var y = e.originalEvent.clientY;
                
                if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
                    $(this).removeClass('column-drag-over');
                    self.clearDropIndicator();
                }
            });

            columnElement.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('column-drag-over');
                
                // Check if this is a column being dragged (not a task)
                var columnId = e.originalEvent.dataTransfer.getData('text/column-id');
                if (columnId) {
                    // This is handled by the column drag-and-drop system
                    return;
                }
                
                // This is a task drop
                var taskDataString = e.originalEvent.dataTransfer.getData('text/json');
                if (!taskDataString) {
                    console.log('No task data found in drop event');
                    return;
                }
                
                var taskData = JSON.parse(taskDataString);
                var newStatus = $(this).data('status');
                
                // Calculate drop position
                var dropInfo = self.calculateDropPosition(e, $(this));
                
                console.log('Drop event - Task:', taskData.id, 'From:', taskData.fromColumn, 'To:', newStatus, 'Position:', dropInfo);
                
                self.clearDropIndicator();
                
                // Always move if there's a status change or position change
                if (taskData.fromColumn !== newStatus || (dropInfo && dropInfo.position !== null)) {
                    self.moveTask(taskData.id, newStatus, dropInfo);
                }
            });
        });
    },

    updateDropIndicator: function(e, columnElement) {
        this.clearDropIndicator();
        
        var columnBody = columnElement.find('.kanban-column-body');
        var tasks = columnBody.find('.kanban-task:not(.task-dragging)');
        var mouseY = e.originalEvent.clientY;
        
        var insertBefore = null;
        var minDistance = Infinity;
        
        tasks.each(function() {
            var taskRect = this.getBoundingClientRect();
            var taskMiddle = taskRect.top + (taskRect.height / 2);
            var distance = Math.abs(mouseY - taskMiddle);
            
            if (mouseY < taskMiddle && distance < minDistance) {
                minDistance = distance;
                insertBefore = $(this);
            }
        });
        
        // Create and show drop indicator
        var indicator = $('<div class="drop-indicator"></div>');
        
        if (insertBefore && insertBefore.length) {
            indicator.insertBefore(insertBefore);
        } else {
            // Insert at the end, but before empty-column message if it exists
            var emptyColumn = columnBody.find('.empty-column');
            if (emptyColumn.length) {
                indicator.insertBefore(emptyColumn);
            } else {
                columnBody.append(indicator);
            }
        }
    },

    clearDropIndicator: function() {
        $('.drop-indicator').remove();
    },

    calculateDropPosition: function(e, columnElement) {
        var columnBody = columnElement.find('.kanban-column-body');
        var tasks = columnBody.find('.kanban-task:not(.task-dragging)');
        var mouseY = e.originalEvent.clientY;
        
        var insertBefore = null;
        var position = 0;
        var minDistance = Infinity;
        
        // If there are no tasks, position is 0
        if (tasks.length === 0) {
            return {
                insertBefore: null,
                position: 0,
                totalTasks: 0
            };
        }
        
        // Find the task we should insert before
        tasks.each(function(index) {
            var taskRect = this.getBoundingClientRect();
            var taskMiddle = taskRect.top + (taskRect.height / 2);
            
            if (mouseY < taskMiddle) {
                insertBefore = $(this);
                position = index;
                return false; // Break the loop
            }
        });
        
        // If no insertBefore found, position at the end
        if (!insertBefore) {
            position = tasks.length;
        }
        
        return {
            insertBefore: insertBefore,
            position: position,
            totalTasks: tasks.length
        };
    },

    moveTask: function(taskId, newStatus, dropInfo) {
        var self = this;
        var taskElement = $('.kanban-task[data-task-id="' + taskId + '"]');
        
        console.log('Moving task:', taskId, 'to status:', newStatus, 'Drop info:', dropInfo);
        console.log('Task element found:', taskElement.length > 0);
        
        if (taskElement.length === 0) {
            console.error('Task element not found for ID:', taskId);
            return;
        }
        
        // Add moving state
        taskElement.addClass('task-moving');
        
        // Prepare AJAX data for position update
        var ajaxData = {
            taskId: taskId,
            status: newStatus,
            position: dropInfo && dropInfo.position !== null ? dropInfo.position : 0
        };
        if (self.config.csrfParam && self.config.csrfToken) {
            ajaxData[self.config.csrfParam] = self.config.csrfToken;
        }
        
        console.log('Sending position update:', ajaxData);
        console.log('URL:', self.config.updatePositionUrl);
        
        $.ajax({
            url: self.config.updatePositionUrl,
            method: 'POST',
            data: ajaxData,
            success: function(response) {
                console.log('AJAX response:', response);
                console.log('Task position after update should be:', ajaxData.position);
                
                if (response.success) {
                    // Move task element to correct position
                    self.moveTaskElement(taskElement, newStatus, dropInfo);
                    
                    self.showNotification('Task moved successfully', 'success');
                } else {
                    console.error('Move task failed:', response.message);
                    self.showNotification(response.message || 'Failed to move task', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                self.showNotification('Error moving task', 'error');
            },
            complete: function() {
                taskElement.removeClass('task-moving');
            }
        });
    },

    moveTaskElement: function(taskElement, newStatus, dropInfo) {
        // Get source column before moving the task
        var sourceColumn = taskElement.closest('.kanban-column-body');
        
        // Move task to new column
        var targetColumn = $('.kanban-column-body[data-status="' + newStatus + '"]');
        
        if (targetColumn.length === 0) {
            console.error('Target column not found for status:', newStatus);
            this.showNotification('Target column not found', 'error');
            return false;
        }
        
        // Remove empty column message from target if it exists
        targetColumn.find('.empty-column').remove();
        
        // Update task data-status attribute
        taskElement.attr('data-status', newStatus);
        
        // Insert task at the correct position
        if (dropInfo && dropInfo.insertBefore && dropInfo.insertBefore.length) {
            taskElement.insertBefore(dropInfo.insertBefore);
        } else {
            // Insert at the end
            targetColumn.append(taskElement);
        }
        
        console.log('Task moved to target column at position');
        
        // Check if source column is now empty and add empty message
        if (sourceColumn.find('.kanban-task').length === 0) {
            sourceColumn.append('<div class="empty-column"><p>No tasks in this column</p></div>');
        }
        
        // Update task counts
        this.updateTaskCounts();
        
        return true;
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
        $('#taskDetailsContent').html(
            '<div class="text-center">' +
                '<div class="spinner-border" role="status">' +
                    '<span class="sr-only">Loading...</span>' +
                '</div>' +
                '<p class="mt-2">Loading task details...</p>' +
            '</div>'
        );
        
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
                imagesHtml += 
                    '<div class="col-md-3 mb-2">' +
                        '<div class="card">' +
                            '<img src="' + image.url + '" class="card-img-top" alt="' + image.name + '" style="height: 100px; object-fit: cover;">' +
                            '<div class="card-body p-2">' +
                                '<small class="card-text">' + image.name + '</small>' +
                                '<br><small class="text-muted">' + image.size + '</small>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
            });
            imagesHtml += '</div></div>';
        }
        
        // Build deadline section with color coding
        var deadlineHtml = '';
        if (task.deadline_timestamp) {
            var deadlineClass = task.is_overdue ? 'text-danger' : (task.days_until_deadline <= 3 ? 'text-warning' : 'text-info');
            var deadlineIcon = task.is_overdue ? 'fa-exclamation-triangle' : 'fa-calendar';
            var deadlineExtra = '';
            if (task.is_overdue) {
                deadlineExtra = ' (OVERDUE)';
            } else if (task.days_until_deadline !== null) {
                deadlineExtra = ' (' + task.days_until_deadline + ' days)';
            }
            
            deadlineHtml = 
                '<div class="deadline-info ' + deadlineClass + '">' +
                    '<i class="fa ' + deadlineIcon + '"></i> ' +
                    task.deadline + deadlineExtra +
                '</div>';
        } else {
            deadlineHtml = '<span class="text-muted">No deadline set</span>';
        }
        
        // Build category section
        var categoryHtml = '';
        if (task.category) {
            var categoryStyle = task.category.color ? 'background-color: ' + task.category.color + '20; border-left: 4px solid ' + task.category.color + ';' : '';
            categoryHtml = 
                '<div class="category-info p-2 mb-3" style="' + categoryStyle + '">' +
                    '<i class="' + (task.category.icon || 'fa-folder') + '"></i>' +
                    '<strong>' + task.category.name + '</strong>' +
                    (task.category.description ? '<br><small class="text-muted">' + task.category.description + '</small>' : '') +
                '</div>';
        }
        
        var html = 
            '<div class="task-details">' +
                '<div class="row">' +
                    '<div class="col-md-8">' +
                        '<h5 class="task-title">' + task.title + '</h5>' +
                        '<div class="task-badges mb-3">' +
                            '<span class="badge ' + priorityClass + '">' + task.priority_label + ' Priority</span>' +
                            '<span class="badge ' + statusClass + '">' + task.status_label + '</span>' +
                        '</div>' +
                        
                        categoryHtml +
                        
                        '<div class="task-description">' +
                            '<h6>Description:</h6>' +
                            '<div class="description-content">' + self.formatDescription(task.description) + '</div>' +
                        '</div>' +
                        
                        imagesHtml +
                    '</div>' +
                    
                    '<div class="col-md-4">' +
                        '<div class="task-meta">' +
                            '<h6>Task Information</h6>' +
                            
                            '<div class="meta-item mb-2">' +
                                '<strong>Deadline:</strong><br>' +
                                deadlineHtml +
                            '</div>' +
                            
                            '<div class="meta-item mb-2">' +
                                '<strong>Created:</strong><br>' +
                                '<small class="text-muted">' + task.created_at + '</small>' +
                            '</div>' +
                            
                            '<div class="meta-item mb-2">' +
                                '<strong>Last Updated:</strong><br>' +
                                '<small class="text-muted">' + task.updated_at + '</small>' +
                            '</div>' +
                            
                            (task.completed_at ? 
                                '<div class="meta-item mb-2">' +
                                    '<strong>Completed:</strong><br>' +
                                    '<small class="text-success">' + task.completed_at + '</small>' +
                                '</div>'
                            : '') +
                            
                            (task.assigned_to ? 
                                '<div class="meta-item mb-2">' +
                                    '<strong>Assigned To:</strong><br>' +
                                    '<small>' + task.assigned_to + '</small>' +
                                '</div>'
                            : '') +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        
        $('#taskDetailsContent').html(html);
        $('#taskDetailsModalLabel').text(task.title);
    },

    showTaskDetailsError: function(message) {
        $('#taskDetailsContent').html(
            '<div class="alert alert-danger">' +
                '<i class="fa fa-exclamation-triangle"></i>' +
                '<strong>Error:</strong> ' + message +
            '</div>'
        );
        $('#editTaskFromDetails, #deleteTaskFromDetails').hide();
    },

    formatDescription: function(description) {
        if (!description) {
            return '<em class="text-muted">No description provided</em>';
        }
        
        // Convert URLs to clickable links
        var formattedDescription = description.replace(
            /(https?:\/\/[^\s]+)/g, 
            '<a href="$1" target="_blank" rel="noopener" class="kanban-modal-url">$1</a>'
        );
        
        return formattedDescription;
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
        // Explicitly handle the include_in_export value to ensure 0 is properly selected
        var exportValue = '1'; // default
        if (task.include_in_export !== undefined && task.include_in_export !== null) {
            exportValue = String(task.include_in_export);
        }
        $('#editTaskIncludeInExport').val(exportValue);
        
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
    },

    /**
     * Initialize column drag and drop functionality
     */
    initColumnDragAndDrop: function() {
        var self = this;
        
        // Make column headers draggable
        $('.kanban-column-header').each(function() {
            $(this).on('dragstart', function(e) {
                var column = $(this).closest('.kanban-column');
                var columnId = column.data('column-id');
                
                console.log('Column drag started:', columnId);
                
                e.originalEvent.dataTransfer.setData('text/column-id', columnId);
                e.originalEvent.dataTransfer.effectAllowed = 'move';
                
                // Add dragging class
                column.addClass('column-dragging');
                
                // Add drag indicator styles
                $('.kanban-column').not(column).addClass('column-drop-target');
            });
            
            $(this).on('dragend', function(e) {
                console.log('Column drag ended');
                
                // Remove all drag-related classes
                $('.kanban-column').removeClass('column-dragging column-drop-target column-drag-over');
                $('.column-drop-indicator').remove();
            });
        });
        
        // Make board container a drop target for columns
        $('.kanban-board-container').on('dragover', function(e) {
            var draggedColumnId = e.originalEvent.dataTransfer.getData('text/column-id');
            if (!draggedColumnId) return; // Not a column drag
            
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            
            // Find the column being hovered over
            var target = $(e.target).closest('.kanban-column');
            if (target.length && target.data('column-id') != draggedColumnId) {
                // Calculate drop position
                var dropInfo = self.calculateColumnDropPosition(e.originalEvent, target);
                self.updateColumnDropIndicator(target, dropInfo);
                
                $('.kanban-column').removeClass('column-drag-over');
                target.addClass('column-drag-over');
            }
        });
        
        $('.kanban-board-container').on('dragleave', function(e) {
            // Only clear if leaving the board container entirely
            if (!$(e.relatedTarget).closest('.kanban-board-container').length) {
                $('.kanban-column').removeClass('column-drag-over');
                $('.column-drop-indicator').remove();
            }
        });
        
        $('.kanban-board-container').on('drop', function(e) {
            var draggedColumnId = e.originalEvent.dataTransfer.getData('text/column-id');
            if (!draggedColumnId) return; // Not a column drop
            
            e.preventDefault();
            
            var target = $(e.target).closest('.kanban-column');
            if (target.length && target.data('column-id') != draggedColumnId) {
                console.log('Column dropped:', draggedColumnId, 'on', target.data('column-id'));
                
                var dropInfo = self.calculateColumnDropPosition(e.originalEvent, target);
                self.moveColumn(draggedColumnId, target.data('column-id'), dropInfo);
            }
            
            // Clean up
            $('.kanban-column').removeClass('column-dragging column-drop-target column-drag-over');
            $('.column-drop-indicator').remove();
        });
    },
    
    /**
     * Calculate column drop position
     */
    calculateColumnDropPosition: function(event, targetColumn) {
        var rect = targetColumn[0].getBoundingClientRect();
        var x = event.clientX - rect.left;
        var columnWidth = rect.width;
        
        // Determine if dropping before or after the target column
        var position = x < columnWidth / 2 ? 'before' : 'after';
        
        return {
            position: position,
            targetColumnId: targetColumn.data('column-id')
        };
    },
    
    /**
     * Update column drop indicator
     */
    updateColumnDropIndicator: function(targetColumn, dropInfo) {
        // Remove existing indicators
        $('.column-drop-indicator').remove();
        
        // Create drop indicator
        var indicator = $('<div class="column-drop-indicator"></div>');
        
        if (dropInfo.position === 'before') {
            targetColumn.before(indicator);
        } else {
            targetColumn.after(indicator);
        }
    },
    
    /**
     * Move column to new position
     */
    moveColumn: function(draggedColumnId, targetColumnId, dropInfo) {
        var self = this;
        
        console.log('Moving column:', draggedColumnId, 'relative to', targetColumnId, dropInfo);
        
        // Calculate new position
        var targetColumn = $('.kanban-column[data-column-id="' + targetColumnId + '"]');
        var targetPosition = $('.kanban-column').index(targetColumn);
        
        var newPosition = dropInfo.position === 'before' ? targetPosition : targetPosition + 1;
        
        // Adjust position if dragged column is before target
        var draggedColumn = $('.kanban-column[data-column-id="' + draggedColumnId + '"]');
        var draggedPosition = $('.kanban-column').index(draggedColumn);
        
        if (draggedPosition < targetPosition) {
            newPosition--;
        }
        
        console.log('New position for column:', newPosition);
        
        // Send AJAX request
        var ajaxData = {
            columnId: draggedColumnId,
            position: newPosition
        };
        if (self.config.csrfParam && self.config.csrfToken) {
            ajaxData[self.config.csrfParam] = self.config.csrfToken;
        }
        
        console.log('Sending column position update:', ajaxData);
        
        $.ajax({
            url: self.config.updateColumnPositionUrl,
            method: 'POST',
            data: ajaxData,
            success: function(response) {
                console.log('Column position update response:', response);
                
                if (response.success) {
                    // Move column element to correct position
                    self.moveColumnElement(draggedColumn, newPosition);
                    self.showNotification('Column moved successfully', 'success');
                } else {
                    console.error('Move column failed:', response.message);
                    self.showNotification(response.message || 'Failed to move column', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Column AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                self.showNotification('Error moving column', 'error');
            }
        });
    },
    
    /**
     * Move column element in DOM
     */
    moveColumnElement: function(columnElement, newPosition) {
        var allColumns = $('.kanban-column');
        
        if (newPosition === 0) {
            // Move to first position
            $('.kanban-board-container').prepend(columnElement);
        } else if (newPosition >= allColumns.length - 1) {
            // Move to last position
            $('.kanban-board-container').append(columnElement);
        } else {
            // Move to specific position
            columnElement.insertAfter(allColumns.eq(newPosition - 1));
        }
        
        console.log('Column moved in DOM to position:', newPosition);
    },

    /**
     * Show task history modal
     */
    showTaskHistory: function(taskId) {
        var self = this;
        
        console.log('Showing history for task:', taskId);
        
        // Show modal with loading state
        $('#taskHistoryModal').modal('show');
        $('#taskHistoryContent').html('<div class="text-center"><i class="fa fa-spin fa-spinner"></i> Loading history...</div>');
        
        // Fetch task history
        $.ajax({
            url: self.config.getTaskHistoryUrl,
            method: 'GET',
            data: { taskId: taskId },
            success: function(response) {
                if (response.success) {
                    self.renderTaskHistory(response.task, response.history);
                } else {
                    $('#taskHistoryContent').html('<div class="alert alert-error">Error: ' + response.message + '</div>');
                }
            },
            error: function() {
                $('#taskHistoryContent').html('<div class="alert alert-error">Failed to load task history.</div>');
            }
        });
    },

    /**
     * Render task history
     */
    renderTaskHistory: function(task, history) {
        var html = '';
        
        if (history.length === 0) {
            html = '<div class="empty-history"><i class="fa fa-history"></i><h5>No History Available</h5><p>This task has no recorded history yet.</p></div>';
        } else {
            html += '<div class="mb-3"><h6><i class="fa fa-tasks"></i> ' + task.title + '</h6></div>';
            html += '<div class="task-history-timeline">';
            
            history.forEach(function(entry) {
                html += '<div class="task-history-item ' + entry.action_type + '">';
                html += '<div class="task-history-icon ' + entry.action_type + '"><i class="' + entry.icon + '"></i></div>';
                html += '<div class="task-history-content">';
                html += '<div class="task-history-action">' + entry.action_label + '</div>';
                html += '<div class="task-history-description">' + entry.description + '</div>';
                
                // Show field changes if available
                if (entry.field_name && entry.old_value && entry.new_value) {
                    html += '<div class="task-history-changes">';
                    html += '<div class="task-history-change">';
                    html += '<span class="task-history-change-label">' + entry.field_name + ':</span>';
                    html += '<span><span class="task-history-change-old">' + entry.old_value + '</span> → <span class="task-history-change-new">' + entry.new_value + '</span></span>';
                    html += '</div>';
                    html += '</div>';
                }
                
                html += '<div class="task-history-meta">';
                html += '<span class="task-history-user"><i class="fa fa-user"></i> ' + entry.user_name + '</span>';
                html += '<span class="task-history-time">' + entry.relative_time + '</span>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';
        }
        
        $('#taskHistoryContent').html(html);
        $('#taskHistoryModalLabel').html('<i class="fa fa-history"></i> Task History (' + history.length + ' entries)');
    }
};

// Add notification styles dynamically
$(document).ready(function() {
    var notificationStyles = '<style>' +
        '.kanban-notification {' +
            'position: fixed;' +
            'top: 20px;' +
            'right: 20px;' +
            'padding: 12px 20px;' +
            'border-radius: 4px;' +
            'color: white;' +
            'font-weight: 500;' +
            'z-index: 10000;' +
            'opacity: 0;' +
            'transform: translateX(100%);' +
            'transition: all 0.3s ease;' +
        '}' +
        '.kanban-notification.show {' +
            'opacity: 1;' +
            'transform: translateX(0);' +
        '}' +
        '.kanban-notification.success {' +
            'background-color: #28a745;' +
        '}' +
        '.kanban-notification.error {' +
            'background-color: #dc3545;' +
        '}' +
        '.kanban-notification.info {' +
            'background-color: #17a2b8;' +
        '}' +
        '</style>';
    
    $('head').append(notificationStyles);
});