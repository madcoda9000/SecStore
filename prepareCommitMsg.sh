#!/bin/bash

# ============================================================================
# Git Hook: prepare-commit-msg
# Automatische CHANGELOG.md Aktualisierung mit Kategorisierung
# ============================================================================

# DEBUG MODE - Aktivieren für Fehlersuche
DEBUG_LOG="/tmp/prepare-commit-debug.log"
echo "=== Hook started at $(date) ===" > "$DEBUG_LOG"

# Pfad zur CHANGELOG.md
CHANGELOG="Documentation/CHANGELOG.md"

# Git Hook Parameter
COMMIT_MSG_FILE=$1
COMMIT_SOURCE=$2

echo "COMMIT_MSG_FILE: $COMMIT_MSG_FILE" >> "$DEBUG_LOG"
echo "COMMIT_SOURCE: $COMMIT_SOURCE" >> "$DEBUG_LOG"

# Nur bei merge/squash commits überspringen
if [ "$COMMIT_SOURCE" = "merge" ] || [ "$COMMIT_SOURCE" = "squash" ]; then
    echo "Skipping: merge or squash commit" >> "$DEBUG_LOG"
    exit 0
fi

# Commit-Message auslesen
COMMIT_MSG=$(cat "$COMMIT_MSG_FILE")
echo "COMMIT_MSG: $COMMIT_MSG" >> "$DEBUG_LOG"

# Leere oder Merge-Messages ignorieren
if [ -z "$COMMIT_MSG" ] || [[ "$COMMIT_MSG" =~ ^Merge ]]; then
    echo "Skipping: empty or merge message" >> "$DEBUG_LOG"
    exit 0
fi

# Changelog existiert?
if [ ! -f "$CHANGELOG" ]; then
    echo "ERROR: CHANGELOG.md nicht gefunden bei: $CHANGELOG" >> "$DEBUG_LOG"
    exit 0
fi
echo "CHANGELOG found: $CHANGELOG" >> "$DEBUG_LOG"

# ============================================================================
# Conventional Commits Mapping
# ============================================================================

declare -A CATEGORY_MAP
CATEGORY_MAP["feat"]="### ✨ Added"
CATEGORY_MAP["fix"]="### 🐛 Fixed"
CATEGORY_MAP["docs"]="### 📝 Documentation"
CATEGORY_MAP["refactor"]="### 🔄 Changed"
CATEGORY_MAP["test"]="### 🧪 Testing"
CATEGORY_MAP["chore"]="### 🔧 Maintenance"
CATEGORY_MAP["style"]="### 💅 Style"
CATEGORY_MAP["perf"]="### ⚡ Performance"
CATEGORY_MAP["security"]="### 🔒 Security"
CATEGORY_MAP["breaking"]="### ⚠️ Breaking"

# Commit-Typ und Message extrahieren
COMMIT_TYPE=""
COMMIT_TEXT="$COMMIT_MSG"

if [[ "$COMMIT_MSG" =~ ^([a-z]+)(\(.*\))?:\ (.+)$ ]]; then
    COMMIT_TYPE="${BASH_REMATCH[1]}"
    COMMIT_TEXT="${BASH_REMATCH[3]}"
fi

# Kategorie bestimmen
if [ -n "$COMMIT_TYPE" ] && [ -n "${CATEGORY_MAP[$COMMIT_TYPE]}" ]; then
    TARGET_CATEGORY="${CATEGORY_MAP[$COMMIT_TYPE]}"
    EMOJI_TYPE="$COMMIT_TYPE"
else
    # Fallback: Keine Kategorisierung erkannt -> "Added"
    TARGET_CATEGORY="### ✨ Added"
    EMOJI_TYPE="added"
fi

# ============================================================================
# Datum und Version ermitteln
# ============================================================================

# Aktuelles Datum im Format YYYY-MM-DD
CURRENT_DATE=$(date +%Y-%m-%d)
echo "CURRENT_DATE: $CURRENT_DATE" >> "$DEBUG_LOG"

# Letzte Version finden (z.B. [1.3.2])
LAST_VERSION=$(sed -n 's/^## \[\([0-9][0-9]*\.[0-9][0-9]*\.[0-9][0-9]*\)\].*/\1/p' "$CHANGELOG" | head -n 1)
echo "LAST_VERSION: $LAST_VERSION" >> "$DEBUG_LOG"

