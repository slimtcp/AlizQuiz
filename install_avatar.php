<?php
require_once __DIR__ . '/includes/securite.php';
demarrerSession();
require_once __DIR__ . '/includes/fonctions.php';

$msgs = [];

try {
    $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS avatar_couleur VARCHAR(20) DEFAULT '#3D7CFF'");
    $msgs[] = "✓ Colonne avatar_couleur ajoutée";
} catch (Exception $e) { $msgs[] = "⚠ avatar_couleur : " . $e->getMessage(); }

try {
    $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS avatar_icone VARCHAR(20) DEFAULT 'shield'");
    $msgs[] = "✓ Colonne avatar_icone ajoutée";
} catch (Exception $e) { $msgs[] = "⚠ avatar_icone : " . $e->getMessage(); }

echo "<pre>" . implode("\n", $msgs) . "\n\nOK — tu peux supprimer ce fichier.</pre>";
