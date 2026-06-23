<?php
/**
 * entrainement_quiz.php
 * Quiz interactif par thème : question par question,
 * images SVG, drag & drop, explication après chaque réponse.
 */
require_once __DIR__ . '/includes/securite.php';
require_once __DIR__ . '/includes/fonctions.php';
exigerConnexion();

$themesValides = ['passwords', 'phishing', 'malwares', 'reseaux', 'donnees', 'chiffrement'];
$theme = $_GET['theme'] ?? '';
if (!in_array($theme, $themesValides, true)) {
    header('Location: entrainement.php');
    exit;
}

$labelsThemes = [
    'passwords'   => 'Mots de passe',
    'phishing'    => 'Phishing',
    'malwares'    => 'Malwares',
    'reseaux'     => 'Réseaux',
    'donnees'     => 'Données personnelles',
    'chiffrement' => 'Chiffrement',
];

$userId = (int) $_SESSION['utilisateur_id'];

// Sauvegarde du résultat (POST AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = (int)($_POST['score'] ?? 0);
    $total = (int)($_POST['total'] ?? 1);
    $score = max(0, min($score, $total));
    $pourcentage = round(($score / $total) * 100, 2);
    $stmt = $pdo->prepare('INSERT INTO entrainement_resultats (utilisateur_id, theme, score, total_questions, pourcentage) VALUES (?,?,?,?,?)');
    $stmt->execute([$userId, $theme, $score, $total, $pourcentage]);
    echo json_encode(['ok' => true]);
    exit;
}

