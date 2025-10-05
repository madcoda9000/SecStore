# 🧑‍💻 SecStore Developer Documentation

> **A comprehensive guide to extending SecStore with custom pages and features**

---

## 📋 Table of Contents

1. [🔧 Setting up Development Environment](#-setting-up-development-environment)
2. [📦 SecStore Installation](#-secstore-installation)  
3. [🎯 Web-Based Setup (Recommended)](#-web-based-setup-recommended)
4. [🔄 Development Workflow](#-development-workflow)
5. [🔒 Using Azure Mock-Mode for local Development](#azure-sso-mock-mode)
6. [🆕 Creating Your First Custom Page](#-creating-your-first-custom-page)
7. [📄 Creating Latte Templates](#-creating-latte-templates)
8. [🎮 Developing Controllers](#-developing-controllers)
9. [🛣️ Adding Routes](#-adding-routes)
10. [🧭 Extending Navigation](#-extending-navigation)
11. [💾 JavaScript Integration](#-javascript-integration)
12. [🌍 Implementing Multilingual Support](#-implementing-multilingual-support)
13. [📚 Best Practices & Guidelines](#-best-practices--guidelines)

---

## 🔧 Setting up Development Environment

### **Prerequisites Check**

```bash
# Check PHP version (≥ 8.3 required)
php --version

# Check Composer
composer --version

# Check MySQL/MariaDB
mysql --version
```

### **Automatic Installation (Recommended)**

SecStore provides an automatic setup script:

```bash
# Make script executable
chmod +x secstore_setup.sh

# Run automated environment setup
./secstore_setup.sh
```

**The script automatically installs:**
- ✅ PHP 8.3+ with all required extensions
- ✅ Composer globally
- ✅ Development tools (PHP CodeSniffer, PHP-CS-Fixer)
- ✅ Project dependencies

### **Manual Installation**

If you prefer manual control:

```bash
# Check PHP extensions
php -m | grep -E "(curl|json|pdo|mysql|xml|zip|bcmath|gd|mbstring)"

# Install Composer (if not present)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install development tools globally
composer global require squizlabs/php_codesniffer
composer global require friendsofphp/php-cs-fixer
```

### **🎣 Git Hooks Setup (Important!)**

**SecStore uses Git hooks for code quality and automatic CHANGELOG management.**

After cloning the repository, install the Git hooks:

```bash
# Make setup script executable
chmod +x setup-hooks.sh

# Install all Git hooks
./setup-hooks.sh
```

**What gets installed:**

| Hook | Purpose | Trigger |
|------|---------|---------|
| **pre-commit** | Security check | Before every commit |

#### **Pre-Commit Hook (Security)**

Blocks commits of sensitive files:

```bash
# These files will be BLOCKED:
- config*.php (except templates)
- .env* (except .env.example)
- *.key, *.credentials
- *backup*, *copy*

# These files are ALLOWED:
- config.php_TEMPLATE
- config.php.example
- .env.example
```

**Testing the hook:**

```bash
# This should be blocked
echo "test" > config-production.php
git add config-production.php
git commit -m "test"  # ❌ Commit blocked

# Clean up
rm config-production.php
```

**Supported commit types:**

| Type | Category | Usage |
|------|----------|-------|
| `feat:` | ✨ Added | New features |
| `fix:` | 🐛 Fixed | Bug fixes |
| `docs:` | 📝 Documentation | Documentation changes |
| `refactor:` | 🔄 Changed | Code refactoring |
| `test:` | 🧪 Testing | Test additions/changes |
| `chore:` | 🔧 Maintenance | Maintenance tasks |
| `security:` | 🔒 Security | Security improvements |
| `perf:` | ⚡ Performance | Performance improvements |

**📖 Full documentation:** See [GIT_HOOKS.md](GIT_HOOKS.md) for detailed usage guide.

### **Verify Installation**

Check if hooks are properly installed:

```bash
# Check hooks are executable
ls -la .git/hooks/

# Should show:
# -rwxr-xr-x ... pre-commit
# -rwxr-xr-x ... prepare-commit-msg

# Test with a commit
echo "test" > test.txt
git add test.txt
git commit -m "test: Verify hooks are working"

# Check if CHANGELOG.md was updated
git diff Documentation/CHANGELOG.md
```

---

## 📦 SecStore Installation

### **Clone Project and Setup**

```bash
# Clone repository
git clone https://github.com/madcoda9000/SecStore.git
cd SecStore

# Install dependencies (with dev tools)
composer install

# Install Git hooks (important!)
./setup-hooks.sh

# Create configuration template
cp config.php_TEMPLATE config.php
```

### **Start Development Server**

```bash
# Start server (port 8000)
php -S localhost:8000 -t public

# Alternative ports
php -S localhost:8080 -t public
```

**🎉 SecStore now runs at:** `http://localhost:8000`

---

## 🎯 Web-Based Setup (Recommended)

### **🚀 Why Use the Web Setup Wizard?**

SecStore features an intuitive **4-step web-based setup wizard** that automatically handles:

1. **📁 Configuration File** - Creates and validates `config.php`
2. **🔐 File Permissions** - Checks write permissions  
3. **🗄️ Database Setup** - Configures connection, creates schema, and admin user
4. **📧 Email Configuration** - Configures SMTP settings (optional)

### **🎯 Setup Process**

1. **Navigate to your SecStore installation:**
   ```
   http://localhost:8000
   ```

2. **Follow the wizard steps:**
   - Step 1: Verify `config.php` exists
   - Step 2: Check file permissions
   - Step 3: Configure database connection
   - Step 4: Set up email (optional)

3. **Login with default credentials:**
   - Username: `super.admin`
   - Password: `Test1000!`
   - ⚠️ **Change immediately after first login!**

---

## 🔄 Development Workflow

### **Quick Commit Helper (Recommended)**

SecStore includes an interactive **quick-commit.sh** script that streamlines the Git workflow:

```bash
# Make it executable (first time only)
chmod +x quick-commit.sh

# Use it for every commit
./quick-commit.sh
```

**What it does:**

1. 📊 **Shows current Git status** - All changes at a glance
2. 📦 **Stages all changes** - One-click "add all" option
3. 💬 **Interactive commit type selection** - Menu with all Conventional Commit types
4. 📝 **Message input** - Just type the description (prefix is added automatically)
5. 🎯 **Optional scope** - Add context like `(auth)`, `(api)`, etc.
6. ✅ **Confirmation** - Preview final message before committing
7. 🚀 **Automatic CHANGELOG update** - Via prepare-commit-msg hook
8. 🌐 **Optional push** - Push to remote with one keystroke

**Interactive Menu:**

```
💬 Select commit type:

  1) feat:      ✨ New feature
  2) fix:       🐛 Bug fix
  3) docs:      📝 Documentation
  4) refactor:  🔄 Code refactoring
  5) test:      🧪 Tests
  6) chore:     🔧 Maintenance
  7) style:     💅 Code style
  8) perf:      ⚡ Performance
  9) security:  🔒 Security
 10) breaking:  ⚠️  Breaking change
 11) custom     ✏️  Custom message (no prefix)

Choose [1-11]: 1
📝 Commit message: feat: Add user export functionality
🎯 Add scope? (e.g., auth, api, docs) [optional]: api

Final commit message: "feat(api): Add user export functionality"
✅ Proceed with commit? [Y/n]: y

✅ CHANGELOG.md aktualisiert
🌐 Push to remote? [y/N]: y
```

**Alternative: Manual Git workflow**

If you prefer traditional Git commands:

```bash
# Stage changes
git add .

# Commit with Conventional Commits
git commit -m "feat: Your feature description"

# CHANGELOG.md is automatically updated by the hook!

# Push
git push origin main
```

### **Daily Development Routine**

```bash
# 1. Pull latest changes
git pull origin main

# 2. Update dependencies if needed
composer install

# 3. Start development server
php -S localhost:8000 -t public

# 4. Make your changes
# ... develop features ...

# 5. Commit using quick-commit script
./quick-commit.sh

# That's it! The script handles:
# - Staging
# - Conventional Commit formatting
# - CHANGELOG.md updates
# - Pushing to remote
```

### **Code Quality Checks**

```bash
# Check PSR-12 compliance
vendor/bin/phpcs app/

# Auto-fix code style
vendor/bin/php-cs-fixer fix

# Clear template cache
rm -rf cache/*.php

# Regenerate autoloader
composer dump-autoload
```

### **Common Development Commands**

```bash
# Database connection test
php -r "
\$config = include 'config.php';
try {
    \$pdo = new PDO(
        'mysql:host='.\$config['db']['host'].';dbname='.\$config['db']['name'], 
        \$config['db']['user'], 
        \$config['db']['pass']
    );
    echo 'Database connection: OK\n';
} catch(Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . \"\n\";
}
"

# Export database schema (for documentation)
php generate_schema.php

# Development server with XDebug
php -S localhost:8000 -t public -d xdebug.mode=debug
```

### **Git Workflow Tips**

**Commit Message Best Practices:**

✅ **Good:**
```bash
./quick-commit.sh
# Select: 1 (feat)
# Message: Add CSV export for user list
# Scope: export
# Result: "feat(export): Add CSV export for user list"
```

❌ **Bad:**
```bash
git commit -m "changes"
git commit -m "fixed stuff"
git commit -m "wip"
```

**When to use which commit type:**

| Use Case | Type | Example |
|----------|------|---------|
| New feature | `feat` | Add OAuth2 authentication |
| Bug fix | `fix` | Resolve session timeout issue |
| Documentation | `docs` | Update API documentation |
| Refactoring | `refactor` | Simplify authentication logic |
| Tests | `test` | Add unit tests for login |
| Dependencies | `chore` | Update composer packages |
| Security fix | `security` | Patch XSS vulnerability |
| Performance | `perf` | Optimize database queries |

**Skipping hooks (emergency only):**

```bash
# Skip all hooks (use with caution!)
git commit --no-verify -m "emergency fix"
```

⚠️ **Warning:** This skips security checks too!

### **Global Quick Commit Access (Optional)**

Make quick-commit.sh available from anywhere:

```bash
# Option 1: Create alias in ~/.bashrc or ~/.zshrc
echo 'alias qc="./quick-commit.sh"' >> ~/.bashrc
source ~/.bashrc

# Now use:
qc

# Option 2: Add to PATH (system-wide)
sudo cp quick-commit.sh /usr/local/bin/qc
sudo chmod +x /usr/local/bin/qc

# Use from any directory:
cd ~/my-project
qc
```

---

## Azure SSO Mock Mode

### 📋 Overview

The Azure SSO Mock Mode allows you to develop and test Azure/Entra ID authentication **without a real Azure tenant**. It simulates the complete OAuth2 flow locally, making it perfect for:

- 🚀 **Rapid Development** - No Azure setup required
- 🧪 **Local Testing** - Test SSO logic offline
- 🔄 **CI/CD Pipelines** - Automated testing without external dependencies
- 📺 **Demos** - Show SSO functionality without Azure credentials

⚠️ **Important**: Mock Mode is for development only. Never use in production!

---

### 🎯 When to Use Mock Mode

| Use Case | Mock Mode | Real Azure |
|----------|-----------|------------|
| Local Development | ✅ Perfect | ❌ Overkill |
| Testing SSO Logic | ✅ Perfect | ❌ Slower |
| Integration Tests | ✅ Fast | ❌ Complex |
| Demo/Presentation | ✅ Easy | ⚠️ Requires setup |
| Production | ❌ NEVER | ✅ Required |
| Testing MFA/Policies | ❌ Not supported | ✅ Required |

---

### ⚙️ Configuration

#### **Enable Mock Mode**

In your `config.php`, you need **TWO settings**:

**1. Set Application Environment to Development:**

```php
$application = [
    'appUrl' => 'http://localhost:8000',
    'sessionTimeout' => 1800,
    'allowPublicRegister' => false,
    'allowPublicPasswordReset' => false,
    'environment' => 'development',  // ⚠️ REQUIRED for Mock Mode!
];
```

**2. Enable Azure SSO Mock Mode:**

```php
$azureSso = [
    'enabled' => true,      // Enable Azure SSO feature
    'mockMode' => true,     // ⚠️ MOCK MODE - Development only!
    'tenantId' => '',       // Not needed in Mock Mode
    'clientId' => '',       // Not needed in Mock Mode
    'clientSecret' => '',   // Not needed in Mock Mode
    'redirectUri' => 'http://localhost:8000/auth/azure/callback', // set here the correct hostname and port for your dev enviroment!
];
```

⚠️ **CRITICAL**: If `environment` is NOT set to `'development'`, the Mock Methods will not work for security reasons!

#### **Disable Mock Mode (Production)**

**1. Set Application Environment to Production:**

```php
$application = [
    'appUrl' => 'https://your-domain.com',
    'sessionTimeout' => 1800,
    'allowPublicRegister' => false,
    'allowPublicPasswordReset' => false,
    'environment' => 'production',  // ⚠️ Disables development mode!
];
```

**2. Configure Real Azure:**

```php
$azureSso = [
    'enabled' => true,
    'mockMode' => false,    // ⚠️ Use real Azure
    'tenantId' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    'clientId' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    'clientSecret' => 'your-client-secret-value',
    'redirectUri' => 'https://your-domain.com/auth/azure/callback',
];
```

---

### 🚀 Usage Guide

#### **Step 1: Create Test User with Entra ID**

```bash
# 1. Log in as Admin
# 2. Navigate to Admin → Users → Create User
# 3. Fill in user details
# 4. ✅ Check "Enable Entra ID Login"
# 5. ❌ Uncheck "LDAP Login" (mutually exclusive)
# 6. Save
```

**Example:**
- Username: `test.azure`
- Email: `test.azure@company.com`
- Entra ID: **Enabled**

#### **Step 2: Test Mock Login**

```bash
# 1. Navigate to /login
# 2. Click "Sign in with Microsoft / Entra ID" button
# 3. Select user from dropdown
# 4. Click "Mock Login durchführen"
# ✅ Automatic login without password/2FA
```

#### **Step 3: Verify Behavior**

**Check Profile Restrictions:**
```bash
# 1. Log in as Entra ID user (via Mock)
# 2. Navigate to Profile
# 3. Verify:
#    ✅ Password change: DISABLED
#    ✅ Email change: DISABLED
#    ✅ 2FA settings: DISABLED
```

**Check Normal Login Block:**
```bash
# 1. Log out
# 2. Try normal login with username/password
# 3. ✅ Error: "Your account requires login via Microsoft/Entra ID"
```

---

### 🔄 Mock Mode Flow

```
User clicks Azure button
        ↓
Redirect to /auth/azure/login (Mock login page will automatically loaded if moch mode enabled)
        ↓
User selects email from dropdown
        ↓
POST to /auth/azure/callback
        ↓
Generate fake azure login
        ↓
Redirect to /auth/azure/callback (normal app flow)
        ↓
AzureSsoUtil detects mockMode = true
        ↓
Read email from session (instead of Azure API)
        ↓
Find user by email → Check entraIdEnabled = 1
        ↓
✅ Login successful (bypass brute force + 2FA)
```

---

### 🧪 Testing Scenarios

#### **Scenario 1: Successful Mock Login**

```php
✅ User exists in database
✅ entraIdEnabled = 1
✅ status = 1 (active)
→ Result: Automatic login
```

#### **Scenario 2: User Not Found**

```php
✅ Email selected from dropdown
❌ Email not in database
→ Result: Error "User not found"
```

#### **Scenario 3: Entra ID Not Enabled**

```php
✅ User exists
❌ entraIdEnabled = 0
→ Result: User not shown in dropdown
```

#### **Scenario 4: Normal Login Blocked**

```php
✅ User has entraIdEnabled = 1
❌ Tries normal login with password
→ Result: Error "Must use Entra ID login"
```

---

### 🔍 Debugging

#### **Check Logs**

```sql
SELECT * FROM logs 
WHERE context LIKE '%Azure%' OR context LIKE '%Mock%' 
ORDER BY datum_zeit DESC 
LIMIT 20;
```

**Expected log entries:**
- `MOCK MODE: Login for test.azure@company.com`
- `Successful Azure SSO login for: test.azure@company.com`

#### **Verify User Configuration**

```sql
SELECT id, username, email, entraIdEnabled, ldapEnabled, status 
FROM users 
WHERE email = 'test.azure@company.com';
```

**Expected:**
```
id | username   | email                  | entraIdEnabled | ldapEnabled | status
---+------------+------------------------+----------------+-------------+-------
1  | test.azure | test.azure@company.com | 1              | 0           | 1
```

#### **Common Issues**

| Problem | Cause | Solution |
|---------|-------|----------|
| Azure button not visible | `enabled = false` | Set `enabled = true` in config |
| Mock routes return 404 | `environment != 'development'` | Set `environment = 'development'` in config |
| No users in dropdown | No user with `entraIdEnabled = 1` | Create user with Entra enabled |
| "Invalid state" error | Session issue | Clear browser cookies |
| Redirect loop | Wrong `redirectUri` | Check config.php URL |

---

### 🔒 Security Considerations

#### **Why Mock Mode is NOT Production-Safe**

❌ **No Real Authentication** - Anyone can select any user  
❌ **No Password Verification** - Email selection is sufficient  
❌ **No MFA** - Multi-factor authentication is bypassed  
❌ **No Azure AD Policies** - Conditional access not enforced  


#### **Best Practices**

1. ✅ Use Mock Mode only on localhost/development
2. ✅ Check environment variables before enabling
3. ✅ Remove Mock routes in production deployments
4. ✅ Use `.gitignore` for config.php
5. ✅ Set `mockMode = false` before production deployment

---

### 🔄 Switching to Real Azure

When ready for production testing:

#### **Step 1: Get Azure Credentials**

```bash
# Option A: Azure Free Account
https://azure.microsoft.com/free

# Option B: Microsoft 365 Developer Program
https://developer.microsoft.com/microsoft-365/dev-program
```

#### **Step 2: Register App in Azure Portal**

```bash
# 1. Go to https://portal.azure.com
# 2. Azure Active Directory → App registrations → New registration
# 3. Set Redirect URI: https://your-domain.com/auth/azure/callback
# 4. Create client secret
# 5. Set API permissions: User.Read, openid, profile, email
# 6. Copy: Tenant ID, Client ID, Client Secret
```

#### **Step 3: Update Config**

```php
$azureSso = [
    'enabled' => true,
    'mockMode' => false,  // ⚠️ Switch to real Azure
    'tenantId' => 'your-tenant-id',
    'clientId' => 'your-client-id',
    'clientSecret' => 'your-client-secret',
    'redirectUri' => 'https://your-domain.com/auth/azure/callback',
];
```

#### **Step 4: Test Real Azure**

```bash
# 1. Navigate to /login
# 2. Click Azure button
# 3. Redirected to real Microsoft login
# 4. Sign in with Azure account
# 5. Redirected back to app
# ✅ Real authentication with MFA/policies
```

---

### 📊 Mock vs Real Comparison

| Feature | Mock Mode | Real Azure |
|---------|-----------|------------|
| Setup Time | 5 minutes | 30 minutes |
| Cost | Free | Free (Developer) |
| Authentication | Fake | Real |
| MFA Support | No | Yes |
| Conditional Access | No | Yes |
| User Management | Local DB | Azure Portal |
| Production Ready | ❌ NO | ✅ YES |
| Offline Testing | ✅ YES | ❌ NO |

---

### 💡 Development Workflow

#### **Recommended Approach**

```bash
# Phase 1: Local Development (Mock Mode)
1. Develop SSO features locally
2. Test user flows with Mock Mode
3. Debug and refine logic
4. Write integration tests

# Phase 2: Staging (Real Azure)
1. Set up Azure Free/Developer tenant
2. Switch mockMode = false
3. Test with real authentication
4. Test MFA/policies
5. Verify production behavior

# Phase 3: Production
1. Use company Azure tenant
2. Remove Mock Mode code (optional)
3. Monitor logs
4. Handle edge cases
```

---

### 🎓 Quick Reference

#### **Check Configuration**
```php
// Verify Mock Mode is properly configured
$config = include 'config.php';

// Both must be true for Mock Mode to work:
echo "Environment: " . ($config['application']['environment'] ?? 'not set');
// Must be: 'development'

echo "Mock Mode: " . ($config['azureSso']['mockMode'] ? 'true' : 'false');
// Must be: true
```

#### **Enable Mock Mode**
```php
// In config.php - TWO settings required:
$application = [
    'environment' => 'development',  // 1. Set environment
];

$azureSso = [
    'mockMode' => true,  // 2. Enable mock
];
```

#### **Disable Mock Mode**
```php
// In config.php:
$application = [
    'environment' => 'production',  // Disables Mock routes
];

$azureSso = [
    'mockMode' => false,  // Use real Azure
];
```

#### **Mock Login URL**
```
http://localhost:8000/login
→ Click Azure button
→ Redirects to /dev/azure-mock/login
```

#### **Clear Mock Session**
```php
unset($_SESSION['mock_azure_email']);
unset($_SESSION['mock_azure_code']);
unset($_SESSION['oauth2state']);
```

---

### ✅ Testing Checklist

Before switching to real Azure, verify:

- [ ] `environment = 'development'` in config.php
- [ ] `mockMode = true` in config.php
- [ ] Mock login works for Entra-enabled users
- [ ] Non-Entra users don't appear in dropdown
- [ ] Normal login blocked for Entra users
- [ ] Profile restrictions work (password/email/2FA)
- [ ] LDAP and Entra are mutually exclusive
- [ ] Logs show correct Mock entries
- [ ] No errors in browser console
- [ ] Session cleanup works properly

---

### 🔗 Related Documentation

- **Full Azure Setup Guide**: See `AZURE_SSO_INSTALLATION.md`
- **Production Deployment**: See `INSTALL.md`
- **Security Guidelines**: See `SECURITY.md`

---

### 💬 Support

**Issues with Mock Mode?**

1. Check logs: `SELECT * FROM logs WHERE context LIKE '%Mock%'`
2. Verify config: `mockMode = true` and `enabled = true`
3. Clear cache: `rm -rf cache/*.php`
4. Check user: `entraIdEnabled = 1` in database

**Ready for Production?**

Set `mockMode = false` and follow `AZURE_SSO_INSTALLATION.md` for Azure setup.

---

**Happy Testing! 🎉**

---

### 🆕 Creating Your First Custom Page

Let's create an example **"FAQ"** page to demonstrate all concepts.

#### **Step-by-Step Guide:**

1. **📄 Create template** → `app/views/faq.latte`
2. **🎮 Develop controller** → `app/Controllers/FaqController.php`
3. **🛣️ Add route** → `app/routes.php`
4. **🧭 Extend navigation** → `app/views/_topbar.latte`
5. **💾 Include JavaScript** → `public/js/faq.latte.js`
6. **🌍 Add translations** → `app/lang/de.php` & `app/lang/en.php`

---

## 📄 Creating Latte Templates

### **Template: `app/views/faq.latte`**

```latte
{extends '_mainLayout.latte'}

{block content}
<div class="container py-4" style="flex:1">
    <h1 class="mb-5">
        <span class="bi bi-question-circle-fill"></span>&nbsp;{trans('faq.title')}
    </h1>
    
    {* FAQ Content *}
    <div class="row">
        <div class="col-md-8">
            <div class="accordion" id="faqAccordion">
                {foreach $faqs as $index => $faq}
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading{$index}">
                        <button class="accordion-button collapsed" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#collapse{$index}">
                            {$faq.question}
                        </button>
                    </h2>
                    <div id="collapse{$index}" class="accordion-collapse collapse" 
                         data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            {$faq.answer}
                        </div>
                    </div>
                </div>
                {/foreach}
            </div>
        </div>
        
        {* Sidebar *}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>{trans('faq.sidebar.title')}</h5>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary" id="searchBtn" data-search-url="/faq/search">
                        {trans('faq.sidebar.search')}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{* CSP-compliant data transfer *}
<div id="faq-config" 
     data-search-url="/faq/search"
     data-total-faqs="{count($faqs)}"
     style="display:none;">
</div>

{* External JavaScript files *}
<script src="/js/faq.latte.js"></script>
{/block}
```

### **Important Template Guidelines:**

✅ **DO's:**
- Use `{extends '_mainLayout.latte'}` for authenticated pages
- Use `{extends '_authLayout.latte'}` for login/register pages
- Use `{trans('key')}` for all text (multilingual support)
- CSP-compliant data transfer via data attributes
- External JavaScript files instead of inline code

❌ **DON'Ts:**
- No inline `<script>` tags
- No inline `onclick` events
- No hardcoded strings
- No direct style definitions

---

## 🎮 Developing Controllers

### **Controller: `app/Controllers/FaqController.php`**

```php
<?php

namespace App\Controllers;

use App\Models\User;
use App\Utils\SessionUtil;
use App\Utils\LogType;
use App\Utils\LogUtil;
use App\Utils\TranslationUtil;
use Flight;

/**
 * FAQ Controller
 *
 * Handles all FAQ-related requests
 *
 * @package App\Controllers
 * @author Your Name
 * @version 1.0
 * @since 2025-01-01
 */
class FaqController
{
    /**
     * Show FAQ page
     */
    public function showFaq()
    {
        // Session validation
        if (SessionUtil::get('user')['id'] === null) {
            Flight::redirect('/login');
            return;
        }

        // Load user data
        $user = User::findUserById(SessionUtil::get('user')['id']);
        if (!$user) {
            SessionUtil::destroy();
            Flight::redirect('/login');
            return;
        }

        // Prepare FAQ data
        $faqs = $this->getFaqData();

        // Audit log
        LogUtil::logAction(
            LogType::AUDIT,
            'FaqController',
            'showFaq',
            'User viewed FAQ page',
            $user->username
        );

        // Render template
        Flight::latte()->render('faq.latte', [
            'title' => TranslationUtil::t('faq.title'),
            'user' => $user,
            'sessionTimeout' => SessionUtil::getRemainingTime(),
            'faqs' => $faqs
        ]);
    }

    /**
     * Controller: FAQ Search
     */
    public function searchFaq()
    {
        // Validate input
        $query = trim($_GET['q'] ?? '');
        if (empty($query)) {
            Flight::json(['error' => 'Query required']);
            return;
        }

        // Perform search
        $results = $this->performSearch($query);

        // JSON response
        Flight::json([
            'success' => true,
            'query' => $query,
            'results' => $results,
            'count' => count($results)
        ]);
    }

    /**
     * Provide FAQ data
     */
    private function getFaqData(): array
    {
        return [
            [
                'question' => TranslationUtil::t('faq.q1.question'),
                'answer' => TranslationUtil::t('faq.q1.answer')
            ],
            [
                'question' => TranslationUtil::t('faq.q2.question'),
                'answer' => TranslationUtil::t('faq.q2.answer')
            ],
            [
                'question' => TranslationUtil::t('faq.q3.question'),
                'answer' => TranslationUtil::t('faq.q3.answer')
            ]
        ];
    }

    /**
     * Implement FAQ search
     */
    private function performSearch(string $query): array
    {
        $allFaqs = $this->getFaqData();
        $results = [];

        foreach ($allFaqs as $index => $faq) {
            if (stripos($faq['question'], $query) !== false || 
                stripos($faq['answer'], $query) !== false) {
                $results[] = array_merge($faq, ['id' => $index]);
            }
        }

        return $results;
    }
}
```

### **Controller Guidelines:**

✅ **Structural Requirements:**
- Namespace: `App\Controllers`
- Consistent naming: `[Feature]Controller`
- Session validation in every protected method
- Audit logging for important actions
- Error handling with meaningful fallbacks

✅ **Security:**
- Input validation for all user inputs
- CSRF tokens for state-changing operations
- Role-based access control where needed

---

## 🛣️ Adding Routes

### **Routes: `app/routes.php`**

Add at the end of the file:

```php
// ==========================================
// FAQ ROUTES
// ==========================================

// FAQ main page (protected, all authenticated users)
secureRoute('GET /faq', function () {
    (new FaqController)->showFaq();
}, 'global', false);

// FAQ search (protected)
secureRoute('GET /faq/search', function () {
    (new FaqController)->searchFaq();
}, 'global', false);

// Optional: Admin route for FAQ management
secureRoute('GET /admin/faq', function () {
    (new FaqController)->showFaqAdmin();
}, 'admin', true);
```

### **Route Parameters Explained:**

| Parameter | Description | Options |
|-----------|-------------|---------|
| **HTTP Method** | `GET`, `POST`, `PUT`, `DELETE` | Standard HTTP verbs |
| **Path** | URL path like `/faq` or `/faq/search` | Can contain parameters |
| **Callback** | Controller method as closure | Instantiates controller |
| **Scope** | Rate limiting scope | `'global'`, `'admin'`, `'login'` |
| **Admin-Only** | Only for admin role | `true` or `false` |

### **Rate Limiting Scopes:**

```php
// From config.php - Rate Limiting Configuration
'limits' => [
    'login' => ['requests' => 15, 'window' => 300],      // Very restrictive
    'admin' => ['requests' => 200, 'window' => 300],     // Moderate access  
    'global' => ['requests' => 1500, 'window' => 300],   // General usage
]
```

---

## 🧭 Extending Navigation

### **Extending Topbar: `app/views/_topbar.latte`**

Add the FAQ link to existing navigation:

```latte
{* In offcanvas navigation (Mobile) *}
<div class="offcanvas-body">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" href="/home">{trans('menu.home')}</a>
        </li>
        
        {* 🆕 Add FAQ link *}
        <li class="nav-item">
            <a class="nav-link" href="/faq">{trans('menu.faq')}</a>
        </li>
        
        {if in_array('Admin', explode(',', $user->roles))}
            <li class="nav-item">
                <a class="nav-link" href="/admin/settings">{trans('menu.settings')}</a>
            </li>
            {* ... existing admin navigation ... *}
        {/if}
    </ul>
</div>
```

### **Advanced Navigation with Dropdown:**

If you want a dropdown menu:

```latte
{* Dropdown for help section *}
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="helpDropdown" 
       role="button" data-bs-toggle="dropdown" aria-expanded="false">
        {trans('menu.help')}
    </a>
    <ul class="dropdown-menu" aria-labelledby="helpDropdown">
        <li><a class="dropdown-item" href="/faq">{trans('menu.help.faq')}</a></li>
        <li><a class="dropdown-item" href="/documentation">{trans('menu.help.docs')}</a></li>
        <li><a class="dropdown-item" href="/support">{trans('menu.help.support')}</a></li>
    </ul>
</li>
```

### **Automatic Active State Detection:**

The existing JavaScript logic in `_topbar.latte.js` automatically detects active links based on the URL. No additional configuration required!

---

## 💾 JavaScript Integration

### **JavaScript: `public/js/faq.latte.js`**

```javascript
/**
 * FAQ Page JavaScript
 * CSP-compliant implementation for FAQ page
 * File: public/js/faq.latte.js
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🤔 FAQ page JavaScript initialized');
    
    // =============================================
    // STEP 1: Load configuration from data attributes
    // =============================================
    const configElement = document.getElementById('faq-config');
    let config = {
        searchUrl: '/faq/search',
        totalFaqs: 0
    };
    
    if (configElement) {
        config.searchUrl = configElement.getAttribute('data-search-url') || config.searchUrl;
        config.totalFaqs = parseInt(configElement.getAttribute('data-total-faqs')) || 0;
        console.log('✅ FAQ config loaded:', config);
    }
    
    // =============================================
    // STEP 2: Setup event listeners
    // =============================================
    setupEventListeners();
    
    function setupEventListeners() {
        // Search button event
        const searchBtn = document.getElementById('searchBtn');
        if (searchBtn) {
            searchBtn.addEventListener('click', handleSearch);
        }
        
        // Accordion events for analytics
        const accordionButtons = document.querySelectorAll('.accordion-button');
        accordionButtons.forEach(button => {
            button.addEventListener('click', handleAccordionClick);
        });
    }
    
    // =============================================
    // STEP 3: Implement event handlers
    // =============================================
    function handleSearch() {
        const query = prompt('Search FAQ:');
        if (!query || query.trim() === '') return;
        
        console.log('🔍 Searching FAQ for:', query);
        
        // Show loading state
        const searchBtn = document.getElementById('searchBtn');
        const originalText = searchBtn.textContent;
        searchBtn.textContent = 'Searching...';
        searchBtn.disabled = true;
        
        // controller call
        fetch(`${config.searchUrl}?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                console.log('✅ Search results:', data);
                displaySearchResults(data);
            })
            .catch(error => {
                console.error('❌ Search error:', error);
                alert('Search error. Please try again.');
            })
            .finally(() => {
                // Reset loading state
                searchBtn.textContent = originalText;
                searchBtn.disabled = false;
            });
    }
    
    function handleAccordionClick(event) {
        const button = event.target;
        const target = button.getAttribute('data-bs-target');
        console.log('📋 FAQ item opened:', target);
        
        // Optional: Analytics tracking
        // trackEvent('faq_item_opened', { item: target });
    }
    
    function displaySearchResults(data) {
        if (data.success && data.results.length > 0) {
            let resultText = `Found: ${data.count} result(s)\n\n`;
            data.results.forEach((result, index) => {
                resultText += `${index + 1}. ${result.question}\n`;
            });
            alert(resultText);
        } else {
            alert('No results found.');
        }
    }
    
    // =============================================
    // STEP 4: Utility functions
    // =============================================
    function trackEvent(eventName, eventData) {
        // Implement analytics integration here
        console.log('📊 Event tracked:', eventName, eventData);
    }
});
```

### **JavaScript Naming Conventions:**

✅ **File Naming:**
- Template-specific: `templatename.latte.js`
- Minified version: `templatename.latte-min.js`
- Utility scripts: `/js/utils/descriptive-name.js`
- Admin scripts: `/js/admin/feature-name.js`

✅ **Code Structure:**
- CSP-compliant implementation (no inline scripts)
- Data attributes for configuration
- Event delegation instead of direct event handlers
- Modular function organization
- Consistent commenting

### **Minification (for Production):**

```bash
# Install tool
npm install -g terser

# Minify JavaScript
terser public/js/faq.latte.js -o public/js/faq.latte-min.js -c -m

# Switch template to minified version
<script src="/js/faq.latte-min.js"></script>
```

---

## 🌍 Implementing Multilingual Support

### **German Translations: `app/lang/de.php`**

Add at the end of the file:

```php
// FAQ translations - German
'menu.faq' => 'FAQ',
'menu.help' => 'Hilfe',
'menu.help.faq' => 'Häufige Fragen',
'menu.help.docs' => 'Dokumentation',
'menu.help.support' => 'Support',

'faq.title' => 'Häufig gestellte Fragen',
'faq.sidebar.title' => 'FAQ-Aktionen',
'faq.sidebar.search' => 'FAQ durchsuchen',

'faq.q1.question' => 'Wie erstelle ich einen neuen Benutzer?',
'faq.q1.answer' => 'Gehen Sie zu Administration > Benutzer und klicken Sie auf "Neuer Benutzer". Füllen Sie das Formular aus und speichern Sie.',

'faq.q2.question' => 'Wie aktiviere ich Zwei-Faktor-Authentifizierung?',
'faq.q2.answer' => 'Besuchen Sie Ihr Profil und aktivieren Sie die 2FA-Option. Scannen Sie den QR-Code mit einer Authenticator-App.',

'faq.q3.question' => 'Wie kann ich mein Passwort zurücksetzen?',
'faq.q3.answer' => 'Klicken Sie auf der Login-Seite auf "Passwort vergessen" und folgen Sie den Anweisungen in der E-Mail.',
```

### **English Translations: `app/lang/en.php`**

```php
// FAQ translations - English
'menu.faq' => 'FAQ',
'menu.help' => 'Help',
'menu.help.faq' => 'Frequently Asked Questions',
'menu.help.docs' => 'Documentation',
'menu.help.support' => 'Support',

'faq.title' => 'Frequently Asked Questions',
'faq.sidebar.title' => 'FAQ Actions',
'faq.sidebar.search' => 'Search FAQ',

'faq.q1.question' => 'How do I create a new user?',
'faq.q1.answer' => 'Go to Administration > Users and click "New User". Fill out the form and save.',

'faq.q2.question' => 'How do I enable Two-Factor Authentication?',
'faq.q2.answer' => 'Visit your profile and enable the 2FA option. Scan the QR code with an authenticator app.',

'faq.q3.question' => 'How can I reset my password?',
'faq.q3.answer' => 'Click "Forgot Password" on the login page and follow the instructions in the email.',
```

### **Translation Best Practices:**

✅ **Structure:**
- Hierarchical keys: `'section.subsection.element'`
- Consistent naming conventions
- Descriptive identifiers

✅ **Usage:**
- `{trans('key')}` in Latte templates
- `TranslationUtil::t('key')` in PHP code
- Never use hardcoded strings

---

## 📚 Best Practices & Guidelines

### **🔒 Security**

✅ **Input Validation:**
```php
// Always validate user input
$query = trim($_GET['q'] ?? '');
if (empty($query) || strlen($query) > 255) {
    Flight::json(['error' => 'Invalid query']);
    return;
}
```

✅ **Session Management:**
```php
// Session validation in every protected route
if (SessionUtil::get('user')['id'] === null) {
    Flight::redirect('/login');
    return;
}
```

✅ **CSRF Protection:**
```php
// For state-changing operations
if (!SessionUtil::validateCsrfToken($_POST['csrf_token'])) {
    Flight::json(['error' => 'Invalid CSRF token']);
    return;
}
```

### **📝 Code Quality**

✅ **PSR-12 Compliance:**
```bash
# Check code style
vendor/bin/phpcs app/Controllers/FaqController.php

# Auto-format
vendor/bin/php-cs-fixer fix app/Controllers/FaqController.php
```

✅ **Documentation:**
```php
/**
 * Descriptive docblock comments
 * 
 * @param string $query Search term
 * @return array Search results
 */
public function searchFaq(string $query): array
```

### **⚡ Performance**

✅ **Database Access:**
```php
// Optimize ORM queries
$users = ORM::for_table('users')
    ->where('status', 'active')
    ->limit(50)
    ->find_array();
```

✅ **JavaScript Optimization:**
```javascript
// Event delegation for better performance
document.addEventListener('click', function(e) {
    if (e.target.matches('.search-btn')) {
        handleSearch(e);
    }
});
```

### **🎨 UI/UX Guidelines**

✅ **Bootstrap Integration:**
```html
<!-- Use consistent Bootstrap classes -->
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">Title</h5>
        </div>
    </div>
</div>
```

✅ **Responsive Design:**
```html
<!-- Mobile-first approach -->
<div class="col-12 col-md-8 col-lg-6">
    <!-- Content -->
</div>
```

✅ **Accessibility:**
```html
<!-- ARIA labels and semantic HTML structure -->
<button class="btn btn-primary" 
        aria-label="Search FAQ"
        aria-describedby="searchHelp">
    Search
</button>
<small id="searchHelp" class="form-text">
    Enter a search term
</small>
```

---

## 🎯 Complete Example: Summary

After completing all steps, you have successfully:

1. ✅ **Created FAQ controller** (`app/Controllers/FaqController.php`)
2. ✅ **Developed FAQ template** (`app/views/faq.latte`)  
3. ✅ **Added routes** (`app/routes.php`)
4. ✅ **Extended navigation** (`app/views/_topbar.latte`)
5. ✅ **Implemented JavaScript** (`public/js/faq.latte.js`)
6. ✅ **Added translations** (`app/lang/de.php`, `app/lang/en.php`)

### **Testing the Implementation:**

```bash
# Start server
php -S localhost:8000 -t public

# Access FAQ page
curl http://localhost:8000/faq

# Test FAQ search  
curl "http://localhost:8000/faq/search?q=password"
```

### **Code Quality Check:**

```bash
# Check PHP syntax
php -l app/Controllers/FaqController.php

# Validate code style
vendor/bin/phpcs app/Controllers/FaqController.php

# Minify JavaScript
terser public/js/faq.latte.js -o public/js/faq.latte-min.js -c -m
```

---

## 🚀 Next Steps

With this knowledge, you can extend SecStore with any features:

- 📊 **Dashboard widgets**
- 🛠️ **Admin tools**  
- 🔌 **Controller endpoints**
- 📱 **Mobile app integration**
- 🎨 **Custom themes**

**Happy developing with SecStore!** 🎉

---

> **💡 Tip:** This documentation is continuously updated. Check regularly for updates in the [SecStore Repository](https://github.com/madcoda9000/SecStore).