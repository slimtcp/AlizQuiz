<?php
/**
 * defi.php — Défi du Jour
 * Même 5 questions pour tous, thème différent chaque jour.
 * Reset à 12h00 heure de Paris.
 */
require_once __DIR__ . '/includes/securite.php';
require_once __DIR__ . '/includes/fonctions.php';
exigerConnexion();

$utilisateurId = (int) $_SESSION['utilisateur_id'];

// ── Calcul de la période courante (reset à 12h Paris) ────────────
$tz      = new DateTimeZone('Europe/Paris');
$now     = new DateTime('now', $tz);
$heure   = (int) $now->format('H');
$minute  = (int) $now->format('i');

if ($heure < 12) {
    $defiDate  = (clone $now)->modify('-1 day')->format('Y-m-d');
    $nextReset = (clone $now)->setTime(12, 0, 0);
} else {
    $defiDate  = $now->format('Y-m-d');
    $nextReset = (clone $now)->modify('+1 day')->setTime(12, 0, 0);
}
$secondsUntilReset = max(0, $nextReset->getTimestamp() - $now->getTimestamp());

// ── Thème du jour (rotation déterministe sur 16 jours) ───────────
$themesRotation = [
    ['theme' => 'Mots de passe',                   'niveau' => 'debutant'],
    ['theme' => 'Phishing',                         'niveau' => 'intermediaire'],
    ['theme' => 'Chiffrement',                      'niveau' => 'expert'],
    ['theme' => 'Virus',                            'niveau' => 'debutant'],
    ['theme' => 'Ransomware',                       'niveau' => 'intermediaire'],
    ['theme' => 'SQL Injection',                    'niveau' => 'expert'],
    ['theme' => 'Emails suspects',                  'niveau' => 'debutant'],
    ['theme' => 'Réseaux',                          'niveau' => 'intermediaire'],
    ['theme' => 'Force brute',                      'niveau' => 'expert'],
    ['theme' => 'HTTPS',                            'niveau' => 'debutant'],
    ['theme' => 'Protection des données',           'niveau' => 'intermediaire'],
    ['theme' => 'Pare-feu',                         'niveau' => 'expert'],
    ['theme' => 'Mises à jour',                     'niveau' => 'debutant'],
    ['theme' => 'Authentification à deux facteurs', 'niveau' => 'intermediaire'],
    ['theme' => 'Sécurité réseau',                  'niveau' => 'expert'],
    ['theme' => 'VPN',                              'niveau' => 'expert'],
];

$refDate     = new DateTime('2026-01-01', $tz);
$defiDateObj = new DateTime($defiDate, $tz);
$dayIndex    = (int) (($defiDateObj->getTimestamp() - $refDate->getTimestamp()) / 86400);
$themeJour   = $themesRotation[abs($dayIndex) % count($themesRotation)];

$niveauBadge = ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'expert' => 'Expert'];
$niveauColor = ['debutant' => '#34D399', 'intermediaire' => '#3D7CFF', 'expert' => '#7C3AED'];

// ── Questions du jour (5, déterministes via mt_srand) ────────────
$stmtQ = $pdo->prepare('SELECT * FROM quiz WHERE theme = ?');
$stmtQ->execute([$themeJour['theme']]);
$toutesQuestions = $stmtQ->fetchAll();

$seed = (int) str_replace('-', '', $defiDate);
mt_srand($seed);
shuffle($toutesQuestions);
mt_srand(); // libérer le seed
$questionsJour = array_slice($toutesQuestions, 0, 5);

// ── Thème de demain ──────────────────────────────────────────────
$demainDate    = (clone new DateTime($defiDate, $tz))->modify('+1 day');
$demainIndex   = (int)(($demainDate->getTimestamp() - $refDate->getTimestamp()) / 86400);
$themeDemain   = $themesRotation[abs($demainIndex) % count($themesRotation)];

