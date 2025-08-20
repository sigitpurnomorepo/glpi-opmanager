# Contributing to GLPI OPManager Integration Plugin

Thank you for your interest in contributing to the GLPI OPManager Integration Plugin! This document provides guidelines and information for contributors.

## 🚀 Getting Started

### Prerequisites
- PHP 7.4 or higher
- Composer
- Git
- MySQL/MariaDB
- GLPI development environment
- OPManager test environment (optional)

### Development Setup
1. Fork the repository
2. Clone your fork locally
3. Install dependencies: `composer install`
4. Set up a local GLPI instance
5. Install the plugin in development mode

## 📋 Development Guidelines

### Code Style
- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Use meaningful variable and function names
- Add comprehensive comments for complex logic
- Keep functions small and focused

### PHP Standards
- Use strict typing where possible
- Implement proper error handling
- Use dependency injection when appropriate
- Follow GLPI coding conventions

### Database
- Use prepared statements for all queries
- Follow GLPI table naming conventions
- Include proper indexes for performance
- Document schema changes in CHANGELOG.md

## 🧪 Testing

### Unit Tests
- Write tests for all new functionality
- Maintain test coverage above 80%
- Use PHPUnit for testing framework
- Mock external dependencies

### Integration Tests
- Test webhook endpoints
- Verify database operations
- Test configuration changes
- Validate error handling

### Manual Testing
- Test in different GLPI versions
- Verify OPManager integration
- Test various alarm scenarios
- Validate security features

## 🔧 Development Workflow

### 1. Create Feature Branch
```bash
git checkout -b feature/your-feature-name
```

### 2. Make Changes
- Implement your feature
- Add/update tests
- Update documentation
- Follow coding standards

### 3. Test Your Changes
```bash
composer test
composer phpstan
composer phpcs
```

### 4. Commit Changes
```bash
git add .
git commit -m "feat: add your feature description"
```

### 5. Push and Create PR
```bash
git push origin feature/your-feature-name
# Create Pull Request on GitHub
```

## 📝 Commit Message Format

We use [Conventional Commits](https://www.conventionalcommits.org/) format:

```
type(scope): description

[optional body]

[optional footer]
```

### Types
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

### Examples
```
feat(webhook): add support for custom alarm fields
fix(sync): resolve ticket status mapping issue
docs(readme): update installation instructions
```

## 🐛 Bug Reports

### Before Reporting
1. Check existing issues
2. Verify the bug in latest version
3. Test in clean environment
4. Gather relevant information

### Bug Report Template
```markdown
**Description**
Brief description of the issue

**Steps to Reproduce**
1. Step 1
2. Step 2
3. Step 3

**Expected Behavior**
What should happen

**Actual Behavior**
What actually happens

**Environment**
- GLPI Version: X.X.X
- Plugin Version: X.X.X
- PHP Version: X.X.X
- Database: MySQL/MariaDB X.X.X
- OPManager Version: X.X.X

**Additional Information**
Logs, screenshots, etc.
```

## 💡 Feature Requests

### Before Requesting
1. Check if feature already exists
2. Verify it aligns with project goals
3. Consider implementation complexity
4. Gather community feedback

### Feature Request Template
```markdown
**Feature Description**
Detailed description of the requested feature

**Use Case**
Why this feature is needed

**Proposed Implementation**
How you think it should work

**Alternatives Considered**
Other approaches you've thought about

**Additional Context**
Any other relevant information
```

## 🔒 Security

### Reporting Security Issues
- **DO NOT** create public issues for security vulnerabilities
- Email security issues to: security@yourdomain.com
- Include detailed reproduction steps
- Allow time for response before disclosure

### Security Guidelines
- Never commit sensitive information
- Use secure coding practices
- Validate all user inputs
- Implement proper authentication
- Follow OWASP guidelines

## 📚 Documentation

### Code Documentation
- Document all public methods
- Include parameter descriptions
- Provide usage examples
- Update when changing functionality

### User Documentation
- Keep README.md current
- Update installation guides
- Document configuration options
- Provide troubleshooting tips

## 🤝 Community Guidelines

### Be Respectful
- Treat all contributors with respect
- Provide constructive feedback
- Be patient with newcomers
- Help others when possible

### Communication
- Use clear, concise language
- Ask questions when unsure
- Share knowledge and experiences
- Participate in discussions

## 🏆 Recognition

### Contributors
- All contributors are listed in CONTRIBUTORS.md
- Significant contributions are highlighted
- Regular contributors may become maintainers

### Types of Contributions
- Code contributions
- Documentation improvements
- Bug reports and fixes
- Feature suggestions
- Testing and feedback
- Community support

## 📞 Getting Help

### Resources
- [GLPI Documentation](https://glpi-project.org/documentation/)
- [OPManager Documentation](https://www.opmanager.com/support/)
- [Plugin Wiki](https://github.com/yourusername/glpi-opmanager/wiki)
- [Community Forum](https://forum.glpi-project.org/)

### Contact
- **Issues**: [GitHub Issues](https://github.com/yourusername/glpi-opmanager/issues)
- **Discussions**: [GitHub Discussions](https://github.com/yourusername/glpi-opmanager/discussions)
- **Email**: support@yourdomain.com

## 📋 Checklist for Contributors

Before submitting your contribution, ensure you have:

- [ ] Followed coding standards
- [ ] Added/updated tests
- [ ] Updated documentation
- [ ] Tested your changes
- [ ] Used conventional commit format
- [ ] Included necessary information in PR description
- [ ] Responded to review feedback
- [ ] Verified no breaking changes

## 🎯 Project Goals

Our main objectives are:
1. **Reliability**: Stable, production-ready integration
2. **Security**: Secure by design
3. **Performance**: Efficient and scalable
4. **Usability**: Easy to configure and use
5. **Maintainability**: Clean, well-documented code

## 🙏 Thank You

Thank you for contributing to the GLPI OPManager Integration Plugin! Your contributions help make this project better for everyone in the GLPI and OPManager communities.

---

**Happy Coding! 🚀**

