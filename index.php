<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TubeAnalyzer — Free YouTube Channel Analytics</title>

    <meta name="description" content="Analyze any YouTube channel for free. Get subscriber counts, view trends, engagement rates, and video performance — delivered as an interactive report to your inbox. No account needed.">
    <meta name="keywords" content="youtube analytics, youtube channel analyzer, free youtube stats, channel analytics, youtube engagement rate, subscriber tracker, youtube insights tool">
    <meta name="author" content="TubeAnalyzer">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://yessherlock.com">
    <meta name="theme-color" content="#111111">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📊</text></svg>">

    <meta property="og:title" content="TubeAnalyzer — Free YouTube Channel Analytics">
    <meta property="og:description" content="Get instant insights on any YouTube channel — subscribers, views, engagement rates, and growth trends, delivered to your inbox.">
    <meta property="og:url" content="https://yessherlock.com">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="TubeAnalyzer">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="TubeAnalyzer — Free YouTube Channel Analytics">
    <meta name="twitter:description" content="Analyze any YouTube channel for free. Interactive reports delivered to your inbox.">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebApplication",
      "name": "TubeAnalyzer",
      "description": "Free tool to analyze YouTube channel statistics including subscribers, views, engagement rates, and growth trends",
      "url": "https://yessherlock.com",
      "applicationCategory": "AnalyticsApplication",
      "operatingSystem": "Web",
      "offers": { "@type": "Offer", "price": "0", "priceCurrency": "USD" },
      "featureList": [
        "Subscriber count tracking",
        "View trend analysis",
        "Engagement rate calculation",
        "Video performance metrics",
        "Interactive HTML reports delivered by email"
      ]
    }
    </script>

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "How do I analyze a YouTube channel?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Enter any YouTube channel name and your email address. TubeAnalyzer fetches the channel's public statistics and sends you an interactive analytics report within a few minutes."
          }
        },
        {
          "@type": "Question",
          "name": "Is TubeAnalyzer free?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Yes, completely free. No account or credit card required — just enter a channel name and your email."
          }
        },
        {
          "@type": "Question",
          "name": "What YouTube metrics does TubeAnalyzer track?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "TubeAnalyzer tracks subscriber counts, total views, individual video performance, engagement rates, upload frequency, and growth trends over time."
          }
        }
      ]
    }
    </script>

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
            --max-w:          1100px;
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

        /* ── Nav ── */
        .nav {
            background: var(--white);
            border-bottom: 1px solid var(--border);
        }

        .nav-inner {
            max-width: var(--max-w);
            margin: 0 auto;
            padding: 0 48px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-wordmark {
            font-size: 0.9375rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            color: var(--black);
        }

        .btn-nav {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--black);
            background: none;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 7px 16px;
            cursor: pointer;
            transition: border-color 0.15s;
            min-height: 36px;
        }

        .btn-nav:hover        { border-color: var(--black); }
        .btn-nav:focus-visible { outline: 2px solid var(--black); outline-offset: 2px; }

        /* ── Main ── */
        main {
            flex: 1;
            max-width: var(--max-w);
            margin: 0 auto;
            padding: 80px 48px 96px;
            width: 100%;
        }

        /* ── Hero ── */
        .hero {
            max-width: 600px;
            margin-bottom: 48px;
        }

        .eyebrow {
            font-size: 0.8125rem;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 20px;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 600;
            line-height: 1.15;
            letter-spacing: -0.025em;
            margin-bottom: 20px;
        }

        .subtitle {
            font-size: 1.0625rem;
            color: var(--muted);
            max-width: 50ch;
            line-height: 1.65;
        }

        /* ── Form section ── */
        .form-section {
            max-width: 480px;
        }

        /* ── Example chips ── */
        .examples {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        .examples-label {
            font-size: 0.8125rem;
            color: var(--muted);
        }

        .chip {
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--black);
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 4px 12px;
            cursor: pointer;
            transition: border-color 0.12s;
            line-height: 1.6;
        }

        .chip:hover        { border-color: var(--black); }
        .chip:focus-visible { outline: 2px solid var(--black); outline-offset: 2px; }

        /* ── Form ── */
        .form-group { margin-bottom: 14px; }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 10px 12px;
            font-size: 0.9375rem;
            color: var(--black);
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            transition: border-color 0.15s;
            -webkit-appearance: none;
        }

        input:focus        { outline: none; border-color: var(--black); }
        input::placeholder { color: #9ca3af; }

        .btn-primary {
            display: block;
            width: 100%;
            padding: 11px 16px;
            margin-top: 20px;
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--white);
            background: var(--black);
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: opacity 0.15s;
            min-height: 44px;
        }

        .btn-primary:hover:not(:disabled)  { opacity: 0.82; }
        .btn-primary:focus-visible         { outline: 2px solid var(--black); outline-offset: 2px; }
        .btn-primary:disabled              { opacity: 0.45; cursor: not-allowed; }

        .form-hint {
            margin-top: 12px;
            font-size: 0.8125rem;
            color: var(--muted);
            text-align: center;
        }

        /* ── Spinner ── */
        .spinner {
            display: none;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
            font-size: 0.875rem;
            color: var(--muted);
        }

        .spinner.active { display: flex; }

        .spinner-ring {
            width: 16px;
            height: 16px;
            border: 2px solid var(--border);
            border-top-color: var(--black);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            flex-shrink: 0;
        }

        /* ── Result ── */
        .result {
            display: none;
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: var(--radius);
            font-size: 0.9375rem;
            line-height: 1.5;
        }

        .result.success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
        }

        .result.error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
        }

        /* ── Footer ── */
        footer {
            border-top: 1px solid var(--border);
        }

        .footer-inner {
            max-width: var(--max-w);
            margin: 0 auto;
            padding: 20px 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8125rem;
            color: var(--muted);
        }

        .footer-inner a {
            color: var(--muted);
            text-decoration: none;
        }

        .footer-inner a:hover { color: var(--black); }

        /* ── Modal ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 100;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .modal-overlay.open { display: flex; }

        .modal {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 32px;
            width: 100%;
            max-width: 420px;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--muted);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            line-height: 1;
            transition: color 0.15s;
            min-width: 44px;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover         { color: var(--black); }
        .modal-close:focus-visible  { outline: 2px solid var(--black); outline-offset: 2px; }

        .modal h2 {
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            margin-bottom: 4px;
        }

        .modal-sub {
            font-size: 0.875rem;
            color: var(--muted);
            margin-bottom: 24px;
        }

        .modal-result {
            display: none;
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: var(--radius);
            font-size: 0.875rem;
        }

        .modal-result.success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
        }

        .modal-result.error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
        }

        .modal-footer {
            margin-top: 16px;
            text-align: center;
            font-size: 0.8125rem;
            color: var(--muted);
        }

        .modal-footer a {
            color: var(--black);
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        /* ── Keyframes ── */
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .nav-inner,
            .footer-inner { padding-left: 24px; padding-right: 24px; }
            main  { padding: 56px 24px 64px; }
            h1    { font-size: 1.875rem; }
            .hero { margin-bottom: 36px; }
        }
    </style>
