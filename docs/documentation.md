# AlizQuiz — Documentation du projet

Chef-d'œuvre BAC PRO CIEL — Plateforme de quiz interactifs sur la cybersécurité.

---

## 1. Arborescence du projet

```
AlizQuiz/
├── accueil.php                 Page d'accueil publique
├── inscription.php             Création de compte
├── connexion.php                Authentification
├── deconnexion.php              Fin de session
├── profil.php                   Tableau de bord personnel
├── quiz_debutant.php            Quiz niveau 1 (10 questions)
├── quiz_intermediaire.php       Quiz niveau 2 (15 questions)
├── quiz_expert.php              Quiz niveau 3 (20 questions)
├── classement.php               Classement général
├── certificat.php               Certificat virtuel final
├── includes/
│   ├── connexion.php             Connexion PDO à MySQL
│   ├── securite.php               Fonctions de sécurité (XSS, CSRF, session)
│   ├── fonctions.php              Logique métier (scores, niveaux, stats)
│   ├── header.php                  En-tête HTML + navigation commune
│   └── footer.php                  Pied de page commun
├── assets/
│   ├── css/style.css              Feuille de style unique du site
│   └── js/script.js                Interactions côté client
└── sql/
    └── alizquiz.sql                Script SQL complet (tables + données)
```

Cette organisation sépare clairement trois responsabilités : **les pages** (ce que voit l'utilisateur), **les includes** (le code partagé et la logique), et **les assets** (le style et l'interactivité). C'est une structure que tu peux présenter telle quelle à l'oral comme preuve d'une architecture pensée plutôt que de fichiers empilés au hasard.

---

## 2. Installation sous XAMPP

### Étape 1 — Copier le projet
Place le dossier `AlizQuiz` entier dans le répertoire `htdocs` de ton installation XAMPP :
- Windows : `C:\xampp\htdocs\AlizQuiz`
- Linux : `/opt/lampp/htdocs/AlizQuiz`
- macOS : `/Applications/XAMPP/htdocs/AlizQuiz`

### Étape 2 — Démarrer les services
Ouvre le panneau de contrôle XAMPP et démarre :
- **Apache** (serveur web)
- **MySQL** (base de données)

### Étape 3 — Créer la base de données
1. Ouvre `http://localhost/phpmyadmin`
2. Clique sur l'onglet **Importer**
3. Choisis le fichier `sql/alizquiz.sql`
4. Clique sur **Exécuter**

Cela crée automatiquement la base `alizquiz`, les trois tables, et insère les 45 questions (10 débutant + 15 intermédiaire + 20 expert).

Tu peux vérifier que tout s'est bien importé en exécutant dans l'onglet SQL de phpMyAdmin :
```sql
SELECT niveau, COUNT(*) FROM quiz GROUP BY niveau;
```
Tu dois obtenir : debutant = 10, intermediaire = 15, expert = 20.

### Étape 4 — Vérifier les identifiants de connexion
Le fichier `includes/connexion.php` utilise par défaut les identifiants standards de XAMPP (utilisateur `root`, pas de mot de passe). Si tu as personnalisé ton installation MySQL, modifie les constantes en haut de ce fichier :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'alizquiz');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Étape 5 — Lancer le site
Ouvre ton navigateur à l'adresse :
```
http://localhost/AlizQuiz/accueil.php
```

Tout est prêt : tu peux créer un compte et commencer le niveau Débutant.

---

## 3. Explications techniques pour l'oral BAC PRO CIEL

Cette section te donne des arguments structurés à réutiliser à l'oral, organisés par thématique d'évaluation typique d'un chef-d'œuvre CIEL.

### 3.1 Architecture générale
Le projet suit une architecture **3-tiers** simplifiée, adaptée au PHP procédural :
- **Présentation** : HTML généré par PHP + CSS + JavaScript (les pages `.php` et `assets/`)
- **Logique métier** : `includes/fonctions.php` centralise les règles (calcul de score, déblocage de niveau, statistiques)
- **Données** : MySQL, interrogé exclusivement via PDO (`includes/connexion.php`)

Le fait de séparer `securite.php` et `fonctions.php` illustre le **principe de responsabilité unique** : chaque fichier a un rôle précis, ce qui facilite la maintenance — un point que les jurys apprécient particulièrement.

### 3.2 Sécurité — le point le plus valorisé à l'oral
C'est la partie où tu peux démontrer le plus de maîtrise. Voici les mécanismes mis en œuvre et comment les expliquer :

**Hashage des mots de passe (`password_hash()` / `password_verify()`)**
Le mot de passe en clair n'est jamais stocké. `password_hash()` utilise l'algorithme bcrypt, qui génère un sel aléatoire à chaque hashage (deux utilisateurs avec le même mot de passe auront des hashs différents) et un coût de calcul volontairement élevé pour ralentir les attaques par force brute. À la connexion, `password_verify()` recalcule et compare les hashs, sans jamais avoir besoin de "déchiffrer" quoi que ce soit — le hashage est unidirectionnel par construction.

