<?php
/**
 * Authentication API Endpoint
 *
 * POST /api/auth.php   - Login with password
 * DELETE /api/auth.php - Logout (clear session)
 * GET /api/auth.php    - Check authentication status
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

// Set JSON response headers
header('Content-Type: application/json; charset=utf-8');

// Handle CORS
handleCors();

// Route the request
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleCheckAuth();
            break;
        case 'POST':
            handleLogin();
            break;
        case 'DELETE':
            handleLogout();
            break;
        case 'OPTIONS':
            http_response_code(204);
            break;
        default:
            sendError(405, 'Method not allowed');
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    sendError(500, 'Database error');
} catch (Exception $e) {
    error_log('Server error: ' . $e->getMessage());
    sendError(500, 'Server error');
}

/**
 * Handle CORS headers
 */
function handleCors(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($origin, CORS_ALLOWED_ORIGINS, true)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
    }

    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
}

/**
 * Handle GET - Check authentication status
 */
function handleCheckAuth(): void
{
    $token = $_COOKIE[SESSION_COOKIE_NAME] ?? null;

    if (!$token) {
        sendJson(['authenticated' => false]);
    }

    // Check if session exists and is valid
    $session = dbQueryOne(
        'SELECT * FROM sessions WHERE token = :token AND expires_at > NOW()',
        ['token' => $token]
    );

    sendJson(['authenticated' => $session !== null]);
}

/**
 * Handle POST - Login
 */
function handleLogin(): void
{
    // Get request body
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (!$data || !isset($data['password'])) {
        sendError(400, 'Missing password');
    }

    // Check password
    if ($data['password'] !== AUTH_PASSWORD) {
        // Add small delay to prevent brute force
        usleep(random_int(100000, 500000));
        sendError(401, 'Invalid password');
    }

    // Generate session token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

    // Store session in database
    dbExecute(
        'INSERT INTO sessions (token, expires_at) VALUES (:token, :expires_at)',
        ['token' => $token, 'expires_at' => $expiresAt]
    );

    // Set session cookie
    setSessionCookie($token);

    // Clean up old sessions
    cleanupExpiredSessions();

    sendJson([
        'success' => true,
        'authenticated' => true,
    ]);
}

/**
 * Handle DELETE - Logout
 */
function handleLogout(): void
{
    $token = $_COOKIE[SESSION_COOKIE_NAME] ?? null;

    if ($token) {
        // Delete session from database
        dbExecute('DELETE FROM sessions WHERE token = :token', ['token' => $token]);

        // Clear the cookie
        clearSessionCookie();
    }

    sendJson(['success' => true, 'authenticated' => false]);
}

/**
 * Set session cookie
 */
function setSessionCookie(string $token): void
{
    setcookie(SESSION_COOKIE_NAME, $token, [
        'expires' => time() + SESSION_LIFETIME,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Clear session cookie
 */
function clearSessionCookie(): void
{
    setcookie(SESSION_COOKIE_NAME, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Remove expired sessions from database
 */
function cleanupExpiredSessions(): void
{
    dbExecute('DELETE FROM sessions WHERE expires_at < NOW()');
}

/**
 * Send JSON response
 */
function sendJson(mixed $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Send error response
 */
function sendError(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
