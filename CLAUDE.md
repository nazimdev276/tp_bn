# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

- **Syntax Validation**: `find . -name '*.php' -not -path './.git/*' -exec php -l {} \;`
- **Single File Check**: `php -l path/to/file.php`
- **Database Reset/Seed**: `/opt/lampp/bin/mysql -uroot < database/database.sql`
- **XAMPP/LAMPP Control**: `/opt/lampp/lampp start|restart|stop` (Apache :80, MySQL :3306)
- **Check MySQL Port**: `ss -ltn | grep 3306`

App Base URL: `http://localhost/tp_bn/` (served from `/opt/lampp/htdocs/tp_bn`).

## Architecture & Conventions

### Core Structure
- **Vanilla PHP 8 multi-page app**: No framework, Composer, router, or build tools.
- **File-per-action Routing**: Modules (`personne/`, `enseignant/`, `module/`, `voyage/`, `bulletin/`, `filiere/`, `nationalite/`, `sport/`, `transport/`) use dedicated action files. HTML forms route buttons using `formaction` attributes (e.g. `enregistrer.php`, `afficher_php.php`, `liste.php`, `supprimer.php`).
- **Symlinks**: `note` -> `bulletin`, `voyager` -> `voyage`, `agents.md` -> `AGENTS.md`.

### Page Contract
Every page rendering HTML **must** set the following variables before requiring `includes/header.php`:
1. `$base` — relative path to root (`''` at root, `'../'` in subdirectories).
2. `$titre` — exact string title.

`includes/fonctions.php` must be loaded before `includes/header.php`.

**Nav Highlighting**: `header.php` highlights active navigation by matching `$titre` against `$titresEtudiants`, `$titresEnseignants`, `$titresModules`, `$titresVoyages`, `$titresBulletin`, `$titresTables`. When adding a page, include its title in the corresponding array in `header.php`.

### Database & Helpers
- **Single Connection**: `config/database.php` creates the single PDO instance (`FETCH_ASSOC`, exceptions enabled). Use `require_once`; never instantiate a second connection.
- **CRUD Patterns**:
  - `enseignant/`, `module/`, `filiere/` use `includes/operations/` helpers (`enregistrer_avec_id()`, `supprimer_par_id()`). Note: CRUD helpers assume an `id` primary key and table/column names matching `^[a-z_]+$`.
  - `personne/`, `voyage/`, `bulletin/`, and reference tables use hand-written SQL queries.
- **Dynamic Note Schema**: Call `assurer_schema_note($pdo)` right after `require_once 'config/database.php'` on any page touching the `note` table.
- **Database Quirks**:
  - `voyage` is a SQL view over table `voyager` (`CREATE OR REPLACE VIEW voyage AS SELECT * FROM voyager`). Read from `voyage` or `voyager`; write only to `voyager`.
  - Denormalized filières: `personne.filiere`, `module.filiere`, `enseignant.filiere` store string names rather than FK IDs.
  - Multi-select choices (`plateformes`, `applications`, `activites`, `modules`, `travel_methodes`, `modele_voiture`) are stored as comma-separated strings in single columns.

### Forms & Validation
- **Dual Validation**: Synchronize validation logic in `js/script.js` and PHP form handlers. PHP validation is the real gate (JS is skipped on `formnovalidate`).
- **Option Whitelisting**: Fixed arrays live in `includes/donnees.php`. Handlers use `post_liste()` to validate against these lists.
- **Image Uploads**: Handled exclusively via `enregistrer_image()` (2 MB max, MIME check via `finfo`, randomized hex filename). Do not modify or remove `uploads/.htaccess`.
- **Localization**: UI text, comments, and validation messages must remain in French.
