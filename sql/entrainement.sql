-- ============================================================
-- AlizQuiz — Entraînement interactif
-- À exécuter dans phpMyAdmin (onglet SQL) sur la base alizquiz
-- ============================================================

USE alizquiz;

CREATE TABLE IF NOT EXISTS entrainement_questions (
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
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entrainement_resultats (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    theme          VARCHAR(50) NOT NULL,
    score          INT NOT NULL,
    total_questions INT NOT NULL,
    pourcentage    DECIMAL(5,2) NOT NULL,
    date_resultat  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- MOTS DE PASSE (4 questions)
-- ============================================================
INSERT INTO entrainement_questions (theme, type, question, visuel, reponse1, reponse2, reponse3, reponse4, bonne_reponse, donnees_json, explication) VALUES

('passwords', 'qcm', 'Lequel de ces mots de passe est le plus robuste ?', 'password_strength',
'azerty123', 'P@ssw0rd!2024', 'sophie1990', 'abcdefgh', 2, NULL,
'P@ssw0rd!2024 mélange majuscules, minuscules, chiffres et symboles sur plus de 12 caractères. C''est la combinaison idéale pour résister aux attaques par force brute.'),

('passwords', 'association', 'Classez ces mots de passe : sécurisé ou non sécurisé ?', NULL,
NULL, NULL, NULL, NULL, NULL,
'{"cats":["🔒 Sécurisé","⚠️ Non sécurisé"],"items":[{"l":"Xk#9mLq$2!","c":0},{"l":"123456","c":1},{"l":"P@ris2024!","c":0},{"l":"monprenom","c":1}]}',
'Un bon mot de passe fait +12 caractères, mélange les types de caractères et n''est pas un mot du dictionnaire ni une info personnelle.'),

('passwords', 'qcm', 'Qu''est-ce qu''un gestionnaire de mots de passe ?', 'password_manager',
'Un carnet papier pour noter ses mots de passe', 'Un logiciel qui génère et stocke vos mots de passe chiffrés', 'Un site web qui mémorise vos identifiants en clair', 'Un type de virus', 2, NULL,
'Un gestionnaire (Bitwarden, 1Password, KeePass…) chiffre tous vos mots de passe avec un seul mot de passe maître et peut en générer des forts aléatoirement.'),

('passwords', 'association', 'Vrai ou Faux ?', NULL,
NULL, NULL, NULL, NULL, NULL,
'{"cats":["✓ Vrai","✗ Faux"],"items":[{"l":"Réutiliser un mot de passe partout est sans risque","c":1},{"l":"La 2FA protège même si le mot de passe est compromis","c":0},{"l":"6 caractères suffisent en 2024","c":1},{"l":"Changer son mot de passe est une bonne pratique","c":0}]}',
'Réutiliser un mot de passe est dangereux : si un site est piraté, tous vos comptes le sont. La 2FA est une barrière supplémentaire essentielle.');

-- ============================================================
-- PHISHING (4 questions)
-- ============================================================
INSERT INTO entrainement_questions (theme, type, question, visuel, reponse1, reponse2, reponse3, reponse4, bonne_reponse, donnees_json, explication) VALUES

('phishing', 'qcm', 'Quel signe révèle le plus clairement un email de phishing ?', 'email_phishing',
'L''email vient d''une adresse connue', 'L''email crée une urgence et demande de cliquer immédiatement', 'L''email contient une pièce jointe PDF', 'L''email a été reçu un lundi matin', 2, NULL,
'Les attaquants jouent sur l''urgence ("Votre compte sera fermé dans 24h !") pour vous faire agir sans réfléchir. Prenez toujours le temps de vérifier avant de cliquer.'),

('phishing', 'association', 'Face à un email suspect, bon ou mauvais réflexe ?', NULL,
NULL, NULL, NULL, NULL, NULL,
'{"cats":["👍 Bon réflexe","👎 À éviter"],"items":[{"l":"Vérifier l''adresse exacte de l''expéditeur","c":0},{"l":"Cliquer sur le lien pour vérifier","c":1},{"l":"Aller directement sur le site officiel","c":0},{"l":"Télécharger la pièce jointe pour voir","c":1}]}',
'Ne jamais cliquer sur les liens d''un email douteux. Allez toujours sur le site officiel en tapant l''URL vous-même dans le navigateur.'),

('phishing', 'qcm', 'Laquelle de ces URL est suspecte ?', 'url_fake',
'https://www.credit-agricole.fr/connexion', 'https://credlt-agricole-secure.com/login', 'https://www.impots.gouv.fr/', 'https://www.ameli.fr/', 2, NULL,
'Indices suspects : "credlt" (L remplacé par l), domaine ".com" au lieu de ".fr", sous-domaine "secure" trompeur. Les attaquants imitent visuellement les vraies URL pour tromper.'),

('phishing', 'association', 'Vrai ou Faux sur le phishing ?', NULL,
NULL, NULL, NULL, NULL, NULL,
'{"cats":["✓ Vrai","✗ Faux"],"items":[{"l":"Un email peut sembler venir de votre banque sans l''être","c":0},{"l":"Un site en HTTPS est forcément légitime","c":1},{"l":"Le spear phishing cible une personne précise","c":0},{"l":"Les antivirus bloquent 100% du phishing","c":1}]}',
'HTTPS garantit le chiffrement, pas la légitimité du site. Les attaquants créent aussi des sites HTTPS frauduleux. Le spear phishing est une attaque ciblée très dangereuse.');

-- ============================================================
-- MALWARES (4 questions)
-- ============================================================
INSERT INTO entrainement_questions (theme, type, question, visuel, reponse1, reponse2, reponse3, reponse4, bonne_reponse, donnees_json, explication) VALUES

('malwares', 'qcm', 'Un ransomware chiffre vos fichiers et exige 500€. Que faire ?', 'ransomware',
'Payer immédiatement pour récupérer ses fichiers', 'Isoler la machine du réseau et contacter un spécialiste', 'Redémarrer le PC en espérant que ça passe', 'Supprimer l''antivirus qui n''a rien détecté', 2, NULL,
'Isoler la machine évite la propagation au réseau. Payer ne garantit pas la récupération des fichiers et finance les criminels. Des sauvegardes hors-ligne régulières permettent de restaurer sans payer.'),

('malwares', 'association', 'Classez ces logiciels : bienveillant ou malveillant ?', NULL,
NULL, NULL, NULL, NULL, NULL,
'{"cats":["✅ Bienveillant","☠️ Malveillant"],"items":[{"l":"Antivirus","c":0},{"l":"Keylogger","c":1},{"l":"Gestionnaire de mots de passe","c":0},{"l":"Spyware","c":1}]}',
'Un keylogger enregistre tout ce que vous tapez (mots de passe compris). Un spyware surveille votre activité sans votre consentement. Ces logiciels s''installent souvent via des pièces jointes malveillantes.'),

('malwares', 'qcm', 'Un cheval de Troie (trojan) se distingue d''un virus car il…', 'trojan',
'Se propage automatiquement de machine en machine', 'Se cache dans un logiciel apparemment légitime', 'Ralentit uniquement la connexion internet', 'N''existe que sur Windows', 2, NULL,
'Le trojan se camouffle dans un programme en apparence normal (jeu piraté, faux antivirus, document Word). Il ouvre une porte dérobée sans se répliquer, contrairement au virus classique.'),

('malwares', 'association', 'Vrai ou Faux sur les malwares ?', NULL,
NULL, NULL, NULL, NULL, NULL,
'{"cats":["✓ Vrai","✗ Faux"],"items":[{"l":"Un PDF peut contenir un malware","c":0},{"l":"Un Mac ne peut jamais être infecté","c":1},{"l":"Les sauvegardes limitent l''impact d''un ransomware","c":0},{"l":"Les mises à jour n''ont aucun lien avec les malwares","c":1}]}',
'Tous les systèmes d''exploitation peuvent être infectés. Les mises à jour corrigent les failles exploitées par les malwares. Les sauvegardes hors-ligne sont votre meilleure assurance.');

-- ============================================================
-- RÉSEAUX (4 questions)
-- ============================================================
INSERT INTO entrainement_questions (theme, type, question, visuel, reponse1, reponse2, reponse3, reponse4, bonne_reponse, donnees_json, explication) VALUES

('reseaux', 'qcm', 'Dans un café, vous voyez "Cafe_Wifi" et "Cafe_Wifi_Free". Que faites-vous ?', 'wifi_choice',
'Vous connectez au plus rapide', 'Vous demandez au personnel quel est le réseau officiel', 'Vous vous connectez aux deux pour plus de débit', 'Vous choisissez "Free" car c''est gratuit', 2, NULL,
'Les faux points d''accès ("evil twin") imitent les réseaux légitimes pour intercepter vos données. Toujours vérifier avec le personnel. Sur un Wi-Fi public, utilisez un VPN.'),

('reseaux', 'association', 'Pratique réseau : sécurisé ou risqué ?', NULL,
NULL, NULL, NULL, NULL, NULL,
'{"cats":["🔒 Sécurisé","⚠️ Risqué"],"items":[{"l":"VPN sur Wi-Fi public","c":0},{"l":"Partager son Wi-Fi avec tous les voisins","c":1},{"l":"Désactiver le Wi-Fi quand inutilisé","c":0},{"l":"Se connecter à n''importe quel réseau ouvert","c":1}]}',
'Un VPN chiffre votre trafic sur les réseaux non sécurisés. Un réseau partagé sans contrôle expose tout votre trafic local à n''importe qui dans la portée radio.'),

('reseaux', 'qcm', 'Qu''est-ce qu''un VPN fait concrètement ?', 'vpn_tunnel',
'Il supprime tous les virus de votre appareil', 'Il crée un tunnel chiffré entre vous et internet', 'Il double votre débit internet', 'Il remplace votre antivirus', 2, NULL,
'Le VPN chiffre vos données et masque votre adresse IP. Sur un réseau public, même si quelqu''un intercepte votre trafic, il ne peut pas le lire sans la clé de chiffrement.'),

('reseaux', 'association', 'Vrai ou Faux sur les réseaux ?', NULL,
NULL, NULL, NULL, NULL, NULL,
'{"cats":["✓ Vrai","✗ Faux"],"items":[{"l":"WPA3 est plus sécurisé que WPA2","c":0},{"l":"Un VPN vous rend totalement anonyme","c":1},{"l":"Un pare-feu peut bloquer des connexions suspectes","c":0},{"l":"HTTP chiffre les données transmises","c":1}]}',
'Un VPN améliore la confidentialité mais ne garantit pas l''anonymat total. HTTP ne chiffre rien — toujours préférer HTTPS. WPA3 est le standard Wi-Fi le plus récent et sécurisé.');

-- ============================================================
-- DONNÉES PERSONNELLES (4 questions)
-- ============================================================
INSERT INTO entrainement_questions (theme, type, question, visuel, reponse1, reponse2, reponse3, reponse4, bonne_reponse, donnees_json, explication) VALUES

('donnees', 'qcm', 'Que garantit le RGPD aux citoyens européens ?', 'rgpd',
'La gratuité des services numériques', 'Le contrôle sur leurs données personnelles', 'L''anonymat total sur internet', 'La suppression des cookies', 2, NULL,
'Le RGPD (entré en vigueur en 2018) donne aux citoyens européens des droits sur leurs données : accès, rectification, effacement (droit à l''oubli), portabilité et opposition.'),

('donnees', 'association', 'Ces données sont-elles personnelles au sens du RGPD ?', NULL,
NULL, NULL, NULL, NULL, NULL,
'{"cats":["📋 Donnée personnelle","🔓 Non personnelle"],"items":[{"l":"Votre adresse email","c":0},{"l":"La météo à Paris","c":1},{"l":"Votre numéro de téléphone","c":0},{"l":"Le prix d''un article en magasin","c":1}]}',
'Une donnée personnelle est toute information permettant d''identifier directement ou indirectement une personne physique : nom, email, téléphone, IP, localisation…'),

('donnees', 'qcm', 'Que signifie le "droit à l''oubli" ?', NULL,
'Le droit d''effacer son historique de navigation', 'Le droit de demander la suppression de ses données auprès d''un organisme', 'Le droit d''oublier ses mots de passe sans conséquence', 'Un article du Code pénal', 2, NULL,
'Le RGPD vous permet de demander la suppression de vos données à n''importe quelle entreprise qui les détient. Elle doit répondre dans un délai d''un mois.'),

('donnees', 'association', 'Bonne ou mauvaise pratique pour protéger ses données ?', NULL,
NULL, NULL, NULL, NULL, NULL,
'{"cats":["👍 Bonne pratique","👎 Mauvaise pratique"],"items":[{"l":"Lire les CGU avant d''accepter","c":0},{"l":"Accepter tous les cookies sans lire","c":1},{"l":"Limiter les permissions des apps","c":0},{"l":"Utiliser la même adresse partout","c":1}]}',
'Les applications demandent souvent plus de permissions que nécessaire. Restreindre l''accès à votre localisation, caméra ou contacts limite drastiquement la collecte de données.');

-- ============================================================
-- CHIFFREMENT (4 questions)
-- ============================================================
INSERT INTO entrainement_questions (theme, type, question, visuel, reponse1, reponse2, reponse3, reponse4, bonne_reponse, donnees_json, explication) VALUES

('chiffrement', 'qcm', 'Le cadenas 🔒 HTTPS dans la barre d''adresse garantit que…', 'https_lock',
'Le site est officiel et de confiance', 'La communication entre vous et le serveur est chiffrée', 'Le site est sans virus', 'Vos données ne sont jamais stockées', 2, NULL,
'HTTPS garantit que les données échangées sont chiffrées en transit (personne ne peut les lire sur le réseau), mais pas que le site lui-même est légitime ou sécurisé.'),

('chiffrement', 'association', 'Chiffrement symétrique ou asymétrique ?', NULL,
NULL, NULL, NULL, NULL, NULL,
'{"cats":["🔑 Symétrique","🗝️ Asymétrique"],"items":[{"l":"Une seule clé pour chiffrer et déchiffrer","c":0},{"l":"Paire de clés publique/privée","c":1},{"l":"Algorithme AES","c":0},{"l":"Protocole TLS/HTTPS","c":1}]}',
'Le symétrique (AES) est rapide mais nécessite de partager la clé secrète. L''asymétrique (RSA) résout ce problème avec une clé publique (partageable) et une clé privée (secrète).'),

('chiffrement', 'qcm', 'Pourquoi les mots de passe sont-ils "hashés" et non chiffrés en base de données ?', NULL,
'Pour économiser de l''espace disque', 'Car le hachage est irréversible : même en cas de fuite, les vrais mots de passe restent inconnus', 'Car c''est moins coûteux en calcul', 'Par obligation légale uniquement', 2, NULL,
'Un hash (bcrypt, SHA-256…) est une empreinte à sens unique. Même si la base est volée, l''attaquant ne peut pas retrouver directement les mots de passe originaux — il doit les deviner.'),

('chiffrement', 'association', 'Vrai ou Faux sur le chiffrement ?', NULL,
NULL, NULL, NULL, NULL, NULL,
'{"cats":["✓ Vrai","✗ Faux"],"items":[{"l":"AES-256 est considéré très sécurisé","c":0},{"l":"Chiffrer = hacher, c''est pareil","c":1},{"l":"TLS sécurise les échanges web","c":0},{"l":"Un fichier chiffré sans clé se lit facilement","c":1}]}',
'Chiffrement et hachage sont différents : le chiffrement est réversible avec la clé, le hachage est à sens unique. TLS est la base de HTTPS et chiffre tout le trafic web.');