// Chargement des questions
$stmt = $pdo->prepare('SELECT * FROM entrainement_questions WHERE theme = ? ORDER BY RAND()');
$stmt->execute([$theme]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titrePage = $labelsThemes[$theme];
require_once __DIR__ . '/includes/header.php';

// SVG visuels par clé
function getVisuel(string $key): string {
    return match ($key) {

        // 4 mots de passe en texte brut — aucun indicateur de force
        'password_strength' => '<div class="q-visual"><div class="pw-visual"><div class="pw-row"><span class="pw-label">azerty123</span></div><div class="pw-row"><span class="pw-label">sophie1990</span></div><div class="pw-row"><span class="pw-label">P@ssw0rd!2024</span></div><div class="pw-row"><span class="pw-label">abcdefgh</span></div></div></div>',

        // Coffre-fort : contexte neutre, pas de lien avec la bonne réponse
        'password_manager' => '<div class="q-visual"><div class="pm-visual"><div class="pm-vault"><svg width="32" height="32" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="3" stroke="#6FA1FF" stroke-width="1.6"/><circle cx="12" cy="12" r="4" stroke="#6FA1FF" stroke-width="1.6"/><circle cx="12" cy="12" r="1.5" fill="#6FA1FF"/><path d="M16 12h2M6 12h2M12 6V8M12 16v2" stroke="#6FA1FF" stroke-width="1.4" stroke-linecap="round"/></svg><span>Coffre-fort chiffré</span></div><div class="pm-keys"><span class="pm-key">service.a ••••••••</span><span class="pm-key">service.b ••••••••</span><span class="pm-key">service.c ••••••••</span></div></div></div>',

        // Email neutre : pas de ⚠ rouge, pas de texte "DANGER", l'adresse reste trompeuse mais non signalée
        'email_phishing' => '<div class="q-visual"><div class="email-visual"><div class="email-header"><span class="email-from">De : service-client@cr3dit-agri0ole.net</span></div><div class="email-body"><div class="email-subject">URGENT : Votre compte va être suspendu</div><div class="email-excerpt">Cher client, nous avons détecté une activité suspecte. Veuillez confirmer vos informations <span class="email-link-fake">en cliquant ici →</span> dans les 24h sous peine de suspension.</div></div></div></div>',

        // UNE seule URL suspecte à analyser, pas de ✓/✗
        'url_fake' => '<div class="q-visual"><div class="url-visual"><div class="url-bar" style="border-color:var(--border-glow)"><span class="url-lock">🔒</span><span style="font-family:monospace;font-size:0.82rem">https://credlt-agricole-secure.com/login</span></div></div></div>',

        // Ransomware : contexte de la situation, pas d'indication sur la bonne action
        'ransomware' => '<div class="q-visual"><div class="ransom-visual"><div class="ransom-files"><span class="ransom-file ransom-locked">📄🔒 rapport.docx</span><span class="ransom-file ransom-locked">🖼️🔒 photos.zip</span><span class="ransom-file ransom-locked">📊🔒 comptes.xlsx</span></div><div class="ransom-msg"><span class="ransom-skull">☠️</span><span>Vos fichiers ont été chiffrés.<br>Payez 500€ en Bitcoin.</span></div></div></div>',

        // Trojan : contexte neutre, le label "porte dérobée" est retiré
        'trojan' => '<div class="q-visual"><div class="trojan-visual"><div class="trojan-app"><div class="trojan-icon">🎮</div><div class="trojan-label">FlappyBird_v2_GRATUIT.exe</div></div></div></div>',

        // Wi-Fi : deux réseaux identiques visuellement, aucun signal de danger
        'wifi_choice' => '<div class="q-visual"><div class="wifi-visual"><div class="wifi-network" style="background:var(--bg-panel-raised);color:var(--text-primary)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1.5 8.5C5.5 4.5 18.5 4.5 22.5 8.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 12c2-2 12-2 14 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8.5 15.5c1-1 6-1 7 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="19" r="1.5" fill="currentColor"/></svg><span>Cafe_Wifi</span></div><div class="wifi-network" style="background:var(--bg-panel-raised);color:var(--text-primary)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1.5 8.5C5.5 4.5 18.5 4.5 22.5 8.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 12c2-2 12-2 14 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8.5 15.5c1-1 6-1 7 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="19" r="1.5" fill="currentColor"/></svg><span>Cafe_Wifi_Free</span></div></div></div>',

        // VPN : schéma neutre sans label "Tunnel chiffré" (ça serait la réponse)
        'vpn_tunnel' => '<div class="q-visual"><div class="vpn-visual"><div class="vpn-node">Vous<br><small>📱</small></div><div class="vpn-arrow"><div class="vpn-line"></div></div><div class="vpn-node">Serveur<br><small>🖥️</small></div><div class="vpn-arrow2"><div class="vpn-line2"></div></div><div class="vpn-node">Internet<br><small>🌐</small></div></div></div>',

        // RGPD : drapeau EU neutre, sans lister les droits (ce serait la réponse)
        'rgpd' => '<div class="q-visual"><div class="rgpd-visual"><div class="rgpd-eu">🇪🇺</div><div style="font-size:0.88rem;color:var(--text-secondary)">Règlement Général sur la Protection des Données<br><span style="color:var(--text-muted);font-size:0.78rem">En vigueur depuis mai 2018</span></div></div></div>',

        // HTTPS : barre de navigateur neutre, sans ✓/? qui révèlent la réponse
        'https_lock' => '<div class="q-visual"><div class="https-visual"><div class="https-bar"><span class="https-lock-icon">🔒</span><span class="https-url">https://credlt-agricole-secure.com</span></div></div></div>',

        default => ''
    };
}
?>

<div class="entr-quiz-wrapper">

    <!-- En-tête du quiz -->
    <div class="entr-quiz-header">
        <a href="entrainement.php" class="entr-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Entraînement
        </a>
        <h2><?= htmlspecialchars($labelsThemes[$theme]) ?></h2>
        <div class="entr-progress-wrap">
            <div class="entr-progress-bar"><div class="entr-progress-fill" id="progressFill"></div></div>
            <span class="entr-progress-label" id="progressLabel">1 / <?= count($questions) ?></span>
        </div>
    </div>

    <!-- Zone quiz -->
    <div id="quizZone" class="entr-quiz-zone">
        <div id="screenQuestion" class="entr-screen"></div>
        <div id="screenResult" class="entr-screen hidden"></div>
    </div>

</div>

<script>
const THEME = <?= json_encode($theme) ?>;
const QUESTIONS = <?= json_encode($questions) ?>;
const VISUALS = {
<?php foreach ($questions as $q): if (!$q['visuel']) continue; ?>
    <?= json_encode($q['visuel']) ?>: <?= json_encode(getVisuel($q['visuel'])) ?>,
<?php endforeach; ?>
};
const TOTAL = QUESTIONS.length;
let current = 0;
let score = 0;
let ddState = {};

// ── Utils ──────────────────────────────────────────────────────────
const $id = id => document.getElementById(id);
const $qs = s => document.querySelector(s);

function setProgress(idx) {
    const pct = (idx / TOTAL) * 100;
    $id('progressFill').style.width = pct + '%';
    $id('progressLabel').textContent = (idx + 1 > TOTAL ? TOTAL : idx + 1) + ' / ' + TOTAL;
}

// ── Affichage question ─────────────────────────────────────────────
function showQuestion(idx) {
    if (idx >= TOTAL) { showFinal(); return; }
    setProgress(idx);
    const q = QUESTIONS[idx];
    const screen = $id('screenQuestion');
    screen.classList.remove('hidden');
    $id('screenResult').classList.add('hidden');

    let html = '';

    // Visuel
    if (q.visuel && VISUALS[q.visuel]) {
        html += VISUALS[q.visuel];
    }

    html += `<p class="entr-q-text">${escHtml(q.question)}</p>`;

    if (q.type === 'qcm') {
        html += buildQCM(q);
    } else {
        html += buildDragDrop(q);
    }

    // Zone explication (masquée)
    html += `<div id="explication" class="entr-expl hidden">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        <span>${escHtml(q.explication)}</span>
    </div>`;

    html += `<button id="btnNext" class="btn btn-primary entr-btn-next hidden" onclick="nextQ()">
        ${idx + 1 < TOTAL ? 'Question suivante' : 'Voir mon score'}
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 12H19M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>`;

    screen.innerHTML = html;

    if (q.type === 'association') initDragDrop(q);
}

// ── QCM ───────────────────────────────────────────────────────────
let selectedAnswer = null;

function buildQCM(q) {
    // Ordre d'affichage mélangé : on garde la position d'origine
    // (1 à 4) dans data-pos pour ne pas fausser la bonne réponse.
    const order = [1, 2, 3, 4];
    for (let k = order.length - 1; k > 0; k--) {
        const j = Math.floor(Math.random() * (k + 1));
        [order[k], order[j]] = [order[j], order[k]];
    }
    selectedAnswer = null;
    let html = '<div class="entr-options">';
    order.forEach(function (pos) {
        html += `<button class="entr-option" data-pos="${pos}" onclick="selectOption(${pos})">${escHtml(q['reponse' + pos])}</button>`;
    });
    html += '</div>';
    html += `<button id="btnValidateQcm" class="btn btn-ghost entr-btn-validate" onclick="confirmQCM()" disabled>Valider</button>`;
    return html;
}

function selectOption(chosen) {
    selectedAnswer = chosen;
    document.querySelectorAll('.entr-option').forEach(function (btn) {
        btn.classList.toggle('entr-option-selected', parseInt(btn.dataset.pos) === chosen);
    });
    const valBtn = document.getElementById('btnValidateQcm');
    if (valBtn) valBtn.disabled = false;
}

function confirmQCM() {
    if (selectedAnswer === null) return;
    const q = QUESTIONS[current];
    const correct = parseInt(q.bonne_reponse);
    const chosen = selectedAnswer;
    const valBtn = document.getElementById('btnValidateQcm');
    if (valBtn) valBtn.disabled = true;
    document.querySelectorAll('.entr-option').forEach(function (btn) {
        const pos = parseInt(btn.dataset.pos);
        btn.disabled = true;
        btn.classList.remove('entr-option-selected');
        if (pos === correct) btn.classList.add('entr-option-correct');
        else if (pos === chosen) btn.classList.add('entr-option-wrong');
    });
    if (chosen === correct) score++;
    revealExpl();
}

// ── Drag & Drop ────────────────────────────────────────────────────
function buildDragDrop(q) {
    const data = JSON.parse(q.donnees_json);
    let html = '<div class="entr-dd">';

    // Items draggables
    html += '<div class="entr-dd-items" id="ddItems">';
    data.items.forEach((item, i) => {
        html += `<div class="entr-dd-item" draggable="true" data-idx="${i}" id="ddItem${i}">${escHtml(item.l)}</div>`;
    });
    html += '</div>';

    // Zones de dépôt
    html += '<div class="entr-dd-zones">';
    data.cats.forEach((cat, ci) => {
        html += `<div class="entr-dd-zone" id="ddZone${ci}" data-cat="${ci}">
            <div class="entr-dd-zone-label">${escHtml(cat)}</div>
            <div class="entr-dd-zone-body" id="ddZoneBody${ci}"></div>
        </div>`;
    });
    html += '</div>';

    html += `<button id="btnValidateDd" class="btn btn-ghost entr-btn-validate" onclick="validateDD()" disabled>Valider</button>`;
    html += '</div>';
    return html;
}

function initDragDrop(q) {
    const data = JSON.parse(q.donnees_json);
    ddState = {};
    data.items.forEach((_, i) => { ddState[i] = null; });

    document.querySelectorAll('.entr-dd-item').forEach(item => {
        item.addEventListener('dragstart', e => {
            e.dataTransfer.setData('itemIdx', item.dataset.idx);
            item.classList.add('dragging');
        });
        item.addEventListener('dragend', () => item.classList.remove('dragging'));
    });

    document.querySelectorAll('.entr-dd-zone').forEach(zone => {
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('drag-over');
            const idx = parseInt(e.dataTransfer.getData('itemIdx'));
            const cat = parseInt(zone.dataset.cat);
            placeItem(idx, cat);
        });
    });
}

