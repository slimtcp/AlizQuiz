<?php
/**
 * includes/fonctions.php
 * ------------------------------------------------------------
 * Fonctions "métier" : logique propre à AlizQuiz (niveaux, scores,
 * statistiques). Séparées de securite.php pour garder une
 * organisation claire (chaque fichier a une responsabilité).
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/connexion.php';

// Seuils de réussite pour débloquer le niveau suivant.
const SEUIL_DEBUTANT      = 70; // % requis pour débloquer "intermediaire"
const SEUIL_INTERMEDIAIRE = 75; // % requis pour débloquer "expert"

// Ordre des niveaux, utilisé pour savoir "où en est" l'utilisateur.
const ORDRE_NIVEAUX = ['debutant', 'intermediaire', 'expert', 'termine'];

/**
 * Retourne les questions d'un niveau donné, mélangées aléatoirement
 * (pour que l'ordre change à chaque tentative).
 */
function recupererQuestions(PDO $pdo, string $niveau): array
{
    $stmt = $pdo->prepare('SELECT * FROM quiz WHERE niveau = ?');
    $stmt->execute([$niveau]);
    $questions = $stmt->fetchAll();
    shuffle($questions);
    return $questions;
}

/**
 * Détermine si un niveau est accessible pour un utilisateur donné,
 * en se basant sur son niveau_actuel enregistré en base.
 */
function niveauAccessible(string $niveauDemande, string $niveauActuelUtilisateur): bool
{
    $positionDemandee = array_search($niveauDemande, ORDRE_NIVEAUX);
    $positionActuelle  = array_search($niveauActuelUtilisateur, ORDRE_NIVEAUX);
    return $positionDemandee !== false && $positionDemandee <= $positionActuelle;
}

/**
 * Enregistre le résultat d'une tentative de quiz dans la table
 * "resultats", et fait progresser l'utilisateur vers le niveau
 * suivant si le seuil de réussite est atteint.
 */
