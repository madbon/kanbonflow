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
        getTaskChecklistUrl: '',
        addChecklistItemUrl: '',
        updateChecklistItemUrl: '',
        deleteChecklistItemUrl: '',
        toggleChecklistItemUrl: '',
        reorderChecklistItemsUrl: '',
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
        
        // Restore collapsed column states from localStorage
        this.restoreCollapsedColumns();
        
        // Restore highlighted checklist items from localStorage
        setTimeout(() => {
            this.restoreHighlightedItems();
        }, 500);
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

        // Column header click to collapse/expand
        $(document).on('click', '.kanban-column-header', function(e) {
            // Don't trigger if clicking on action buttons
            if ($(e.target).closest('.column-actions').length || 
                $(e.target).closest('.btn-column-edit, .btn-column-delete').length) {
                return;
            }
            
            e.stopPropagation();
            var $column = $(this).closest('.kanban-column');
            var columnId = $column.data('column-id');
            var status = $column.data('status');
            
            console.log('Column header clicked:', status, 'Column ID:', columnId);
            self.toggleColumnCollapse($column, status);
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

        // Checklist Events  
        $(document).on('click', '#addChecklistItemBtn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Add Step button clicked from debug.js!');
            
            var taskId = $('#editTaskId').val();
            console.log('Task ID:', taskId);
            
            if (!taskId) {
                console.error('No task ID found');
                self.showNotification('Error: No task selected', 'error');
                return;
            }
            
            console.log('Calling addChecklistItem with taskId:', taskId);
            self.addChecklistItem(taskId);
        });

        // Checklist item interactions
        $(document).on('click', '.checklist-checkbox', function() {
            var itemId = $(this).closest('.checklist-item').data('item-id');
            self.toggleChecklistItem(itemId);
        });

        $(document).on('blur', '.checklist-text', function() {
            var item = $(this).closest('.checklist-item');
            var itemId = item.data('item-id');
            var newText = $(this).val().trim();
            var originalText = item.data('original-text');
            
            if (newText !== originalText && newText !== '') {
                self.updateChecklistItem(itemId, newText);
                item.data('original-text', newText);
            } else if (newText === '') {
                $(this).val(originalText);
            }
        });

        $(document).on('click', '.checklist-delete', function() {
            var itemId = $(this).closest('.checklist-item').data('item-id');
            self.deleteChecklistItem(itemId);
        });

        // Checklist item highlight/focus functionality
        $(document).on('click', '.checklist-item', function(e) {
            // Don't highlight if clicking on checkbox, textarea, or buttons
            if ($(e.target).is('.checklist-checkbox, .checklist-text, .checklist-delete, .checklist-drag-handle, .fa')) {
                return;
            }
            
            // Prevent text selection when clicking to highlight
            e.preventDefault();
            
            var itemId = $(this).data('item-id');
            var wasHighlighted = $(this).hasClass('highlighted');
            
            // Remove highlight from all items first
            $('.checklist-item').removeClass('highlighted');
            self.clearAllHighlightedItems();
            
            // Add highlight to this item only if it wasn't already highlighted
            if (!wasHighlighted) {
                $(this).addClass('highlighted');
                self.saveHighlightedItem(itemId);
            }
        });

        // Checklist item highlight/focus functionality for details modal
        $(document).on('click', '.checklist-item-interactive', function(e) {
            // Don't highlight if clicking on checkbox, textarea, or buttons  
            if ($(e.target).is('.checklist-checkbox-details, .checklist-text-details, .fa')) {
                return;
            }
            
            // Prevent text selection when clicking to highlight
            e.preventDefault();
            
            var itemId = $(this).data('item-id');
            var wasHighlighted = $(this).hasClass('highlighted');
            
            // Remove highlight from all items first
            $('.checklist-item-interactive').removeClass('highlighted');
            self.clearAllHighlightedItems();
            
            // Add highlight to this item only if it wasn't already highlighted
            if (!wasHighlighted) {
                $(this).addClass('highlighted');
                self.saveHighlightedItem(itemId);
            }
        });

        // Clear highlights when clicking elsewhere in checklist container
        $(document).on('click', '#checklistContainer, #detailsChecklistItems', function(e) {
            // Only clear if clicking on the container itself, not on checklist items
            if (e.target === this) {
                $('.checklist-item, .checklist-item-interactive').removeClass('highlighted');
                // Clear from localStorage as well
                self.clearAllHighlightedItems();
            }
        });

        // Interactive checklist in task details modal
        $(document).on('click', '.checklist-checkbox-details', function() {
            var itemId = $(this).closest('.checklist-item-interactive').data('item-id');
            self.toggleChecklistItemInDetails(itemId);
        });

        // Add Step button in task details modal
        $(document).on('click', '#addStepInDetailsBtn', function() {
            $('#addStepForm').show();
            $('#newStepInput').focus();
            $(this).hide();
        });

        // Save new step in task details modal
        $(document).on('click', '#saveNewStepBtn', function() {
            var stepText = $('#newStepInput').val().trim();
            if (stepText) {
                var taskId = $('#taskDetailsModal').data('task-id');
                if (taskId) {
                    self.addChecklistItemInDetails(taskId, stepText);
                }
            }
        });

        // Cancel new step in task details modal
        $(document).on('click', '#cancelNewStepBtn', function() {
            $('#addStepForm').hide();
            $('#newStepInput').val('');
            $('#addStepInDetailsBtn').show();
        });

        // Handle Enter key in step input
        $(document).on('keydown', '#newStepInput', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $('#saveNewStepBtn').click();
            } else if (e.which === 27) { // Escape key
                e.preventDefault();
                $('#cancelNewStepBtn').click();
            }
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
                    
                    // Load and render checklist for this task
                    self.loadTaskChecklistForDetails(taskId);
                    
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
        
        // Images section removed - now handled by task-images-section
        
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
                        
                        '<div id="taskDetailsChecklist" class="task-checklist-section">' +
                            '<!-- Checklist will be loaded here -->' +
                        '</div>' +
                    '</div>' +
                    
                    '<div class="col-md-4">' +
                        '<div class="task-meta">' +
                            '<h6>Task Information</h6>' +
                            
                            '<div class="meta-item mb-2">' +
                                '<strong>Deadline:</strong><br>' +
                                deadlineHtml +
                            '</div>' +
                            
                            // Target dates section
                            (task.target_start_date || task.target_end_date ? 
                                '<div class="meta-item mb-2">' +
                                    '<strong>Target Dates:</strong><br>' +
                                    '<small class="text-info">' +
                                        '<i class="fa fa-bullseye"></i> ' +
                                        (task.target_start_date && task.target_end_date ? 
                                            task.target_start_date + ' - ' + task.target_end_date :
                                            (task.target_start_date ? 'From ' + task.target_start_date : 'Until ' + task.target_end_date)
                                        ) +
                                    '</small>' +
                                '</div>'
                            : '') +
                            
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
                        '<div class="task-comments-section">' +
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

    /**
     * Add a new checklist item
     */
    addChecklistItem: function(taskId, stepText) {
        var self = this;
        console.log('addChecklistItem method called with taskId:', taskId);
        
        if (!stepText) {
            console.log('No stepText provided, showing prompt');
            stepText = prompt('Enter step description:');
            console.log('User entered stepText:', stepText);
            if (!stepText || !stepText.trim()) {
                console.log('No valid stepText entered, returning');
                return;
            }
        }
        
        $.ajax({
            url: self.config.addChecklistItemUrl,
            method: 'POST',
            data: {
                taskId: taskId,
                stepText: stepText.trim(),
                [self.config.csrfParam]: self.config.csrfToken
            },
            success: function(response) {
                console.log('Add checklist item response:', response);
                if (response.success) {
                    self.showNotification('Step added successfully', 'success');
                    // Refresh the edit task modal to show the new checklist item
                    self.editTask(taskId);
                } else {
                    self.showNotification('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                console.log('AJAX error when adding checklist item');
                self.showNotification('Failed to add step', 'error');
            }
        });
    },

    /**
     * Load checklist items for a task
     */
    loadTaskChecklist: function(taskId) {
        var self = this;
        
        $.ajax({
            url: self.config.getTaskChecklistUrl,
            method: 'GET',
            data: { taskId: taskId },
            success: function(response) {
                if (response.success) {
                    self.renderChecklist(response.items, response.progress);
                } else {
                    $('#checklistContainer').html('<div class="alert alert-danger">Error loading checklist: ' + response.message + '</div>');
                }
            },
            error: function() {
                $('#checklistContainer').html('<div class="alert alert-danger">Failed to load checklist items.</div>');
            }
        });
    },

    /**
     * Render checklist items in the modal
     */
    renderChecklist: function(items, progress) {
        var html = '';
        
        if (items.length === 0) {
            html = '<div class="checklist-empty"><i class="fa fa-list-ul"></i><br>No checklist items yet. Add some steps to track your progress!</div>';
        } else {
            html += '<div id="checklistItems">';
            
            items.forEach(function(item) {
                html += '<div class="checklist-item' + (item.is_completed ? ' completed' : '') + '" data-item-id="' + item.id + '" data-original-text="' + item.step_text + '">';
                html += '<div class="checklist-drag-handle"><i class="fa fa-grip-vertical"></i></div>';
                html += '<input type="checkbox" class="checklist-checkbox"' + (item.is_completed ? ' checked' : '') + '>';
                html += '<textarea class="checklist-text" rows="1">' + item.step_text + '</textarea>';
                html += '<div class="checklist-actions">';
                html += '<button type="button" class="btn btn-sm btn-danger checklist-delete" title="Delete step"><i class="fa fa-trash"></i></button>';
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';
        }
        
        $('#checklistContainer').html(html);
        
        // Update progress bar if available
        if (progress && progress.total > 0) {
            $('#checklistProgress').show();
            $('#checklistProgressBar').css('width', progress.percentage + '%');
            $('#checklistProgressText').text(progress.percentage + '%');
            $('#checklistProgressDetails').text(progress.completed + ' of ' + progress.total + ' steps completed');
        } else {
            $('#checklistProgress').hide();
        }
        
        // Restore highlighted state after rendering
        setTimeout(() => {
            this.restoreHighlightedItems();
        }, 50);
    },

    /**
     * Toggle checklist item completion
     */
    toggleChecklistItem: function(itemId) {
        var self = this;
        
        $.ajax({
            url: self.config.toggleChecklistItemUrl,
            method: 'POST',
            data: {
                itemId: itemId,
                [self.config.csrfParam]: self.config.csrfToken
            },
            success: function(response) {
                if (response.success) {
                    var item = $('.checklist-item[data-item-id="' + itemId + '"]');
                    var checkbox = item.find('.checklist-checkbox');
                    var textarea = item.find('.checklist-text');
                    
                    if (response.is_completed) {
                        item.addClass('completed');
                        checkbox.prop('checked', true);
                        textarea.css('text-decoration', 'line-through');
                    } else {
                        item.removeClass('completed');
                        checkbox.prop('checked', false);
                        textarea.css('text-decoration', 'none');
                    }
                    
                    // Update progress
                    if (response.progress) {
                        $('#checklistProgressBar').css('width', response.progress.percentage + '%');
                        $('#checklistProgressText').text(response.progress.percentage + '%');
                        $('#checklistProgressDetails').text(response.progress.completed + ' of ' + response.progress.total + ' steps completed');
                    }
                } else {
                    self.showNotification('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                self.showNotification('Failed to toggle step', 'error');
            }
        });
    },

    /**
     * Update checklist item text
     */
    updateChecklistItem: function(itemId, stepText) {
        var self = this;
        
        $.ajax({
            url: self.config.updateChecklistItemUrl,
            method: 'POST',
            data: {
                itemId: itemId,
                stepText: stepText,
                [self.config.csrfParam]: self.config.csrfToken
            },
            success: function(response) {
                if (response.success) {
                    self.showNotification('Step updated successfully', 'success');
                } else {
                    self.showNotification('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                self.showNotification('Failed to update step', 'error');
            }
        });
    },

    /**
     * Delete checklist item
     */
    deleteChecklistItem: function(itemId) {
        var self = this;
        
        if (confirm('Are you sure you want to delete this step?')) {
            $.ajax({
                url: self.config.deleteChecklistItemUrl,
                method: 'POST',
                data: {
                    itemId: itemId,
                    [self.config.csrfParam]: self.config.csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        $('.checklist-item[data-item-id="' + itemId + '"]').remove();
                        self.showNotification('Step deleted successfully', 'success');
                        
                        // Update progress
                        if (response.progress) {
                            $('#checklistProgressBar').css('width', response.progress.percentage + '%');
                            $('#checklistProgressText').text(response.progress.percentage + '%');
                            $('#checklistProgressDetails').text(response.progress.completed + ' of ' + response.progress.total + ' steps completed');
                        }
                        
                        // Check if no items left
                        if ($('#checklistItems .checklist-item').length === 0) {
                            $('#checklistContainer').html('<div class="checklist-empty"><i class="fa fa-list-ul"></i><br>No checklist items yet. Add some steps to track your progress!</div>');
                            $('#checklistProgress').hide();
                        }
                    } else {
                        self.showNotification('Error: ' + response.message, 'error');
                    }
                },
                error: function() {
                    self.showNotification('Failed to delete step', 'error');
                }
            });
        }
    },

    /**
     * Render checklist items in the task details modal (viewing mode)
     */
    renderChecklistForDetails: function(checklist) {
        if (!checklist || checklist.length === 0) {
            return '';
        }

        var progressHtml = '';
        var totalItems = checklist.length;
        var completedItems = 0;
        
        // Calculate progress
        for (var i = 0; i < checklist.length; i++) {
            if (checklist[i].is_completed) {
                completedItems++;
            }
        }
        
        var percentage = totalItems > 0 ? Math.round((completedItems / totalItems) * 100) : 0;
        
        if (totalItems > 0) {
            progressHtml = 
                '<div class="checklist-progress mb-3" id="detailsChecklistProgress">' +
                    '<div class="progress">' +
                        '<div class="progress-bar bg-success" style="width: ' + percentage + '%"></div>' +
                    '</div>' +
                    '<small class="text-muted">' + completedItems + ' of ' + totalItems + ' steps completed (' + percentage + '%)</small>' +
                '</div>';
        }

        // Build items HTML
        var itemsHtml = '';
        for (var i = 0; i < checklist.length; i++) {
            var item = checklist[i];
            var isCompleted = item.is_completed;
            var itemClass = isCompleted ? 'checklist-item-interactive completed' : 'checklist-item-interactive';
            var textStyle = isCompleted ? 'text-decoration: line-through; color: #6c757d;' : '';
            
            itemsHtml += 
                '<div class="' + itemClass + '" data-item-id="' + item.id + '">' +
                    '<div class="form-check">' +
                        '<input type="checkbox" class="form-check-input checklist-checkbox-details" ' + (isCompleted ? 'checked' : '') + '>' +
                        '<label class="form-check-label checklist-text-display" style="' + textStyle + '">' +
                            item.step_text +
                            (isCompleted && item.completed_at ? 
                                '<br><small class="text-muted">Completed ' + item.completed_at + 
                                (item.completed_by_name ? ' by ' + item.completed_by_name : '') + '</small>'
                            : '') +
                        '</label>' +
                    '</div>' +
                '</div>';
        }

        return (
            '<div class="task-checklist mt-4">' +
                '<div class="d-flex justify-content-between align-items-center mb-3">' +
                    '<h6 class="mb-0"><i class="fa fa-list-ul"></i> Checklist</h6>' +
                    '<div>' +
                        '<button class="btn btn-sm btn-success mr-2" id="addStepInDetailsBtn" title="Add new step">' +
                            '<i class="fa fa-plus"></i> Add Step' +
                        '</button>' +
                        '<button class="btn btn-sm btn-outline-primary" onclick="KanbanBoard.openTaskForEditing()" title="Edit checklist">' +
                            '<i class="fa fa-edit"></i> Edit' +
                        '</button>' +
                    '</div>' +
                '</div>' +
                progressHtml +
                '<div class="checklist-items-interactive" id="detailsChecklistItems">' +
                    itemsHtml +
                '</div>' +
                '<div class="add-step-form mt-3" id="addStepForm" style="display: none;">' +
                    '<div class="input-group">' +
                        '<input type="text" class="form-control" id="newStepInput" placeholder="Enter step description..." maxlength="1000">' +
                        '<div class="input-group-append">' +
                            '<button class="btn btn-success" type="button" id="saveNewStepBtn" title="Save step">' +
                                '<i class="fa fa-check"></i>' +
                            '</button>' +
                            '<button class="btn btn-secondary" type="button" id="cancelNewStepBtn" title="Cancel">' +
                                '<i class="fa fa-times"></i>' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );
    },

    /**
     * Load checklist items for task details view
     */
    loadTaskChecklistForDetails: function(taskId) {
        var self = this;
        
        $.ajax({
            url: self.config.getTaskChecklistUrl,
            method: 'GET',
            data: { taskId: taskId },
            success: function(response) {
                if (response.success && response.items && response.items.length > 0) {
                    // Render checklist in the details section
                    var checklistHtml = self.renderChecklistForDetails(response.items);
                    $('#taskDetailsChecklist').html(checklistHtml);
                    
                    // Restore highlighted state after rendering
                    setTimeout(() => {
                        self.restoreHighlightedItems();
                    }, 50);
                } else {
                    // Hide the checklist section if no items
                    $('#taskDetailsChecklist').html('');
                }
            },
            error: function() {
                console.log('Failed to load checklist for details view');
                $('#taskDetailsChecklist').html('');
            }
        });
    },

    /**
     * Toggle checklist item in details view
     */
    toggleChecklistItemInDetails: function(itemId) {
        var self = this;
        
        $.ajax({
            url: self.config.toggleChecklistItemUrl,
            method: 'POST',
            data: {
                itemId: itemId,
                [self.config.csrfParam]: self.config.csrfToken
            },
            success: function(response) {
                if (response.success) {
                    var item = $('.checklist-item-interactive[data-item-id="' + itemId + '"]');
                    var checkbox = item.find('.checklist-checkbox-details');
                    var textSpan = item.find('.checklist-text-display');
                    
                    if (response.is_completed) {
                        item.addClass('completed');
                        checkbox.prop('checked', true);
                        textSpan.css({
                            'text-decoration': 'line-through',
                            'color': '#6c757d'
                        });
                        
                        // Add completion info if available
                        if (response.completed_at) {
                            var completionInfo = '<br><small class="text-muted">Completed ' + response.completed_at + 
                                (response.completed_by_name ? ' by ' + response.completed_by_name : '') + '</small>';
                            textSpan.append(completionInfo);
                        }
                    } else {
                        item.removeClass('completed');
                        checkbox.prop('checked', false);
                        textSpan.css({
                            'text-decoration': 'none',
                            'color': 'inherit'
                        });
                        
                        // Remove completion info
                        item.find('small').remove();
                    }
                    
                    // Update progress bar
                    if (response.progress) {
                        $('#detailsChecklistProgress .progress-bar').css('width', response.progress.percentage + '%');
                        $('#detailsChecklistProgress small').text(response.progress.completed + ' of ' + response.progress.total + ' steps completed (' + response.progress.percentage + '%)');
                    }
                    
                    var status = response.is_completed ? 'completed' : 'marked incomplete';
                    self.showNotification('Step ' + status, 'success');
                } else {
                    self.showNotification('Error: ' + response.message, 'error');
                    // Revert checkbox state
                    var checkbox = $('.checklist-item-interactive[data-item-id="' + itemId + '"] .checklist-checkbox-details');
                    checkbox.prop('checked', !checkbox.prop('checked'));
                }
            },
            error: function() {
                self.showNotification('Failed to toggle step', 'error');
                // Revert checkbox state
                var checkbox = $('.checklist-item-interactive[data-item-id="' + itemId + '"] .checklist-checkbox-details');
                checkbox.prop('checked', !checkbox.prop('checked'));
            }
        });
    },

    /**
     * Add new checklist item from task details modal
     */
    addChecklistItemInDetails: function(taskId, stepText) {
        var self = this;
        
        $.ajax({
            url: self.config.addChecklistItemUrl,
            method: 'POST',
            data: {
                taskId: taskId,
                stepText: stepText,
                [self.config.csrfParam]: self.config.csrfToken
            },
            success: function(response) {
                if (response.success) {
                    // Hide the add form and reset
                    $('#addStepForm').hide();
                    $('#newStepInput').val('');
                    $('#addStepInDetailsBtn').show();
                    
                    // Refresh the checklist in details view
                    self.loadTaskChecklistForDetails(taskId);
                    
                    self.showNotification('Step added successfully', 'success');
                } else {
                    self.showNotification('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                self.showNotification('Failed to add step', 'error');
            }
        });
    },

    /**
     * Save highlighted checklist item to localStorage
     */
    saveHighlightedItem: function(itemId) {
        if (!itemId) return;
        localStorage.setItem('kanban_highlighted_checklist_item', itemId);
    },

    /**
     * Get highlighted checklist item from localStorage
     */
    getHighlightedItem: function() {
        return localStorage.getItem('kanban_highlighted_checklist_item');
    },

    /**
     * Clear all highlighted items from localStorage
     */
    clearAllHighlightedItems: function() {
        localStorage.removeItem('kanban_highlighted_checklist_item');
    },

    /**
     * Restore highlighted state for checklist items
     */
    restoreHighlightedItems: function() {
        var highlightedItemId = this.getHighlightedItem();
        if (highlightedItemId) {
            // Try to find and highlight the item in regular checklist
            var $regularItem = $('.checklist-item[data-item-id="' + highlightedItemId + '"]');
            if ($regularItem.length) {
                $regularItem.addClass('highlighted');
            }
            
            // Try to find and highlight the item in interactive checklist
            var $interactiveItem = $('.checklist-item-interactive[data-item-id="' + highlightedItemId + '"]');
            if ($interactiveItem.length) {
                $interactiveItem.addClass('highlighted');
            }
        }
    },

    /**
     * Open task for editing from details modal
     */
    openTaskForEditing: function() {
        var taskId = $('#taskDetailsModal').data('task-id');
        if (taskId) {
            $('#taskDetailsModal').modal('hide');
            this.editTask(taskId);
        }
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
        $('#editTaskStatus').val(task.status);
        $('#editTaskDeadline').val(task.deadline);
        $('#editTaskAssignedTo').val(task.assigned_to);
        $('#editTaskTargetStartDate').val(task.target_start_date || '');
        $('#editTaskTargetEndDate').val(task.target_end_date || '');
        // Explicitly handle the include_in_export value to ensure 0 is properly selected
        var exportValue = '1'; // default
        if (task.include_in_export !== undefined && task.include_in_export !== null) {
            exportValue = String(task.include_in_export);
        }
        $('#editTaskIncludeInExport').val(exportValue);
        
        // Load checklist for this task
        this.loadTaskChecklist(task.id);
        
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
        console.log('NEW VERSION: Status field value:', $('#editTaskStatus').val());
        
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
     * Toggle column collapse/expand state
     */
    toggleColumnCollapse: function($column, status) {
        var self = this;
        var isCollapsed = $column.hasClass('collapsed');
        var columnName = $column.find('.column-title').text();
        
        console.log('Toggling column:', status, 'Currently collapsed:', isCollapsed);
        
        if (isCollapsed) {
            // Expand column
            $column.removeClass('collapsed');
            self.showNotification('Expanded column: ' + columnName, 'info');
            
            // Remove from collapsed columns in localStorage
            self.removeCollapsedColumn(status);
        } else {
            // Collapse column
            $column.addClass('collapsed');
            self.showNotification('Collapsed column: ' + columnName, 'info');
            
            // Save to collapsed columns in localStorage
            self.saveCollapsedColumn(status);
        }
        
        // Trigger a resize event to help with any layout adjustments
        $(window).trigger('resize');
    },

    /**
     * Save collapsed column state to localStorage
     */
    saveCollapsedColumn: function(status) {
        var collapsedColumns = this.getCollapsedColumns();
        if (collapsedColumns.indexOf(status) === -1) {
            collapsedColumns.push(status);
            localStorage.setItem('kanban-collapsed-columns', JSON.stringify(collapsedColumns));
            console.log('Saved collapsed columns:', collapsedColumns);
        }
    },

    /**
     * Remove collapsed column state from localStorage
     */
    removeCollapsedColumn: function(status) {
        var collapsedColumns = this.getCollapsedColumns();
        var index = collapsedColumns.indexOf(status);
        if (index > -1) {
            collapsedColumns.splice(index, 1);
            localStorage.setItem('kanban-collapsed-columns', JSON.stringify(collapsedColumns));
            console.log('Updated collapsed columns:', collapsedColumns);
        }
    },

    /**
     * Get collapsed columns from localStorage
     */
    getCollapsedColumns: function() {
        var stored = localStorage.getItem('kanban-collapsed-columns');
        return stored ? JSON.parse(stored) : [];
    },

    /**
     * Restore collapsed column states from localStorage
     */
    restoreCollapsedColumns: function() {
        var self = this;
        var collapsedColumns = self.getCollapsedColumns();
        
        console.log('Restoring collapsed columns:', collapsedColumns);
        
        collapsedColumns.forEach(function(status) {
            var $column = $('.kanban-column[data-status="' + status + '"]');
            if ($column.length) {
                $column.addClass('collapsed');
                console.log('Restored collapsed state for column:', status);
            }
        });
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
            // Check if comments section exists and insert before it
            var $commentsSection = $('#taskDetailsModal .task-comments-section');
            if ($commentsSection.length > 0) {
                $commentsSection.before(imageUploadHtml);
            } else {
                // If no comments section, append to modal body
                $('#taskDetailsModal .modal-body').append(imageUploadHtml);
            }
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

    // Statistics card click handlers for deadline, completion, and category tasks modals
    $(document).on('click', '.stat-card', function(e) {
        e.preventDefault();
        var categoryKey = $(this).data('category');
        var categoryName = $(this).find('.stat-title').text().trim();
        var taskCount = $(this).find('.stat-value').text().trim();
        
        if (!categoryKey) {
            console.log('No category key found for this card');
            return;
        }
        
        // Check if this is the completion status card
        if (categoryKey === 'completion_status') {
            // Show completion tasks modal
            showCompletionTasksModal();
        } else if (categoryKey.indexOf('category_') === 0) {
            // This is a category-based statistics card
            var categoryId = categoryKey.replace('category_', '');
            if (categoryId === 'none') {
                categoryId = null;
            }
            
            // Show category completion tasks modal
            showCategoryCompletionTasksModal(categoryId, categoryName);
        } else if (categoryKey === 'targeted_today') {
            // Show targeted today tasks modal
            $('#deadlineTasksModalLabel').text(categoryName + ' Tasks');
            $('.deadline-tasks-summary').text('Showing ' + taskCount + ' tasks');
            
            // Show loading state
            $('#deadlineTasksModal .modal-body').html(
                '<div class="text-center py-4">' +
                    '<div class="spinner-border text-primary" role="status">' +
                        '<span class="sr-only">Loading...</span>' +
                    '</div>' +
                    '<p class="mt-3 text-muted">Loading tasks...</p>' +
                '</div>'
            );
            
            // Show the modal
            $('#deadlineTasksModal').modal('show');
            
            // Fetch tasks for targeted today
            fetchTargetedTodayTasks();
        } else {
            // Show deadline tasks modal
            $('#deadlineTasksModalLabel').text(categoryName + ' Tasks');
            $('.deadline-tasks-summary').text('Showing ' + taskCount + ' tasks');
            
            // Show loading state
            $('#deadlineTasksModal .modal-body').html(
                '<div class="text-center py-4">' +
                    '<div class="spinner-border text-primary" role="status">' +
                        '<span class="sr-only">Loading...</span>' +
                    '</div>' +
                    '<p class="mt-3 text-muted">Loading tasks...</p>' +
                '</div>'
            );
            
            // Show the modal
            $('#deadlineTasksModal').modal('show');
            
            // Fetch tasks for this category
            fetchDeadlineTasks(categoryKey);
        }
    });
});

function fetchDeadlineTasks(categoryKey) {
    var ajaxData = {
        category: categoryKey
    };
    
    // Add CSRF token to form data
    if (KanbanBoard.config.csrfParam && KanbanBoard.config.csrfToken) {
        ajaxData[KanbanBoard.config.csrfParam] = KanbanBoard.config.csrfToken;
    }
    
    $.ajax({
        url: KanbanBoard.config.getDeadlineTasks,
        type: 'GET',
        data: ajaxData,
        success: function(response) {
            if (response.success) {
                renderDeadlineTasks(response.tasks);
            } else {
                showDeadlineTasksError(response.message || 'Failed to load tasks');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching deadline tasks:', error);
            showDeadlineTasksError('Network error occurred while loading tasks');
        }
    });
}

function renderDeadlineTasks(tasks) {
    var container = $('#deadlineTasksModal .modal-body');
    
    if (!tasks || tasks.length === 0) {
        container.html(
            '<div class="deadline-tasks-empty">' +
                '<i class="fas fa-calendar-times"></i>' +
                '<h5>No Tasks Found</h5>' +
                '<p>There are no tasks in this deadline category.</p>' +
            '</div>'
        );
        return;
    }
    
    var html = '<div class="deadline-tasks-grid">';
    
    tasks.forEach(function(task) {
        var priorityClass = 'priority-' + (task.priority || 'medium').toLowerCase();
        var categoryColor = task.color || '#6c757d';
        var daysUntilDeadline = task.days_until_deadline;
        var deadlineText = '';
        
        if (daysUntilDeadline === 0) {
            deadlineText = '<span class="text-danger font-weight-bold">Due Today</span>';
        } else if (daysUntilDeadline < 0) {
            deadlineText = '<span class="text-danger">' + Math.abs(daysUntilDeadline) + ' days overdue</span>';
        } else {
            deadlineText = '<span class="text-muted">' + daysUntilDeadline + ' days remaining</span>';
        }
        
        html += '<div class="deadline-task-card" onclick="emphasizeTaskInBoard(' + task.id + ')">';
        html += '    <div class="deadline-task-header">';
        html += '        <h6 class="deadline-task-title">' + htmlEscape(task.title) + '</h6>';
        html += '        <span class="deadline-task-priority ' + priorityClass + '">' + (task.priority || 'Medium') + '</span>';
        html += '    </div>';
        
        html += '    <div class="deadline-task-meta">';
        html += '        <div class="deadline-task-meta-item">';
        html += '            <i class="fas fa-calendar-alt"></i>';
        html += '            <span>' + deadlineText + '</span>';
        html += '        </div>';
        if (task.assigned_to_name) {
            html += '        <div class="deadline-task-meta-item">';
            html += '            <i class="fas fa-user"></i>';
            html += '            <span>' + htmlEscape(task.assigned_to_name) + '</span>';
            html += '        </div>';
        }
        html += '    </div>';
        
        if (task.description) {
            html += '    <div class="deadline-task-description">' + htmlEscape(task.description) + '</div>';
        }
        
        html += '    <div class="deadline-task-footer">';
        html += '        <div class="deadline-task-category" style="background-color: ' + categoryColor + '">';
        html += '            <i class="' + (task.icon || 'fas fa-circle') + '"></i>';
        html += '            <span>' + htmlEscape(task.category_name || 'No Category') + '</span>';
        html += '        </div>';
        html += '        <div class="deadline-task-status">' + htmlEscape(task.status || 'To Do') + '</div>';
        html += '    </div>';
        html += '</div>';
    });
    
    html += '</div>';
    container.html(html);
}

function showDeadlineTasksError(message) {
    $('#deadlineTasksModal .modal-body').html(
        '<div class="deadline-tasks-empty">' +
            '<i class="fas fa-exclamation-triangle text-warning"></i>' +
            '<h5>Error Loading Tasks</h5>' +
            '<p>' + htmlEscape(message) + '</p>' +
            '<button class="btn btn-primary btn-sm" onclick="$(this).closest(\'.modal\').modal(\'hide\')">Close</button>' +
        '</div>'
    );
}

function fetchTargetedTodayTasks() {
    var ajaxData = {};
    
    // Add CSRF token to form data
    if (KanbanBoard.config.csrfParam && KanbanBoard.config.csrfToken) {
        ajaxData[KanbanBoard.config.csrfParam] = KanbanBoard.config.csrfToken;
    }
    
    $.ajax({
        url: KanbanBoard.config.getTargetedTodayTasks,
        type: 'GET',
        data: ajaxData,
        success: function(response) {
            if (response.success) {
                renderTargetedTodayTasks(response.tasks);
            } else {
                showDeadlineTasksError(response.message || 'Failed to load tasks');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching targeted today tasks:', error);
            showDeadlineTasksError('Network error occurred while loading tasks');
        }
    });
}

function renderTargetedTodayTasks(tasks) {
    var container = $('#deadlineTasksModal .modal-body');
    
    if (!tasks || tasks.length === 0) {
        container.html(
            '<div class="deadline-tasks-empty">' +
                '<i class="fas fa-bullseye"></i>' +
                '<h5>No Tasks Targeted for Today</h5>' +
                '<p>There are no tasks with target date ranges that include today.</p>' +
            '</div>'
        );
        return;
    }
    
    var html = '<div class="deadline-tasks-grid">';
    
    tasks.forEach(function(task) {
        var priorityClass = 'priority-' + (task.priority || 'medium').toLowerCase();
        var categoryColor = task.color || '#6c757d';
        var daysUntilDeadline = task.days_until_deadline;
        var deadlineText = '';
        
        if (daysUntilDeadline === 0) {
            deadlineText = 'Due today';
        } else if (daysUntilDeadline < 0) {
            deadlineText = Math.abs(daysUntilDeadline) + ' days overdue';
        } else {
            deadlineText = 'Due in ' + daysUntilDeadline + ' days';
        }
        
        var targetStartDate = new Date(task.target_start_date * 1000).toLocaleDateString();
        var targetEndDate = new Date(task.target_end_date * 1000).toLocaleDateString();
        
        html += '<div class="deadline-task-card" data-task-id="' + task.id + '" onclick="emphasizeTaskInBoard(' + task.id + ')">';
        html += '    <div class="deadline-task-header">';
        html += '        <h5 class="deadline-task-title">' + htmlEscape(task.title) + '</h5>';
        html += '        <span class="deadline-task-priority ' + priorityClass + '">' + (task.priority || 'Medium') + '</span>';
        html += '    </div>';
        html += '    <div class="deadline-task-meta">';
        html += '        <div class="deadline-task-meta-item">';
        html += '            <i class="fas fa-clock"></i>';
        html += '            <span>' + deadlineText + '</span>';
        html += '        </div>';
        html += '        <div class="deadline-task-meta-item">';
        html += '            <i class="fas fa-bullseye"></i>';
        html += '            <span>Target: ' + targetStartDate + ' - ' + targetEndDate + '</span>';
        html += '        </div>';
        
        if (task.assigned_to_name) {
            html += '        <div class="deadline-task-meta-item">';
            html += '            <i class="fas fa-user"></i>';
            html += '            <span>' + htmlEscape(task.assigned_to_name) + '</span>';
            html += '        </div>';
        }
        html += '    </div>';
        
        if (task.description) {
            html += '    <div class="deadline-task-description">' + htmlEscape(task.description) + '</div>';
        }
        
        html += '    <div class="deadline-task-footer">';
        html += '        <div class="deadline-task-category" style="background-color: ' + categoryColor + '">';
        html += '            <i class="' + (task.icon || 'fas fa-circle') + '"></i>';
        html += '            <span>' + htmlEscape(task.category_name || 'No Category') + '</span>';
        html += '        </div>';
        html += '        <div class="deadline-task-status">' + htmlEscape(task.status || 'To Do') + '</div>';
        html += '    </div>';
        html += '</div>';
    });
    
    html += '</div>';
    container.html(html);
}

// Function to emphasize task in board when clicked from deadline tasks modal
function emphasizeTaskInBoard(taskId) {
    console.log('Emphasizing task in board: ' + taskId);
    
    // First, close the deadline tasks modal
    $('#deadlineTasksModal').modal('hide');
    
    // Wait a bit for modal to close and DOM to settle
    setTimeout(function() {
        // Find the task card in the board
        var taskCard = $('.kanban-task[data-task-id="' + taskId + '"]');
        
        console.log('Looking for task with selector: .kanban-task[data-task-id="' + taskId + '"]');
        console.log('Found ' + taskCard.length + ' matching tasks');
        
        // Debug: Show all tasks on board
        var allTasks = $('.kanban-task[data-task-id]');
        console.log('All tasks on board:', allTasks.length);
        allTasks.each(function(i, el) {
            var id = $(el).data('task-id');
            var status = $(el).data('status'); 
            console.log('Task ' + i + ': ID=' + id + ', Status=' + status + ', Visible=' + $(el).is(':visible'));
        });
        
        if (taskCard.length === 0) {
            console.warn('Task not found in board: ' + taskId);
            
            // Try alternative selectors
            var altTask1 = $('[data-task-id="' + taskId + '"]');
            var altTask2 = $('.kanban-task').filter(function() {
                return $(this).data('task-id') == taskId;
            });
            
            console.log('Alternative selector 1 found: ' + altTask1.length);
            console.log('Alternative selector 2 found: ' + altTask2.length);
            
            if (altTask1.length > 0) {
                taskCard = altTask1.first();
                console.log('Using alternative selector 1');
            } else if (altTask2.length > 0) {
                taskCard = altTask2.first();
                console.log('Using alternative selector 2');
            } else {
                // Show a notification that the task couldn't be found
                if (typeof showNotification === 'function') {
                    showNotification('Task not found in the current board view', 'warning');
                } else {
                    alert('Task not found in the current board view');
                }
                return;
            }
        }
        
                console.log('Found task card, scrolling and emphasizing...');
                
                // First, scroll the column container to show the task
                var columnBody = taskCard.closest('.kanban-column-body');
                if (columnBody.length > 0) {
                    var taskOffsetInColumn = taskCard.position().top;
                    var columnScrollTop = columnBody.scrollTop();
                    var columnHeight = columnBody.height();
                    var taskHeight = taskCard.outerHeight();
                    
                    console.log('Column scroll info:', {
                        taskOffsetInColumn: taskOffsetInColumn,
                        columnScrollTop: columnScrollTop,
                        columnHeight: columnHeight,
                        taskHeight: taskHeight
                    });
                    
                    // Check if task is outside visible area of the column
                    if (taskOffsetInColumn < 0 || taskOffsetInColumn + taskHeight > columnHeight) {
                        // Calculate new scroll position to center the task in the column
                        var newScrollTop = columnScrollTop + taskOffsetInColumn - (columnHeight / 2) + (taskHeight / 2);
                        
                        console.log('Scrolling column to position:', newScrollTop);
                        
                        // Scroll the column smoothly
                        columnBody.animate({
                            scrollTop: newScrollTop
                        }, 600, 'swing');
                    }
                }
                
                // Then scroll the page to the column area
                $('html, body').animate({
                    scrollTop: taskCard.offset().top - 150
                }, 800, 'swing');
                
                // Add emphasis effect after scrolling completes
                setTimeout(function() {
                    taskCard.addClass('task-emphasized');
                    console.log('Added emphasis class to task: ' + taskId);
                }, 900); // Wait for both column and page scrolling to complete
                
                // Remove emphasis after 2 seconds
                setTimeout(function() {
                    taskCard.removeClass('task-emphasized');
                    console.log('Removed emphasis class from task: ' + taskId);
                }, 2900); // Adjusted timing based on new emphasis start time
        
                // Also open the task modal after the emphasis effect for better UX
                setTimeout(function() {
                    console.log('Opening task modal for: ' + taskId);
                    openTaskModal(taskId);
                }, 1500); // Wait for scrolling to complete before opening modal
        
    }, 300); // Wait for modal close animation
}

// Function to show completion tasks modal
function showCompletionTasksModal() {
    // Show loading state
    $('#completionTasksContent').html(
        '<div class="text-center py-4">' +
            '<div class="spinner-border text-primary" role="status">' +
                '<span class="sr-only">Loading...</span>' +
            '</div>' +
            '<p class="mt-3 text-muted">Loading tasks...</p>' +
        '</div>'
    );
    
    // Show the modal
    $('#completionTasksModal').modal('show');
    
    // Fetch completion tasks
    fetchCompletionTasks();
}

// Function to fetch completion tasks via AJAX
function fetchCompletionTasks() {
    var ajaxData = {};
    
    // Add CSRF token if available
    if (KanbanBoard.config.csrfParam && KanbanBoard.config.csrfToken) {
        ajaxData[KanbanBoard.config.csrfParam] = KanbanBoard.config.csrfToken;
    }
    
    $.ajax({
        url: KanbanBoard.config.getCompletionTasks,
        type: 'GET',
        data: ajaxData,
        success: function(response) {
            if (response.success) {
                renderCompletionTasks(response.tasks, response.completed_count, response.not_completed_count);
            } else {
                showCompletionTasksError(response.message || 'Failed to load tasks');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching completion tasks:', error);
            showCompletionTasksError('Network error occurred while loading tasks');
        }
    });
}

// Function to render completion tasks in the modal
function renderCompletionTasks(tasks, completedCount, notCompletedCount) {
    var container = $('#completionTasksModal .modal-body');
    
    // Update summary
    $('#completionTasksCount').text(completedCount + ' completed, ' + notCompletedCount + ' not completed');
    
    var html = '<div class="completion-tasks-container">';
    
    // Not Completed Column
    html += '<div class="completion-tasks-column">';
    html += '    <div class="completion-tasks-column-header not-completed">';
    html += '        <span><i class="fa fa-clock"></i> Not Completed</span>';
    html += '        <span class="completion-count-badge not-completed">' + notCompletedCount + '</span>';
    html += '    </div>';
    html += '    <div class="completion-tasks-column-body">';
    
    if (tasks.not_completed && tasks.not_completed.length > 0) {
        tasks.not_completed.forEach(function(task) {
            html += renderCompletionTaskCard(task, 'not-completed');
        });
    } else {
        html += '<div class="text-center text-muted py-4">';
        html += '    <i class="fa fa-check-circle fa-2x mb-2"></i>';
        html += '    <p>All tasks are completed!</p>';
        html += '</div>';
    }
    
    html += '    </div>';
    html += '</div>';
    
    // Completed Column
    html += '<div class="completion-tasks-column">';
    html += '    <div class="completion-tasks-column-header completed">';
    html += '        <span><i class="fa fa-check-circle"></i> Completed</span>';
    html += '        <span class="completion-count-badge completed">' + completedCount + '</span>';
    html += '    </div>';
    html += '    <div class="completion-tasks-column-body">';
    
    if (tasks.completed && tasks.completed.length > 0) {
        tasks.completed.forEach(function(task) {
            html += renderCompletionTaskCard(task, 'completed');
        });
    } else {
        html += '<div class="text-center text-muted py-4">';
        html += '    <i class="fa fa-hourglass-half fa-2x mb-2"></i>';
        html += '    <p>No completed tasks yet.</p>';
        html += '</div>';
    }
    
    html += '    </div>';
    html += '</div>';
    html += '</div>';
    
    container.html(html);
}

// Function to render individual completion task card
function renderCompletionTaskCard(task, type) {
    var html = '<div class="completion-task-card ' + type + '" onclick="emphasizeTaskInBoard(' + task.id + ')">';
    html += '    <div class="completion-task-title">' + htmlEscape(task.title) + '</div>';
    html += '    <div class="completion-task-meta">';
    html += '        <span class="completion-task-status ' + type + '">' + htmlEscape(task.status) + '</span>';
    
    if (task.category_name) {
        html += '        <span style="color: ' + (task.color || '#6c757d') + '">';
        html += '            <i class="' + (task.icon || 'fas fa-circle') + '"></i> ';
        html += '            ' + htmlEscape(task.category_name);
        html += '        </span>';
    }
    
    html += '    </div>';
    html += '</div>';
    
    return html;
}

// Function to show error in completion tasks modal
function showCompletionTasksError(message) {
    $('#completionTasksModal .modal-body').html(
        '<div class="alert alert-danger" role="alert">' +
            '<i class="fa fa-exclamation-triangle"></i> ' +
            '<strong>Error:</strong> ' + htmlEscape(message) +
        '</div>'
    );
}

// Function to show category completion tasks modal
function showCategoryCompletionTasksModal(categoryId, categoryName) {
    // Update modal title
    $('#categoryCompletionTasksModalLabel span').text(categoryName + ' Tasks');
    
    // Show loading state
    $('#categoryCompletionTasksContent').html(
        '<div class="text-center py-4">' +
            '<div class="spinner-border text-primary" role="status">' +
                '<span class="sr-only">Loading...</span>' +
            '</div>' +
            '<p class="mt-3 text-muted">Loading category tasks...</p>' +
        '</div>'
    );
    
    // Show the modal
    $('#categoryCompletionTasksModal').modal('show');
    
    // Fetch category completion tasks
    fetchCategoryCompletionTasks(categoryId);
}

// Function to fetch category completion tasks via AJAX
function fetchCategoryCompletionTasks(categoryId) {
    console.log('Fetching category completion tasks for categoryId:', categoryId);
    
    var ajaxData = {
        categoryId: categoryId
    };
    
    // Add CSRF token if available
    if (KanbanBoard.config.csrfParam && KanbanBoard.config.csrfToken) {
        ajaxData[KanbanBoard.config.csrfParam] = KanbanBoard.config.csrfToken;
        console.log('CSRF token added:', KanbanBoard.config.csrfParam, '=', KanbanBoard.config.csrfToken.substring(0, 20) + '...');
    }
    
    console.log('AJAX URL:', KanbanBoard.config.getCategoryCompletionTasks);
    console.log('AJAX Data:', ajaxData);
    
    $.ajax({
        url: KanbanBoard.config.getCategoryCompletionTasks,
        type: 'POST',
        data: ajaxData,
        dataType: 'json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        },
        success: function(response) {
            console.log('Category completion tasks response:', response);
            if (response.success) {
                renderCategoryCompletionTasks(response.tasks, response.completed_count, response.not_completed_count);
            } else {
                showCategoryCompletionTasksError(response.message || 'Failed to load category tasks');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching category completion tasks:', error);
            console.error('XHR Status:', xhr.status);
            console.error('XHR Response:', xhr.responseText);
            
            if (xhr.status === 403) {
                showCategoryCompletionTasksError('Access denied. Please refresh the page and try again.');
            } else {
                showCategoryCompletionTasksError('Network error occurred while loading category tasks');
            }
        }
    });
}

// Function to render category completion tasks in the modal
function renderCategoryCompletionTasks(tasks, completedCount, notCompletedCount) {
    var container = $('#categoryCompletionTasksModal .modal-body');
    
    // Update summary to show only non-completed count
    $('#categoryCompletionTasksCount').text(notCompletedCount + ' active tasks');
    
    var html = '<div class="completion-tasks-container single-column">';
    
    // Only show Not Completed Column
    html += '<div class="completion-tasks-column full-width">';
    html += '    <div class="completion-tasks-column-header not-completed">';
    html += '        <span><i class="fa fa-clock"></i> Active Tasks</span>';
    html += '        <span class="completion-count-badge not-completed">' + notCompletedCount + '</span>';
    html += '    </div>';
    html += '    <div class="completion-tasks-column-body">';
    
    if (tasks.not_completed && tasks.not_completed.length > 0) {
        tasks.not_completed.forEach(function(task) {
            html += renderCompletionTaskCard(task, 'not-completed');
        });
    } else {
        html += '<div class="text-center text-muted py-4">';
        html += '    <i class="fa fa-check-circle fa-2x mb-2"></i>';
        html += '    <p>All tasks in this category are completed!</p>';
        html += '</div>';
    }
    
    html += '    </div>';
    html += '</div>';
    
    // Remove the completed column entirely
    html += '</div>';
    
    container.html(html);
}

// Function to show error in category completion tasks modal
function showCategoryCompletionTasksError(message) {
    $('#categoryCompletionTasksModal .modal-body').html(
        '<div class="alert alert-danger" role="alert">' +
            '<i class="fa fa-exclamation-triangle"></i> ' +
            '<strong>Error:</strong> ' + htmlEscape(message) +
        '</div>'
    );
}

function htmlEscape(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}