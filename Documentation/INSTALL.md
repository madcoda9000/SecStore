# Installation der Anwendung

Diese Anleitung beschreibt die Schritte zur Installation und Einrichtung der Anwendung.

## Voraussetzungen

Stellen Sie sicher, dass Ihr System die folgenden Anforderungen erfüllt:

- **PHP**: Version 8.3 oder höher
- Aktivierte PHP-Erweiterungen:
  - `curl`
  - `json`
  - `openssl`
  - `pdo`
  - `pdo_mysql`
  - `xml`
  - `xmlwriter`
  - `simplexml`
  - `zip`
  - `bcmath`
  - `gd`
  - `ctype`
  - `iconv`
  - `fileinfo`
  - `mbstring`
  - `tokenizer`
  - `filter`
- **Composer**: Installiert und konfiguriert
- **Datenbank**: MySql oder MariaDB

## Installation

1. **Repository klonen**  
     Klonen Sie das Repository in das gewünschte Verzeichnis:
   
   ```bash
   git clone https://github.com/madcoda9000/SecStore.git
   cd SecStore
   ```

2. **Abhängigkeiten installieren**  
     Installieren Sie die benötigten PHP-Pakete mit Composer:
   
   ```bash
   composer install
   ```

3. **Berechtigungen Überprüfen**
    Stellen Sie sicher, dass das `cache` verzeichnis für den Webserver Benutzer beschreibbar ist.

4. **Konfiguration anpassen**  
   
     Kopieren Sie die Beispiel-Konfigurationsdatei und passen Sie sie an:
   
   ```bash
   cp config.php_TEMPLATE config.php
   ```
   
     Bearbeiten Sie die `config.php`-Datei und tragen Sie die entsprechenden Werte ein.
   
     **Datenbank ($db)**
     Tragen Sie in diesem Abschnitt die entsprechenden Werte für Ihren Datenbankserver ein.
   
     **Mail ($mail)**
     Tragen Sie in diesem Abschnitt die entsprechenden Werte für Ihren Mailserver ein.
   
     **Cors ($allowedHosts)**
     Hier müssen Sie die Ihrem Webserver entsprechnden http werte erstzen. Wenn Sie die Anwendung z.b. auf http://localhost:8080 publiziert haben, sollte der Eintrag wie folgt aussehen:
   
   ```code
   /**
   * CORS: define allowd Hosts
   * NOTE: change this according to your setup
   */
   $allowedHosts = [
   'capacitor://localhost',
   'ionic://localhost',
   'http://localhost',
   'http://localhost:8080',
   ];
   ```
   
     Diese Einstellung ist für einen reibungslosen Betrieb sehr wichtig, da bei fehlerhafter konfiguration der Anwendung der Zugriff nauf das API-Backend verweigert wird!

5. **Datenbank einrichten**  
     Eine manuelle Einrichtung ist nicht erforderlich. Die Datenbank und Tabellen werden beim start der Anwendung automatisch erstellt, vorrausgesetzt Sie haben in der Konfigurationsdatei (config.php) die entsprechenden Zugangsdaten eingetragen.

6. **Webserver konfigurieren**  
     Richten Sie Ihren Webserver (z. B. Apache oder Nginx) so ein, dass er auf das `public`-Verzeichnis der Anwendung zeigt.

## Entwicklungstools (optional)

Für die Entwicklung stehen folgende Tools zur Verfügung:

- **PHP CS Fixer**: Code-Formatierung
  
  ```bash
  vendor/bin/php-cs-fixer fix
  ```
- **PHP CodeSniffer**: Code-Qualitätsprüfung
  
  ```bash
  vendor/bin/phpcs
  ```

## Starten der Anwendung

**Integrierter Webserver**
Sollten Sie keinen eigenen Webserver betreiben, können Sie die Anwendung über den integrierten PHP-Server (für Entwicklungszwecke oder zum testen) starten:

```bash
php -S localhost:8000 -t public
```

Besuchen Sie die Anwendung unter [http://localhost:8000](http://localhost:8000).

**Apache oder Nginx**
Sollten Sie einen eigenen Webserver betreiben, können Sie die Anwendung unter dem von Ihnen konfigurierten Port erreichn. Z.b. http://localhost oder https://localhost

## Anmeldung

Nach erfolgreicher Installation können Sie sich mit den folgenden Zugangsdaten anmelden:

**Username**: super.admin
**Passwort**: Test1000!
