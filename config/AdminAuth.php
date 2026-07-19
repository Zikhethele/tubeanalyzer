<?php
/**
 * Shared password gate for admin.php / admin_prospects.php.
 * Not web-accessible on its own — always required by another script,
 * which must call adminRequireAuth() before printing any page output.
 */

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function adminSelfUrl(): string {
    return strtok($_SERVER['REQUEST_URI'], '?');
}

/**
 * Ensures a valid admin session exists. If not, renders the login form
 * (or processes a login/logout attempt) and exits — callers only continue
 * past this call once genuinely authenticated.
 */
function adminRequireAuth(): void {
    session_start();

    if (isset($_GET['logout'])) {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . adminSelfUrl());
        exit;
    }

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && empty($_SESSION['admin_authed'])) {
        $submitted = (string) $_POST['password'];
        $expected = (string) Config::ADMIN_PASSWORD;

        // Throttle guesses regardless of outcome so timing can't reveal validity,
        // and cap attempts per session to blunt brute-forcing.
        $_SESSION['admin_attempts'] = ($_SESSION['admin_attempts'] ?? 0) + 1;
        usleep(300000);

        if ($_SESSION['admin_attempts'] > 20) {
            $error = 'Too many attempts. Try again later.';
        } elseif ($expected !== '' && hash_equals($expected, $submitted)) {
            session_regenerate_id(true);
            $_SESSION['admin_authed'] = true;
            unset($_SESSION['admin_attempts']);
            header('Location: ' . adminSelfUrl());
            exit;
        } else {
            $error = 'Incorrect password.';
        }
    }

    if (empty($_SESSION['admin_authed'])) {
        adminRenderLogin($error);
        exit;
    }
}

function adminRenderLogin(string $error): void {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — TubeAnalyzer</title>
<meta name="robots" content="noindex, nofollow">
<style>
    :root { --black: #111; --muted: #6b7280; --border: #e5e7eb; --bg: #fafafa; --white: #fff; --error: #b91c1c; --radius: 6px; }
    * { box-sizing: border-box; }
    body {
        margin: 0; background: var(--bg); color: var(--black);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
    }
    .card { max-width: 360px; width: 100%; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 32px; }
    .card h1 { font-size: 18px; margin: 0 0 20px; }
    .field { margin-bottom: 16px; }
    label { display: block; font-size: 13px; color: var(--muted); margin-bottom: 6px; }
    input[type="password"] {
        width: 100%; font-size: 15px; padding: 10px 12px; border: 1px solid var(--border);
        border-radius: var(--radius); background: var(--white); color: var(--black);
    }
    input[type="password"]:focus-visible { outline: 2px solid var(--black); outline-offset: 1px; }
    button {
        width: 100%; font-size: 14px; font-weight: 500; padding: 10px 16px; border: 1px solid var(--black);
        border-radius: var(--radius); background: var(--black); color: var(--white); cursor: pointer;
    }
    button:hover { background: #2a2a2a; }
    button:focus-visible { outline: 2px solid var(--black); outline-offset: 2px; }
    .error { font-size: 13px; color: var(--error); margin: 0 0 16px; }
</style>
</head>
<body>
    <div class="card">
        <h1>Admin</h1>
        <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
        <form method="post" action="<?= e(adminSelfUrl()) ?>">
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autofocus required>
            </div>
            <button type="submit">Sign in</button>
        </form>
    </div>
</body>
</html>
<?php
}

/** Shared top nav for authenticated admin pages. $active is 'analyses' or 'prospects'. */
function adminNav(string $active): void {
    ?>
    <div class="top">
        <div class="admin-links">
            <a href="/admin.php" class="<?= $active === 'analyses' ? 'active' : '' ?>">Analyses</a>
            <a href="/admin_prospects.php" class="<?= $active === 'prospects' ? 'active' : '' ?>">Prospects</a>
        </div>
        <a href="?logout=1" class="signout">Sign out</a>
    </div>
    <?php
}
