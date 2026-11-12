// motion.js - IntelliFeed+ interface behaviour (styles in theme.css).
// Loaded after each page's own script. Wraps a few page functions without
// changing what they do.
(function () {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ── Emoji -> line icons (Phosphor). Text the user typed is left alone. ──
    const ICON = {
        '❌': 'x-circle', '✅': 'check-circle', '🗑': 'trash', '🔄': 'arrows-clockwise', '🍖': 'bowl-food',
        '⏰': 'alarm', '🐱': 'cat', '🐶': 'dog', '⚠': 'warning', '💧': 'drop', '🍽': 'fork-knife',
        '📝': 'note-pencil', '📊': 'chart-bar', '☀': 'sun', '🌙': 'moon', '📹': 'video-camera',
        '💡': 'lightbulb', '🧪': 'flask', '➕': 'plus', '🕐': 'clock', '👁': 'eye', '✓': 'check',
        '🌅': 'sun-horizon', '💾': 'floppy-disk', '⚖': 'scales', '🎯': 'target', '🎨': 'palette',
        '📔': 'notebook', '🔔': 'bell', '⚡': 'lightning', '🫙': 'package', '📄': 'file-text',
        '✏': 'pencil-simple', '📋': 'clipboard-text', '🚀': 'paper-plane-tilt', '⏳': 'hourglass',
        '🔒': 'lock', '📷': 'camera', '📖': 'book-open', '⏸': 'pause', '🚱': 'drop-slash',
        '🔍': 'magnifying-glass', '📅': 'calendar', '↩': 'arrow-u-up-left', '🐾': 'paw-print',
        '⏹': 'stop', '📸': 'camera', '👀': 'eye', '👆': 'hand-pointing', '☰': 'list', '🚪': 'sign-out',
        '👋': 'user', '📡': 'wifi-high', '📭': 'tray', '📌': 'push-pin', '🏷': 'tag', '🔴': 'record',
        '📜': 'scroll', '🗄': 'database', '🔰': 'info', '📈': 'chart-line-up', '💻': 'desktop',
        '🏠': 'house', '⬆': 'arrow-up', '📤': 'upload-simple', '🧠': 'brain', '📱': 'device-mobile'
    };
    const EMOJI = /(\p{Extended_Pictographic}|[☀-➿⬀-⯿←-⇿⌀-⏿])️?/gu;
    const KEEP = /[←→↑↓]/;                         // typographic arrows stay as text
    const SKIP = 'script,style,textarea,input,.note-content,.note-title,.note-pet-name,.journal-entries [contenteditable]';

    function swapText(node) {
        const text = node.nodeValue;
        EMOJI.lastIndex = 0;
        if (!EMOJI.test(text)) return;
        const parent = node.parentElement;
        if (!parent || parent.closest(SKIP)) return;
        if (parent.tagName === 'OPTION' || parent.tagName === 'TITLE') {   // no markup allowed here
            node.nodeValue = text.replace(EMOJI, m => KEEP.test(m) ? m : '').replace(/^\s+/, '');
            return;
        }
        const frag = document.createDocumentFragment();
        let last = 0;
        text.replace(EMOJI, (m, _g, at) => {
            if (KEEP.test(m)) return m;
            frag.append(text.slice(last, at));
            const name = ICON[m.replace('️', '')];
            if (name) {
                const i = document.createElement('i');
                i.className = 'ph ph-' + name + ' ic';
                i.setAttribute('aria-hidden', 'true');
                frag.append(i);
            }
            last = at + m.length;
            return m;
        });
        frag.append(text.slice(last));
        node.replaceWith(frag);
    }
    function swapIn(root) {
        if (root.nodeType === 3) return swapText(root);
        if (root.nodeType !== 1 || root.closest(SKIP)) return;
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        const nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(swapText);
    }
    swapIn(document.body);

    // ── New panel content settles in when a menu item is chosen ──
    const panel = document.getElementById('panelContent');
    function enter() {
        if (!panel) return;
        panel.classList.remove('panel-enter');
        panel.querySelectorAll('.live-status-item, .schedule-item, .journal-note, .log-entry')
            .forEach((el, i) => el.style.setProperty('--i', Math.min(i, 10)));
        void panel.offsetWidth;
        panel.classList.add('panel-enter');
        clearTimeout(enter.t);
        enter.t = setTimeout(() => panel.classList.remove('panel-enter'), 700);
    }
    if (typeof window.selectMenuItem === 'function') {
        const original = window.selectMenuItem;
        window.selectMenuItem = function () {
            const out = original.apply(this, arguments);
            enter();
            return out;
        };
    }

    // ── Readings: tint a value briefly when it changes; mark the feeding tile ──
    const last = new WeakMap();
    function check(el) {
        const text = el.textContent.trim();
        const prev = last.get(el);
        last.set(el, text);
        if (el.id === 'feedingStatusPanel') {
            const tile = el.closest('.live-status-item');
            if (tile) tile.classList.toggle('is-live', /feed|dispens|pour/i.test(text) && !/idle|done|complete/i.test(text));
        }
        if (prev === undefined || prev === text || reduce) return;
        el.classList.add('changed');
        requestAnimationFrame(() => requestAnimationFrame(() => el.classList.remove('changed')));
    }
    document.querySelectorAll('.live-status-value').forEach(check);

    // One observer: icons for anything rendered later, and reading changes
    new MutationObserver(muts => {
        const values = new Set();
        for (const m of muts) {
            if (m.type === 'characterData') swapText(m.target);
            m.addedNodes.forEach(n => {
                swapIn(n);
                if (n.nodeType === 1 && n.querySelectorAll) n.querySelectorAll('.live-status-value').forEach(v => values.add(v));
            });
            const t = m.target.nodeType === 3 ? m.target.parentElement : m.target;
            const v = t && t.closest ? t.closest('.live-status-value') : null;
            if (v) values.add(v);
        }
        values.forEach(check);
    }).observe(document.body, { childList: true, subtree: true, characterData: true });

    // ── Theme switch: short cross-fade where the browser supports it ──
    if (typeof window.toggleTheme === 'function' && document.startViewTransition && !reduce) {
        const original = window.toggleTheme;
        window.toggleTheme = function () {
            const args = arguments, self = this;
            document.startViewTransition(() => original.apply(self, args));
        };
    }
})();
