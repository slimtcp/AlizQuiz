-- ============================================================
-- AlizQuiz — Script de création de la base de données
-- Projet BAC PRO CIEL — Chef-d'œuvre
-- ============================================================
-- Ce script :
--   1. Crée la base de données alizquiz
--   2. Crée les 3 tables : utilisateurs, quiz, resultats
--   3. Insère automatiquement les 45 questions (10 + 15 + 20)
--
-- Installation : ouvrir phpMyAdmin > onglet "Importer" > choisir
-- ce fichier > Exécuter. Ou copier/coller dans l'onglet SQL.
-- ============================================================

CREATE DATABASE IF NOT EXISTS alizquiz
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE alizquiz;

-- ------------------------------------------------------------
-- Table : utilisateurs
-- Stocke les comptes. Le mot de passe n'est JAMAIS stocké en
-- clair : on enregistre uniquement son empreinte (hash) générée
-- par password_hash() en PHP (algorithme bcrypt).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    pseudo          VARCHAR(50)  NOT NULL UNIQUE,
    email           VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    niveau_actuel   ENUM('debutant', 'intermediaire', 'expert', 'termine') NOT NULL DEFAULT 'debutant',
    date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : quiz
-- Contient toutes les questions, tous niveaux confondus.
-- La colonne "niveau" permet de filtrer les questions affichées.
-- bonne_reponse contient le numéro (1 à 4) de la bonne réponse.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quiz (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    niveau        ENUM('debutant', 'intermediaire', 'expert') NOT NULL,
    theme         VARCHAR(100) NOT NULL,
    question      TEXT NOT NULL,
    reponse1      VARCHAR(255) NOT NULL,
    reponse2      VARCHAR(255) NOT NULL,
    reponse3      VARCHAR(255) NOT NULL,
    reponse4      VARCHAR(255) NOT NULL,
    bonne_reponse TINYINT NOT NULL CHECK (bonne_reponse BETWEEN 1 AND 4)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : resultats
-- Historique de chaque tentative de quiz d'un utilisateur.
-- La clé étrangère utilisateur_id garantit l'intégrité : un
-- résultat ne peut pas exister sans utilisateur valide, et si un
-- utilisateur est supprimé, ses résultats le sont aussi (CASCADE).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS resultats (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    niveau         ENUM('debutant', 'intermediaire', 'expert') NOT NULL,
    score          INT NOT NULL,
    total_questions INT NOT NULL,
    pourcentage    DECIMAL(5,2) NOT NULL,
    reussi         TINYINT(1) NOT NULL DEFAULT 0,
    date_resultat  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- INSERTION DES QUESTIONS — NIVEAU DÉBUTANT (10 questions)
-- Thèmes : mots de passe, virus, mises à jour, emails suspects, HTTPS
-- ============================================================
INSERT INTO quiz (niveau, theme, question, reponse1, reponse2, reponse3, reponse4, bonne_reponse) VALUES
('debutant', 'Mots de passe', 'Quelle est la caractéristique d''un mot de passe robuste ?', 'Un mot court et familier que l''on retient sans effort', 'Une longue suite mêlant majuscules, minuscules, chiffres et symboles', 'Le même mot de passe réutilisé sur l''ensemble de ses comptes', 'Une information personnelle comme un prénom ou une date', 2),
('debutant', 'Mots de passe', 'Faut-il utiliser le même mot de passe pour plusieurs sites ?', 'Oui, cela simplifie nettement la gestion au quotidien', 'Oui, mais seulement pour les réseaux sociaux', 'Non, chaque compte doit avoir son propre mot de passe unique', 'Peu importe, ce choix n''a aucune conséquence réelle', 3),
('debutant', 'Virus', 'Qu''est-ce qu''un virus informatique ?', 'Un programme malveillant qui se propage et endommage un système', 'Un logiciel officiel qui améliore les performances de la machine', 'Un composant matériel chargé d''afficher l''image à l''écran', 'Un protocole servant à relier deux ordinateurs en réseau', 1),
('debutant', 'Virus', 'Quel outil permet de détecter et supprimer un virus ?', 'Un navigateur web doté d''une navigation privée', 'Un tableur capable de trier de grandes quantités de données', 'Une imprimante connectée au réseau local de la maison', 'Un logiciel antivirus régulièrement mis à jour', 4),
('debutant', 'Mises à jour', 'Pourquoi est-il important de mettre à jour régulièrement son système ?', 'Pour modifier l''apparence et les couleurs de l''interface', 'Pour corriger les failles de sécurité découvertes par l''éditeur', 'Pour réduire volontairement la vitesse de l''appareil', 'Pour libérer automatiquement de l''espace de stockage', 2),
('debutant', 'Mises à jour', 'Que risque-t-on en ignorant les mises à jour de sécurité ?', 'Profiter d''une amélioration automatique des performances', 'Gagner de l''espace disque sans le moindre effort', 'Rester exposé à des failles déjà corrigées par l''éditeur', 'Rien, ces mises à jour sont purement esthétiques', 3),
('debutant', 'Emails suspects', 'Quel est un signe typique d''un email de phishing ?', 'Un ton alarmant qui pousse à cliquer en urgence sur un lien', 'Une adresse d''expéditeur connue et parfaitement officielle', 'Un message soigné, sans faute et sans pièce jointe', 'Un envoi provenant d''un collègue que l''on contacte souvent', 1),
('debutant', 'Emails suspects', 'Que faire si un email demande vos identifiants bancaires de toute urgence ?', 'Répondre aussitôt en communiquant ses identifiants', 'Cliquer sur le lien fourni pour vérifier son compte', 'Transférer le message à l''ensemble de ses contacts', 'Ne pas répondre et signaler le message comme suspect', 4),
('debutant', 'HTTPS', 'Que signifie le "S" dans HTTPS ?', 'System, en référence au système d''exploitation utilisé', 'Secure, c''est-à-dire une connexion sécurisée et chiffrée', 'Server, le serveur distant qui héberge le site web', 'Standard, une norme commune d''affichage des pages', 2),
('debutant', 'HTTPS', 'Quel élément visuel indique généralement une connexion HTTPS dans un navigateur ?', 'Un petit cadenas affiché dans la barre d''adresse', 'Une icône de corbeille juste à côté de l''adresse', 'Un drapeau coloré épinglé en haut de la fenêtre', 'Un symbole de haut-parleur près de l''onglet actif', 1);

-- ============================================================
-- INSERTION DES QUESTIONS — NIVEAU INTERMÉDIAIRE (15 questions)
-- Thèmes : phishing, ransomware, 2FA, réseaux, protection des données
-- ============================================================
INSERT INTO quiz (niveau, theme, question, reponse1, reponse2, reponse3, reponse4, bonne_reponse) VALUES
('intermediaire', 'Phishing', 'Le phishing consiste principalement à...', 'Optimiser un réseau Wi-Fi', 'Usurper l''identité d''un tiers de confiance pour voler des informations', 'Compresser des fichiers volumineux', 'Mettre à jour un pare-feu', 2),
('intermediaire', 'Phishing', 'Le "spear phishing" se distingue du phishing classique car il est...', 'Envoyé en masse à des millions de destinataires', 'Ciblé sur une personne ou organisation précise', 'Toujours envoyé par SMS', 'Inoffensif', 2),
('intermediaire', 'Ransomware', 'Qu''est-ce qu''un ransomware ?', 'Un logiciel qui accélère le démarrage du PC', 'Un logiciel qui chiffre les données et exige une rançon pour les déverrouiller', 'Un antivirus gratuit', 'Un outil de sauvegarde automatique', 2),
('intermediaire', 'Ransomware', 'Quelle est la meilleure protection contre les ransomwares ?', 'Payer immédiatement la rançon demandée', 'Désactiver le pare-feu', 'Effectuer des sauvegardes régulières hors-ligne', 'Ouvrir toutes les pièces jointes reçues', 3),
('intermediaire', 'Authentification à deux facteurs', 'L''authentification à deux facteurs (2FA) repose sur...', 'Un seul mot de passe très long', 'La combinaison de deux éléments de preuve distincts (ex : mot de passe + code SMS)', 'Le partage du mot de passe avec un ami', 'L''absence totale d''authentification', 2),
('intermediaire', 'Authentification à deux facteurs', 'Lequel de ces éléments est un exemple valide de second facteur ?', 'Un second mot de passe identique', 'Une application générant un code temporaire (TOTP)', 'Le nom d''utilisateur', 'L''adresse IP', 2),
('intermediaire', 'Réseaux', 'Qu''est-ce qu''une adresse IP ?', 'Un identifiant unique attribué à un appareil sur un réseau', 'Un type de virus', 'Un protocole de chiffrement', 'Un mot de passe réseau', 1),
('intermediaire', 'Réseaux', 'Quel est le rôle principal d''un routeur ?', 'Stocker des fichiers', 'Acheminer les données entre différents réseaux', 'Afficher des pages web', 'Détecter les virus', 2),
('intermediaire', 'Réseaux', 'Un réseau Wi-Fi public non sécurisé présente un risque car...', 'Il est toujours plus rapide', 'Les données échangées peuvent être interceptées plus facilement', 'Il bloque automatiquement les virus', 'Il chiffre toutes les connexions', 2),
('intermediaire', 'Protection des données', 'Le RGPD est un règlement européen qui encadre...', 'La vente de matériel informatique', 'La protection des données personnelles', 'La vitesse des connexions internet', 'La fabrication des processeurs', 2),
('intermediaire', 'Protection des données', 'Le principe de "minimisation des données" signifie...', 'Collecter le maximum de données possible', 'Ne collecter que les données strictement nécessaires à un traitement', 'Supprimer toutes les données après une heure', 'Dupliquer les données sur plusieurs serveurs', 2),
('intermediaire', 'Phishing', 'Un lien raccourci (type bit.ly) dans un email douteux doit inciter à...', 'Cliquer sans réfléchir', 'La méfiance, car il masque la destination réelle', 'Le transférer à tous ses contacts', 'L''ignorer complètement sans vérifier l''expéditeur', 2),
('intermediaire', 'Ransomware', 'Une fois infecté par un ransomware, il est recommandé de...', 'Payer immédiatement sans réfléchir', 'Isoler la machine du réseau et contacter un professionnel', 'Redémarrer en boucle l''ordinateur', 'Supprimer l''antivirus', 2),
('intermediaire', 'Réseaux', 'Un pare-feu (firewall) sert principalement à...', 'Accélérer la connexion internet', 'Filtrer les connexions entrantes et sortantes selon des règles', 'Stocker des mots de passe', 'Créer des sauvegardes', 2),
('intermediaire', 'Protection des données', 'Le chiffrement d''un fichier permet de...', 'Le rendre illisible sans la clé de déchiffrement appropriée', 'Le supprimer définitivement', 'Le rendre plus volumineux uniquement', 'L''envoyer plus rapidement par email', 1);

-- ============================================================
-- INSERTION DES QUESTIONS — NIVEAU EXPERT (20 questions)
-- Thèmes : pare-feu, VPN, chiffrement, injection SQL, force brute, sécurité réseau
-- ============================================================
INSERT INTO quiz (niveau, theme, question, reponse1, reponse2, reponse3, reponse4, bonne_reponse) VALUES
('expert', 'Pare-feu', 'Un pare-feu de type "stateful" se distingue d''un pare-feu simple car il...', 'Bloque tout le trafic sans exception', 'Garde en mémoire l''état des connexions pour filtrer plus finement', 'Ne fonctionne que sur les réseaux filaires', 'Chiffre automatiquement toutes les données', 2),
('expert', 'Pare-feu', 'Une DMZ (zone démilitarisée) dans une architecture réseau sert à...', 'Isoler les serveurs exposés à internet du réseau interne', 'Augmenter la vitesse du Wi-Fi', 'Remplacer l''antivirus', 'Supprimer le besoin de pare-feu', 1),
('expert', 'VPN', 'Un VPN (réseau privé virtuel) permet principalement de...', 'Supprimer tous les virus du système', 'Créer un tunnel chiffré entre l''utilisateur et un serveur distant', 'Augmenter la puissance du processeur', 'Remplacer un mot de passe', 2),
('expert', 'VPN', 'Quel protocole est couramment utilisé pour sécuriser un VPN ?', 'HTTP', 'FTP', 'OpenVPN ou IPsec', 'SMTP', 3),
('expert', 'Chiffrement', 'Quelle est la différence entre chiffrement symétrique et asymétrique ?', 'Le symétrique utilise une seule clé partagée, l''asymétrique une paire de clés publique/privée', 'Il n''existe aucune différence', 'L''asymétrique n''est jamais utilisé en pratique', 'Le symétrique est uniquement utilisé pour les images', 1),
('expert', 'Chiffrement', 'L''algorithme AES est un algorithme de chiffrement de type...', 'Asymétrique', 'Symétrique', 'De compression', 'De hachage uniquement', 2),
('expert', 'Chiffrement', 'Le hachage (ex : bcrypt, SHA-256) diffère du chiffrement car...', 'Il est réversible avec la bonne clé', 'Il est conçu pour être à sens unique (non réversible)', 'Il ralentit uniquement les disques durs', 'Il ne s''applique qu''aux images', 2),
('expert', 'SQL Injection', 'Une injection SQL consiste à...', 'Optimiser les performances d''une base de données', 'Insérer du code SQL malveillant via une entrée utilisateur non sécurisée', 'Sauvegarder automatiquement une base de données', 'Chiffrer une base de données', 2),
('expert', 'SQL Injection', 'Quelle pratique permet de se protéger efficacement contre les injections SQL en PHP ?', 'Concaténer directement les variables dans la requête SQL', 'Utiliser des requêtes préparées (PDO ou MySQLi)', 'Désactiver le mot de passe de la base de données', 'Afficher les erreurs SQL aux utilisateurs', 2),
('expert', 'SQL Injection', 'Une entrée comme  '' OR ''1''=''1  dans un champ de connexion est révélatrice d''une tentative...', 'De réinitialisation de mot de passe légitime', 'D''injection SQL visant à contourner l''authentification', 'De mise à jour automatique', 'De simple faute de frappe', 2),
('expert', 'Force brute', 'Une attaque par force brute consiste à...', 'Deviner un mot de passe en testant toutes les combinaisons possibles', 'Envoyer un email de phishing ciblé', 'Chiffrer un disque dur', 'Mettre à jour un système', 1),
('expert', 'Force brute', 'Quelle mesure ralentit efficacement une attaque par force brute ?', 'Autoriser un nombre illimité de tentatives de connexion', 'Limiter le nombre de tentatives et imposer un délai après échec', 'Afficher le mot de passe en clair', 'Désactiver le pare-feu', 2),
('expert', 'Force brute', 'Une attaque par "dictionnaire" utilise...', 'Une liste de mots de passe courants ou déjà compromis', 'Un générateur de nombres aléatoires uniquement', 'Un VPN', 'Un certificat SSL', 1),
('expert', 'Sécurité réseau', 'Un IDS (Intrusion Detection System) a pour rôle de...', 'Chiffrer le trafic réseau', 'Détecter une activité suspecte ou une intrusion sur le réseau', 'Remplacer le pare-feu', 'Accélérer le débit internet', 2),
('expert', 'Sécurité réseau', 'La segmentation d''un réseau en VLAN permet de...', 'Isoler des groupes de machines pour limiter la propagation d''une attaque', 'Supprimer le besoin d''adresses IP', 'Augmenter automatiquement la bande passante', 'Remplacer les mots de passe', 1),
('expert', 'Sécurité réseau', 'Le protocole WPA3 concerne la sécurisation...', 'Des emails', 'Des réseaux Wi-Fi', 'Des bases de données', 'Des imprimantes réseau', 2),
('expert', 'Pare-feu', 'Le filtrage par liste blanche (whitelist) dans un pare-feu signifie...', 'Tout autoriser par défaut', 'Tout bloquer par défaut sauf ce qui est explicitement autorisé', 'Chiffrer uniquement les emails', 'Supprimer tous les logs', 2),
('expert', 'VPN', 'Dans une entreprise, un VPN est souvent utilisé pour...', 'Permettre un accès distant sécurisé au réseau interne', 'Remplacer entièrement l''antivirus', 'Augmenter la taille de l''écran', 'Supprimer les sauvegardes', 1),
('expert', 'Chiffrement', 'Le protocole TLS (successeur de SSL) sert à...', 'Sécuriser les échanges de données sur un réseau, notamment le web', 'Compresser des fichiers vidéo', 'Remplacer le DNS', 'Stocker des mots de passe en clair', 1),
('expert', 'Sécurité réseau', 'Un test d''intrusion (pentest) a pour objectif de...', 'Endommager volontairement un système en production sans autorisation', 'Identifier les vulnérabilités d''un système avec l''accord du propriétaire', 'Supprimer les pare-feux existants', 'Remplacer les antivirus par des VPN', 2);

-- ============================================================
-- Fin du script — Vérification rapide possible avec :
-- SELECT niveau, COUNT(*) FROM quiz GROUP BY niveau;
-- (doit retourner debutant=10, intermediaire=15, expert=20)
-- ============================================================