function placeItem(itemIdx, catIdx) {
    // Retirer l'item de son ancienne zone si besoin
    const existing = document.getElementById('ddItem' + itemIdx);
    if (existing) existing.remove();

    // Remettre depuis ddItems si déjà placé ailleurs
    ddState[itemIdx] = catIdx;

    const body = document.getElementById('ddZoneBody' + catIdx);
    const el = document.createElement('div');
    el.className = 'entr-dd-item entr-dd-placed';
    el.draggable = true;
    el.dataset.idx = itemIdx;
    el.id = 'ddItem' + itemIdx;
    el.textContent = QUESTIONS[current].donnees_json ? JSON.parse(QUESTIONS[current].donnees_json).items[itemIdx].l : '';
    el.addEventListener('dragstart', e => {
        e.dataTransfer.setData('itemIdx', itemIdx);
        el.classList.add('dragging');
    });
    el.addEventListener('dragend', () => el.classList.remove('dragging'));
    body.appendChild(el);

    // Clic pour renvoyer dans la réserve
    el.addEventListener('dblclick', () => {
        el.remove();
        ddState[itemIdx] = null;
        const reserve = document.getElementById('ddItems');
        const orig = document.createElement('div');
        orig.className = 'entr-dd-item';
        orig.draggable = true;
        orig.dataset.idx = itemIdx;
        orig.id = 'ddItem' + itemIdx;
        orig.textContent = JSON.parse(QUESTIONS[current].donnees_json).items[itemIdx].l;
        orig.addEventListener('dragstart', ev => {
            ev.dataTransfer.setData('itemIdx', itemIdx);
            orig.classList.add('dragging');
        });
        orig.addEventListener('dragend', () => orig.classList.remove('dragging'));
        reserve.appendChild(orig);
        checkDDReady();
    });

    checkDDReady();
}