**Requêtes préparées PDO**
Toutes les requêtes SQL utilisent des marqueurs (`?`) remplis séparément via `execute([...])`, jamais de concaténation directe de variables utilisateur dans le texte SQL. C'est la défense de référence contre l'**injection SQL** : le moteur MySQL traite la donnée utilisateur comme une valeur, jamais comme du code SQL exécutable, même si l'utilisateur saisit `' OR '1'='1`.

**Protection XSS (Cross-Site Scripting)**
La fonction `nettoyer()` applique `htmlspecialchars()` sur toute donnée affichée provenant de l'utilisateur (pseudo, réponses de quiz, etc.). Cela transforme les caractères spéciaux HTML (`<`, `>`, `"`) en leur équivalent inoffensif, empêchant l'injection de scripts malveillants dans la page.

**Protection CSRF (Cross-Site Request Forgery)**
Chaque formulaire embarque un jeton unique généré par `obtenirJetonCSRF()` et vérifié à la soumission par `verifierJetonCSRF()`. Cela empêche qu'un site tiers malveillant ne soumette un formulaire à la place de l'utilisateur sans son consentement.

**Sessions sécurisées**
Les cookies de session sont configurés avec `httponly` (inaccessible en JavaScript, donc résistant au vol par XSS) et `samesite=Lax` (limite les requêtes cross-site). La déconnexion détruit explicitement la session côté serveur et le cookie côté client.

**Anti force-brute basique**
La page de connexion compte les tentatives échouées en session et bloque temporairement après 5 essais, ce qui ralentit une attaque automatisée cherchant à deviner un mot de passe.

**Validation systématique côté serveur**
Même si le JavaScript (`script.js`) offre un retour visuel immédiat (indicateur de robustesse du mot de passe), **toute la validation réelle est refaite côté PHP**. C'est un point essentiel à savoir expliquer : le JavaScript peut être désactivé ou contourné par l'utilisateur, donc on ne peut jamais lui faire confiance pour la sécurité — seul le serveur a le dernier mot.

### 3.3 Logique de progression des niveaux
Le score n'est **jamais calculé en JavaScript**. Le formulaire envoie uniquement les identifiants des questions et les réponses choisies ; le serveur PHP relit la bonne réponse depuis la base de données et compare, empêchant un utilisateur de truquer son score en modifiant le code source de la page. C'est un point fort à mentionner : la confiance zéro envers le client.

Le déblocage de niveau (`niveauAccessible()` dans `fonctions.php`) revérifie systématiquement le niveau réel de l'utilisateur en base de données — jamais uniquement la session — avant d'autoriser l'accès à une page de quiz avancée, ce qui empêche un utilisateur de "sauter" un niveau en tapant directement l'URL.

### 3.4 Base de données
Trois tables liées par une clé étrangère (`resultats.utilisateur_id` → `utilisateurs.id`) avec `ON DELETE CASCADE` : si un compte est supprimé, son historique l'est aussi automatiquement, garantissant l'intégrité référentielle sans code PHP supplémentaire.

La requête du classement (`classement.php`) utilise une sous-requête imbriquée pour ne garder que le **meilleur résultat de chaque utilisateur** — un bon exemple de logique SQL à expliquer si le jury pousse sur ce point.

### 3.5 Responsive design
Le CSS utilise des media queries à trois points de rupture (880px, 720px, 520px) pour adapter la mise en page aux tablettes et smartphones, avec un menu de navigation qui se transforme en menu déroulant mobile (JavaScript dans `script.js`).

---

## 4. Améliorations possibles pour une version future

Ces pistes montrent au jury que tu as une vision d'évolution du projet, ce qui est valorisé à l'oral :

- **Confirmation d'email** : envoi d'un email de vérification à l'inscription (via PHPMailer) avant activation du compte.
- **Réinitialisation de mot de passe** : système "mot de passe oublié" avec jeton à durée de vie limitée envoyé par email.
- **Authentification à deux facteurs (2FA)** : cohérent avec le contenu pédagogique du niveau Intermédiaire, l'ajouter au site lui-même serait une belle mise en abyme.
- **Limitation anti force-brute persistante** : stocker les tentatives échouées en base de données (par IP) plutôt qu'en session, pour résister même si l'utilisateur change de navigateur.
- **API REST** : exposer les quiz via une API JSON pour permettre une future application mobile.
- **Génération de certificat en PDF réel** : utiliser une bibliothèque comme TCPDF ou Dompdf plutôt que l'impression navigateur.
- **Mode "défi chronométré"** : ajouter un temps limite par question pour pimenter l'expérience.
- **Statistiques administrateur** : tableau de bord back-office pour ajouter/modifier des questions sans toucher au code SQL directement.
- **Tests automatisés** : ajouter des tests unitaires PHPUnit sur les fonctions de `fonctions.php` (calcul de score, déblocage de niveau).
- **Accessibilité renforcée** : audit complet avec un lecteur d'écran et amélioration des contrastes si besoin.

---

## 5. Identifiants de test rapide

Aucun compte n'est pré-créé : la table `utilisateurs` est vide après l'import du script SQL. Crée un compte via `inscription.php` pour commencer à tester immédiatement le parcours complet.
