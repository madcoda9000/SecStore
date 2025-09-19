#!/bin/bash
echo "🔍 Prüfe auf sensitive Dateien..."

# Alle staged Dateien abrufen
STAGED_FILES=$(git diff --cached --name-only)

# Erlaubte Template/Beispiel-Dateien definieren
ALLOWED_TEMPLATES=(
    "config.php_TEMPLATE"
    "config.php.template" 
    "config.php.example"
    "config.example.php"
    ".env.example"
    ".env.template"
    "*.php.backup"          # ← Backup-Dateien erlauben
    "*.backup"              # ← Allgemeine Backup-Dateien
)

# Funktion: Prüfen ob Datei in erlaubten Templates ist (mit Wildcard-Support)
is_allowed_template() {
    local file="$1"
    for template in "${ALLOWED_TEMPLATES[@]}"; do
        # Exakte Übereinstimmung
        if [[ "$file" == "$template" ]]; then
            return 0  # Erlaubt
        fi
        # Wildcard-Übereinstimmung (für *.backup etc.)
        if [[ "$file" == $template ]]; then
            return 0  # Erlaubt
        fi
    done
    return 1  # Nicht erlaubt
}

# Sensitive Dateien finden (aber Templates ausschließen)
SENSITIVE_FILES=""
for file in $STAGED_FILES; do
    # Prüfe auf sensitive Muster
    if [[ "$file" =~ (config.*\.php|\.env|\.key|\.pem|\.credentials|.*copy.*|.*backup.*) ]]; then
        # Prüfe ob es eine erlaubte Template-Datei ist
        if ! is_allowed_template "$file"; then
            SENSITIVE_FILES="$SENSITIVE_FILES$file\n"
        fi
    fi
done

# Ergebnis auswerten
if [ ! -z "$SENSITIVE_FILES" ]; then
    echo "❌ WARNUNG: Sensitive Dateien erkannt!"
    echo -e "Blockierte Dateien:\n$SENSITIVE_FILES"
    echo "💡 Tipp: Template-Dateien wie 'config.php_TEMPLATE' und '*.backup' sind erlaubt."
    echo "Commit wurde abgebrochen. Bitte .gitignore prüfen."
    exit 1
fi

echo "✅ Keine sensitiven Dateien gefunden."
echo "📋 Erlaubte Template-Dateien: ${ALLOWED_TEMPLATES[*]}"