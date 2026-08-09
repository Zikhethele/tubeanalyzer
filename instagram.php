<?php
require_once __DIR__ . '/config/Auth.php';
authStart();
$currentUser = authCurrentUser();
if (!$currentUser) {
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram Audit — TubeAnalyzer</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📊</text></svg>">
    <style>
        :root {
            --black:          #111;
            --muted:          #6b7280;
            --border:         #e5e7eb;
            --bg:             #fafafa;
            --white:          #fff;
            --radius:         6px;
            --success-bg:     #f0fdf4;
            --success-border: #bbf7d0;
            --success-text:   #166534;
            --error-bg:       #fef2f2;
            --error-border:   #fecaca;
            --error-text:     #991b1b;
            --warn-bg:        #fffbeb;
            --warn-border:    #fde68a;
            --warn-text:      #92400e;
            --max-w:          640px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--black);
            min-height: 100vh;
            font-size: 1rem;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
        }

        .nav { background: var(--white); border-bottom: 1px solid var(--border); }
        .nav-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 48px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-wordmark { font-size: 0.9375rem; font-weight: 600; letter-spacing: -0.01em; color: var(--black); text-decoration: none; }
        .nav-actions { display: flex; align-items: center; gap: 20px; }
        .nav-greeting { font-size: 0.875rem; color: var(--muted); }
        .nav-link {
            font-size: 0.875rem; font-weight: 500; color: var(--muted);
            background: none; border: none; padding: 7px 4px; text-decoration: none; cursor: pointer;
        }
        .nav-link:hover { color: var(--black); }
        .nav-link:focus-visible { outline: 2px solid var(--black); outline-offset: 2px; }

        main { flex: 1; padding: 64px 24px; }

        .card {
            max-width: var(--max-w);
            margin: 0 auto;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 32px;
        }

        h1 { font-size: 1.375rem; font-weight: 600; letter-spacing: -0.01em; margin-bottom: 8px; }
        .sub { font-size: 0.9375rem; color: var(--muted); margin-bottom: 24px; max-width: 60ch; }

        .form-row { display: flex; gap: 8px; align-items: flex-start; }

        label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 6px; }

        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            font-size: 0.9375rem;
            color: var(--black);
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            transition: border-color 0.15s;
        }
        input:focus        { outline: none; border-color: var(--black); }
        input::placeholder { color: #9ca3af; }

        .btn-primary {
            padding: 11px 20px;
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--white);
            background: var(--black);
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: opacity 0.15s;
            min-height: 44px;
            white-space: nowrap;
        }
        .btn-primary:hover:not(:disabled) { opacity: 0.82; }
        .btn-primary:focus-visible        { outline: 2px solid var(--black); outline-offset: 2px; }
        .btn-primary:disabled             { opacity: 0.45; cursor: not-allowed; }

        .spinner { display: none; align-items: center; gap: 12px; margin-top: 16px; font-size: 0.875rem; color: var(--muted); }
        .spinner.active { display: flex; }
        .spinner-ring {
            width: 16px; height: 16px; border: 2px solid var(--border); border-top-color: var(--black);
            border-radius: 50%; animation: spin 0.7s linear infinite; flex-shrink: 0;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .result-error {
            display: none;
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: var(--radius);
            font-size: 0.9375rem;
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
        }

        /* ── Profile result ── */
        .profile-result { display: none; margin-top: 24px; }
        .profile-head { display: flex; gap: 14px; align-items: center; }
        .profile-pic { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; background: var(--bg); flex-shrink: 0; }
        .profile-name { font-size: 1rem; font-weight: 600; }
        .profile-handle { font-size: 0.875rem; color: var(--muted); }
        .badge {
            display: inline-block; font-size: 0.75rem; font-weight: 500; padding: 2px 8px;
            border-radius: 100px; margin-left: 6px; vertical-align: middle;
        }
        .badge-verified { background: var(--success-bg); color: var(--success-text); border: 1px solid var(--success-border); }
        .badge-private  { background: var(--bg); color: var(--muted); border: 1px solid var(--border); }

        .profile-bio { margin-top: 12px; font-size: 0.875rem; color: var(--black); }

        .stat-row { display: flex; gap: 24px; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border); }
        .stat-value { font-size: 1rem; font-weight: 600; }
        .stat-label { font-size: 0.75rem; color: var(--muted); }

        .score-row {
            margin-top: 20px; padding: 14px 16px; border-radius: var(--radius);
            display: flex; align-items: center; justify-content: space-between;
        }
        .score-row.real       { background: var(--success-bg); border: 1px solid var(--success-border); }
        .score-row.suspicious { background: var(--warn-bg); border: 1px solid var(--warn-border); }
        .score-row.fake       { background: var(--error-bg); border: 1px solid var(--error-border); }
        .score-label { font-size: 0.9375rem; font-weight: 500; }
        .score-value { font-size: 1.125rem; font-weight: 700; }

        .flag-list { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 6px; }
        .flag-pill {
            font-size: 0.75rem; color: var(--muted); background: var(--bg);
            border: 1px solid var(--border); border-radius: 100px; padding: 3px 10px;
        }

        footer { border-top: 1px solid var(--border); }
        .footer-inner {
            max-width: 1100px; margin: 0 auto; padding: 20px 48px;
            display: flex; justify-content: space-between; align-items: center;
            font-size: 0.8125rem; color: var(--muted);
        }
        .footer-inner a { color: var(--muted); text-decoration: none; }
        .footer-inner a:hover { color: var(--black); }

        @media (max-width: 640px) {
            .nav-inner, .footer-inner { padding: 0 20px; }
            main { padding: 40px 16px; }
            .card { padding: 24px; }
            .form-row { flex-direction: column; }
            .btn-primary { width: 100%; }
        }
    </style>
