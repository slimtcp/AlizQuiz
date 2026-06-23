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

        var validerBtn = document.createElement('button');
        validerBtn.type = 'button';
        validerBtn.className = 'btn btn-primary stepper-valider';
        validerBtn.textContent = 'Valider';
        validerBtn.style.display = 'none';

        var skipBtn = document.createElement('button');
        skipBtn.type = 'button';
        skipBtn.className = 'btn btn-ghost stepper-skip';
        skipBtn.textContent = 'Passer cette question';

        nav.appendChild(prevBtn);
        nav.appendChild(validerBtn);
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
            refreshNav();

            if (doScroll) scrollToTop();
        }

        // Met à jour les boutons selon que la question courante a (ou non)
        // une réponse sélectionnée. Appelée au changement de carte, à la
        // sélection et à la désélection d'une réponse.
        function refreshNav() {
            var last = current === total - 1;
            var hasAnswer = cards[current].querySelector('input[type=radio]:checked');
            if (last) {
                validerBtn.style.display = 'none';
                skipBtn.style.display = 'none';
                if (submitBtn) submitBtn.style.display = hasAnswer ? '' : 'none';
            } else if (hasAnswer) {
                validerBtn.style.display = '';
                skipBtn.style.display = 'none';
                if (submitBtn) submitBtn.style.display = 'none';
            } else {
                validerBtn.style.display = 'none';
                skipBtn.style.display = '';
                if (submitBtn) submitBtn.style.display = 'none';
            }
        }

        // ── Sélection / désélection d'une réponse ───────────────────
        cards.forEach(function (card) {
            card.querySelectorAll('input[type=radio]').forEach(function (r) {
                // Re-clic sur une réponse déjà cochée → on la décoche.
                // (les boutons radio ne se décochent pas nativement, on
                // mémorise l'état au mousedown pour le détecter au click).
                var etaitCochee = false;
                r.addEventListener('mousedown', function () { etaitCochee = r.checked; });
                r.addEventListener('keydown', function (e) {
                    if (e.key === ' ' || e.key === 'Enter') etaitCochee = r.checked;
                });
                r.addEventListener('click', function () {
                    if (etaitCochee) {
                        r.checked = false;
                        var l = r.closest('.option-item');
                        if (l) l.classList.remove('option-selected');
                        etaitCochee = false;
                        refreshNav();
                    }
                });
                r.addEventListener('change', function () {
                    card.querySelectorAll('.option-item').forEach(function (o) {
                        o.classList.remove('option-selected');
                    });
                    var label = r.closest('.option-item');
                    if (label) label.classList.add('option-selected');
                    refreshNav();
                });
            });
        });

        validerBtn.addEventListener('click', function () {
            validerBtn.style.display = 'none';
            show(current + 1, true);
        });

        prevBtn.addEventListener('click', function () {
            validerBtn.style.display = 'none';
            show(current - 1, true);
        });
        skipBtn.addEventListener('click', function () {
            validerBtn.style.display = 'none';
            show(current + 1, true);
        });

        show(0, false);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStepper);
    } else {
        initStepper();
    }
})();