// ── Déjà participé ? ─────────────────────────────────────────────
$dejaParticipe = false;
try {
    $stmtCheck = $pdo->prepare('SELECT score, temps_secondes FROM defi_resultats WHERE utilisateur_id = ? AND date_defi = ?');
    $stmtCheck->execute([$utilisateurId, $defiDate]);
    $dejaParticipe = $stmtCheck->fetch() ?: false;
} catch (Throwable $e) {}

// ── Soumission du quiz ────────────────────────────────────────────
$resultatAffiche = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['ids_questions'])
    && verifierJetonCSRF($_POST['csrf_token'] ?? null)
    && !$dejaParticipe
) {
    $idsQuestions  = array_map('intval', explode(',', $_POST['ids_questions'] ?? ''));
    $tempsSecondes = max(0, min(3600, (int) ($_POST['temps_ecoule'] ?? 0)));
    $score         = 0;
    $detailReponses = [];

    foreach ($idsQuestions as $idQ) {
        $stmt = $pdo->prepare('SELECT * FROM quiz WHERE id = ? AND theme = ?');
        $stmt->execute([$idQ, $themeJour['theme']]);
        $question = $stmt->fetch();
        if (!$question) continue;

        $reponseDonnee = isset($_POST['reponse_' . $idQ]) ? (int) $_POST['reponse_' . $idQ] : 0;
        $estCorrecte   = $reponseDonnee === (int) $question['bonne_reponse'];
        if ($estCorrecte) $score++;

        $detailReponses[] = [
            'question'       => $question['question'],
            'reponse_donnee' => $reponseDonnee > 0 ? $question['reponse' . $reponseDonnee] : '(aucune réponse)',
            'bonne_reponse'  => $question['reponse' . $question['bonne_reponse']],
            'est_correcte'   => $estCorrecte,
        ];
    }

    try {
        $stmtIns = $pdo->prepare('INSERT INTO defi_resultats (utilisateur_id, date_defi, score, temps_secondes) VALUES (?, ?, ?, ?)');
        $stmtIns->execute([$utilisateurId, $defiDate, $score, $tempsSecondes]);
    } catch (Throwable $e) {}

    $pourcentage     = round(($score / 5) * 100);
    $resultatAffiche = [
        'score'      => $score,
        'pourcentage'=> $pourcentage,
        'reussi'     => $score >= 3,
        'detail'     => $detailReponses,
        'temps'      => $tempsSecondes,
    ];
    $dejaParticipe = ['score' => $score, 'temps_secondes' => $tempsSecondes];
}

