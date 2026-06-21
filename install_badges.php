<?php
require_once __DIR__ . '/includes/securite.php';
demarrerSession();
require_once __DIR__ . '/includes/fonctions.php';

$msgs = [];

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS badges_utilisateurs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        utilisateur_id INT NOT NULL,
        badge_slug VARCHAR(40) NOT NULL,
        obtenu_le DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_badge (utilisateur_id, badge_slug),
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
    )");
    $msgs[] = "✓ Table badges_utilisateurs créée";
} catch (Exception $e) { $msgs[] = "⚠ " . $e->getMessage(); }

echo "<pre>" . implode("\n", $msgs) . "\n\nOK — tu peux supprimer ce fichier.</pre>";
