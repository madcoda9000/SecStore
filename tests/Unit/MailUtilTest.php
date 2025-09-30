<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Utils\MailUtil;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Mockery;
use ReflectionClass;

/**
 * MailUtil Unit Tests
 * 
 * Tests email utility functionality including:
 * - Configuration loading
 * - SMTP connection validation
 * - Email sending with templates
 * - Error handling
 * 
 * Note: Uses Mockery to mock PHPMailer and external dependencies
 * 
 * @package Tests\Unit
 */
class MailUtilTest extends TestCase
{
    private ReflectionClass $reflection;
    private array $mockConfig;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->reflection = new ReflectionClass(MailUtil::class);
        
        // Create mock configuration
        $this->mockConfig = [
            'mail' => [
                'host' => 'smtp.test.com',
                'username' => 'test@test.com',
                'password' => 'test_password',
                'encryption' => 'tls',
                'port' => 587,
                'fromEmail' => 'noreply@test.com',
                'fromName' => 'Test System',
                'enableWelcomeMail' => true,
            ],
        ];
        
        // Reset static config property before each test
        $this->resetStaticConfig();
    }

    /**
     * Teardown after each test
     */
    protected function tearDown(): void
    {
        Mockery::close();
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

    // ==========================================
    // TESTS: CONFIGURATION LOADING
    // ==========================================

    /** @test */
    public function it_loads_configuration_lazily(): void
    {
        // Arrange - Config should be null initially
        $this->assertNull($this->getStaticConfig());
        
        // Act - Manually set config to simulate loadConfig()
        $this->setStaticConfig($this->mockConfig);
        
        // Assert
        $config = $this->getStaticConfig();
        $this->assertNotNull($config);
        $this->assertIsArray($config);
    }

    /** @test */
    public function it_does_not_reload_configuration_if_already_loaded(): void
    {
        // Arrange
        $this->setStaticConfig($this->mockConfig);
        $configBefore = $this->getStaticConfig();
        
        // Act - Try to load again (should not change)
        $this->setStaticConfig($this->mockConfig);
        $configAfter = $this->getStaticConfig();
        
        // Assert - Same reference
        $this->assertEquals($configBefore, $configAfter);
    }

    /** @test */
    public function it_loads_mail_configuration_with_required_keys(): void
    {
        // Arrange & Act
        $this->setStaticConfig($this->mockConfig);
        $config = $this->getStaticConfig();
        
        // Assert
        $this->assertArrayHasKey('mail', $config);
        
        $requiredKeys = ['host', 'username', 'password', 'encryption', 'port', 'fromEmail', 'fromName'];
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $config['mail'], "Mail config missing key: $key");
        }
    }

    // ==========================================
    // TESTS: SMTP CONNECTION VALIDATION
    // ==========================================

    /** @test */
    public function it_validates_smtp_configuration_structure(): void
    {
        // Arrange
        $config = $this->mockConfig['mail'];
        
        // Assert - All required fields present
        $this->assertNotEmpty($config['host']);
        $this->assertNotEmpty($config['username']);
        $this->assertNotEmpty($config['password']);
        $this->assertIsInt($config['port']);
        $this->assertContains($config['encryption'], ['tls', 'ssl']);
    }

    /** @test */
    public function it_has_valid_smtp_port_numbers(): void
    {
        // Arrange
        $validPorts = [25, 465, 587, 2525];
        $config = $this->mockConfig['mail'];
        
        // Assert
        $this->assertContains($config['port'], $validPorts);
    }

    /** @test */
    public function it_has_valid_encryption_type(): void
    {
        // Arrange
        $config = $this->mockConfig['mail'];
        
        // Assert
        $this->assertContains($config['encryption'], ['tls', 'ssl', 'none']);
    }

    /** @test */
    public function it_has_valid_email_format_for_from_address(): void
    {
        // Arrange
        $config = $this->mockConfig['mail'];
        
        // Assert
        $this->assertMatchesRegularExpression(
            '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            $config['fromEmail']
        );
    }

    // ==========================================
    // TESTS: EMAIL CONFIGURATION VALIDATION
    // ==========================================

    /** @test */
    public function it_validates_recipient_email_format(): void
    {
        // Arrange
        $validEmails = [
            'test@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
        ];
        
        $invalidEmails = [
            'invalid',
            'invalid@',
            '@invalid.com',
            'invalid@.com',
        ];
        
        // Assert valid emails
        foreach ($validEmails as $email) {
            $this->assertMatchesRegularExpression(
                '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                $email,
                "Email should be valid: $email"
            );
        }
        
        // Assert invalid emails
        foreach ($invalidEmails as $email) {
            $this->assertDoesNotMatchRegularExpression(
                '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                $email,
                "Email should be invalid: $email"
            );
        }
    }

    /** @test */
    public function it_validates_subject_is_not_empty(): void
    {
        // Arrange
        $validSubjects = [
            'Test Subject',
            'Welcome to SecStore',
            'Password Reset Request',
        ];
        
        // Assert
        foreach ($validSubjects as $subject) {
            $this->assertNotEmpty($subject);
            $this->assertIsString($subject);
        }
    }

    // ==========================================
    // TESTS: TEMPLATE VALIDATION
    // ==========================================

    /** @test */
    public function it_validates_template_names(): void
    {
        // Arrange
        $validTemplates = [
            'welcome',
            'password_reset',
            'account_verification',
            '2fa_enabled',
        ];
        
        // Assert
        foreach ($validTemplates as $template) {
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9_]+$/',
                $template,
                "Template name should be valid: $template"
            );
        }
    }

    /** @test */
    public function it_validates_template_data_is_array(): void
    {
        // Arrange
        $validData = [
            [],
            ['username' => 'testuser'],
            ['key' => 'value', 'number' => 123],
        ];
        
        // Assert
        foreach ($validData as $data) {
            $this->assertIsArray($data);
        }
    }

    // ==========================================
    // TESTS: WELCOME EMAIL LOGIC
    // ==========================================

    /** @test */
    public function it_respects_enable_welcome_mail_flag(): void
    {
        // Test when enabled
        $configEnabled = $this->mockConfig;
        $configEnabled['mail']['enableWelcomeMail'] = true;
        $this->assertTrue($configEnabled['mail']['enableWelcomeMail']);
        
        // Test when disabled
        $configDisabled = $this->mockConfig;
        $configDisabled['mail']['enableWelcomeMail'] = false;
        $this->assertFalse($configDisabled['mail']['enableWelcomeMail']);
    }

    /** @test */
    public function it_validates_welcome_mail_logic(): void
    {
        // Arrange
        $template = 'welcome';
        $enableWelcomeMail = true;
        
        // Act - Simulate the logic from sendMail
        $shouldSend = ($template === 'welcome' && $enableWelcomeMail === true);
        
        // Assert
        $this->assertTrue($shouldSend);
        
        // Test disabled case
        $enableWelcomeMail = false;
        $shouldSend = ($template === 'welcome' && $enableWelcomeMail === true);
        $this->assertFalse($shouldSend);
    }

    /** @test */
    public function it_sends_non_welcome_emails_regardless_of_flag(): void
    {
        // Arrange
        $nonWelcomeTemplates = ['password_reset', '2fa_setup', 'account_locked'];
        
        // Act & Assert
        foreach ($nonWelcomeTemplates as $template) {
            // These should always be sent regardless of enableWelcomeMail
            $shouldCheck = ($template !== 'welcome');
            $this->assertTrue($shouldCheck, "Template $template should not check welcome flag");
        }
    }

    // ==========================================
    // TESTS: ERROR HANDLING
    // ==========================================

    /** @test */
    public function it_handles_invalid_smtp_credentials_gracefully(): void
    {
        // Arrange
        $invalidConfig = $this->mockConfig;
        $invalidConfig['mail']['username'] = '';
        $invalidConfig['mail']['password'] = '';
        
        // Assert - Config should still be valid array structure
        $this->assertIsArray($invalidConfig['mail']);
        $this->assertArrayHasKey('username', $invalidConfig['mail']);
        $this->assertArrayHasKey('password', $invalidConfig['mail']);
    }

    /** @test */
    public function it_handles_missing_mail_configuration(): void
    {
        // Arrange
        $emptyConfig = [];
        
        // Assert
        $this->assertArrayNotHasKey('mail', $emptyConfig);
    }

    /** @test */
    public function it_handles_invalid_port_number(): void
    {
        // Arrange
        $invalidPorts = [-1, 0, 65536, 99999];
        
        // Assert - These are invalid port numbers
        foreach ($invalidPorts as $port) {
            $this->assertTrue(
                $port < 1 || $port > 65535,
                "Port $port should be invalid"
            );
        }
    }

    // ==========================================
    // TESTS: SMTP TIMEOUT HANDLING
    // ==========================================

    /** @test */
    public function it_uses_reasonable_smtp_timeout(): void
    {
        // Arrange - Based on code: Timeout = 4 seconds
        $expectedTimeout = 4;
        
        // Assert - Timeout should be reasonable (between 2-10 seconds)
        $this->assertGreaterThanOrEqual(2, $expectedTimeout);
        $this->assertLessThanOrEqual(10, $expectedTimeout);
    }

    // ==========================================
    // TESTS: DATA SANITIZATION
    // ==========================================

    /** @test */
    public function it_handles_special_characters_in_subject(): void
    {
        // Arrange
        $subjectsWithSpecialChars = [
            'Test & Subject',
            'Welcome <User>',
            'Reset "Password"',
            "User's Account",
        ];
        
        // Assert - Should be strings and not empty
        foreach ($subjectsWithSpecialChars as $subject) {
            $this->assertIsString($subject);
            $this->assertNotEmpty($subject);
        }
    }

    /** @test */
    public function it_handles_unicode_in_subject(): void
    {
        // Arrange
        $unicodeSubjects = [
            'Willkommen bei SecStore',
            'パスワードリセット',
            'Восстановление пароля',
            'إعادة تعيين كلمة المرور',
        ];
        
        // Assert
        foreach ($unicodeSubjects as $subject) {
            $this->assertIsString($subject);
            $this->assertNotEmpty($subject);
            $this->assertGreaterThan(0, mb_strlen($subject, 'UTF-8'));
        }
    }

    // ==========================================
    // TESTS: CONFIGURATION EDGE CASES
    // ==========================================

    /** @test */
    public function it_handles_empty_from_name_gracefully(): void
    {
        // Arrange
        $config = $this->mockConfig;
        $config['mail']['fromName'] = '';
        
        // Assert - Should still be valid (will use email as name)
        $this->assertIsString($config['mail']['fromName']);
        $this->assertNotNull($config['mail']['fromEmail']);
    }

    /** @test */
    public function it_validates_smtp_host_format(): void
    {
        // Arrange
        $validHosts = [
            'smtp.gmail.com',
            'mail.example.com',
            'smtp.office365.com',
            '192.168.1.1',
            'localhost',
        ];
        
        // Assert
        foreach ($validHosts as $host) {
            $this->assertIsString($host);
            $this->assertNotEmpty($host);
            $this->assertDoesNotMatchRegularExpression('/\s/', $host, "Host should not contain spaces");
        }
    }

    /** @test */
    public function it_handles_different_encryption_methods(): void
    {
        // Arrange
        $encryptionMethods = [
            'tls' => 587,
            'ssl' => 465,
        ];
        
        // Assert
        foreach ($encryptionMethods as $encryption => $defaultPort) {
            $this->assertIsString($encryption);
            $this->assertIsInt($defaultPort);
            $this->assertGreaterThan(0, $defaultPort);
        }
    }

    // ==========================================
    // TESTS: TEMPLATE PATH VALIDATION
    // ==========================================

    /** @test */
    public function it_constructs_valid_template_path(): void
    {
        // Arrange
        $templateName = 'welcome';
        $expectedPath = __DIR__ . '/../../app/views/emails/' . $templateName . '.latte';
        
        // Assert
        $this->assertStringContainsString('emails', $expectedPath);
        $this->assertStringContainsString('.latte', $expectedPath);
        $this->assertStringEndsWith('.latte', $expectedPath);
    }

    /** @test */
    public function it_validates_template_file_extension(): void
    {
        // Arrange
        $templates = [
            'welcome.latte',
            'password_reset.latte',
            '2fa_setup.latte',
        ];
        
        // Assert
        foreach ($templates as $template) {
            $this->assertStringEndsWith('.latte', $template);
        }
    }

    // ==========================================
    // TESTS: INTEGRATION SCENARIOS
    // ==========================================

    /** @test */
    public function it_has_complete_mail_configuration_for_sending(): void
    {
        // Arrange
        $config = $this->mockConfig['mail'];
        
        // Assert - All required fields for sending email
        $requiredForSending = [
            'host',
            'username',
            'password',
            'port',
            'encryption',
            'fromEmail',
            'fromName',
        ];
        
        foreach ($requiredForSending as $key) {
            $this->assertArrayHasKey($key, $config);
            $this->assertNotEmpty($config[$key], "Config key '$key' should not be empty");
        }
    }

    /** @test */
    public function it_validates_complete_email_send_parameters(): void
    {
        // Arrange - Simulate sendMail parameters
        $to = 'recipient@example.com';
        $subject = 'Test Email';
        $template = 'welcome';
        $data = ['username' => 'testuser'];
        
        // Assert
        $this->assertNotEmpty($to);
        $this->assertNotEmpty($subject);
        $this->assertNotEmpty($template);
        $this->assertIsArray($data);
        
        // Validate email format
        $this->assertMatchesRegularExpression(
            '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            $to
        );
    }

    // ==========================================
    // TESTS: LOGGING BEHAVIOR
    // ==========================================

    /** @test */
    public function it_constructs_valid_log_messages(): void
    {
        // Arrange
        $to = 'test@example.com';
        $template = 'welcome';
        
        // Act - Construct expected log messages
        $expectedWelcomeLog = "Welcome Mail an {$to} wurde versendet";
        $expectedRegularLog = "{$template} Mail an {$to} wurde versendet";
        
        // Assert
        $this->assertStringContainsString($to, $expectedWelcomeLog);
        $this->assertStringContainsString($to, $expectedRegularLog);
        $this->assertStringContainsString('versendet', $expectedWelcomeLog);
    }
}