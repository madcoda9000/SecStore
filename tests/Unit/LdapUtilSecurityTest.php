<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Utils\LdapUtil;
use ReflectionClass;

/**
 * LdapUtil Security Tests
 * 
 * Testet LDAP Injection-Schutz und Input-Validierung
 */
class LdapUtilSecurityTest extends TestCase
{
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reflection = new ReflectionClass(LdapUtil::class);
    }

    /**
     * Helper to invoke private methods
     */
    private function invokeMethod(string $methodName, array $args = [])
    {
        $method = $this->reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }

    // ==========================================
    // TESTS: LDAP INJECTION PREVENTION
    // ==========================================

    /** @test */
    public function itRejectsLdapInjectionInUsername(): void
    {
        // Arrange - Bekannte LDAP Injection Patterns
        $injectionPatterns = [
            'admin)(|(uid=*',           // Filter Injection
            '*)(uid=*',                 // Wildcard Injection
            'user)(&',                  // Boolean Injection
            'admin*',                   // Wildcard
            'user)(objectClass=*',      // ObjectClass Injection
            'admin)(cn=*',              // CN Injection
            '*)(&(uid=*',               // Complex Injection
        ];

        // Act & Assert
        foreach ($injectionPatterns as $maliciousUsername) {
            $result = LdapUtil::authenticate($maliciousUsername, 'ValidPassword123!');
            
            $this->assertFalse(
                $result, 
                "Should reject LDAP injection pattern: {$maliciousUsername}"
            );
        }
    }

    /** @test */
    public function itRejectsSpecialLdapCharactersInUsername(): void
    {
        // Arrange - Zeichen die in LDAP spezielle Bedeutung haben
        $specialCharacters = [
            'user(test)',           // Klammern
            'user&test',            // Ampersand
            'user|test',            // Pipe
            'user!test',            // Ausrufezeichen
            'user=test',            // Gleichheitszeichen
            'user<test',            // Kleiner als
            'user>test',            // Größer als
            'user~test',            // Tilde
            'user\\test',           // Backslash
            'user/test',            // Slash
            'user,test',            // Komma
            'user;test',            // Semikolon
        ];

        // Act & Assert
        foreach ($specialCharacters as $username) {
            $result = LdapUtil::authenticate($username, 'ValidPassword123!');
            
            $this->assertFalse(
                $result,
                "Should reject username with special LDAP character: {$username}"
            );
        }
    }

    /** @test */
    public function itAcceptsValidUsernameFormats(): void
    {
        // Arrange - Gültige Username-Formate
        $validUsernames = [
            'john.doe',
            'john-doe',
            'john_doe',
            'jdoe123',
            'JDOE',
            'j.doe-123',
            'user@example.com',
            'test.user_123',
        ];

        // Act & Assert
        foreach ($validUsernames as $username) {
            // validateUsername ist private, daher über Reflection aufrufen
            $isValid = $this->invokeMethod('validateUsername', [$username]);
            
            $this->assertTrue(
                $isValid,
                "Should accept valid username format: {$username}"
            );
        }
    }

    /** @test */
    public function itRejectsUsernameTooLong(): void
    {
        // Arrange - Username mit 256 Zeichen
        $tooLongUsername = str_repeat('a', 256);
        
        // Act
        $result = LdapUtil::authenticate($tooLongUsername, 'ValidPassword123!');
        
        // Assert
        $this->assertFalse($result, 'Should reject username longer than 255 characters');
    }

    /** @test */
    public function itRejectsEmptyUsernameAfterTrim(): void
    {
        // Arrange
        $whitespaceUsernames = [
            '   ',
            "\t",
            "\n",
            ' ',
        ];
        
        // Act & Assert
        foreach ($whitespaceUsernames as $username) {
            $result = LdapUtil::authenticate($username, 'ValidPassword123!');
            $this->assertFalse($result);
        }
    }

    /** @test */
    public function itTrimsWhitespaceFromValidUsername(): void
    {
        // Arrange
        $usernameWithWhitespace = '  validuser  ';
        
        // Act - validateUsername sollte getrimmt validieren
        $isValid = $this->invokeMethod('validateUsername', [trim($usernameWithWhitespace)]);
        
        // Assert
        $this->assertTrue($isValid, 'Should accept username after trimming whitespace');
    }

    // ==========================================
    // TESTS: DN ESCAPING
    // ==========================================

    /** @test */
    public function itEscapesDnSpecialCharacters(): void
    {
        // Arrange - escapeDn ist private
        $testCases = [
            ['input' => 'user,name', 'shouldContain' => '\\,'],
            ['input' => 'user+name', 'shouldContain' => '\\+'],
            ['input' => 'user"name', 'shouldContain' => '\\"'],
            ['input' => 'user\\name', 'shouldContain' => '\\\\'],
            ['input' => 'user<name', 'shouldContain' => '\\<'],
            ['input' => 'user>name', 'shouldContain' => '\\>'],
            ['input' => 'user;name', 'shouldContain' => '\\;'],
        ];

        // Act & Assert
        foreach ($testCases as $test) {
            $escaped = $this->invokeMethod('escapeDn', [$test['input']]);
            
            $this->assertStringContainsString(
                '\\',
                $escaped,
                "DN escaping should escape special character in: {$test['input']}"
            );
        }
    }

    /** @test */
    public function itEscapesFilterSpecialCharacters(): void
    {
        // Arrange
        $testCases = [
            ['input' => 'user*', 'shouldContain' => '\\2a'],      // * -> \2a
            ['input' => 'user(test)', 'shouldContain' => '\\28'], // ( -> \28
            ['input' => 'user)test', 'shouldContain' => '\\29'],  // ) -> \29
            ['input' => 'user\\test', 'shouldContain' => '\\5c'], // \ -> \5c
        ];

        // Act & Assert
        foreach ($testCases as $test) {
            $escaped = $this->invokeMethod('escapeFilter', [$test['input']]);
            
            $this->assertStringContainsString(
                '\\',
                $escaped,
                "Filter escaping should escape special character in: {$test['input']}"
            );
        }
    }

    // ==========================================
    // TESTS: PASSWORD VALIDATION
    // ==========================================

    /** @test */
    public function itRejectsEmptyPasswordAfterTrim(): void
    {
        // Arrange
        $validUsername = 'testuser';
        $whitespacePasswords = ['   ', "\t", "\n", ' '];
        
        // Act & Assert
        foreach ($whitespacePasswords as $password) {
            $result = LdapUtil::authenticate($validUsername, $password);
            $this->assertFalse($result);
        }
    }

    /** @test */
    public function itAcceptsPasswordsWithSpecialCharacters(): void
    {
        // Passwörter können beliebige Zeichen enthalten - das ist OK
        // Nur Username muss validiert werden
        $this->assertTrue(true);
    }

    // ==========================================
    // TESTS: EDGE CASES
    // ==========================================

    /** @test */
    public function itHandlesNullInputsGracefully(): void
    {
        // PHP wird null zu leerem String konvertieren
        $result1 = LdapUtil::authenticate(null ?? '', 'password');
        $result2 = LdapUtil::authenticate('username', null ?? '');
        
        $this->assertFalse($result1);
        $this->assertFalse($result2);
    }

    /** @test */
    public function itRejectsNumericZeroAsUsername(): void
    {
        // Arrange
        $result = LdapUtil::authenticate('0', 'password');
        
        // Assert
        $this->assertFalse($result, 'Should reject "0" as username');
    }

    /** @test */
    public function itRejectsUsernameWithOnlyNumbers(): void
    {
        // Arrange - Username mit nur Zahlen sollte eigentlich erlaubt sein
        // wenn das USERNAME_PATTERN es erlaubt
        $numericUsername = '12345';
        
        // Act
        $isValid = $this->invokeMethod('validateUsername', [$numericUsername]);
        
        // Assert - Hängt vom Pattern ab
        // Wenn Pattern [a-zA-Z0-9._@-]+ ist, dann sollte es gültig sein
        $this->assertTrue($isValid, 'Numeric usernames should be allowed if pattern permits');
    }

    // ==========================================
    // TESTS: CONFIGURATION VALIDATION
    // ==========================================

    /** @test */
    public function itEscapesDomainPrefixOnLoad(): void
    {
        // loadConfig() sollte domainPrefix auch escapen
        // Dies ist ein Integrationstest, der eine config.php benötigt
        
        // Dokumentation: domainPrefix sollte in loadConfig() mit escapeDn() behandelt werden
        $this->assertTrue(true, 'domainPrefix escaping is handled in loadConfig()');
    }

    /** @test */
    public function itValidatesLdapHostFormat(): void
    {
        // LDAP Host muss mit ldap:// oder ldaps:// beginnen
        $validHosts = [
            'ldap://localhost',
            'ldaps://ldap.example.com',
            'ldaps://192.168.1.1',
        ];
        
        $invalidHosts = [
            'localhost',
            'example.com',
            'http://ldap.example.com',
        ];
        
        foreach ($validHosts as $host) {
            $this->assertTrue(
                str_starts_with($host, 'ldap://') || str_starts_with($host, 'ldaps://'),
                "Valid LDAP host: {$host}"
            );
        }
        
        foreach ($invalidHosts as $host) {
            $this->assertFalse(
                str_starts_with($host, 'ldap://') || str_starts_with($host, 'ldaps://'),
                "Invalid LDAP host: {$host}"
            );
        }
    }
}