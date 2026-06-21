<?php
/**
 * certificat.php
 * ------------------------------------------------------------
 * Affiche un certificat virtuel à l'utilisateur ayant validé
 * les trois niveaux (niveau_actuel = 'termine').
 * Page protégée + vérification de l'éligibilité.
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/includes/securite.php';
require_once __DIR__ . '/includes/fonctions.php';
exigerConnexion();

$utilisateurId = (int) $_SESSION['utilisateur_id'];

$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
$stmt->execute([$utilisateurId]);
$utilisateur = $stmt->fetch();

if ($utilisateur['niveau_actuel'] !== 'termine') {
    header('Location: profil.php');
    exit;
}

// Récupère la date du résultat Expert réussi le plus récent, pour
// l'afficher comme date d'obtention du certificat.
$stmt = $pdo->prepare(
    "SELECT date_resultat FROM resultats
     WHERE utilisateur_id = ? AND niveau = 'expert' AND reussi = 1
     ORDER BY date_resultat DESC LIMIT 1"
);
$stmt->execute([$utilisateurId]);
$dateObtention = $stmt->fetchColumn() ?: date('Y-m-d H:i:s');

$titrePage = 'Mon certificat';
require_once __DIR__ . '/includes/header.php';
?>

<div class="certificate">
    <div class="certificate-frame">
        <svg class="cert-seal" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L20 6V12C20 17 16.5 20.5 12 22C7.5 20.5 4 17 4 12V6L12 2Z" stroke="#3D7CFF" stroke-width="1.4"/>
            <path d="M9 12L11 14L15 9.5" stroke="#3D7CFF" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div class="cert-eyebrow">Certificat de réussite</div>
        <h1>Parcours Cybersécurité AlizQuiz</h1>
        <p>Ce certificat atteste que</p>
        <div class="cert-name"><?= nettoyer($utilisateur['pseudo']) ?></div>
        <p>a validé avec succès les trois niveaux du parcours AlizQuiz — Débutant, Intermédiaire et Expert — démontrant une maîtrise des fondamentaux de la cybersécurité.</p>
        <p style="margin-top: 24px; font-size: 0.85rem; color: var(--text-muted);">Délivré le <?= date('d/m/Y', strtotime($dateObtention)) ?></p>
    </div>

    <div style="text-align:center; margin-top: 28px;">
        <button onclick="window.print()" class="btn btn-ghost">Imprimer / Enregistrer en PDF</button>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
