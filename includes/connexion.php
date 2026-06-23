<?php
/**
 * includes/connexion.php
 * ------------------------------------------------------------
 * Connexion à la base de données MySQL via PDO.
 *
 * Pourquoi PDO et pas mysqli ?
 *   PDO permet d'utiliser des "requêtes préparées" facilement,
 *   ce qui est la meilleure protection contre les injections SQL.
 *   Le principe : on n'écrit jamais les variables utilisateur
 *   directement dans la requête, on utilise des "marqueurs" (?)
 *   qui sont remplis après coup, séparément.
 *
 * Ce fichier est inclus (require) en haut de chaque page qui a
 * besoin de parler à la base de données.
 * ------------------------------------------------------------
 */

// Paramètres de connexion — à adapter si besoin (XAMPP par défaut :
// utilisateur "root", pas de mot de passe, base sur le port 3306).
define('DB_HOST',    getenv('MYSQLHOST')     ?: 'localhost');
define('DB_NAME',    getenv('MYSQLDATABASE') ?: 'alizquiz');
define('DB_USER',    getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS',    getenv('MYSQLPASSWORD') ?: '');
define('DB_PORT',    getenv('MYSQLPORT')     ?: '3306');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    // Options PDO :
    // - ERRMODE_EXCEPTION : toute erreur SQL déclenche une exception
    //   (plus facile à gérer qu'un code retour à vérifier partout)
    // - FETCH_ASSOC : les résultats sont retournés sous forme de
    //   tableaux associatifs ['colonne' => 'valeur'] plutôt que
    //   des tableaux numériques
    // - EMULATE_PREPARES => false : on utilise les VRAIES requêtes
    //   préparées du serveur MySQL (sécurité maximale), pas une
    //   émulation côté PHP.
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // S'assure que les colonnes / tables optionnelles existent
    // (avatar, badges, défi). Ne tourne qu'une fois par conteneur.
    require_once __DIR__ . '/schema.php';
    assurerSchema($pdo);

    // Rééquilibrage des réponses des questions (distracteurs crédibles).
    require_once __DIR__ . '/ameliorations_questions.php';
    ameliorerQuestions($pdo);

} catch (PDOException $e) {
    // En production, on ne doit jamais afficher le message d'erreur
    // brut (il peut révéler des infos sur la base de données).
    // Ici, message générique + on stoppe l'exécution de la page.
    die('Erreur de connexion à la base de données. Vérifiez que MySQL est démarré dans XAMPP.');
}
