<?php
/**
 * includes/header.php
 * ------------------------------------------------------------
 * En-tête HTML commun, inclus en haut de chaque page.
 * La navigation s'adapte selon que l'utilisateur est connecté
 * ou non (variable $_SESSION['utilisateur_id']).
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/securite.php';
demarrerSession();
$estConnecte = !empty($_SESSION['utilisateur_id']);
$pseudoConnecte = $_SESSION['pseudo'] ?? '';
$pageActuelle = basename($_SERVER['PHP_SELF']);

// Avatar custom — tolérant si la migration avatar n'a pas été lancée
// (colonnes avatar_couleur/avatar_icone absentes) : on retombe sur
// les valeurs par défaut au lieu de planter toute la page.
if ($estConnecte && empty($_SESSION['avatar_couleur'])) {
    require_once __DIR__ . '/fonctions.php';
    try {
        $stmtAv = $pdo->prepare('SELECT avatar_couleur, avatar_icone FROM utilisateurs WHERE id = ?');
        $stmtAv->execute([$_SESSION['utilisateur_id']]);
        $av = $stmtAv->fetch() ?: [];
    } catch (Throwable $e) {
        $av = [];
    }
    $_SESSION['avatar_couleur'] = $av['avatar_couleur'] ?? '#3D7CFF';
    $_SESSION['avatar_icone']   = $av['avatar_icone']   ?? 'shield';
}
$avCouleur = $_SESSION['avatar_couleur'] ?? '#3D7CFF';
$avIcone   = $_SESSION['avatar_icone']   ?? 'shield';
$avSvgs = [
    'shield' => '<path d="M12 2L4 5.5V11C4 16.5 7.5 20.5 12 22C16.5 20.5 20 16.5 20 11V5.5L12 2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 12L11 14L15.5 9.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
    'lock'   => '<rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 11V7a4 4 0 0 1 8 0v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="16" r="1.5" fill="currentColor"/>',
    'key'    => '<circle cx="8" cy="15" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M11.5 11.5L20 3M17 6l2 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
    'eye'    => '<path d="M1 12C1 12 5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/>',
    'zap'    => '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
    'star'   => '<polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
    'code'   => '<polyline points="16,18 22,12 16,6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><polyline points="8,6 2,12 8,18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
    'wifi'   => '<path d="M1.5 8.5C5.5 4.5 18.5 4.5 22.5 8.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 12c2-2 12-2 14 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8.5 15.5c1-1 6-1 7 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="19" r="1.5" fill="currentColor"/>',
    'heart'  => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titrePage) ? nettoyer($titrePage) . ' — AlizQuiz' : 'AlizQuiz' ?></title>
    <?php $base = getenv('RAILWAY_ENVIRONMENT') ? '' : '/AlizQuiz'; ?>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css?v=29">
    <link rel="manifest" href="<?= $base ?>/manifest.json">
    <meta name="theme-color" content="#3D7CFF">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="AlizQuiz">
    <link rel="apple-touch-icon" href="<?= $base ?>/assets/icons/icon-192.png">
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('<?= $base ?>/sw.js');
        }
    </script>
    <script>
        // Appliquer le thème avant le rendu pour éviter le flash.
        // Le menu mobile et le bouton de thème sont gérés une seule
        // fois dans assets/js/script.js (éviter le double binding qui
        // annulait le toggle au clic).
        (function() {
            var t = localStorage.getItem('alizquiz-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,700;0,800;0,900;1,700&family=DM+Sans:ital,wght@0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="accueil.php" class="logo">
            <span class="logo-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L4 5.5V11C4 16.5 7.5 20.5 12 22C16.5 20.5 20 16.5 20 11V5.5L12 2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M9 12L11 14L15.5 9.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            AlizQuiz
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Ouvrir le menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <nav class="main-nav" id="mainNav">
            <a href="accueil.php" class="<?= $pageActuelle === 'accueil.php' ? 'active' : '' ?>">Accueil</a>
            <?php if ($estConnecte): ?>
                <a href="niveaux.php" class="<?= in_array($pageActuelle, ['niveaux.php','quiz_debutant.php','quiz_intermediaire.php','quiz_expert.php']) ? 'active' : '' ?>">Niveaux</a>
                <a href="entrainement.php" class="<?= in_array($pageActuelle, ['entrainement.php','entrainement_quiz.php']) ? 'active' : '' ?>">Entraînement</a>
                <a href="quiz_custom.php" class="<?= $pageActuelle === 'quiz_custom.php' ? 'active' : '' ?>">Quiz Perso</a>
                <a href="defi.php" class="<?= $pageActuelle === 'defi.php' ? 'active' : '' ?>" style="position:relative;">
                    Défi du Jour
                    <span style="position:absolute;top:-6px;right:-8px;background:#FBBF24;color:#000;font-size:0.55rem;font-weight:800;padding:1px 5px;border-radius:999px;text-transform:uppercase;letter-spacing:.04em;">NEW</span>
                </a>
                <a href="classement.php" class="<?= $pageActuelle === 'classement.php' ? 'active' : '' ?>">Classement</a>
                <a href="profil.php" class="nav-profil <?= $pageActuelle === 'profil.php' ? 'active' : '' ?>">
                    <span class="avatar-mini" style="background:<?= $avCouleur ?>22; border-color:<?= $avCouleur ?>55; color:<?= $avCouleur ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><?= $avSvgs[$avIcone] ?? $avSvgs['shield'] ?></svg>
                    </span>
                    <?= nettoyer($pseudoConnecte) ?>
                </a>
                <a href="deconnexion.php" class="btn-nav-deco" id="btnDeconnexion" onclick="event.preventDefault(); document.getElementById('modalDeconnexion').style.display='flex';">Déconnexion</a>
            <?php else: ?>
                <a href="classement.php" class="<?= $pageActuelle === 'classement.php' ? 'active' : '' ?>">Classement</a>
                <a href="connexion.php" class="<?= $pageActuelle === 'connexion.php' ? 'active' : '' ?>">Connexion</a>
                <a href="inscription.php" class="btn-nav-cta">Créer un compte</a>
            <?php endif; ?>
            <button class="theme-toggle" id="themeToggle" aria-label="Changer le thème">
                <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" style="display:none"><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </nav>
    </div>
</header>

<?php if ($estConnecte): ?>
<div id="modalDeconnexion" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.65); align-items:center; justify-content:center; backdrop-filter:blur(6px);">
    <div style="background:var(--bg-panel); border:1px solid var(--border-subtle); border-radius:var(--radius-lg); padding:40px 36px; max-width:380px; width:90%; text-align:center; position:relative; overflow:hidden; backdrop-filter:blur(20px); animation:popIn .35s cubic-bezier(.34,1.56,.64,1);">
        <div style="position:absolute;top:0;left:0;right:0;height:2px;background:var(--grad-blue);"></div>
        <div style="font-size:2.8rem; margin-bottom:14px;">👋</div>
        <h3 style="font-size:1.25rem; margin-bottom:10px;">Tu pars déjà ?</h3>
        <p style="color:var(--text-secondary); font-size:0.92rem; margin-bottom:28px; line-height:1.6;">Ta progression est sauvegardée. Tu pourras reprendre là où tu t'es arrêté·e.</p>
        <div style="display:flex; gap:12px;">
            <button onclick="document.getElementById('modalDeconnexion').style.display='none';" class="btn btn-ghost btn-block">Rester</button>
            <a href="deconnexion.php" class="btn btn-primary btn-block">Se déconnecter</a>
        </div>
    </div>
</div>
<style>
@keyframes popIn { from { transform:scale(.75); opacity:0; } to { transform:scale(1); opacity:1; } }
</style>
<script>
document.getElementById('modalDeconnexion').addEventListener('click', function(e){
    if (e.target === this) this.style.display = 'none';
});
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') document.getElementById('modalDeconnexion').style.display = 'none';
});
</script>
<?php endif; ?>

<main class="site-main">
