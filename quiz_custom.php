<?php
/**
 * quiz_custom.php
 * Quiz personnalisé : l'utilisateur choisit le nombre de questions
 * et les thèmes avant de démarrer. Accessible dès le niveau débutant.
 */
require_once __DIR__ . '/includes/securite.php';
require_once __DIR__ . '/includes/fonctions.php';
exigerConnexion();

$utilisateurId = (int) $_SESSION['utilisateur_id'];

// Récupérer le niveau actuel pour filtrer les thèmes accessibles
$stmtUser = $pdo->prepare('SELECT niveau_actuel FROM utilisateurs WHERE id = ?');
$stmtUser->execute([$utilisateurId]);
$niveauActuel = $stmtUser->fetchColumn();

// Niveaux accessibles selon la progression
$niveauxAccessibles = [];
if (in_array($niveauActuel, ['debutant','intermediaire','expert','termine'])) $niveauxAccessibles[] = 'debutant';
if (in_array($niveauActuel, ['intermediaire','expert','termine']))             $niveauxAccessibles[] = 'intermediaire';
if (in_array($niveauActuel, ['expert','termine']))                             $niveauxAccessibles[] = 'expert';

// Récupérer tous les thèmes disponibles selon les niveaux accessibles
$placeholders = implode(',', array_fill(0, count($niveauxAccessibles), '?'));
$stmtThemes = $pdo->prepare("SELECT DISTINCT theme, niveau FROM quiz WHERE niveau IN ($placeholders) ORDER BY FIELD(niveau,'debutant','intermediaire','expert'), theme");
$stmtThemes->execute($niveauxAccessibles);
$themesDisponibles = $stmtThemes->fetchAll();

$resultatAffiche = null;
$etape = 'config'; // 'config' ou 'quiz' ou 'resultat'

// ── Étape 2 : soumission du quiz ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids_questions']) && verifierJetonCSRF($_POST['csrf_token'] ?? null)) {
    $idsQuestions = array_map('intval', explode(',', $_POST['ids_questions'] ?? ''));
    $score = 0;
    $detailReponses = [];

    foreach ($idsQuestions as $idQuestion) {
        $stmt = $pdo->prepare('SELECT * FROM quiz WHERE id = ?');
        $stmt->execute([$idQuestion]);
        $question = $stmt->fetch();
        if (!$question) continue;

        $reponseDonnee = isset($_POST['reponse_' . $idQuestion]) ? (int) $_POST['reponse_' . $idQuestion] : 0;
        $estCorrecte = $reponseDonnee === (int) $question['bonne_reponse'];
        if ($estCorrecte) $score++;

        $detailReponses[] = [
            'question'       => $question['question'],
            'theme'          => $question['theme'],
            'reponse_donnee' => $reponseDonnee > 0 ? $question['reponse' . $reponseDonnee] : '(aucune réponse)',
            'bonne_reponse'  => $question['reponse' . $question['bonne_reponse']],
            'est_correcte'   => $estCorrecte,
        ];
    }

    $totalQuestions = count($idsQuestions);
    $pourcentage = $totalQuestions > 0 ? round(($score / $totalQuestions) * 100, 1) : 0;

    $resultatAffiche = [
        'score'       => $score,
        'total'       => $totalQuestions,
        'pourcentage' => $pourcentage,
        'reussi'      => $pourcentage >= 70,
        'detail'      => $detailReponses,
    ];
    $etape = 'resultat';
}

// ── Étape 1 : configuration soumise → charger les questions ──────
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['themes']) && verifierJetonCSRF($_POST['csrf_token'] ?? null)) {
    $nbDemande = max(5, min(30, (int)($_POST['nb_questions'] ?? 10)));
    $themesChoisis = array_filter($_POST['themes'] ?? [], function($t) use ($themesDisponibles) {
        return in_array($t, array_column($themesDisponibles, 'theme'));
    });

    if (empty($themesChoisis)) {
        $erreurConfig = "Sélectionne au moins un thème.";
        $etape = 'config';
    } else {
        $placeholdersT = implode(',', array_fill(0, count($themesChoisis), '?'));
        $stmtQ = $pdo->prepare("SELECT * FROM quiz WHERE theme IN ($placeholdersT) AND niveau IN ($placeholders)");
        $stmtQ->execute(array_merge(array_values($themesChoisis), $niveauxAccessibles));
        $toutesQuestions = $stmtQ->fetchAll();
        shuffle($toutesQuestions);
        $questions = array_slice($toutesQuestions, 0, min($nbDemande, count($toutesQuestions)));
        $etape = 'quiz';
    }
}

