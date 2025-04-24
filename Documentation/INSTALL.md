# 🛠️ Installation der SecStore-Anwendung

Diese Anleitung beschreibt die Schritte zur Installation und Einrichtung der Anwendung.

---

## ✅ Voraussetzungen

Stellen Sie sicher, dass Ihr System die folgenden Anforderungen erfüllt:

- **PHP**: Version **8.3 oder höher**
- Aktivierte PHP-Erweiterungen:
  - `curl`, `json`, `openssl`, `pdo`, `pdo_mysql`, `xml`, `xmlwriter`, `simplexml`
  - `zip`, `bcmath`, `gd`, `ctype`, `iconv`, `fileinfo`, `mbstring`, `tokenizer`, `filter`
- **Composer**: Installiert und konfiguriert
- **Datenbank**: MySQL oder MariaDB

💡 **Hinweis für Ubuntu-Nutzer**  
Verwenden Sie das mitgelieferte Script `Ubuntu_setup_php_dev_env.sh`, um alle erforderlichen Pakete automatisch zu installieren.

---

## 🚀 Installation

### 1. Repository klonen

```bash
git clone https://github.com/madcoda9000/SecStore.git
cd SecStore
```

### 2. Abhängigkeiten installieren

```bash
composer install
```

### 3. Berechtigungen setzen

Stellen Sie sicher, dass das Verzeichnis `cache` für den Webserver-Benutzer **beschreibbar** ist:

```bash
chmod -R 775 cache
chown -R www-data:www-data cache
```

(Anpassen je nach Webserver-Nutzer)

### 4. Konfiguration anpassen

```bash
cp config.php_TEMPLATE config.php
```

Bearbeiten Sie nun `config.php` und tragen Sie Ihre Werte ein:

#### 🔧 Datenbank (`$db`)
Zugangsdaten zur Datenbank eintragen.

#### 📧 Mail (`$mail`)
SMTP-Server konfigurieren.

#### 🌐 CORS (`$allowedHosts`)
Erlaubte Ursprünge für Frontend-Zugriffe eintragen, z. B.:

```php
$allowedHosts = [
  'http://localhost:8080',
  'capacitor://localhost',
  'ionic://localhost',
];
```

❗ Eine falsche Konfiguration blockiert API-Zugriffe im Frontend!

#### 🔐 Sicherheit (`$security`)

Generieren Sie einen sicheren Schlüssel mit dem CLI-Tool:

```bash
php generate_key.php
```

Tragen Sie den erzeugten Schlüssel in `config.php` ein:

```php
'key' => 'HIER_IHR_GEHEIMSCHLÜSSEL',
```

---

## 🛠️ Datenbank einrichten

Es ist **keine manuelle Einrichtung** erforderlich.  
Die Datenbanktabellen werden automatisch beim ersten Start der Anwendung erstellt – vorausgesetzt, die Zugangsdaten sind korrekt konfiguriert.

---

## 🌍 Webserver konfigurieren

Richten Sie Ihren Apache- oder Nginx-Server so ein, dass er auf das Verzeichnis `public/` zeigt.

Beispiel Apache vHost:

```apache
<VirtualHost *:80>
    ServerName secstore.local
    DocumentRoot /pfad/zur/anwendung/public

    <Directory /pfad/zur/anwendung/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## 🧪 Entwicklungstools (optional)

### Code-Formatierung mit PHP CS Fixer

```bash
vendor/bin/php-cs-fixer fix
```

### Code-Prüfung mit PHP CodeSniffer

```bash
vendor/bin/phpcs
```

---

## ▶️ Anwendung starten

### 💻 Lokale Entwicklung (integrierter Webserver)

```bash
php -S localhost:8000 -t public
```

Zugriff über: [http://localhost:8000](http://localhost:8000)

### 🌐 Produktivbetrieb (Apache oder Nginx)

Zugriff über Ihren eingerichteten Host, z. B.:  
[http://secstore.local](http://secstore.local)

---

## 🔐 Standard-Zugangsdaten

Nach der Installation können Sie sich mit folgendem Benutzer anmelden:

- **Benutzername**: `super.admin`  
- **Passwort**: `Test1000!`

⚠️ **Bitte ändern Sie diese Zugangsdaten nach dem ersten Login!**

---

## ✅ Erfolgreiche Installation

🎉 Wenn Sie diese Schritte abgeschlossen haben, ist Ihre Anwendung bereit zur Nutzung!  
Bei Fragen oder Problemen schauen Sie bitte in die Dokumentation oder melden sich im Projekt-Repository.

---
