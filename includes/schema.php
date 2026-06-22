<?php
/**
 * includes/schema.php
 * ------------------------------------------------------------
 * Migration automatique du schéma.
 *
 * Sur l'hébergement Railway, la base ne contient au départ que les
 * tables de base (utilisateurs, quiz, resultats). Les fonctionnalités
 * ajoutées ensuite (avatar, badges, défi du jour) ont besoin de
 * colonnes / tables supplémentaires.
 *
 * Plutôt que de demander de lancer manuellement les scripts
 * install_*.php, on s'assure ici que tout existe. Les instructions
 * sont idempotentes (IF NOT EXISTS) : sans effet si déjà présent.
 *
 * Pour éviter de relancer ces requêtes à chaque page, on pose un
 * fichier marqueur dans le dossier temporaire : la migration ne
 * tourne donc qu'une fois par démarrage du conteneur.
 * ------------------------------------------------------------
 */

function assurerSchema(PDO $pdo): void
{
    $marqueur = sys_get_temp_dir() . '/alizquiz_schema_ok';
    if (is_file($marqueur)) {
        return; // déjà vérifié pour ce conteneur
    }

    // Avatar : colonnes sur la table utilisateurs.
    // On vérifie l'existence via information_schema avant d'ajouter :
    // "ADD COLUMN IF NOT EXISTS" n'existe que sur MariaDB, pas MySQL 8.
    $ajouterColonne = function (string $colonne, string $definition) use ($pdo) {
        try {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = "utilisateurs"
                   AND COLUMN_NAME = ?'
            );
            $stmt->execute([$colonne]);
            if ((int) $stmt->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN $colonne $definition");
            }
        } catch (Throwable $e) {}
    };
    $ajouterColonne('avatar_couleur', "VARCHAR(20) DEFAULT '#3D7CFF'");
    $ajouterColonne('avatar_icone',   "VARCHAR(20) DEFAULT 'shield'");

    // Badges
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS badges_utilisateurs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            utilisateur_id INT NOT NULL,
            badge_slug VARCHAR(40) NOT NULL,
            obtenu_le DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_badge (utilisateur_id, badge_slug),
            FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
        )");
    } catch (Throwable $e) {}

    // Défi du jour
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS defi_resultats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            utilisateur_id INT NOT NULL,
            date_defi DATE NOT NULL,
            score INT NOT NULL,
            temps_secondes INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_defi (utilisateur_id, date_defi),
            FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
        )");
    } catch (Throwable $e) {}

    // Ne pose le marqueur que si la migration clé a réussi (colonne
    // avatar présente) : ainsi un échec transitoire sera retenté.
    try {
        $stmt = $pdo->query(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = "utilisateurs"
               AND COLUMN_NAME = "avatar_couleur"'
        );
        if ((int) $stmt->fetchColumn() > 0) {
            @file_put_contents($marqueur, date('c'));
        }
    } catch (Throwable $e) {}
}
