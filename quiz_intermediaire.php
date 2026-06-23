<?php
/**
 * quiz_intermediaire.php
 * ------------------------------------------------------------
 * Quiz du niveau Intermédiaire (25 questions).
 * Contrairement au niveau Débutant, ce niveau exige d'avoir
 * débloqué l'accès (niveau_actuel de l'utilisateur >= intermediaire).
 * Si l'utilisateur tente d'accéder directement via l'URL sans
 * avoir réussi le niveau Débutant, il est redirigé.
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/includes/securite.php';
require_once __DIR__ . '/includes/fonctions.php';
exigerConnexion();

const NIVEAU = 'intermediaire';
$utilisateurId = (int) $_SESSION['utilisateur_id'];

// Vérification d'accès : on relit le niveau actuel depuis la base
// (pas depuis la session, qui pourrait être obsolète) pour être sûr.
$stmt = $pdo->prepare('SELECT niveau_actuel FROM utilisateurs WHERE id = ?');
$stmt->execute([$utilisateurId]);
$niveauActuelUtilisateur = $stmt->fetchColumn();

if (!niveauAccessible(NIVEAU, $niveauActuelUtilisateur)) {
    header('Location: quiz_debutant.php');
    exit;
}

$resultatAffiche = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifierJetonCSRF($_POST['csrf_token'] ?? null)) {

    $idsQuestions = array_map('intval', explode(',', $_POST['ids_questions'] ?? ''));
    $score = 0;
    $detailReponses = [];

    foreach ($idsQuestions as $idQuestion) {
        $stmt = $pdo->prepare('SELECT * FROM quiz WHERE id = ? AND niveau = ?');
        $stmt->execute([$idQuestion, NIVEAU]);
        $question = $stmt->fetch();

        if (!$question) {
            continue;
        }

        $reponseDonnee = isset($_POST['reponse_' . $idQuestion]) ? (int) $_POST['reponse_' . $idQuestion] : 0;
        $estCorrecte = $reponseDonnee === (int) $question['bonne_reponse'];

        if ($estCorrecte) {
            $score++;
        }

        $detailReponses[] = [
            'question'       => $question['question'],
            'reponse_donnee' => $reponseDonnee > 0 ? $question['reponse' . $reponseDonnee] : '(aucune réponse)',
            'bonne_reponse'  => $question['reponse' . $question['bonne_reponse']],
            'est_correcte'   => $estCorrecte,
        ];
    }

    $totalQuestions = count($idsQuestions);
    $resultat = enregistrerResultat($pdo, $utilisateurId, NIVEAU, $score, $totalQuestions);

    $resultatAffiche = [
        'score'           => $score,
        'total'           => $totalQuestions,
        'pourcentage'     => $resultat['pourcentage'],
        'reussi'          => $resultat['reussi'],
        'detail'          => $detailReponses,
        'niveau_debloque' => $resultat['niveau_debloque'],
    ];
}

$questions = recupererQuestions($pdo, NIVEAU, 25);

$titrePage = 'Quiz Intermédiaire';
require_once __DIR__ . '/includes/header.php';
?>

<div class="quiz-wrapper">

    <?php if ($resultatAffiche !== null): ?>
        <div class="result-summary">
            <span class="quiz-theme-tag">Niveau Intermédiaire</span>
            <div class="result-score <?= $resultatAffiche['reussi'] ? 'success' : 'fail' ?>"
                 id="resultScore"
                 data-pct="<?= $resultatAffiche['pourcentage'] ?>">
                <?= $resultatAffiche['pourcentage'] ?>%
            </div>
            <p><?= $resultatAffiche['score'] ?> bonnes réponses sur <?= $resultatAffiche['total'] ?></p>
            <?php if ($resultatAffiche['reussi']): ?>
                <p style="color: var(--success); margin-top: 10px; font-weight: 600;">Niveau Expert débloqué !</p>
            <?php else: ?>
                <p style="color: var(--error); margin-top: 10px; font-weight: 600;">Seuil de 75% non atteint. Réessayez !</p>
            <?php endif; ?>
        </div>

        <div class="result-detail-list">
            <?php foreach ($resultatAffiche['detail'] as $item): ?>
                <div class="result-line <?= $item['est_correcte'] ? 'correct' : 'incorrect' ?>">
                    <div class="q-text"><?= nettoyer($item['question']) ?></div>
                    <div class="answer-given">
                        <span class="badge-tag <?= $item['est_correcte'] ? 'ok' : 'ko' ?>"><?= $item['est_correcte'] ? 'Correct' : 'Incorrect' ?></span>
                        Votre réponse : <?= nettoyer($item['reponse_donnee']) ?>
                    </div>
                    <?php if (!$item['est_correcte']): ?>
                        <div class="answer-correct">✓ Bonne réponse : <?= nettoyer($item['bonne_reponse']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display:flex; gap:14px; margin-top: 30px;">
            <a href="quiz_intermediaire.php" class="btn btn-ghost btn-block">Refaire le quiz</a>
            <a href="<?= $resultatAffiche['reussi'] ? 'quiz_expert.php' : 'profil.php' ?>" class="btn btn-primary btn-block">
                <?= $resultatAffiche['reussi'] ? 'Niveau suivant' : 'Voir mon profil' ?>
            </a>
        </div>

        <?php if (!empty($resultatAffiche['niveau_debloque'])): ?>
        <?php
        $libellesNiveau = ['expert' => 'Niveau Expert', 'termine' => 'Parcours complet'];
        $libelleDebloque = $libellesNiveau[$resultatAffiche['niveau_debloque']] ?? $resultatAffiche['niveau_debloque'];
        ?>
        <div id="unlock-overlay" class="unlock-overlay">
            <div class="unlock-modal">
                <div class="unlock-icon">🔓</div>
                <h2 class="unlock-title">Niveau débloqué !</h2>
                <p class="unlock-subtitle"><?= nettoyer($libelleDebloque) ?> est maintenant accessible.</p>
                <button class="btn btn-primary" onclick="fermerUnlock()">Continuer</button>
            </div>
        </div>
        <style>
        .unlock-overlay { position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.65);display:flex;align-items:center;justify-content:center;animation:fadeInOverlay .3s ease; }
        @keyframes fadeInOverlay{from{opacity:0}to{opacity:1}}
        .unlock-modal{background:var(--card-bg,#1a1a2e);border:1px solid var(--border,#2a2a3e);border-radius:18px;padding:48px 40px;text-align:center;max-width:400px;width:90%;animation:popIn .4s cubic-bezier(.34,1.56,.64,1)}
        @keyframes popIn{from{transform:scale(.7);opacity:0}to{transform:scale(1);opacity:1}}
        .unlock-icon{font-size:64px;margin-bottom:16px;animation:bounce 1s ease infinite alternate}
        @keyframes bounce{from{transform:translateY(0)}to{transform:translateY(-10px)}}
        .unlock-title{font-size:1.8rem;font-weight:700;margin-bottom:10px}
        .unlock-subtitle{color:var(--text-muted,#888);margin-bottom:28px;font-size:1.05rem}
        </style>
        <script>
        function fermerUnlock(){document.getElementById('unlock-overlay').style.animation='fadeInOverlay .2s ease reverse';setTimeout(()=>document.getElementById('unlock-overlay').remove(),200);}
        setTimeout(fermerUnlock,6000);
        </script>
        <?php endif; ?>

    <?php else: ?>
        <div class="quiz-header">
            <span class="quiz-theme-tag">Niveau Intermédiaire</span>
            <span><?= count($questions) ?> questions · seuil de réussite 75%</span>
        </div>
        <div class="progress-track"><div class="progress-fill" id="quizProgressFill" style="width: 0%;"></div></div>

        <form method="post" action="quiz_intermediaire.php">
            <input type="hidden" name="csrf_token" value="<?= obtenirJetonCSRF() ?>">
            <input type="hidden" name="ids_questions" value="<?= implode(',', array_column($questions, 'id')) ?>">

            <?php foreach ($questions as $index => $q): ?>
                <div class="question-card" style="margin-bottom: 18px;">
                    <span class="quiz-theme-tag" style="margin-bottom: 14px; display:inline-block;"><?= nettoyer($q['theme']) ?></span>
                    <h2>Q<?= $index + 1 ?>. <?= nettoyer($q['question']) ?></h2>
                    <div class="options-list">
                        <?php $ordreReponses = [1, 2, 3, 4]; shuffle($ordreReponses); ?>
                        <?php foreach ($ordreReponses as $pos => $i): ?>
                            <label class="option-item">
                                <span class="option-letter"><?= chr(65 + $pos) ?></span>
                                <input type="radio" name="reponse_<?= $q['id'] ?>" value="<?= $i ?>" required>
                                <?= nettoyer($q['reponse' . $i]) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary btn-block">Valider mes réponses</button>
        </form>
        <script>
        (function(){
            var total = <?= count($questions) ?>;
            var fill  = document.getElementById('quizProgressFill');
            function maj(){
                var rep = new Set();
                document.querySelectorAll('input[type=radio]:checked').forEach(function(r){ rep.add(r.name); });
                fill.style.width = (rep.size / total * 100).toFixed(1) + '%';
            }
            document.querySelectorAll('input[type=radio]').forEach(function(r){ r.addEventListener('change', maj); });
        })();
        </script>
    <?php endif; ?>

</div>

<?php if ($resultatAffiche !== null && $resultatAffiche['reussi']): ?>
<script src="<?= $base ?>/assets/js/confetti.js"></script>
<script>window.addEventListener('load', function(){ setTimeout(lancerConfettis, 300); });</script>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
