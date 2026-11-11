# TP01 – PAW : Gestion universitaire

Ce projet est une application PHP 8 en page unique / "page par action" pour gérer les étudiants, enseignants, modules, voyages, bulletins et tables de références d’un établissement universitaire.

Il ne s’appuie pas sur un framework. Les pages PHP s’occupent de l’affichage, des validations, des requêtes SQL et du traitement des formulaires. La structure est volontairement simple et pédagogique.

## 1) Vue d’ensemble

Le site est servi depuis :

- http://localhost/tp_bn/

Le dossier racine contient les modules principaux et la base de données applicative.

### Stack technique
- PHP 8
- MySQL / MariaDB
- PDO pour la connexion SQL
- HTML + CSS + JavaScript pour le front
- Formulaires HTML classiques en GET/POST

### API / points d’entrée
Ce projet n’utilise pas de vraie API REST externe. Les interactions se font par fichiers PHP de formulaire :

- `formulaire.php` : écran de saisie / recherche
- `enregistrer.php` : sauvegarde d’un enregistrement
- `liste.php` : affichage de la liste
- `supprimer.php` : suppression
- `afficher_php.php` / `details.php` / `afficher_bulletin.php` : vues lisibles et parfois imprimables

Autrement dit, la "API" du projet est essentiellement un ensemble de endpoints internes côté serveur, appelés à partir des formulaires HTML.

## 2) Structure du projet

```text
.
├── config/
│   └── database.php          # connexion PDO unique à MySQL
├── includes/
│   ├── fonctions.php          # helpers partagés (h(), upload, validation, schema note)
│   ├── donnees.php            # listes fixes (pays, civilités, modules, etc.)
│   ├── header.php             # menu + layout d’en-tête
│   ├── footer.php             # pied de page
│   └── operations/
│       ├── enregistrer_avec_id.php
│       ├── supprimer_par_id.php
│       ├── charger_options_colonne.php
│       └── lire_liste_texte.php
├── database/
│   └── database.sql           # schéma + données de départ
├── css/
│   └── style.css
├── js/
│   └── script.js              # validation côté navigateur
├── uploads/                   # images uploadées (doit rester accessible en écriture)
├── index.php                  # page d’accueil
├── tables.php                 # sous-menu des tables de référence
├── statistiques.php           # statistiques globales
├── pv/
│   └── pv.php                # PV de filière (moyennes, impression)
├── personne/
│   ├── formulaire.php
│   ├── enregistrer.php
│   ├── liste.php
│   ├── afficher_php.php
│   ├── supprimer.php
│   ├── modifier.php
│   └── upload_image.php
├── enseignant/
│   ├── enseignant.php
│   ├── enregistrer.php
│   ├── liste.php
│   ├── etudiants.php
│   ├── supprimer.php
│   └── modifier.php
├── module/
│   ├── module.php
│   ├── enregistrer.php
│   ├── liste.php
│   ├── supprimer.php
│   └── modifier.php
├── voyage/
│   ├── voyage.php
│   ├── formulaire.php
│   ├── enregistrer.php
│   ├── liste.php
│   ├── details.php
│   ├── afficher_php.php
│   ├── supprimer.php
│   └── modifier.php
├── bulletin/
│   ├── bulletin.php
│   ├── formulaire.php
│   ├── enregistrer.php
│   ├── liste.php
│   ├── modifier.php
│   ├── supprimer.php
│   └── afficher_bulletin.php
├── filiere/
│   ├── filiere.php
│   ├── enregistrer.php
│   ├── liste.php
│   ├── supprimer.php
│   ├── etudiants.php
│   └── statistiques.php
├── nationalite/
├── sport/
├── transport/
└── README.md
```

## 3) Base de données

La base de données principale est `tp01_paw`.

### Tables et données principales
- `nationalite` : codes ISO de nationalité (`DZ`, `FR`, etc.)
- `filiere` : filières, ex. `TC`, `2SC`, `3ISIL`, `3SI`, `M1`, `M2ISI`
- `personne` : étudiants
- `enseignant` : enseignants
- `module` : modules enseignés
- `voyager` : voyages étudiants
- `note` : notes d’étudiants sur les modules
- `moyen_transport`, `sport`, `modele_voiture` : tables de référence

