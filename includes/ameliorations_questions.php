<?php
/**
 * includes/ameliorations_questions.php
 * ------------------------------------------------------------
 * Réécrit les réponses des questions officielles pour qu'elles
 * soient mieux équilibrées : distracteurs crédibles, longueurs
 * comparables, bonne réponse à des positions variées (avant, la
 * bonne réponse était presque toujours la plus longue et détaillée,
 * donc devinable au premier coup d'œil).
 *
 * On met à jour les questions existantes en base (correspondance sur
 * le texte exact de la question) pour ne pas avoir à réimporter le
 * jeu de données. Idempotent : un fichier marqueur évite de relancer
 * les requêtes à chaque page.
 * ------------------------------------------------------------
 */

function ameliorerQuestions(PDO $pdo): void
{
    $marqueur = sys_get_temp_dir() . '/alizquiz_questions_v1';
    if (is_file($marqueur)) {
        return;
    }

    // Format : [niveau, question, r1, r2, r3, r4, bonne_reponse]
    $maj = [
        ['debutant', "Quelle est la caractéristique d'un mot de passe robuste ?",
            "Un mot court et familier que l'on retient sans effort",
            "Une longue suite mêlant majuscules, minuscules, chiffres et symboles",
            "Le même mot de passe réutilisé sur l'ensemble de ses comptes",
            "Une information personnelle comme un prénom ou une date", 2],

        ['debutant', "Faut-il utiliser le même mot de passe pour plusieurs sites ?",
            "Oui, cela simplifie nettement la gestion au quotidien",
            "Oui, mais seulement pour les réseaux sociaux",
            "Non, chaque compte doit avoir son propre mot de passe unique",
            "Peu importe, ce choix n'a aucune conséquence réelle", 3],

        ['debutant', "Qu'est-ce qu'un virus informatique ?",
            "Un programme malveillant qui se propage et endommage un système",
            "Un logiciel officiel qui améliore les performances de la machine",
            "Un composant matériel chargé d'afficher l'image à l'écran",
            "Un protocole servant à relier deux ordinateurs en réseau", 1],

        ['debutant', "Quel outil permet de détecter et supprimer un virus ?",
            "Un navigateur web doté d'une navigation privée",
            "Un tableur capable de trier de grandes quantités de données",
            "Une imprimante connectée au réseau local de la maison",
            "Un logiciel antivirus régulièrement mis à jour", 4],

        ['debutant', "Pourquoi est-il important de mettre à jour régulièrement son système ?",
            "Pour modifier l'apparence et les couleurs de l'interface",
            "Pour corriger les failles de sécurité découvertes par l'éditeur",
            "Pour réduire volontairement la vitesse de l'appareil",
            "Pour libérer automatiquement de l'espace de stockage", 2],

        ['debutant', "Que risque-t-on en ignorant les mises à jour de sécurité ?",
            "Profiter d'une amélioration automatique des performances",
            "Gagner de l'espace disque sans le moindre effort",
            "Rester exposé à des failles déjà corrigées par l'éditeur",
            "Rien, ces mises à jour sont purement esthétiques", 3],

        ['debutant', "Quel est un signe typique d'un email de phishing ?",
            "Un ton alarmant qui pousse à cliquer en urgence sur un lien",
            "Une adresse d'expéditeur connue et parfaitement officielle",
            "Un message soigné, sans faute et sans pièce jointe",
            "Un envoi provenant d'un collègue que l'on contacte souvent", 1],

        ['debutant', "Que faire si un email demande vos identifiants bancaires de toute urgence ?",
            "Répondre aussitôt en communiquant ses identifiants",
            "Cliquer sur le lien fourni pour vérifier son compte",
            "Transférer le message à l'ensemble de ses contacts",
            "Ne pas répondre et signaler le message comme suspect", 4],

        ['debutant', 'Que signifie le "S" dans HTTPS ?',
            "System, en référence au système d'exploitation utilisé",
            "Secure, c'est-à-dire une connexion sécurisée et chiffrée",
            "Server, le serveur distant qui héberge le site web",
            "Standard, une norme commune d'affichage des pages", 2],

        ['debutant', "Quel élément visuel indique généralement une connexion HTTPS dans un navigateur ?",
            "Un petit cadenas affiché dans la barre d'adresse",
            "Une icône de corbeille juste à côté de l'adresse",
            "Un drapeau coloré épinglé en haut de la fenêtre",
            "Un symbole de haut-parleur près de l'onglet actif", 1],
    ];

    try {
        $stmt = $pdo->prepare(
            'UPDATE quiz SET reponse1 = ?, reponse2 = ?, reponse3 = ?, reponse4 = ?, bonne_reponse = ?
             WHERE question = ? AND niveau = ?'
        );
        foreach ($maj as $q) {
            $stmt->execute([$q[2], $q[3], $q[4], $q[5], $q[6], $q[1], $q[0]]);
        }
        @file_put_contents($marqueur, date('c'));
    } catch (Throwable $e) {}
}