if [ -z "$LAST_VERSION" ]; then
    echo "ERROR: Keine Version gefunden" >> "$DEBUG_LOG"
    exit 0
fi

echo "TARGET_CATEGORY: $TARGET_CATEGORY" >> "$DEBUG_LOG"
echo "COMMIT_TEXT: $COMMIT_TEXT" >> "$DEBUG_LOG"

# ============================================================================
# CHANGELOG.md bearbeiten
# ============================================================================

TEMP_FILE=$(mktemp)
echo "TEMP_FILE created: $TEMP_FILE" >> "$DEBUG_LOG"

VERSION_FOUND=false
CATEGORY_FOUND=false
DATE_UPDATED=false

while IFS= read -r line; do
    # Version-Zeile gefunden und Datum noch nicht aktualisiert?
    if [[ "$line" =~ ^##\ \[$LAST_VERSION\] ]] && [ "$DATE_UPDATED" = false ]; then
        echo "## [$LAST_VERSION] - $CURRENT_DATE" >> "$TEMP_FILE"
        VERSION_FOUND=true
        DATE_UPDATED=true
        echo "Found version line, updated date" >> "$DEBUG_LOG"
        continue
    fi
    
    # Innerhalb der aktuellen Version?
    if [ "$VERSION_FOUND" = true ]; then
        # Trennstriche oder nächste Version erreicht? -> Eintrag einfügen falls Kategorie nicht gefunden
        if [[ "$line" =~ ^---$ ]] || [[ "$line" =~ ^##\ \[.*\] ]]; then
            if [ "$CATEGORY_FOUND" = false ]; then
                # Kategorie existiert nicht -> vor Trennstrichen/nächster Version einfügen
                echo "" >> "$TEMP_FILE"
                echo "$TARGET_CATEGORY" >> "$TEMP_FILE"
                echo "- **$COMMIT_TEXT**" >> "$TEMP_FILE"
                echo "" >> "$TEMP_FILE"
                echo "Category not found, added new category before separator/next version" >> "$DEBUG_LOG"
            fi
            VERSION_FOUND=false
        fi
        
        # Passende Kategorie gefunden?
        if [[ "$line" == "$TARGET_CATEGORY" ]]; then
            echo "$line" >> "$TEMP_FILE"
            CATEGORY_FOUND=true
            echo "Found matching category: $TARGET_CATEGORY" >> "$DEBUG_LOG"
            
            # Nächste Zeile lesen und neuen Eintrag davor einfügen
            IFS= read -r next_line
            echo "- **$COMMIT_TEXT**" >> "$TEMP_FILE"
            echo "$next_line" >> "$TEMP_FILE"
            echo "Added entry under category" >> "$DEBUG_LOG"
            continue
        fi
    fi
    
    echo "$line" >> "$TEMP_FILE"
done < "$CHANGELOG"

# Falls Version am Ende der Datei und Kategorie nicht gefunden
if [ "$VERSION_FOUND" = true ] && [ "$CATEGORY_FOUND" = false ]; then
    echo "" >> "$TEMP_FILE"
    echo "$TARGET_CATEGORY" >> "$TEMP_FILE"
    echo "- **$COMMIT_TEXT**" >> "$TEMP_FILE"
    echo "Version at end of file, added category" >> "$DEBUG_LOG"
fi

echo "VERSION_FOUND: $VERSION_FOUND" >> "$DEBUG_LOG"
echo "CATEGORY_FOUND: $CATEGORY_FOUND" >> "$DEBUG_LOG"
echo "DATE_UPDATED: $DATE_UPDATED" >> "$DEBUG_LOG"

# Changelog aktualisieren
mv "$TEMP_FILE" "$CHANGELOG"
echo "CHANGELOG updated: moved temp file to $CHANGELOG" >> "$DEBUG_LOG"

# Changelog zur Stage hinzufügen
git add "$CHANGELOG"
echo "CHANGELOG added to git stage" >> "$DEBUG_LOG"

# ============================================================================
# Erfolgs-Ausgabe
# ============================================================================

echo ""
echo "✅ CHANGELOG.md aktualisiert"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📦 Version: $LAST_VERSION"
echo "📅 Datum:   $CURRENT_DATE"
echo "📝 Typ:     $EMOJI_TYPE"
echo "💬 Message: $COMMIT_TEXT"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo "=== Hook completed successfully ===" >> "$DEBUG_LOG"
exit 0