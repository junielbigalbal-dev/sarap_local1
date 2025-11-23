<?php
/**
 * Session Management
 * Secure session handling and role-based access control
 */

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Get current user ID
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return getUserRole() === $role;
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . SITE_URL . '/pages/auth/login.php');
        exit;
    }
}

/**
 * Require specific role - redirect to appropriate dashboard if wrong role
 */
function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        // Redirect to user's appropriate dashboard
        switch (getUserRole()) {
            case 'customer':
                header('Location: ' . SITE_URL . '/pages/customer/dashboard.php');
                break;
            case 'vendor':
                header('Location: ' . SITE_URL . '/pages/vendor/dashboard.php');
                break;
            case 'admin':
                header('Location: ' . SITE_URL . '/pages/admin/dashboard.php');
                break;
            default:
                header('Location: ' . SITE_URL . '/pages/auth/login.php');
        }
        exit;
    }
}

/**
 * Login user - set session variables
 */
function loginUser($userId, $userRole, $userData = []) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = $userRole;
    $_SESSION['user_data'] = $userData;
    $_SESSION['login_time'] = time();
}

/**
 * Logout user - destroy session
 */
function logoutUser() {
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF token input field
 */
function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (isLoggedIn() && isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > SESSION_LIFETIME) {
            logoutUser();
            header('Location: ' . SITE_URL . '/pages/auth/login.php?timeout=1');
            exit;
        }
    }
}

// Check session timeout on every request
checkSessionTimeout();
