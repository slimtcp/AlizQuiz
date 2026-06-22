<?php
/**
 * includes/securite.php
 * ------------------------------------------------------------
 * Fonctions utilitaires de sécurité, utilisées sur tout le site.
 * Centraliser ces fonctions ici évite de dupliquer du code et
 * garantit que la même règle de sécurité est appliquée partout.
 * ------------------------------------------------------------
 */

/**
 * Nettoie une chaîne avant affichage HTML pour éviter les failles XSS
 * (Cross-Site Scripting). Le principe : si un utilisateur tape du
 * code <script> dans un champ, on le transforme en texte inoffensif
 * au lieu de le laisser s'exécuter dans le navigateur.
 */
function nettoyer(string $valeur): string
{
    return htmlspecialchars(trim($valeur), ENT_QUOTES, 'UTF-8');
}

/**
 * Valide le format d'une adresse email.
 */
function emailValide(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valide la robustesse d'un mot de passe :
 * au moins 8 caractères, une majuscule, une minuscule et un chiffre.
 */
function motDePasseRobuste(string $motDePasse): bool
{
    return strlen($motDePasse) >= 8
        && preg_match('/[A-Z]/', $motDePasse)
        && preg_match('/[a-z]/', $motDePasse)
        && preg_match('/[0-9]/', $motDePasse);
}

/**
 * Valide un pseudo : lettres, chiffres, tirets et underscores,
 * entre 3 et 50 caractères.
 */
function pseudoValide(string $pseudo): bool
{
    return preg_match('/^[A-Za-z0-9_-]{3,50}$/', $pseudo) === 1;
}

/**
 * Démarre la session de façon sécurisée si elle n'est pas déjà active.
 * Les paramètres de cookie limitent les risques de vol de session.
 */
function demarrerSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        // Durée de connexion : 30 jours. On reste connecté même après
        // avoir fermé l'app / le navigateur ou être resté inactif
        // longtemps (utile surtout sur mobile, où changer d'app peut
        // sinon "perdre" la session).
        $duree = 60 * 60 * 24 * 30; // 30 jours en secondes

        // Côté serveur : durée de vie des données de session.
        ini_set('session.gc_maxlifetime', (string) $duree);

        // Cookie HTTPS uniquement en production (Railway), pas en local.
        $secure = !empty($_SERVER['HTTPS']) || ($_SERVER['SERVER_PORT'] ?? null) == 443;

        session_set_cookie_params([
            'lifetime' => $duree,   // le cookie survit à la fermeture du navigateur
            'path'     => '/',
            'secure'   => $secure,  // cookie envoyé seulement en HTTPS en prod
            'httponly' => true,     // inaccessible en JavaScript (anti-XSS)
            'samesite' => 'Lax',    // limite les attaques CSRF
        ]);
        session_start();

        // Rafraîchit le cookie à chaque visite : la fenêtre de 30 jours
        // repart de zéro tant que l'utilisateur revient régulièrement.
        if (!empty($_SESSION['utilisateur_id'])) {
            setcookie(session_name(), session_id(), [
                'expires'  => time() + $duree,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }
}

/**
 * Vérifie que l'utilisateur est connecté, sinon le redirige vers
 * la page de connexion. À appeler en haut des pages protégées.
 */
function exigerConnexion(): void
{
    demarrerSession();
    if (empty($_SESSION['utilisateur_id'])) {
        header('Location: connexion.php');
        exit;
    }
}

/**
 * Génère (ou récupère) un jeton CSRF pour protéger les formulaires
 * contre les soumissions forgées depuis un autre site.
 */
function obtenirJetonCSRF(): string
{
    demarrerSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie qu'un jeton CSRF soumis correspond bien à celui de la session.
 */
function verifierJetonCSRF(?string $jeton): bool
{
    demarrerSession();
    return !empty($jeton) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $jeton);
}
