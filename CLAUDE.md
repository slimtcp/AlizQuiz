# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Environment

- **Stack** : PHP 8+ (procédural), MySQL/MariaDB, CSS pur, JS vanille — zéro framework, zéro dépendance externe
- **Serveur local** : XAMPP sur macOS (`/Applications/XAMPP/xamppfiles/`)
- **URL locale** : `http://localhost/AlizQuiz/`
- **Base de données** : `alizquiz` (utilisateur `root`, pas de mot de passe)

## Commandes utiles

```bash
# Démarrer/arrêter Apache et MySQL
/Applications/XAMPP/xamppfiles/bin/apachectl start
/Applications/XAMPP/xamppfiles/bin/apachectl stop
/Applications/XAMPP/xamppfiles/bin/mysql.server start

# Accéder à MySQL en CLI
/Applications/XAMPP/xamppfiles/bin/mysql -u root alizquiz

# Importer le schéma complet (tables + questions de base)
/Applications/XAMPP/xamppfiles/bin/mysql -u root alizquiz < sql/alizquiz.sql

# Importer les questions supplémentaires (is_extra = 1)
/Applications/XAMPP/xamppfiles/bin/mysql -u root alizquiz < sql/questions_extra.sql

# Requêtes de vérification rapides
/Applications/XAMPP/xamppfiles/bin/mysql -u root alizquiz -e "SELECT theme, COUNT(*) FROM quiz GROUP BY theme ORDER BY niveau, theme;"
/Applications/XAMPP/xamppfiles/bin/mysql -u root alizquiz -e "DESCRIBE utilisateurs;"
```

## Architecture

```
AlizQuiz/
├── includes/
│   ├── connexion.php     — PDO $pdo (FETCH_ASSOC, ERRMODE_EXCEPTION, pas d'émulation)
│   ├── securite.php      — nettoyer(), exigerConnexion(), obtenirJetonCSRF(), verifierJetonCSRF()
│   ├── fonctions.php     — logique métier : recupererQuestions(), enregistrerResultat(), verifierBadges()
│   ├── header.php        — HTML commun + nav + modal déconnexion (inclus en premier sur chaque page)
│   └── footer.php        — fermeture HTML
├── assets/
│   ├── css/style.css     — feuille de style unique (~1600 lignes), design system complet en CSS custom properties
│   └── js/confetti.js    — moteur confetti canvas pur (lancerConfettis())
├── sql/
│   ├── alizquiz.sql          — schéma complet + questions officielles (is_extra = 0 implicite)
│   ├── questions_extra.sql   — 18 questions supplémentaires par thème (is_extra = 1) + ALTER TABLE
│   └── entrainement.sql      — tables pour le mode entraînement
└── [pages].php
```

### Pages principales

| Page | Rôle |
|------|------|
| `quiz_debutant/intermediaire/expert.php` | Quiz officiels — appellent `enregistrerResultat()`, débloquent niveaux |
| `quiz_custom.php` | Quiz personnalisé — N'appelle PAS `enregistrerResultat()`, pioche dans toutes les questions |
| `defi.php` | Défi du jour — 5 questions identiques pour tous, reset à 12h00 Paris, résultats en `defi_resultats` |
| `profil.php` | Tableau de bord — statistiques + canvas chart progression + badges |
| `entrainement.php / entrainement_quiz.php` | Mode entraînement libre, sans progression |

## Schéma base de données

```sql
utilisateurs  (id, pseudo, email, mot_de_passe_hash, niveau_actuel ENUM, date_inscription)
quiz          (id, niveau ENUM, theme, question, reponse1-4, bonne_reponse, is_extra TINYINT)
resultats     (id, utilisateur_id→utilisateurs, niveau, score, total_questions, pourcentage, reussi, date_resultat)
badges_utilisateurs (id, utilisateur_id, badge_slug, obtenu_le)
defi_resultats      (id, utilisateur_id, date_defi DATE, score, temps_secondes, created_at)
-- + tables entrainement (voir sql/entrainement.sql)
```

**Colonne `is_extra`** : `0` = question officielle, `1` = question supplémentaire. `recupererQuestions()` prend toutes les questions (0 et 1). Les quiz officiels affichent donc toutes les questions mélangées. `quiz_custom.php` filtre aussi sur le niveau accessible à l'utilisateur.

## Progression des niveaux

`utilisateurs.niveau_actuel` stocke le **prochain niveau débloqué** (pas le dernier complété).

```
debutant → (≥70%) → intermediaire → (≥75%) → expert → (≥75%) → termine
```

`niveauAccessible($demande, $actuel)` compare les positions dans `ORDRE_NIVEAUX = ['debutant','intermediaire','expert','termine']`. Les pages de quiz redirigent si le niveau n'est pas accessible.

`enregistrerResultat()` détecte si le niveau a **réellement progressé** (via comparaison en DB, pas en session) et retourne `['niveau_debloque' => 'intermediaire'|null, ...]` pour afficher le popup de déblocage.

## Conventions de code

- **Toute sortie HTML** passe par `nettoyer()` (`htmlspecialchars`), sans exception
- **Toutes les requêtes SQL** utilisent des requêtes préparées PDO avec `?` ou `:param`, jamais de concaténation
- **Tout formulaire POST** inclut `<input type="hidden" name="csrf_token" value="<?= obtenirJetonCSRF() ?>">` et est vérifié avec `verifierJetonCSRF($_POST['csrf_token'] ?? null)`
- Le score est **toujours recalculé côté serveur** : le formulaire envoie `ids_questions` + `reponse_N`, le PHP relit les bonnes réponses en DB
- Les pages protégées commencent par `require_once 'includes/securite.php'` + `require_once 'includes/fonctions.php'` + `exigerConnexion()`

## Design system (style.css)

Variables CSS clés :
```css
--grad-blue: linear-gradient(135deg, #3D7CFF 0%, #7C3AED 100%)
--grad-text: /* même gradient pour texte clip */
--blue-accent / --blue-accent-soft / --blue-bright
--bg-panel / --bg-glass / --border-subtle / --border-glow
--radius-md / --radius-lg
--font-display: 'Space Grotesk' / --font-body: 'Inter'
```

Thème clair/sombre géré via `data-theme="dark|light"` sur `<html>`, persisté dans `localStorage('alizquiz-theme')`. Appliqué **avant rendu** dans un `<script>` inline dans `<head>` pour éviter le flash.

Cards : `class="card"` avec glass morphism (`backdrop-filter: blur(16px)`). Boutons : `btn btn-primary`, `btn btn-ghost`, `btn btn-block`.

## Défi du Jour — logique temporelle

Le thème du jour est déterministe : `$dayIndex = (timestamp($defiDate) - timestamp(2026-01-01)) / 86400`, puis `$themes[$dayIndex % 16]`. Les 5 questions sont choisies via `mt_srand(intval(str_replace('-','', $defiDate)))` + `shuffle()` — identiques pour tous les utilisateurs. La "date du défi courant" est aujourd'hui si heure Paris ≥ 12h, hier sinon.
