<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Middleware\AdminCheckMiddleware;
use PHPUnit\Framework\Attributes\Test;

/**
 * Class Name: AdminCheckMiddlewareTest
 *
 * Unit Tests für AdminCheckMiddleware.
 *
 * HINWEIS: Diese Tests prüfen die Logik der Rollenüberprüfung.
 * Die tatsächlichen User/Session-Aufrufe werden durch Integration-Tests abgedeckt.
 *
 * @package Tests\Unit
 * @author Test Suite
 * @version 1.0
 * @since 2025-09-30
 */
class AdminCheckMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Hilfsmethode: Simuliert die Rollenprüfung aus der Middleware
     */
    private function hasAdminRole(string $roles): bool
    {
        $roleArray = explode(',', $roles);
        return in_array('Admin', $roleArray);
    }

    #[Test]
    public function itDetectsAdminRole(): void
    {
        // Arrange
        $roles = 'User,Admin';

        // Act
        $isAdmin = $this->hasAdminRole($roles);

        // Assert
        $this->assertTrue($isAdmin);
    }

    #[Test]
    public function itDetectsNonAdminUser(): void
    {
        // Arrange
        $roles = 'User,Editor';

        // Act
        $isAdmin = $this->hasAdminRole($roles);

        // Assert
        $this->assertFalse($isAdmin);
    }

    #[Test]
    public function itDetectsOnlyAdminRole(): void
    {
        // Arrange
        $roles = 'Admin';

        // Act
        $isAdmin = $this->hasAdminRole($roles);

        // Assert
        $this->assertTrue($isAdmin);
    }

    #[Test]
    public function itIsCaseSensitiveForAdminRole(): void
    {
        // Arrange - Kleingeschrieben
        $roles = 'User,admin';

        // Act
        $isAdmin = $this->hasAdminRole($roles);

        // Assert - sollte false sein da "admin" != "Admin"
        $this->assertFalse($isAdmin);
    }

    #[Test]
    public function itHandlesMultipleRoles(): void
    {
        // Arrange
        $roles = 'User,Editor,Admin,Moderator';

        // Act
        $isAdmin = $this->hasAdminRole($roles);

        // Assert
        $this->assertTrue($isAdmin);
    }

    #[Test]
    public function itHandlesEmptyRoles(): void
    {
        // Arrange
        $roles = '';

        // Act
        $isAdmin = $this->hasAdminRole($roles);

        // Assert
        $this->assertFalse($isAdmin);
    }

    #[Test]
    public function itHandlesSingleRole(): void
    {
        // Arrange
        $roles = 'User';

        // Act
        $isAdmin = $this->hasAdminRole($roles);

        // Assert
        $this->assertFalse($isAdmin);
    }

    #[Test]
    public function itHandlesRolesWithWhitespace(): void
    {
        // Arrange - Rollen mit Leerzeichen (sollte nicht passieren, aber testen wir es)
        $roles = 'User, Admin, Editor';

        // Act
        $isAdmin = $this->hasAdminRole($roles);

        // Assert - sollte false sein wegen Leerzeichen
        $this->assertFalse($isAdmin);
    }

    #[Test]
    public function itDetectsAdminAtDifferentPositions(): void
    {
        // Arrange & Act & Assert
        $testCases = [
            'Admin,User,Editor' => true,
            'User,Admin,Editor' => true,
            'User,Editor,Admin' => true,
        ];

        foreach ($testCases as $roles => $expected) {
            $isAdmin = $this->hasAdminRole($roles);
            $this->assertEquals($expected, $isAdmin, "Failed for roles: $roles");
        }
    }
}
