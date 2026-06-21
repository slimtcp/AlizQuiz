<?php
/**
 * classement.php
 * ------------------------------------------------------------
 * Classement général : affiche le meilleur résultat de chaque
 * utilisateur, trié par niveau atteint (debutant < intermediaire
 * < expert < termine), puis par meilleur pourcentage en cas
 * d'égalité de niveau.
 * Accessible sans connexion (vitrine publique du site).
 *
 * Requête SQL expliquée :
 *   On utilise une sous-requête pour ne garder que le MEILLEUR
 *   résultat de chaque utilisateur (sinon un même utilisateur
 *   apparaîtrait plusieurs fois dans le classement).
 *   Le niveau affiché et utilisé pour le tri est niveau_actuel
 *   de la table utilisateurs (niveau réel courant).
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/includes/securite.php';
require_once __DIR__ . '/includes/fonctions.php';
demarrerSession();

$sql = "
    SELECT u.pseudo, u.niveau_actuel AS niveau, r.pourcentage, r.date_resultat
    FROM resultats r
    INNER JOIN utilisateurs u ON u.id = r.utilisateur_id
    WHERE r.id IN (
        SELECT MAX(r2.id)
        FROM resultats r2
        WHERE r2.utilisateur_id = r.utilisateur_id
        AND r2.pourcentage = (
            SELECT MAX(r3.pourcentage) FROM resultats r3 WHERE r3.utilisateur_id = r2.utilisateur_id
        )
    )
    ORDER BY
        CASE u.niveau_actuel
            WHEN 'termine'      THEN 4
            WHEN 'expert'       THEN 3
            WHEN 'intermediaire' THEN 2
            WHEN 'debutant'     THEN 1
            ELSE 0
        END DESC,
        r.pourcentage DESC,
        r.date_resultat ASC
    LIMIT 50
";

$classement = $pdo->query($sql)->fetchAll();

$titrePage = 'Classement';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-bottom: 0;">
    <div class="section-head">
        <h2>Classement général</h2>
        <p>Le meilleur score de chaque joueur, toutes tentatives confondues.</p>
    </div>
</section>

<div class="history-table-wrap">
    <?php if (empty($classement)): ?>
        <div class="empty-state card">
            <p>Aucun résultat enregistré pour l'instant. Soyez le premier à apparaître dans le classement !</p>
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0;">
            <div class="rank-row" style="font-weight:600; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">
                <span>#</span>
                <span>Pseudo</span>
                <span class="col-niveau">Niveau atteint</span>
                <span>Score</span>
                <span class="col-date">Date</span>
            </div>
            <?php foreach ($classement as $index => $ligne): ?>
                <?php $rang = $index + 1; ?>
                <div class="rank-row <?= $rang <= 3 ? 'top-' . $rang : '' ?>">
                    <span class="rank-number">#<?= $rang ?></span>
                    <span><?= nettoyer($ligne['pseudo']) ?></span>
                    <span class="col-niveau"><?= nettoyer(libelleNiveauComplete($ligne['niveau'])) ?></span>
                    <span><?= $ligne['pourcentage'] ?>%</span>
                    <span class="col-date"><?= date('d/m/Y', strtotime($ligne['date_resultat'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