// Libellés niveaux pour affichage
$libellesNiveau = ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'expert' => 'Expert'];

$titrePage = 'Quiz Personnalisé';
require_once __DIR__ . '/includes/header.php';
?>

<div class="quiz-wrapper" style="max-width:780px;">

<?php if ($etape === 'config'): ?>
<!-- ══ CONFIGURATION ══════════════════════════════════════════ -->
<div class="quiz-header">
    <span class="quiz-theme-tag">Quiz Personnalisé</span>
    <span style="color:var(--text-muted); font-size:0.82rem;">Choisis tes thèmes</span>
</div>

<div class="card" style="margin-bottom:28px; padding:36px;">
    <h2 style="margin-bottom:8px; font-size:1.5rem;">Configure ton quiz</h2>
    <p style="color:var(--text-secondary); margin-bottom:32px;">Sélectionne les thèmes et le nombre de questions qui t'intéressent.</p>

    <?php if (!empty($erreurConfig)): ?>
        <div class="alert alert-error" style="margin-bottom:20px;"><?= nettoyer($erreurConfig) ?></div>
    <?php endif; ?>

    <form method="POST" action="quiz_custom.php">
        <input type="hidden" name="csrf_token" value="<?= obtenirJetonCSRF() ?>">

        <!-- Nombre de questions -->
        <div style="margin-bottom:32px;">
            <p style="font-size:0.84rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); margin-bottom:14px;">Nombre de questions</p>
            <div class="custom-nb-grid">
                <?php foreach ([5, 10, 15, 20] as $n): ?>
                <label class="custom-nb-btn">
                    <input type="radio" name="nb_questions" value="<?= $n ?>" <?= $n === 10 ? 'checked' : '' ?> style="display:none;">
                    <span><?= $n ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Thèmes -->
        <div style="margin-bottom:36px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                <p style="font-size:0.84rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); margin:0;">Thèmes</p>
                <div style="display:flex;gap:8px;">
                    <button type="button" onclick="toutCocher(true)"  class="btn btn-ghost" style="padding:5px 12px;font-size:0.78rem;">Tout sélectionner</button>
                    <button type="button" onclick="toutCocher(false)" class="btn btn-ghost" style="padding:5px 12px;font-size:0.78rem;">Tout décocher</button>
                </div>
            </div>

            <?php
            $niveauCourant = null;
            foreach ($themesDisponibles as $t):
                if ($t['niveau'] !== $niveauCourant):
                    if ($niveauCourant !== null) echo '</div>';
                    $niveauCourant = $t['niveau'];
            ?>
            <p style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); margin:18px 0 10px;">
                <?= $libellesNiveau[$t['niveau']] ?? $t['niveau'] ?>
            </p>
            <div class="custom-theme-grid">
            <?php endif; ?>
                <label class="custom-theme-btn">
                    <input type="checkbox" name="themes[]" value="<?= nettoyer($t['theme']) ?>" class="theme-check" style="display:none;">
                    <span><?= nettoyer($t['theme']) ?></span>
                </label>
            <?php endforeach; ?>
            <?php if ($niveauCourant !== null) echo '</div>'; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="font-size:1rem; padding:15px;">
            Lancer le quiz
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </form>
</div>

<style>
.custom-nb-grid { display:flex; gap:12px; flex-wrap:wrap; }
.custom-nb-btn  { cursor:pointer; }
.custom-nb-btn span {
    display:flex; align-items:center; justify-content:center;
    width:64px; height:64px;
    border:1px solid var(--border-subtle);
    border-radius:var(--radius-md);
    background:var(--bg-glass);
    font-family:var(--font-display); font-size:1.3rem; font-weight:700;
    color:var(--text-secondary);
    transition:border-color .15s, background .15s, color .15s, box-shadow .15s;
    backdrop-filter:blur(8px);
}
.custom-nb-btn input:checked + span {
    border-color:var(--blue-accent);
    background:var(--blue-accent-soft);
    color:var(--blue-bright);
    box-shadow:0 0 16px -4px rgba(61,124,255,0.4);
}
.custom-nb-btn:hover span { border-color:var(--border-glow); }