### Particularités importantes
- `personne`, `enseignant`, `module` stockent certains champs de type liste sous forme de chaînes séparées par des virgules (`plateformes`, `applications`, `modules`, `travel_methodes`, etc.).
- `voyage` est une vue SQL sur la table `voyager`. Il faut écrire dans `voyager`, et lire dans `voyage` ou `voyager`.
- `note` est créée/migrée automatiquement via `assurer_schema_note()` dans `includes/fonctions.php`.
- La contrainte `UNIQUE (etudiant_id, module_id)` évite de saisir deux notes identiques pour le même module.
- Les filières sont stockées en chaîne dans les tables concernées, ce qui est dénormalisé par conception.
- Le schéma et les données de démo sont dans `database/database.sql`.

### Sécurité et validation
- Toutes les requêtes SQL doivent passer par des requêtes préparées.
- Les valeurs venant des formulaires sont validées à la fois côté navigateur (`js/script.js`) et côté serveur (PHP). Le PHP est la vraie garde-fou.
- Les images sont stockées par `enregistrer_image()` dans `uploads/` ; le dossier doit rester accessible en écriture et `.htaccess` doit être conservé.

## 4) Modèles de navigation et boutons

Les pages suivent un schéma standard :

1. Ouvrir un formulaire
2. Rechercher un enregistrement existant (par ID ou numéro)
3. Modifier les champs
4. Cliquer sur `Enregistrer`
5. Revenir à la `liste.php` ou supprimer via `Supprimer`

### Convention générale
Chaque module suit la même logique de boutons :
- `Rechercher` : charge un enregistrement existant
- `Enregistrer` : sauvegarde et valide la donnée
- `Liste` : affiche tout le contenu du module
- `Supprimer` : supprime un enregistrement
- `Afficher` / `Détails` / `Imprimer` : vue ou impression du document

## 5) Description par module

### 5.1 Étudiants (`personne/`)
Rôle : gérer les étudiants et leurs informations complètes.

Pages principales :
- `personne/formulaire.php` : formulaire principal et recherche par ID ou numéro
- `personne/enregistrer.php` : enregistre ou met à jour l’étudiant
- `personne/liste.php` : liste des étudiants
- `personne/afficher_php.php` : vue de détail / affichage PHP
- `personne/supprimer.php` : suppression
- `personne/modifier.php` : édition d’un étudiant existant

Interaction habituelle :
- Rechercher un étudiant par ID ou numéro
- Remplir les champs (nom, filière, pays, transport, sport, photo, etc.)
- Cliquer sur `Enregistrer`
- Utiliser `Supprimer` pour effacer un étudiant
- `Formulaire` + `Liste` permettent le cycle CRUD complet

### 5.2 Enseignants (`enseignant/`)
Rôle : gérer les enseignants, leur filière, leurs modules et leurs étudiants associés.

Pages principales :
- `enseignant/enseignant.php` : formulaire principal
- `enseignant/enregistrer.php` : save / update
- `enseignant/liste.php` : liste des enseignants
- `enseignant/etudiants.php` : liste des étudiants d’un enseignant / d’une filière
- `enseignant/supprimer.php` : suppression

Interaction habituelle :
- Rechercher un enseignant par ID ou numéro
- Choisir une filière et des modules
- Enregistrer
- Dans `Liste Des Etudiants`, consulter les étudiants liés à cette filière et à ces modules

### 5.3 Modules (`module/`)
Rôle : gérer les modules d’enseignement.

Pages principales :
- `module/module.php` : formulaire de module
- `module/enregistrer.php` : création / mise à jour
- `module/liste.php` : liste des modules
- `module/supprimer.php` : suppression

Interaction habituelle :
- Saisir `code_module`, `designation`, `coefficient`, `volume_horaire`, `filiere`
- Enregistrer pour l’utiliser ensuite dans les notes et les enseignants

### 5.4 Voyages (`voyage/`)
Rôle : gérer les voyages déclarés par les étudiants.

