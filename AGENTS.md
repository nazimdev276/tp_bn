# AGENTS.md

<!--toc:start-->

- [AGENTS.md](#agentsmd)
  - [Environment (XAMPP/LAMPP)](#environment-xampplampp)
  - [Page contract (easy to break)](#page-contract-easy-to-break)
  - [Structure & flow](#structure-flow)
  - [Shared CRUD helpers (includes/operations/)](#shared-crud-helpers-includesoperations)
  - [Database quirks](#database-quirks)
  - [Module Voyager (Voyage)](#module-voyager-voyage)
  - [Module Bulletin de Notes (Bulletin / Note)](#module-bulletin-de-notes-bulletin--note)
  - [Module Enseignants (Enseignant)](#module-enseignants-enseignant)
  - [Conventions](#conventions)

<!--toc:end-->

Vanilla PHP 8 multi-page app (university TP01/PAW). No framework, no Composer, no tests, no CI, no lint/build config — do not invent commands.

## Environment (XAMPP/LAMPP)

- Served from `/opt/lampp/htdocs/tp_bn` → `http://localhost/tp_bn/` (Apache `DocumentRoot` is `/opt/lampp/htdocs`).
- A separate sibling app lives at `/opt/lampp/htdocs/tp01`. It is a different checkout — do not edit it from this repo.
- Both apps point at the **same** MySQL database `tp01_paw` (`config/database.php`). Data written here is visible in `tp01` and vice versa.
- Services: `/opt/lampp/lampp start|restart|stop` (Apache :80, MySQL :3306). `lampp status` can wrongly report MySQL stopped; check `ss -ltn | grep 3306`.
- MySQL CLI is not on `PATH`: `/opt/lampp/bin/mysql -uroot` (no password).
- Re-import schema + seed: `/opt/lampp/bin/mysql -uroot < database/database.sql` (idempotent: `CREATE IF NOT EXISTS` / `INSERT IGNORE`).
- Only verification step: `find . -name '*.php' -not -path './.git/*' -exec php -l {} \;` (PHP 8.5 at `/usr/bin/php`).

## Page contract (easy to break)

Every page that renders HTML must set, **before** `require includes/header.php`:

- `$base` — relative path to app root: `''` at root, `'../'` in subdirectories. Drives CSS/JS/menu links.
- `$titre` — page title.

`includes/header.php` calls `h()`, so `includes/fonctions.php` must be loaded first. `header.php`/`footer.php` are half-page includes, not layout wrappers.

**Nav highlighting is an exact string match.** `header.php` holds `$titresEtudiants`, `$titresEnseignants`, `$titresModules`, `$titresVoyages`, `$titresBulletin`, `$titresTables` and compares `$titre` against them. A new page with a new title renders fine but silently loses its active menu state — add the title to the right array. Currently unmatched: `filiere/etudiants.php` ("Étudiants par filière"), `filiere/statistiques.php` ("Statistiques de réussite par filière"), and `transport/enregistrer.php` ("Enregistrement du moyen de transport" vs the listed `"Enregistrement transport"`).

## Structure & flow

- One file per action, no router/front controller. Modules: `personne/` (Étudiants), `enseignant/`, `module/`, `voyage/`, `bulletin/`, `filiere/`, plus reference tables `nationalite/`, `sport/`, `transport/` (transport + voiture), reachable through `tables.php`.
- Forms route buttons with `formaction`: `enregistrer.php` (save), `afficher_php.php` (echo only), `liste.php` (GET), `supprimer.php` (delete). Keep this pattern; don't merge into one endpoint.
- `config/database.php` is the single PDO connection (exceptions on, `FETCH_ASSOC`). `require_once` it; never open a second connection.
- `includes/fonctions.php` is the shared bootstrap: it pulls in `includes/operations/*` and defines `h()`, `post_liste()`, `enregistrer_image()`, `assurer_schema_note()`.
- Two handler styles coexist: `enseignant/`, `module/`, `filiere/` use the shared helpers (`enregistrer_avec_id()`, `supprimer_par_id()`); `personne/`, `voyage/`, `bulletin/` use hand-written SQL because they upsert by form ID, handle photos, or touch `note`. Match the module you are editing; don't refactor one into the other.
- `personne/modifier.php` is a full edit page (linked from `personne/liste.php`). `voyage/modifier.php` and `bulletin/modifier.php` are redirects back into their form with query params.
- `voyage/formulaire.php` and `bulletin/formulaire.php` are one-line aliases that `require` the main page.
- `filiere/` holds the CRUD quartet plus `etudiants.php` (students of one filière with their notes) and `statistiques.php` (pass/fail stats per filière). Both are read-only views, not edit forms.

## Shared CRUD helpers (`includes/operations/`)

- `enregistrer_avec_id($pdo, $table, $valeurs, $id, $modifier)` returns `['status' => ..., 'id' => ...]` with statuses `inserted`, `updated`, `unchanged`, `missing_id`, `not_found`. With `$modifier = false` and a non-null `$id` it upserts: updates if the row exists, otherwise inserts with that explicit ID.
- `supprimer_par_id($pdo, $table, $id)` returns `deleted`, `missing`, or `invalid`.
- `charger_options_colonne($pdo, $table, $colonne, $ordre, $defaut)` loads dropdown values from a table, falling back to the static array when the table is empty.
- `lire_liste_texte()` / `ecrire_liste_texte()` split/join the comma-separated columns.
- The DB helpers validate table/column names against `^[a-z_]+$` and interpolate them into SQL. They cannot target names containing digits or uppercase, and `enregistrer_avec_id()`/`supprimer_par_id()` assume an `id` primary key. The reference tables (`nationalite` keyed by `code`, `sport`/`moyen_transport`/`modele_voiture` keyed by `libelle`) therefore use hand-written INSERTs instead.

## Database quirks

- `voyage` is a **view** over `voyager` (`CREATE OR REPLACE VIEW voyage AS SELECT * FROM voyager`). Read from either; write only to `voyager`.
- `assurer_schema_note($pdo)` creates/migrates the `note` table at runtime and syncs `note.nationalite_code` from `personne`. Call it right after the DB require on any page touching `note`. `bulletin/modifier.php`, `bulletin/supprimer.php`, and `enseignant/etudiants.php` currently query `note` without it.
- Filière is denormalized by name: `personne.filiere`, `module.filiere`, and `enseignant.filiere` are `VARCHAR`, not FKs to `filiere.nom`. Renaming a filière does not cascade.
- `plateformes`, `applications`, `activites`, `modules`, `modele_voiture`, `travel_methodes` are comma-joined strings in single columns, not join tables.
- FKs: `personne.nationalite_code → nationalite(code)` RESTRICT; `voyager.nationalite_code` and `voyager.etudiant_id` SET NULL; `note.etudiant_id → personne(id)` CASCADE; `note.module_id → module(id)` RESTRICT; `note` has `UNIQUE (etudiant_id, module_id)`.
- `moyen_transport`, `sport`, `modele_voiture` are referenced by `libelle` (no FK). `filiere.nom` is UNIQUE; `module.code_module` is not.

## Module Voyager (Voyage)

- Table `voyager`; symlink `voyager` → `voyage` (tracked in git) so both URLs work.
- Visible in the form and `liste.php`: `numero`, `civilite`, `nom_prenom`, `filiere`, `adresse`, `pays`, plus the three Voyage-only fields `travel_methodes` (checkboxes), `travel_with` (radio), `description` (textarea).
- All other student fields are hidden behind a collapsed section in the form and omitted from the list table, but are still stored and shown on the printable detail sheet.
- Searching by ID or `numero` in `voyage/voyage.php` checks both `voyager` and `personne`, prints the full record in a printable card, and pre-fills the form. `details.php` is the standalone printable sheet.
- `voyage/` is intentionally separate from `personne/`; changes to Voyage must not modify `personne/`.

## Module Bulletin de Notes (Bulletin / Note)

- Table `note`; symlink `note` → `bulletin`.
- Search a student by ID or `numero`, show their info and photo, pick a module (auto-shows code and coefficient), enter a note from 0 to 20.
- Three actions: Enregistrer (insert), Modifier (update), Supprimer (POST delete). The recap table computes the coefficient-weighted average and prints via `window.print()`.
- `bulletin.php` is the form/search page; `afficher_bulletin.php` renders the printable bulletin.

## Module Enseignants (Enseignant)

- Table `enseignant`. `filiere` is a single `<select>` loaded from the `filiere` table; `modules` is a custom multi-select (`name="modules[]"`, `#modules-dropdown` in `enseignant/enseignant.php`) stored as a comma-separated string of `code_module` values.
- `enseignant/etudiants.php` is the per-teacher class view (students in the teacher's filière, with module, coefficient, note, photo). `filiere/etudiants.php` is a separate view by filière — they are not the same page.

## Conventions

- Fixed option lists (civilités, pays, plateformes, applications, activités, modules, groupes sanguins, travel methods/companions) live in `includes/donnees.php`; forms iterate those arrays and `post_liste()` whitelists against them. Add a new option there, not in a form. Filière, transport, sport, and modèle de voiture are loaded from their tables with the `donnees.php` array as fallback.
- Validation exists in both `js/script.js` and the PHP handler, with duplicated French messages. JS validation is skipped for `formnovalidate` buttons, so PHP is the real gate. Update both when a rule changes.
- Output through `h()`; use prepared statements.
- Uploads go only through `enregistrer_image()` (2 MB cap, `finfo` MIME check, random hex name). `uploads/` must stay writable and its `.htaccess` blocks PHP execution — never remove it. `uploads/` is in `.gitignore`, but older images are still tracked.
- Comments, validation messages, and UI strings are French; keep new text French.
- `.github/copilot-instructions.md` mirrors much of this file; update both when conventions change.