</head>
<body>

<nav class="nav" role="banner">
    <div class="nav-inner">
        <span class="nav-wordmark">TubeAnalyzer</span>
        <button class="btn-nav" id="openSignUp">Sign up</button>
    </div>
</nav>

<main>
    <div class="hero">
        <p class="eyebrow">Free YouTube analytics</p>
        <h1>Analyze any YouTube<br>channel in seconds.</h1>
        <p class="subtitle">Subscribers, video performance, engagement rates, and growth trends — delivered as an interactive report to your inbox.</p>
    </div>

    <div class="form-section">
        <div class="examples" aria-label="Example channels">
            <span class="examples-label">Try:</span>
            <button class="chip" type="button" data-channel="MrBeast">MrBeast</button>
            <button class="chip" type="button" data-channel="MKBHD">MKBHD</button>
            <button class="chip" type="button" data-channel="Veritasium">Veritasium</button>
            <button class="chip" type="button" data-channel="Kurzgesagt">Kurzgesagt</button>
        </div>

        <form id="analyzeForm" method="POST" action="analyze.php" novalidate>
            <div class="form-group">
                <label for="channel">Channel name</label>
                <input type="text" id="channel" name="channel" placeholder="e.g. MrBeast" required autocomplete="off">
            </div>
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" required>
            </div>
            <button type="submit" class="btn-primary" id="submitBtn">Analyze channel</button>
        </form>

        <p class="form-hint">Free &middot; No account needed &middot; Report arrives in a few minutes</p>

        <div class="spinner" id="spinner">
            <div class="spinner-ring"></div>
            <span>Queuing your analysis&hellip;</span>
        </div>

        <div class="result" id="result" role="status"></div>
    </div>
</main>

