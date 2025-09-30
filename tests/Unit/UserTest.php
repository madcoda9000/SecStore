<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use ORM;
use App\Utils\LogUtil;
use App\Utils\LogType;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class Name: UserTest
 *
 * Unit Tests für die User Model Klasse.
 *
 * @package Tests\Unit
 * @author Test Suite
 * @version 1.0
 * @since 2025-09-30
 */
class UserTest extends TestCase
{
    private $mockOrmInstance;
    private $mockQueryLog;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock ORM Query Log
        $this->mockQueryLog = ['SELECT * FROM users WHERE id = 1'];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Hilfsmethode: Erstellt einen Mock für ORM::for_table()->create()
     */
    private function mockOrmCreate(array $userData, bool $saveSuccess = true): void
    {
        $mockUser = Mockery::mock('stdClass');
        
        foreach ($userData as $key => $value) {
            $mockUser->$key = $value;
        }
        
        $mockUser->shouldReceive('save')
            ->andReturn($saveSuccess);
        
        $mockForTable = Mockery::mock('alias:ORM');
        $mockForTable->shouldReceive('for_table')
            ->with('users')
            ->andReturnSelf();
        $mockForTable->shouldReceive('create')
            ->andReturn($mockUser);
        $mockForTable->shouldReceive('configure')
            ->with('logging', Mockery::anyOf(true, false))
            ->andReturn(true);
        $mockForTable->shouldReceive('get_query_log')
            ->andReturn($this->mockQueryLog);
    }

    /**
     * Hilfsmethode: Erstellt einen Mock für ORM::for_table()->where()->find_one()
     */
    private function mockOrmFindOne($returnValue): void
    {
        $mockForTable = Mockery::mock('alias:ORM');
        $mockForTable->shouldReceive('configure')
            ->with('logging', Mockery::anyOf(true, false))
            ->andReturn(true);
        $mockForTable->shouldReceive('for_table')
            ->with('users')
            ->andReturnSelf();
        $mockForTable->shouldReceive('where')
            ->andReturnSelf();
        $mockForTable->shouldReceive('find_one')
            ->andReturn($returnValue);
        $mockForTable->shouldReceive('get_query_log')
            ->andReturn($this->mockQueryLog);
    }

    // ========================================================================
    // CREATE TESTS
    // ========================================================================

    #[Test]
    public function itCreatesUserSuccessfully(): void
    {
        // Arrange
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'firstname' => 'Test',
            'lastname' => 'User',
            'status' => 1,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'roles' => 'User',
            'ldapEnabled' => 0
        ];

        $this->mockOrmCreate($userData, true);

        // Act
        $user = User::createUser(
            $userData['username'],
            $userData['email'],
            $userData['firstname'],
            $userData['lastname'],
            $userData['status'],
            $userData['password'],
            $userData['roles'],
            $userData['ldapEnabled']
        );

