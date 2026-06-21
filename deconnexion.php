<?php
/**
 * deconnexion.php
 * ------------------------------------------------------------
 * Détruit complètement la session de l'utilisateur :
 *  - vide le tableau $_SESSION
 *  - supprime le cookie de session côté navigateur
 *  - détruit la session côté serveur
 * Cela évite qu'une session reste valide après la déconnexion.
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/includes/securite.php';
demarrerSession();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: accueil.php');
exit;
