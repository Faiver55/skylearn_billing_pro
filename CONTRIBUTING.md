# Contributing to Skylearn Billing Pro

Thank you for your interest in contributing to Skylearn Billing Pro! We welcome contributions from the community and appreciate your help in making this plugin better.

## Getting Started

1. **Fork the repository** on GitHub
2. **Clone your fork** locally
3. **Create a new branch** for your feature or bug fix
4. **Make your changes** following our coding standards
5. **Test your changes** thoroughly
6. **Submit a pull request** with a clear description

## Code of Conduct

We are committed to providing a welcoming and inclusive experience for everyone. Please be respectful and professional in all interactions.

## Development Setup

### Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Node.js and npm (for development tools)
- Git

### Local Development

1. Set up a local WordPress development environment
2. Clone the plugin into your `wp-content/plugins/` directory
3. Install development dependencies (if any)
4. Activate the plugin in your WordPress admin

## Coding Standards

### PHP Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use proper DocBlocks for all functions and classes
- Sanitize and validate all user inputs
- Use WordPress APIs and hooks appropriately

### JavaScript Standards

- Follow WordPress JavaScript coding standards
- Use ES6+ features where appropriate
- Ensure browser compatibility

### CSS Standards

- Follow our [Style Guide](STYLE_GUIDE.md) for colors and typography
- Use clean, semantic CSS
- Ensure responsive design principles

## Branding Guidelines

Please refer to our [Style Guide](STYLE_GUIDE.md) for:
- Brand colors and their usage
- Typography guidelines
- UI component styling
- Logo and icon usage

## Testing

### Manual Testing

- Test all functionality in different WordPress environments
- Verify compatibility with popular themes and plugins
- Test payment gateway integrations thoroughly
- Ensure responsive design works on all devices

### Automated Testing

- Write unit tests for new functionality
- Ensure all existing tests pass
- Test security and validation functions

## Security

### Reporting Security Issues

If you discover a security vulnerability, please email us directly at security@skyian.com instead of opening a public issue.

### Security Best Practices

- Always sanitize and validate user inputs
- Use WordPress nonces for form submissions
- Follow WordPress security guidelines
- Never commit sensitive information (API keys, passwords)

## Payment Gateway Development

### Stripe Integration

- Use the latest Stripe PHP SDK
- Implement proper webhook handling
- Follow Stripe's security best practices
- Test with Stripe's test mode

### Lemon Squeezy Integration

- Use Lemon Squeezy's official API
- Implement proper webhook validation
- Test thoroughly with sandbox environment

## Documentation

- Update documentation for any new features
- Include code examples where helpful
- Keep the README.md file current
- Update inline code comments

## Pull Request Process

1. **Create a descriptive title** for your pull request
2. **Provide a detailed description** of your changes
3. **Reference any related issues** using GitHub keywords
4. **Include screenshots** for UI changes
5. **Ensure all tests pass**
6. **Request review** from maintainers

### Pull Request Template

```markdown
## Description
Brief description of the changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Manual testing completed
- [ ] All existing tests pass
- [ ] New tests added (if applicable)

## Screenshots (if applicable)
Add screenshots to help explain your changes

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Code is commented where necessary
- [ ] Documentation updated
```

## Questions and Support

- **General Questions**: Open an issue with the `question` label
- **Bug Reports**: Use the bug report template
- **Feature Requests**: Use the feature request template
- **Development Help**: Contact us at support@skyian.com

## Contact

- **Email**: support@skyian.com
- **Website**: [https://skyian.com/skylearn-billing/](https://skyian.com/skylearn-billing/)
- **Documentation**: [https://skyian.com/skylearn-billing/doc/](https://skyian.com/skylearn-billing/doc/)

## License

By contributing to Skylearn Billing Pro, you agree that your contributions will be licensed under the GPLv3 License.

---

Thank you for contributing to Skylearn Billing Pro!

© 2024 Skyian LLC