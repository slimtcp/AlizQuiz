/**
 * assets/js/script.js
 * ------------------------------------------------------------
 * Comportements JavaScript côté client :
 *  - ouverture/fermeture du menu mobile
 *  - mise en surbrillance de la réponse choisie dans un quiz
 *  - activation du bouton "Valider" seulement quand une réponse
 *    est sélectionnée (meilleure expérience utilisateur)
 * Aucune logique de score n'est faite ici : le calcul du score
 * est fait côté PHP (jamais confier la notation au navigateur,
 * un utilisateur pourrait trafiquer le JavaScript).
 * ------------------------------------------------------------
 */

document.addEventListener('DOMContentLoaded', function () {

    /* ---------- Toggle thème sombre/clair ---------- */
    function majIconesTheme(theme) {
        var moon = document.querySelector('.icon-moon');
        var sun  = document.querySelector('.icon-sun');
        if (moon) moon.style.display = theme === 'light' ? 'none' : '';
        if (sun)  sun.style.display  = theme === 'light' ? ''     : 'none';
    }

    // Appliquer icônes selon thème courant (déjà appliqué par le script inline du head)
    majIconesTheme(document.documentElement.getAttribute('data-theme') || 'dark');

    var themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            var actuel = document.documentElement.getAttribute('data-theme') || 'dark';
            var nouveau = actuel === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', nouveau);
            localStorage.setItem('alizquiz-theme', nouveau);
            majIconesTheme(nouveau);
        });
    }

    /* ---------- Menu mobile ---------- */
    var navToggle = document.getElementById('navToggle');
    var mainNav = document.getElementById('mainNav');

    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function () {
            var estOuvert = mainNav.classList.toggle('open');
            navToggle.classList.toggle('open', estOuvert);
            document.body.classList.toggle('nav-open', estOuvert);
            navToggle.setAttribute('aria-expanded', estOuvert ? 'true' : 'false');
        });

        // Fermer en cliquant sur un lien
        mainNav.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                mainNav.classList.remove('open');
                navToggle.classList.remove('open');
                document.body.classList.remove('nav-open');
            });
        });
    }

    /* ---------- Dropdown Quiz ---------- */
    var dropdown = document.querySelector('.nav-dropdown');
    var dropdownToggle = document.querySelector('.nav-dropdown-toggle');

    if (dropdown && dropdownToggle) {
        dropdownToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var estOuvert = dropdown.classList.toggle('open');
            dropdownToggle.setAttribute('aria-expanded', estOuvert ? 'true' : 'false');
        });

        document.addEventListener('click', function () {
            dropdown.classList.remove('open');
            dropdownToggle.setAttribute('aria-expanded', 'false');
        });
    }

    /* ---------- Sélection visuelle des réponses du quiz ---------- */
    var optionItems = document.querySelectorAll('.option-item');
    var boutonValider = document.getElementById('btnValider');

    optionItems.forEach(function (item) {
        item.addEventListener('click', function () {
            var groupName = item.querySelector('input[type="radio"]').name;

            // Retire la surbrillance des autres options du même groupe
            document.querySelectorAll('input[name="' + groupName + '"]').forEach(function (radio) {
                radio.closest('.option-item').classList.remove('selected');
            });

            item.classList.add('selected');

            if (boutonValider) {
                boutonValider.disabled = false;
            }
        });
    });

    /* ---------- Barre de progression animée à l'arrivée ---------- */
    var progressFill = document.querySelector('.progress-fill');
    if (progressFill) {
        var largeurCible = progressFill.style.width;
        progressFill.style.width = '0%';
        requestAnimationFrame(function () {
            setTimeout(function () {
                progressFill.style.width = largeurCible;
            }, 80);
        });
    }

    /* ---------- Validation simple des formulaires (feedback immédiat) ---------- */
    var formulaireInscription = document.getElementById('formInscription');
    if (formulaireInscription) {
        var champMdp = document.getElementById('mot_de_passe');
        var indiceMdp = document.getElementById('indiceMdp');

        if (champMdp && indiceMdp) {
            champMdp.addEventListener('input', function () {
                var valeur = champMdp.value;
                var estRobuste = valeur.length >= 8 && /[A-Z]/.test(valeur) && /[a-z]/.test(valeur) && /[0-9]/.test(valeur);
                indiceMdp.textContent = estRobuste
                    ? 'Mot de passe robuste ✓'
                    : 'Min. 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre';
                indiceMdp.style.color = estRobuste ? 'var(--success)' : 'var(--text-muted)';
            });
        }
    }
});
