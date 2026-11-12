// motion.js - small interaction effects for IntelliFeed+ (styles live in theme.css).
// Loaded after each page's own script. Wraps a few page functions; never changes what they do.
(function () {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Ripple from the click point on buttons
    const RIPPLE = 'button, .btn-feed, .dropdown-item, .plate-option, .badge';
    document.addEventListener('pointerdown', e => {
        if (reduce) return;
        const el = e.target.closest(RIPPLE);
        if (!el || el.disabled) return;
        const r = el.getBoundingClientRect();
        const dot = document.createElement('span');
        dot.className = 'fx-ripple';
        dot.style.left = (e.clientX - r.left) + 'px';
        dot.style.top = (e.clientY - r.top) + 'px';
        if (getComputedStyle(el).position === 'static') el.style.position = 'relative';
        el.style.overflow = 'hidden';
        el.appendChild(dot);
        setTimeout(() => dot.remove(), 650);
    });

    // Dashboard: replay the entrance animation whenever a menu panel opens
    const panel = document.getElementById('panelContent');
    function enter() {
        if (!panel) return;
        panel.classList.remove('panel-enter');
        panel.querySelectorAll('.live-status-item, .schedule-item, .journal-note, .log-entry, .water-widget, .card')
            .forEach((el, i) => el.style.setProperty('--i', Math.min(i, 12)));
        void panel.offsetWidth;          // restart the CSS animation
        panel.classList.add('panel-enter');
        clearTimeout(enter.t);
        enter.t = setTimeout(() => panel.classList.remove('panel-enter'), 1400);
    }
    if (typeof window.selectMenuItem === 'function') {
        const original = window.selectMenuItem;
        window.selectMenuItem = function () {
            const out = original.apply(this, arguments);
            enter();
            return out;
        };
    }
    enter();

    // Status values: pop when the number actually changes; glow the tile while feeding
    const last = new WeakMap();
    function check(el) {
        const text = el.textContent.trim();
        const prev = last.get(el);
        last.set(el, text);
        const tile = el.closest('.live-status-item');
        if (tile && el.id === 'feedingStatusPanel') {
            tile.classList.toggle('is-live', /feed|dispens|pour/i.test(text) && !/idle|done|complete/i.test(text));
        }
        if (prev === undefined || prev === text || reduce) return;
        el.classList.remove('value-pop');
        void el.offsetWidth;
        el.classList.add('value-pop');
    }
    const host = document.getElementById('mainContent');
    if (host) {
        host.querySelectorAll('.live-status-value').forEach(check);
        new MutationObserver(muts => {
            const seen = new Set();
            muts.forEach(m => {
                const node = m.target.nodeType === 3 ? m.target.parentElement : m.target;
                const v = node && node.closest ? node.closest('.live-status-value') : null;
                if (v) seen.add(v);
                if (m.type === 'childList') m.addedNodes.forEach(n => {
                    if (n.querySelectorAll) n.querySelectorAll('.live-status-value').forEach(x => seen.add(x));
                });
            });
            seen.forEach(check);
        }).observe(host, { childList: true, subtree: true, characterData: true });
    }

    // Theme switch: circle grows out from the toggle button (browsers with View Transitions)
    if (typeof window.toggleTheme === 'function' && document.startViewTransition && !reduce) {
        const original = window.toggleTheme;
        window.toggleTheme = function () {
            const btn = document.getElementById('themeToggle');
            const r = btn ? btn.getBoundingClientRect() : { left: innerWidth / 2, top: 0, width: 0, height: 0 };
            const x = r.left + r.width / 2, y = r.top + r.height / 2;
            const end = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
            const args = arguments, self = this;
            document.startViewTransition(() => original.apply(self, args)).ready.then(() => {
                document.documentElement.animate(
                    { clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${end}px at ${x}px ${y}px)`] },
                    { duration: 600, easing: 'cubic-bezier(.22,1,.36,1)', pseudoElement: '::view-transition-new(root)' }
                );
            });
        };
    }
})();
