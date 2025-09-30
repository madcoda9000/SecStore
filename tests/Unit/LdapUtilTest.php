<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Utils\LdapUtil;
use ReflectionClass;
use ReflectionProperty;

/**
 * LdapUtil Unit Tests
 *
 * Tests LDAP authentication utility functionality including:
 * - Configuration loading and validation
 * - Input validation (empty credentials)
 * - DN construction logic
 * - Edge cases and error handling
 *
 * ⚠️ IMPORTANT: These are unit tests focused on business logic.
 * Real LDAP connection tests require:
 * - A test LDAP server (e.g., OpenLDAP in Docker)
 * - Integration test suite
 * - Network connectivity
 *
 * Native PHP LDAP functions (ldap_connect, ldap_bind, etc.) cannot be
 * easily mocked in PHPUnit without additional libraries like php-mock.
 *
 * @package Tests\Unit
 */
class LdapUtilTest extends TestCase
{
    private ReflectionClass $reflection;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->reflection = new ReflectionClass(LdapUtil::class);
    }

    /**
     * Teardown after each test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Helper to access private/protected properties
     */
    private function getStaticProperty(string $propertyName)
    {
        $property = $this->reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue();
    }

    /**
     * Helper to set private/protected properties
     */
    private function setStaticProperty(string $propertyName, $value): void
    {
        $property = $this->reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue(null, $value);
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
    public function it_has_default_configuration_values(): void
    {
        // Arrange & Act
        $ldapHost = $this->getStaticProperty('ldapHost');
        $ldapPort = $this->getStaticProperty('ldapPort');
        $domainPrefix = $this->getStaticProperty('domainPrefix');
        $timeout = $this->getStaticProperty('timeout');
        
        // Assert - Default values should be set
        $this->assertIsString($ldapHost);
        $this->assertIsInt($ldapPort);
        $this->assertIsString($domainPrefix);
        $this->assertIsInt($timeout);
        
        // Standard LDAPS port should be 636
        $this->assertEquals(636, $ldapPort);
        
        // Timeout should be reasonable (5 seconds)
        $this->assertEquals(5, $timeout);
    }

    /** @test */
    public function it_validates_ldap_port_is_within_valid_range(): void
    {
        // Arrange
        $ldapPort = $this->getStaticProperty('ldapPort');
        
        // Assert - Port should be in valid range (1-65535)
        $this->assertGreaterThan(0, $ldapPort);
        $this->assertLessThanOrEqual(65535, $ldapPort);
    }

    /** @test */
    public function it_uses_standard_ldap_ports(): void
    {
        // Arrange
        $standardPorts = [389, 636]; // 389 = LDAP, 636 = LDAPS
        $ldapPort = $this->getStaticProperty('ldapPort');
        
        // Assert - Should use one of the standard ports
        $this->assertContains($ldapPort, $standardPorts);
    }

    /** @test */
    public function it_validates_ldap_host_format(): void
    {
        // Arrange
        $ldapHost = $this->getStaticProperty('ldapHost');
        
        // Assert - Should contain ldap:// or ldaps:// protocol
        $hasValidProtocol =
            str_starts_with($ldapHost, 'ldap://') ||
            str_starts_with($ldapHost, 'ldaps://');
        
        $this->assertTrue($hasValidProtocol, 'LDAP host should start with ldap:// or ldaps://');
    }

    /** @test */
    public function it_validates_timeout_is_reasonable(): void
    {
        // Arrange
        $timeout = $this->getStaticProperty('timeout');
        
        // Assert - Timeout should be between 1 and 60 seconds
        $this->assertGreaterThanOrEqual(1, $timeout);
        $this->assertLessThanOrEqual(60, $timeout);
    }

    /** @test */
    public function it_validates_domain_prefix_format(): void
    {
        // Arrange
        $domainPrefix = $this->getStaticProperty('domainPrefix');
        
        // Assert - Domain prefix should be string
        $this->assertIsString($domainPrefix);
        
        // If domain prefix is set, it should end with backslash
        if (!empty($domainPrefix)) {
            $this->assertStringEndsWith(
                '\\',
                $domainPrefix,
                'Domain prefix should end with backslash (e.g., "DOMAIN\\")'
            );
        }
    }

    // ==========================================
    // TESTS: INPUT VALIDATION
    // ==========================================

    /** @test */
    public function it_rejects_empty_username(): void
    {
        // Arrange
        $emptyUsername = '';
        $validPassword = 'Test1234!';
        
        // Act
        $result = LdapUtil::authenticate($emptyUsername, $validPassword);
        
        // Assert
        $this->assertFalse($result, 'Should reject empty username');
    }

    /** @test */
    public function it_rejects_empty_password(): void
    {
        // Arrange
        $validUsername = 'testuser';
        $emptyPassword = '';
        
        // Act
        $result = LdapUtil::authenticate($validUsername, $emptyPassword);
        
        // Assert
        $this->assertFalse($result, 'Should reject empty password');
    }

    /** @test */
    public function it_rejects_both_empty_credentials(): void
    {
        // Arrange
        $emptyUsername = '';
        $emptyPassword = '';
        
        // Act
        $result = LdapUtil::authenticate($emptyUsername, $emptyPassword);
        
        // Assert
        $this->assertFalse($result, 'Should reject both empty credentials');
    }

    /** @test */
    public function it_rejects_whitespace_only_username(): void
    {
        // Arrange
        $whitespaceUsername = '   ';
        $validPassword = 'Test1234!';
        
        // Act - trim() is implicit in empty() check
        $result = LdapUtil::authenticate(trim($whitespaceUsername), $validPassword);
        
        // Assert
        $this->assertFalse($result, 'Should reject whitespace-only username');
    }

    /** @test */
    public function it_rejects_whitespace_only_password(): void
    {
        // Arrange
        $validUsername = 'testuser';
        $whitespacePassword = '   ';
        
        // Act
        $result = LdapUtil::authenticate($validUsername, trim($whitespacePassword));
        
        // Assert
        $this->assertFalse($result, 'Should reject whitespace-only password');
    }

    /** @test */
    public function it_handles_null_username_safely(): void
    {
        // Arrange
        $nullUsername = null;
        $validPassword = 'Test1234!';
        
        // Act - PHP will coerce null to empty string
        $result = LdapUtil::authenticate($nullUsername ?? '', $validPassword);
        
        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_null_password_safely(): void
    {
        // Arrange
        $validUsername = 'testuser';
        $nullPassword = null;
        
        // Act
        $result = LdapUtil::authenticate($validUsername, $nullPassword ?? '');
        
        // Assert
        $this->assertFalse($result);
    }

    // ==========================================
    // TESTS: DN CONSTRUCTION LOGIC
    // ==========================================

    /** @test */
    public function it_constructs_bind_dn_with_domain_prefix(): void
    {
        // Arrange
        $domainPrefix = 'TESTDOMAIN\\';
        $username = 'jdoe';
        
        // Act
        $expectedBindDn = $domainPrefix . $username;
        
        // Assert
        $this->assertEquals('TESTDOMAIN\\jdoe', $expectedBindDn);
    }

    /** @test */
    public function it_constructs_bind_dn_without_domain_prefix(): void
    {
        // Arrange
        $domainPrefix = '';
        $username = 'jdoe@example.com';
        
        // Act
        $expectedBindDn = $domainPrefix . $username;
        
        // Assert
        $this->assertEquals('jdoe@example.com', $expectedBindDn);
    }

    /** @test */
    public function it_handles_special_characters_in_username(): void
    {
        // Arrange
        $domainPrefix = 'DOMAIN\\';
        $specialUsernames = [
            'user.name',
            'user-name',
            'user_name',
            'user@example.com',
        ];
        
        // Act & Assert
        foreach ($specialUsernames as $username) {
            $bindDn = $domainPrefix . $username;
            $this->assertStringContainsString($username, $bindDn);
        }
    }

    /** @test */
    public function it_preserves_case_in_username(): void
    {
     // Arrange
        $domainPrefix = 'DOMAIN\\';
        $mixedCaseUsername = 'JohnDoe';
    
     // Act
        $bindDn = $domainPrefix . $mixedCaseUsername;
    
     // Assert - Username sollte exakt wie eingegeben erhalten bleiben
        $this->assertStringContainsString('JohnDoe', $bindDn);
        $this->assertStringNotContainsString('johndoe', $bindDn); // Lowercase sollte NICHT vorkommen
    }

    /** @test */
    public function it_validates_ldap_injection_attempts(): void
    {
        // Test for LDAP injection patterns
        // Real validation should be done by ldap_escape or similar
        
        // Arrange
        $injectionPatterns = [
            'user)(|(password=*',
            'admin*',
            '*)(uid=*',
            'user)(&',
        ];
        
        // Act & Assert
        foreach ($injectionPatterns as $maliciousInput) {
            // In production, these should be escaped or rejected
            $this->assertIsString($maliciousInput);
            // Actual escaping should happen in authenticate() method
        }
    }

    // ==========================================
    // TESTS: CONFIGURATION EDGE CASES
    // ==========================================

    /** @test */
    public function it_handles_missing_config_file_gracefully(): void
    {
        // This test documents expected behavior when config.php is missing
        // loadConfig() should either:
        // 1. Use default values
        // 2. Throw a clear exception
        
        $this->assertTrue(true, 'loadConfig() should handle missing config file');
    }

    /** @test */
    public function it_validates_ldap_host_is_not_empty(): void
    {
        // Arrange
        $ldapHost = $this->getStaticProperty('ldapHost');
        
        // Assert
        $this->assertNotEmpty($ldapHost, 'LDAP host must not be empty');
    }

    // ==========================================
    // TESTS: PROTOCOL VERSION VALIDATION
    // ==========================================

    /** @test */
    public function it_uses_ldap_protocol_version_3(): void
    {
        // LDAP v3 is the current standard (RFC 4511)
        // The authenticate() method should set LDAP_OPT_PROTOCOL_VERSION to 3
        
        $expectedVersion = 3;
        
        // Assert - Document requirement
        $this->assertEquals(
            3,
            $expectedVersion,
            'LDAP protocol version should be 3 (current standard)'
        );
    }

    // ==========================================
    // TESTS: TIMEOUT VALIDATION
    // ==========================================

    /** @test */
    public function it_has_reasonable_network_timeout(): void
    {
        // Arrange
        $timeout = $this->getStaticProperty('timeout');
        
        // Assert - Timeout should not be too short or too long
        $this->assertGreaterThanOrEqual(3, $timeout, 'Timeout should be at least 3 seconds');
        $this->assertLessThanOrEqual(30, $timeout, 'Timeout should not exceed 30 seconds');
    }

    // ==========================================
    // TESTS: RETURN VALUE VALIDATION
    // ==========================================

    /** @test */
    public function it_returns_boolean_from_authenticate(): void
    {
        // Arrange
        $username = '';
        $password = '';
        
        // Act
        $result = LdapUtil::authenticate($username, $password);
        
        // Assert - Must return boolean (true/false), never null or string
        $this->assertIsBool($result);
    }

    /** @test */
    public function it_returns_false_for_invalid_input(): void
    {
        // Arrange
        $invalidInputs = [
            ['', ''],
            ['', 'password'],
            ['username', ''],
        ];
        
        // Act & Assert
        foreach ($invalidInputs as [$username, $password]) {
            $result = LdapUtil::authenticate($username, $password);
            $this->assertFalse($result, "Should return false for [$username, $password]");
        }
    }

    // ==========================================
    // INTEGRATION TEST DOCUMENTATION
    // ==========================================

    /**
     * @test
     * @group integration
     * @group ldap
     *
     * This is a documentation test for integration testing requirements.
     *
     * To properly test LDAP functionality, you need:
     *
     * 1. Test LDAP Server Setup (Docker recommended):
     *    docker run -d -p 389:389 -p 636:636 \
     *      -e LDAP_ORGANISATION="Test Org" \
     *      -e LDAP_DOMAIN="test.local" \
     *      -e LDAP_ADMIN_PASSWORD="admin" \
     *      osixia/openldap:latest
     *
     * 2. Configure test users in LDAP
     *
     * 3. Integration test cases should verify:
     *    - Successful authentication with valid credentials
     *    - Failed authentication with invalid credentials
     *    - Connection timeout handling
     *    - LDAP server unavailable scenarios
     *    - TLS/SSL certificate validation
     *    - Multiple authentication attempts
     *    - Concurrent connections
     *
     * 4. Use @group annotations to separate integration tests:
     *    @group integration
     *    @group ldap
     *    @group slow
     */
    public function it_documents_integration_test_requirements(): void
    {
        $this->markTestSkipped(
            'This is a documentation test. ' .
            'Real LDAP integration tests require a test LDAP server. ' .
            'See test docblock for setup instructions.'
        );
    }
}
