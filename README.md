# KanbonFlow - Task Management System

A comprehensive task management system built with Yii2 Advanced Template featuring a modern Kanban board interface, rich task management capabilities, and dynamic category-based organization.

## 🚀 Features

### 📋 Kanban Board
- **Visual Task Management**: Drag-and-drop interface for intuitive task organization
- **Dynamic Columns**: Add, edit, and delete custom workflow columns
- **Real-time Updates**: AJAX-powered interactions with instant feedback
- **Category-Based Colors**: Tasks display with colors based on their category
- **Responsive Design**: Works seamlessly on desktop and mobile devices

### 🎯 Task Management
- **Complete CRUD Operations**: Create, read, update, and delete tasks
- **Rich Task Details**: Title, description, category, priority, deadline, and assignment
- **Priority Levels**: Low, Medium, High, and Critical priority settings
- **Status Tracking**: Pending, In Progress, Completed, and Cancelled states
- **Deadline Management**: Visual indicators for overdue and upcoming tasks
- **File Attachments**: Support for task-related image uploads

### 🎨 Category System
- **Hierarchical Categories**: Support for main categories and subcategories
- **Custom Colors**: Assign unique colors to each category for visual organization
- **Color Picker Interface**: Easy-to-use color selection with live preview
- **Category Statistics**: Track task counts and activity per category

### 🔧 Advanced Features
- **3-Column Layout**: Organized interface with categories, tasks, and details
- **Rich Text Editor**: CKEditor integration for detailed task descriptions
- **Image Management**: Drag-and-drop image uploads with preview
- **Color-Coded Urgency**: Dynamic color changes based on deadline proximity
- **Search and Filtering**: Find tasks quickly across categories
- **User Authentication**: Secure access with Yii2's built-in security

## 🛠 Technical Stack

- **Framework**: Yii2 Advanced Application Template
- **Frontend**: Bootstrap 4, jQuery, Native HTML5 Drag & Drop
- **Backend**: PHP 7.4+, MySQL/MariaDB
- **Rich Editor**: CKEditor 4
- **Icons**: Font Awesome
- **Architecture**: MVC pattern with modular design

## 📦 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB
- Composer
- Web server (Apache/Nginx)

### Quick Setup

1. **Clone the Repository**
   ```bash
   git clone https://github.com/madbon/kanbonflow.git
   cd kanbonflow
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Initialize Application**
   ```bash
   php init
   ```
   Choose `Development` environment when prompted.

4. **Configure Database**
   Edit `common/config/main-local.php`:
   ```php
   'db' => [
       'dsn' => 'mysql:host=localhost;dbname=taskviewer',
       'username' => 'your_username',
       'password' => 'your_password',
   ],
   ```

5. **Run Migrations**
   ```bash
   php yii migrate
   ```

6. **Set Web Root**
   Point your web server to `backend/web/` for the admin interface.

### Docker Setup (Alternative)

```bash
docker-compose up -d
```

## 🎯 Usage

### Accessing the Application

- **Backend Admin**: `http://localhost/backend/web/`
- **Kanban Board**: `http://localhost/backend/web/index.php?r=kanban/board`
- **Task Monitor**: `http://localhost/backend/web/index.php?r=taskmonitor`

### Quick Start Guide

1. **Create Categories**
   - Navigate to Task Monitor
   - Add main categories for your projects
   - Set custom colors for visual organization

2. **Set Up Kanban Columns**
   - Go to the Kanban Board
   - Add custom columns for your workflow (e.g., "To Do", "In Progress", "Review", "Done")
   - Configure column colors and icons

3. **Create Tasks**
   - Click "Add New Task" in any Kanban column
   - Fill in task details: title, description, category, priority, deadline
   - Tasks automatically appear in the correct column

4. **Manage Tasks**
   - Drag tasks between columns to update status
   - Click the edit icon to modify task details
   - Use the delete icon to remove completed tasks

## 🔧 Project Structure

```
kanbonflow/
├── backend/                 # Backend application
│   ├── controllers/        # Backend controllers
│   ├── views/             # Backend views
│   └── web/               # Web accessible files
├── common/                 # Shared components
│   ├── modules/           # Custom modules
│   │   ├── kanban/        # Kanban board module
│   │   └── taskmonitor/   # Task management module
│   ├── models/            # Shared models
│   └── config/            # Shared configuration
├── console/               # Console application
│   ├── controllers/       # Console controllers
│   └── migrations/        # Database migrations
├── frontend/              # Frontend application (future use)
└── vendor/                # Composer dependencies
```

## 🎨 Customization

### Adding Custom Colors
1. Navigate to Categories management
2. Click "Edit" on any category
3. Use the color picker to select a custom color
4. Save changes - tasks will automatically update

### Custom Workflow Columns
1. Go to the Kanban Board
2. Click "Add Column" 
3. Define column name, color, and icon
4. Drag tasks to the new column as needed

### Theming
- Modify `common/modules/kanban/assets/css/kanban.css` for Kanban styling
- Edit Bootstrap variables in your custom CSS for global theming

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

## 🐛 Bug Reports & Feature Requests

Please use the [GitHub Issues](https://github.com/madbon/kanbonflow/issues) page to report bugs or request new features.

## 📊 Screenshots

### Kanban Board Interface
![Kanban Board](docs/screenshots/kanban-board.png)

### Task Management
![Task Management](docs/screenshots/task-management.png)

### Category Management
![Category Colors](docs/screenshots/category-colors.png)

## 🔮 Roadmap

- [ ] User management and authentication
- [ ] Team collaboration features
- [ ] Time tracking integration
- [ ] Advanced reporting and analytics
- [ ] REST API for mobile app integration
- [ ] Real-time notifications
- [ ] Export functionality (PDF, Excel)
- [ ] Integration with external tools (Slack, Trello, etc.)

## 💡 Support

If you find this project helpful, please consider:
- ⭐ Starring the repository
- 🐛 Reporting bugs
- 💡 Suggesting new features
- 🤝 Contributing to the codebase

---

**Made with ❤️ by [madbon](https://github.com/madbon)**