# Skylearn Billing Pro - Testing Suite

This directory contains the comprehensive testing suite for Skylearn Billing Pro, implemented as part of Phase 17.

## Directory Structure

```
tests/
├── bootstrap.php           # Test environment bootstrap
├── helpers.php            # Test helper functions and utilities
├── run-tests.sh          # Test runner script
├── unit/                 # Unit tests
│   ├── test-lms-manager.php
│   ├── test-payment-manager.php
│   ├── test-webhook-handler.php
│   ├── test-automation-manager.php
│   ├── test-portal.php
│   ├── test-subscription-manager.php
│   └── test-user-enrollment.php
└── integration/          # Integration tests
    └── test-payment-integration.php
```

## Running Tests

### Prerequisites

1. PHPUnit installed and available in PATH
2. WordPress test environment (optional, tests will run with mocks if not available)

### Quick Start

```bash
# Run all unit tests
./tests/run-tests.sh

# Run with coverage report
./tests/run-tests.sh --coverage

# Run integration tests
./tests/run-tests.sh --integration

# Run with verbose output
./tests/run-tests.sh --verbose
```

### Using WP-CLI (if available)

```bash
# Run unit tests
wp skylearn test:unit

# Run unit tests with coverage
wp skylearn test:unit --coverage

# Run integration tests
wp skylearn test:integration

# Run performance tests
wp skylearn test:performance

# Generate test data
wp skylearn generate:testdata --users=50 --orders=100

# Check plugin health
wp skylearn health:check
```

### Using PHPUnit Directly

```bash
# Run all tests
phpunit

# Run only unit tests
phpunit tests/unit/

# Run specific test file
phpunit tests/unit/test-lms-manager.php

# Run with coverage
phpunit --coverage-html tests/coverage
```

## Test Categories

### Unit Tests

Test individual classes and functions in isolation:

- **LMS Manager** (`test-lms-manager.php`): Tests LMS detection, course enrollment, and connector functionality
- **Payment Manager** (`test-payment-manager.php`): Tests payment gateway integration, processing, and validation
- **Webhook Handler** (`test-webhook-handler.php`): Tests webhook processing, security, and automation triggers
- **Automation Manager** (`test-automation-manager.php`): Tests workflow creation, execution, and management
- **Portal** (`test-portal.php`): Tests customer portal functionality, authentication, and data access
- **Subscription Manager** (`test-subscription-manager.php`): Tests subscription lifecycle, billing, and management
- **User Enrollment** (`test-user-enrollment.php`): Tests user creation, enrollment, and role management

### Integration Tests

Test complete workflows and component interactions:

- **Payment Integration** (`test-payment-integration.php`): End-to-end payment processing, webhook handling, and user enrollment

## Test Coverage

### Target Coverage

- **Core Modules**: 80% minimum
- **Payment Processing**: 90% minimum
- **Security Functions**: 95% minimum
- **User Management**: 85% minimum

### Coverage Reports

Coverage reports are generated in multiple formats:

- **HTML Report**: `tests/coverage/index.html`
- **Text Report**: `tests/coverage.txt`
- **XML Report**: `tests/results.xml`

## Writing Tests

### Test Class Structure

All test classes should extend `SkyLearn_Billing_Pro_Test_Case`:

```php
<?php
class Test_Your_Module extends SkyLearn_Billing_Pro_Test_Case {
    
    protected function setUp(): void {
        parent::setUp();
        // Your setup code here
    }
    
    public function test_your_functionality() {
        // Your test code here
        $this->assertTrue(true);
    }
}
```

### Helper Functions

The base test class provides several helper functions:

- `create_mock_user($args)`: Create mock user data
- `create_mock_payment($args)`: Create mock payment data
- `create_mock_webhook($args)`: Create mock webhook data
- `assertLogEntryCreated($type, $message)`: Assert log entry was created
- `assertEmailSent($email, $subject)`: Assert email was sent

### Mocking

Tests use WordPress function mocks for standalone testing:

- `get_option()` and `update_option()` are mocked
- `wp_mail()` is mocked and logs emails for testing
- `wp_create_user()` returns mock user IDs

## Test Data Management

### Generate Test Data

```bash
# Generate test data for development
wp skylearn generate:testdata --users=50 --orders=100 --subscriptions=25

# Clean up test data
wp skylearn cleanup:testdata --confirm
```

### Mock Data

Tests use consistent mock data structures:

- **Users**: Test users with predictable email patterns
- **Payments**: Realistic payment amounts and currencies
- **Webhooks**: Standard webhook event structures

## Continuous Integration

### Automated Testing

Tests are designed to run in CI environments:

- No external dependencies required
- Mocked WordPress functions for standalone execution
- Consistent test data and assertions

### Quality Gates

Before merging code:

1. All unit tests must pass
2. Coverage targets must be met
3. No critical or high-priority bugs
4. Code style checks must pass

## Troubleshooting

### Common Issues

**PHPUnit not found**
```bash
# Install PHPUnit globally
composer global require phpunit/phpunit

# Or use project-specific installation
composer require --dev phpunit/phpunit
```

**WordPress functions not available**
- Tests include fallback mocks for WordPress functions
- Full WordPress test environment is optional

**Test failures in CI**
- Check that all test dependencies are available
- Verify mock data is consistent
- Ensure no real API calls are made in tests

### Debug Mode

Run tests with verbose output to debug issues:

```bash
./tests/run-tests.sh --verbose
```

Or use PHPUnit directly:

```bash
phpunit --verbose --debug tests/unit/test-specific-file.php
```

## Contributing

### Adding New Tests

1. Create test file in appropriate directory (`unit/` or `integration/`)
2. Follow naming convention: `test-module-name.php`
3. Extend `SkyLearn_Billing_Pro_Test_Case`
4. Include comprehensive test coverage
5. Add documentation for complex test scenarios

### Test Standards

- One test class per module/component
- Descriptive test method names (`test_functionality_description`)
- Comprehensive assertions with meaningful error messages
- Use helper functions for common operations
- Mock external dependencies consistently

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Testing Handbook](https://make.wordpress.org/core/handbook/testing/)
- [PHP Unit Testing Best Practices](https://phpunit.de/manual/current/en/appendixes.best-practices.html)