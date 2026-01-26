<?php
/**
 * Mïse API Configuration
 *
 * IMPORTANT: This file contains sensitive credentials.
 * - Do NOT commit to version control with real passwords
 * - Server should have its own copy with production credentials
 */

// Database Configuration (PostgreSQL)
define('DB_HOST', '10.0.0.16');
define('DB_NAME', 'mise');
define('DB_USER', 'mise_user');
define('DB_PASS', 'mise_user');

// Authentication
// TODO: Change this password before deploying to production!
define('AUTH_PASSWORD', 'mise_admin');

// Session Configuration
define('SESSION_LIFETIME', 86400 * 7); // 7 days in seconds
define('SESSION_COOKIE_NAME', 'mise_session');

// Security
define('CORS_ALLOWED_ORIGINS', [
    'http://localhost:5173',        // SvelteKit dev server
    'http://localhost:4173',        // SvelteKit preview
    'http://10.0.0.16',             // Production server
]);

// Recipe Validation Constants
define('VALID_CATEGORIES', ['main', 'starter', 'dessert', 'side', 'drink', 'sauce']);
define('VALID_DIFFICULTIES', ['easy', 'intermediate', 'advanced']);
