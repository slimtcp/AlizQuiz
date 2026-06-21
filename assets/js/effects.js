/* effects.js — Typewriter · Score ring · Flip reveal */

/* ══════════════════════════════════════════════════════
   1. TYPEWRITER
   ══════════════════════════════════════════════════════ */
(function () {
    var el = document.getElementById('typewriter-text');
    if (!el) return;

    var phrases = [
        'mots de passe, phishing, ransomware…',
        'du débutant jusqu\'au niveau Expert.',
        'Apprenez. Progressez. Certifiez-vous.',
        'SQL injection, pare-feu, chiffrement.',
        'Devenez incollable en cybersécurité.',
    ];

    var pi = 0, ci = 0, deleting = false, wait = 0;
    var T_TYPE = 48, T_DEL = 20, T_PAUSE_END = 1800, T_PAUSE_START = 380;

    function tick() {
        if (wait > 0) { wait -= 16; setTimeout(tick, 16); return; }
        var phrase = phrases[pi];
        if (!deleting) {
            el.textContent = phrase.slice(0, ci + 1);
            ci++;
            if (ci === phrase.length) { deleting = true; wait = T_PAUSE_END; }
            setTimeout(tick, T_TYPE + Math.random() * 20);
        } else {
            el.textContent = phrase.slice(0, ci - 1);
            ci--;
            if (ci === 0) { deleting = false; pi = (pi + 1) % phrases.length; wait = T_PAUSE_START; }
            setTimeout(tick, T_DEL);
        }
    }
    tick();
})();

/* ══════════════════════════════════════════════════════
   2. SCORE RING + COMPTEUR ANIMÉ
   ══════════════════════════════════════════════════════ */
(function () {
    var scoreEl = document.getElementById('resultScore');
    if (!scoreEl) return;

    var pct     = parseInt(scoreEl.dataset.pct,   10) || 0;
    var score   = parseInt(scoreEl.dataset.score, 10);
    var total   = parseInt(scoreEl.dataset.total, 10);
    var isRatio = !isNaN(score) && !isNaN(total);

    /* ── Créer le SVG ring ── */
    var R = 72, C = +(2 * Math.PI * R).toFixed(3);
    var ns  = 'http://www.w3.org/2000/svg';
    var svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('viewBox', '0 0 164 164');
    svg.setAttribute('aria-hidden', 'true');
    svg.style.cssText = [
        'position:absolute', 'inset:0', 'width:100%', 'height:100%',
        'pointer-events:none', 'z-index:0', 'overflow:visible'
    ].join(';');

    var gId = 'sg' + Date.now();
    var defs = document.createElementNS(ns, 'defs');
    var grad = document.createElementNS(ns, 'linearGradient');
    grad.setAttribute('id', gId);
    grad.setAttribute('x1', '0%'); grad.setAttribute('y1', '0%');
    grad.setAttribute('x2', '100%'); grad.setAttribute('y2', '100%');
    var isSuccess = scoreEl.classList.contains('success');
    [[0, '#3D7CFF'], [100, isSuccess ? '#34D399' : '#F87171']].forEach(function (s) {
        var stop = document.createElementNS(ns, 'stop');
        stop.setAttribute('offset', s[0] + '%');
        stop.setAttribute('stop-color', s[1]);
        grad.appendChild(stop);
    });
    defs.appendChild(grad);
    svg.appendChild(defs);

    function makeCircle(stroke, sw, dash, offset, extra) {
        var c = document.createElementNS(ns, 'circle');
        c.setAttribute('cx', '82'); c.setAttribute('cy', '82'); c.setAttribute('r', R);
        c.setAttribute('fill', 'none'); c.setAttribute('stroke', stroke);
        c.setAttribute('stroke-width', sw);
        if (dash)   c.setAttribute('stroke-dasharray', dash);
        if (offset !== undefined) c.setAttribute('stroke-dashoffset', offset);
        if (extra) Object.keys(extra).forEach(function (k) { c.style[k] = extra[k]; });
        return c;
    }

    svg.appendChild(makeCircle('rgba(255,255,255,0.06)', '6'));
    var arc = makeCircle('url(#' + gId + ')', '6', C, C, {
        strokeLinecap: 'round',
        transformOrigin: 'center',
        transform: 'rotate(-90deg)',
        transition: 'stroke-dashoffset 1.5s cubic-bezier(0.34,1.4,0.64,1)',
        willChange: 'stroke-dashoffset'
    });
    svg.appendChild(arc);

    /* ── Injecter le SVG + wraper le texte ── */
    scoreEl.style.cssText += ';position:relative;display:flex;align-items:center;justify-content:center;';
    var numSpan = document.createElement('span');
    numSpan.style.cssText = 'position:relative;z-index:1;';
    numSpan.textContent   = isRatio ? '0/' + total : '0%';
    scoreEl.innerHTML = '';
    scoreEl.appendChild(svg);
    scoreEl.appendChild(numSpan);

    /* ── Animation ── */
    var duration = 1500, startTs = null;
    var targetOffset = +(C - pct / 100 * C).toFixed(3);

    function step(ts) {
        if (!startTs) startTs = ts;
        var p    = Math.min((ts - startTs) / duration, 1);
        var ease = 1 - Math.pow(1 - p, 3);
        var cur  = Math.round(ease * (isRatio ? score : pct));
        numSpan.textContent = isRatio ? cur + '/' + total : cur + '%';
        arc.setAttribute('stroke-dashoffset', (C - ease * (pct / 100 * C)).toFixed(3));
        if (p < 1) requestAnimationFrame(step);
    }

    /* Légère pause avant de lancer pour que la carte soit visible */
    setTimeout(function () { requestAnimationFrame(step); }, 150);
})();

/* ══════════════════════════════════════════════════════
   3. FLIP REVEAL DES LIGNES DE RÉSULTAT
   ══════════════════════════════════════════════════════ */
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var lines = document.querySelectorAll('.result-line');
    if (!lines.length) return;

    lines.forEach(function (line, i) {
        line.style.opacity   = '0';
        line.style.transform = 'perspective(700px) rotateY(-80deg) scale(0.95)';
        line.style.transition = 'none';

        setTimeout(function () {
            line.style.transition =
                'transform 0.55s cubic-bezier(0.34,1.15,0.64,1), ' +
                'opacity 0.35s ease';
            line.style.transform = 'perspective(700px) rotateY(0deg) scale(1)';
            line.style.opacity   = '1';
        }, 180 + i * 80);
    });
})();
