#!/bin/bash

# ============================================================================
# Git Hooks Setup Script für SecStore
# Installiert Pre-Commit und Prepare-Commit-Msg Hooks automatisch
# ============================================================================

echo ""
echo "🔧 SecStore Git Hooks Installation"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Prüfen ob wir in einem Git-Repository sind
if [ ! -d ".git" ]; then
    echo "❌ Fehler: Kein Git-Repository gefunden!"
    echo "   Bitte führe das Script im Projekt-Root aus."
    exit 1
fi

# Hooks-Verzeichnis erstellen falls nicht vorhanden
mkdir -p .git/hooks

# ============================================================================
# Pre-Commit Hook (Sicherheitsprüfung)
# ============================================================================

if [ -f "preCommitHook.sh" ]; then
    cp preCommitHook.sh .git/hooks/pre-commit
    chmod +x .git/hooks/pre-commit
    echo "✅ pre-commit Hook installiert (Sicherheitsprüfung)"
else
    echo "⚠️  preCommitHook.sh nicht gefunden - übersprungen"
fi

# ============================================================================
# Prepare-Commit-Msg Hook (Changelog-Automatisierung)
# ============================================================================

#if [ -f "prepareCommitMsg.sh" ]; then
#    cp prepareCommitMsg.sh .git/hooks/prepare-commit-msg
#    chmod +x .git/hooks/prepare-commit-msg
#    echo "✅ prepare-commit-msg Hook installiert (Changelog-Update)"
#else
#    echo "⚠️  prepareCommitMsg.sh nicht gefunden - übersprungen"
#fi

# ============================================================================
# Erfolgsmeldung
# ============================================================================

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎉 Installation abgeschlossen!"
echo ""
echo "📋 Installierte Hooks:"
if [ -f ".git/hooks/pre-commit" ]; then
    echo "   ✓ pre-commit         → Blockiert sensitive Dateien"
fi
if [ -f ".git/hooks/prepare-commit-msg" ]; then
    echo "   ✓ prepare-commit-msg → Aktualisiert CHANGELOG.md"
fi
echo ""
echo "💡 Verwendung:"
echo "   git commit -m \"feat: Add new feature\""
echo "   git commit -m \"fix: Correct bug in login\""
echo "   git commit -m \"docs: Update README\""
echo ""
echo "📖 Unterstützte Commit-Typen:"
echo "   • feat:     → ✨ Added (neue Features)"
echo "   • fix:      → 🐛 Fixed (Bugfixes)"
echo "   • docs:     → 📝 Documentation"
echo "   • refactor: → 🔄 Changed (Code-Refactoring)"
echo "   • test:     → 🧪 Testing"
echo "   • chore:    → 🔧 Maintenance"
echo "   • style:    → 💅 Style (Formatierung)"
echo "   • perf:     → ⚡ Performance"
echo "   • security: → 🔒 Security"
echo "   • breaking: → ⚠️ Breaking Changes"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""