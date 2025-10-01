# 🎣 Git Hooks for SecStore

Automatic CHANGELOG.md management with Conventional Commits.

---

## 📋 Table of Contents

- [Installation](#-installation)
- [Usage](#-usage)
- [Commit Types](#-commit-types)
- [Examples](#-examples)
- [CHANGELOG Structure](#-changelog-structure)
- [Troubleshooting](#-troubleshooting)

---

## 🚀 Installation

### Quick Installation

```bash
# Make script executable
chmod +x setup-hooks.sh

# Install hooks
./setup-hooks.sh
```

### Manual Installation

```bash
# Pre-Commit Hook (Security)
cp preCommitHook.sh .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit

# Prepare-Commit-Msg Hook (Changelog)
cp prepareCommitMsg.sh .git/hooks/prepare-commit-msg
chmod +x .git/hooks/prepare-commit-msg
```

---

## 💡 Usage

### Basic Syntax

```bash
git commit -m "<type>: <description>"
```

### With Scope (optional)

```bash
git commit -m "<type>(scope): <description>"
```

**Examples:**
```bash
git commit -m "feat(auth): Add OAuth2 support"
git commit -m "fix(login): Correct session timeout"
git commit -m "docs(readme): Update installation guide"
```

---

## 📝 Commit Types

| Type | Category | Emoji | Usage |
|------|----------|-------|-------|
| **feat** | Added | ✨ | New features and functionality |
| **fix** | Fixed | 🐛 | Bug fixes and corrections |
| **docs** | Documentation | 📝 | Documentation changes |
| **refactor** | Changed | 🔄 | Code refactoring without functional changes |
| **test** | Testing | 🧪 | Adding or modifying tests |
| **chore** | Maintenance | 🔧 | Maintenance tasks, dependencies |
| **style** | Style | 💅 | Code formatting, whitespace |
| **perf** | Performance | ⚡ | Performance improvements |
| **security** | Security | 🔒 | Security fixes and improvements |
| **breaking** | Breaking | ⚠️ | Breaking changes |

---

## 📚 Examples

### ✨ New Features

```bash
git commit -m "feat: Add user export to CSV"
git commit -m "feat(api): Implement REST API endpoints"
git commit -m "feat(2fa): Add TOTP authentication"
```

**Result in CHANGELOG.md:**
```markdown
### ✨ Added
- **Add user export to CSV**
- **Implement REST API endpoints**
- **Add TOTP authentication**
```

### 🐛 Bug Fixes

```bash
git commit -m "fix: Correct timezone handling in logs"
git commit -m "fix(email): Resolve SMTP connection timeout"
git commit -m "fix(session): Fix session regeneration bug"
```

**Result in CHANGELOG.md:**
```markdown
### 🐛 Fixed
- **Correct timezone handling in logs**
- **Resolve SMTP connection timeout**
- **Fix session regeneration bug**
```

### 📝 Documentation

```bash
git commit -m "docs: Update installation guide"
git commit -m "docs(api): Add API documentation"
git commit -m "docs: Fix typos in README"
```

**Result in CHANGELOG.md:**
```markdown
### 📝 Documentation
- **Update installation guide**
- **Add API documentation**
- **Fix typos in README**
```

### 🔄 Refactoring

```bash
git commit -m "refactor: Simplify authentication logic"
git commit -m "refactor(db): Optimize database queries"
git commit -m "refactor: Extract validation into separate class"
```

**Result in CHANGELOG.md:**
```markdown
### 🔄 Changed
- **Simplify authentication logic**
- **Optimize database queries**
- **Extract validation into separate class**
```

### 🔒 Security

```bash
git commit -m "security: Fix XSS vulnerability in search"
git commit -m "security: Update dependencies with CVE fixes"
git commit -m "security(auth): Improve password hashing"
```

**Result in CHANGELOG.md:**
```markdown
### 🔒 Security
- **Fix XSS vulnerability in search**
- **Update dependencies with CVE fixes**
- **Improve password hashing**
```

---

## 📊 CHANGELOG Structure

### Before Commit

```markdown
## [1.3.2] - 2025-10-01
### ✨ Added
- **Docker support with complete containerization**
- **Dockerfile for PHP 8.3** with all required extensions
```

### After: `git commit -m "feat: Add backup functionality"`

```markdown
## [1.3.2] - 2025-10-01
### ✨ Added
- **Add backup functionality**
- **Docker support with complete containerization**
- **Dockerfile for PHP 8.3** with all required extensions
```

### Date Update

The date is **automatically** updated to the current commit date:

```markdown
# Before
## [1.3.2] - 2025-10-01

# After new commit on October 15th
## [1.3.2] - 2025-10-15
```

### New Categories

If a category doesn't exist yet, it's automatically added:

```bash
git commit -m "test: Add unit tests for authentication"
```

```markdown
## [1.3.2] - 2025-10-15
### ✨ Added
- **Docker support with complete containerization**

### 🧪 Testing
- **Add unit tests for authentication**
```

---

## 🔧 Troubleshooting

### Hook Not Executing

```bash
# Check if hook is installed
ls -la .git/hooks/

# Hook must be executable
chmod +x .git/hooks/prepare-commit-msg

# Reinstall
./setup-hooks.sh
```

### CHANGELOG.md Not Updated

```bash
# Check path
ls -la Documentation/CHANGELOG.md

# Test hook manually
bash prepareCommitMsg.sh .git/COMMIT_EDITMSG
```

### Commit Without Categorization

If no Conventional Commit is detected, the entry automatically goes under **Added**:

```bash
# Without prefix
git commit -m "Add new feature"

# Becomes:
### ✨ Added
- **Add new feature**
```

### Version Not Found

Ensure CHANGELOG.md contains a version in format `[X.Y.Z]`:

```markdown
## [1.3.2] - 2025-10-01
```

---

## 🎯 Best Practices

### 1. Descriptive Commit Messages

❌ **Bad:**
```bash
git commit -m "fix: fixed bug"
git commit -m "feat: changes"
```

✅ **Good:**
```bash
git commit -m "fix: Resolve session timeout in admin panel"
git commit -m "feat: Implement CSV export for user list"
```

### 2. One Commit Per Logical Change

```bash
# Separate commits for separate features
git commit -m "feat: Add email validation"
git commit -m "feat: Add password strength meter"

# NOT: One commit for everything
git commit -m "feat: Add email validation and password strength meter"
```

### 3. Use Scope for Context

```bash
git commit -m "fix(auth): Correct OAuth token refresh"
git commit -m "feat(api): Add pagination to user endpoint"
git commit -m "docs(docker): Update container setup guide"
```

### 4. Mark Breaking Changes

```bash
git commit -m "breaking: Remove deprecated API v1 endpoints"
git commit -m "breaking(db): Change user table schema"
```

---

## 🔄 Workflow Example

```bash
# 1. Develop feature
# ... write code ...

# 2. Stage changes
git add app/Controllers/BackupController.php
git add app/views/backup.latte

# 3. Commit with appropriate type
git commit -m "feat: Add automated backup scheduling"

# 4. Hook runs automatically:
#    ✅ CHANGELOG.md updated
#    📦 Version: 1.3.2
#    📅 Date:   2025-10-15
#    📝 Type:   feat
#    💬 Message: Add automated backup scheduling

# 5. Push changes
git push origin main
```

---

## 📖 Further Reading

- [Conventional Commits Specification](https://www.conventionalcommits.org/)
- [Keep a Changelog](https://keepachangelog.com/)
- [Semantic Versioning](https://semver.org/)
- [Git Hooks Documentation](https://git-scm.com/book/en/v2/Customizing-Git-Git-Hooks)

---

## 🤝 Team Setup

### For New Developers

```bash
# 1. Clone repository
git clone https://github.com/madcoda9000/SecStore.git
cd SecStore

# 2. Install hooks
./setup-hooks.sh

# 3. Make first commit
git commit -m "chore: Setup development environment"
```

### Mention in Documentation

Add to **README.md** or **CONTRIBUTING.md**:

```markdown
## 🛠️ Development Setup

After cloning the repository:

1. Install Git hooks:
   ```bash
   ./setup-hooks.sh
   ```

2. Use Conventional Commits:
   ```bash
   git commit -m "feat: Your new feature"
   ```

See [GIT_HOOKS.md](GIT_HOOKS.md) for details.
```

---

## ⚠️ Important Notes

1. **Hooks are local** - Each developer must run `setup-hooks.sh`
2. **CHANGELOG.md must exist** at path `Documentation/CHANGELOG.md`
3. **Version format** must be `[X.Y.Z]`
4. **Date is overwritten** with each new commit to the same version
5. **Version number** must be manually incremented for new releases

---

**💡 Tip:** Add `setup-hooks.sh` to your onboarding documentation!

---

## 🚀 Version Management

### Incrementing Version Numbers

The script **only updates the date and adds entries**. When you want to release a new version:

1. **Manually** add new version in CHANGELOG.md:
```markdown
## [Unreleased] - Next Version
### Planned
- Future features

---

## [1.3.3] - 2025-10-15
### ✨ Added
...

---

## [1.3.2] - 2025-10-14
### ✨ Added
...
```

2. The script will automatically work with the new version from then on

### Release Workflow

```bash
# 1. Finish development for version 1.3.3
git commit -m "feat: Add final feature for v1.3.3"

# 2. Manually update CHANGELOG.md
# Add new [1.3.4] section at top

# 3. Commit version bump
git commit -m "chore: Bump version to 1.3.4"

# 4. Tag release
git tag -a v1.3.3 -m "Release version 1.3.3"
git push origin v1.3.3

# 5. Continue development
git commit -m "feat: Start new feature for 1.3.4"
```

---

## 🔍 Advanced Usage

### Custom Categories

You can extend the script with custom commit types. Edit `prepareCommitMsg.sh`:

```bash
# Add to CATEGORY_MAP
CATEGORY_MAP["build"]="### 🏗️ Build"
CATEGORY_MAP["ci"]="### 👷 CI/CD"
CATEGORY_MAP["revert"]="### ⏪ Reverts"
```

### Skip Hook Temporarily

If you need to skip the hook for a specific commit:

```bash
git commit -m "feat: Add feature" --no-verify
```

**⚠️ Warning:** This skips ALL hooks including security checks!

### Multi-line Commit Messages

The hook only uses the first line (subject) for the CHANGELOG:

```bash
git commit -m "feat: Add backup feature

This adds a new backup feature that allows:
- Automatic scheduled backups
- Manual backup triggering
- Backup restoration"
```

**CHANGELOG entry:** Only "Add backup feature" is added

---

## 📊 Statistics & Monitoring

### View Changelog Statistics

```bash
# Count entries per category
grep -c "^### ✨ Added" Documentation/CHANGELOG.md
grep -c "^### 🐛 Fixed" Documentation/CHANGELOG.md

# Count total version entries
grep -c "^## \[" Documentation/CHANGELOG.md

# Show all commit types used
grep "^- \*\*" Documentation/CHANGELOG.md | wc -l
```

### Generate Release Notes

Extract a specific version:

```bash
# Extract version 1.3.2
sed -n '/## \[1.3.2\]/,/## \[/p' Documentation/CHANGELOG.md | head -n -1
```

---

**Made with ❤️ for SecStore Development Team**