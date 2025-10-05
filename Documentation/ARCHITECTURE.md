# 🏗️ SecStore Architecture Documentation

> **Complete technical architecture overview for SecStore authentication framework**

---

## 📋 Table of Contents

1. [🎯 Architecture Overview](#-architecture-overview)
2. [🔄 Request Lifecycle](#-request-lifecycle)
3. [📂 Directory Structure](#-directory-structure)
4. [🧩 Core Components](#-core-components)
5. [🔐 Security Layer](#-security-layer)
6. [💾 Data Layer](#-data-layer)
7. [🎨 Presentation Layer](#-presentation-layer)
8. [⚙️ Configuration System](#️-configuration-system)
9. [🔌 Extension Points](#-extension-points)

---

## 🎯 Architecture Overview

SecStore follows a **Model-View-Controller (MVC)** architecture with additional **Middleware** and **Utility** layers for enhanced security and modularity.

```
┌─────────────────────────────────────────────────────────────┐
│                         CLIENT                               │
│                    (Browser/HTTP Client)                     │
└──────────────────────┬──────────────────────────────────────┘
                       │ HTTP Request
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                    public/index.php                          │
│                  (Application Bootstrap)                     │
│  • Environment Check (Dev/Production)                        │
│  • Setup Detection                                           │
│  • Flight Framework Init                                     │
│  • Latte Template Engine Config                             │
│  • Security Headers (CSP, HSTS, X-Frame-Options)            │
│  • Translation Init                                          │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                      app/routes.php                          │
│                    (Route Registration)                      │
│  • Setup Routes (if needed)                                  │
│  • Authentication Routes                                     │
│  • Protected User Routes                                     │
│  • Protected Admin Routes                                    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                  MIDDLEWARE CHAIN                            │
│  1. Rate Limiting (RateLimiter)                             │
│  2. Request Logging (LogUtil)                               │
│  3. IP Whitelist Check (Admin routes only)                  │
│  4. CSRF Protection (POST requests)                         │
│  5. Authentication Check                                     │
│  6. Authorization Check (Admin role)                        │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                     CONTROLLERS                              │
│  • AuthController (Login, Register, 2FA)                    │
│  • AdminController (Users, Roles, Settings)                 │
│  • ProfileController (User profile management)              │
│  • HomeController (Dashboard)                               │
│  • LogController (Log management)                           │
│  • RateLimitController (Rate limit management)              │
│  • SetupController (Initial setup wizard)                   │
│  • AzureSsoController (Azure AD SSO)                        │
└──────────────────────┬──────────────────────────────────────┘
                       │
        ┌──────────────┼──────────────┐
        ▼              ▼              ▼
┌─────────────┐ ┌──────────┐ ┌─────────────┐
│   MODELS    │ │  UTILS   │ │   VIEWS     │
│  • User     │ │ Session  │ │  Latte      │
│  • Role     │ │ Log      │ │ Templates   │
│             │ │ Security │ │             │
└──────┬──────┘ └────┬─────┘ └──────┬──────┘
       │             │               │
       ▼             ▼               ▼
┌─────────────────────────────────────────────┐
│              DATABASE LAYER                  │
│  • Paris ORM (Active Record)                │
│  • Idiorm (Query Builder)                   │
│  • PDO (Database Driver)                    │
└─────────────┬───────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────┐
│         MySQL/MariaDB Database               │
│  • users                                     │
│  • roles                                     │
│  • failed_logins                            │
│  • logs                                      │
└─────────────────────────────────────────────┘
```

---

## 🔄 Request Lifecycle

### **1. Request Entry**

```php
// public/index.php
┌─────────────────────────────────────────────────────────┐
│ 1. Environment Detection                                 │
│    • Check if Development or Production                  │
│    • Enable/Disable error display                        │
│                                                          │
│ 2. Setup Check                                          │
│    • Does config.php exist?                             │
│    • Is config.php writable?                            │
│    • Is database configured?                            │
│    • → If not: Redirect to /setup                       │
│                                                          │
│ 3. Framework Bootstrap                                  │
│    • Load Composer autoloader                           │
│    • Initialize Flight framework                        │
│    • Configure Latte template engine                    │
│    • Initialize Translation system                      │
│                                                          │
│ 4. Security Headers                                     │
│    • Content-Security-Policy (CSP)                      │
│    • HTTP Strict Transport Security (HSTS)             │
│    • X-Frame-Options: DENY                              │
│    • X-Content-Type-Options: nosniff                    │
│    • X-XSS-Protection: 1; mode=block                    │
│    • Referrer-Policy: no-referrer-when-downgrade        │
│    • Permissions-Policy: geolocation=()                 │
│                                                          │
│ 5. Load Routes                                          │
│    • include '../app/routes.php'                        │
│                                                          │
│ 6. Start Flight Framework                               │
│    • Flight::start()                                    │
└─────────────────────────────────────────────────────────┘
```

### **2. Route Matching**

```php
// app/routes.php

// Route Helper Functions:
// - secureRoute()      → GET routes with Auth + optional Admin check
// - securePostRoute()  → POST routes with Auth + CSRF + optional Admin
// - authRoute()        → Login/Register routes with Rate Limiting

┌─────────────────────────────────────────────────────────┐
│ Route Pattern Matching                                   │
│                                                          │
│ Examples:                                                │
│ • GET  /home             → secureRoute()                │
│ • POST /login            → authRoute() with CSRF        │
│ • GET  /admin/users      → secureRoute(requireAdmin)    │
│ • POST /admin/users/bulk → securePostRoute(requireAdmin)│
└─────────────────────────────────────────────────────────┘
```

### **3. Middleware Execution Chain**

```php
┌─────────────────────────────────────────────────────────┐
│ Middleware Chain (Sequential Execution)                  │
│                                                          │
│ 1️⃣ Rate Limiting Check                                  │
│    • checkRateLimit($limitType)                         │
│    • Scopes: login, admin, global, 2fa, etc.           │
│    • If exceeded: HTTP 429 + JSON error                │
│                                                          │
│ 2️⃣ Request Logging                                      │
│    • LogUtil::logAction(LogType::REQUEST, ...)         │
│    • Logs: Method, URI, Timestamp                      │
│                                                          │
│ 3️⃣ IP Whitelist Check (Admin routes only)              │
│    • IpWhitelistMiddleware::checkIpWhitelist()         │
│    • If not whitelisted: HTTP 403                       │
│                                                          │
│ 4️⃣ CSRF Protection (POST requests only)                │
│    • CsrfMiddleware->before([])                        │
│    • Validates CSRF token from form/header             │
│    • Token rotation for critical routes                │
│    • If invalid: HTTP 403                               │
│                                                          │
│ 5️⃣ Authentication Check                                 │
│    • AuthCheckMiddleware::checkIfAuthenticated()       │
│    • Checks session for user data                      │
│    • If not authenticated: Redirect to /login          │
│                                                          │
│ 6️⃣ Authorization Check (Admin routes only)             │
│    • AdminCheckMiddleware::checkForAdminRole()         │
│    • Checks if user has 'Admin' role                   │
│    • If not authorized: HTTP 403                        │
│                                                          │
│ ✅ All checks passed → Execute Controller Handler       │
└─────────────────────────────────────────────────────────┘
```

### **4. Controller Execution**

```php
┌─────────────────────────────────────────────────────────┐
│ Controller Method Execution                              │
│                                                          │
│ 1. Session Validation                                    │
│    if (SessionUtil::get('user')['id'] === null) {       │
│        Flight::redirect('/login');                      │
│    }                                                     │
│                                                          │
│ 2. Load User from Database                              │
│    $user = User::findUserById(                          │
│        SessionUtil::get('user')['id']                   │
│    );                                                    │
│                                                          │
│ 3. Business Logic Execution                             │
│    • Input Validation (InputValidator)                  │
│    • Database Operations (ORM)                          │
│    • Utility Functions (LogUtil, MailUtil, etc.)       │
│                                                          │
│ 4. Audit Logging                                        │
│    LogUtil::logAction(                                  │
│        LogType::AUDIT,                                  │
│        'ControllerName',                                │
│        'methodName',                                    │
│        'Action description',                            │
│        $user->username                                  │
│    );                                                    │
│                                                          │
│ 5. Render Response                                      │
│    • JSON: Flight::json([...])                         │
│    • HTML: Flight::latte()->render('view.latte', [...])│
│    • Redirect: Flight::redirect('/path')               │
└─────────────────────────────────────────────────────────┘
```

### **5. View Rendering**

```php
┌─────────────────────────────────────────────────────────┐
│ Latte Template Rendering                                 │
│                                                          │
│ 1. Template Selection                                    │
│    • Authenticated pages: {extends '_mainLayout.latte'} │
│    • Auth pages: {extends '_authLayout.latte'}         │
│                                                          │
│ 2. Data Binding                                         │
│    • $user, $title, $sessionTimeout, etc.              │
│    • Translation: {trans('key')}                        │
│                                                          │
│ 3. CSP-Compliant Output                                │
│    • No inline scripts or styles                        │
│    • External JS: <script src="/js/file.js">           │
│    • Data transfer via data-attributes                  │
│                                                          │
│ 4. Cache Management                                     │
│    • Latte compiles to PHP in cache/                   │
│    • Production: Cached templates                       │
│    • Development: Auto-reload on changes               │
└─────────────────────────────────────────────────────────┘
```

---

## 📂 Directory Structure

```
SecStore/
├── 📁 public/                      # Web Root (Entry Point)
│   ├── index.php                   # Application Bootstrap
│   ├── css/                        # Stylesheets
│   │   └── style.css              # Main CSS
│   └── js/                         # JavaScript Files
│       ├── *.latte.js             # Page-specific JS
│       └── admin/                  # Admin-specific JS
│
├── 📁 app/                         # Core Application
│   ├── 📁 Controllers/            # MVC Controllers
│   │   ├── AdminController.php    # Admin panel logic
│   │   ├── AuthController.php     # Authentication
│   │   ├── ProfileController.php  # User profile
│   │   ├── HomeController.php     # Dashboard
│   │   ├── LogController.php      # Log management
│   │   ├── RateLimitController.php# Rate limiting
│   │   ├── SetupController.php    # Initial setup wizard
│   │   └── AzureSsoController.php # Azure AD SSO
│   │
│   ├── 📁 Models/                 # Data Models (ORM)
│   │   └── User.php               # User model (Paris ORM)
│   │
│   ├── 📁 Utils/                  # Helper Classes
│   │   ├── SessionUtil.php        # Session management
│   │   ├── LogUtil.php            # Logging system
│   │   ├── TranslationUtil.php    # i18n support
│   │   ├── InputValidator.php     # Input validation
│   │   ├── MailUtil.php           # Email sending
│   │   ├── LdapUtil.php           # LDAP auth
│   │   ├── BruteForceUtil.php     # Brute force protection
│   │   ├── BackupCodeUtil.php     # 2FA backup codes
│   │   ├── SecurityMetrics.php    # Security analytics
│   │   ├── LoginAnalytics.php     # Login pattern analysis
│   │   └── CorsUtil.php           # CORS handling
│   │
│   ├── 📁 Middleware/             # Request Middleware
│   │   ├── CsrfMiddleware.php     # CSRF protection
│   │   ├── AuthCheckMiddleware.php# Authentication check
│   │   ├── AdminCheckMiddleware.php# Admin authorization
│   │   ├── RateLimiter.php        # Rate limiting
│   │   └── IpWhitelistMiddleware.php# IP whitelist
│   │
│   ├── 📁 views/                  # Latte Templates
│   │   ├── _mainLayout.latte      # Authenticated layout
│   │   ├── _authLayout.latte      # Login/Register layout
│   │   ├── _topbar.latte          # Navigation bar
│   │   ├── _footer.latte          # Footer
│   │   ├── login.latte            # Login page
│   │   ├── register.latte         # Registration
│   │   ├── home.latte             # Dashboard
│   │   ├── profile.latte          # User profile
│   │   ├── setup.latte            # Setup wizard
│   │   └── admin/                 # Admin templates
│   │       ├── users.latte        # User management
│   │       ├── roles.latte        # Role management
│   │       ├── settings.latte     # System settings
│   │       └── logs*.latte        # Log viewers
│   │
│   ├── 📁 lang/                   # Translations
│   │   ├── de.php                 # German
│   │   └── en.php                 # English
│   │
│   ├── routes.php                 # Route definitions
│   └── DatabaseSetup.php          # DB initialization
│
├── 📁 database/                    # Database Files
│   ├── schema.sql                 # Table structures
│   └── default_data.sql           # Default admin + roles
│
├── 📁 cache/                       # Template Cache
│   └── *.php                      # Compiled Latte templates
│
├── 📁 vendor/                      # Composer Dependencies
│   ├── flight/                    # Flight framework
│   ├── latte/                     # Latte template engine
│   ├── idiorm/                    # Idiorm ORM
│   ├── paris/                     # Paris Active Record
│   ├── phpmailer/                 # Email library
│   └── robthree/                  # 2FA library
│
├── 📁 tests/                       # PHPUnit Tests
│   ├── Unit/                      # Unit tests
│   ├── Integration/               # Integration tests
│   ├── TestCase.php               # Base test class
│   └── bootstrap.php              # Test environment
│
├── 📁 Documentation/               # Project Documentation
│   ├── INSTALL.md                 # Installation guide
│   ├── DEVDOC.md                  # Developer documentation
│   ├── ARCHITECTURE.md            # This file
│   ├── TESTING.md                 # Testing strategy
│   ├── SECURITY.md                # Security policy
│   ├── CHANGELOG.md               # Version history
│   └── Screenshots/               # UI screenshots
│
├── config.php                     # Main Configuration
├── config.php_TEMPLATE            # Config template
├── composer.json                  # PHP dependencies
├── phpunit.xml                    # PHPUnit config
├── .env.example                   # Docker env template
├── docker-compose.yml             # Docker setup
└── README.md                      # Project overview
```

---

## 🧩 Core Components

### **1. Flight PHP Framework**

SecStore uses **Flight PHP** - a lightweight micro-framework that provides:

```php
// Core Flight Features Used:
Flight::route()           // Routing
Flight::redirect()        // Redirects
Flight::json()           // JSON responses
Flight::halt()           // Stop execution with status code
Flight::latte()          // Latte template engine integration
Flight::request()        // HTTP request object
Flight::response()       // HTTP response object
Flight::get()/set()      // Container for DI
```

**Why Flight?**
- ✅ Minimal overhead (~100KB)
- ✅ No MVC enforcement (freedom to structure)
- ✅ Easy to extend
- ✅ Excellent performance
- ✅ Simple routing

### **2. Latte Template Engine**

**Latte** is a modern, secure template engine with automatic XSS protection:

```latte
{* Template Inheritance *}
{extends '_mainLayout.latte'}

{block content}
    <h1>{trans('page.title')}</h1>
    
    {* Translation Support *}
    <p>{trans('welcome.message')}</p>
    
    {* CSP-Compliant Data Transfer *}
    <div id="config" 
         data-url="{$apiUrl}" 
         data-timeout="{$sessionTimeout}">
    </div>
    
    {* Loops and Conditionals *}
    {foreach $users as $user}
        <div class="user">{$user->username}</div>
    {/foreach}
    
    {if $user->isAdmin}
        <a href="/admin">Admin Panel</a>
    {/if}
{/block}
```

**Latte Features:**
- ✅ Auto-escaping (XSS protection)
- ✅ Template inheritance
- ✅ Macros and blocks
- ✅ Compiled to PHP (fast)
- ✅ Caching system

### **3. Paris/Idiorm ORM**

**Paris** (Active Record) built on **Idiorm** (Query Builder):

```php
// Active Record (Paris)
$user = User::findUserByEmail('user@example.com');
$user->status = 1;
$user->save();

// Query Builder (Idiorm)
$users = ORM::for_table('users')
    ->where('status', 1)
    ->where_like('email', '%@example.com')
    ->order_by_desc('created_at')
    ->limit(10)
    ->find_array();

// Raw SQL when needed
$users = ORM::for_table('users')
    ->raw_query("SELECT * FROM users WHERE roles LIKE ?", ['%Admin%'])
    ->find_array();
```

**ORM Features:**
- ✅ Lightweight (single file)
- ✅ Active Record pattern
- ✅ Fluent query interface
- ✅ Prepared statements (SQL injection protection)
- ✅ Query logging

### **4. Translation System**

```php
// app/Utils/TranslationUtil.php

// Initialize with browser detection
TranslationUtil::init();

// Or set explicitly
TranslationUtil::setLang('de');

// Get current language
$lang = TranslationUtil::getLang(); // 'en' or 'de'

// Translate
echo TranslationUtil::t('login.title'); // "Login"

// In Latte templates
{trans('login.title')}
```

**Translation Files:**
```php
// app/lang/en.php
return [
    'login.title' => 'Login',
    'login.username' => 'Username',
    'login.password' => 'Password',
    // ...
];

// app/lang/de.php
return [
    'login.title' => 'Anmelden',
    'login.username' => 'Benutzername',
    'login.password' => 'Passwort',
    // ...
];
```

---

## 🔐 Security Layer

### **1. Authentication Flow**

```
┌─────────────────────────────────────────────────────────┐
│                  LOGIN PROCESS                           │
│                                                          │
│ 1. User submits credentials                             │
│    • Username + Password                                │
│    • CSRF token validated                               │
│                                                          │
│ 2. Brute Force Check                                    │
│    • BruteForceUtil::isAccountLocked($username)        │
│    • Max attempts: 5 in 15 minutes (configurable)      │
│    • If locked: Display error + lockout time           │
│                                                          │
│ 3. User Lookup                                          │
│    • User::findUserByUsername($username)               │
│    • Check if account exists                            │
│    • Check if account is enabled (status = 1)          │
│                                                          │
│ 4. Authentication Method Selection                      │
│    ┌─────────────────┬──────────────────┬──────────┐  │
│    │ Entra ID SSO?   │ LDAP Enabled?    │ Local?   │  │
│    ├─────────────────┼──────────────────┼──────────┤  │
│    │ Redirect Azure  │ LdapUtil::auth() │ password │  │
│    │ (external)      │ (LDAP server)    │ verify   │  │
│    └─────────────────┴──────────────────┴──────────┘  │
│                                                          │
│ 5. Password Verification (if local auth)                │
│    • password_verify($password, $user->password)       │
│    • If fails: Record failed attempt + exit            │
│                                                          │
│ 6. 2FA Check (if enabled)                              │
│    • Store user in session temporarily                  │
│    • Redirect to /2fa-verify                           │
│    • Validate TOTP code or backup code                 │
│                                                          │
│ 7. Session Creation                                     │
│    • SessionUtil::createSession($user)                 │
│    • Generate CSRF token                                │
│    • Store user data in session                         │
│    • Session fingerprint (User-Agent + Language)       │
│                                                          │
│ 8. Login Analytics                                      │
│    • LoginAnalytics::recordLogin($user)                │
│    • Track IP, timestamp, browser                      │
│                                                          │
│ 9. Redirect to Dashboard                                │
│    • Flight::redirect('/home')                         │
└─────────────────────────────────────────────────────────┘
```

### **2. Session Security**

```php
// app/Utils/SessionUtil.php

┌─────────────────────────────────────────────────────────┐
│                SESSION MANAGEMENT                        │
│                                                          │
│ Session Structure:                                       │
│ $_SESSION = [                                           │
│     'user' => [                                         │
│         'id' => 1,                                      │
│         'username' => 'john.doe',                       │
│         'email' => 'john@example.com',                  │
│         'roles' => 'Admin,User',                        │
│     ],                                                   │
│     'csrf_token' => 'random_token_here',               │
│     'last_activity' => 1706789123,                     │
│     'session_fingerprint' => 'hash_of_ua_lang',        │
│     'created_at' => 1706785523,                        │
│ ];                                                       │
│                                                          │
│ Security Features:                                       │
│ ✅ Session ID Regeneration (every 30 minutes)          │
│ ✅ Session Fingerprinting (UA + Accept-Language)       │
│ ✅ Timeout Detection (configurable)                    │
│ ✅ CSRF Token per Session                              │
│ ✅ HttpOnly + Secure + SameSite cookies                │
│ ✅ Automatic Cleanup on Logout                         │
└─────────────────────────────────────────────────────────┘
```

### **3. CSRF Protection**

```php
// app/Middleware/CsrfMiddleware.php

┌─────────────────────────────────────────────────────────┐
│                 CSRF MIDDLEWARE                          │
│                                                          │
│ Token Generation:                                        │
│ • bin2random_bytes(32) → base64 encode                 │
│ • Stored in session: $_SESSION['csrf_token']           │
│                                                          │
│ Token Validation:                                        │
│ 1. Extract token from:                                  │
│    • POST: $_POST['csrf_token']                        │
│    • Header: X-CSRF-Token, X-Csrf-Token, CSRF-Token    │
│                                                          │
│ 2. Compare using hash_equals()                         │
│    • Timing-safe comparison                             │
│                                                          │
│ 3. Token Rotation Strategy:                            │
│    • REFRESH for critical routes:                      │
│      - /login, /register, /reset-password              │
│      - /profileChangePassword, /2fa-verify             │
│                                                          │
│    • NO REFRESH for UX-critical routes:                │
│      - /admin/users/bulk (multi-select operations)     │
│      - /admin/rate-limits/update (settings)            │
│                                                          │
│ Error Handling:                                         │
│ • Missing token: HTTP 400                              │
│ • Invalid token: HTTP 403                               │
│ • Session expired: Redirect to /login                  │
└─────────────────────────────────────────────────────────┘
```

### **4. Rate Limiting**

```php
// app/Middleware/RateLimiter.php

┌─────────────────────────────────────────────────────────┐
│                  RATE LIMITING                           │
│                                                          │
│ Limit Scopes (requests per time window):                │
│ • login:            5 req / 5 min                       │
│ • register:         3 req / 60 min                      │
│ • forgot-password:  3 req / 60 min                      │
│ • 2fa:             10 req / 5 min                       │
│ • admin:           50 req / 60 min                      │
│ • global:         500 req / 60 min                      │
│                                                          │
│ Algorithm: Sliding Window                                │
│ • Store: $_SESSION['rate_limits'][$key]                │
│ • Format: [$timestamp1, $timestamp2, ...]              │
│ • Cleanup: Remove timestamps outside window            │
│                                                          │
│ Identifier:                                              │
│ • IP + User-Agent hash                                  │
│ • Key: "{scope}:{identifier}"                          │
│                                                          │
│ Violation Handling:                                     │
│ • Log to database (logs table)                         │
│ • Return HTTP 429 + JSON error                         │
│ • Track statistics for security dashboard              │
└─────────────────────────────────────────────────────────┘
```

### **5. Input Validation**

```php
// app/Utils/InputValidator.php

┌─────────────────────────────────────────────────────────┐
│              INPUT VALIDATION                            │
│                                                          │
│ Validation Rules:                                        │
│ • RULE_REQUIRED        → Not empty                      │
│ • RULE_EMAIL          → Valid email format              │
│ • RULE_USERNAME       → Alphanumeric + _ - .           │
│ • RULE_PASSWORD_STRONG → Min 12 chars, uppercase,      │
│                          lowercase, number              │
│ • RULE_NUMERIC        → Integer only                    │
│ • RULE_BOOLEAN        → true/false                      │
│ • RULE_MIN_LENGTH     → Minimum length                  │
│ • RULE_MAX_LENGTH     → Maximum length                  │
│ • RULE_OTP            → 6-digit TOTP code              │
│                                                          │
│ Usage Example:                                          │
│ $rules = [                                              │
│     'email' => [                                        │
│         InputValidator::RULE_REQUIRED,                 │
│         InputValidator::RULE_EMAIL,                    │
│         [InputValidator::RULE_MAX_LENGTH => 255]       │
│     ],                                                   │
│     'password' => [                                     │
│         InputValidator::RULE_REQUIRED,                 │
│         InputValidator::RULE_PASSWORD_STRONG           │
│     ]                                                    │
│ ];                                                       │
│                                                          │
│ $validated = InputValidator::validateAndSanitize(      │
│     $rules,                                             │
│     $_POST                                              │
│ );                                                       │
│                                                          │
│ Sanitization:                                           │
│ • htmlspecialchars() for strings                       │
│ • filter_var() for emails                              │
│ • Type casting for numbers/booleans                    │
└─────────────────────────────────────────────────────────┘
```

---

## 💾 Data Layer

### **Database Schema**

```sql
-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(255) NOT NULL,
    lastname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,        -- BCRYPT hash
    status TINYINT(1) DEFAULT 1,           -- 0=disabled, 1=enabled
    roles VARCHAR(255) NOT NULL,           -- Comma-separated: 'Admin,User'
    
    -- Password Reset
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    
    -- 2FA Fields
    mfaStartSetup TINYINT(1) DEFAULT 0,
    mfaEnabled TINYINT(1) DEFAULT 0,
    mfaEnforced TINYINT(1) DEFAULT 0,
    mfaSecret VARCHAR(2500) DEFAULT '',
    mfaBackupCodes TEXT DEFAULT NULL,      -- JSON array of hashed codes
    
    -- External Auth
    ldapEnabled TINYINT(1) DEFAULT 0,
    entraIdEnabled TINYINT(1) DEFAULT 0,
    
    -- Registration Verification
    verification_token VARCHAR(255) DEFAULT NULL,
    verification_token_expires DATETIME DEFAULT NULL,
    
    -- Session Tracking
    activeSessionId VARCHAR(255) DEFAULT '',
    lastKnownIp VARCHAR(255) DEFAULT '',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles Table
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    roleName VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed Logins (Brute Force Protection)
CREATE TABLE failed_logins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255) NOT NULL,
    attempts INT DEFAULT 1,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
                           ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_ip_email (ip_address, email),
    INDEX idx_last_attempt (last_attempt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs Table
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    datum_zeit DATETIME DEFAULT CURRENT_TIMESTAMP,
    type ENUM('ERROR','AUDIT','REQUEST','SYSTEM','MAIL','SQL','SECURITY') NOT NULL,
    user VARCHAR(255) NOT NULL,
    context TEXT NOT NULL,              -- ControllerName/methodName
    message TEXT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    
    INDEX idx_type (type),
    INDEX idx_user (user),
    INDEX idx_datum_zeit (datum_zeit),
    INDEX idx_context (context(100))    -- Partial index for TEXT column
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### **ORM Usage Patterns**

```php
// ============================================
// USER MODEL (Paris Active Record)
// ============================================

// Create
$user = User::createUser(
    'john.doe',                    // username
    'john@example.com',           // email
    'John',                        // firstname
    'Doe',                         // lastname
    1,                             // status
    password_hash('pass', PASSWORD_DEFAULT),
    'User',                        // roles
    0                              // ldapEnabled
);

// Find
$user = User::findUserById(1);
$user = User::findUserByUsername('john.doe');
$user = User::findUserByEmail('john@example.com');

// Update
$user = User::findUserById(1);
$user->status = 0;
$user->save();

// Delete
$user->delete();

// Custom Queries
$activeAdmins = ORM::for_table('users')
    ->where('status', 1)
    ->where_like('roles', '%Admin%')
    ->find_many();

// ============================================
// LOGGING
// ============================================

LogUtil::logAction(
    LogType::AUDIT,               // Type: AUDIT, ERROR, SECURITY, etc.
    'AuthController',             // Context (Controller name)
    'login',                      // Method name
    'User logged in successfully', // Message
    'john.doe'                    // Username (optional)
);

// Fetch logs
$logs = ORM::for_table('logs')
    ->where('type', 'AUDIT')
    ->where('user', 'john.doe')
    ->order_by_desc('datum_zeit')
    ->limit(50)
    ->find_array();

// ============================================
// SECURITY METRICS
// ============================================

// Daily summary
$summary = SecurityMetrics::generateDailySummary();
// Returns:
// [
//     'failed_logins' => 15,
//     'successful_logins' => 123,
//     'password_resets' => 2,
//     'new_registrations' => 5,
//     'csrf_violations' => 0,
//     'rate_limit_hits' => 3
// ]

// Weekly metrics
$weekly = SecurityMetrics::getWeeklyMetrics();

// Security score
$dashboard = SecurityMetrics::getSecurityDashboardData();
```

---

## 🎨 Presentation Layer

### **Latte Template Hierarchy**

```
_mainLayout.latte           (Authenticated users)
    ├── _topbar.latte       (Navigation)
    ├── {block content}     (Page content)
    └── _footer.latte       (Footer + session timer)

_authLayout.latte           (Login/Register pages)
    ├── {block content}     (Login form, etc.)
    └── _footer.latte

Admin Templates:
admin/users.latte
admin/roles.latte
admin/settings.latte
admin/logs*.latte
```

### **CSP-Compliant JavaScript Pattern**

```html
<!-- ❌ WRONG: Inline Script -->
<button onclick="deleteUser(123)">Delete</button>

<!-- ✅ CORRECT: Data Attributes + External JS -->
<button class="delete-btn" data-user-id="123">Delete</button>

<script src="/js/admin/users.latte.js"></script>
```

```javascript
// public/js/admin/users.latte.js

document.addEventListener('DOMContentLoaded', function() {
    // Event delegation for dynamic elements
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-btn')) {
            const userId = e.target.getAttribute('data-user-id');
            deleteUser(userId);
        }
    });
});

function deleteUser(userId) {
    if (!confirm('Delete user?')) return;
    
    fetch('/admin/deleteUser', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': getCsrfToken()
        },
        body: `id=${userId}&csrf_token=${getCsrfToken()}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
```

---

## ⚙️ Configuration System

### **config.php Structure**

```php
<?php
return [
    // ==========================================
    // APPLICATION SETTINGS
    // ==========================================
    'application' => [
        'appUrl' => 'http://localhost:8000',
        'sessionTimeout' => 1800,              // 30 minutes
        'allowPublicRegister' => true,
        'allowPublicPasswordReset' => true,
        'environment' => 'development',        // or 'production'
    ],

    // ==========================================
    // DATABASE CONFIGURATION
    // ==========================================
    'db' => [
        'host' => 'localhost',
        'name' => 'secstore',
        'user' => 'secstore_user',
        'pass' => 'secure_password',
        'port' => 3306,
    ],

    // ==========================================
    // SECURITY SETTINGS
    // ==========================================
    'security' => [
        'sessionFingerprinting' => true,
        'sessionIdRegeneration' => true,
        'regenerationInterval' => 1800,       // 30 min
    ],

    // ==========================================
    // BRUTE FORCE PROTECTION
    // ==========================================
    'bruteforce' => [
        'enabled' => true,
        'maxAttempts' => 5,
        'lockoutDuration' => 900,             // 15 minutes
    ],

    // ==========================================
    // RATE LIMITING
    // ==========================================
    'rateLimiting' => [
        'enabled' => true,
        'limits' => [
            'login' => ['requests' => 5, 'window' => 300],
            'admin' => ['requests' => 50, 'window' => 3600],
            'global' => ['requests' => 500, 'window' => 3600],
        ],
    ],

    // ==========================================
    // LOGGING CONFIGURATION
    // ==========================================
    'logging' => [
        'enableSqlLogging' => true,
        'enableMailLogging' => true,
        'enableSystemLogging' => true,
        'enableAuditLogging' => true,
        'enableRequestLogging' => true,
        'enableSecurityLogging' => true,
    ],

    // ==========================================
    // EMAIL SETTINGS
    // ==========================================
    'mail' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'noreply@example.com',
        'password' => 'smtp_password',
        'from' => 'noreply@example.com',
        'fromName' => 'SecStore',
        'encryption' => 'tls',                // or 'ssl'
        'enableWelcomeEmail' => true,
    ],

    // ==========================================
    // LDAP SETTINGS
    // ==========================================
    'ldapSettings' => [
        'ldapHost' => 'ldap.example.com',
        'ldapPort' => 636,
        'domainPrefix' => 'DOMAIN\\',
    ],

    // ==========================================
    // AZURE SSO / ENTRA ID
    // ==========================================
    'azureSso' => [
        'enabled' => false,
        'mockMode' => false,                  // Development only
        'tenantId' => 'your-tenant-id',
        'clientId' => 'your-client-id',
        'clientSecret' => 'your-client-secret',
        'redirectUri' => 'http://localhost:8000/auth/azure/callback',
    ],
];
```

---

## 🔌 Extension Points

### **1. Adding a New Controller**

```php
// app/Controllers/BlogController.php
<?php
namespace App\Controllers;

use App\Models\User;
use App\Utils\SessionUtil;
use App\Utils\LogUtil;
use App\Utils\LogType;
use App\Utils\TranslationUtil;
use Flight;

class BlogController
{
    public function showBlog()
    {
        if (SessionUtil::get('user')['id'] === null) {
            Flight::redirect('/login');
            return;
        }

        $user = User::findUserById(SessionUtil::get('user')['id']);
        
        Flight::latte()->render('blog.latte', [
            'title' => TranslationUtil::t('blog.title'),
            'user' => $user,
            'sessionTimeout' => SessionUtil::getRemainingTime(),
        ]);
    }
}
```

### **2. Adding Routes**

```php
// app/routes.php

// Add at the end, before closing PHP tag
secureRoute('GET /blog', function () {
    (new BlogController())->showBlog();
}, 'global', false);
```

### **3. Adding a Middleware**

```php
// app/Middleware/CustomMiddleware.php
<?php
namespace App\Middleware;

class CustomMiddleware
{
    public static function before()
    {
        // Custom logic before request
        // E.g., check for maintenance mode
        
        if (file_exists('../maintenance.flag')) {
            Flight::halt(503, 'Maintenance in progress');
        }
    }
}
```

```php
// Use in routes.php
Flight::route('GET /some-route', function() {
    CustomMiddleware::before();
    // ... controller logic
});
```

### **4. Adding a Utility**

```php
// app/Utils/FileUtil.php
<?php
namespace App\Utils;

class FileUtil
{
    public static function uploadFile($file, $destination)
    {
        // File upload logic
        // Validation, sanitization, storage
    }
    
    public static function deleteFile($path)
    {
        // Safe file deletion
    }
}
```

### **5. Adding a Model**

```php
// app/Models/Post.php
<?php
namespace App\Models;

use ORM;

class Post extends ORM
{
    protected static $tableName = 'posts';
    
    public static function findBySlug($slug)
    {
        return ORM::for_table(self::$tableName)
            ->where('slug', $slug)
            ->find_one();
    }
    
    public static function getAllPublished()
    {
        return ORM::for_table(self::$tableName)
            ->where('status', 'published')
            ->order_by_desc('created_at')
            ->find_many();
    }
}
```

---

## 📊 Performance Considerations

### **1. Database Query Optimization**

```php
// ❌ BAD: N+1 Query Problem
foreach ($users as $user) {
    $role = ORM::for_table('roles')
        ->where('id', $user->role_id)
        ->find_one();
}

// ✅ GOOD: Single Query with JOIN or WHERE IN
$userIds = array_column($users, 'role_id');
$roles = ORM::for_table('roles')
    ->where_in('id', $userIds)
    ->find_many();
```

### **2. Latte Template Caching**

```php
// Production: Pre-compile all templates
$latte->setTempDirectory('../cache/');

// Development: Auto-reload on changes
// (already configured in index.php)
```

### **3. Session Storage**

```php
// For high-traffic: Use Redis/Memcached instead of file-based sessions
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://127.0.0.1:6379');
```

---

## 🔍 Debugging

### **1. Enable Logging**

```php
// index.php (Development only)
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Check logs table
SELECT * FROM logs ORDER BY datum_zeit DESC LIMIT 50;
```

### **2. ORM Query Logging**

```php
// Enable in controller
ORM::configure('logging', true);

// Execute queries
$users = ORM::for_table('users')->find_many();

// Get logged queries
$queries = ORM::get_query_log();
var_dump($queries);
```

### **3. Session Debugging**

```php
// Check session contents
var_dump($_SESSION);

// Check CSRF token
echo SessionUtil::getCsrfToken();
```

---

## 📚 Further Reading

- **[DEVDOC.md](DEVDOC.md)** - Developer guide for extending SecStore
- **[TESTING.md](TESTING.md)** - Testing strategy and guidelines
- **[SECURITY.md](SECURITY.md)** - Security policies and best practices
- **[Flight PHP Docs](https://flightphp.com/learn)** - Framework documentation
- **[Latte Documentation](https://latte.nette.org/)** - Template engine guide
- **[Paris/Idiorm](https://github.com/j4mie/idiorm)** - ORM documentation

---

**🎯 This architecture enables:**
- ✅ Rapid feature development
- ✅ High security by default
- ✅ Easy maintenance
- ✅ Scalability
- ✅ Test coverage for critical paths

**Built with ❤️ for developers who value security and simplicity.**