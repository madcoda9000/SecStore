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
    ".env.EXAMPLE"
    ".env.template"
)

# Erlaubte Dateinamen-Muster (basename matching)
ALLOWED_PATTERNS=(
    "admin-backup-codes-display.js"
    "admin-backup-codes-display-min.js"
)

# Funktion: Prüfen ob Dateiname erlaubt ist
is_allowed_file() {
    local file="$1"
    local basename=$(basename "$file")
    
    # Prüfe exakte Übereinstimmung mit Templates
    for template in "${ALLOWED_TEMPLATES[@]}"; do
        if [[ "$file" == "$template" || "$basename" == "$template" ]]; then
            return 0
        fi
    done
    
    # Prüfe Patterns
    for pattern in "${ALLOWED_PATTERNS[@]}"; do
        if [[ "$basename" == "$pattern" ]]; then
            return 0
        fi
    done
    
    return 1
}

# Sensitive Dateien finden
SENSITIVE_FILES=""
for file in $STAGED_FILES; do
    # Prüfe auf sensitive Muster
    if [[ "$file" =~ (config.*\.php|\.env|\.key|\.pem|\.credentials|.*copy.*|.*backup.*) ]]; then
        if ! is_allowed_file "$file"; then
            SENSITIVE_FILES="$SENSITIVE_FILES$file\n"
        fi
    fi
done

# Ergebnis auswerten
if [ ! -z "$SENSITIVE_FILES" ]; then
    echo "❌ WARNUNG: Sensitive Dateien erkannt!"
    echo -e "Blockierte Dateien:\n$SENSITIVE_FILES"
    echo "💡 Tipp: Template-Dateien wie 'config.php_TEMPLATE' sind erlaubt."
    echo "Commit wurde abgebrochen. Bitte .gitignore prüfen."
    exit 1
fi

echo "✅ Keine sensitiven Dateien gefunden."
exit 0