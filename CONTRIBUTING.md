# Contributing to KanbonFlow

We welcome contributions to KanbonFlow! This document provides guidelines for contributing to the project.

## 🤝 How to Contribute

### 1. Fork the Repository
- Click the "Fork" button on the GitHub repository page
- Clone your fork locally: `git clone https://github.com/yourusername/kanbonflow.git`

### 2. Set Up Development Environment
```bash
cd kanbonflow
composer install
php init
# Configure your database in common/config/main-local.php
php yii migrate
```

### 3. Create a Feature Branch
```bash
git checkout -b feature/your-feature-name
```

### 4. Make Your Changes
- Write clean, documented code
- Follow PSR-12 coding standards
- Add appropriate comments
- Test your changes thoroughly

### 5. Submit a Pull Request
- Push your changes to your fork
- Create a pull request from your feature branch to the main repository
- Include a clear description of your changes

## 🐛 Bug Reports

When reporting bugs, please include:
- **Environment**: PHP version, database version, browser
- **Steps to reproduce**: Clear, step-by-step instructions
- **Expected behavior**: What should happen
- **Actual behavior**: What actually happens
- **Screenshots**: If applicable

## 💡 Feature Requests

For new features, please:
- Check if the feature already exists
- Describe the problem you're trying to solve
- Explain your proposed solution
- Consider backward compatibility

## 📝 Code Guidelines

### PHP Standards
- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Add PHPDoc comments for classes and methods
- Keep functions small and focused

### Frontend Standards
- Use consistent indentation (2 spaces for HTML/CSS/JS)
- Follow Bootstrap 4 conventions
- Write semantic HTML
- Use meaningful CSS class names

### Database Changes
- Always create migrations for schema changes
- Use descriptive migration names
- Test migrations both up and down
- Update model annotations when adding fields

## 🧪 Testing

Before submitting:
- Test your changes in multiple browsers
- Verify database migrations work correctly
- Check responsive design on different screen sizes
- Test drag-and-drop functionality thoroughly

## 📋 Pull Request Checklist

- [ ] Code follows project style guidelines
- [ ] Self-review of code completed
- [ ] Changes are tested and working
- [ ] Database migrations included (if applicable)
- [ ] Documentation updated (if applicable)
- [ ] No breaking changes (or clearly documented)

## 🎯 Development Priorities

Current focus areas:
1. **Performance improvements**: Database query optimization
2. **User experience**: Mobile responsiveness enhancements
3. **Security**: Additional validation and sanitization
4. **Testing**: Unit and integration test coverage
5. **Documentation**: Code examples and tutorials

## 💬 Questions?

- Create an issue for questions about contributing
- Join discussions in existing issues
- Check the README.md for setup instructions

Thank you for contributing to KanbonFlow! 🚀