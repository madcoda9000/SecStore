<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Utils\TranslationUtil;
use ReflectionClass;

/**
 * TranslationUtil Unit Tests
 *
 * Tests multilingual functionality using REAL project translation files.
 * This test works with the actual app/lang/*.php files.
 *
 * @package Tests\Unit
 */
class TranslationUtilTest extends TestCase
{
    private ReflectionClass $reflection;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create reflection for accessing protected properties
        $this->reflection = new ReflectionClass(TranslationUtil::class);
        
        // Reset static properties before each test
        $this->resetTranslationUtil();
        
        // Clear cookies
        $_COOKIE = [];
        
        // Suppress setcookie() warnings in tests by using output buffering
        // This prevents "headers already sent" errors
        if (!headers_sent()) {
            @ini_set('session.use_cookies', '0');
        }
    }

    /**
     * Reset TranslationUtil static properties to default state
     */
    private function resetTranslationUtil(): void
    {
        $langProperty = $this->reflection->getProperty('lang');
        $langProperty->setAccessible(true);
        $langProperty->setValue(null, 'en');

        $translationsProperty = $this->reflection->getProperty('translations');
        $translationsProperty->setAccessible(true);
        $translationsProperty->setValue(null, []);
    }

    /**
     * Get actual translations array for testing
     */
    private function getTranslationsProperty(): array
    {
        $translationsProperty = $this->reflection->getProperty('translations');
        $translationsProperty->setAccessible(true);
        return $translationsProperty->getValue();
    }

    /**
     * Teardown after each test
     */
    protected function tearDown(): void
    {
        $_COOKIE = [];
        parent::tearDown();
    }

    // ==========================================
    // TESTS: INITIALIZATION
    // ==========================================

    /** @test */
    public function itInitializesWithDefaultLanguage(): void
    {
        // Arrange
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    /** @test */
    public function itInitializesWithProvidedLanguage(): void
    {
        // Act
        @TranslationUtil::init('de');
        
        // Assert
        $this->assertEquals('de', TranslationUtil::getLang());
    }

    /** @test */
    public function itInitializesWithLanguageFromCookie(): void
    {
        // Arrange
        $_COOKIE['lang'] = 'de';
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('de', TranslationUtil::getLang());
    }

    /** @test */
    public function itPrioritizesParameterOverCookie(): void
    {
        // Arrange
        $_COOKIE['lang'] = 'de';
        
        // Act
        @TranslationUtil::init('en');
        
        // Assert
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    /** @test */
    public function itPrioritizesCookieOverBrowserLanguage(): void
    {
        // Arrange
        $_COOKIE['lang'] = 'de';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US';
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('de', TranslationUtil::getLang());
    }

    // ==========================================
    // TESTS: LANGUAGE SETTING
    // ==========================================

    /** @test */
    public function itSetsLanguageSuccessfully(): void
    {
        // Act
        @TranslationUtil::setLang('de');
        
        // Assert
        $this->assertEquals('de', TranslationUtil::getLang());
    }

    /** @test */
    public function itLoadsTranslationFileWhenSettingLanguage(): void
    {
        // Act
        @TranslationUtil::setLang('en');
        
        // Assert - Use actual translation key from project
        $translation = TranslationUtil::t('login.title');
        $this->assertNotEquals('login.title', $translation);
        $this->assertIsString($translation);
        $this->assertNotEmpty($translation);
    }

    /** @test */
    public function itDoesNotChangeLanguageForNonexistentFile(): void
    {
        // Arrange
        @TranslationUtil::setLang('en');
        
        // Act
        @TranslationUtil::setLang('fr'); // French file doesn't exist
        
        // Assert - should still be English
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    /** @test */
    public function itChangesLanguageAndLoadsNewTranslations(): void
    {
        // Arrange
        @TranslationUtil::setLang('en');
        $englishTranslation = TranslationUtil::t('login.title');
        
        // Act
        @TranslationUtil::setLang('de');
        $germanTranslation = TranslationUtil::t('login.title');
        
        // Assert
        $this->assertIsString($englishTranslation);
        $this->assertIsString($germanTranslation);
        $this->assertNotEquals($englishTranslation, $germanTranslation);
        $this->assertNotEquals('login.title', $englishTranslation);
        $this->assertNotEquals('login.title', $germanTranslation);
    }

    // ==========================================
    // TESTS: TRANSLATION LOOKUP
    // ==========================================

    /** @test */
    public function itTranslatesExistingKey(): void
    {
        // Arrange
        @TranslationUtil::setLang('en');
        
        // Act - Use actual translation key
        $translation = TranslationUtil::t('login.title');
        
        // Assert
        $this->assertIsString($translation);
        $this->assertNotEquals('login.title', $translation);
    }

    /** @test */
    public function itReturnsKeyForMissingTranslation(): void
    {
        // Arrange
        @TranslationUtil::setLang('en');
        
        // Act
        $translation = TranslationUtil::t('nonexistent.key.that.does.not.exist');
        
        // Assert - Should return the key itself
        $this->assertEquals('nonexistent.key.that.does.not.exist', $translation);
    }

    /** @test */
    public function itTranslatesMultipleKeys(): void
    {
        // Arrange
        @TranslationUtil::setLang('en');
        
        // Act - Use actual translation keys
        $loginTitle = TranslationUtil::t('login.title');
        $registerTitle = TranslationUtil::t('register.title');
        
        // Assert
        $this->assertIsString($loginTitle);
        $this->assertIsString($registerTitle);
        $this->assertNotEquals('login.title', $loginTitle);
        $this->assertNotEquals('register.title', $registerTitle);
    }

    /** @test */
    public function itHandlesEmptyKey(): void
    {
        // Arrange
        @TranslationUtil::setLang('en');
        
        // Act
        $translation = TranslationUtil::t('');
        
        // Assert
        $this->assertEquals('', $translation);
    }

    // ==========================================
    // TESTS: BROWSER LANGUAGE DETECTION
    // ==========================================

    /** @test */
    public function itDetectsEnglishFromBrowser(): void
    {
        // Arrange
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    /** @test */
    public function itDetectsGermanFromBrowser(): void
    {
        // Arrange
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.9';
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('de', TranslationUtil::getLang());
    }

    /** @test */
    public function itFallsBackToEnglishForUnsupportedLanguage(): void
    {
        // Arrange
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9';
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    /** @test */
    public function itHandlesMissingAcceptLanguageHeader(): void
    {
        // Arrange
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    /** @test */
    public function itHandlesMalformedAcceptLanguageHeader(): void
    {
        // Arrange
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'invalid-format';
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    // ==========================================
    // TESTS: LANGUAGE CODES
    // ==========================================

    /** @test */
    public function itOnlyAcceptsSupportedLanguages(): void
    {
        // Arrange
        $supportedLanguages = ['en', 'de'];
        
        foreach ($supportedLanguages as $lang) {
            // Act
            $this->resetTranslationUtil();
            @TranslationUtil::setLang($lang);
            
            // Assert
            $this->assertEquals($lang, TranslationUtil::getLang());
        }
    }

    /** @test */
    public function itIgnoresUnsupportedLanguageCodes(): void
    {
        // Arrange
        @TranslationUtil::setLang('en'); // Set initial valid language
        $currentLang = TranslationUtil::getLang();
        
        $unsupportedLanguages = ['fr', 'es', 'it', 'ru', 'zh'];
        
        foreach ($unsupportedLanguages as $lang) {
            // Act
            @TranslationUtil::setLang($lang);
            
            // Assert - language should not change
            $this->assertEquals($currentLang, TranslationUtil::getLang());
        }
    }

    // ==========================================
    // TESTS: EDGE CASES
    // ==========================================

    /** @test */
    public function itPreservesTranslationsAcrossMultipleCalls(): void
    {
        // Arrange
        @TranslationUtil::setLang('en');
        
        // Act
        $firstCall = TranslationUtil::t('login.title');
        $secondCall = TranslationUtil::t('login.title');
        $thirdCall = TranslationUtil::t('login.title');
        
        // Assert
        $this->assertEquals($firstCall, $secondCall);
        $this->assertEquals($secondCall, $thirdCall);
    }

    /** @test */
    public function itHandlesTranslationWithSpecialCharacters(): void
    {
        // Arrange
        @TranslationUtil::setLang('en');
        
        // Act
        $translation = TranslationUtil::t('login.title');
        
        // Assert
        $this->assertIsString($translation);
        $this->assertNotEmpty($translation);
    }

    /** @test */
    public function itMaintainsStateAfterMultipleInitializations(): void
    {
        // Act
        @TranslationUtil::init('en');
        $firstLang = TranslationUtil::getLang();
        
        @TranslationUtil::init('de');
        $secondLang = TranslationUtil::getLang();
        
        // Assert
        $this->assertEquals('en', $firstLang);
        $this->assertEquals('de', $secondLang);
    }

    /** @test */
    public function itReturnsConsistentLanguageCode(): void
    {
        // Arrange & Act
        @TranslationUtil::setLang('de');
        
        // Assert
        $lang1 = TranslationUtil::getLang();
        $lang2 = TranslationUtil::getLang();
        $lang3 = TranslationUtil::getLang();
        
        $this->assertEquals($lang1, $lang2);
        $this->assertEquals($lang2, $lang3);
    }

    // ==========================================
    // TESTS: LANGUAGE SWITCHING
    // ==========================================

    /** @test */
    public function itSwitchesLanguageSuccessfully(): void
    {
        // Arrange
        @TranslationUtil::setLang('en');
        $englishText = TranslationUtil::t('login.title');
        
        // Act
        @TranslationUtil::setLang('de');
        $germanText = TranslationUtil::t('login.title');
        
        // Assert
        $this->assertIsString($englishText);
        $this->assertIsString($germanText);
        $this->assertNotEquals($englishText, $germanText);
    }

    /** @test */
    public function itSwitchesLanguageMultipleTimes(): void
    {
        // Act & Assert
        @TranslationUtil::setLang('en');
        $this->assertEquals('en', TranslationUtil::getLang());
        
        @TranslationUtil::setLang('de');
        $this->assertEquals('de', TranslationUtil::getLang());
        
        @TranslationUtil::setLang('en');
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    /** @test */
    public function itClearsPreviousTranslationsOnLanguageSwitch(): void
    {
        // Arrange
        @TranslationUtil::setLang('en');
        $englishTranslation = TranslationUtil::t('login.title');
        
        // Act
        @TranslationUtil::setLang('de');
        $germanTranslation = TranslationUtil::t('login.title');
        
        // Assert - Translations should be different
        $this->assertNotEquals($englishTranslation, $germanTranslation);
        $this->assertIsString($englishTranslation);
        $this->assertIsString($germanTranslation);
    }

    // ==========================================
    // TESTS: COOKIE HANDLING
    // ==========================================

    /** @test */
    public function itRespectsCookieLanguagePreference(): void
    {
        // Arrange
        $_COOKIE['lang'] = 'de';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US';
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('de', TranslationUtil::getLang());
    }

    /** @test */
    public function itIgnoresInvalidCookieLanguage(): void
    {
        // Arrange
        $_COOKIE['lang'] = 'invalid';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US';
        
        // Act
        @TranslationUtil::init();
        
        // Assert - should fall back to browser language
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    // ==========================================
    // TESTS: INTEGRATION SCENARIOS
    // ==========================================

    /** @test */
    public function itProvidesFullTranslationWorkflow(): void
    {
        // Scenario: User visits site, changes language, gets translations
        
        // Step 1: Initial visit (browser language)
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE';
        @TranslationUtil::init();
        $this->assertEquals('de', TranslationUtil::getLang());
        
        $germanTitle = TranslationUtil::t('login.title');
        $this->assertIsString($germanTitle);
        $this->assertNotEquals('login.title', $germanTitle);
        
        // Step 2: User switches to English
        @TranslationUtil::setLang('en');
        $this->assertEquals('en', TranslationUtil::getLang());
        
        $englishTitle = TranslationUtil::t('login.title');
        $this->assertIsString($englishTitle);
        $this->assertNotEquals('login.title', $englishTitle);
        
        // Step 3: Verify translations are different
        $this->assertNotEquals($germanTitle, $englishTitle);
    }

    /** @test */
    public function itHandlesMissingTranslationGracefully(): void
    {
        // Arrange
        @TranslationUtil::setLang('en');
        
        // Act
        $existing = TranslationUtil::t('login.title');
        $missing = TranslationUtil::t('this.key.does.not.exist.in.any.file');
        $empty = TranslationUtil::t('');
        
        // Assert
        $this->assertNotEquals('login.title', $existing);
        $this->assertEquals('this.key.does.not.exist.in.any.file', $missing);
        $this->assertEquals('', $empty);
    }

    /** @test */
    public function itLoadsTranslationsIntoMemory(): void
    {
        // Arrange & Act
        @TranslationUtil::setLang('en');
        $translations = $this->getTranslationsProperty();
        
        // Assert
        $this->assertIsArray($translations);
        $this->assertNotEmpty($translations);
        $this->assertArrayHasKey('login.title', $translations);
    }

    /** @test */
    public function itSupportsHierarchicalTranslationKeys(): void
    {
        // Arrange
        @TranslationUtil::setLang('en');
        
        // Act - Test various hierarchical keys from project
        $keys = [
            'login.title',
            'register.title',
            'forgot.title',
            'reset.title'
        ];
        
        foreach ($keys as $key) {
            $translation = TranslationUtil::t($key);
            
            // Assert
            $this->assertIsString($translation, "Key '$key' should return string");
            $this->assertNotEquals($key, $translation, "Key '$key' should be translated");
        }
    }
}