Pages principales :
- `voyage/voyage.php` : formulaire + recherche par étudiant / numéro
- `voyage/enregistrer.php` : sauvegarde du voyage
- `voyage/liste.php` : liste des voyages
- `voyage/details.php` : fiche imprimable détaillée
- `voyage/afficher_php.php` : rendu de détail
- `voyage/supprimer.php` : suppression

Interaction habituelle :
- Rechercher un étudiant existant ou créer un voyage associé
- Choisir les moyens de transport, le type d’accompagnement, la description
- Enregistrer
- Ouvrir `Détails` pour voir la fiche complète et l’imprimer

### 5.5 Bulletin de notes (`bulletin/`)
Rôle : enregistrer, modifier et supprimer les notes par étudiant et par module.

Pages principales :
- `bulletin/bulletin.php` : page de recherche + formulaire principal
- `bulletin/enregistrer.php` : enregistrer une note
- `bulletin/liste.php` : liste des notes
- `bulletin/modifier.php` : redirection vers le formulaire avec les données préremplies
- `bulletin/supprimer.php` : supprime une note
- `bulletin/afficher_bulletin.php` : bulletin imprimable

Interaction habituelle :
- Rechercher un étudiant par ID ou numéro
- Choisir un module
- Saisir la note entre 0 et 20
- `Enregistrer` la note
- La liste affiche la moyenne pondérée par coefficient
- `Imprimer` permet d’obtenir le bulletin final

### 5.6 Tables de référence
Rôle : gérer les listes de référence utilisées dans les formulaires.

Modules :
- `nationalite/` : nationalités
- `sport/` : sports
- `transport/` : moyens de transport + modèles de voiture
- `filiere/` : filières

Interaction habituelle :
- Ouvrir le formulaire
- Saisir le libellé ou le code
- `Enregistrer`
- Vérifier dans la liste que la valeur apparaît dans les dropdowns des autres formulaires

### 5.7 Statistiques et PV
- `statistiques.php` : statistiques générales (répartition par sexe)
- `pv/pv.php` : PV par filière, avec moyenne générale et impression

Interaction habituelle :
- Choisir une filière dans le menu
- Consulter la moyenne et les étudiant·e·s concernés
- Cliquer sur `Imprimer` pour générer une version papier

## 6) Lancer le projet

### Prérequis
- XAMPP / LAMPP démarré
- MySQL accessible sur localhost:3306
- Apache servi sur http://localhost/

### Étapes
1. Démarrer les services
   ```bash
   /opt/lampp/lampp start
   ```

2. Importer la base de données si nécessaire
   ```bash
   /opt/lampp/bin/mysql -uroot < database/database.sql
   ```

3. Ouvrir dans le navigateur
   ```text
   http://localhost/tp_bn/
   ```

### Vérification syntaxique PHP
   ```bash
   find . -name '*.php' -not -path './.git/*' -exec php -l {} \;
   ```

## 7) Règles de développement du projet

Le projet suit des conventions fortes :
- `config/database.php` est la seule connexion à la base
- `includes/fonctions.php` est la bibliothèque commune
- `h()` est utilisée pour tous les affichages HTML
- Les requêtes SQL sont préparées
- La validation est faite en JS et en PHP
- Les images passent par `enregistrer_image()`
- Les titres de page doivent être définis avec `$titre` avant `include header.php`
- Les liens du menu reposent sur les titres exacts définis dans `includes/header.php`

## 8) En résumé

Cette application est un mini ERP de gestion universitaire très orienté CRUD (Create, Read, Update, Delete) sur des pages PHP classiques. Elle vise surtout l’apprentissage de la programmation web côté serveur, des formulaires, de la persistance SQL et de la gestion de données académiques.

Pour un utilisateur humain, l’idée est simple :
- ouvrir une page de formulaire,
- rechercher ou créer un enregistrement,
- remplir les champs,
- enregistrer,
- consulter la liste,
- modifier ou supprimer si nécessaire.

C’est un projet de gestion administrative, pensé pour être compréhensible, modifiable et pédagogique.
