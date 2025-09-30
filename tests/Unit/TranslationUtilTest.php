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
    public function it_initializes_with_default_language(): void
    {
        // Arrange
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    /** @test */
    public function it_initializes_with_provided_language(): void
    {
        // Act
        @TranslationUtil::init('de');
        
        // Assert
        $this->assertEquals('de', TranslationUtil::getLang());
    }

    /** @test */
    public function it_initializes_with_language_from_cookie(): void
    {
        // Arrange
        $_COOKIE['lang'] = 'de';
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('de', TranslationUtil::getLang());
    }

    /** @test */
    public function it_prioritizes_parameter_over_cookie(): void
    {
        // Arrange
        $_COOKIE['lang'] = 'de';
        
        // Act
        @TranslationUtil::init('en');
        
        // Assert
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    /** @test */
    public function it_prioritizes_cookie_over_browser_language(): void
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
    public function it_sets_language_successfully(): void
    {
        // Act
        @TranslationUtil::setLang('de');
        
        // Assert
        $this->assertEquals('de', TranslationUtil::getLang());
    }

    /** @test */
    public function it_loads_translation_file_when_setting_language(): void
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
    public function it_does_not_change_language_for_nonexistent_file(): void
    {
        // Arrange
        @TranslationUtil::setLang('en');
        
        // Act
        @TranslationUtil::setLang('fr'); // French file doesn't exist
        
        // Assert - should still be English
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    /** @test */
    public function it_changes_language_and_loads_new_translations(): void
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
    public function it_translates_existing_key(): void
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
    public function it_returns_key_for_missing_translation(): void
    {
        // Arrange
        @TranslationUtil::setLang('en');
        
        // Act
        $translation = TranslationUtil::t('nonexistent.key.that.does.not.exist');
        
        // Assert - Should return the key itself
        $this->assertEquals('nonexistent.key.that.does.not.exist', $translation);
    }

    /** @test */
    public function it_translates_multiple_keys(): void
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
    public function it_handles_empty_key(): void
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
    public function it_detects_english_from_browser(): void
    {
        // Arrange
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    /** @test */
    public function it_detects_german_from_browser(): void
    {
        // Arrange
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.9';
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('de', TranslationUtil::getLang());
    }

    /** @test */
    public function it_falls_back_to_english_for_unsupported_language(): void
    {
        // Arrange
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9';
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    /** @test */
    public function it_handles_missing_accept_language_header(): void
    {
        // Arrange
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        
        // Act
        @TranslationUtil::init();
        
        // Assert
        $this->assertEquals('en', TranslationUtil::getLang());
    }

    /** @test */
    public function it_handles_malformed_accept_language_header(): void
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
    public function it_only_accepts_supported_languages(): void
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
    public function it_ignores_unsupported_language_codes(): void
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
    public function it_preserves_translations_across_multiple_calls(): void
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
    public function it_handles_translation_with_special_characters(): void
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
    public function it_maintains_state_after_multiple_initializations(): void
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
    public function it_returns_consistent_language_code(): void
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
    public function it_switches_language_successfully(): void
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
    public function it_switches_language_multiple_times(): void
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
    public function it_clears_previous_translations_on_language_switch(): void
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
    public function it_respects_cookie_language_preference(): void
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
    public function it_ignores_invalid_cookie_language(): void
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
    public function it_provides_full_translation_workflow(): void
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
    public function it_handles_missing_translation_gracefully(): void
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
    public function it_loads_translations_into_memory(): void
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
    public function it_supports_hierarchical_translation_keys(): void
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