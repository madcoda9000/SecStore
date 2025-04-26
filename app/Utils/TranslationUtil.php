<?php

namespace App\Utils;

/**
 * Class Name: TranslationUtil
 *
 * Die Klasse stellt Methoden zur verfügung um eine ANmwendung mehrsprachig zu gestalten.
 *
 * @package App\Utils
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-04-24
 *
 * Änderungen:
 * - 1.0 (2025-04-24): Erstellt.
 */
class TranslationUtil
{
    protected static $lang = 'en';
    protected static $translations = [];

    public static function init($lang = null)
    {
        // 1. Wenn Sprache übergeben wird, diese verwenden
        if ($lang) {
            self::setLang($lang);
            return;
        }

        // 2. Wenn Sprache im Cookie, dann verwenden
        if (isset($_COOKIE['lang'])) {
            self::setLang($_COOKIE['lang']);
            return;
        }

        // 3. Fallback: Sprache aus Browser
        self::setLang(self::detectBrowserLanguage());
    }

    public static function setLang($lang)
    {
        $path = __DIR__ . '/../lang/' . $lang . '.php';
        if (file_exists($path)) {
            self::$lang = $lang;
            self::$translations = require $path;
            // Cookie setzen (1 Jahr gültig)
            setcookie('lang', $lang, time() + (365 * 24 * 60 * 60), '/');
        }
    }

    public static function getLang()
    {
        return self::$lang;
    }

    public static function t($key)
    {
        return self::$translations[$key] ?? $key;
    }

    protected static function detectBrowserLanguage()
    {
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2);
        return in_array($lang, ['de', 'en']) ? $lang : 'en';
    }
}