<footer>
    <div class="footer-inner">
        <span>&copy; 2026 TubeAnalyzer</span>
        <a href="mailto:support@yessherlock.com">support@yessherlock.com</a>
    </div>
</footer>

<!-- Sign up modal -->
<div class="modal-overlay" id="signUpModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal">
        <button class="modal-close" id="closeSignUp" aria-label="Close">&times;</button>
        <h2 id="modalTitle">Create an account</h2>
        <p class="modal-sub">Free — takes under a minute.</p>

        <form id="registerForm" novalidate>
            <div class="form-group">
                <label for="reg-name">Full name</label>
                <input type="text" id="reg-name" name="name" placeholder="Jane Smith" required>
            </div>
            <div class="form-group">
                <label for="reg-email">Email address</label>
                <input type="email" id="reg-email" name="email" placeholder="jane@example.com" required>
            </div>
            <div class="form-group">
                <label for="reg-phone">Phone <span style="color:#9ca3af;font-weight:400">(optional)</span></label>
                <input type="tel" id="reg-phone" name="phone" placeholder="+27 81 234 5678">
            </div>
            <div class="form-group">
                <label for="reg-password">Password</label>
                <input type="password" id="reg-password" name="password" placeholder="Min. 8 characters" required>
            </div>
            <div class="form-group">
                <label for="reg-confirm">Confirm password</label>
                <input type="password" id="reg-confirm" name="confirm_password" placeholder="Repeat password" required>
            </div>
            <button type="submit" class="btn-primary" id="regSubmitBtn">Create account</button>
        </form>

        <div class="modal-result" id="modalResult"></div>
        <p class="modal-footer">Already have an account? <a href="#" id="goToLogin">Log in</a></p>
    </div>
</div>

<script>
    // ── Example channel chips ──
    document.querySelectorAll('.chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            document.getElementById('channel').value = this.dataset.channel;
            document.getElementById('channel').focus();
        });
    });

    // ── Analyze form ──
    document.getElementById('analyzeForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('submitBtn');
        const spinner   = document.getElementById('spinner');
        const result    = document.getElementById('result');

        submitBtn.disabled    = true;
        submitBtn.textContent = 'Analyzing…';
        spinner.classList.add('active');
        result.style.display  = 'none';

        try {
            const response = await fetch('analyze.php', { method: 'POST', body: new FormData(e.target) });
            const data     = await response.json();
            result.style.display = 'block';
            if (data.success) {
                result.className = 'result success';
                result.textContent = data.message;
            } else {
                result.className   = 'result error';
                result.textContent = data.message;
            }
        } catch {
            result.style.display = 'block';
            result.className     = 'result error';
            result.textContent   = 'Something went wrong. Please try again.';
        } finally {
            submitBtn.disabled    = false;
            submitBtn.textContent = 'Analyze channel';
            spinner.classList.remove('active');
        }
    });

    // ── Sign up modal ──
    const modal     = document.getElementById('signUpModal');
    const openBtn   = document.getElementById('openSignUp');
    const closeBtn  = document.getElementById('closeSignUp');
    const regForm   = document.getElementById('registerForm');
    const regSubmit = document.getElementById('regSubmitBtn');
    const regResult = document.getElementById('modalResult');

    function openModal()  { modal.classList.add('open'); document.getElementById('reg-name').focus(); }
    function closeModal() {
        modal.classList.remove('open');
        regForm.reset();
        regResult.style.display = 'none';
        regResult.className     = 'modal-result';
    }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && modal.classList.contains('open')) closeModal(); });
    document.getElementById('goToLogin').addEventListener('click', function(e) { e.preventDefault(); });

    regForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        regSubmit.disabled    = true;
        regSubmit.textContent = 'Creating account…';
        regResult.style.display = 'none';

        try {
            const response = await fetch('register.php', { method: 'POST', body: new FormData(regForm) });
            const data     = await response.json();
            regResult.style.display = 'block';
            if (data.success) {
                regResult.className   = 'modal-result success';
                regResult.textContent = data.message;
                regForm.reset();
            } else {
                regResult.className   = 'modal-result error';
                regResult.textContent = data.message;
            }
        } catch {
            regResult.style.display = 'block';
            regResult.className     = 'modal-result error';
            regResult.textContent   = 'Something went wrong. Please try again.';
        } finally {
            regSubmit.disabled    = false;
            regSubmit.textContent = 'Create account';
        }
    });
</script>
</body>
</html>
