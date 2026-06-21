<?php
/**
 * inscription.php
 * ------------------------------------------------------------
 * Création de compte. Sécurité mise en œuvre :
 *  - validation stricte de tous les champs côté serveur
 *    (ne JAMAIS faire confiance uniquement au JavaScript)
 *  - vérification du jeton CSRF
 *  - hashage du mot de passe avec password_hash() (bcrypt)
 *  - requêtes préparées PDO pour éviter toute injection SQL
 *  - vérification d'unicité du pseudo et de l'email
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/includes/securite.php';
require_once __DIR__ . '/includes/fonctions.php';
require_once __DIR__ . '/includes/mailer.php';
demarrerSession();

// Si déjà connecté, inutile de s'inscrire à nouveau.
if (!empty($_SESSION['utilisateur_id'])) {
    header('Location: profil.php');
    exit;
}

$erreurs = [];
$pseudoSaisi = '';
$emailSaisi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifierJetonCSRF($_POST['csrf_token'] ?? null)) {
        $erreurs[] = 'Jeton de sécurité invalide. Veuillez réessayer.';
    }

    $pseudoSaisi = nettoyer($_POST['pseudo'] ?? '');
    $emailSaisi  = nettoyer($_POST['email'] ?? '');
    $motDePasse  = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    // --- Validation côté serveur (indispensable, le JS peut être désactivé) ---
    if (!pseudoValide($pseudoSaisi)) {
        $erreurs[] = 'Le pseudo doit contenir entre 3 et 50 caractères (lettres, chiffres, - ou _).';
    }
    if (!emailValide($emailSaisi)) {
        $erreurs[] = 'L\'adresse email saisie n\'est pas valide.';
    }
    if (!motDePasseRobuste($motDePasse)) {
        $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.';
    }
    if ($motDePasse !== $confirmation) {
        $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    }

    // --- Vérification d'unicité en base ---
    if (empty($erreurs)) {
        $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE pseudo = ? OR email = ?');
        $stmt->execute([$pseudoSaisi, $emailSaisi]);
        if ($stmt->fetch()) {
            $erreurs[] = 'Ce pseudo ou cette adresse email est déjà utilisé.';
        }
    }

    // --- Création du compte ---
    if (empty($erreurs)) {
        // password_hash() utilise l'algorithme bcrypt par défaut :
        // il génère un sel aléatoire et un coût de calcul élevé,
        // rendant les attaques par force brute très coûteuses.
        $hash = password_hash($motDePasse, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO utilisateurs (pseudo, email, mot_de_passe_hash) VALUES (?, ?, ?)'
        );
        $stmt->execute([$pseudoSaisi, $emailSaisi, $hash]);

        // Connexion automatique après inscription
        $_SESSION['utilisateur_id'] = $pdo->lastInsertId();
        $_SESSION['pseudo'] = $pseudoSaisi;

        // Email de bienvenue (silencieux si erreur)
        try { emailBienvenue($emailSaisi, $pseudoSaisi); } catch (Exception $e) {}

        header('Location: profil.php');
        exit;
    }
}

$titrePage = 'Inscription';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Créer un compte</h1>
        <p class="subtitle">Rejoignez AlizQuiz et commencez le niveau Débutant dès maintenant.</p>

        <?php foreach ($erreurs as $erreur): ?>
            <div class="alert alert-error"><?= nettoyer($erreur) ?></div>
        <?php endforeach; ?>

        <form method="post" action="inscription.php" id="formInscription" novalidate>
            <input type="hidden" name="csrf_token" value="<?= obtenirJetonCSRF() ?>">

            <div class="form-group">
                <label for="pseudo">Pseudo</label>
                <input type="text" id="pseudo" name="pseudo" value="<?= nettoyer($pseudoSaisi) ?>" required minlength="3" maxlength="50" autocomplete="username">
            </div>

            <div class="form-group">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" value="<?= nettoyer($emailSaisi) ?>" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="8" autocomplete="new-password">
                <p class="form-hint" id="indiceMdp">Min. 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre</p>
            </div>

            <div class="form-group">
                <label for="confirmation">Confirmer le mot de passe</label>
                <input type="password" id="confirmation" name="confirmation" required minlength="8" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
        </form>

        <p class="auth-switch">Déjà inscrit ? <a href="connexion.php">Se connecter</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