</head>
<body>

<nav class="nav" role="banner">
    <div class="nav-inner">
        <a class="nav-wordmark" href="/">TubeAnalyzer</a>
        <div class="nav-actions">
            <span class="nav-greeting">Hi, <?= htmlspecialchars($currentUser['name'] ?: $currentUser['email'], ENT_QUOTES, 'UTF-8') ?></span>
            <a class="nav-link" href="logout.php">Log out</a>
        </div>
    </div>
</nav>

<main>
    <div class="card">
        <h1>Instagram profile audit</h1>
        <p class="sub">Check any public Instagram account for bot/fake-account signals — no login to Instagram required.</p>

        <form id="auditForm" novalidate>
            <div class="form-row">
                <div style="flex:1">
                    <label for="ig-username">Instagram username</label>
                    <input type="text" id="ig-username" name="username" placeholder="e.g. nasa" required autocomplete="off">
                </div>
                <button type="submit" class="btn-primary" id="auditSubmitBtn" style="margin-top:22px">Audit</button>
            </div>
        </form>

        <div class="spinner" id="auditSpinner">
            <div class="spinner-ring"></div>
            <span>Fetching profile…</span>
        </div>

        <div class="result-error" id="auditError"></div>

        <div class="profile-result" id="profileResult">
            <div class="profile-head">
                <img class="profile-pic" id="pf-pic" src="" alt="">
                <div>
                    <div class="profile-name">
                        <span id="pf-name"></span>
                        <span class="badge badge-verified" id="pf-verified" style="display:none">Verified</span>
                        <span class="badge badge-private" id="pf-private" style="display:none">Private</span>
                    </div>
                    <div class="profile-handle">@<span id="pf-username"></span></div>
                </div>
            </div>

            <p class="profile-bio" id="pf-bio"></p>

            <div class="stat-row">
                <div><div class="stat-value" id="pf-followers"></div><div class="stat-label">Followers</div></div>
                <div><div class="stat-value" id="pf-following"></div><div class="stat-label">Following</div></div>
                <div><div class="stat-value" id="pf-posts"></div><div class="stat-label">Posts</div></div>
            </div>

            <div class="score-row" id="pf-score-row">
                <span class="score-label" id="pf-score-label"></span>
                <span class="score-value" id="pf-score-value"></span>
            </div>
            <div class="flag-list" id="pf-flags"></div>
        </div>
    </div>
</main>

<footer>
    <div class="footer-inner">
        <span>&copy; 2026 TubeAnalyzer</span>
        <span>
            <a href="terms.php">Terms &amp; Privacy</a>
            &middot;
            <a href="mailto:support@yessherlock.com">support@yessherlock.com</a>
        </span>
    </div>
</footer>

<script>
    const form        = document.getElementById('auditForm');
    const submitBtn   = document.getElementById('auditSubmitBtn');
    const spinner     = document.getElementById('auditSpinner');
    const errorBox    = document.getElementById('auditError');
    const resultBox   = document.getElementById('profileResult');

    const fmt = n => (typeof n === 'number') ? n.toLocaleString() : '—';

    function renderProfile(p) {
        document.getElementById('pf-pic').src         = p.profile_pic_url || '';
        document.getElementById('pf-name').textContent = p.full_name || p.username;
        document.getElementById('pf-username').textContent = p.username;
        document.getElementById('pf-verified').style.display = p.is_verified ? 'inline-block' : 'none';
        document.getElementById('pf-private').style.display  = p.is_private  ? 'inline-block' : 'none';
        document.getElementById('pf-bio').textContent  = p.biography || '';
        document.getElementById('pf-followers').textContent = fmt(p.followers);
        document.getElementById('pf-following').textContent = fmt(p.following);
        document.getElementById('pf-posts').textContent     = fmt(p.posts);

        const scoreRow = document.getElementById('pf-score-row');
        scoreRow.className = 'score-row ' + (p.bot_label || 'real');
        document.getElementById('pf-score-label').textContent =
            p.bot_label === 'fake' ? 'Likely fake / bot' :
            p.bot_label === 'suspicious' ? 'Suspicious signals' : 'Looks real';
        document.getElementById('pf-score-value').textContent = fmt(p.bot_score) + '/100';

        const flagsEl = document.getElementById('pf-flags');
        flagsEl.innerHTML = '';
        (p.bot_flags || []).forEach(flag => {
            const pill = document.createElement('span');
            pill.className = 'flag-pill';
            pill.textContent = flag.replace(/_/g, ' ');
            flagsEl.appendChild(pill);
        });

        resultBox.style.display = 'block';
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        errorBox.style.display  = 'none';
        resultBox.style.display = 'none';
        submitBtn.disabled      = true;
        spinner.classList.add('active');

        try {
            const response = await fetch('instagram_audit.php', { method: 'POST', body: new FormData(form) });
            const data     = await response.json();

            if (data.success) {
                renderProfile(data.data);
            } else {
                errorBox.textContent   = data.message || 'Something went wrong. Please try again.';
                errorBox.style.display = 'block';
            }
        } catch {
            errorBox.textContent   = 'Something went wrong. Please try again.';
            errorBox.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            spinner.classList.remove('active');
        }
    });
</script>
</body>
</html>
