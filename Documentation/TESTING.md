# Testing Strategy

## 📊 Coverage Overview

| Component | Coverage | Status |
|-----------|----------|--------|
| **Utils & Models** | 85%+ | ✅ Excellent |
| **Middleware** | 80%+ | ✅ Excellent |
| **Business Logic** | 85%+ | ✅ Fully tested |
| **Controllers** | ~5% | ⚠️ Critical paths only |
| **Overall Project** | 30%+ | ✅ Pragmatic |

[![codecov](https://codecov.io/gh/madcoda9000/SecStore/branch/main/graph/badge.svg)](https://codecov.io/gh/madcoda9000/SecStore)

## 🎯 Testing Philosophy

SecStore follows a **pragmatic testing approach** that focuses on maximizing value while minimizing maintenance burden.

### What IS Thoroughly Tested ✅

- **Session Management** (`SessionUtil`) - 90%+ coverage
- **Rate Limiting** (`RateLimiter`) - 85%+ coverage  
- **Security Metrics** (`SecurityMetrics`, `LoginAnalytics`) - 90%+ coverage
- **Input Validation** (`InputValidator`) - 95%+ coverage
- **LDAP Authentication** (`LdapUtil`) - 80%+ coverage
- **User Model** - 85%+ coverage
- **Middleware** (CSRF, Admin checks) - 80%+ coverage
- **Translation System** (`TranslationUtil`) - 90%+ coverage
- **Logging Utilities** (`LogUtil`) - 85%+ coverage

### What is NOT Tested ❌

- **Controller rendering logic** - Framework glue code (Flight routes, Latte rendering)
- **Simple CRUD operations** - Trivial pass-through to models
- **UI logic** - Better tested via E2E tests or manual QA

## 🤔 Why Not 70%+ Coverage?

Controllers in SecStore primarily contain:

1. **Framework integration code**
   ```php
   Flight::latte()->render('profile.latte', [...]);
   Flight::redirect('/dashboard');
   ```

2. **Simple CRUD operations**
   ```php
   $user = User::findById($id);
   Flight::json($user);
   ```

3. **Request/Response handling**

Testing this code requires extensive mocking of:
- Flight framework methods
- Latte template engine
- Database connections
- Session management

**The effort-to-value ratio is extremely poor.** These tests would be:
- Brittle (break on every refactor)
- Slow (heavy mocking overhead)
- Low value (don't catch real bugs)

## ✅ Our Approach Instead

**We test the business logic, not the framework:**

- ✅ Extract complex logic into testable Utils
- ✅ Test all business rules and validations
- ✅ Test security-critical code paths
- ❌ Don't test framework glue code

**Example:**

```php
// ❌ Hard to test - tightly coupled to framework
public function register() {
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        Flight::json(['error' => 'Invalid email']);
        return;
    }
    // ...
}

// ✅ Easy to test - business logic extracted
public function register() {
    $validator = new InputValidator();
    $data = $validator->validate($_POST, self::getRegistrationRules());
    // ...
}
```

## 🧪 Running Tests

```bash
# Run all tests
composer test

# Run with coverage (requires Xdebug)
composer test:coverage

# Run specific suite
composer test:unit
composer test:integration

# Run specific test file
vendor/bin/phpunit tests/Unit/SessionUtilTest.php

# Run specific test method
composer test:filter it_validates_csrf_tokens
```

## 📈 Coverage Goals

- **Current:** 40%+ overall, 85%+ for business logic
- **Target:** Maintain 85%+ for Utils/Models, add tests for new business logic
- **Not a goal:** 70%+ overall (would require testing framework glue code)

## 🎓 For Contributors

When adding new features:

- ✅ **DO** write tests for new Utils/Models
- ✅ **DO** write tests for business logic in Controllers
- ✅ **DO** write integration tests for critical workflows
- ❌ **DON'T** write tests for simple CRUD operations
- ❌ **DON'T** write tests that just mock `Flight::render()`

### Example: Adding a New Feature

**Bad approach (hard to test):**
```php
// AdminController.php
public function exportUsers() {
    $users = User::getAllUsers();
    $csv = "Name,Email\n";
    foreach ($users as $user) {
        $csv .= $user->name . "," . $user->email . "\n";
    }
    Flight::response()->header('Content-Type', 'text/csv');
    echo $csv;
}
```

**Good approach (easy to test):**
```php
// Utils/CsvExporter.php
class CsvExporter {
    public static function exportUsers(array $users): string {
        $csv = "Name,Email\n";
        foreach ($users as $user) {
            $csv .= $user->name . "," . $user->email . "\n";
        }
        return $csv;
    }
}

// AdminController.php
public function exportUsers() {
    $users = User::getAllUsers();
    $csv = CsvExporter::exportUsers($users);
    Flight::response()->header('Content-Type', 'text/csv');
    echo $csv;
}

// tests/Unit/CsvExporterTest.php
public function testExportUsers() {
    $users = [
        (object)['name' => 'John', 'email' => 'john@example.com'],
        (object)['name' => 'Jane', 'email' => 'jane@example.com'],
    ];
    
    $result = CsvExporter::exportUsers($users);
    
    $this->assertStringContainsString('John,john@example.com', $result);
    $this->assertStringContainsString('Jane,jane@example.com', $result);
}
```

## 🔍 Code Review Checklist

Before merging new code, ensure:

- [ ] New Utils/Models have >80% test coverage
- [ ] Business logic is extracted from Controllers into testable classes
- [ ] Security-critical code paths are tested
- [ ] Tests follow the AAA pattern (Arrange, Act, Assert)
- [ ] Test names are descriptive (`it_validates_email_format`)
- [ ] Edge cases are covered (empty strings, null values, max limits)

## 📚 Further Reading

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Test-Driven Development](https://martinfowler.com/bliki/TestDrivenDevelopment.html)
- [When to Mock](https://blog.cleancoder.com/uncle-bob/2014/05/14/TheLittleMocker.html)
- [The Practical Test Pyramid](https://martinfowler.com/articles/practical-test-pyramid.html)

## 📞 Questions?

If you have questions about testing strategy or need help writing tests:

- [Open an issue](https://github.com/madcoda9000/SecStore/issues)
- [Start a discussion](https://github.com/madcoda9000/SecStore/discussions)
- Check existing tests in `tests/Unit/` for examples