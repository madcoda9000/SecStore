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

### 🐛 Bug Fixes

```bash
git commit -m "fix: Correct timezone handling in logs"
git commit -m "fix(email): Resolve SMTP connection timeout"
git commit -m "fix(session): Fix session regeneration bug"
```

### 📝 Documentation

```bash
git commit -m "docs: Update installation guide"
git commit -m "docs(api): Add API documentation"
git commit -m "docs: Fix typos in README"
```

### 🔄 Refactoring

```bash
git commit -m "refactor: Simplify authentication logic"
git commit -m "refactor(db): Optimize database queries"
git commit -m "refactor: Extract validation into separate class"
```

### 🔒 Security

```bash
git commit -m "security: Fix XSS vulnerability in search"
git commit -m "security: Update dependencies with CVE fixes"
git commit -m "security(auth): Improve password hashing"
```

---

## 🔧 Troubleshooting

### Hook Not Executing

```bash
# Check if hook is installed
ls -la .git/hooks/

# Reinstall
./setup-hooks.sh
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

# 5. Push changes
git push origin main
```

---

## 📖 Further Reading

- [Conventional Commits Specification](https://www.conventionalcommits.org/)
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

---

**💡 Tip:** Add `setup-hooks.sh` to your onboarding documentation!

---

**Made with ❤️ for SecStore Development Team**