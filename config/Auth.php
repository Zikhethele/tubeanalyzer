<?php
/**
 * Shared user-session helpers for logged-in visitors (login.php / logout.php / index.php).
 * Uses $_SESSION['user'] — kept separate from AdminAuth's $_SESSION['admin_authed']
 * so the two auth systems can't collide.
 */

function authStart(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/** Returns the logged-in user's session data, or null if not logged in. */
function authCurrentUser(): ?array {
    authStart();
    return $_SESSION['user'] ?? null;
}

/** Establishes a logged-in session for the given user row. */
function authLogin(array $user): void {
    authStart();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'                => $user['id'],
        'email'             => $user['email'],
        'name'              => $user['name'],
        'subscription_tier' => $user['subscription_tier'] ?? 'free',
    ];
}

/** Returns the logged-in user, or halts the request with a 401 JSON body. */
function authRequireUser(): array {
    $user = authCurrentUser();
    if (!$user) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please log in to use this feature.']);
        exit;
    }
    return $user;
}

function authLogout(): void {
    authStart();
    $_SESSION = [];
    session_destroy();
}
