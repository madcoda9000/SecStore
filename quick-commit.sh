#!/bin/bash

# ============================================================================
# Quick Commit Script for SecStore
# Interactive helper for fast Git commits with Conventional Commits
# ============================================================================

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ============================================================================
# Check if we're in a Git repository
# ============================================================================

if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo -e "${RED}❌ Error: Not a Git repository!${NC}"
    exit 1
fi

# ============================================================================
# Show current status
# ============================================================================

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}🔍 Current Git Status${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

git status --short

# Check if there are any changes
if [ -z "$(git status --porcelain)" ]; then
    echo ""
    echo -e "${GREEN}✅ Working directory clean - nothing to commit!${NC}"
    exit 0
fi

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# ============================================================================
# Ask if user wants to stage all changes
# ============================================================================

echo -ne "${YELLOW}📦 Add all changes to staging area? [Y/n]: ${NC}"
read -n 1 -r REPLY
echo

if [[ ! $REPLY =~ ^[Nn]$ ]]; then
    git add .
    echo -e "${GREEN}✅ All changes staged${NC}"
    echo ""
else
    echo -e "${YELLOW}⚠️  Skipped staging. Use 'git add <file>' manually.${NC}"
    echo ""
    echo -ne "${YELLOW}Continue with commit? [y/N]: ${NC}"
    read -n 1 -r REPLY
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${RED}❌ Aborted${NC}"
        exit 1
    fi
fi

# ============================================================================
# Show what will be committed
# ============================================================================

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}📋 Files to be committed:${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

git diff --cached --name-status

if [ -z "$(git diff --cached)" ]; then
    echo -e "${RED}❌ No files staged for commit!${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# ============================================================================
# Commit Type Selection
# ============================================================================

echo -e "${YELLOW}💬 Select commit type:${NC}"
echo ""
echo "  1) feat:      ✨ New feature"
echo "  2) fix:       🐛 Bug fix"
echo "  3) docs:      📝 Documentation"
echo "  4) refactor:  🔄 Code refactoring"
echo "  5) test:      🧪 Tests"
echo "  6) chore:     🔧 Maintenance"
echo "  7) style:     💅 Code style"
echo "  8) perf:      ⚡ Performance"
echo "  9) security:  🔒 Security"
echo " 10) breaking:  ⚠️  Breaking change"
echo " 11) custom     ✏️  Custom message (no prefix)"
echo ""

echo -ne "${YELLOW}Choose [1-11]: ${NC}"
read choice

case $choice in
    1)  PREFIX="feat" ;;
    2)  PREFIX="fix" ;;
    3)  PREFIX="docs" ;;
    4)  PREFIX="refactor" ;;
    5)  PREFIX="test" ;;
    6)  PREFIX="chore" ;;
    7)  PREFIX="style" ;;
    8)  PREFIX="perf" ;;
    9)  PREFIX="security" ;;
    10) PREFIX="breaking" ;;
    11) PREFIX="" ;;
    *)  
        echo -e "${RED}❌ Invalid choice. Using 'feat' as default.${NC}"
        PREFIX="feat"
        ;;
esac

# ============================================================================
# Get commit message
# ============================================================================

echo ""
if [ -n "$PREFIX" ]; then
    echo -ne "${YELLOW}📝 Commit message: ${PREFIX}: ${NC}"
    read MESSAGE
    FULL_MESSAGE="${PREFIX}: ${MESSAGE}"
else
    echo -ne "${YELLOW}📝 Commit message: ${NC}"
    read MESSAGE
    FULL_MESSAGE="${MESSAGE}"
fi

# Check if message is empty
if [ -z "$MESSAGE" ]; then
    echo -e "${RED}❌ Commit message cannot be empty!${NC}"
    exit 1
fi

# ============================================================================
# Optional: Add scope
# ============================================================================

echo ""
echo -ne "${YELLOW}🎯 Add scope? (e.g., auth, api, docs) [optional]: ${NC}"
read SCOPE

if [ -n "$SCOPE" ] && [ -n "$PREFIX" ]; then
    FULL_MESSAGE="${PREFIX}(${SCOPE}): ${MESSAGE}"
fi

# ============================================================================
# Show final commit message
# ============================================================================

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}📄 Final commit message:${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${GREEN}\"${FULL_MESSAGE}\"${NC}"
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# ============================================================================
# Confirm commit
# ============================================================================

echo -ne "${YELLOW}✅ Proceed with commit? [Y/n]: ${NC}"
read -n 1 -r REPLY
echo

if [[ $REPLY =~ ^[Nn]$ ]]; then
    echo -e "${RED}❌ Commit aborted${NC}"
    exit 1
fi

# ============================================================================
# Execute commit
# ============================================================================

echo ""
echo -e "${BLUE}🚀 Committing...${NC}"

if git commit -m "$FULL_MESSAGE"; then
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅ Commit successful!${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    
    # Check if CHANGELOG was updated
    if git diff HEAD~1 HEAD --name-only | grep -q "Documentation/CHANGELOG.md"; then
        echo -e "${GREEN}📋 CHANGELOG.md automatically updated${NC}"
    fi
else
    echo ""
    echo -e "${RED}❌ Commit failed!${NC}"
    exit 1
fi

# ============================================================================
# Optional: Push to remote
# ============================================================================

echo ""
echo -ne "${YELLOW}🌐 Push to remote? [y/N]: ${NC}"
read -n 1 -r REPLY
echo

if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Get current branch
    BRANCH=$(git rev-parse --abbrev-ref HEAD)
    
    echo ""
    echo -e "${BLUE}🚀 Pushing to origin/${BRANCH}...${NC}"
    
    if git push origin "$BRANCH"; then
        echo ""
        echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        echo -e "${GREEN}✅ Successfully pushed to remote!${NC}"
        echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    else
        echo ""
        echo -e "${RED}❌ Push failed!${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠️  Skipped push. Use 'git push' manually when ready.${NC}"
fi

echo ""
echo -e "${GREEN}🎉 All done!${NC}"
echo ""