function checkDDReady() {
    const allPlaced = Object.values(ddState).every(v => v !== null);
    const btn = document.getElementById('btnValidateDd');
    if (btn) btn.disabled = !allPlaced;
}

function validateDD() {
    const q = QUESTIONS[current];
    const data = JSON.parse(q.donnees_json);
    let correct = 0;
    data.items.forEach((item, i) => {
        const el = document.getElementById('ddItem' + i);
        if (ddState[i] === item.c) {
            correct++;
            if (el) el.classList.add('dd-correct');
        } else {
            if (el) el.classList.add('dd-wrong');
        }
    });
    if (correct === data.items.length) score++;
    document.getElementById('btnValidateDd').disabled = true;
    revealExpl();
}

// ── Commun ────────────────────────────────────────────────────────
function revealExpl() {
    const expl = $id('explication');
    if (expl) expl.classList.remove('hidden');
    const next = $id('btnNext');
    if (next) next.classList.remove('hidden');
}

function nextQ() {
    current++;
    showQuestion(current);
}

// ── Résultats ─────────────────────────────────────────────────────
function showFinal() {
    setProgress(TOTAL);
    $id('screenQuestion').classList.add('hidden');
    const pct = Math.round((score / TOTAL) * 100);
    const mention = pct >= 75 ? '🎉 Excellent !' : pct >= 50 ? '👍 Bien joué !' : '💪 Continuez à vous entraîner !';
    const color = pct >= 75 ? '#34D399' : pct >= 50 ? '#FBBF24' : '#F87171';

    let dots = '';
    for (let i = 0; i < TOTAL; i++) dots += `<span class="result-dot" style="background:${i < score ? color : 'var(--border-glow)'}"></span>`;

    $id('screenResult').innerHTML = `
        <div class="entr-final">
            <div class="entr-final-score" style="--score-color:${color}">
                <svg viewBox="0 0 100 100" width="130" height="130">
                    <circle cx="50" cy="50" r="44" fill="none" stroke="var(--bg-panel-raised)" stroke-width="8"/>
                    <circle cx="50" cy="50" r="44" fill="none" stroke="${color}" stroke-width="8"
                        stroke-dasharray="${Math.round(pct * 2.76)} 276"
                        stroke-dashoffset="69" stroke-linecap="round" transform="rotate(-90 50 50)"/>
                </svg>
                <div class="entr-final-pct">${pct}<span>%</span></div>
            </div>
            <p class="entr-final-mention">${mention}</p>
            <p class="entr-final-sub">${score} bonne${score > 1 ? 's' : ''} réponse${score > 1 ? 's' : ''} sur ${TOTAL}</p>
            <div class="entr-final-dots">${dots}</div>
            <div class="entr-final-actions">
                <a href="entrainement_quiz.php?theme=${THEME}" class="btn btn-ghost">Recommencer</a>
                <a href="entrainement.php" class="btn btn-primary">Autres thèmes</a>
            </div>
        </div>`;
    $id('screenResult').classList.remove('hidden');

    // Sauvegarde
    fetch('entrainement_quiz.php?theme=' + THEME, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `score=${score}&total=${TOTAL}`
    });
}

function escHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Démarrage
showQuestion(0);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
