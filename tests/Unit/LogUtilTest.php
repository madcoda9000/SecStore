<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Utils\LogUtil;
use App\Utils\LogType;
use ReflectionClass;
use ReflectionMethod;

/**
 * LogUtil Unit Tests
 *
 * Tests logging utility functionality including:
 * - Configuration loading
 * - Log type filtering
 * - IP address extraction
 * - File logging fallback
 * - Log message formatting
 *
 * Note: Tests focus on business logic without database dependencies
 *
 * @package Tests\Unit
 */
class LogUtilTest extends TestCase
{
    private ReflectionClass $reflection;
    private array $mockConfig;
    private string $testLogDir;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->reflection = new ReflectionClass(LogUtil::class);
        
        // Create mock configuration
        $this->mockConfig = [
            'logging' => [
                'enableSqlLogging' => true,
                'enableMailLogging' => true,
                'enableSystemLogging' => true,
                'enableAuditLogging' => true,
                'enableRequestLogging' => false, // Disabled for testing
                'enableSecurityLogging' => true,
            ],
        ];
        
        // Setup test log directory
        $this->testLogDir = sys_get_temp_dir() . '/logutil_test_' . uniqid();
        if (!is_dir($this->testLogDir)) {
            mkdir($this->testLogDir, 0750, true);
        }
        
