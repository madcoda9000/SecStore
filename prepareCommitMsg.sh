#!/bin/bash

# ============================================================================
# Git Hook: prepare-commit-msg
# Automatische CHANGELOG.md Aktualisierung mit Kategorisierung
# ============================================================================

# Pfad zur CHANGELOG.md
CHANGELOG="Documentation/CHANGELOG.md"

# Git Hook Parameter
COMMIT_MSG_FILE=$1
COMMIT_SOURCE=$2

# Nur bei normalen Commits ausführen (nicht bei merge, squash, etc.)
if [ -n "$COMMIT_SOURCE" ]; then
    exit 0
fi

# Commit-Message auslesen
COMMIT_MSG=$(cat "$COMMIT_MSG_FILE")

# Leere oder Merge-Messages ignorieren
if [ -z "$COMMIT_MSG" ] || [[ "$COMMIT_MSG" =~ ^Merge ]]; then
    exit 0
fi

# Changelog existiert?
if [ ! -f "$CHANGELOG" ]; then
    echo "⚠️  CHANGELOG.md nicht gefunden bei: $CHANGELOG"
    exit 0
fi

# ============================================================================
# Conventional Commits Mapping
# ============================================================================

declare -A CATEGORY_MAP
CATEGORY_MAP["add"]="### ✨ Added"
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

# Letzte Version finden (z.B. [1.3.2])
LAST_VERSION=$(grep -oP '## \[\K[0-9]+\.[0-9]+\.[0-9]+' "$CHANGELOG" | head -n 1)

if [ -z "$LAST_VERSION" ]; then
    echo "⚠️  Keine Version in CHANGELOG.md gefunden"
    exit 0
fi

# ============================================================================
# CHANGELOG.md bearbeiten
# ============================================================================

TEMP_FILE=$(mktemp)
VERSION_FOUND=false
CATEGORY_FOUND=false
DATE_UPDATED=false

while IFS= read -r line; do
    # Version-Zeile gefunden und Datum noch nicht aktualisiert?
    if [[ "$line" =~ ^##\ \[$LAST_VERSION\] ]] && [ "$DATE_UPDATED" = false ]; then
        echo "## [$LAST_VERSION] - $CURRENT_DATE" >> "$TEMP_FILE"
        VERSION_FOUND=true
        DATE_UPDATED=true
        continue
    fi
    
    # Innerhalb der aktuellen Version?
    if [ "$VERSION_FOUND" = true ]; then
        # Nächste Version erreicht? -> Eintrag einfügen falls Kategorie nicht gefunden
        if [[ "$line" =~ ^##\ \[.*\] ]]; then
            if [ "$CATEGORY_FOUND" = false ]; then
                # Kategorie existiert nicht -> vor nächster Version einfügen
                echo "" >> "$TEMP_FILE"
                echo "$TARGET_CATEGORY" >> "$TEMP_FILE"
                echo "- **$COMMIT_TEXT**" >> "$TEMP_FILE"
                echo "" >> "$TEMP_FILE"
            fi
            VERSION_FOUND=false
        fi
        
        # Passende Kategorie gefunden?
        if [[ "$line" == "$TARGET_CATEGORY" ]]; then
            echo "$line" >> "$TEMP_FILE"
            CATEGORY_FOUND=true
            
            # Nächste Zeile lesen und neuen Eintrag davor einfügen
            IFS= read -r next_line
            echo "- **$COMMIT_TEXT**" >> "$TEMP_FILE"
            echo "$next_line" >> "$TEMP_FILE"
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
fi

# Changelog aktualisieren
mv "$TEMP_FILE" "$CHANGELOG"

# Changelog zur Stage hinzufügen
git add "$CHANGELOG"

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

exit 0