function enregistrerResultat(PDO $pdo, int $utilisateurId, string $niveau, int $score, int $totalQuestions): array
{
    $pourcentage = round(($score / $totalQuestions) * 100, 2);

    $seuil = match ($niveau) {
        'debutant'      => SEUIL_DEBUTANT,
        'intermediaire' => SEUIL_INTERMEDIAIRE,
        'expert'        => 0, // le niveau expert ne débloque rien d'autre qu'un certificat
        default         => 100,
    };

    $reussi = $pourcentage >= $seuil ? 1 : 0;

    // Requête préparée : les valeurs utilisateur ne sont jamais
    // concaténées directement dans le texte SQL.
    $stmt = $pdo->prepare(
        'INSERT INTO resultats (utilisateur_id, niveau, score, total_questions, pourcentage, reussi)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$utilisateurId, $niveau, $score, $totalQuestions, $pourcentage, $reussi]);

    // Progression du niveau si le quiz est réussi et que ce n'est
    // pas déjà le cas (on ne fait jamais régresser un utilisateur).
    if ($reussi) {
        $prochainNiveau = match ($niveau) {
            'debutant'      => 'intermediaire',
            'intermediaire' => 'expert',
            'expert'        => 'termine',
            default         => null,
        };

        $niveauDebloque = null;
        if ($prochainNiveau !== null) {
            $stmtUser = $pdo->prepare('SELECT niveau_actuel FROM utilisateurs WHERE id = ?');
            $stmtUser->execute([$utilisateurId]);
            $niveauActuel = $stmtUser->fetchColumn();

            if (array_search($prochainNiveau, ORDRE_NIVEAUX) > array_search($niveauActuel, ORDRE_NIVEAUX)) {
                $stmtMaj = $pdo->prepare('UPDATE utilisateurs SET niveau_actuel = ? WHERE id = ?');
                $stmtMaj->execute([$prochainNiveau, $utilisateurId]);
                $niveauDebloque = $prochainNiveau;
            }
        }
    }

    // Vérifier et attribuer les badges
    $nouveauxBadges = [];
    try { $nouveauxBadges = verifierBadges($pdo, $utilisateurId); } catch (Exception $e) {}

    return ['pourcentage' => $pourcentage, 'reussi' => (bool) $reussi, 'nouveaux_badges' => $nouveauxBadges, 'niveau_debloque' => $niveauDebloque ?? null];
}

/**
 * Calcule les statistiques globales d'un utilisateur pour son
 * tableau de bord : nombre de tentatives, moyenne, meilleur score.
 */
function statistiquesUtilisateur(PDO $pdo, int $utilisateurId): array
{
    $stmt = $pdo->prepare('SELECT * FROM resultats WHERE utilisateur_id = ? ORDER BY date_resultat DESC');
    $stmt->execute([$utilisateurId]);
    $resultats = $stmt->fetchAll();

    $nombreTentatives = count($resultats);
    $moyenne = 0;
    $meilleurScore = 0;

    if ($nombreTentatives > 0) {
        $sommePourcentages = array_sum(array_column($resultats, 'pourcentage'));
        $moyenne = round($sommePourcentages / $nombreTentatives, 1);
        $meilleurScore = max(array_column($resultats, 'pourcentage'));
    }

    return [
        'historique'        => $resultats,
        'nombre_tentatives' => $nombreTentatives,
        'moyenne'           => $moyenne,
        'meilleur_score'    => $meilleurScore,
    ];
}

/**
 * Libellé humain d'un niveau (pour l'affichage).
 */
function libelleNiveau(string $niveau): string
{
    return match ($niveau) {
        'debutant'      => 'Débutant',
        'intermediaire' => 'Intermédiaire',
        'expert'        => 'Expert',
        'termine'       => 'Toutes les épreuves validées',
        default         => ucfirst($niveau),
    };
}

/**
 * Retourne le libellé du dernier niveau COMPLÉTÉ.
 * niveau_actuel stocke le prochain niveau débloqué, pas celui terminé.
 * debutant     → en cours (aucun complété) → "Débutant"
 * intermediaire → a terminé débutant        → "Débutant"
 * expert        → a terminé intermédiaire   → "Intermédiaire"
 * termine       → a tout terminé            → "Expert"
 */
function libelleNiveauComplete(string $niveauActuel): string
{
    return match ($niveauActuel) {
        'debutant'      => 'Débutant',
        'intermediaire' => 'Débutant',
        'expert'        => 'Intermédiaire',
        'termine'       => 'Expert',
        default         => ucfirst($niveauActuel),
    };
}

// ── Définition des badges ──────────────────────────────────────────
const BADGES = [
    'premier_pas'  => ['label' => 'Premier pas',         'emoji' => '🥇', 'desc' => 'Terminer son premier quiz'],
    'parfait'      => ['label' => 'Parfait',             'emoji' => '🎯', 'desc' => '100% sur un quiz'],
    'en_feu'       => ['label' => 'En feu',              'emoji' => '🔥', 'desc' => '3 quiz réussis d\'affilée'],
    'debutant_ok'  => ['label' => 'Débutant validé',     'emoji' => '🛡️', 'desc' => 'Passer le niveau Débutant'],
    'inter_ok'     => ['label' => 'Intermédiaire validé','emoji' => '⚡', 'desc' => 'Passer le niveau Intermédiaire'],
    'expert_ok'    => ['label' => 'Expert',              'emoji' => '💎', 'desc' => 'Terminer le niveau Expert'],
    'assidu'       => ['label' => 'Assidu',              'emoji' => '🏋️', 'desc' => '10 quiz complétés'],
    'maitre'       => ['label' => 'Maître',              'emoji' => '🎓', 'desc' => 'Tous les niveaux validés'],
];

/**
 * Vérifie et attribue les badges mérités. À appeler après chaque quiz.
 */
function verifierBadges(PDO $pdo, int $userId): array
{
    $nouveaux = [];
    $dejaBadges = $pdo->prepare('SELECT badge_slug FROM badges_utilisateurs WHERE utilisateur_id = ?');
    $dejaBadges->execute([$userId]);
    $obtenus = array_column($dejaBadges->fetchAll(), 'badge_slug');

    $attribuer = function(string $slug) use ($pdo, $userId, &$obtenus, &$nouveaux) {
        if (!in_array($slug, $obtenus)) {
            try {
                $pdo->prepare('INSERT INTO badges_utilisateurs (utilisateur_id, badge_slug) VALUES (?,?)')
                    ->execute([$userId, $slug]);
                $obtenus[] = $slug;
                $nouveaux[] = $slug;
            } catch (Exception $e) {}
        }
    };

    $stats = $pdo->prepare('SELECT COUNT(*) as nb FROM resultats WHERE utilisateur_id = ?');
    $stats->execute([$userId]);
    $nb = (int)$stats->fetchColumn();

    $niveau = $pdo->prepare('SELECT niveau_actuel FROM utilisateurs WHERE id = ?');
    $niveau->execute([$userId]);
    $niveauActuel = $niveau->fetchColumn();

    $derniers = $pdo->prepare('SELECT reussi FROM resultats WHERE utilisateur_id = ? ORDER BY date_resultat DESC LIMIT 3');
    $derniers->execute([$userId]);
    $derniers3 = $derniers->fetchAll();

    $dernier = $pdo->prepare('SELECT pourcentage FROM resultats WHERE utilisateur_id = ? ORDER BY date_resultat DESC LIMIT 1');
    $dernier->execute([$userId]);
    $dernierPct = (float)$dernier->fetchColumn();

    if ($nb >= 1)  $attribuer('premier_pas');
    if ($dernierPct >= 100) $attribuer('parfait');
    if (count($derniers3) === 3 && !in_array(0, array_column($derniers3, 'reussi'))) $attribuer('en_feu');
    if (in_array($niveauActuel, ['intermediaire','expert','termine'])) $attribuer('debutant_ok');
    if (in_array($niveauActuel, ['expert','termine'])) $attribuer('inter_ok');
    if ($niveauActuel === 'termine') $attribuer('expert_ok');
    if ($nb >= 10) $attribuer('assidu');
    if ($niveauActuel === 'termine') $attribuer('maitre');

    return $nouveaux;
}

/**
 * Retourne tous les badges obtenus par un utilisateur.
 */
function getBadges(PDO $pdo, int $userId): array
{
    // Tolérant : si la table badges_utilisateurs n'a pas encore été
    // créée (migration install_badges.php non lancée), on renvoie une
    // liste vide au lieu de faire planter la page profil.
    try {
        $stmt = $pdo->prepare('SELECT badge_slug, obtenu_le FROM badges_utilisateurs WHERE utilisateur_id = ? ORDER BY obtenu_le ASC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}
