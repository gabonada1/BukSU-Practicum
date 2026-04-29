@php
    $hexPattern = '/^#[0-9A-Fa-f]{6}$/';
    $branding = isset($tenant) && is_array($tenant->settings['branding'] ?? null) ? $tenant->settings['branding'] : [];
    $sanitizeHex = function ($value, string $default) use ($hexPattern): string {
        return preg_match($hexPattern, (string) $value) ? strtoupper((string) $value) : $default;
    };
    $hexToRgba = function (string $hex, float $alpha): string {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    };
    $darken = function (string $hex, float $factor): string {
        $hex = ltrim($hex, '#');
        $r = max(0, min(255, (int) round(hexdec(substr($hex, 0, 2)) * (1 - $factor))));
        $g = max(0, min(255, (int) round(hexdec(substr($hex, 2, 2)) * (1 - $factor))));
        $b = max(0, min(255, (int) round(hexdec(substr($hex, 4, 2)) * (1 - $factor))));

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    };

    $themePrimary = $sanitizeHex($branding['accent'] ?? null, '#5C6CFA');
    $themeSecondary = $sanitizeHex($branding['secondary'] ?? null, '#73C7B6');
    $themePage = $sanitizeHex($branding['page'] ?? null, '#09111F');
    $themePageAlt = $sanitizeHex($branding['page_alt'] ?? null, '#0E1830');
    $themeSurface = $sanitizeHex($branding['surface'] ?? null, '#0F172A');
    $themeSurfaceSoft = $sanitizeHex($branding['surface_soft'] ?? null, '#16213B');
    $themeSurfaceAlt = $sanitizeHex($branding['surface_alt'] ?? null, '#1B2946');
    $themeText = $sanitizeHex($branding['text'] ?? null, '#EEF4FF');
    $themeTextMuted = $sanitizeHex($branding['text_muted'] ?? null, '#9EABC5');
    $themeBorderHex = $sanitizeHex($branding['border'] ?? null, '#8094C4');
    $themePrimaryStrong = $darken($themePrimary, 0.18);
    $themeBorder = $hexToRgba($themeBorderHex, 0.18);
@endphp

@vite(['resources/css/app.css', 'resources/js/app.js'])
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    :root {
        --app-primary: {{ $themePrimary }};
        --app-primary-strong: {{ $themePrimaryStrong }};
        --app-primary-soft: {{ $hexToRgba($themePrimary, 0.12) }};
        --app-primary-glow: {{ $hexToRgba($themePrimary, 0.22) }};
        --app-secondary: {{ $themeSecondary }};
        --app-page: {{ $themePage }};
        --app-page-alt: {{ $themePageAlt }};
        --app-surface: {{ $themeSurface }};
        --app-surface-soft: {{ $themeSurfaceSoft }};
        --app-surface-alt: {{ $themeSurfaceAlt }};
        --app-panel: {{ $hexToRgba($themeSurface, 0.92) }};
        --app-panel-strong: {{ $hexToRgba($themeSurfaceSoft, 0.98) }};
        --app-panel-soft: {{ $hexToRgba($themeText, 0.04) }};
        --app-border: {{ $themeBorder }};
        --app-border-strong: {{ $hexToRgba($themePrimary, 0.24) }};
        --app-text: {{ $themeText }};
        --app-text-muted: {{ $themeTextMuted }};
        --app-text-soft: {{ $hexToRgba($themeTextMuted, 0.78) }};
        --app-success: #2fa772;
        --app-warning: #f1b85d;
        --app-danger: #d96b7a;
        --app-shadow: 0 24px 56px rgba(2, 8, 24, 0.34);
        --app-radius-xl: 28px;
        --app-radius-lg: 22px;
        --app-radius-md: 18px;
        --app-radius-sm: 14px;
        --app-shell-width: 252px;
    }
</style>

<script>
    (function () {
        try {
            if (window.localStorage.getItem('app-sidebar-collapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed-pref');
            }
        } catch (error) {
            // Ignore storage failures and allow the default expanded shell.
        }
    })();
</script>

<script>
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-generate-password]');

        if (! trigger) {
            return;
        }

        const selector = trigger.getAttribute('data-target');
        const form = trigger.closest('form');
        let target = null;

        if (selector && form) {
            target = form.querySelector(selector);
        }

        if (! target && selector) {
            target = document.querySelector(selector);
        }

        if (! target) {
            return;
        }

        const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        let password = '';

        for (let index = 0; index < 14; index += 1) {
            password += alphabet[Math.floor(Math.random() * alphabet.length)];
        }

        target.value = password;
        if (target.type === 'password') {
            target.type = 'text';
        }

        target.focus();
        target.select();
        target.dispatchEvent(new Event('input', { bubbles: true }));
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const confirmShell = document.querySelector('[data-confirm-shell]');
        const confirmMessage = document.getElementById('app-confirm-message');
        const confirmTitle = document.getElementById('app-confirm-title');
        const confirmSubmit = document.querySelector('[data-confirm-submit]');
        const confirmCancel = document.querySelector('[data-confirm-cancel]');
        let pendingForm = null;

        document.addEventListener('click', function (event) {
            const closeButton = event.target.closest('[data-toast-close]');

            if (closeButton) {
                closeButton.closest('[data-toast]')?.remove();
                return;
            }

            const detailsClose = event.target.closest('[data-details-close]');

            if (detailsClose) {
                const details = detailsClose.closest('details');

                if (details) {
                    details.removeAttribute('open');
                }

                return;
            }

            if (event.target === confirmShell) {
                closeConfirm();
            }
        });

        document.addEventListener('submit', function (event) {
            const form = event.target;

            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) {
                applySubmittingState(form);
                return;
            }

            if (form.dataset.confirmed === '1') {
                form.dataset.confirmed = '0';
                applySubmittingState(form);
                return;
            }

            event.preventDefault();
            pendingForm = form;

            if (confirmMessage) {
                confirmMessage.textContent = form.getAttribute('data-confirm-message') || 'Are you sure you want to continue?';
            }

            if (confirmTitle) {
                confirmTitle.textContent = form.getAttribute('data-confirm-title') || 'Confirm action';
            }

            if (confirmSubmit) {
                confirmSubmit.textContent = form.getAttribute('data-confirm-submit-label') || 'Confirm';
            }

            openConfirm();
        }, true);

        confirmSubmit?.addEventListener('click', function () {
            if (!pendingForm) {
                closeConfirm();
                return;
            }

            pendingForm.dataset.confirmed = '1';
            const formToSubmit = pendingForm;
            closeConfirm();
            formToSubmit.requestSubmit ? formToSubmit.requestSubmit() : formToSubmit.submit();
        });

        confirmCancel?.addEventListener('click', function () {
            closeConfirm();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && confirmShell && !confirmShell.hasAttribute('hidden')) {
                closeConfirm();
            }
        });

            window.setTimeout(function () {
            document.querySelectorAll('[data-toast]').forEach(function (toast) {
                toast.remove();
            });
        }, 4200);

        function applySubmittingState(form) {
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            const button = form.querySelector('[data-submit-button]');

            if (!button) {
                return;
            }

            button.disabled = true;
            button.textContent = form.getAttribute('data-submitting-label') || 'Submitting...';
        }

        function openConfirm() {
            if (!confirmShell) {
                return;
            }

            confirmShell.removeAttribute('hidden');
            document.body.classList.add('modal-open');
        }

        function closeConfirm() {
            pendingForm = null;

            if (!confirmShell) {
                return;
            }

            confirmShell.setAttribute('hidden', 'hidden');
            document.body.classList.remove('modal-open');
        }
    });
</script>
