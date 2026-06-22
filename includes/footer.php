</main>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <span class="logo-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L4 5.5V11C4 16.5 7.5 20.5 12 22C16.5 20.5 20 16.5 20 11V5.5L12 2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                </svg>
            </span>
            AlizQuiz
        </div>
        <p class="footer-note">Projet pédagogique réalisé dans le cadre du chef-d'œuvre BAC PRO CIEL — apprendre la cybersécurité par la pratique.</p>
        <p class="footer-copy">&copy; <?= date('Y') ?> AlizQuiz — Tous droits réservés.</p>
    </div>
</footer>
<?php $base = isset($base) ? $base : (getenv('RAILWAY_ENVIRONMENT') ? '' : '/AlizQuiz'); ?>
<script src="<?= $base ?>/assets/js/script.js"></script>
<script src="<?= $base ?>/assets/js/effects.js"></script>
<script src="<?= $base ?>/assets/js/anim.js"></script>
<script src="<?= $base ?>/assets/js/quiz-stepper.js"></script>
</body>
</html>