// ── Historique des 7 derniers défis + streak ─────────────────────
$historiqueDefi = [];
try {
    $stmtHisto = $pdo->prepare('
        SELECT date_defi, score FROM defi_resultats
        WHERE utilisateur_id = ?
        ORDER BY date_defi DESC
        LIMIT 7
    ');
    $stmtHisto->execute([$utilisateurId]);
    $historiqueDefi = $stmtHisto->fetchAll();
} catch (Throwable $e) {}

// Calcul du streak : jours consécutifs terminant à defiDate (ou hier si pas encore fait)
$streakCount = 0;
$historiqueIndex = array_column($historiqueDefi, 'score', 'date_defi');
// Si le défi du jour n'est pas encore fait, on commence le streak à partir d'hier
$cursorStreak = isset($historiqueIndex[$defiDate])
    ? new DateTime($defiDate, $tz)
    : (clone new DateTime($defiDate, $tz))->modify('-1 day');
while (isset($historiqueIndex[$cursorStreak->format('Y-m-d')])) {
    $streakCount++;
    $cursorStreak->modify('-1 day');
}

// ── Classement du jour ────────────────────────────────────────────
$classementJour = [];
try {
    $stmtClass = $pdo->prepare('
        SELECT u.pseudo, dr.score, dr.temps_secondes,
               (dr.utilisateur_id = :uid) AS c_est_moi
        FROM defi_resultats dr
        JOIN utilisateurs u ON u.id = dr.utilisateur_id
        WHERE dr.date_defi = :date
        ORDER BY dr.score DESC, dr.temps_secondes ASC
        LIMIT 20
    ');
    $stmtClass->execute([':uid' => $utilisateurId, ':date' => $defiDate]);
    $classementJour = $stmtClass->fetchAll();
} catch (Throwable $e) {}

// Rang de l'utilisateur courant
$monRang = null;
foreach ($classementJour as $i => $r) {
    if ($r['c_est_moi']) { $monRang = $i + 1; break; }
}

function formatTemps(int $s): string {
    if ($s < 60) return $s . 's';
    return floor($s/60) . 'min ' . ($s%60) . 's';
}

$titrePage = 'Défi du Jour';
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ── Défi du jour — styles ───────────────────────────── */
.defi-hero {
    text-align:center;
    padding:48px 24px 36px;
}
.defi-date-badge {
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-size:0.75rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.1em;
    color:var(--text-muted);
    background:var(--bg-glass);
    border:1px solid var(--border-subtle);
    border-radius:999px;
    padding:4px 14px;
    margin-bottom:20px;
    backdrop-filter:blur(8px);
}
.defi-hero h1 {
    font-family:var(--font-display);
    font-size:clamp(1.8rem, 4vw, 2.8rem);
    font-weight:700;
    background:var(--grad-text);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
    margin-bottom:10px;
}
.defi-theme-pill {
    display:inline-flex;
    align-items:center;
    gap:12px;
    background:var(--bg-glass);
    border:1px solid var(--border-subtle);
    border-radius:var(--radius-lg);
    padding:16px 32px;
    margin:0;
    backdrop-filter:blur(12px);
    box-shadow:0 0 32px -8px rgba(61,124,255,.2);
    min-height:80px;
}
.defi-theme-name {
    font-size:1.3rem;
    font-weight:700;
    color:var(--text-primary);
}
.defi-niveau-dot {
    font-size:0.72rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.08em;
    padding:3px 10px;
    border-radius:999px;
    opacity:.9;
}
/* Countdown */
.defi-countdown-wrap {
    display:inline-flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:6px;
    min-height:80px;
}
.defi-countdown-label {
    font-size:0.7rem;
    text-transform:uppercase;
    letter-spacing:.1em;
    color:var(--text-muted);
}
.defi-countdown {
    display:flex;
    gap:6px;
    align-items:center;
}
.countdown-block {
    display:flex;
    flex-direction:column;
    align-items:center;
    background:var(--bg-glass);
    border:1px solid var(--border-subtle);
    border-radius:var(--radius-md);
    padding:8px 14px;
    min-width:52px;
    backdrop-filter:blur(8px);
}
.countdown-num {
    font-family:var(--font-display);
    font-size:1.5rem;
    font-weight:700;
    color:var(--blue-bright);
    line-height:1;
}
.countdown-unit {
    font-size:0.6rem;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:var(--text-muted);
    margin-top:3px;
}
.countdown-sep {
    font-size:1.4rem;
    font-weight:700;
    color:var(--text-muted);
    padding-bottom:14px;
}
/* Classement */
.defi-classement {
    margin-top:40px;
}
.classement-row {
    display:flex;
    align-items:center;
    gap:14px;
    padding:12px 18px;
    border-radius:var(--radius-md);
    margin-bottom:6px;
    background:var(--bg-glass);
    border:1px solid var(--border-subtle);
    backdrop-filter:blur(8px);
    transition:border-color .15s;
}
.classement-row.c-moi {
    border-color:var(--blue-accent);
    background:var(--blue-accent-soft);
    box-shadow:0 0 20px -6px rgba(61,124,255,.3);
}
.classement-row:hover { border-color:var(--border-glow); }
.rank-num {
    font-family:var(--font-display);
    font-size:1rem;
    font-weight:700;
    width:28px;
    text-align:center;
    color:var(--text-muted);
    flex-shrink:0;
}
.rank-num.top1 { color:#FBBF24; font-size:1.3rem; }
.rank-num.top2 { color:#CBD5E1; }
.rank-num.top3 { color:#CD7C40; }
.classement-pseudo { flex:1; font-weight:600; }
.classement-score {
    font-family:var(--font-display);
    font-weight:700;
    font-size:1.05rem;
}
.classement-temps { font-size:0.8rem; color:var(--text-muted); }
.score-stars { font-size:0.85rem; }
/* Résultats */
.defi-result-header {
    text-align:center;
    padding:36px 24px 24px;
}
.defi-rang-badge {
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:var(--blue-accent-soft);
    border:1px solid var(--blue-accent);
    border-radius:999px;
    padding:6px 18px;
    font-size:0.88rem;
    font-weight:700;
    color:var(--blue-bright);
    margin-top:14px;
}
.already-badge {
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:rgba(52,211,153,.1);
    border:1px solid rgba(52,211,153,.3);
    border-radius:999px;
    padding:5px 16px;
    font-size:0.82rem;
    font-weight:600;
    color:#34D399;
    margin-top:12px;
}
/* Streak & historique */
.streak-histo-wrap {
    display:flex;
    align-items:center;
    justify-content:center;
    gap:24px;
    flex-wrap:wrap;
    margin:22px 0 4px;
}
.streak-badge {
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:rgba(251,191,36,.08);
    border:1px solid rgba(251,191,36,.3);
    border-radius:var(--radius-lg);
    padding:10px 20px;
}
.streak-count {
    font-family:var(--font-display);
    font-size:1.6rem;
    font-weight:700;
    color:#FBBF24;
    line-height:1;
}
.streak-label {
    font-size:0.75rem;
    color:var(--text-muted);
    text-transform:uppercase;
    letter-spacing:.06em;
    line-height:1.3;
}
.histo-days {
    display:flex;
    gap:7px;
    align-items:flex-end;
}
.histo-day {
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:5px;
}
.histo-dot {
    width:34px;
    height:34px;
    border-radius:var(--radius-md);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:0.75rem;
    font-weight:700;
    border:1px solid var(--border-subtle);
    background:var(--bg-glass);
    color:var(--text-muted);
    transition:transform .15s;
}
.histo-dot.done   { background:rgba(52,211,153,.15); border-color:rgba(52,211,153,.4); color:#34D399; }
.histo-dot.perfect{ background:rgba(251,191,36,.15);  border-color:rgba(251,191,36,.4);  color:#FBBF24; }
.histo-dot.today  { border-style:dashed; }
.histo-date {
    font-size:0.6rem;
    color:var(--text-muted);
    text-transform:uppercase;
    letter-spacing:.04em;
}
</style>

<div class="quiz-wrapper" style="max-width:720px;">

<!-- ══ EN-TÊTE ══════════════════════════════════════════════════ -->
<div class="defi-hero">
    <div class="defi-date-badge">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <?= (new DateTime($defiDate, $tz))->format('d/m/Y') ?>
    </div>
    <h1>Défi du Jour</h1>

    <div style="display:flex; align-items:flex-end; justify-content:center; gap:40px; flex-wrap:wrap; margin:24px 0 12px;">
        <div class="defi-theme-pill" style="margin:0;">
            <span class="defi-theme-name"><?= nettoyer($themeJour['theme']) ?></span>
            <span class="defi-niveau-dot" style="background:<?= $niveauColor[$themeJour['niveau']] ?>22; color:<?= $niveauColor[$themeJour['niveau']] ?>; border:1px solid <?= $niveauColor[$themeJour['niveau']] ?>44">
                <?= $niveauBadge[$themeJour['niveau']] ?>
            </span>
        </div>

        <div class="defi-countdown-wrap" style="margin:0;">
            <span class="defi-countdown-label">Prochain défi dans</span>
            <div class="defi-countdown" id="countdown">
                <div class="countdown-block"><span class="countdown-num" id="cd-h">00</span><span class="countdown-unit">h</span></div>
                <span class="countdown-sep">:</span>
                <div class="countdown-block"><span class="countdown-num" id="cd-m">00</span><span class="countdown-unit">min</span></div>
                <span class="countdown-sep">:</span>
                <div class="countdown-block"><span class="countdown-num" id="cd-s">00</span><span class="countdown-unit">sec</span></div>
            </div>
        </div>
    </div>

    <?php if ($dejaParticipe && !$resultatAffiche): ?>
        <div class="already-badge">✓ Déjà complété · <?= $dejaParticipe['score'] ?>/5 en <?= formatTemps((int)$dejaParticipe['temps_secondes']) ?></div>
    <?php endif; ?>

    <!-- Streak + historique 7 jours -->
    <?php
    // Construire les 7 derniers jours (du plus ancien au plus récent)
    $histoIndex = array_column($historiqueDefi, 'score', 'date_defi');
    $jours7 = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = (clone new DateTime($defiDate, $tz))->modify("-{$i} day");
        $ds = $d->format('Y-m-d');
        $jours7[] = [
            'date'    => $ds,
            'label'   => $d->format('d/m'),
            'score'   => $histoIndex[$ds] ?? null,
            'isToday' => $ds === $defiDate,
        ];
    }
    ?>
    <div class="streak-histo-wrap">
        <div class="streak-badge">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 22c4.418 0 8-3.134 8-7 0-3.5-2.5-5.5-4-7-.5 2-1.5 3-3 3 0-2-1-4-4-6 0 4-4 5-4 10 0 3.866 3.582 7 7 7z" fill="var(--gold)" opacity=".25"/><path d="M12 22c4.418 0 8-3.134 8-7 0-3.5-2.5-5.5-4-7-.5 2-1.5 3-3 3 0-2-1-4-4-6 0 4-4 5-4 10 0 3.866 3.582 7 7 7z" stroke="var(--gold)" stroke-width="1.5" stroke-linejoin="round"/></svg>
            <div>
                <div class="streak-count"><?= $streakCount ?></div>
                <div class="streak-label">jour<?= $streakCount > 1 ? 's' : '' ?> de<br>streak</div>
            </div>
        </div>

        <div class="histo-days">
            <?php foreach ($jours7 as $j): ?>
            <?php
                $cls = 'histo-dot';
                $inner = '–';
                if ($j['score'] !== null) {
                    $inner = $j['score'] . '/5';
                    $cls  .= $j['score'] === 5 ? ' perfect' : ' done';
                } elseif ($j['isToday']) {
                    $cls  .= ' today';
                    $inner = '?';
                }
            ?>
            <div class="histo-day">
                <div class="<?= $cls ?>"><?= $inner ?></div>
                <div class="histo-date"><?= $j['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ══ RÉSULTATS (juste après soumission) ═══════════════════════ -->
<?php if ($resultatAffiche !== null): ?>

<div class="result-summary" style="margin-bottom:28px;">
    <div class="result-score <?= $resultatAffiche['reussi'] ? 'success' : 'fail' ?>"
         id="resultScore"
         data-pct="<?= $resultatAffiche['pourcentage'] ?>"
         data-score="<?= $resultatAffiche['score'] ?>"
         data-total="5">
        <?= $resultatAffiche['score'] ?>/5
    </div>
    <p><?= $resultatAffiche['pourcentage'] ?>% de réussite · <?= formatTemps($resultatAffiche['temps']) ?></p>
    <?php if ($monRang !== null): ?>
        <div class="defi-rang-badge"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6L12 2z" fill="currentColor" opacity=".3"/><path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6L12 2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg> Tu es <?= $monRang === 1 ? '1er' : $monRang . 'ème' ?> aujourd'hui</div>
    <?php endif; ?>
    <?php if ($resultatAffiche['reussi']): ?>
        <p style="color:var(--success); margin-top:12px; font-weight:600;">Défi relevé !</p>
    <?php else: ?>
        <p style="color:var(--error); margin-top:12px; font-weight:600;">Reviens demain pour le prochain défi !</p>
    <?php endif; ?>
</div>

<div class="result-detail-list" style="margin-bottom:32px;">
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

<?php if ($resultatAffiche['reussi']): ?>
<?php $base = getenv('RAILWAY_ENVIRONMENT') ? '' : '/AlizQuiz'; ?>
<script src="<?= $base ?>/assets/js/confetti.js"></script>
<script>window.addEventListener('load', function(){ setTimeout(lancerConfettis, 300); });</script>
<?php endif; ?>

<!-- ══ QUIZ (pas encore participé) ═════════════════════════════ -->
<?php elseif (!$dejaParticipe): ?>

<div class="card" style="padding:24px 28px 18px; margin-bottom:24px;">
    <p style="color:var(--text-secondary); font-size:0.9rem; margin:0;">
        5 questions · une seule tentative · ton temps est mesuré · même quiz pour tous
    </p>
</div>

<div class="progress-track" style="margin-bottom:20px;"><div class="progress-fill" id="defiProgressFill" style="width:0%;"></div></div>

<form method="POST" action="defi.php" id="defiForm">
    <input type="hidden" name="csrf_token" value="<?= obtenirJetonCSRF() ?>">
    <input type="hidden" name="ids_questions" value="<?= implode(',', array_column($questionsJour, 'id')) ?>">
    <input type="hidden" name="temps_ecoule" id="tempsEcoule" value="0">

    <?php foreach ($questionsJour as $index => $q): ?>
    <div class="question-card" style="margin-bottom:18px;">
        <span class="quiz-theme-tag" style="margin-bottom:14px; display:inline-block;"><?= nettoyer($q['theme']) ?></span>
        <h2>Q<?= $index + 1 ?>. <?= nettoyer($q['question']) ?></h2>
        <div class="options-list">
            <?php for ($i = 1; $i <= 4; $i++): ?>
            <label class="option-item">
                <span class="option-letter"><?= chr(64 + $i) ?></span>
                <input type="radio" name="reponse_<?= $q['id'] ?>" value="<?= $i ?>" required>
                <?= nettoyer($q['reponse' . $i]) ?>
            </label>
            <?php endfor; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary btn-block" style="font-size:1rem; padding:15px;">
        Valider mon défi
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
</form>

<script>
var startTime = Date.now();
document.getElementById('defiForm').addEventListener('submit', function(){
    document.getElementById('tempsEcoule').value = Math.round((Date.now() - startTime) / 1000);
});
(function(){
    var fill = document.getElementById('defiProgressFill');
    function maj(){
        var rep = new Set();
        document.querySelectorAll('input[type=radio]:checked').forEach(function(r){ rep.add(r.name); });
        fill.style.width = (rep.size / 5 * 100).toFixed(1) + '%';
    }
    document.querySelectorAll('input[type=radio]').forEach(function(r){ r.addEventListener('change', maj); });
})();
</script>

<?php endif; ?>

<!-- ══ CLASSEMENT DU JOUR ════════════════════════════════════════ -->
<div class="defi-classement">
    <h3 style="font-size:1rem; font-weight:700; margin-bottom:16px; color:var(--text-secondary);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="vertical-align:-.2em" aria-hidden="true"><circle cx="12" cy="13" r="6" stroke="currentColor" stroke-width="2"/><path d="M8 3l1.5 4.5H5L8 3zM16 3l-1.5 4.5H19L16 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg> Classement du jour
        <span style="font-size:0.78rem; font-weight:400; color:var(--text-muted); margin-left:8px;"><?= count($classementJour) ?> participant<?= count($classementJour) > 1 ? 's' : '' ?></span>
    </h3>

    <?php if (empty($classementJour)): ?>
    <div class="card" style="text-align:center; padding:32px; color:var(--text-muted);">
        Sois le premier à relever le défi aujourd'hui !
    </div>
    <?php else: ?>
    <?php foreach ($classementJour as $i => $r): ?>
    <?php $rang = $i + 1; $rankClass = $rang === 1 ? 'top1' : ($rang === 2 ? 'top2' : ($rang === 3 ? 'top3' : '')); ?>
    <div class="classement-row <?= $r['c_est_moi'] ? 'c-moi' : '' ?>">
        <div class="rank-num <?= $rankClass ?>">
            <?php if ($rang <= 3): ?>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="14" r="7" fill="currentColor" opacity=".15"/><circle cx="12" cy="14" r="7" stroke="currentColor" stroke-width="1.5"/><text x="12" y="18.5" text-anchor="middle" font-size="9" font-weight="800" fill="currentColor" font-family="sans-serif"><?= $rang ?></text></svg>
            <?php else: echo $rang; endif; ?>
        </div>
        <div class="classement-pseudo"><?= nettoyer($r['pseudo']) ?></div>
        <div class="score-stars">
            <?php for ($s = 1; $s <= 5; $s++): ?>
                <?php if ($s <= $r['score']): ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="var(--gold)" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <?php else: ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="var(--text-muted)" stroke-width="2"/></svg>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <div class="classement-score"><?= $r['score'] ?>/5</div>
        <div class="classement-temps"><?= formatTemps((int)$r['temps_secondes']) ?></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ══ THÈME DE DEMAIN ══════════════════════════════════════════ -->
<div style="margin-top:28px; text-align:center;">
    <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:.1em; color:var(--text-muted); margin-bottom:12px;">Prochain défi · <?= $demainDate->format('d/m') ?></p>
    <div style="display:inline-flex; align-items:center; gap:12px; background:var(--bg-glass); border:1px solid var(--border-subtle); border-radius:var(--radius-lg); padding:14px 24px; backdrop-filter:blur(12px); opacity:.75;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z" stroke="var(--violet-bright)" stroke-width="1.5" stroke-linejoin="round"/><path d="M19 16l.75 2.25L22 19l-2.25.75L19 22l-.75-2.25L16 19l2.25-.75L19 16z" stroke="var(--violet-bright)" stroke-width="1.5" stroke-linejoin="round"/><path d="M5 5l.5 1.5L7 7l-1.5.5L5 9l-.5-1.5L3 7l1.5-.5L5 5z" stroke="var(--cyan)" stroke-width="1.5" stroke-linejoin="round"/></svg>
        <span style="font-weight:600; color:var(--text-secondary);"><?= nettoyer($themeDemain['theme']) ?></span>
        <span style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; padding:2px 9px; border-radius:999px; background:<?= $niveauColor[$themeDemain['niveau']] ?>22; color:<?= $niveauColor[$themeDemain['niveau']] ?>; border:1px solid <?= $niveauColor[$themeDemain['niveau']] ?>44;">
            <?= $niveauBadge[$themeDemain['niveau']] ?>
        </span>
    </div>
</div>

</div>

<script>
// Countdown vers prochain reset
var secondsLeft = <?= $secondsUntilReset ?>;
function updateCountdown() {
    var h = Math.floor(secondsLeft / 3600);
    var m = Math.floor((secondsLeft % 3600) / 60);
    var s = secondsLeft % 60;
    document.getElementById('cd-h').textContent = String(h).padStart(2,'0');
    document.getElementById('cd-m').textContent = String(m).padStart(2,'0');
    document.getElementById('cd-s').textContent = String(s).padStart(2,'0');
    if (secondsLeft > 0) {
        secondsLeft--;
        setTimeout(updateCountdown, 1000);
    } else {
        // Reset atteint → recharger la page pour le nouveau défi
        window.location.reload();
    }
}
updateCountdown();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