        // Assert
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('test@example.com', $user->email);
    }

    #[Test]
    public function itCreatesUserWithMultipleRoles(): void
    {
        // Arrange
        $userData = [
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'firstname' => 'Admin',
            'lastname' => 'User',
            'status' => 1,
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'roles' => 'User,Admin,IT',
            'ldapEnabled' => 0
        ];

        $this->mockOrmCreate($userData, true);

        // Act
        $user = User::createUser(
            $userData['username'],
            $userData['email'],
            $userData['firstname'],
            $userData['lastname'],
            $userData['status'],
            $userData['password'],
            $userData['roles']
        );

        // Assert
        $this->assertNotNull($user);
        $this->assertEquals('User,Admin,IT', $user->roles);
    }

    #[Test]
    public function itReturnsNullWhenUserCreationFails(): void
    {
        // Arrange
        $userData = [
            'username' => 'failuser',
            'email' => 'fail@example.com',
            'firstname' => 'Fail',
            'lastname' => 'User',
            'status' => 1,
            'password' => 'hashed_password',
            'roles' => 'User',
            'ldapEnabled' => 0
        ];

        $this->mockOrmCreate($userData, false);

        // Act
        $user = User::createUser(
            $userData['username'],
            $userData['email'],
            $userData['firstname'],
            $userData['lastname'],
            $userData['status'],
            $userData['password'],
            $userData['roles']
        );

        // Assert
        $this->assertNull($user);
    }

    #[Test]
    public function itCreatesUserWithLdapEnabled(): void
    {
        // Arrange
        $userData = [
            'username' => 'ldapuser',
            'email' => 'ldap@example.com',
            'firstname' => 'LDAP',
            'lastname' => 'User',
            'status' => 1,
            'password' => '',
            'roles' => 'User',
            'ldapEnabled' => 1
        ];

        $this->mockOrmCreate($userData, true);

        // Act
        $user = User::createUser(
            $userData['username'],
            $userData['email'],
            $userData['firstname'],
            $userData['lastname'],
            $userData['status'],
            $userData['password'],
            $userData['roles'],
            1
        );

        // Assert
        $this->assertNotNull($user);
        $this->assertEquals(1, $user->ldapEnabled);
    }

    // ========================================================================
    // READ TESTS - findUserById
    // ========================================================================

    #[Test]
    public function itFindsUserById(): void
    {
        // Arrange
        $mockUser = Mockery::mock('stdClass');
        $mockUser->id = 1;
        $mockUser->username = 'testuser';
        $mockUser->email = 'test@example.com';

        $this->mockOrmFindOne($mockUser);

        // Act
        $user = User::findUserById(1);

        // Assert
        $this->assertNotNull($user);
        $this->assertEquals(1, $user->id);
        $this->assertEquals('testuser', $user->username);
    }

    #[Test]
    public function itReturnsFalseWhenUserNotFoundById(): void
    {
        // Arrange
        $this->mockOrmFindOne(false);

        // Act
        $user = User::findUserById(999);

        // Assert
        $this->assertFalse($user);
    }

    #[Test]
    #[DataProvider('provideInvalidUserIds')]
    public function itHandlesInvalidUserIds($invalidId): void
    {
        // Arrange
        $this->mockOrmFindOne(false);

        // Act
        $user = User::findUserById($invalidId);

        // Assert
        $this->assertFalse($user);
    }

    public static function provideInvalidUserIds(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
            'non_existent' => [99999],
        ];
    }

    // ========================================================================
    // UPDATE TESTS - setNewPassword
    // ========================================================================

    #[Test]
    public function itSetsNewPasswordSuccessfully(): void
    {
        // Arrange
        $mockUser = Mockery::mock('stdClass');
        $mockUser->id = 1;
        $mockUser->reset_token = 'old_token';
        $mockUser->shouldReceive('save')->andReturn(true);

        $this->mockOrmFindOne($mockUser);

        $newPassword = password_hash('newPassword123', PASSWORD_DEFAULT);

        // Act
        $result = User::setNewPassword(1, $newPassword);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('', $mockUser->reset_token);
        $this->assertEquals($newPassword, $mockUser->password);
    }

    #[Test]
    public function itReturnsFalseWhenUserNotFoundForPasswordReset(): void
    {
        // Arrange
        $this->mockOrmFindOne(false);

        // Act
        $result = User::setNewPassword(999, 'newPassword');

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function itClearsResetTokenWhenSettingNewPassword(): void
    {
        // Arrange
        $mockUser = Mockery::mock('stdClass');
        $mockUser->id = 1;
        $mockUser->reset_token = 'some_reset_token_12345';
        $mockUser->shouldReceive('save')->andReturn(true);

        $this->mockOrmFindOne($mockUser);

        // Act
        User::setNewPassword(1, 'newPassword');

        // Assert
        $this->assertEquals('', $mockUser->reset_token);
    }

    // ========================================================================
    // DELETE TESTS
    // ========================================================================

    #[Test]
    public function itDeletesUserSuccessfully(): void
    {
        // Arrange
        $mockUser = Mockery::mock('stdClass');
        $mockUser->id = 1;
        $mockUser->shouldReceive('delete')->andReturn(true);

        $this->mockOrmFindOne($mockUser);

        // Act
        $result = User::deleteUser(1);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function itReturnsFalseWhenDeletingNonExistentUser(): void
    {
        // Arrange
        $this->mockOrmFindOne(false);

        // Act
        $result = User::deleteUser(999);

        // Assert
        $this->assertFalse($result);
    }

    // ========================================================================
    // SESSION TESTS
    // ========================================================================

    #[Test]
    public function itGetsActiveSessionId(): void
    {
        // Arrange
        $mockUser = Mockery::mock('stdClass');
        $mockUser->id = 1;
        $mockUser->activeSessionId = 'session123abc';

        $this->mockOrmFindOne($mockUser);

        // Act
        $sessionId = User::getActiveSessionId(1);

        // Assert
        $this->assertEquals('session123abc', $sessionId);
    }

    #[Test]
    public function itReturnsNullWhenUserHasNoActiveSession(): void
    {
        // Arrange
        $this->mockOrmFindOne(false);

        // Act
        $sessionId = User::getActiveSessionId(999);

        // Assert
        $this->assertNull($sessionId);
    }

    #[Test]
    public function itSetsActiveSessionId(): void
    {
        // Arrange
        $mockUser = Mockery::mock('stdClass');
        $mockUser->id = 1;
        $mockUser->shouldReceive('save')->andReturn(true);

        $this->mockOrmFindOne($mockUser);

        // Mock session_id()
        if (!function_exists('session_id')) {
            function session_id()
            {
                return 'mocked_session_id_123';
            }
        }

        // Act
        $result = User::setActiveSessionId(1);

        // Assert
        $this->assertTrue($result);
    }

    // ========================================================================
    // IP ADDRESS TESTS
    // ========================================================================

    #[Test]
    public function itGetsLastKnownIp(): void
    {
        // Arrange
        $mockUser = Mockery::mock('stdClass');
        $mockUser->id = 1;
        $mockUser->lastKnownIp = '192.168.1.100';

        $this->mockOrmFindOne($mockUser);

        // Act
        $ip = User::getLastKnownIp(1);

        // Assert
        $this->assertEquals('192.168.1.100', $ip);
    }

    #[Test]
    public function itReturnsNullWhenUserHasNoLastKnownIp(): void
    {
        // Arrange
        $this->mockOrmFindOne(false);

        // Act
        $ip = User::getLastKnownIp(999);

        // Assert
        $this->assertNull($ip);
    }

    #[Test]
    public function itUpdatesLastKnownIp(): void
    {
        // Arrange
        $mockUser = Mockery::mock('stdClass');
        $mockUser->id = 1;
        $mockUser->lastKnownIp = '192.168.1.1';
        $mockUser->shouldReceive('save')->andReturn(true);

        $this->mockOrmFindOne($mockUser);

        // Act
        $result = User::updateLastKnownIp(1, '192.168.1.200');

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('192.168.1.200', $mockUser->lastKnownIp);
    }

    #[Test]
    public function itReturnsFalseWhenUpdatingIpForNonExistentUser(): void
    {
        // Arrange
        $this->mockOrmFindOne(false);

        // Act
        $result = User::updateLastKnownIp(999, '192.168.1.1');

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('provideValidIpAddresses')]
    public function itHandlesDifferentIpFormats(string $ipAddress): void
    {
        // Arrange
        $mockUser = Mockery::mock('stdClass');
        $mockUser->id = 1;
        $mockUser->shouldReceive('save')->andReturn(true);

        $this->mockOrmFindOne($mockUser);

        // Act
        $result = User::updateLastKnownIp(1, $ipAddress);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals($ipAddress, $mockUser->lastKnownIp);
    }

    public static function provideValidIpAddresses(): array
    {
        return [
            'ipv4' => ['192.168.1.1'],
            'ipv6' => ['2001:0db8:85a3:0000:0000:8a2e:0370:7334'],
            'localhost_ipv4' => ['127.0.0.1'],
            'localhost_ipv6' => ['::1'],
        ];
    }
}
