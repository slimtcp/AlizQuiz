/**
 * assets/js/quiz-stepper.js
 * ------------------------------------------------------------
 * Transforme un quiz "liste défilante" en quiz "une question à la
 * fois". S'active automatiquement sur toute page contenant un
 * formulaire avec des .question-card (quiz débutant/intermédiaire/
 * expert, quiz perso, défi du jour).
 *
 * Comportement :
 *  - une seule question affichée à l'écran ;
 *  - dès qu'on choisit une réponse, passage auto à la suivante ;
 *  - bouton "Passer cette question" pour avancer sans répondre ;
 *  - bouton "Précédent" pour revenir en arrière ;
 *  - sur la dernière question, le bouton de validation apparaît.
 *
 * Le scoring reste géré côté serveur : on ne touche pas aux champs
 * du formulaire, on se contente d'afficher/masquer les cartes. Les
 * questions passées sont simplement envoyées sans réponse (le PHP
 * les compte comme "(aucune réponse)").
 * ------------------------------------------------------------
 */
(function () {
    'use strict';

    function initStepper() {
        var firstCard = document.querySelector('.question-card');
        if (!firstCard) return;

        var form = firstCard.closest('form');
        if (!form) return;

        var cards = Array.prototype.slice.call(form.querySelectorAll('.question-card'));
        if (cards.length === 0) return;

        var reduceMotion = window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Les questions peuvent désormais être passées : on retire la
        // contrainte "required" qui empêcherait la validation finale.
        form.querySelectorAll('input[type=radio]').forEach(function (r) {
            r.removeAttribute('required');
        });

        var submitBtn = form.querySelector('button[type=submit]') ||
            form.querySelector('button:not([type])');
        var progressFill = document.querySelector('.progress-fill');
        var total = cards.length;
        var current = 0;

        // ── Compteur en haut du formulaire ──────────────────────────
        var head = document.createElement('div');
        head.className = 'stepper-head';
        var count = document.createElement('span');
        count.className = 'stepper-count';
        head.appendChild(count);
        form.insertBefore(head, cards[0]);

        // ── Barre de navigation (avant le bouton de validation) ─────
        var nav = document.createElement('div');
        nav.className = 'stepper-nav';

        var prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'btn btn-ghost stepper-prev';
        prevBtn.textContent = 'Précédent';

        var skipBtn = document.createElement('button');
        skipBtn.type = 'button';
        skipBtn.className = 'btn btn-ghost stepper-skip';
        skipBtn.textContent = 'Passer cette question';

        nav.appendChild(prevBtn);
        nav.appendChild(skipBtn);

        if (submitBtn) {
            form.insertBefore(nav, submitBtn);
        } else {
            form.appendChild(nav);
        }

        function scrollToTop() {
            var anchor = document.querySelector('.quiz-wrapper') || form;
            var y = anchor.getBoundingClientRect().top + window.pageYOffset - 16;
            window.scrollTo({ top: y, behavior: reduceMotion ? 'auto' : 'smooth' });
        }

        function show(i, doScroll) {
            current = Math.max(0, Math.min(total - 1, i));
            var last = current === total - 1;

            cards.forEach(function (c, idx) {
                c.style.display = idx === current ? '' : 'none';
            });

            // Petite animation d'entrée
            if (!reduceMotion) {
                var c = cards[current];
                c.classList.remove('step-in');
                void c.offsetWidth; // force le redémarrage de l'animation
                c.classList.add('step-in');
            }

            count.textContent = 'Question ' + (current + 1) + ' / ' + total;
            if (progressFill) {
                progressFill.style.width = ((current + 1) / total * 100).toFixed(1) + '%';
            }

            prevBtn.style.visibility = current === 0 ? 'hidden' : 'visible';
            skipBtn.style.display = last ? 'none' : '';
            if (submitBtn) submitBtn.style.display = last ? '' : 'none';

            if (doScroll) scrollToTop();
        }

        // ── Avancer automatiquement quand on choisit une réponse ────
        cards.forEach(function (card, idx) {
            card.querySelectorAll('input[type=radio]').forEach(function (r) {
                r.addEventListener('change', function () {
                    // Surligne en bleu l'option choisie (repli pour les
                    // navigateurs sans :has()).
                    card.querySelectorAll('.option-item').forEach(function (o) {
                        o.classList.remove('option-selected');
                    });
                    var label = r.closest('.option-item');
                    if (label) label.classList.add('option-selected');

                    // Laisse le temps de voir la sélection avant d'avancer.
                    if (idx < total - 1) {
                        setTimeout(function () { show(idx + 1, true); }, 600);
                    } else if (submitBtn) {
                        submitBtn.style.display = '';
                    }
                });
            });
        });

        prevBtn.addEventListener('click', function () { show(current - 1, true); });
        skipBtn.addEventListener('click', function () { show(current + 1, true); });

        show(0, false);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStepper);
    } else {
        initStepper();
    }
})();
