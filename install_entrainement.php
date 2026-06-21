<?php
/**
 * install_entrainement.php
 * Script d'installation unique — crée les tables et insère les questions.
 * À SUPPRIMER après utilisation.
 */
require_once __DIR__ . '/includes/connexion.php';

$steps = [];

try {
    // Table entrainement_questions
    $pdo->exec("CREATE TABLE IF NOT EXISTS entrainement_questions (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        theme         VARCHAR(50) NOT NULL,
        type          ENUM('qcm','association') NOT NULL DEFAULT 'qcm',
        question      TEXT NOT NULL,
        visuel        VARCHAR(50) DEFAULT NULL,
        reponse1      VARCHAR(255) DEFAULT NULL,
        reponse2      VARCHAR(255) DEFAULT NULL,
        reponse3      VARCHAR(255) DEFAULT NULL,
        reponse4      VARCHAR(255) DEFAULT NULL,
        bonne_reponse TINYINT DEFAULT NULL,
        donnees_json  TEXT DEFAULT NULL,
        explication   TEXT NOT NULL
    ) ENGINE=InnoDB");
    $steps[] = ['ok', 'Table <b>entrainement_questions</b> créée'];

    // Table entrainement_resultats
    $pdo->exec("CREATE TABLE IF NOT EXISTS entrainement_resultats (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        utilisateur_id INT NOT NULL,
        theme          VARCHAR(50) NOT NULL,
        score          INT NOT NULL,
        total_questions INT NOT NULL,
        pourcentage    DECIMAL(5,2) NOT NULL,
        date_resultat  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $steps[] = ['ok', 'Table <b>entrainement_resultats</b> créée'];

    // Vider si déjà des questions
    $pdo->exec("DELETE FROM entrainement_questions");

    // Insertion des questions
    $stmt = $pdo->prepare("INSERT INTO entrainement_questions
        (theme, type, question, visuel, reponse1, reponse2, reponse3, reponse4, bonne_reponse, donnees_json, explication)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)");

    $questions = [
        // ── MOTS DE PASSE ──────────────────────────────────────────
        ['passwords','qcm','Parmi ces quatre mots de passe, lequel résiste le mieux à une attaque par dictionnaire ?','password_strength',
            'azerty123','P@ssw0rd!2024','sophie1990','abcdefgh',2,null,
            'P@ssw0rd!2024 cumule longueur (+12 car.), majuscules, minuscules, chiffres et symboles. Les autres sont soit trop courts, soit des mots ou dates prévisibles facilement devinés par un dictionnaire.'],

        ['passwords','association','Classez ces mots de passe selon leur niveau de sécurité.',null,
            null,null,null,null,null,
            '{"cats":["🔒 Sécurisé","⚠️ Non sécurisé"],"items":[{"l":"Correct-Horse-Battery!9","c":0},{"l":"P@ssword!","c":1},{"l":"Xk#9mLq$2vR!","c":0},{"l":"ILoveYou2024","c":1}]}',
            '"P@ssword!" est un classique connu des attaquants malgré les substitutions. "ILoveYou2024" est une phrase prévisible. Une longue phrase aléatoire ou une chaîne complexe restent les meilleures options.'],

        ['passwords','qcm','Un attaquant récupère la base de données d\'un site. Les mots de passe sont stockés en clair. Quelle est la conséquence directe ?','password_manager',
            'L\'attaquant doit encore les déchiffrer','Il accède immédiatement à tous les comptes','Les mots de passe sont inutilisables sans le sel cryptographique','L\'antivirus du serveur bloque l\'accès',2,null,
            'Un stockage en clair signifie lecture directe, sans aucune opération supplémentaire. C\'est pourquoi les mots de passe doivent être hashés (bcrypt, Argon2) : même en cas de fuite, l\'attaquant ne peut pas les utiliser directement.'],

        ['passwords','association','Vrai ou Faux — sans indice sur quelle affirmation est correcte.',null,
            null,null,null,null,null,
            '{"cats":["✓ Vrai","✗ Faux"],"items":[{"l":"Un hash bcrypt est réversible avec la bonne clé","c":1},{"l":"La 2FA protège même si le mot de passe fuite","c":0},{"l":"Un mot de passe de 8 car. suffit si complexe","c":1},{"l":"Réutiliser un mot de passe multiplie les risques","c":0}]}',
            'bcrypt est un algorithme de hachage à sens unique — non réversible. 8 caractères se cassent en quelques heures en 2024. La 2FA est essentielle car elle bloque l\'accès même avec un mot de passe compromis.'],

        // ── PHISHING ───────────────────────────────────────────────
        ['phishing','qcm','Cet email est-il légitime ? Si non, quel est l\'indice le plus fiable ?','email_phishing',
            'Oui, le ton urgent est normal pour une banque','Non — l\'adresse de l\'expéditeur contient des substitutions de caractères','Non — un email légitime ne contient jamais de lien','Non — une banque n\'envoie jamais d\'email',2,null,
            '"cr3dit-agri0ole.net" remplace des lettres par des chiffres (3→e, 0→o) pour imiter une adresse officielle. L\'urgence renforce la manipulation, mais c\'est l\'adresse qui trahit l\'attaque.'],

        ['phishing','association','Face à cet email suspect, classez ces réactions.',null,
            null,null,null,null,null,
            '{"cats":["👍 Bon réflexe","👎 À éviter"],"items":[{"l":"Survoler le lien pour voir l\'URL réelle","c":0},{"l":"Répondre pour demander confirmation","c":1},{"l":"Contacter la banque via son numéro officiel","c":0},{"l":"Transférer l\'email à ses contacts pour les prévenir","c":1}]}',
            'Répondre à un phishing confirme que votre adresse est active. Transférer l\'email répand l\'attaque. Survoler un lien (sans cliquer) révèle la vraie destination. Toujours contacter l\'organisme via ses coordonnées officielles.'],

        ['phishing','qcm','Quel élément précis dans cette URL doit vous alerter ?','url_fake',
            'La présence du protocole HTTPS','Le mot "credlt" (i remplacé par l) et "secure" en sous-domaine trompeur','L\'extension .com','Le chemin /login',2,null,
            'Le "i" de "credit" est remplacé par un "l" (credlt) — quasiment invisible. Le sous-domaine "secure" est un classique pour inspirer confiance. HTTPS et /login sont légitimes en eux-mêmes.'],

        ['phishing','association','Vrai ou Faux sur le phishing ?',null,
            null,null,null,null,null,
            '{"cats":["✓ Vrai","✗ Faux"],"items":[{"l":"Un site HTTPS peut héberger du phishing","c":0},{"l":"Le spear phishing cible n\'importe qui au hasard","c":1},{"l":"Un expéditeur peut être usurpé (spoofing)","c":0},{"l":"Signaler un phishing n\'a aucun effet","c":1}]}',
            'Le spear phishing cible une personne ou organisation précise avec des infos personnalisées. Le spoofing permet d\'afficher n\'importe quelle adresse expéditeur. Signaler permet aux fournisseurs de bloquer la campagne.'],

        // ── MALWARES ───────────────────────────────────────────────
        ['malwares','qcm','Votre PC affiche ce message. Quelle est la première action à effectuer ?','ransomware',
            'Payer la rançon pour récupérer les fichiers rapidement','Couper immédiatement la connexion réseau pour stopper la propagation','Redémarrer en mode sans échec','Lancer un antivirus en ligne',2,null,
            'Isoler la machine stoppe la propagation aux autres postes du réseau — priorité absolue. L\'antivirus ne pourra pas déchiffrer les fichiers déjà chiffrés. Payer finance les attaquants sans garantie de récupération.'],

        ['malwares','association','Classez ces programmes.',null,
            null,null,null,null,null,
            '{"cats":["✅ Légitime","☠️ Malveillant"],"items":[{"l":"Keylogger","c":1},{"l":"Pare-feu","c":0},{"l":"Rootkit","c":1},{"l":"VPN","c":0}]}',
            'Un keylogger capture tout ce que vous tapez. Un rootkit masque sa présence au système d\'exploitation pour persister indétecté. Le pare-feu et le VPN sont des outils de protection légitimes.'],

        ['malwares','qcm','Ce fichier est téléchargé depuis un forum. Quelle caractéristique est la plus suspecte ?','trojan',
            'L\'extension .exe','Le mot "GRATUIT" dans le nom','Le fait qu\'il soit hébergé sur un forum','L\'icône de jeu vidéo',2,null,
            '"GRATUIT" sur un contenu normalement payant est un signal d\'alarme classique. Les trojans se dissimulent souvent dans des cracks et logiciels piratés. L\'extension .exe est normale pour un jeu Windows.'],

        ['malwares','association','Vrai ou Faux sur les malwares ?',null,
            null,null,null,null,null,
            '{"cats":["✓ Vrai","✗ Faux"],"items":[{"l":"Un worm se propage sans action de l\'utilisateur","c":0},{"l":"Un antivirus détecte 100% des menaces","c":1},{"l":"Un malware peut rester dormant des semaines","c":0},{"l":"Seuls les fichiers .exe peuvent être infectés","c":1}]}',
            'Les worms exploitent des failles réseau pour se propager seuls. Aucun antivirus n\'est exhaustif — les malwares zero-day passent souvent. Les macros Office, PDF et scripts JS peuvent aussi être vecteurs.'],

        // ── RÉSEAUX ────────────────────────────────────────────────
        ['reseaux','qcm','Vous voyez ces deux réseaux Wi-Fi dans un café. Laquelle de ces affirmations est exacte ?','wifi_choice',
            'Le réseau avec le plus de barres est toujours le bon','Un réseau sans mot de passe est forcément dangereux','Un attaquant peut créer un réseau imitant celui du café pour intercepter le trafic','Le réseau "Free" appartient à l\'opérateur Free',3,null,
            'Un "evil twin" est un faux point d\'accès qui imite un réseau légitime. Connecté dessus, tout votre trafic non chiffré passe par l\'attaquant. Toujours vérifier le nom exact auprès du personnel.'],

        ['reseaux','association','Classez ces pratiques réseau.',null,
            null,null,null,null,null,
            '{"cats":["🔒 Sécurisé","⚠️ Risqué"],"items":[{"l":"Activer le pare-feu local","c":0},{"l":"Désactiver le Bluetooth en public","c":0},{"l":"Utiliser HTTP pour les achats en ligne","c":1},{"l":"Se connecter à un hotspot inconnu sans VPN","c":1}]}',
            'HTTP ne chiffre pas les données — un attaquant sur le même réseau peut lire vos informations bancaires. Le Bluetooth en public expose à des attaques (bluejacking, MITM). Activer le pare-feu bloque les connexions non sollicitées.'],

        ['reseaux','qcm','Une attaque de type "Man-in-the-Middle" (MITM) consiste à…','vpn_tunnel',
            'Forcer l\'arrêt d\'un service réseau par saturation','S\'interposer entre deux parties pour lire ou modifier leurs échanges','Injecter du code malveillant dans une base de données','Scanner les ports ouverts d\'une machine',2,null,
            'Dans une MITM, l\'attaquant se place entre vous et le serveur : il peut lire, modifier ou rejouer les messages. HTTPS et les VPN chiffrent le trafic pour rendre cette attaque inefficace même si l\'interception réussit.'],

        ['reseaux','association','Vrai ou Faux sur les protocoles réseau ?',null,
            null,null,null,null,null,
            '{"cats":["✓ Vrai","✗ Faux"],"items":[{"l":"DNS over HTTPS chiffre les requêtes DNS","c":0},{"l":"Un VPN empêche totalement le tracking","c":1},{"l":"WPA2 peut être vulnérable à KRACK","c":0},{"l":"Un pare-feu suffit à sécuriser un réseau","c":1}]}',
            'WPA2 a été vulnérable à l\'attaque KRACK (Key Reinstallation Attack) en 2017. Un VPN masque votre IP mais pas les cookies ou fingerprints. La sécurité réseau nécessite plusieurs couches (défense en profondeur).'],

        // ── DONNÉES PERSONNELLES ───────────────────────────────────
        ['donnees','qcm','Une entreprise collecte vos données sans base légale. Que pouvez-vous faire en vertu du RGPD ?','rgpd',
            'Rien, si vous avez accepté les CGU','Porter plainte auprès de la CNIL et demander la suppression de vos données','Saisir uniquement la justice pénale','Envoyer un email recommandé à l\'entreprise sous 48h',2,null,
            'La CNIL (Commission Nationale de l\'Informatique et des Libertés) est l\'autorité compétente en France. Elle peut sanctionner les entreprises jusqu\'à 4% de leur chiffre d\'affaires mondial ou 20M€.'],

        ['donnees','association','Quelle base légale peut justifier le traitement de ces données ?',null,
            null,null,null,null,null,
            '{"cats":["📋 Donnée personnelle","🔓 Non personnelle"],"items":[{"l":"Adresse IP dynamique","c":0},{"l":"Données agrégées et anonymisées","c":1},{"l":"Photo de profil avec votre visage","c":0},{"l":"Statistiques de vente par région","c":1}]}',
            'Une IP dynamique est considérée comme donnée personnelle par la CJUE car elle peut identifier un utilisateur. Des données vraiment anonymisées (non réidentifiables) ne sont plus soumises au RGPD.'],

        ['donnees','qcm','Une app mobile demande l\'accès à vos contacts, votre localisation et votre appareil photo pour... afficher la météo. Que faire ?',null,
            'Accepter, c\'est sûrement pour personnaliser l\'expérience','Refuser les permissions non nécessaires à la fonction de l\'app','Désinstaller et ne pas utiliser d\'apps météo','Tout accepter puis désactiver dans les paramètres',2,null,
            'Le principe de minimisation du RGPD impose de ne collecter que les données strictement nécessaires. Une app météo n\'a besoin que de la localisation. Refuser les autres permissions est votre droit.'],

        ['donnees','association','Vrai ou Faux sur la vie privée numérique ?',null,
            null,null,null,null,null,
            '{"cats":["✓ Vrai","✗ Faux"],"items":[{"l":"Le mode navigation privée vous rend anonyme","c":1},{"l":"Les métadonnées peuvent révéler votre localisation","c":0},{"l":"Supprimer une app supprime toutes vos données","c":1},{"l":"Un VPN chiffre votre trafic local","c":0}]}',
            'La navigation privée cache l\'historique local mais pas votre IP ni votre activité au FAI. Supprimer une app ne supprime pas les données déjà collectées sur les serveurs. Les métadonnées EXIF d\'une photo contiennent souvent les coordonnées GPS.'],

        // ── CHIFFREMENT ────────────────────────────────────────────
        ['chiffrement','qcm','Vous voyez ce cadenas dans votre navigateur. Qu\'est-ce que HTTPS ne garantit PAS ?','https_lock',
            'Le chiffrement des données en transit','L\'identité et la légitimité du site web','L\'utilisation du protocole TLS','La confidentialité des données échangées sur le réseau',2,null,
            'Un certificat HTTPS ne prouve que l\'identité technique du serveur, pas la légitimité de l\'entité derrière. Des sites de phishing utilisent HTTPS avec des certificats valides. Toujours vérifier l\'URL et l\'organisme.'],

        ['chiffrement','association','Symétrique ou asymétrique ?',null,
            null,null,null,null,null,
            '{"cats":["🔑 Symétrique","🗝️ Asymétrique"],"items":[{"l":"AES-256","c":0},{"l":"RSA-2048","c":1},{"l":"Même clé pour chiffrer et déchiffrer","c":0},{"l":"Utilisé dans l\'échange de clés TLS","c":1}]}',
            'AES est symétrique : rapide, mais nécessite un canal sécurisé pour partager la clé. RSA est asymétrique : la clé publique chiffre, la clé privée déchiffre. TLS utilise l\'asymétrique pour échanger une clé symétrique de session.'],

        ['chiffrement','qcm','SHA-256 produit toujours un hash de 256 bits, quelle que soit la taille du fichier d\'entrée. Quelle propriété cela illustre-t-il ?',null,
            'La réversibilité du hachage','La résistance aux collisions','La longueur fixe de la sortie (déterminisme)','Le chiffrement par blocs',3,null,
            'Un algorithme de hachage produit toujours une sortie de taille fixe (256 bits pour SHA-256), peu importe si l\'entrée fait 1 octet ou 1 Go. Cette propriété, combinée à la non-réversibilité, en fait un outil idéal pour stocker des mots de passe.'],

        ['chiffrement','association','Vrai ou Faux sur le chiffrement ?',null,
            null,null,null,null,null,
            '{"cats":["✓ Vrai","✗ Faux"],"items":[{"l":"Deux fichiers différents peuvent avoir le même hash SHA-256","c":1},{"l":"Le chiffrement de bout en bout protège contre l\'opérateur","c":0},{"l":"Un hash MD5 est sûr pour stocker des mots de passe","c":1},{"l":"TLS 1.3 est plus rapide et plus sûr que TLS 1.2","c":0}]}',
            'Une collision SHA-256 est théoriquement possible mais infiniment improbable (2^128 résistance). MD5 est cassé : des collisions ont été démontrées en pratique. TLS 1.3 supprime les algorithmes obsolètes et réduit la latence du handshake.'],
    ];

    foreach ($questions as $q) {
        $stmt->execute($q);
    }
    $steps[] = ['ok', count($questions) . ' questions insérées'];

} catch (Exception $e) {
    $steps[] = ['error', 'Erreur : ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Installation Entraînement</title>
<style>
body { font-family: sans-serif; background: #0A0E14; color: #EAEEF6; padding: 40px; max-width: 600px; margin: 0 auto; }
h1 { color: #6FA1FF; margin-bottom: 24px; }
.step { padding: 10px 16px; border-radius: 8px; margin-bottom: 8px; font-size: 0.95rem; }
.ok    { background: #34D39922; border: 1px solid #34D39940; color: #34D399; }
.error { background: #F8717122; border: 1px solid #F8717140; color: #F87171; }
.done { margin-top: 28px; background: #3D7CFF1A; border: 1px solid #3D7CFF40; padding: 16px 20px; border-radius: 10px; }
a { color: #6FA1FF; }
</style>
</head>
<body>
<h1>⚙️ Installation Entraînement</h1>
<?php foreach ($steps as [$type, $msg]): ?>
<div class="step <?= $type ?>">
    <?= $type === 'ok' ? '✓' : '✗' ?> <?= $msg ?>
</div>
<?php endforeach; ?>
<?php if (!in_array('error', array_column($steps, 0))): ?>
<div class="done">
    ✅ Installation terminée ! <a href="entrainement.php">Aller sur la page Entraînement</a>
    <br><br>
    <small style="color:#5C6478">⚠️ Pense à supprimer ce fichier : <code>install_entrainement.php</code></small>
</div>
<?php endif; ?>
</body>
</html>
