<?php
/**
 * accueil.php
 * Page d'accueil publique : présente le concept du site et les
 * 3 niveaux de quiz. Accessible sans connexion.
 */
$titrePage = 'Accueil';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div>
        <span class="hero-eyebrow"><span class="dot"></span> Plateforme d'entraînement cybersécurité</span>
        <h1>Apprenez à repérer les menaces <span class="accent">avant qu'elles ne vous atteignent.</span></h1>
        <p class="lead">AlizQuiz est une plateforme de quiz progressifs sur&nbsp;:</p>
        <p class="lead typewriter-line"><span id="typewriter-text" class="typewriter-accent"></span></p>
        <div class="hero-actions">
            <?php if (!empty($_SESSION['utilisateur_id'])): ?>
                <a href="quiz_debutant.php" class="btn btn-primary">Continuer l'entraînement</a>
                <a href="profil.php" class="btn btn-ghost">Voir mon profil</a>
            <?php else: ?>
                <a href="inscription.php" class="btn btn-primary">Commencer gratuitement</a>
                <a href="classement.php" class="btn btn-ghost">Voir le classement</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero-visual">
        <div class="hero-visual-head">
            <span>Parcours de progression</span>
            <span class="hero-visual-dots"><span></span><span></span><span></span></span>
        </div>
        <div class="hero-level-row">
            <div>
                <div class="level-name">Niveau Débutant</div>
                <div class="level-tag">15 questions · seuil 70%</div>
            </div>
            <span class="level-pill beginner">Accessible</span>
        </div>
        <div class="hero-level-row">
            <div>
                <div class="level-name">Niveau Intermédiaire</div>
                <div class="level-tag">25 questions · seuil 75%</div>
            </div>
            <span class="level-pill inter">À débloquer</span>
        </div>
        <div class="hero-level-row">
            <div>
                <div class="level-name">Niveau Expert</div>
                <div class="level-tag">40 questions · seuil 80%</div>
            </div>
            <span class="level-pill expert">Verrouillé</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <h2>Trois niveaux, une progression logique</h2>
        <p>Chaque niveau débloque le suivant uniquement si le seuil de réussite est atteint — exactement comme une vraie montée en compétence.</p>
    </div>
    <div class="grid-3">
        <div class="card card-level">
            <div class="card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 3L4 6.5V11C4 16 7.5 19.5 12 21C16.5 19.5 20 16 20 11V6.5L12 3Z" stroke="currentColor" stroke-width="1.6"/></svg>
            </div>
            <h3>Débutant</h3>
            <p>Mots de passe, virus, mises à jour, emails suspects, HTTPS. Les bases indispensables pour tout utilisateur.</p>
            <div class="meta"><span>15 questions</span><span>Seuil : 70%</span></div>
        </div>
        <div class="card card-level">
            <div class="card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M4 12H20M4 6H20M4 18H14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            </div>
            <h3>Intermédiaire</h3>
            <p>Phishing, ransomware, double authentification, réseaux, protection des données personnelles.</p>
            <div class="meta"><span>25 questions</span><span>Seuil : 75%</span></div>
        </div>
        <div class="card card-level">
            <div class="card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 2L20 6V12C20 17 16.5 20.5 12 22C7.5 20.5 4 17 4 12V6L12 2Z" stroke="currentColor" stroke-width="1.6"/><path d="M9 12L11 14L15 9.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            </div>
            <h3>Expert</h3>
            <p>Pare-feu, VPN, chiffrement, injection SQL, force brute, sécurité réseau. Un certificat final récompense la réussite.</p>
            <div class="meta"><span>40 questions</span><span>Seuil : 80%</span></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <h2>Comment ça marche</h2>
        <p>Un parcours simple, pensé pour progresser réellement.</p>
    </div>
    <div class="grid-3">
        <div class="card">
            <div class="card-icon">1</div>
            <h3>Créez votre compte</h3>
            <p>Inscription rapide avec pseudo, email et mot de passe sécurisé (hashé, jamais stocké en clair).</p>
        </div>
        <div class="card">
            <div class="card-icon">2</div>
            <h3>Répondez aux quiz</h3>
            <p>Progressez niveau par niveau. Votre score est calculé automatiquement et enregistré dans votre historique.</p>
        </div>
        <div class="card">
            <div class="card-icon">3</div>
            <h3>Suivez vos progrès</h3>
            <p>Tableau de bord personnel, classement général et certificat virtuel à la fin du parcours Expert.</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
