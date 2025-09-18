# 📋 Changelog

Alle wichtigen Änderungen an SecStore werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/),
und dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

---

## [Unreleased] - Nächste Version
### Geplant
- Docker-Container Support
- API-Endpoints für externe Integration
- Backup/Restore-Funktionalität
- Advanced Security Analytics

---

## [1.3.0] - 2025-01-15
### ✨ Neu hinzugefügt
- **Security Dashboard** mit Echtzeit-Übersicht über Bedrohungen
- **Rate Limiting System** mit konfigurierbaren Scopes nach Sensitivität
- **Rate Limiting Statistiken** und Live-Monitoring
- **Session Fingerprinting** zum Schutz vor Session-Hijacking
- **Automatische Session-ID Regeneration** alle 30 Minuten
- **Content Security Policy (CSP)** mit XSS-Schutz und HSTS

### 🔄 Geändert
- Verbesserte Admin-Oberfläche mit modernerer UI
- Optimierte Datenbankabfragen für bessere Performance
- Erweiterte Logging-Kategorien (SQL, Security, Mail)

### 🐛 Behoben
- Session-Timeout-Probleme bei gleichzeitigen Benutzern
- CSRF-Token-Validierung in AJAX-Requests
- Memory Leaks bei längeren Admin-Sessions

---

## [1.2.0] - 2024-12-20
### ✨ Neu hinzugefügt
- **LDAP-Integration** mit per-User-Konfiguration
- **Rollenbasierte Zugriffskontrolle** mit flexiblem Rollensystem
- **Mail-Template-System** mit Latte-Engine
- **Mehrsprachigkeit** (Deutsch und Englisch)
- **Dark-Mode** mit Benutzer-Präferenzen

### 🔄 Geändert
- Migration von PHP 8.1 auf PHP 8.3 Mindestanforderung
- Überarbeitung der Datenbankstruktur für bessere Skalierbarkeit
- Verbessertes Error-Handling mit detaillierten Log-Informationen

---

## [1.1.0] - 2024-11-10
### ✨ Neu hinzugefügt
- **Zwei-Faktor-Authentifizierung (2FA)** mit TOTP-Unterstützung
- **QR-Code-Generierung** für 2FA-Setup
- **2FA-Erzwingung** durch Administratoren pro Benutzer
- **Brute-Force-Schutz** mit konfigurierbaren Parametern
- **Audit-Logging** für alle sicherheitsrelevanten Aktionen
- **Passwort-Reset-Funktionalität** via E-Mail

### 🔄 Geändert
- Erweiterte Benutzerprofile mit 2FA-Management
- Verbesserte E-Mail-Templates für bessere Benutzererfahrung
- Optimierte Session-Verwaltung mit Fingerprinting

### 🔒 Sicherheit
- BCRYPT-Passwort-Hashing (60 Zeichen) implementiert
- CSRF-Schutz für alle Formulare aktiviert
- Session-Security durch User-Agent/Accept-Language-Validierung

---

## [1.0.0] - 2024-10-01 🎉
### ✨ Erste Veröffentlichung
- **Core Authentication System** mit Login/Logout
- **Benutzerregistrierung** (optional aktivierbar)
- **Admin-Panel** zur Benutzerverwaltung
- **E-Mail-System** mit SMTP-Unterstützung und Begrüßungsmails
- **Konfigurierbare Settings** über Web-Interface
- **Automatische Datenbank-Migration** und -Setup
- **CLI-Tool** zum Generieren sicherer Schlüssel

### 🛠️ Technischer Stack
- **PHP 8.3+** als Mindestanforderung
- **Flight PHP Microframework** für schlanke Performance  
- **Latte Template Engine** für moderne Templates
- **MariaDB/MySQL** mit UTF8MB4-Unterstützung
- **Idiorm + Paris ORM** für Datenbankoperationen
- **PHPMailer** für zuverlässigen E-Mail-Versand

### 📦 Kern-Features
- Responsive Design mit Bootstrap 5
- Minimalistisches und modernes Interface
- Vollständige CRUD-Operationen für Benutzer
- Konfigurierbare Session-Timeouts
- Umfangreiche Fehlerbehandlung und Logging
- PSR-12-konforme Codequalität

---

## [0.9.0] - 2024-09-15
### 🔧 Beta-Release
- Erste funktionsfähige Version für Testing
- Grundlegende Authentication-Features
- Admin-Interface Prototyp
- Database-Schema finalisiert

---

## [0.5.0] - 2024-08-20
### 🚀 Alpha-Release
- Projekt-Setup und Grundstruktur
- Flight PHP Framework Integration
- Erste Controller und Models
- Database Setup Scripts

---

## [0.1.0] - 2024-08-01
### 🌱 Projekt-Start
- Repository initialisiert
- Grundlegende Projektstruktur erstellt
- Dependency Management mit Composer
- Entwicklungsumgebung konfiguriert

---

## 📝 Legende

- ✨ **Neu hinzugefügt** - Neue Features und Funktionalitäten
- 🔄 **Geändert** - Änderungen an bestehenden Features
- ⚠️ **Deprecated** - Features, die in Zukunft entfernt werden
- ❌ **Entfernt** - Entfernte Features
- 🐛 **Behoben** - Bugfixes
- 🔒 **Sicherheit** - Sicherheitsverbesserungen und -fixes

---

## 🚀 Migration Guide

### Von 1.2.x zu 1.3.0
- **Rate Limiting:** Neue `$rateLimiting`-Konfiguration in `config.php` erforderlich
- **Session Security:** Bestehende Sessions werden einmalig invalidiert
- **Database:** Automatische Migration der `logs`-Tabelle für neue Security-Logs

### Von 1.1.x zu 1.2.0
- **PHP Version:** Update auf PHP 8.3+ erforderlich
- **LDAP Config:** Neue `$ldapSettings`-Sektion in `config.php` hinzufügen
- **Database:** Neue `roles`-Tabelle wird automatisch erstellt

### Von 1.0.x zu 1.1.0  
- **2FA Setup:** Bestehende Benutzer müssen 2FA neu konfigurieren
- **Database:** Migration der `users`-Tabelle für 2FA-Spalten
- **Mail Templates:** Neue E-Mail-Templates werden automatisch verwendet

---

## 🤝 Contributing

Interessiert an der Mitarbeit? Schauen Sie sich unsere [Contributing Guidelines](CONTRIBUTING.md) an!

**Changelog-Format:** Wir folgen den [Keep a Changelog](https://keepachangelog.com/) Konventionen für konsistente und nachvollziehbare Release-Notes.

---

*Letztes Update: Januar 2025*