.custom-theme-grid { display:flex; flex-wrap:wrap; gap:10px; }
.custom-theme-btn { cursor:pointer; }
.custom-theme-btn span {
    display:inline-flex; align-items:center;
    padding:8px 16px;
    border:1px solid var(--border-subtle);
    border-radius:999px;
    background:var(--bg-glass);
    font-size:0.88rem; font-weight:500;
    color:var(--text-secondary);
    transition:border-color .15s, background .15s, color .15s;
    backdrop-filter:blur(8px);
}
.custom-theme-btn input:checked + span {
    border-color:var(--blue-accent);
    background:var(--blue-accent-soft);
    color:var(--blue-bright);
}
.custom-theme-btn:hover span { border-color:var(--border-glow); }
</style>

<script>
function toutCocher(etat) {
    document.querySelectorAll('.theme-check').forEach(function(c){ c.checked = etat; });
}
// Nb de questions : style radio visuel
document.querySelectorAll('.custom-nb-btn input').forEach(function(r){
    r.addEventListener('change', function(){ /* le CSS :checked gère le style */ });
});
</script>

<?php elseif ($etape === 'quiz' && !empty($questions)): ?>
<!-- ══ QUIZ ════════════════════════════════════════════════════ -->
<div class="quiz-header">
    <span class="quiz-theme-tag">Quiz Personnalisé</span>
    <span><?= count($questions) ?> questions</span>
</div>
<div class="progress-track"><div class="progress-fill" style="width:100%;"></div></div>

<form method="POST" action="quiz_custom.php">
    <input type="hidden" name="csrf_token" value="<?= obtenirJetonCSRF() ?>">
    <input type="hidden" name="ids_questions" value="<?= implode(',', array_column($questions, 'id')) ?>">

    <?php foreach ($questions as $index => $q): ?>
    <div class="question-card" style="margin-bottom:18px;">
        <span class="quiz-theme-tag" style="margin-bottom:14px; display:inline-block;"><?= nettoyer($q['theme']) ?></span>
        <h2>Q<?= $index+1 ?>. <?= nettoyer($q['question']) ?></h2>
        <div class="options-list">
            <?php $ordreReponses = [1, 2, 3, 4]; shuffle($ordreReponses); ?>
            <?php foreach ($ordreReponses as $pos => $i): ?>
            <label class="option-item">
                <span class="option-letter"><?= chr(65+$pos) ?></span>
                <input type="radio" name="reponse_<?= $q['id'] ?>" value="<?= $i ?>" required>
                <?= nettoyer($q['reponse'.$i]) ?>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary btn-block">Valider mes réponses</button>
</form>

<?php elseif ($etape === 'resultat' && $resultatAffiche !== null): ?>
<!-- ══ RÉSULTATS ══════════════════════════════════════════════ -->
<div class="result-summary">
    <span class="quiz-theme-tag">Quiz Personnalisé</span>
    <div class="result-score <?= $resultatAffiche['reussi'] ? 'success' : 'fail' ?>">
        <?= $resultatAffiche['pourcentage'] ?>%
    </div>
    <p><?= $resultatAffiche['score'] ?> bonnes réponses sur <?= $resultatAffiche['total'] ?></p>
    <?php if ($resultatAffiche['reussi']): ?>
        <p style="color:var(--success); margin-top:10px; font-weight:600;">Beau score !</p>
    <?php else: ?>
        <p style="color:var(--error); margin-top:10px; font-weight:600;">Continue à t'entraîner !</p>
    <?php endif; ?>
</div>

<div class="result-detail-list">
    <?php foreach ($resultatAffiche['detail'] as $item): ?>
    <div class="result-line <?= $item['est_correcte'] ? 'correct' : 'incorrect' ?>">
        <div style="font-size:0.72rem; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:.05em;"><?= nettoyer($item['theme']) ?></div>
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

<div style="display:flex; gap:14px; margin-top:30px;">
    <a href="quiz_custom.php" class="btn btn-ghost btn-block">Nouveau quiz</a>
    <a href="niveaux.php" class="btn btn-primary btn-block">Niveaux officiels</a>
</div>

<?php if ($resultatAffiche['reussi']): ?>
<script src="<?= $base ?>/assets/js/confetti.js"></script>
<script>window.addEventListener('load', function(){ setTimeout(lancerConfettis, 300); });</script>
<?php endif; ?>

<?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
