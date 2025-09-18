# 🔒 Security Policy

SecStore nimmt Sicherheit sehr ernst. Als Authentifizierungs- und Benutzerverwaltungsplattform implementieren wir strenge Sicherheitsmaßnahmen zum Schutz von Benutzerdaten und zur Verhinderung unbefugten Zugriffs.

---

## 🚨 Sicherheitslücken melden

### Responsible Disclosure

Wenn Sie eine Sicherheitslücke in SecStore entdecken, melden Sie diese bitte **verantwortungsvoll**:

**✅ Sicher melden über:**
- 📧 **E-Mail:** [Sicherheitskontakt einfügen] mit Betreff "SecStore Security"

**❌ NICHT öffentlich melden über:**
- GitHub Issues (diese sind öffentlich sichtbar)
- Soziale Medien oder Foren
- Öffentliche Mailinglisten

### Was Sie in Ihrer Meldung angeben sollten

Bitte includen Sie folgende Informationen:

- **Beschreibung** der Sicherheitslücke
- **Schritte zur Reproduktion** (wenn möglich)
- **Betroffene Versionen** von SecStore
- **Potentielle Auswirkungen** der Schwachstelle
- **Ihre Kontaktdaten** für Rückfragen

### Unser Prozess

1. **Bestätigung** innerhalb von 48 Stunden
2. **Analyse** und Bewertung der Schwachstelle
3. **Fix entwickeln** und testen
4. **Koordinierter Release** mit Security-Updates
5. **Öffentliche Bekanntgabe** nach dem Fix (mit Ihrer Zustimmung)

---

## 🛡️ Unterstützte Versionen

Wir stellen Sicherheitsupdates für folgende Versionen bereit:

| Version | Unterstützt | End of Life |
|---------|-------------|-------------|
| 1.3.x   | ✅ Vollständig | - |
| 1.2.x   | ✅ Sicherheitsupdates | 2025-06-01 |
| 1.1.x   | ⚠️ Kritische Fixes nur | 2025-03-01 |
| 1.0.x   | ❌ Nicht unterstützt | 2024-12-01 |
| < 1.0   | ❌ Nicht unterstützt | - |

> **💡 Empfehlung:** Aktualisieren Sie immer auf die neueste stabile Version für optimale Sicherheit.

---

## 🔐 Sicherheitsfeatures von SecStore

### Authentifizierung & Autorisierung
- ✅ **BCRYPT-Passwort-Hashing** (60 Zeichen, Salted)
- ✅ **Zwei-Faktor-Authentifizierung (2FA)** mit TOTP-Standard
- ✅ **Rollenbasierte Zugriffskontrolle** (RBAC)
- ✅ **LDAP-Integration** mit sicherer Authentifizierung
- ✅ **Brute-Force-Schutz** mit konfigurierbaren Parametern

### Session-Sicherheit
- ✅ **Session-Fingerprinting** (User-Agent/Accept-Language-Validierung)
- ✅ **Automatische Session-ID-Regeneration** alle 30 Minuten
- ✅ **Konfigurierbare Session-Timeouts**
- ✅ **Sichere Session-Cookies** (HttpOnly, SameSite, Secure)

### Angriffsprävention
- ✅ **CSRF-Schutz** für alle Formulare
- ✅ **Rate Limiting** mit granularen Scopes nach Sensitivität
- ✅ **Content Security Policy (CSP)** mit XSS-Schutz
- ✅ **HTTP Strict Transport Security (HSTS)**
- ✅ **X-Frame-Options, X-Content-Type-Options** Headers

### Überwachung & Logging
- ✅ **Security Dashboard** mit Echtzeit-Bedrohungsübersicht
- ✅ **Audit-Logging** aller sicherheitsrelevanten Aktionen
- ✅ **Rate-Limiting-Statistiken** und Violation-Tracking
- ✅ **Umfassende Log-Kategorien** (Security, Audit, System, Error)

---

## 🎯 Sicherheits-Best-Practices

### Für Administratoren

#### Server-Konfiguration
```bash
# HTTPS erzwingen
# Apache
Redirect 301 / https://yourdomain.com/

# Nginx  
return 301 https://$server_name$request_uri;

# Sichere PHP-Einstellungen
expose_php = Off
display_errors = Off
log_errors = On
```

#### Dateiberechtigungen
```bash
# Konfigurationsdatei schützen (aber webserver-beschreibbar lassen)
chmod 664 config.php
chown www-data:www-data config.php

# Cache-Verzeichnis
chmod 755 cache/
chown www-data:www-data cache/

# Logs vor öffentlichem Zugriff schützen
chmod 640 /var/log/secstore/
```

