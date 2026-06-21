<?php
/**
 * connexion.php
 * ------------------------------------------------------------
 * Authentification d'un utilisateur existant.
 * Sécurité :
 *  - le mot de passe saisi est vérifié avec password_verify(),
 *    JAMAIS en comparant directement des chaînes de caractères
 *  - message d'erreur volontairement générique ("identifiants
 *    incorrects") pour ne pas révéler si c'est le pseudo ou le
 *    mot de passe qui est erroné (évite de faciliter une attaque)
 *  - limitation simple des tentatives via la session pour
 *    ralentir une attaque par force brute
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/includes/securite.php';
require_once __DIR__ . '/includes/fonctions.php';
demarrerSession();

if (!empty($_SESSION['utilisateur_id'])) {
    header('Location: profil.php');
    exit;
}

$erreurs = [];
$identifiantSaisi = '';

// Compteur de tentatives échouées, stocké en session.
if (!isset($_SESSION['tentatives_echouees'])) {
    $_SESSION['tentatives_echouees'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifierJetonCSRF($_POST['csrf_token'] ?? null)) {
        $erreurs[] = 'Jeton de sécurité invalide. Veuillez réessayer.';
    }

    // Blocage temporaire après 5 tentatives échouées (anti force-brute).
    if ($_SESSION['tentatives_echouees'] >= 5) {
        $erreurs[] = 'Trop de tentatives échouées. Veuillez réessayer dans quelques minutes.';
    }

    $identifiantSaisi = nettoyer($_POST['identifiant'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    if (empty($erreurs) && ($identifiantSaisi === '' || $motDePasse === '')) {
        $erreurs[] = 'Veuillez renseigner tous les champs.';
    }

    if (empty($erreurs)) {
        // L'utilisateur peut se connecter avec son pseudo OU son email.
        $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE pseudo = ? OR email = ?');
        $stmt->execute([$identifiantSaisi, $identifiantSaisi]);
        $utilisateur = $stmt->fetch();

        // password_verify() compare le mot de passe saisi avec le
        // hash stocké, sans jamais avoir besoin de déchiffrer quoi
        // que ce soit (le hash n'est pas réversible par nature).
        if ($utilisateur && password_verify($motDePasse, $utilisateur['mot_de_passe_hash'])) {
            $_SESSION['utilisateur_id'] = $utilisateur['id'];
            $_SESSION['pseudo'] = $utilisateur['pseudo'];
            $_SESSION['tentatives_echouees'] = 0;

            header('Location: profil.php');
            exit;
        } else {
            $_SESSION['tentatives_echouees']++;
            $erreurs[] = 'Identifiant ou mot de passe incorrect.';
        }
    }
}

$titrePage = 'Connexion';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Connexion</h1>
        <p class="subtitle">Accédez à votre tableau de bord et reprenez votre progression.</p>

        <?php foreach ($erreurs as $erreur): ?>
            <div class="alert alert-error"><?= nettoyer($erreur) ?></div>
        <?php endforeach; ?>

        <form method="post" action="connexion.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= obtenirJetonCSRF() ?>">

            <div class="form-group">
                <label for="identifiant">Pseudo ou email</label>
                <input type="text" id="identifiant" name="identifiant" value="<?= nettoyer($identifiantSaisi) ?>" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>

        <p class="auth-switch">Pas encore de compte ? <a href="inscription.php">S'inscrire</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
