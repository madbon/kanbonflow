/**
 * Kanban Board JavaScript - DEBUG Version
 * Updated: 2025-11-06 - Fixed template literals syntax
 */
console.log('Kanban-debug.js loaded - FIXED VERSION without template literals');

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
        getCommentsUrl: '',
        addCommentUrl: '',
        editCommentUrl: '',
        deleteCommentUrl: '',
        imageUploadUrl: '',
        imageClipboardUrl: '',
        imageListUrl: '',
        imageDeleteUrl: '',
        csrfToken: '',
        csrfParam: '_csrf'
    },

    init: function(options) {
        this.config = $.extend(this.config, options);
        console.log('Kanban config initialized (NEW VERSION):', this.config);
        this.bindEvents();
        this.initDragAndDrop();
        this.initColumnDragAndDrop();
        this.initKeyboardShortcuts();
        
        // Restore focused task from localStorage
        this.restoreFocusedTask();
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

        // Focus button
        $(document).on('click', '.btn-focus', function(e) {
            e.stopPropagation();
            var taskId = $(this).data('task-id');
            console.log('Focus button clicked for task', taskId);
            self.toggleTaskFocus(taskId);
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
                    
                    // Initialize image upload functionality
                    self.initImageUpload(taskId);
                    
                    // Store task ID in modal for later use
                    $('#taskDetailsModal').data('task-id', taskId);
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
                            '<div class="description-content">' + (task.description || '<em class="text-muted">No description provided</em>') + '</div>' +
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
                
                // Comments Section
                '<div class="row mt-4">' +
                    '<div class="col-12">' +
                        '<div class="comments-section">' +
                            '<h6><i class="fa fa-comments"></i> Comments</h6>' +
                            '<div class="add-comment-form mb-3">' +
                                '<div class="form-group">' +
                                    '<textarea class="form-control" id="newCommentText" rows="3" placeholder="Add a comment..."></textarea>' +
                                '</div>' +
                                '<div class="d-flex justify-content-between align-items-center">' +
                                    '<div class="form-check">' +
                                        '<input type="checkbox" class="form-check-input" id="isInternalComment">' +
                                        '<label class="form-check-label" for="isInternalComment">' +
                                            '<small>Internal comment (team only)</small>' +
                                        '</label>' +
                                    '</div>' +
                                    '<button type="button" class="btn btn-primary btn-sm" id="addCommentBtn" data-task-id="' + task.id + '">' +
                                        '<i class="fa fa-paper-plane"></i> Add Comment' +
                                    '</button>' +
                                '</div>' +
                            '</div>' +
                            '<div id="commentsContainer">' +
                                '<div class="text-center text-muted">' +
                                    '<i class="fa fa-spinner fa-spin"></i> Loading comments...' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        
        $('#taskDetailsContent').html(html);
        $('#taskDetailsModalLabel').text(task.title);
        
        // Load comments after rendering task details
        self.loadTaskComments(task.id);
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
    },

    /**
     * Load comments for a task
     */
    loadTaskComments: function(taskId) {
        var self = this;
        
        console.log('Loading comments for task:', taskId);
        console.log('Available config:', self.config);
        
        var ajaxData = { taskId: taskId };
        
        // For GET requests, CSRF token is usually not needed, but let's include it anyway
        var csrfToken = self.config.csrfToken;
        var csrfParam = self.config.csrfParam || '_csrf';
        
        if (!csrfToken) {
            csrfToken = $('meta[name="csrf-token"]').attr('content');
            csrfParam = $('meta[name="csrf-param"]').attr('content') || '_csrf';
        }
        
        if (csrfParam && csrfToken) {
            ajaxData[csrfParam] = csrfToken;
        }
        
        $.ajax({
            url: self.config.getCommentsUrl,
            method: 'GET',
            data: ajaxData,
            success: function(response) {
                console.log('Comments response:', response);
                if (response.success) {
                    self.renderComments(response.comments);
                } else {
                    console.error('Failed to load comments:', response.message);
                    $('#commentsContainer').html('<div class="alert alert-warning">Failed to load comments: ' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading comments:', xhr, status, error);
                $('#commentsContainer').html('<div class="alert alert-danger">Error loading comments. Please try again.</div>');
            }
        });
        
        // Bind add comment event
        $(document).off('click', '#addCommentBtn').on('click', '#addCommentBtn', function() {
            var taskId = $(this).data('task-id');
            var comment = $('#newCommentText').val().trim();
            var isInternal = $('#isInternalComment').is(':checked');
            
            if (!comment) {
                alert('Please enter a comment.');
                return;
            }
            
            console.log('Add comment button clicked - TaskId:', taskId, 'Comment:', comment, 'Internal:', isInternal);
            self.addComment(taskId, comment, null, isInternal);
        });
    },

    /**
     * Render comments list
     */
    renderComments: function(comments) {
        var html = '';
        
        if (comments.length === 0) {
            html = '<div class="text-center text-muted py-3"><i class="fa fa-comment-slash"></i><br>No comments yet. Be the first to comment!</div>';
        } else {
            comments.forEach(function(comment) {
                html += '<div class="comment-item mb-3" data-comment-id="' + comment.id + '">';
                html += '<div class="comment-header d-flex align-items-center mb-2">';
                html += '<div class="comment-avatar me-2">';
                html += '<div class="avatar-circle">' + comment.user_avatar + '</div>';
                html += '</div>';
                html += '<div class="comment-meta flex-grow-1">';
                html += '<strong>' + comment.user_name + '</strong>';
                if (comment.is_internal) {
                    html += ' <span class="badge badge-secondary badge-sm">Internal</span>';
                }
                html += '<br><small class="text-muted">' + comment.relative_time + '</small>';
                html += '</div>';
                html += '<div class="comment-actions">';
                if (comment.can_edit) {
                    html += '<button class="btn btn-link btn-sm edit-comment-btn" data-comment-id="' + comment.id + '"><i class="fa fa-edit"></i></button>';
                }
                if (comment.can_delete) {
                    html += '<button class="btn btn-link btn-sm text-danger delete-comment-btn" data-comment-id="' + comment.id + '"><i class="fa fa-trash"></i></button>';
                }
                html += '</div>';
                html += '</div>';
                html += '<div class="comment-content">' + comment.comment.replace(/\n/g, '<br>') + '</div>';
                
                // Add replies if any
                if (comment.replies && comment.replies.length > 0) {
                    html += '<div class="comment-replies ml-4 mt-2">';
                    comment.replies.forEach(function(reply) {
                        html += '<div class="comment-item reply mb-2" data-comment-id="' + reply.id + '">';
                        html += '<div class="comment-header d-flex align-items-center mb-1">';
                        html += '<div class="comment-avatar me-2">';
                        html += '<div class="avatar-circle small">' + reply.user_avatar + '</div>';
                        html += '</div>';
                        html += '<div class="comment-meta flex-grow-1">';
                        html += '<strong>' + reply.user_name + '</strong>';
                        if (reply.is_internal) {
                            html += ' <span class="badge badge-secondary badge-sm">Internal</span>';
                        }
                        html += '<br><small class="text-muted">' + reply.relative_time + '</small>';
                        html += '</div>';
                        if (reply.can_delete) {
                            html += '<div class="comment-actions">';
                            html += '<button class="btn btn-link btn-sm text-danger delete-comment-btn" data-comment-id="' + reply.id + '"><i class="fa fa-trash"></i></button>';
                            html += '</div>';
                        }
                        html += '</div>';
                        html += '<div class="comment-content">' + reply.comment.replace(/\n/g, '<br>') + '</div>';
                        html += '</div>';
                    });
                    html += '</div>';
                }
                
                html += '<div class="comment-reply-section mt-2" style="display: none;">';
                html += '<textarea class="form-control form-control-sm reply-text" rows="2" placeholder="Write a reply..."></textarea>';
                html += '<div class="mt-1">';
                html += '<button class="btn btn-primary btn-sm add-reply-btn" data-parent-id="' + comment.id + '">Reply</button>';
                html += '<button class="btn btn-secondary btn-sm cancel-reply-btn">Cancel</button>';
                html += '</div>';
                html += '</div>';
                
                html += '<div class="comment-footer mt-1">';
                html += '<button class="btn btn-link btn-sm reply-toggle-btn" data-comment-id="' + comment.id + '">Reply</button>';
                html += '</div>';
                
                html += '</div>';
            });
        }
        
        $('#commentsContainer').html(html);
        
        // Bind comment events
        this.bindCommentEvents();
    },

    /**
     * Bind comment-related events
     */
    bindCommentEvents: function() {
        var self = this;
        
        // Reply toggle
        $(document).off('click', '.reply-toggle-btn').on('click', '.reply-toggle-btn', function() {
            var commentId = $(this).data('comment-id');
            var replySection = $(this).closest('.comment-item').find('.comment-reply-section');
            replySection.toggle();
            $(this).text(replySection.is(':visible') ? 'Cancel' : 'Reply');
        });
        
        // Cancel reply
        $(document).off('click', '.cancel-reply-btn').on('click', '.cancel-reply-btn', function() {
            var replySection = $(this).closest('.comment-reply-section');
            replySection.hide();
            replySection.closest('.comment-item').find('.reply-toggle-btn').text('Reply');
        });
        
        // Add reply
        $(document).off('click', '.add-reply-btn').on('click', '.add-reply-btn', function() {
            var parentId = $(this).data('parent-id');
            var replyText = $(this).closest('.comment-reply-section').find('.reply-text').val().trim();
            var taskId = $('#addCommentBtn').data('task-id');
            
            if (!replyText) {
                alert('Please enter a reply.');
                return;
            }
            
            self.addComment(taskId, replyText, parentId, false);
        });
        
        // Edit comment
        $(document).off('click', '.edit-comment-btn').on('click', '.edit-comment-btn', function() {
            var commentId = $(this).data('comment-id');
            var commentItem = $(this).closest('.comment-item');
            var commentContent = commentItem.find('.comment-content');
            var currentText = commentContent.text();
            
            // Replace content with editable textarea
            var editForm = '<div class="edit-comment-form">' +
                '<textarea class="form-control edit-comment-text" rows="3">' + currentText + '</textarea>' +
                '<div class="mt-2">' +
                    '<button class="btn btn-primary btn-sm save-comment-btn" data-comment-id="' + commentId + '">Save</button>' +
                    '<button class="btn btn-secondary btn-sm cancel-edit-btn">Cancel</button>' +
                '</div>' +
            '</div>';
            
            commentContent.hide();
            commentContent.after(editForm);
            $(this).hide(); // Hide edit button while editing
        });
        
        // Save edited comment
        $(document).off('click', '.save-comment-btn').on('click', '.save-comment-btn', function() {
            var commentId = $(this).data('comment-id');
            var newText = $(this).closest('.edit-comment-form').find('.edit-comment-text').val().trim();
            
            if (!newText) {
                alert('Please enter a comment.');
                return;
            }
            
            self.editComment(commentId, newText);
        });
        
        // Cancel comment editing
        $(document).off('click', '.cancel-edit-btn').on('click', '.cancel-edit-btn', function() {
            var commentItem = $(this).closest('.comment-item');
            commentItem.find('.edit-comment-form').remove();
            commentItem.find('.comment-content').show();
            commentItem.find('.edit-comment-btn').show();
        });

        // Delete comment
        $(document).off('click', '.delete-comment-btn').on('click', '.delete-comment-btn', function() {
            if (confirm('Are you sure you want to delete this comment?')) {
                var commentId = $(this).data('comment-id');
                self.deleteComment(commentId);
            }
        });
    },

    /**
     * Add a new comment
     */
    addComment: function(taskId, comment, parentId, isInternal) {
        var self = this;
        
        console.log('Adding comment with CSRF token:', self.config.csrfToken);
        console.log('CSRF param:', self.config.csrfParam);
        
        var data = {
            taskId: taskId,
            comment: comment,
            isInternal: isInternal ? 1 : 0  // Convert boolean to 1/0 for Yii2
        };
        
        if (parentId) {
            data.parentId = parentId;
        }
        
        // Get CSRF token from meta tags as fallback
        var csrfToken = self.config.csrfToken;
        var csrfParam = self.config.csrfParam || '_csrf';
        
        if (!csrfToken) {
            csrfToken = $('meta[name="csrf-token"]').attr('content');
            csrfParam = $('meta[name="csrf-param"]').attr('content') || '_csrf';
        }
        
        console.log('Final CSRF token to use:', csrfToken);
        console.log('Final CSRF param to use:', csrfParam);
        
        if (csrfParam && csrfToken) {
            data[csrfParam] = csrfToken;
        }
        
        console.log('AJAX data being sent:', data);
        
        $.ajax({
            url: self.config.addCommentUrl,
            method: 'POST',
            data: data,
            success: function(response) {
                console.log('Add comment response:', response);
                if (response.success) {
                    // Clear the form
                    $('#newCommentText').val('');
                    $('#isInternalComment').prop('checked', false);
                    $('.reply-text').val('');
                    $('.comment-reply-section').hide();
                    $('.reply-toggle-btn').text('Reply');
                    
                    // Reload comments
                    self.loadTaskComments(taskId);
                } else {
                    alert('Failed to add comment: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error adding comment:', xhr, status, error);
                console.error('Response text:', xhr.responseText);
                alert('Failed to add comment: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Failed to add comment'));
            }
        });
    },

    /**
     * Edit a comment
     */
    editComment: function(commentId, newText) {
        var self = this;
        var taskId = $('#addCommentBtn').data('task-id');
        
        var data = {
            commentId: commentId,
            comment: newText
        };
        
        // Get CSRF token from meta tags as fallback
        var csrfToken = self.config.csrfToken;
        var csrfParam = self.config.csrfParam || '_csrf';
        
        if (!csrfToken) {
            csrfToken = $('meta[name="csrf-token"]').attr('content');
            csrfParam = $('meta[name="csrf-param"]').attr('content') || '_csrf';
        }
        
        if (csrfParam && csrfToken) {
            data[csrfParam] = csrfToken;
        }
        
        $.ajax({
            url: self.config.editCommentUrl,
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Reload comments to show updated content
                    self.loadTaskComments(taskId);
                } else {
                    alert('Failed to edit comment: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error editing comment:', xhr, status, error);
                alert('Error editing comment. Please try again.');
            }
        });
    },

    /**
     * Delete a comment
     */
    deleteComment: function(commentId) {
        var self = this;
        var taskId = $('#addCommentBtn').data('task-id');
        
        var data = {
            commentId: commentId
        };
        
        // Get CSRF token from meta tags as fallback
        var csrfToken = self.config.csrfToken;
        var csrfParam = self.config.csrfParam || '_csrf';
        
        if (!csrfToken) {
            csrfToken = $('meta[name="csrf-token"]').attr('content');
            csrfParam = $('meta[name="csrf-param"]').attr('content') || '_csrf';
        }
        
        if (csrfParam && csrfToken) {
            data[csrfParam] = csrfToken;
        }
        
        $.ajax({
            url: self.config.deleteCommentUrl,
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Reload comments
                    self.loadTaskComments(taskId);
                } else {
                    alert('Failed to delete comment: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error deleting comment:', xhr, status, error);
                alert('Error deleting comment. Please try again.');
            }
        });
    },

    /**
     * Toggle focus on a specific task
     */
    toggleTaskFocus: function(taskId) {
        var self = this;
        var taskElement = $('.kanban-task[data-task-id="' + taskId + '"]');
        var focusButton = $('.btn-focus[data-task-id="' + taskId + '"]');
        
        console.log('Toggling focus for task:', taskId);
        
        if (taskElement.hasClass('task-focused')) {
            // Remove focus
            self.clearAllFocus();
            self.showNotification('Task focus cleared', 'info');
        } else {
            // Clear any existing focus first
            self.clearAllFocus();
            
            // Add focus to this task
            taskElement.addClass('task-focused');
            focusButton.addClass('focused');
            
            // Store focused task ID in localStorage for persistence
            localStorage.setItem('kanban-focused-task', taskId);
            self.focusedTaskId = taskId;
            
            // Show notification
            var taskTitle = taskElement.find('.task-title').text();
            self.showNotification('Focusing on: ' + taskTitle, 'success');
            
            // Auto-scroll to focused task if not in view
            self.scrollToTask(taskElement);
            
            // Optional: Clear focus after a certain time (5 minutes for longer persistence)
            if (self.focusTimeout) {
                clearTimeout(self.focusTimeout);
            }
            self.focusTimeout = setTimeout(function() {
                self.clearAllFocus();
                self.showNotification('Focus timeout - task unfocused', 'info');
            }, 300000); // 5 minutes
        }
    },

    /**
     * Clear focus from all tasks
     */
    clearAllFocus: function() {
        $('.kanban-task').removeClass('task-focused');
        $('.btn-focus').removeClass('focused');
        
        // Clear from localStorage
        localStorage.removeItem('kanban-focused-task');
        
        if (this.focusTimeout) {
            clearTimeout(this.focusTimeout);
            this.focusTimeout = null;
        }
        
        this.focusedTaskId = null;
    },

    /**
     * Restore focused task state from localStorage
     */
    restoreFocusedTask: function() {
        var focusedTaskId = localStorage.getItem('kanban-focused-task');
        if (focusedTaskId) {
            var $task = $('.kanban-task[data-task-id="' + focusedTaskId + '"]');
            if ($task.length) {
                this.focusedTaskId = focusedTaskId;
                $task.find('.btn-focus').addClass('focused');
                $task.find('.focus-indicator').show();
                console.log('Restored focus for task:', focusedTaskId);
            } else {
                // Task not found, clear from localStorage
                localStorage.removeItem('kanban-focused-task');
            }
        }
    },

    /**
     * Initialize image upload functionality
     */
    initImageUpload: function(taskId) {
        var self = this;
        
        // Add image upload UI to task details modal
        this.addImageUploadUI(taskId);
        
        // Bind file input change event
        $('#imageFileInput').off('change').on('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                self.uploadImageFile(taskId, file);
            }
        });
        
        // Bind clipboard paste event
        $(document).off('paste.imageUpload').on('paste.imageUpload', function(e) {
            if ($('#taskDetailsModal').is(':visible')) {
                var items = (e.clipboardData || e.originalEvent.clipboardData).items;
                for (var i = 0; i < items.length; i++) {
                    if (items[i].type.indexOf('image') !== -1) {
                        var blob = items[i].getAsFile();
                        self.uploadImageFromClipboard(taskId, blob);
                        break;
                    }
                }
            }
        });
        
        // Load existing images
        this.loadTaskImages(taskId);
        
        // Initialize image viewing functionality
        this.initImageView();
    },

    /**
     * Add image upload UI to task details modal
     */
    addImageUploadUI: function(taskId) {
        var imageUploadHtml = 
            '<div class="task-images-section">' +
                '<h5><i class="fa fa-paperclip"></i> Attachments</h5>' +
                '<div class="image-upload-controls">' +
                    '<input type="file" id="imageFileInput" accept="image/*" style="display:none">' +
                    '<button type="button" class="btn btn-sm btn-primary" onclick="$(\'#imageFileInput\').click()">' +
                        '<i class="fa fa-upload"></i> Browse Image' +
                    '</button>' +
                    '<span class="clipboard-hint"> or paste image from clipboard (Ctrl+V)</span>' +
                '</div>' +
                '<div id="taskImagesList" class="task-images-list"></div>' +
            '</div>';
        
        // Add to task details modal if not already present
        if ($('#taskDetailsModal .task-images-section').length === 0) {
            $('#taskDetailsModal .modal-body').append(imageUploadHtml);
        }
    },

    /**
     * Upload image file
     */
    uploadImageFile: function(taskId, file) {
        var self = this;
        
        if (!this.validateImageFile(file)) {
            return;
        }
        
        var formData = new FormData();
        formData.append('image', file);
        formData.append('taskId', taskId);
        formData.append(this.config.csrfParam, this.config.csrfToken);
        
        this.showImageUploadProgress();
        
        $.ajax({
            url: this.config.imageUploadUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                self.hideImageUploadProgress();
                if (response.success) {
                    self.showNotification('Image uploaded successfully!', 'success');
                    self.addImageToList(response.image);
                    self.updateTaskImageCount(taskId);
                } else {
                    self.showNotification('Upload failed: ' + response.message, 'error');
                }
            },
            error: function() {
                self.hideImageUploadProgress();
                self.showNotification('Upload failed. Please try again.', 'error');
            }
        });
    },

    /**
     * Upload image from clipboard
     */
    uploadImageFromClipboard: function(taskId, blob) {
        var self = this;
        
        if (!blob || blob.type.indexOf('image') !== 0) {
            return;
        }
        
        // Convert blob to base64
        var reader = new FileReader();
        reader.onload = function(e) {
            var imageData = e.target.result;
            
            var postData = {};
            postData['taskId'] = taskId;
            postData['imageData'] = imageData;
            postData[self.config.csrfParam] = self.config.csrfToken;
            
            self.showImageUploadProgress();
            
            $.ajax({
                url: self.config.imageClipboardUrl,
                type: 'POST',
                data: postData,
                success: function(response) {
                    self.hideImageUploadProgress();
                    if (response.success) {
                        self.showNotification('Image pasted successfully!', 'success');
                        self.addImageToList(response.image);
                        self.updateTaskImageCount(taskId);
                    } else {
                        self.showNotification('Upload failed: ' + response.message, 'error');
                    }
                },
                error: function() {
                    self.hideImageUploadProgress();
                    self.showNotification('Upload failed. Please try again.', 'error');
                }
            });
        };
        reader.readAsDataURL(blob);
    },

    /**
     * Load existing images for task
     */
    loadTaskImages: function(taskId) {
        var self = this;
        
        $.ajax({
            url: this.config.imageListUrl,
            type: 'GET',
            data: { taskId: taskId },
            success: function(response) {
                if (response.success) {
                    $('#taskImagesList').empty();
                    if (response.images.length === 0) {
                        $('#taskImagesList').html('<p class="text-muted text-center">No images attached yet.</p>');
                    } else {
                        for (var i = 0; i < response.images.length; i++) {
                            self.addImageToList(response.images[i]);
                        }
                    }
                }
            },
            error: function() {
                console.error('Failed to load task images');
            }
        });
    },

    /**
     * Add image to the list display
     */
    addImageToList: function(image) {
        // Remove "no images" message if it exists
        $('#taskImagesList p.text-muted').remove();
        
        var imageHtml = 
            '<div class="task-image-item" data-image-id="' + image.id + '">' +
                '<img src="' + image.url + '" alt="' + image.original_name + '" class="task-image-thumb" />' +
                '<div class="image-info">' +
                    '<div class="image-name">' + image.original_name + '</div>' +
                    '<div class="image-size">' + image.size + '</div>' +
                '</div>' +
                '<button type="button" class="btn btn-sm btn-danger image-delete-btn" data-image-id="' + image.id + '">' +
                    '<i class="fa fa-trash"></i>' +
                '</button>' +
            '</div>';
        
        $('#taskImagesList').append(imageHtml);
        
        // Bind delete event for this image
        this.bindImageDeleteEvent(image.id);
    },

    /**
     * Bind delete event for image
     */
    bindImageDeleteEvent: function(imageId) {
        var self = this;
        
        $('.image-delete-btn[data-image-id="' + imageId + '"]').off('click').on('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this image?')) {
                self.deleteTaskImage(imageId);
            }
        });
    },

    /**
     * Delete task image
     */
    deleteTaskImage: function(imageId) {
        var self = this;
        
        var postData = {};
        postData['imageId'] = imageId;
        postData[this.config.csrfParam] = this.config.csrfToken;
        
        $.ajax({
            url: this.config.imageDeleteUrl,
            type: 'POST',
            data: postData,
            success: function(response) {
                if (response.success) {
                    $('.task-image-item[data-image-id="' + imageId + '"]').remove();
                    
                    // Show "no images" message if no images left
                    if ($('#taskImagesList .task-image-item').length === 0) {
                        $('#taskImagesList').html('<p class="text-muted text-center">No images attached yet.</p>');
                    }
                    
                    self.showNotification('Image deleted successfully!', 'success');
                    // Update image count on task card
                    var taskId = $('#taskDetailsModal').data('task-id');
                    if (taskId) {
                        self.updateTaskImageCount(taskId);
                    }
                } else {
                    self.showNotification('Delete failed: ' + response.message, 'error');
                }
            },
            error: function() {
                self.showNotification('Delete failed. Please try again.', 'error');
            }
        });
    },

    /**
     * Update task image count on task card
     */
    updateTaskImageCount: function(taskId) {
        var imageCount = $('#taskImagesList .task-image-item').length;
        var $taskCard = $('.kanban-task[data-task-id="' + taskId + '"]');
        var $attachmentEl = $taskCard.find('.task-attachments');
        
        if (imageCount > 0) {
            if ($attachmentEl.length === 0) {
                $taskCard.find('.task-footer').append(
                    '<div class="task-attachments"><i class="fa fa-paperclip"></i> ' + imageCount + '</div>'
                );
            } else {
                $attachmentEl.html('<i class="fa fa-paperclip"></i> ' + imageCount);
            }
        } else {
            $attachmentEl.remove();
        }
    },

    /**
     * Validate image file
     */
    validateImageFile: function(file) {
        // Check file type
        var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (allowedTypes.indexOf(file.type) === -1) {
            this.showNotification('Invalid file type. Only images are allowed.', 'error');
            return false;
        }
        
        // Check file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            this.showNotification('File too large. Maximum size is 5MB.', 'error');
            return false;
        }
        
        return true;
    },

    /**
     * Show image upload progress
     */
    showImageUploadProgress: function() {
        $('.image-upload-controls').append(
            '<div class="upload-progress"><i class="fa fa-spinner fa-spin"></i> Uploading...</div>'
        );
        $('.image-upload-controls button').prop('disabled', true);
    },

    /**
     * Hide image upload progress
     */
    hideImageUploadProgress: function() {
        $('.upload-progress').remove();
        $('.image-upload-controls button').prop('disabled', false);
    },

    /**
     * Initialize image view functionality
     */
    initImageView: function() {
        var self = this;
        
        // Add image overlay to body if not exists
        if ($('.image-overlay').length === 0) {
            $('body').append('<div class="image-overlay"><img src="" alt="Full size image" /></div>');
        }
        
        // Bind click event for image thumbnails
        $(document).off('click.imageView', '.task-image-thumb').on('click.imageView', '.task-image-thumb', function(e) {
            e.preventDefault();
            var fullImageUrl = $(this).attr('src');
            $('.image-overlay img').attr('src', fullImageUrl);
            $('.image-overlay').fadeIn(300).css('display', 'flex');
        });
        
        // Close overlay on click
        $(document).off('click.imageOverlay', '.image-overlay').on('click.imageOverlay', '.image-overlay', function() {
            $(this).fadeOut(300);
        });
        
        // Close overlay on ESC key
        $(document).off('keyup.imageOverlay').on('keyup.imageOverlay', function(e) {
            if (e.keyCode === 27) { // ESC key
                $('.image-overlay').fadeOut(300);
            }
        });
    },

    /**
     * Scroll to a specific task element
     */
    scrollToTask: function(taskElement) {
        if (taskElement.length) {
            var container = $('.kanban-board-container');
            var elementTop = taskElement.offset().top;
            var elementLeft = taskElement.offset().left;
            var containerTop = container.offset().top;
            var containerLeft = container.offset().left;
            
            // Scroll vertically if needed
            if (elementTop < containerTop || elementTop > containerTop + container.height()) {
                $('html, body').animate({
                    scrollTop: elementTop - 100
                }, 500);
            }
            
            // Scroll horizontally if needed
            if (elementLeft < containerLeft || elementLeft > containerLeft + container.width()) {
                container.animate({
                    scrollLeft: container.scrollLeft() + (elementLeft - containerLeft) - 200
                }, 500);
            }
        }
    },

    /**
     * Get currently focused task ID
     */
    getFocusedTaskId: function() {
        return this.focusedTaskId || null;
    },

    /**
     * Initialize keyboard shortcuts for focus functionality
     */
    initKeyboardShortcuts: function() {
        var self = this;
        
        $(document).on('keydown', function(e) {
            // Only handle shortcuts when not typing in input fields
            if ($(e.target).is('input, textarea, select')) {
                return;
            }
            
            // Escape key - clear focus
            if (e.keyCode === 27) { // ESC
                if (self.focusedTaskId) {
                    self.clearAllFocus();
                    self.showNotification('Focus cleared with ESC key', 'info');
                }
            }
            
            // F key - focus on first visible task
            if (e.keyCode === 70 && !e.ctrlKey && !e.altKey && !e.shiftKey) { // F key
                e.preventDefault();
                var firstTask = $('.kanban-task').first();
                if (firstTask.length) {
                    var taskId = firstTask.data('task-id');
                    self.toggleTaskFocus(taskId);
                }
            }
        });
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