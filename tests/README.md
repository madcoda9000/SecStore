# SecStore Test Suite

Comprehensive test coverage for SecStore authentication framework.

## 📋 Test Structure

```
tests/
├── Unit/                      # Unit tests (isolated components)
│   ├── SessionUtilTest.php
│   ├── RateLimiterTest.php
│   └── ...
├── Integration/               # Integration tests (multiple components)
│   ├── AuthenticationTest.php
│   └── ...
├── Feature/                   # Feature tests (complete workflows)
│   ├── LoginFlowTest.php
│   └── ...
├── bootstrap.php             # Test environment setup
├── TestCase.php              # Base test class
└── README.md                 # This file
```

## 🚀 Quick Start

### Install Test Dependencies

```bash
composer install
```

This installs:
- PHPUnit 11.5+ (test framework)
- Mockery (mocking library)
- Faker (test data generator)

### Run All Tests

```bash
# Run all tests with nice output
composer test

# Alternative
vendor/bin/phpunit --testdox
```

### Run Specific Test Suites

```bash
# Unit tests only
composer test:unit

# Integration tests only
composer test:integration

# Specific test file
vendor/bin/phpunit tests/Unit/SessionUtilTest.php

# Specific test method
composer test:filter it_validates_csrf_tokens
```

## 📊 Code Coverage

### Generate Coverage Report

```bash
# HTML coverage report (requires Xdebug)
composer test:coverage

# Opens in: coverage/index.html
```

### Enable Xdebug (if not installed)

```bash
# Ubuntu/Debian
sudo apt install php8.3-xdebug

# Fedora
sudo dnf install php-xdebug

# Verify
php -m | grep xdebug
```

## 🧪 Writing Tests

### Test Naming Convention

```php
/** @test */
public function itValidatesUserCredentials(): void
{
    // Arrange
    $credentials = ['username' => 'test', 'password' => 'pass'];
    
    // Act
    $result = $validator->validate($credentials);
    
    // Assert
    $this->assertTrue($result);
}
```

### Use TestCase Base Class

```php
use Tests\TestCase;

class MyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Your setup code
    }
}
```

### Available Helpers

```php
// Mock server variables
$this->mockServerVariables(['REQUEST_URI' => '/test']);

// Set POST data
$this->withPostData(['username' => 'test']);

// Set session data
$this->withSession(['user_id' => 1]);

// Create mock user
$user = $this->createMockUser(['role' => 'admin']);

// Session assertions
$this->assertSessionHas('authenticated');
$this->assertSessionMissing('error');

// Array assertions
$this->assertArrayHasKeys(['id', 'name'], $user);
```

## 🔍 Test Categories

### Unit Tests
Test individual components in isolation.

**Example:** `SessionUtilTest.php`
- Tests SessionUtil methods independently
- No database or external dependencies
- Fast execution

### Integration Tests
Test multiple components working together.

**Example:** `AuthenticationTest.php`
- Tests authentication flow with session and validation
- May use mocked database
- Moderate execution time

### Feature Tests
Test complete user workflows end-to-end.

**Example:** `LoginFlowTest.php`
- Tests full login process (form → validation → session → redirect)
- Closest to real user experience
- Slower execution

## 🐛 Debugging Tests

### Verbose Output

```bash
vendor/bin/phpunit --testdox --verbose
```

### Stop on Failure

```bash
vendor/bin/phpunit --stop-on-failure
```

### Debug Single Test

```bash
# Add to test method
$this->dumpSession(); // Dump session data
var_dump($result);    // Dump variables
```

### PHPUnit Configuration

See `phpunit.xml` for:
- Test suites
- Code coverage settings
- Environment variables
- Logging options

## 🔒 Testing Security Features

### Testing Rate Limiting

```php
// Test successful requests
for ($i = 0; $i < 5; $i++) {
    $this->assertTrue($rateLimiter->checkLimit('test'));
}

// Test blocked request
$this->assertFalse($rateLimiter->checkLimit('test'));
```

### Testing CSRF Protection

```php
$token = SessionUtil::getCsrfToken();
$this->assertTrue(SessionUtil::validateCsrfToken($token));
$this->assertFalse(SessionUtil::validateCsrfToken('invalid'));
```

### Testing Authentication

```php
SessionUtil::set('authenticated', true);
$this->assertSessionHas('authenticated');
$this->assertTrue(SessionUtil::get('authenticated'));
```

## 📝 Best Practices

1. **One assertion per test** (when possible)
2. **Use descriptive test names** (`itValidatesEmailFormat`)
3. **Follow AAA pattern** (Arrange, Act, Assert)
4. **Test edge cases** (empty strings, null values, max limits)
5. **Clean up after tests** (done automatically in `tearDown()`)
6. **Mock external dependencies** (database, APIs, email)

## 🔄 Continuous Integration

### GitHub Actions

Tests run automatically on:
- Every push to `main` branch
- Every pull request
- Scheduled daily runs

### Local Pre-commit Hook

```bash
# Run tests before committing
cp tests/hooks/pre-commit .git/hooks/
chmod +x .git/hooks/pre-commit
```

## 📞 Support

**Issues with tests?**
- Check `tests/reports/` for detailed logs
- Ensure `tests/bootstrap.php` ran successfully
- Verify PHP version: `php --version` (requires 8.3+)
- Check PHPUnit version: `vendor/bin/phpunit --version`

**Need help?**
- [GitHub Issues](https://github.com/madcoda9000/SecStore/issues)
- [Discussions](https://github.com/madcoda9000/SecStore/discussions)

## 🎯 Next Steps

1. **Run tests:** `composer test`
2. **Check coverage:** `composer test:coverage`
3. **Write new tests** for your features
4. **Aim for 30%+ coverage** (See [TESTING.md](TESTING.md))

---