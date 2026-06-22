/**
 * assets/js/anim.js
 * ------------------------------------------------------------
 * Animations visuelles non essentielles, en JS vanille :
 *   1. Reveal au scroll (IntersectionObserver) avec décalage
 *   2. Spotlight qui suit le curseur sur les cartes
 *   3. Count-up des valeurs numériques (profil)
 * Tout est désactivé si l'utilisateur préfère moins de mouvement.
 * Aucune logique métier ici : purement décoratif.
 * ------------------------------------------------------------
 */
(function () {
    'use strict';

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var coarse = window.matchMedia('(pointer: coarse)').matches;

    /* ══════════════════════════════════════════════════════
       1. REVEAL AU SCROLL
       ══════════════════════════════════════════════════════ */
    function initReveal() {
        if (reduce || !('IntersectionObserver' in window)) return;

        var selectors = [
            '.card', '.niveau-card', '.entr-card', '.stat-card',
            '.badge-card', '.section-head', '.history-table-wrap',
            '.certificate-frame', '.auth-card'
        ];
        var nodes = document.querySelectorAll(selectors.join(','));
        if (!nodes.length) return;

        // Décalage en cascade par groupe de parent
        var counters = new Map();
        nodes.forEach(function (el) {
            var parent = el.parentElement;
            var i = counters.get(parent) || 0;
            counters.set(parent, i + 1);
            el.style.setProperty('--reveal-delay', Math.min(i, 6) * 70 + 'ms');
            el.classList.add('reveal');
        });

        var io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var el = entry.target;
                el.classList.add('is-visible');
                obs.unobserve(el);
                // Nettoyage : on retire les classes pour rendre les
                // transitions de survol d'origine intactes.
                el.addEventListener('transitionend', function handler(e) {
                    if (e.propertyName !== 'transform') return;
                    el.classList.remove('reveal', 'is-visible');
                    el.style.removeProperty('--reveal-delay');
                    el.style.removeProperty('will-change');
                    el.removeEventListener('transitionend', handler);
                });
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        nodes.forEach(function (el) { io.observe(el); });
    }

    /* ══════════════════════════════════════════════════════
       2. SPOTLIGHT QUI SUIT LE CURSEUR
       ══════════════════════════════════════════════════════ */
    function initSpotlight() {
        if (reduce || coarse) return;
        var cards = document.querySelectorAll('.card, .stat-card');
        cards.forEach(function (card) {
            card.addEventListener('pointermove', function (e) {
                var r = card.getBoundingClientRect();
                card.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100).toFixed(1) + '%');
                card.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100).toFixed(1) + '%');
            });
        });
    }

    /* ══════════════════════════════════════════════════════
       3. COUNT-UP DES VALEURS NUMÉRIQUES
       ══════════════════════════════════════════════════════ */
    function initCountUp() {
        if (reduce || !('IntersectionObserver' in window)) return;
        var values = document.querySelectorAll('.stat-value');
        if (!values.length) return;

        var io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                animate(entry.target);
                obs.unobserve(entry.target);
            });
        }, { threshold: 0.5 });

        values.forEach(function (el) {
            var raw = el.textContent.trim();
            var m = raw.match(/^(\d+)(\s*%?)$/);   // entier, éventuellement suivi de %
            if (!m) return;                          // ex. "Débutant" → on ignore
            el.dataset.target = m[1];
            el.dataset.suffix = m[2].trim();
            el.textContent = '0' + el.dataset.suffix;
            io.observe(el);
        });

        function animate(el) {
            var target = parseInt(el.dataset.target, 10);
            var suffix = el.dataset.suffix || '';
            var dur = 1100, start = null;
            function step(ts) {
                if (!start) start = ts;
                var p = Math.min((ts - start) / dur, 1);
                var ease = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(ease * target) + suffix;
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }
    }

    function init() {
        initReveal();
        initSpotlight();
        initCountUp();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
