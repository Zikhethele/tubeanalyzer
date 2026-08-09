<!-- Cookie notice: only necessary session cookie in use, see terms.php#cookies -->
<style>
    #cookie-notice {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 100;
        background: var(--white);
        border-top: 1px solid var(--border);
        padding: 16px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        opacity: 0;
        transform: translateY(8px);
        transition: opacity 150ms ease, transform 150ms ease;
    }

    #cookie-notice.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    #cookie-notice p {
        margin: 0;
        max-width: 68ch;
        font-size: 0.875rem;
        color: var(--muted);
    }

    #cookie-notice p a {
        color: var(--black);
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    #cookie-notice p a:hover { color: var(--muted); }

    #cookie-notice button {
        flex-shrink: 0;
        min-height: 44px;
        padding: 0 20px;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--white);
        background: var(--black);
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }

    #cookie-notice button:focus-visible {
        outline: 2px solid var(--black);
        outline-offset: 2px;
    }

    @media (max-width: 640px) {
        #cookie-notice {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
            padding: 16px;
        }

        #cookie-notice button { width: 100%; }
    }
</style>

<div id="cookie-notice" role="region" aria-label="Cookie notice" hidden>
    <p>
        We use one necessary cookie to keep you signed in. No tracking or analytics cookies.
        <a href="/terms.php#cookies">Learn more</a>
    </p>
    <button type="button" id="cookie-notice-dismiss">Got it</button>
</div>

<script>
    (function () {
        var STORAGE_KEY = 'cookieNoticeDismissed';
        var notice = document.getElementById('cookie-notice');
        if (!notice) return;

        if (localStorage.getItem(STORAGE_KEY) === '1') return;

        notice.hidden = false;
        requestAnimationFrame(function () {
            notice.classList.add('is-visible');
        });

        document.getElementById('cookie-notice-dismiss').addEventListener('click', function () {
            localStorage.setItem(STORAGE_KEY, '1');
            notice.classList.remove('is-visible');
            setTimeout(function () {
                notice.hidden = true;
            }, 150);
        });
    })();
</script>
