<?php

namespace App\Utils;

/**
 * Class Name: LogType
 *
 * Enum class for different log types.
 *
 * @package App\Utils
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
enum LogType: string
{
    case ERROR = 'ERROR';
    case AUDIT = 'AUDIT';
    case REQUEST = 'REQUEST';
    case SYSTEM = 'SYSTEM';
    case MAIL = 'MAIL';
    case SQL = 'SQL';
    case SECURITY = 'SECURITY';
}