#### Firewall-Konfiguration
```bash
# Nur notwendige Ports öffnen
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP (für HTTPS-Redirect)
ufw allow 443/tcp   # HTTPS
ufw enable
```

### Für Entwickler

#### Sichere Coding-Practices
- **Niemals** Passwörter oder Secrets in Code committen
- **Immer** prepared statements für Datenbankabfragen verwenden
- **Immer** Input-Validierung und Sanitization durchführen
- **CSRF-Token** in allen staatverändernden Operationen verwenden

#### Development-Umgebung
```bash
# Debug-Modus nur in Development
ini_set('display_errors', '1'); // NUR in Development!

# Produktionsumgebung
ini_set('display_errors', '0');
ini_set('log_errors', '1');
```

---

## ⚠️ Bekannte Sicherheitsüberlegungen

### Rate Limiting
- **Konfiguration prüfen:** Zu restriktive Limits können DoS-ähnliche Effekte haben
- **Whitelist-IPs:** Admin-IPs können vom Rate Limiting ausgenommen werden
- **Monitoring:** Überwachen Sie Rate-Limit-Violations auf ungewöhnliche Muster

### Session Management
- **Session-Fixation:** Sessions werden automatisch bei Login/Logout regeneriert
- **Concurrent Sessions:** Ein Benutzer = eine aktive Session (konfigurierbar)
- **Timeout-Warnungen:** Benutzer werden vor Session-Ablauf gewarnt

### LDAP-Integration
- **TLS/SSL verwenden:** LDAP-Verbindungen sollten immer verschlüsselt sein
- **Bind-Credentials:** LDAP-Service-Account mit minimalen Rechten verwenden
- **Fallback-Authentication:** Lokale Auth als Backup zu LDAP konfigurieren

### E-Mail-Security
- **SMTP-TLS:** E-Mail-Versand immer über verschlüsselte Verbindungen
- **Rate-Limiting:** E-Mail-Versand ist rate-limited (Spam-Schutz)
- **Template-Injection:** E-Mail-Templates sind gegen Injection-Angriffe geschützt

---

## 🚀 Security Updates

### Update-Benachrichtigungen

**📬 Abonnieren Sie Sicherheitsupdates:**
- **GitHub Releases:** Watch-Funktion für automatische Benachrichtigungen
- **Security Advisories:** GitHub Security Advisories aktivieren
- **Changelog:** Regelmäßig [CHANGELOG.md](CHANGELOG.md) prüfen

### Update-Prozess

1. **Backup erstellen** vor jedem Update
   ```bash
   # Datenbank-Backup
   mysqldump -u root -p secstore > backup_$(date +%Y%m%d).sql
   
   # Dateien-Backup  
   tar -czf secstore_backup_$(date +%Y%m%d).tar.gz /path/to/secstore
   ```

2. **Staging-Umgebung** testen vor Produktion
3. **Funktionstest** nach Update durchführen

---

## ⚖️ Rechtliche Hinweise

### Safe Harbor

Wir verpflichten uns zu:

- **Keine rechtlichen Schritte** gegen Sicherheitsforscher, die sich an diese Richtlinien halten
- **Schnelle und faire Bearbeitung** aller Sicherheitsmeldungen
- **Anerkennung** Ihrer Beiträge (wenn gewünscht)

### Grenzen

**Erlaubt:**
- ✅ Automatisierte Vulnerability-Scanner auf eigenen Instanzen
- ✅ Source-Code-Analyse
- ✅ Responsible Disclosure

**Nicht erlaubt:**
- ❌ Social Engineering gegen SecStore-Nutzer
- ❌ DoS/DDoS-Angriffe auf Live-Systeme
- ❌ Datenzugriff/-manipulation ohne Erlaubnis
- ❌ Öffentliche Disclosure vor koordinierter Veröffentlichung

---

## 📚 Weiterführende Ressourcen

### Standards & Frameworks
- **OWASP Top 10:** [owasp.org/www-project-top-ten](https://owasp.org/www-project-top-ten/)
- **OWASP ASVS:** Application Security Verification Standard
- **NIST Cybersecurity Framework**

### Tools & Testing
- **OWASP ZAP:** Web Application Security Scanner
- **Nikto:** Web Server Scanner
- **SQLMap:** SQL Injection Testing

### Secure Development
- **OWASP Secure Coding Practices**
- **SANS Secure Programming Guidelines**
- **PHP Security Best Practices**

---

*Letzte Aktualisierung: Januar 2025*

**Sicherheit ist ein kontinuierlicher Prozess. Helfen Sie uns dabei, SecStore sicher zu halten!** 🛡️