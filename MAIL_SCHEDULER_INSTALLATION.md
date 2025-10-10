# Mail Scheduler - Manuelle Installation

## Übersicht
Der Mail Scheduler ist jetzt fast vollständig implementiert. Diese Anleitung hilft dir beim Abschluss der Installation.

## 🔧 Manuelle Anpassungen erforderlich

### 1. AdminController erweitern

Öffne `app/Controllers/AdminController.php` und füge **am Ende der Klasse** (vor der letzten `}`) den Inhalt aus dieser Datei ein:
```
app/Controllers/AdminController_MailScheduler_Extension.php
```

Die Methoden, die eingefügt werden müssen:
- `showMailScheduler()`
- `getSchedulerStatus()`
- `startScheduler()`
- `stopScheduler()`
- `getMailJobs()`
- `deleteMailJob()`

### 2. LogController erweitern

Öffne `app/Controllers/LogController.php` und füge **am Ende der Klasse** (vor der letzten `}`) den Inhalt aus dieser Datei ein:
```
app/Controllers/LogController_MailScheduler_Extension.php
```

Die Methoden, die eingefügt werden müssen:
- `showMailSchedulerLogs()`
- `fetchMailSchedulerLogs()`

### 3. Middleware aktivieren

Öffne `app/routes.php` und füge nach den bestehenden `use` Statements hinzu:

```php
use App\Middleware\SchedulerAutoStartMiddleware;
```

Dann füge nach `$csrfMiddleware = new CsrfMiddleware();` (ca. Zeile 30) hinzu:

```php
// Auto-start mail scheduler on each request
SchedulerAutoStartMiddleware::checkAndStart();
```

### 4. MailUtil erweitern (optional, aber empfohlen)

Öffne `app/Utils/MailUtil.php` und füge am Ende der Klasse folgende Helper-Methode hinzu:

```php
/**
 * Queue an email for background processing
 *
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $template Template name
 * @param array $data Template data
 * @return bool|object Job object or false on failure
 */
public static function queueMail(string $to, string $subject, string $template, array $data = [])
{
    return \App\Models\MailJob::createJob($to, $subject, $template, $data);
}
```

## ✅ Bereits erledigt

- ✓ Database Migration (mail_jobs Tabelle)
- ✓ LogType ENUM erweitert (MAILSCHEDULER)
- ✓ MailJob Model erstellt
- ✓ MailSchedulerService erstellt
- ✓ MailSchedulerWorker erstellt
- ✓ Routes hinzugefügt
- ✓ Middleware erstellt

## 📁 Latte Templates erstellen

Ich erstelle jetzt die notwendigen Latte Templates für die Admin-Oberfläche.

## 🧪 Nach der Installation testen

1. Navigiere zu `/admin/mail-scheduler`
2. Scheduler sollte automatisch starten
3. Teste einen Mail-Job:
```php
use App\Models\MailJob;

MailJob::createJob(
    'test@example.com',
    'Test Subject',
    'welcome',  // Dein Template-Name
    ['name' => 'Test User']
);
```
4. Überprüfe die Logs unter `/admin/logsMailScheduler`

## 🔍 Troubleshooting

### Scheduler startet nicht
- Prüfe Schreibrechte auf `/cache` Ordner
- Prüfe PHP exec/popen Funktionen (nicht disabled)
- Prüfe Logs unter `/admin/logsMailScheduler`

### Jobs werden nicht verarbeitet
- Prüfe ob Scheduler läuft: `/admin/mail-scheduler`
- Prüfe SMTP-Konfiguration in Settings
- Prüfe Worker-Logs: `tail -f cache/mail_scheduler_*.txt`

### Performance
- Worker prüft Queue alle 5 Sekunden
- Verarbeitet max. 5 Jobs gleichzeitig
- Cleanup alter Jobs: Standard 7 Tage

## 📞 Support

Bei Problemen:
1. Prüfe `/admin/logsMailScheduler`
2. Prüfe `/admin/system-info` für PHP-Konfiguration
3. Prüfe Dateirechte auf `/cache` Ordner
