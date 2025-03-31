<?php

namespace App\Utils;

enum LogType: string
{
    case ERROR = 'ERROR';
    case AUDIT = 'AUDIT';
    case REQUEST = 'REQUEST';
    case SYSTEM = 'SYSTEM';
    case MAIL = 'MAIL';
    case SQL = 'SQL';
}
