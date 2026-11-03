# AGENTS.md

<!--toc:start-->

- [AGENTS.md](#agentsmd)
  - [Environment (XAMPP/LAMPP)](#environment-xampplampp)
  - [Page contract (easy to break)](#page-contract-easy-to-break)
  - [Structure & flow](#structure-flow)
  - [Module Voyager (Voyage)](#module-voyager-voyage)
  - [Module Bulletin de Notes (Bulletin / Note)](#module-bulletin-de-notes-bulletin--note)
  - [Conventions](#conventions)

<!--toc:end-->

Vanilla PHP 8 multi-page app (university TP01/PAW). No framework, no Composer, no tests, CI, lint, or build config — do not invent commands.

## Environment (XAMPP/LAMPP)

- Served from `/opt/lampp/htdocs/tp01` → `http://localhost/tp01/`.
- Control services: `/opt/lampp/lampp start|restart|stop` (Apache :80, MySQL :3306). `lampp status` may wrongly report MySQL stopped (pid file permissions); check `ss -ltn | grep 3306`.
- MySQL CLI is **not** on `PATH`: `/opt/lampp/bin/mysql -uroot` (no password).
- Database `tp01_paw`; schema + seed data in `database/database.sql` (idempotent: `CREATE IF NOT EXISTS` + `INSERT IGNORE`). Re-import with `/opt/lampp/bin/mysql -uroot < database/database.sql`.
- Only verification available: `find . -name '*.php' -exec php -l {} \;` (PHP 8.5 CLI at `/usr/bin/php`).

## Page contract (easy to break)

Every page must set, **before** `require includes/header.php`:

- `$base` — relative path to app root: `''` in `index.php`, `'../'` in subdirectories. Used by header for CSS/JS/menu links.
- `$titre` — page title.

`includes/header.php` calls `h()`, so `includes/fonctions.php` must be loaded first. `header.php`/`footer.php` are half-page includes, not layout wrappers — a page with no `$base`/`$titre` renders broken links silently.

## Structure & flow

- One file per action, no router/front controller. `personne/` (Étudiants), `voyage/` (Voyager), `enseignant/`, `module/`, `nationalite/`, `transport/`, `sport/`, and `filiere/` use separate form, POST handler, and list pages.
- The form's buttons route via `formaction` to different handlers: `enregistrer.php` (save), `afficher_php.php` (echo only), `liste.php` (GET), `supprimer.php` (delete). Keep this pattern; don't merge into one endpoint.
- `config/database.php` is the single PDO connection (exceptions on, `FETCH_ASSOC`). Pages `require_once` it; never open a second connection.
- Nationalité, moyen de transport, sport, filière, et modèle de voiture dropdowns are loaded from their respective database tables.
- `plateformes`, `applications`, `activites`, `modules`, `modele_voiture`, and `travel_methodes` are comma-joined strings in single columns — not join tables.
- FK `personne.nationalite_code → nationalite(code)` with `ON DELETE RESTRICT`.
- FK `voyager.nationalite_code → nationalite(code)` with `ON DELETE SET NULL`.
- FK `voyager.etudiant_id → personne(id)` with `ON DELETE SET NULL`.
- The Étudiants page (`personne/`) and the Voyage page (`voyage/`) are separate modules; changes to Voyage must not modify `personne/`.

## Module Voyager (Voyage)

- **Database Table**: `voyager` (with MySQL view `voyage` pointing to `voyager`).
  - Contains student fields: `id`, `etudiant_id`, `numero`, `civilite`, `nom_prenom`, `filiere`, `adresse`, `code_postal`, `localite`, `pays`, `plateformes`, `applications`, `transport`, `sport`, `activites`, `modules`, `groupe_sanguin`, `modele_voiture`, `nationalite_code`, `photo`.
  - Plus 3 unique Voyage features:
    1. `travel_methodes` (Train, Car, Flight) — checkboxes.
    2. `travel_with` (Alone, With someone, With family) — radio buttons.
    3. `description` — big textarea for comments.
- **Field Visibility**:
  - Visible fields in Voyage form & list table: `numero`, `civilite`, `nom_prenom`, `filiere`, `adresse`, `pays` + the 3 unique Voyage features (`travel_methodes`, `travel_with`, `description`).
  - All other student fields (`code_postal`, `localite`, `plateformes`, `applications`, `transport`, `sport`, `modele_voiture`, `groupe_sanguin`, `modules`, `activites`, `nationalite`, `photo`) are hidden by default in the form and collapsed/toggleable, and omitted from the main table columns in `liste.php`.
- **Search by ID**:
  - Searching by ID in `voyage/voyage.php` looks up the ID in both `voyager` and `personne`.
  - It **prints the complete informations** of the student searched for (all fields) + the 3 Voyage features in a dedicated printable card/sheet, and pre-fills the Voyage form.
  - An "Imprimer (Print)" button triggers `window.print()` with custom print styling (hiding navigation and extraneous controls).
- **Files in `voyage/`**:
  - `voyage.php` (form + ID search + print card).
  - `formulaire.php` (alias / include of `voyage.php`).
  - `enregistrer.php` (validates and inserts/updates in `voyager`).
  - `liste.php` (table showing only the specified columns + search by ID).
  - `afficher_php.php` (outputs all student + voyage data via POST).
  - `details.php` (printable full sheet of any voyage record).
  - `supprimer.php` (POST deletion handler).
  - `modifier.php` (redirects to edit).
  - Symlink `/tp01/voyager` points to `/tp01/voyage` for seamless routing.

## Module Bulletin de Notes (Bulletin / Note)

- **Database Table**: `note`.
  - Fields: `id`, `etudiant_id`, `module_id`, `note`, `date_saisie`.
  - FK `note.etudiant_id → personne(id)` with `ON DELETE CASCADE`.
  - FK `note.module_id → module(id)` with `ON DELETE RESTRICT`.
  - Contrainte unique `uk_etudiant_module UNIQUE (etudiant_id, module_id)` pour empêcher les doublons de note pour un même étudiant et même module.
- **Formulaire & Fonctionnalités**:
  - Recherche étudiant par ID ou Numéro étudiant (`numero`), affichage automatique de ses informations (numéro, civilité, nom/prénom, filière) et de sa photo (`#cadre-photo`).
  - Sélection de module depuis la table `module` avec affichage automatique de son code module et coefficient.
  - Saisie de la note (entre 0 et 20).
  - Trois boutons d'action : `Enregistrer` (nouvelle note), `Modifier` (mise à jour d'une note existante), `Supprimer` (suppression après confirmation).
  - Tableau récapitulatif des notes de l'étudiant recherché avec calcul automatique de la moyenne générale et impression (`window.print()`).
- **Fichiers dans `bulletin/`**:
  - `bulletin.php` (formulaire + recherche étudiant + affichage photo & relevé).
  - `formulaire.php` (alias / include de `bulletin.php`).
  - `enregistrer.php` (contrôle et insertion / mise à jour).
  - `modifier.php` (redirection pour modification préremplie).
  - `supprimer.php` (suppression sécurisée par POST).
  - `liste.php` (liste globale des notes avec recherche et impression).
  - Symlink `/tp01/note` pointe vers `/tp01/bulletin`.

## Module Enseignants (Enseignant)

- **Database Table**: `enseignant`.
  - Fields: `id`, `numero`, `civilite`, `nom_prenom`, `adresse`, `date_naissance`, `lieu_naissance`, `pays`, `grade`, `specialite`, `filiere`, `modules`.
  - `filiere` : choix unique sélectionné dynamiquement depuis la table `filiere`.
  - `modules` : dropdown à choix multiples relié à la table `module`, stocké sous forme de liste séparée par des virgules (ex: `BDD, POO`).
- **Tableau des Enseignants (`liste.php`)**:
  - Affiche les colonnes `Filière` et `Modules` sous forme de badges.
  - Bouton d'action `Liste Des Etudiants` par enseignant.
- **Consultation des Étudiants par Classe (`etudiants.php`)**:
  - Accessible via le bouton `Liste Des Etudiants` (dans le formulaire et dans la liste).
  - Affiche le tableau des étudiants inscrits dans la filière de l'enseignant pour les modules enseignés avec les colonnes : `Id`, `image`, `nom / prenom`, `Module`, `Coeff`, `Note`, `Filiere`. Le coefficient provient directement de la table `module` et se met à jour automatiquement en cas de modification.
  - Affiche la photo de l'étudiant, sa note avec code couleur admis/ajourné, la filière, et propose un filtre par module ainsi qu'un bouton d'impression.
- **Fichiers dans `enseignant/`**:
  - `enseignant.php` (formulaire avec choix unique Filière et dropdown choix multiples Modules).
  - `enregistrer.php` (validation et insertion / mise à jour des champs dont `filiere` et `modules`).
  - `liste.php` (liste des enseignants avec colonnes Filière, Modules et bouton `Liste Des Etudiants`).
  - `etudiants.php` (vue de la classe avec les colonnes Id, image, nom / prenom, Module, Note, Filiere).
  - `supprimer.php` (suppression sécurisée par POST).

## Conventions

- Validation exists in **both** `js/script.js` and PHP (`enregistrer.php`), with duplicate messages and length rules — update both together. JS validation is skipped for `formnovalidate` buttons, so PHP is the real gate.
- `post_liste()` in `includes/fonctions.php` whitelists checkbox values against `includes/donnees.php` arrays; new option values must be added there _and_ in the JS validator.
- Always output through `h()` (XSS) and use prepared statements (existing code does).
- File uploads go only through `enregistrer_image()` (2 MB cap, `finfo` MIME check, random hex name). `uploads/` must stay writable, and its `.htaccess` blocks PHP execution — never remove it.
- Comments, validation messages, and UI strings are French; keep new text French.
