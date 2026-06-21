/**
 * confetti.js — Moteur de confettis pur canvas, zéro dépendance.
 * Appeler : lancerConfettis()
 */
(function () {
    function lancerConfettis() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        var canvas = document.createElement('canvas');
        canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9000;';
        document.body.appendChild(canvas);

        var ctx = canvas.getContext('2d');
        canvas.width  = window.innerWidth;
        canvas.height = window.innerHeight;

        var couleurs = ['#3D7CFF','#7C3AED','#06B6D4','#34D399','#FBBF24','#F472B6','#A78BFA','#6FA1FF'];

        var particules = [];
        var nb = Math.min(180, Math.floor(window.innerWidth / 6));

        for (var i = 0; i < nb; i++) {
            particules.push({
                x:     Math.random() * canvas.width,
                y:     Math.random() * canvas.height - canvas.height,
                w:     Math.random() * 10 + 6,
                h:     Math.random() * 6 + 4,
                couleur: couleurs[Math.floor(Math.random() * couleurs.length)],
                vitesse: Math.random() * 4 + 2,
                angle:   Math.random() * Math.PI * 2,
                rotation: (Math.random() - 0.5) * 0.15,
                oscillation: Math.random() * 0.04 + 0.01,
                phase: Math.random() * Math.PI * 2,
                opacite: 1,
            });
        }

        var debut = null;
        var duree = 4500;

        function animer(ts) {
            if (!debut) debut = ts;
            var elapsed = ts - debut;
            var progression = elapsed / duree;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            particules.forEach(function (p) {
                p.y += p.vitesse;
                p.angle += p.rotation;
                p.phase += p.oscillation;
                p.x += Math.sin(p.phase) * 1.5;

                if (elapsed > duree * 0.6) {
                    p.opacite = Math.max(0, 1 - (progression - 0.6) / 0.4);
                }

                ctx.save();
                ctx.translate(p.x + p.w / 2, p.y + p.h / 2);
                ctx.rotate(p.angle);
                ctx.globalAlpha = p.opacite;
                ctx.fillStyle = p.couleur;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                ctx.restore();
            });

            if (elapsed < duree) {
                requestAnimationFrame(animer);
            } else {
                canvas.remove();
            }
        }

        requestAnimationFrame(animer);
    }

    window.lancerConfettis = lancerConfettis;
})();
