<?php
/**
 * profil.php
 * ------------------------------------------------------------
 * Tableau de bord personnel de l'utilisateur connecté :
 *  - nombre de quiz réalisés
 *  - niveau actuel débloqué
 *  - pourcentage moyen de réussite
 *  - historique complet des tentatives
 * Page protégée : accessible uniquement si connecté.
 * ------------------------------------------------------------
 */
require_once __DIR__ . '/includes/securite.php';
require_once __DIR__ . '/includes/fonctions.php';
exigerConnexion();

$utilisateurId = (int) $_SESSION['utilisateur_id'];

// Sauvegarde avatar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['avatar_couleur'])) {
    $couleurs_ok = ['#3D7CFF','#34D399','#F87171','#FBBF24','#A78BFA','#F472B6','#22D3EE','#FB923C'];
    $icones_ok   = ['shield','lock','key','eye','zap','star','code','wifi','heart'];
    $couleur = in_array($_POST['avatar_couleur'], $couleurs_ok) ? $_POST['avatar_couleur'] : '#3D7CFF';
    $icone   = in_array($_POST['avatar_icone'], $icones_ok)   ? $_POST['avatar_icone']   : 'shield';
    try {
        $pdo->prepare('UPDATE utilisateurs SET avatar_couleur=?, avatar_icone=? WHERE id=?')
            ->execute([$couleur, $icone, $utilisateurId]);
    } catch (Throwable $e) {}
    $_SESSION['avatar_couleur'] = $couleur;
    $_SESSION['avatar_icone']   = $icone;
    header('Location: profil.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
$stmt->execute([$utilisateurId]);
$utilisateur = $stmt->fetch();

$avatarCouleur = $utilisateur['avatar_couleur'] ?? '#3D7CFF';
$avatarIcone   = $utilisateur['avatar_icone']   ?? 'shield';

$stats  = statistiquesUtilisateur($pdo, $utilisateurId);
try { verifierBadges($pdo, $utilisateurId); } catch (Exception $e) {}
$badges = getBadges($pdo, $utilisateurId);

$titrePage = 'Mon profil';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-bottom: 0;">
    <div class="section-head" style="margin-bottom: 0;">

        <?php
        $svgIcones = [
            'shield' => '<path d="M12 2L4 5.5V11C4 16.5 7.5 20.5 12 22C16.5 20.5 20 16.5 20 11V5.5L12 2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 12L11 14L15.5 9.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
            'lock'   => '<rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 11V7a4 4 0 0 1 8 0v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="16" r="1.5" fill="currentColor"/>',
            'key'    => '<circle cx="8" cy="15" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M11.5 11.5L20 3M17 6l2 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
            'eye'    => '<path d="M1 12C1 12 5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/>',
            'zap'    => '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
            'star'   => '<polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
            'code'   => '<polyline points="16,18 22,12 16,6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><polyline points="8,6 2,12 8,18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
            'wifi'   => '<path d="M1.5 8.5C5.5 4.5 18.5 4.5 22.5 8.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 12c2-2 12-2 14 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8.5 15.5c1-1 6-1 7 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="19" r="1.5" fill="currentColor"/>',
            'heart'  => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        ];
        $couleurs = ['#3D7CFF','#34D399','#F87171','#FBBF24','#A78BFA','#F472B6','#22D3EE','#FB923C'];
        ?>

        <!-- Avatar affiché -->
        <div class="avatar-preview" style="--av-color:<?= $avatarCouleur ?>">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <?= $svgIcones[$avatarIcone] ?? $svgIcones['shield'] ?>
            </svg>
        </div>

        <span class="hero-eyebrow" style="margin-top:14px"><span class="dot"></span> Niveau actuel : <?= nettoyer(libelleNiveauComplete($utilisateur['niveau_actuel'])) ?></span>
        <h2>Bonjour, <?= nettoyer($utilisateur['pseudo']) ?></h2>
        <p>Inscrit·e depuis le <?= date('d/m/Y', strtotime($utilisateur['date_inscription'])) ?></p>

        <!-- Picker avatar -->
        <form method="POST" class="avatar-picker">
            <p class="avatar-picker-label">Couleur</p>
            <div class="avatar-colors">
                <?php foreach ($couleurs as $c): ?>
                <button type="button" class="av-color-btn <?= $c === $avatarCouleur ? 'selected' : '' ?>"
                    style="background:<?= $c ?>" data-color="<?= $c ?>" onclick="pickColor(this)"></button>
                <?php endforeach; ?>
            </div>
            <p class="avatar-picker-label">Icône</p>
            <div class="avatar-icons">
                <?php foreach ($svgIcones as $slug => $path): ?>
                <button type="button" class="av-icon-btn <?= $slug === $avatarIcone ? 'selected' : '' ?>"
                    data-icon="<?= $slug ?>" onclick="pickIcon(this)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><?= $path ?></svg>
                </button>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="avatar_couleur" id="inputCouleur" value="<?= $avatarCouleur ?>">
            <input type="hidden" name="avatar_icone"   id="inputIcone"   value="<?= $avatarIcone ?>">
            <button type="submit" class="btn btn-primary" style="margin-top:16px">Sauvegarder l'avatar</button>
        </form>

    </div>
</section>

<script>
var svgPaths = {
    shield: '<path d="M12 2L4 5.5V11C4 16.5 7.5 20.5 12 22C16.5 20.5 20 16.5 20 11V5.5L12 2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 12L11 14L15.5 9.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
    lock:   '<rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 11V7a4 4 0 0 1 8 0v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="16" r="1.5" fill="currentColor"/>',
    key:    '<circle cx="8" cy="15" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M11.5 11.5L20 3M17 6l2 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
    eye:    '<path d="M1 12C1 12 5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/>',
    zap:    '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
    star:   '<polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
    code:   '<polyline points="16,18 22,12 16,6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><polyline points="8,6 2,12 8,18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
    wifi:   '<path d="M1.5 8.5C5.5 4.5 18.5 4.5 22.5 8.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M5 12c2-2 12-2 14 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8.5 15.5c1-1 6-1 7 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="19" r="1.5" fill="currentColor"/>',
    heart:  '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
};

function pickColor(btn) {
    document.querySelectorAll('.av-color-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('inputCouleur').value = btn.dataset.color;
    document.querySelector('.avatar-preview').style.setProperty('--av-color', btn.dataset.color);
}

function pickIcon(btn) {
    document.querySelectorAll('.av-icon-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    var slug = btn.dataset.icon;
    document.getElementById('inputIcone').value = slug;
    var svg = document.querySelector('.avatar-preview svg');
    if (svg && svgPaths[slug]) svg.innerHTML = svgPaths[slug];
}
</script>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-label">Quiz réalisés</div>
        <div class="stat-value"><?= $stats['nombre_tentatives'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Niveau actuel</div>
        <div class="stat-value accent"><?= nettoyer(libelleNiveauComplete($utilisateur['niveau_actuel'])) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Moyenne de réussite</div>
        <div class="stat-value"><?= $stats['moyenne'] ?>%</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Meilleur score</div>
        <div class="stat-value accent"><?= $stats['meilleur_score'] ?>%</div>
    </div>
</div>

<!-- Badges -->
<div class="badges-wrapper">
    <h3 class="badges-title">Trophées & Badges</h3>
    <div class="badges-grid">
        <?php foreach (BADGES as $slug => $info):
            $obtenu = array_filter($badges, fn($b) => $b['badge_slug'] === $slug);
            $obtenu = !empty($obtenu);
        ?>
        <div class="badge-card <?= $obtenu ? 'badge-obtained' : 'badge-locked' ?>">
            <span class="badge-emoji"><?= $info['emoji'] ?></span>
            <span class="badge-label"><?= $info['label'] ?></span>
            <span class="badge-desc"><?= $info['desc'] ?></span>
            <?php if (!$obtenu): ?><span class="badge-lock">🔒</span><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!empty($stats['historique'])): ?>
<div class="history-table-wrap" style="margin-bottom: 40px;">
    <h3 style="margin-bottom: 20px;">Progression dans le temps</h3>
    <div class="card" style="padding: 24px 28px;">
        <canvas id="chartProgression" height="200"></canvas>
    </div>
</div>
<script>
(function(){
    var data = <?= json_encode(array_map(function($r){ return [
        'date'   => date('d/m', strtotime($r['date_resultat'])),
        'pct'    => (float)$r['pourcentage'],
        'niveau' => $r['niveau'],
        'reussi' => (bool)$r['reussi'],
    ]; }, array_reverse($stats['historique']))) ?>;

    var canvas = document.getElementById('chartProgression');
    canvas.width = canvas.parentElement.offsetWidth - 56;
    var ctx = canvas.getContext('2d');
    var W = canvas.width, H = canvas.height;
    var pad = { top: 20, right: 20, bottom: 40, left: 48 };
    var innerW = W - pad.left - pad.right;
    var innerH = H - pad.top - pad.bottom;

    var couleurs = { debutant: '#34D399', intermediaire: '#6FA1FF', expert: '#A78BFA' };

    function xOf(i) { return pad.left + (data.length <= 1 ? innerW/2 : i / (data.length-1) * innerW); }
    function yOf(pct) { return pad.top + innerH - (pct / 100) * innerH; }

    // Grille
    ctx.strokeStyle = 'rgba(255,255,255,0.05)';
    ctx.lineWidth = 1;
    [0,25,50,75,100].forEach(function(v){
        var y = yOf(v);
        ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W-pad.right, y); ctx.stroke();
        ctx.fillStyle = 'rgba(255,255,255,0.25)';
        ctx.font = '11px Inter, sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText(v+'%', pad.left-8, y+4);
    });

    if (data.length < 2) {
        ctx.fillStyle = 'rgba(255,255,255,0.3)';
        ctx.font = '13px Inter, sans-serif'; ctx.textAlign = 'center';
        ctx.fillText('Faites au moins 2 quiz pour voir la courbe.', W/2, H/2);
        return;
    }

    // Aire de remplissage dégradée
    var grad = ctx.createLinearGradient(0, pad.top, 0, pad.top + innerH);
    grad.addColorStop(0, 'rgba(61,124,255,0.25)');
    grad.addColorStop(1, 'rgba(61,124,255,0.0)');

    ctx.beginPath();
    ctx.moveTo(xOf(0), yOf(data[0].pct));
    data.forEach(function(d,i){ if(i>0) ctx.lineTo(xOf(i), yOf(d.pct)); });
    ctx.lineTo(xOf(data.length-1), pad.top+innerH);
    ctx.lineTo(xOf(0), pad.top+innerH);
    ctx.closePath();
    ctx.fillStyle = grad;
    ctx.fill();

    // Ligne principale
    ctx.beginPath();
    ctx.moveTo(xOf(0), yOf(data[0].pct));
    data.forEach(function(d,i){ if(i>0) ctx.lineTo(xOf(i), yOf(d.pct)); });
    ctx.strokeStyle = '#3D7CFF';
    ctx.lineWidth = 2.5;
    ctx.lineJoin = 'round';
    ctx.stroke();

    // Points
    data.forEach(function(d,i){
        var x = xOf(i), y = yOf(d.pct);
        ctx.beginPath();
        ctx.arc(x, y, 5, 0, Math.PI*2);
        ctx.fillStyle = couleurs[d.niveau] || '#6FA1FF';
        ctx.fill();
        ctx.strokeStyle = '#080C12';
        ctx.lineWidth = 2;
        ctx.stroke();

        // Label date
        if (data.length <= 15 || i % Math.ceil(data.length/10) === 0 || i === data.length-1) {
            ctx.fillStyle = 'rgba(255,255,255,0.4)';
            ctx.font = '10px Inter, sans-serif'; ctx.textAlign = 'center';
            ctx.fillText(d.date, x, H - 8);
        }
    });

    // Légende
    var lvls = [{l:'Débutant',c:'#34D399'},{l:'Intermédiaire',c:'#6FA1FF'},{l:'Expert',c:'#A78BFA'}];
    var lx = pad.left;
    lvls.forEach(function(lv){
        ctx.beginPath(); ctx.arc(lx+6, pad.top-8, 4, 0, Math.PI*2);
        ctx.fillStyle = lv.c; ctx.fill();
        ctx.fillStyle = 'rgba(255,255,255,0.45)';
        ctx.font = '11px Inter, sans-serif'; ctx.textAlign = 'left';
        ctx.fillText(lv.l, lx+14, pad.top-4);
        lx += ctx.measureText(lv.l).width + 36;
    });
})();
</script>
<?php endif; ?>

<div class="history-table-wrap">
    <h3 style="margin-bottom: 18px;">Historique des tentatives</h3>

    <?php if (empty($stats['historique'])): ?>
        <div class="empty-state card">
            <p>Aucune tentative enregistrée pour le moment. Lancez votre premier quiz pour commencer votre progression.</p>
            <br>
            <a href="quiz_debutant.php" class="btn btn-primary">Lancer le quiz Débutant</a>
        </div>
    <?php else: ?>
        <table class="history-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Niveau</th>
                    <th>Score</th>
                    <th>Pourcentage</th>
                    <th>Résultat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['historique'] as $tentative): ?>
                    <tr>
                        <td><?= date('d/m/Y à H:i', strtotime($tentative['date_resultat'])) ?></td>
                        <td><?= nettoyer(libelleNiveau($tentative['niveau'])) ?></td>
                        <td><?= (int) $tentative['score'] ?> / <?= (int) $tentative['total_questions'] ?></td>
                        <td><?= $tentative['pourcentage'] ?>%</td>
                        <td>
                            <?php if ($tentative['reussi']): ?>
                                <span class="badge-tag ok">Réussi</span>
                            <?php else: ?>
                                <span class="badge-tag ko">Échoué</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if ($utilisateur['niveau_actuel'] === 'termine'): ?>
<div class="container" style="margin-bottom: 80px;">
    <div class="card" style="text-align:center;">
        <h3>Félicitations, parcours complet !</h3>
        <p>Vous avez validé les trois niveaux. Récupérez votre certificat virtuel.</p>
        <br>
        <a href="certificat.php" class="btn btn-primary">Voir mon certificat</a>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
