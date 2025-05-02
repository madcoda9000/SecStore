# 🔐 SecStore

![PHP Version](https://img.shields.io/badge/PHP-%3E=8.3-blue?logo=php)
![License](https://img.shields.io/github/license/madcoda9000/SecStore?color=green)
![Last Commit](https://img.shields.io/github/last-commit/madcoda9000/SecStore)
![Maintained](https://img.shields.io/maintenance/yes/2025)

**SecStore** ist eine moderne, sichere und minimalistische Benutzerverwaltungs- und Authentifizierungsplattform auf Basis von [Flight PHP](http://flightphp.com/).  
Die Anwendung unterstützt eine breite Palette von Sicherheitsfunktionen wie Login, Registrierung, Passwort zurücksetzen, Zwei-Faktor-Authentifizierung (2FA) sowie ein Admin-Panel zur Verwaltung von Benutzern.

---

## ✨ Features

- 🔑 Login mit Benutzername & Passwort  
- 🔑 Optional LDAP-Authentifizierung (per User) 
- 📝 Registrierung (optional mit Begrüßungsmail)  
- 🔐 Zwei-Faktor-Authentifizierung (TOTP) (Per User erzwingbar)
- 🔁 Passwort zurücksetzen via E-Mail  
- 👤 Benutzerprofil bearbeiten (Passwort, 2FA, Name, E-Mail)  
- 👤 Mehrsprachig (zur Zeit EN und DE)
- 🛡️ Brute-Force-Schutz  
- 🧑‍💼 Admin-Bereich zur Benutzerverwaltung, Rollenverwaltung  
- 🌘 Dark-Mode mit Umschaltung  
- 🔍 Audit-, Mail-, Error-, Datenbank-, Request- und System-Logging 
- 📦 REST-konformes API-Backend  
- ⚙️ Automatische Datenbank-Migration  
- 🚀 CLI-Tool zum Generieren sicherer Schlüssel  
- 🛡️ konfigurierbarer Sessiontimer
---

## 📸 Screenshots

[Login](Documentation/Screenshots/Login.png)\
[Register](Documentation/Screenshots/Register.png)\
[Audit-Logs](Documentation/Screenshots/AuditLogs.png)\
[Usermanagement](Documentation/Screenshots/Users.png)\
[User-Profile](Documentation/Screenshots/UserProfile.png)\
[Settings](Documentation/Screenshots/Settings.png)


---

## 📦 Technologie-Stack

- **Backend**: PHP 8.3, Flight Microframework  
- **Templating**: Latte (https://latte.nette.org)  
- **Frontend**: Minimal-Design mit Darkmode
- **Datenbank**: MariaDB / MySQL  
- **ORM**: Idiorm + Paris  
- **Mail**: SMTP via PHPMailer  

---

## 🚀 Installation

Die vollständige Installationsanleitung findest du in [INSTALL.md](Documentation/INSTALL.md)

## 🧑‍💻 Für Entwickler

### Coding Standards

- PSR-12 konform  
- Unterstützt `phpcs` und `php-cs-fixer` zur Codeprüfung und -formatierung  

### Entwicklung starten

```bash
php -S localhost:8000 -t public
```

### Build-Tools (optional)

```bash
vendor/bin/phpcs
vendor/bin/php-cs-fixer fix
```

---

## 📁 Projektstruktur

```
SecStore/
├── app/                    # Applikation
├── app/controllers/        # Controller-Logik
├── app/Middleware/         # Middleware-Logik
├── app/Utils/              # Utility Klassen
├── app/Models/             # Model Klassen
├── app/views/              # Latte-Templates
├── public/                 # Öffentlicher Ordner (Entry Point)
├── app/routes.php          # Alle definierten Routen
├── app/DatabaseSetup.php   # Datenbankskripte
├── generate_key.php        # CLI zum Generieren eines geheimen Schlüssels
└── config.php              # Hauptkonfigurationsdatei
```

---

## 📄 Lizenz

Dieses Projekt steht unter der **MIT License**.  
Siehe [LICENSE](LICENSE) für Details.

---

## 🤝 Mitwirken

Pull Requests, Bugreports und Feedback sind jederzeit willkommen!  
Forke das Projekt, erstelle einen Branch (`feature/xyz`), und sende einen PR ✨

---

## 💬 Kontakt & Support

Bei Fragen oder Anregungen:
- GitHub Issues  
- Oder direkt per E-Mail an den Projektbetreuer

---

Danke, dass du **SecStore** verwendest! ✨