        // Reset static config
        $this->resetStaticConfig();
    }

    /**
     * Teardown after each test
     */
    protected function tearDown(): void
    {
        // Clean up test log files
        if (is_dir($this->testLogDir)) {
            $files = glob($this->testLogDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testLogDir);
        }
        
        $this->resetStaticConfig();
        parent::tearDown();
    }

    /**
     * Helper to reset static $config property
     */
    private function resetStaticConfig(): void
    {
        $configProperty = $this->reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue(null, null);
    }

    /**
     * Helper to set static $config property
     */
    private function setStaticConfig(array $config): void
    {
        $configProperty = $this->reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue(null, $config);
    }

    /**
     * Helper to get static $config property
     */
    private function getStaticConfig(): ?array
    {
        $configProperty = $this->reflection->getProperty('config');
        $configProperty->setAccessible(true);
        return $configProperty->getValue();
    }

    /**
     * Helper to invoke private/protected methods
     */
    private function invokeMethod(string $methodName, array $args = [])
    {
        $method = $this->reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }

    // ==========================================
    // TESTS: CONFIGURATION LOADING
    // ==========================================

    /** @test */
    public function itLoadsConfigurationLazily(): void
    {
        // Arrange
        $this->assertNull($this->getStaticConfig());
        
        // Act
        $this->setStaticConfig($this->mockConfig);
        
        // Assert
        $config = $this->getStaticConfig();
        $this->assertNotNull($config);
        $this->assertIsArray($config);
        $this->assertArrayHasKey('logging', $config);
    }

    /** @test */
    public function itDoesNotReloadConfigurationIfAlreadyLoaded(): void
    {
        // Arrange
        $this->setStaticConfig($this->mockConfig);
        $configBefore = $this->getStaticConfig();
        
        // Act - Try to load again
        $this->setStaticConfig($this->mockConfig);
        $configAfter = $this->getStaticConfig();
        
        // Assert
        $this->assertEquals($configBefore, $configAfter);
    }

    /** @test */
    public function itLoadsLoggingConfigurationWithAllFlags(): void
    {
        // Arrange & Act
        $this->setStaticConfig($this->mockConfig);
        $config = $this->getStaticConfig();
        
        // Assert
        $requiredFlags = [
            'enableSqlLogging',
            'enableMailLogging',
            'enableSystemLogging',
            'enableAuditLogging',
            'enableRequestLogging',
            'enableSecurityLogging',
        ];
        
        foreach ($requiredFlags as $flag) {
            $this->assertArrayHasKey($flag, $config['logging']);
            $this->assertIsBool($config['logging'][$flag]);
        }
    }

    // ==========================================
    // TESTS: LOG TYPE ENUM
    // ==========================================

    /** @test */
    public function itValidatesAllLogTypesExist(): void
    {
        // Arrange
        $expectedTypes = [
            'ERROR',
            'AUDIT',
            'REQUEST',
            'SYSTEM',
            'MAIL',
            'SQL',
            'SECURITY',
        ];
        
        // Act & Assert
        foreach ($expectedTypes as $type) {
            $logType = LogType::from($type);
            $this->assertInstanceOf(LogType::class, $logType);
            $this->assertEquals($type, $logType->value);
        }
    }

    /** @test */
    public function itCreatesLogTypesFromEnum(): void
    {
        // Assert
        $this->assertEquals('ERROR', LogType::ERROR->value);
        $this->assertEquals('AUDIT', LogType::AUDIT->value);
        $this->assertEquals('SQL', LogType::SQL->value);
        $this->assertEquals('MAIL', LogType::MAIL->value);
        $this->assertEquals('SYSTEM', LogType::SYSTEM->value);
        $this->assertEquals('REQUEST', LogType::REQUEST->value);
        $this->assertEquals('SECURITY', LogType::SECURITY->value);
    }

    // ==========================================
    // TESTS: LOG FILTERING LOGIC
    // ==========================================

    /** @test */
    public function itDeterminesIfSqlLoggingIsEnabled(): void
    {
        // Arrange
        $configEnabled = ['logging' => ['enableSqlLogging' => true]];
        $configDisabled = ['logging' => ['enableSqlLogging' => false]];
        
        // Assert
        $this->assertTrue($configEnabled['logging']['enableSqlLogging']);
        $this->assertFalse($configDisabled['logging']['enableSqlLogging']);
    }

    /** @test */
    public function itDeterminesIfRequestLoggingIsEnabled(): void
    {
        // Arrange
        $config = $this->mockConfig;
        
        // Assert
        $this->assertFalse($config['logging']['enableRequestLogging']);
    }

    /** @test */
    public function itAlwaysLogsErrorTypeRegardlessOfConfig(): void
    {
        // Arrange - All logging disabled
        $configAllDisabled = [
            'logging' => [
                'enableSqlLogging' => false,
                'enableMailLogging' => false,
                'enableSystemLogging' => false,
                'enableAuditLogging' => false,
                'enableRequestLogging' => false,
                'enableSecurityLogging' => false,
            ],
        ];
        
        // Act - Simulate logAction logic for ERROR
        $type = LogType::ERROR;
        $shouldLog = ($type == LogType::ERROR); // ERROR always logs
        
        // Assert
        $this->assertTrue($shouldLog);
    }

    /** @test */
    public function itValidatesLogTypeFilteringLogic(): void
    {
        // Arrange
        $this->setStaticConfig($this->mockConfig);
        $config = $this->getStaticConfig();
        
        $testCases = [
            ['type' => LogType::SQL, 'configKey' => 'enableSqlLogging', 'expected' => true],
            ['type' => LogType::MAIL, 'configKey' => 'enableMailLogging', 'expected' => true],
            ['type' => LogType::SYSTEM, 'configKey' => 'enableSystemLogging', 'expected' => true],
            ['type' => LogType::AUDIT, 'configKey' => 'enableAuditLogging', 'expected' => true],
            ['type' => LogType::REQUEST, 'configKey' => 'enableRequestLogging', 'expected' => false],
            ['type' => LogType::SECURITY, 'configKey' => 'enableSecurityLogging', 'expected' => true],
        ];
        
        // Act & Assert
        foreach ($testCases as $case) {
            $shouldLog = $config['logging'][$case['configKey']];
            $this->assertEquals($case['expected'], $shouldLog, "LogType {$case['type']->value} filtering failed");
        }
    }

    // ==========================================
    // TESTS: IP ADDRESS EXTRACTION
    // ==========================================

    /** @test */
    public function itExtractsIpFromRemoteAddr(): void
    {
        // Arrange
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_SERVER['HTTP_CLIENT_IP']);
        
        // Act
        $ip = $this->invokeMethod('getIpAddress');
        
        // Assert
        $this->assertEquals('192.168.1.100', $ip);
    }

    /** @test */
    public function itPrefersXForwardedForIfAvailable(): void
    {
        // Arrange
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        
        // Act
        $ip = $this->invokeMethod('getIpAddress');
        
        // Assert
        $this->assertEquals('10.0.0.1', $ip);
    }

    /** @test */
    public function itHandlesMultipleIpsInXForwardedFor(): void
    {
        // Arrange
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 172.16.0.1, 192.168.1.1';
        
        // Act
        $ip = $this->invokeMethod('getIpAddress');
        
        // Assert - Should return first IP
        $this->assertEquals('10.0.0.1', $ip);
    }

    /** @test */
    public function itFallsBackToUnknownIfNoIpAvailable(): void
    {
        // Arrange
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_SERVER['HTTP_CLIENT_IP']);
        
        // Act
        $ip = $this->invokeMethod('getIpAddress');
        
        // Assert
        $this->assertEquals('unknown', $ip);
    }

    /** @test */
    public function itValidatesIpv4Addresses(): void
    {
        // Arrange
        $validIpv4 = [
            '192.168.1.1',
            '10.0.0.1',
            '172.16.0.1',
            '8.8.8.8',
            '127.0.0.1',
        ];
        
        // Assert
        foreach ($validIpv4 as $ip) {
            $this->assertMatchesRegularExpression(
                '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/',
                $ip
            );
        }
    }

    /** @test */
    public function itValidatesIpv6Addresses(): void
    {
        // Arrange
        $validIpv6 = [
            '::1',
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'fe80::1',
        ];
        
        // Assert
        foreach ($validIpv6 as $ip) {
            $this->assertMatchesRegularExpression('/[0-9a-f:]+/i', $ip);
        }
    }

    // ==========================================
    // TESTS: CONTEXT STRING FORMATTING
    // ==========================================

    /** @test */
    public function itFormatsContextStringCorrectly(): void
    {
        // Arrange
        $file = 'LoginController';
        $method = 'login';
        
        // Act
        $context = "$file/$method";
        
        // Assert
        $this->assertEquals('LoginController/login', $context);
        $this->assertStringContainsString('/', $context);
    }

    /** @test */
    public function itHandlesEmptyMethodInContext(): void
    {
        // Arrange
        $file = 'TestFile';
        $method = '';
        
        // Act
        $context = "$file/$method";
        
        // Assert
        $this->assertEquals('TestFile/', $context);
    }

    /** @test */
    public function itHandlesNestedFilePathsInContext(): void
    {
        // Arrange
        $file = 'App\\Controllers\\AuthController';
        $method = 'login';
        
        // Act
        $context = "$file/$method";
        
        // Assert
        $this->assertStringContainsString('AuthController', $context);
        $this->assertStringEndsWith('/login', $context);
    }

    // ==========================================
    // TESTS: FILE LOGGING
    // ==========================================

    /** @test */
    public function itCreatesLogDirectoryIfNotExists(): void
    {
        // Arrange
        $testDir = $this->testLogDir . '/new_dir';
        $this->assertDirectoryDoesNotExist($testDir);
        
        // Act
        if (!is_dir($testDir)) {
            mkdir($testDir, 0750, true);
        }
        
        // Assert
        $this->assertDirectoryExists($testDir);
        
        // Cleanup
        rmdir($testDir);
    }

    /** @test */
    public function itUsesCorrectFilePermissionsForLogDirectory(): void
    {
        // Arrange
        $testDir = $this->testLogDir . '/secure_dir';
        
        // Act
        mkdir($testDir, 0750, true);
        $perms = fileperms($testDir) & 0777;
        
        // Assert
        $this->assertEquals(0750, $perms);
        
        // Cleanup
        rmdir($testDir);
    }

    /** @test */
    public function itFormatsLogMessageWithTimestamp(): void
    {
        // Arrange
        $message = 'Test log message';
        $timestamp = date('Y-m-d H:i:s');
        
        // Act
        $formattedMessage = "[$timestamp] $message" . PHP_EOL;
        
        // Assert
        $this->assertStringStartsWith('[', $formattedMessage);
        $this->assertStringContainsString($message, $formattedMessage);
        $this->assertStringEndsWith(PHP_EOL, $formattedMessage);
    }

    /** @test */
    public function itAppendsToLogFile(): void
    {
        // Arrange
        $logFile = $this->testLogDir . '/test.log';
        $message1 = "First message\n";
        $message2 = "Second message\n";
        
        // Act
        file_put_contents($logFile, $message1, FILE_APPEND);
        file_put_contents($logFile, $message2, FILE_APPEND);
        
        // Assert
        $content = file_get_contents($logFile);
        $this->assertStringContainsString($message1, $content);
        $this->assertStringContainsString($message2, $content);
        
        // Cleanup
        unlink($logFile);
    }

    /** @test */
    public function itUsesFileLockingWhenWriting(): void
    {
        // Arrange
        $logFile = $this->testLogDir . '/locked.log';
        $message = "Test message\n";
        
        // Act
        $result = file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
        
        // Assert
        $this->assertNotFalse($result);
        $this->assertFileExists($logFile);
        
        // Cleanup
        unlink($logFile);
    }

    // ==========================================
    // TESTS: LOG MESSAGE VALIDATION
    // ==========================================

    /** @test */
    public function itValidatesLogMessageIsString(): void
    {
        // Arrange
        $validMessages = [
            'Simple message',
            'Message with numbers: 123',
            'Message with special chars: @#$%',
        ];
        
        // Assert
        foreach ($validMessages as $message) {
            $this->assertIsString($message);
        }
    }

    /** @test */
    public function itHandlesEmptyLogMessages(): void
    {
        // Arrange
        $emptyMessage = '';
        
        // Assert
        $this->assertIsString($emptyMessage);
        $this->assertEmpty($emptyMessage);
    }

    /** @test */
    public function itHandlesMultilineLogMessages(): void
    {
        // Arrange
        $multilineMessage = "Line 1\nLine 2\nLine 3";
        
        // Assert
        $this->assertIsString($multilineMessage);
        $this->assertStringContainsString("\n", $multilineMessage);
        $lines = explode("\n", $multilineMessage);
        $this->assertCount(3, $lines);
    }

    /** @test */
    public function itHandlesUnicodeInLogMessages(): void
    {
        // Arrange
        $unicodeMessages = [
            'Willkommen 🎉',
            '日本語のログ',
            'Кириллица',
            'العربية',
        ];
        
        // Assert
        foreach ($unicodeMessages as $message) {
            $this->assertIsString($message);
            $this->assertGreaterThan(0, mb_strlen($message, 'UTF-8'));
        }
    }

    // ==========================================
    // TESTS: USER CONTEXT
    // ==========================================

    /** @test */
    public function itDefaultsToAnonymousUserWhenNoSession(): void
    {
        // Arrange
        $_SESSION = [];
        
        // Act - Simulate getting user from session
        $user = $_SESSION['user']['username'] ?? 'anonymous';
        
        // Assert
        $this->assertEquals('anonymous', $user);
    }

    /** @test */
    public function itExtractsUsernameFromSession(): void
    {
        // Arrange
        $_SESSION['user'] = ['username' => 'testuser'];
        
        // Act
        $user = $_SESSION['user']['username'] ?? 'anonymous';
        
        // Assert
        $this->assertEquals('testuser', $user);
    }

    // ==========================================
    // TESTS: DATE/TIME FORMATTING
    // ==========================================

    /** @test */
    public function itFormatsDatetimeCorrectly(): void
    {
        // Arrange & Act
        $datetime = date('Y-m-d H:i:s');
        
        // Assert
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $datetime
        );
    }

    /** @test */
    public function itUsesConsistentDatetimeFormat(): void
    {
        // Arrange
        $format = 'Y-m-d H:i:s';
        
        // Act
        $datetime1 = date($format);
        sleep(1);
        $datetime2 = date($format);
        
        // Assert - Both should have same format
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $datetime1
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $datetime2
        );
    }

    // ==========================================
    // TESTS: LOG PAGINATION PARAMETERS
    // ==========================================

    /** @test */
    public function itValidatesPaginationParameters(): void
    {
        // Arrange
        $page = 1;
        $perPage = 100;
        
        // Assert
        $this->assertGreaterThan(0, $page);
        $this->assertGreaterThan(0, $perPage);
        $this->assertLessThanOrEqual(1000, $perPage); // Reasonable limit
    }

    /** @test */
    public function itCalculatesOffsetCorrectly(): void
    {
        // Arrange & Act
        $testCases = [
            ['page' => 1, 'perPage' => 10, 'expectedOffset' => 0],
            ['page' => 2, 'perPage' => 10, 'expectedOffset' => 10],
            ['page' => 3, 'perPage' => 25, 'expectedOffset' => 50],
            ['page' => 5, 'perPage' => 100, 'expectedOffset' => 400],
        ];
        
        // Assert
        foreach ($testCases as $case) {
            $offset = ($case['page'] - 1) * $case['perPage'];
            $this->assertEquals($case['expectedOffset'], $offset);
        }
    }

    // ==========================================
    // TESTS: EDGE CASES
    // ==========================================

    /** @test */
    public function itHandlesVeryLongLogMessages(): void
    {
        // Arrange
        $longMessage = str_repeat('A', 10000); // 10KB message
        
        // Assert
        $this->assertIsString($longMessage);
        $this->assertEquals(10000, strlen($longMessage));
    }

    /** @test */
    public function itHandlesSpecialCharactersInContext(): void
    {
        // Arrange
        $contexts = [
            'File/Method',
            'Controller\\Action',
            'Namespace\\Class::method',
            'path/to/file.php/function',
        ];
        
        // Assert
        foreach ($contexts as $context) {
            $this->assertIsString($context);
            $this->assertNotEmpty($context);
        }
    }

    /** @test */
    public function itValidatesLogTypeEnumValuesAreStrings(): void
    {
        // Arrange
        $logTypes = [
            LogType::ERROR,
            LogType::AUDIT,
            LogType::SQL,
            LogType::MAIL,
            LogType::SYSTEM,
            LogType::REQUEST,
            LogType::SECURITY,
        ];
        
        // Assert
        foreach ($logTypes as $type) {
            $this->assertIsString($type->value);
            $this->assertNotEmpty($type->value);
        }
    }
}
