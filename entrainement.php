<?php
/**
 * entrainement.php
 * Page de sélection du thème d'entraînement.
 * Accessible à tous les niveaux, sans blocage.
 */
$titrePage = 'Entraînement';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/fonctions.php';
exigerConnexion();

$userId = (int) $_SESSION['utilisateur_id'];

// Meilleurs scores par thème
$themes = ['passwords', 'phishing', 'malwares', 'reseaux', 'donnees', 'chiffrement'];
$meilleurs = [];
foreach ($themes as $t) {
    $stmt = $pdo->prepare('SELECT MAX(pourcentage) FROM entrainement_resultats WHERE utilisateur_id = ? AND theme = ?');
    $stmt->execute([$userId, $t]);
    $meilleurs[$t] = $stmt->fetchColumn();
}

$infosThemes = [
    'passwords'   => ['label' => 'Mots de passe',      'desc' => 'Créer et gérer des mots de passe robustes.',        'color' => 'success', 'questions' => 4],
    'phishing'    => ['label' => 'Phishing',            'desc' => 'Reconnaître et déjouer les tentatives d\'hameçonnage.', 'color' => 'blue',    'questions' => 4],
    'malwares'    => ['label' => 'Malwares',            'desc' => 'Comprendre les logiciels malveillants et s\'en protéger.', 'color' => 'error',   'questions' => 4],
    'reseaux'     => ['label' => 'Réseaux',             'desc' => 'Sécuriser ses connexions Wi-Fi et comprendre les VPN.', 'color' => 'yellow',  'questions' => 4],
    'donnees'     => ['label' => 'Données personnelles','desc' => 'Connaître ses droits et protéger ses données (RGPD).', 'color' => 'purple',  'questions' => 4],
    'chiffrement' => ['label' => 'Chiffrement',         'desc' => 'Comprendre les bases du chiffrement et de HTTPS.',   'color' => 'teal',    'questions' => 4],
];
?>

<div class="entr-wrapper">

    <div class="niveaux-header">
        <span class="hero-eyebrow"><span class="dot" style="background:var(--success)"></span> Libre accès · Tous niveaux</span>
        <h1>Entraînement</h1>
        <p>Six thèmes pour renforcer vos réflexes. Questions interactives, images et explications à chaque réponse.</p>
    </div>

    <div class="entr-grid">
        <?php foreach ($infosThemes as $slug => $info): ?>
        <?php $score = $meilleurs[$slug]; ?>
        <a href="entrainement_quiz.php?theme=<?= $slug ?>" class="entr-card entr-<?= $info['color'] ?>">
            <div class="entr-card-top">
                <div class="entr-icon entr-icon-<?= $info['color'] ?>">
                    <?php if ($slug === 'passwords'): ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 11V7a4 4 0 0 1 8 0v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="12" cy="16" r="1.5" fill="currentColor"/></svg>
                    <?php elseif ($slug === 'phishing'): ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M3 5h18v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5z" stroke="currentColor" stroke-width="1.7"/><path d="M3 5l9 8 9-8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M15 13l3 3M18 13l-3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <?php elseif ($slug === 'malwares'): ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.7"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8 5.5L6 3M16 5.5l2-2M12 4V2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <?php elseif ($slug === 'reseaux'): ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M1.5 8.5C5.5 4.5 18.5 4.5 22.5 8.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M5 12c2-2 12-2 14 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M8.5 15.5c1-1 6-1 7 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="12" cy="19" r="1.5" fill="currentColor"/></svg>
                    <?php elseif ($slug === 'donnees'): ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><ellipse cx="12" cy="7" rx="8" ry="3.5" stroke="currentColor" stroke-width="1.7"/><path d="M4 7v5c0 1.93 3.58 3.5 8 3.5s8-1.57 8-3.5V7" stroke="currentColor" stroke-width="1.7"/><path d="M4 12v5c0 1.93 3.58 3.5 8 3.5s8-1.57 8-3.5v-5" stroke="currentColor" stroke-width="1.7"/></svg>
                    <?php else: ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M12 15v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <?php endif; ?>
                </div>
                <?php if ($score !== null): ?>
                <span class="entr-best-badge"><?= number_format((float)$score, 0) ?> %</span>
                <?php else: ?>
                <span class="entr-new-badge">Nouveau</span>
                <?php endif; ?>
            </div>
            <h2><?= $info['label'] ?></h2>
            <p><?= $info['desc'] ?></p>
            <div class="entr-card-foot">
                <span class="entr-meta"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg><?= $info['questions'] ?> questions</span>
                <span class="entr-meta entr-meta-right">Commencer →</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
