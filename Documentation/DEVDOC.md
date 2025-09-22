# 🧑‍💻 SecStore Developer Documentation

> **A comprehensive guide to extending SecStore with custom pages and features**

---

## 📋 Table of Contents

1. [🔧 Setting up Development Environment](#-setting-up-development-environment)
2. [📦 SecStore Installation](#-secstore-installation)  
3. [🎯 Web-Based Setup (Recommended)](#-web-based-setup-recommended)
4. [🆕 Creating Your First Custom Page](#-creating-your-first-custom-page)
5. [📄 Creating Latte Templates](#-creating-latte-templates)
6. [🎮 Developing Controllers](#-developing-controllers)
7. [🛣️ Adding Routes](#️-adding-routes)
8. [🧭 Extending Navigation](#-extending-navigation)
9. [💾 JavaScript Integration](#-javascript-integration)
10. [🌍 Implementing Multilingual Support](#-implementing-multilingual-support)
11. [📚 Best Practices & Guidelines](#-best-practices--guidelines)

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

---

## 📦 SecStore Installation

### **Clone Project and Setup**

```bash
# Clone repository
git clone https://github.com/madcoda9000/SecStore.git
cd SecStore

# Install dependencies (with dev tools)
composer install

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

2. **The setup wizard automatically activates** if:
   - `config.php` is missing
   - Database is not configured
   - Setup hasn't been completed

3. **Follow the guided steps:**

#### **Step 1: Configuration File**
- The wizard checks if `config.php` exists
- If missing, shows exact commands to run:
  ```bash
  cp config.php_TEMPLATE config.php
  chmod 664 config.php
  chown www-data:www-data config.php
  ```

#### **Step 2: File Permissions**
- Verifies web server can write to configuration file
- Shows permission fix commands if needed

#### **Step 3: Database Configuration**
- Interactive form for database credentials:
  - **Host**: Usually `localhost`
  - **Database Name**: e.g., `secstore_dev`
  - **Username**: Your MySQL/MariaDB user
  - **Password**: Your database password
- **Automatic validation** - tests connection before saving
- **Schema creation** - creates all tables and indexes
- **Admin user creation** - sets up default administrator

#### **Step 4: Email Configuration (Optional)**
- Configure SMTP settings for email features
- **Can be skipped** and configured later in admin panel
- Validates SMTP connection before saving

### **🔐 Default Login Credentials**

After successful setup:

| Field | Value |
|-------|-------|
| **Username** | `super.admin` |
| **Password** | `Test1000!` |
| **Email** | `super.admin@test.local` |

> **⚠️ Important:** Change the default password immediately after first login!

### **🎯 Setup URLs**

| URL | Purpose |
|-----|---------|
| `http://localhost:8000/` | Main entry - auto-redirects to setup if needed |
| `http://localhost:8000/setup` | Direct access to setup wizard |

> **💡 Tip:** The setup wizard is automatically disabled after completion for security.

---

## 🆕 Creating Your First Custom Page

Let's create an example **"FAQ"** page to demonstrate all concepts.

### **Step-by-Step Guide:**

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
                    <button class="btn btn-primary" id="searchBtn" data-search-url="/api/faq/search">
                        {trans('faq.sidebar.search')}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{* CSP-compliant data transfer *}
<div id="faq-config" 
     data-search-url="/api/faq/search"
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
     * API: FAQ Search
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

// FAQ search API (protected)
secureRoute('GET /api/faq/search', function () {
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
| **Path** | URL path like `/faq` or `/api/faq/search` | Can contain parameters |
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
        searchUrl: '/api/faq/search',
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
        
        // API call
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
curl "http://localhost:8000/api/faq/search?q=password"
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
- 🔌 **API endpoints**
- 📱 **Mobile app integration**
- 🎨 **Custom themes**

**Happy developing with SecStore!** 🎉

---

> **💡 Tip:** This documentation is continuously updated. Check regularly for updates in the [SecStore Repository](https://github.com/madcoda9000/SecStore).