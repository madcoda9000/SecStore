<?php

declare(strict_types=1);

use App\Utils\SodiumEncryption;

require 'vendor/autoload.php';

// Generate a new encryption key and print it in a format suitable for config.php

$key = SodiumEncryption::generateKey();
$encoded = SodiumEncryption::encodeKey($key);

echo "Raw key length: " . strlen($key) . " bytes\n";
echo "Config value (Base64URL): " . $encoded . "\n\n";
echo "👉 Diesen Wert bitte in config.php als ENCRYPTION_KEY speichern: " . $encoded . "\n";