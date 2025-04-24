<?php
// Diese Datei generiert einen sicheren Schlüssel für die Verschlüsselung.
// Der Schlüssel wird in der Konfigurationsdatei config.php gespeichert.
// Der Schlüssel ist 256 Bit lang und wird in hexadezimaler Form ausgegeben.
// Der Schlüssel wird mit der Funktion random_bytes() generiert, die kryptographisch sichere Zufallszahlen erzeugt.
$key = bin2hex(random_bytes(32)); // 64 Zeichen, 256-Bit
echo "🔐 Secret key: Please insert the key in config.php in section security\n";
echo $key . "\n";
