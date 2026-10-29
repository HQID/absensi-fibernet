if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            })
            .catch((error) => {
                console.log('ServiceWorker registration failed: ', error);
            });
    });
}

(() => {
    if (window.__fibernetLoaderInit) return;
    window.__fibernetLoaderInit = true;

    let trickleTimer = null;
    let hideBarTimer = null;
    let safetyTimer = null;
    let progress = 0;
    let activeNavUrl = null;

    function ensureLoaderDom() {
        let root = document.getElementById('fibernet-loader-root');
        if (root || !document.body) return root;

        root = document.createElement('div');
        root.id = 'fibernet-loader-root';
        root.setAttribute('aria-live', 'polite');
        root.innerHTML = `
            <div class="fibernet-top-bar-wrap">
                <div id="fibernet-top-bar" class="fibernet-top-bar"></div>
            </div>
            <div id="fibernet-top-pill" class="fibernet-top-pill" data-active="false">
                <div class="inline-flex items-center gap-2 rounded-full bg-slate-900/90 px-3.5 py-1.5 text-xs font-medium text-white shadow-lg ring-1 ring-white/15 backdrop-blur-md">
                    <svg class="h-3.5 w-3.5 animate-spin text-cyan-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span id="fibernet-top-pill-text">Memuat...</span>
                </div>
            </div>
            <div id="fibernet-overlay" class="fibernet-overlay" data-active="false" role="status">
                <div class="mx-4 flex max-w-xs flex-col items-center gap-3 rounded-2xl border border-gray-100 bg-white px-7 py-6 text-center shadow-2xl">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-cyan-50">
                        <svg class="h-6 w-6 animate-spin text-cyan-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                    <div>
                        <p id="fibernet-overlay-text" class="text-sm font-semibold text-gray-900">Memproses...</p>
                        <p class="mt-0.5 text-xs text-gray-500">Mohon tunggu sebentar</p>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(root);
        return root;
    }

    function clearTimers() {
        if (trickleTimer) clearInterval(trickleTimer);
        if (hideBarTimer) clearTimeout(hideBarTimer);
        if (safetyTimer) clearTimeout(safetyTimer);
        trickleTimer = null;
        hideBarTimer = null;
        safetyTimer = null;
    }

    function startTopBar(label = 'Memuat...') {
        ensureLoaderDom();
        clearTimers();

        const bar = document.getElementById('fibernet-top-bar');
        const pill = document.getElementById('fibernet-top-pill');
        const pillText = document.getElementById('fibernet-top-pill-text');
        if (!bar || !pill) return;

        if (pillText) pillText.textContent = label;
        progress = 18;
        bar.style.opacity = '1';
        bar.style.width = `${progress}%`;
        pill.dataset.active = 'true';

        trickleTimer = setInterval(() => {
            if (progress < 70) progress += 12;
            else if (progress < 90) progress += 3;
            else if (progress < 95) progress += 0.5;
            bar.style.width = `${Math.min(progress, 95)}%`;
        }, 250);

        safetyTimer = setTimeout(() => resetAllLoaders(false), 15000);
    }

    function finishTopBar() {
        ensureLoaderDom();
        if (trickleTimer) clearInterval(trickleTimer);
        if (safetyTimer) clearTimeout(safetyTimer);
        trickleTimer = null;
        safetyTimer = null;

        const bar = document.getElementById('fibernet-top-bar');
        const pill = document.getElementById('fibernet-top-pill');
        if (pill) pill.dataset.active = 'false';

        if (bar && bar.style.opacity === '1') {
            bar.style.width = '100%';
            hideBarTimer = setTimeout(() => {
                bar.style.opacity = '0';
                bar.style.width = '0%';
            }, 220);
        }
    }

    function startOverlay(label = 'Memproses...') {
        ensureLoaderDom();
        clearTimers();

        const overlay = document.getElementById('fibernet-overlay');
        const overlayText = document.getElementById('fibernet-overlay-text');
        if (!overlay) return;

        if (overlayText) overlayText.textContent = label;
        overlay.dataset.active = 'true';
        document.body.style.overflow = 'hidden';

        safetyTimer = setTimeout(() => resetAllLoaders(false), 20000);
    }

    function resetAllLoaders(smoothBar = false) {
        activeNavUrl = null;

        if (smoothBar) {
            finishTopBar();
        } else {
            clearTimers();
            const bar = document.getElementById('fibernet-top-bar');
            const pill = document.getElementById('fibernet-top-pill');
            if (bar) {
                bar.style.opacity = '0';
                bar.style.width = '0%';
            }
            if (pill) pill.dataset.active = 'false';
        }

        const overlay = document.getElementById('fibernet-overlay');
        if (overlay) overlay.dataset.active = 'false';
        if (document.body) document.body.style.overflow = '';

        document.querySelectorAll('form[data-submitting="true"]').forEach((form) => {
            delete form.dataset.submitting;
        });

        document.querySelectorAll('[data-locked-by-loader="true"]').forEach((btn) => {
            btn.disabled = false;
            delete btn.dataset.lockedByLoader;
        });
    }

    function resolveOverlayLabel(form) {
        if (form.dataset.loadingText) return form.dataset.loadingText;
        let pathname = '';
        try {
            pathname = new URL(form.action || window.location.href, window.location.origin).pathname;
        } catch {
            pathname = '';
        }

        if (pathname.endsWith('/login')) return 'Sedang masuk...';
        if (pathname.endsWith('/register')) return 'Mendaftarkan akun...';
        if (pathname.endsWith('/logout')) return 'Sedang keluar...';
        if (pathname.includes('password')) return 'Memproses kata sandi...';
        return form.dataset.loading === 'overlay' ? 'Memproses...' : null;
    }

    function isLivewireForm(form) {
        return Array.from(form.attributes).some((attr) => attr.name.startsWith('wire:submit'));
    }

    function parseInternalLink(link) {
        const rawHref = link.getAttribute('href') || '';
        if (!rawHref || rawHref.startsWith('#') || rawHref.startsWith('javascript:') || link.target === '_blank') {
            return null;
        }
        try {
            const url = new URL(link.href, window.location.origin);
            if (url.origin !== window.location.origin) return null;
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
                return null;
            }
            return url;
        } catch {
            return null;
        }
    }

    // 1a. Capture phase: strictly block duplicate form submissions
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form instanceof HTMLFormElement && form.dataset.submitting === 'true') {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, true);

    // 1b. Bubble phase: activate form loading UI (Overlay for Auth/Logout, TopBar for others)
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (isLivewireForm(form) || form.target === '_blank' || e.defaultPrevented) return;
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) return;

        form.dataset.submitting = 'true';

        const buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        setTimeout(() => {
            buttons.forEach((btn) => {
                if (!btn.disabled) {
                    btn.disabled = true;
                    btn.dataset.lockedByLoader = 'true';
                }
            });
        }, 0);

        const overlayLabel = resolveOverlayLabel(form);
        if (overlayLabel) {
            startOverlay(overlayLabel);
        } else {
            startTopBar('Memuat...');
        }
    });

    // 2a. Capture phase: block duplicate link clicks & repeated file downloads
    document.addEventListener('click', (e) => {
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const link = e.target instanceof Element ? e.target.closest('a[href]') : null;
        if (!link) return;

        if (link.dataset.downloading === 'true') {
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }

        const targetUrl = parseInternalLink(link);
        if (targetUrl && activeNavUrl === targetUrl.href) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, true);

    // 2b. Bubble phase: start TopBar for non-wire:navigate links & download buttons
    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const link = e.target instanceof Element ? e.target.closest('a[href]') : null;
        if (!link) return;

        if (link.hasAttribute('data-download') || link.hasAttribute('download')) {
            link.dataset.downloading = 'true';
            link.classList.add('opacity-60', 'pointer-events-none');
            startTopBar('Menyiapkan unduhan...');

            setTimeout(() => {
                delete link.dataset.downloading;
                link.classList.remove('opacity-60', 'pointer-events-none');
                finishTopBar();
            }, 4000);
            return;
        }

        const targetUrl = parseInternalLink(link);
        if (!targetUrl) return;

        activeNavUrl = targetUrl.href;
        startTopBar('Memuat halaman...');
    });

    document.addEventListener('DOMContentLoaded', () => ensureLoaderDom());
    document.addEventListener('livewire:navigate', (e) => {
        if (e.detail?.url?.href) {
            activeNavUrl = e.detail.url.href;
        }
        startTopBar('Memuat halaman...');
    });
    document.addEventListener('livewire:navigated', () => {
        ensureLoaderDom();
        resetAllLoaders(true);
    });
    window.addEventListener('pageshow', () => resetAllLoaders(false));
})();
