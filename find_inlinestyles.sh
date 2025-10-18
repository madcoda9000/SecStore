#!/bin/bash

################################################################################
# find-inline-styles.sh
# 
# Scans all Latte templates for inline styles and generates a report
# with CSS class suggestions.
#
# Usage: ./find-inline-styles.sh
################################################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Directories to search
VIEWS_DIR="app/views"
TEMP_FILE=$(mktemp)
STYLES_FILE=$(mktemp)
REPORT_FILE="inline-styles-report.txt"

echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║           Inline Styles Scanner for Latte Templates           ║${NC}"
echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo ""

# Check if views directory exists
if [ ! -d "$VIEWS_DIR" ]; then
    echo -e "${RED}Error: $VIEWS_DIR directory not found!${NC}"
    exit 1
fi

echo -e "${BLUE}📁 Scanning directory: ${VIEWS_DIR}${NC}"
echo ""

# Find all .latte files and search for style= attributes
find "$VIEWS_DIR" -name "*.latte" | while read -r file; do
    # Search for style= in each file
    grep -n 'style="[^"]*"' "$file" | while IFS=: read -r line_num match; do
        # Extract just the style content
        style=$(echo "$match" | sed -n 's/.*style="\([^"]*\)".*/\1/p')
        
        if [ ! -z "$style" ]; then
            # Relative path from project root
            rel_path=${file#./}
            echo "$rel_path:$line_num:$style" >> "$TEMP_FILE"
            echo "$style" >> "$STYLES_FILE"
        fi
    done
done

# Count total occurrences
TOTAL_COUNT=$(wc -l < "$TEMP_FILE" 2>/dev/null || echo "0")

if [ "$TOTAL_COUNT" -eq 0 ]; then
    echo -e "${GREEN}✅ No inline styles found! Your templates are CSP-compliant!${NC}"
    rm -f "$TEMP_FILE" "$STYLES_FILE"
    exit 0
fi

echo -e "${YELLOW}⚠️  Found ${TOTAL_COUNT} inline style occurrences${NC}"
echo ""

################################################################################
# Generate Report
################################################################################

{
    echo "╔════════════════════════════════════════════════════════════════╗"
    echo "║           INLINE STYLES REPORT - $(date +%Y-%m-%d)                     ║"
    echo "╚════════════════════════════════════════════════════════════════╝"
    echo ""
    echo "Total inline styles found: ${TOTAL_COUNT}"
    echo ""
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "1. GROUPED BY STYLE (Most common first)"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    
    # Group and count identical styles
    sort "$STYLES_FILE" | uniq -c | sort -rn | while read -r count style; do
        echo "├─ Used ${count}x: style=\"${style}\""
        
        # Suggest CSS class name
        class_name=$(echo "$style" | \
            sed 's/font-size: *\([0-9.]*\)rem/icon-\1rem/' | \
            sed 's/width: *100%; *max-width: *\([0-9]*\)px/auth-card-\1px/' | \
            sed 's/cursor: *pointer/cursor-pointer/' | \
            sed 's/font-size: *\([0-9.]*\)rem/text-\1rem/' | \
            sed 's/display: *flex; *flex-direction: *column/flex-column-center/' | \
            sed 's/background-color: *#\([a-f0-9]*\)/bg-custom-\1/' | \
            sed 's/height: *\([0-9]*\)px/height-\1px/' | \
            sed 's/ /_/g' | \
            sed 's/:/-/g' | \
            sed 's/;$//' | \
            tr '[:upper:]' '[:lower:]')
        
        echo "│  Suggested class: .${class_name}"
        echo ""
    done
    
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "2. BY FILE LOCATION"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    
    current_file=""
    while IFS=: read -r file line style; do
        if [ "$file" != "$current_file" ]; then
            echo ""
            echo "📄 ${file}"
            current_file="$file"
        fi
        echo "   Line ${line}: style=\"${style}\""
    done < "$TEMP_FILE"
    
    echo ""
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "3. SUGGESTED CSS CLASSES"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "/* Add these to public/css/utilities.css */"
    echo ""
    
    # Generate unique CSS classes
    sort "$STYLES_FILE" | uniq | while read -r style; do
        # Generate class name
        class_name=$(echo "$style" | \
            sed 's/font-size: *\([0-9.]*\)rem/icon-\1rem/' | \
            sed 's/width: *100%; *max-width: *\([0-9]*\)px/auth-card-\1px/' | \
            sed 's/cursor: *pointer/cursor-pointer/' | \
            sed 's/font-size: *\([0-9.]*\)rem/text-\1rem/' | \
            sed 's/display: *flex; *flex-direction: *column/flex-column-center/' | \
            sed 's/background-color: *#\([a-f0-9]*\)/bg-custom-\1/' | \
            sed 's/height: *\([0-9]*\)px/height-\1px/' | \
            sed 's/ /_/g' | \
            sed 's/:/-/g' | \
            sed 's/;$//' | \
            tr '[:upper:]' '[:lower:]')
        
        echo ".${class_name} {"
        echo "    ${style}"
        echo "}"
        echo ""
    done
    
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "4. REPLACEMENT INSTRUCTIONS"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "Step 1: Create public/css/utilities.css with the suggested classes above"
    echo ""
    echo "Step 2: Add to your layout file (_mainLayout.latte or _authLayout.latte):"
    echo '        <link rel="stylesheet" href="/css/utilities.css">'
    echo ""
    echo "Step 3: Replace inline styles in templates. Examples:"
    echo ""
    
    # Show first 3 unique examples
    sort "$STYLES_FILE" | uniq | head -3 | while read -r style; do
        class_name=$(echo "$style" | \
            sed 's/font-size: *\([0-9.]*\)rem/icon-\1rem/' | \
            sed 's/width: *100%; *max-width: *\([0-9]*\)px/auth-card-\1px/' | \
            sed 's/cursor: *pointer/cursor-pointer/' | \
            tr '[:upper:]' '[:lower:]')
        
        echo "   Before: <div style=\"${style}\">"
        echo "   After:  <div class=\"${class_name}\">"
        echo ""
    done
    
    echo "Step 4: After replacing all styles, update index.php CSP header:"
    echo '        Change: "style-src '\''self'\'' '\''unsafe-inline'\''"'
    echo '        To:     "style-src '\''self'\''"  // ✅ Fully secure!'
    echo ""
    echo "Step 5: Test thoroughly and verify no styles are broken"
    echo ""
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "5. AUTOMATED FIX SCRIPT"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "Want to automate this? Run: ./fix-inline-styles.sh (coming next)"
    echo ""
    
} | tee "$REPORT_FILE"

# Terminal output with colors
echo -e "${GREEN}✅ Report generated: ${REPORT_FILE}${NC}"
echo ""
echo -e "${CYAN}📊 Summary:${NC}"
echo -e "   • Total inline styles: ${YELLOW}${TOTAL_COUNT}${NC}"
echo -e "   • Unique styles: ${YELLOW}$(sort "$STYLES_FILE" | uniq | wc -l)${NC}"
echo -e "   • Files affected: ${YELLOW}$(cut -d: -f1 "$TEMP_FILE" | sort -u | wc -l)${NC}"
echo ""
echo -e "${BLUE}Next steps:${NC}"
echo -e "   1. Review ${REPORT_FILE}"
echo -e "   2. Create public/css/utilities.css with suggested classes"
echo -e "   3. Replace inline styles in templates"
echo -e "   4. Update CSP header in index.php"
echo ""

# Cleanup
rm -f "$TEMP_FILE" "$STYLES_FILE"

exit 0