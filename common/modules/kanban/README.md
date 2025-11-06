# Kanban Module

A powerful Kanban board module for managing tasks in a visual, drag-and-drop interface.

## Features

- **Visual Task Management**: Organize tasks in customizable columns (To Do, In Progress, Done)
- **Drag & Drop**: Move tasks between columns with intuitive drag-and-drop functionality
- **Task Prioritization**: Visual priority indicators (Low, Medium, High, Critical)
- **Category Organization**: Color-coded task categories
- **Real-time Statistics**: Dashboard showing task counts and overdue items
- **Responsive Design**: Works seamlessly on desktop and mobile devices
- **Task Details**: Quick access to task information and attachments

## Installation

The Kanban module is already integrated into your Yii2 application. To complete the setup:

1. **Run the migration** to add Kanban-specific fields:
   ```bash
   php yii migrate --migrationPath=@common/modules/kanban/migrations
   ```

2. **The module is automatically registered** in the backend configuration.

3. **Create sample data** (optional):
   ```bash
   php yii kanban/create-sample-data
   ```

## Usage

### Accessing the Kanban Board

Navigate to: `http://your-domain/backend/web/kanban/board`

### Task Management

- **Move Tasks**: Drag tasks between columns to change their status
- **View Details**: Click on any task to see detailed information
- **Priority Colors**: Tasks are color-coded by priority level
- **Category Labels**: Each task displays its category with a colored label
- **Deadline Tracking**: Overdue tasks are highlighted in red

### Column Structure

- **To Do**: Tasks with `pending` status
- **In Progress**: Tasks with `in_progress` status  
- **Done**: Tasks with `completed` status

## File Structure

```
common/modules/kanban/
├── Module.php                 # Module definition
├── controllers/
│   └── BoardController.php    # Main board controller
├── models/
│   └── KanbanBoard.php       # Board business logic
├── views/
│   └── board/
│       └── index.php         # Main Kanban board view
├── assets/
│   ├── KanbanAsset.php       # Asset bundle
│   ├── css/
│   │   └── kanban.css        # Kanban board styles
│   └── js/
│       └── kanban.js         # JavaScript functionality
└── migrations/
    └── m000000_000006_add_kanban_fields_to_tasks_table.php
```

## API Endpoints

### Update Task Status
- **URL**: `/kanban/board/update-task-status`
- **Method**: POST
- **Parameters**: 
  - `taskId`: Task ID
  - `status`: New status (pending, in_progress, completed)

### Update Task Position
- **URL**: `/kanban/board/update-task-position`
- **Method**: POST
- **Parameters**:
  - `taskId`: Task ID
  - `position`: New position
  - `status`: Current status

## Customization

### Adding New Columns

To add new task status columns, update the following:

1. **Task Model**: Add new status constants in `common/modules/taskmonitor/models/Task.php`
2. **Board Model**: Update the columns configuration in `KanbanBoard::getColumns()`
3. **CSS**: Add column-specific styles in `kanban.css`

### Styling

The Kanban board uses CSS classes for styling:

- `.kanban-board`: Main container
- `.kanban-column`: Individual columns
- `.kanban-task`: Task cards
- `.priority-{level}`: Priority-based styling
- `.task-{element}`: Task component styling

### JavaScript Events

The board fires custom events for integration:

- `task:moved`: When a task is moved between columns
- `task:updated`: When task data is updated
- `board:refreshed`: When the board is refreshed

## Dependencies

- **Yii2 Framework**: Core framework
- **jQuery UI**: Drag and drop functionality
- **Bootstrap**: UI components and responsive design
- **Font Awesome**: Icons (ensure it's included in your layout)

## Troubleshooting

### Tasks Not Draggable
- Ensure jQuery UI is loaded
- Check browser console for JavaScript errors
- Verify CSRF token is present

### Styles Not Loading
- Clear asset cache: `php yii asset/clear`
- Check asset permissions
- Verify asset paths in web directory

### AJAX Errors
- Check CSRF token configuration
- Verify module is properly registered
- Ensure user has proper permissions

## Contributing

When extending the Kanban module:

1. Follow Yii2 coding standards
2. Add appropriate unit tests
3. Update documentation
4. Test drag-and-drop functionality across browsers

## License

This module is part of the TaskViewer application and follows the same license terms.