-- SecStore Default Data
-- Generated: 2025-09-20 06:28:53
-- Database: secstore_test1222
-- Description: Default roles and admin user for SecStore

-- ===================================
-- Default data for: roles
-- ===================================
INSERT INTO `roles` (`id`, `roleName`) VALUES ('1', 'Admin');
INSERT INTO `roles` (`id`, `roleName`) VALUES ('2', 'User');

-- ===================================
-- Default data for: users
-- ===================================
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `username`, `password`, `status`, `roles`, `reset_token`, `reset_token_expires`, `mfaStartSetup`, `mfaEnabled`, `mfaEnforced`, `mfaSecret`, `ldapEnabled`, `created_at`, `activeSessionId`, `lastKnownIp`) VALUES ('4', 'Super', 'Admin', 'super.admin@test.local', 'super.admin', '$2y$12$OkyTM75e6a8FmwZtuHDDW.8C7.4dpVtCZr1z8wXa/djUTQc9I1INe', '1', 'Admin', '', NULL, '0', '0', '0', '', '0', '2025-09-19 17:48:43', '', '');

-- ===================================
-- 🔐 SECURITY NOTES
-- ===================================
-- 
-- Default Admin Credentials:
-- Username: super.admin
-- Password: Test1000!
-- Email: super.admin@test.local
-- 
-- ⚠️  IMPORTANT: Change the admin password immediately after first login!
-- 
-- Production Recommendations:
-- 1. Change admin password
-- 2. Update admin email to real address
-- 3. Enable 2FA for admin accounts
-- 4. Create additional admin users
-- 5. Consider disabling default admin after setup
