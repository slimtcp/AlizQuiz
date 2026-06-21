<?php
/**
 * niveaux.php
 * Page de sélection du niveau — regroupe les 3 quiz en un seul endroit.
 */
$titrePage = 'Choisir son niveau';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/fonctions.php';
exigerConnexion();

$userId = (int) $_SESSION['utilisateur_id'];

// Niveau actuel de l'utilisateur (détermine ce qui est débloqué)
$stmt = $pdo->prepare('SELECT niveau_actuel FROM utilisateurs WHERE id = ?');
$stmt->execute([$userId]);
$niveauActuel = $stmt->fetchColumn();

// Meilleurs scores
$scores = [];
foreach (['debutant', 'intermediaire', 'expert'] as $n) {
    $stmt = $pdo->prepare('SELECT MAX(pourcentage) FROM resultats WHERE utilisateur_id = ? AND niveau = ?');
    $stmt->execute([$userId, $n]);
    $scores[$n] = $stmt->fetchColumn();
}

$accessible = [
    'debutant'      => niveauAccessible('debutant',      $niveauActuel),
    'intermediaire' => niveauAccessible('intermediaire',  $niveauActuel),
    'expert'        => niveauAccessible('expert',         $niveauActuel),
];
?>

<div class="niveaux-wrapper">

    <div class="niveaux-header">
        <span class="hero-eyebrow"><span class="dot"></span> Parcours cybersécurité</span>
        <h1>Choisissez votre niveau</h1>
        <p>Trois niveaux progressifs. Chaque quiz débloque le suivant si le seuil est atteint.</p>
    </div>

    <div class="niveaux-grid">

        <!-- Débutant (toujours accessible) -->
        <a href="quiz_debutant.php" class="niveau-card niveau-debut">
            <div class="niveau-card-body">
                <div class="niveau-card-top">
                    <div class="niveau-icon debut">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                            <path d="M12 3L4 6.5V11C4 16 7.5 19.5 12 21C16.5 19.5 20 16 20 11V6.5L12 3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span class="niveau-pill debut">Débutant</span>
                </div>
                <h2>Les fondamentaux</h2>
                <p>Mots de passe, virus, mises à jour, emails suspects, HTTPS. Les réflexes essentiels pour tout utilisateur.</p>
            </div>
            <div class="niveau-divider"></div>
            <div class="niveau-card-foot">
                <div class="niveau-stats">
                    <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>10 questions</span>
                    <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 2L4 5.5V11C4 16.5 7.5 20.5 12 22C16.5 20.5 20 16.5 20 11V5.5L12 2Z" stroke="currentColor" stroke-width="1.6"/><path d="M9 12L11 14L15.5 9.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>Seuil : 70 %</span>
                </div>
                <?php if ($scores['debutant'] !== null): ?>
                <div class="niveau-score">
                    <span>Meilleur score</span>
                    <strong class="debut"><?= number_format((float)$scores['debutant'], 0) ?> %</strong>
                </div>
                <?php endif; ?>
                <div class="niveau-cta">Commencer <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 12H19M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            </div>
        </a>

        <!-- Intermédiaire -->
        <?php if ($accessible['intermediaire']): ?>
        <a href="quiz_intermediaire.php" class="niveau-card niveau-inter">
        <?php else: ?>
        <div class="niveau-card niveau-inter niveau-locked">
        <?php endif; ?>
            <div class="niveau-card-body">
                <div class="niveau-card-top">
                    <div class="niveau-icon inter">
                        <?php if (!$accessible['intermediaire']): ?>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 11V7a4 4 0 0 1 8 0v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <?php else: ?>
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M4 12H20M4 6H20M4 18H14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <?php endif; ?>
                    </div>
                    <span class="niveau-pill inter"><?= $accessible['intermediaire'] ? 'Intermédiaire' : 'Verrouillé' ?></span>
                </div>
                <h2>Montez en compétence</h2>
                <p>Phishing avancé, ransomware, double authentification, sécurité réseau, protection des données personnelles.</p>
                <?php if (!$accessible['intermediaire']): ?>
                <p class="niveau-unlock-hint">Obtenez au moins <strong>70 %</strong> au niveau Débutant pour débloquer.</p>
                <?php endif; ?>
            </div>
            <div class="niveau-divider"></div>
            <div class="niveau-card-foot">
                <div class="niveau-stats">
                    <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>15 questions</span>
                    <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 2L4 5.5V11C4 16.5 7.5 20.5 12 22C16.5 20.5 20 16.5 20 11V5.5L12 2Z" stroke="currentColor" stroke-width="1.6"/><path d="M9 12L11 14L15.5 9.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>Seuil : 75 %</span>
                </div>
                <?php if ($scores['intermediaire'] !== null): ?>
                <div class="niveau-score">
                    <span>Meilleur score</span>
                    <strong class="inter"><?= number_format((float)$scores['intermediaire'], 0) ?> %</strong>
                </div>
                <?php endif; ?>
                <?php if ($accessible['intermediaire']): ?>
                <div class="niveau-cta">Commencer <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 12H19M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                <?php else: ?>
                <div class="niveau-cta niveau-cta-locked">Niveau verrouillé <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 11V7a4 4 0 0 1 8 0v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div>
                <?php endif; ?>
            </div>
        <?php if ($accessible['intermediaire']): ?></a><?php else: ?></div><?php endif; ?>

        <!-- Expert -->
        <?php if ($accessible['expert']): ?>
        <a href="quiz_expert.php" class="niveau-card niveau-expert">
        <?php else: ?>
        <div class="niveau-card niveau-expert niveau-locked">
        <?php endif; ?>
            <div class="niveau-card-body">
                <div class="niveau-card-top">
                    <div class="niveau-icon expert">
                        <?php if (!$accessible['expert']): ?>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 11V7a4 4 0 0 1 8 0v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <?php else: ?>
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M12 2L20 6V12C20 17 16.5 20.5 12 22C7.5 20.5 4 17 4 12V6L12 2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 12L11 14L15 9.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <?php endif; ?>
                    </div>
                    <span class="niveau-pill expert"><?= $accessible['expert'] ? 'Expert' : 'Verrouillé' ?></span>
                </div>
                <h2>Maîtrisez les menaces</h2>
                <p>Pare-feu, VPN, chiffrement, injection SQL, attaques par force brute. Décrochez le certificat final.</p>
                <?php if (!$accessible['expert']): ?>
                <p class="niveau-unlock-hint">Obtenez au moins <strong>75 %</strong> au niveau Intermédiaire pour débloquer.</p>
                <?php endif; ?>
            </div>
            <div class="niveau-divider"></div>
            <div class="niveau-card-foot">
                <div class="niveau-stats">
                    <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>20 questions</span>
                    <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M17 3H7C5.9 3 5 3.9 5 5v14l7-3 7 3V5c0-1.1-.9-2-2-2z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>Certificat à la clé</span>
                </div>
                <?php if ($scores['expert'] !== null): ?>
                <div class="niveau-score">
                    <span>Meilleur score</span>
                    <strong class="expert"><?= number_format((float)$scores['expert'], 0) ?> %</strong>
                </div>
                <?php endif; ?>
                <?php if ($accessible['expert']): ?>
                <div class="niveau-cta">Commencer <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 12H19M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                <?php else: ?>
                <div class="niveau-cta niveau-cta-locked">Niveau verrouillé <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 11V7a4 4 0 0 1 8 0v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div>
                <?php endif; ?>
            </div>
        <?php if ($accessible['expert']): ?></a><?php else: ?></div><?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
