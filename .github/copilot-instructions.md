# Copilot instructions for this repository

This repository is a vanilla PHP 8 multi-page app for a university assignment. Treat `AGENTS.md` as the repo-specific source of truth for behavior and constraints; it is more specific than generic coding guidance.

## Build, test, and lint commands

- There is no Composer setup, no package.json, no CI, and no dedicated JS test/lint runner.
- PHP syntax validation is the supported verification step:
  - Single file: `php -l path/to/file.php`
  - Project-wide check: `find . -name '*.php' -exec php -l {} \;`
- If the database needs to be reset to the project seed state:
  - `/opt/lampp/bin/mysql -uroot < database/database.sql`
- Local web app base URL:
  - `http://localhost/tp01/`

## High-level architecture

- This is a page-based PHP app, not a framework app. Each module has separate files for form display, form submission, listing, and deletion.
- Core bootstrap/config:
  - `config/database.php` creates the single PDO connection. All pages require it once; do not open additional DB connections.
  - `includes/fonctions.php` contains shared helpers such as `h()` and other utility functions.
  - `includes/header.php` and `includes/footer.php` are partial page includes, not full-page layouts.
- Page contract:
  - Each page must define `$base` and `$titre` before including `includes/header.php`.
  - `$base` is the relative root path (`''` for the root, `'../'` for subdirectories).
- Module structure:
  - `personne/`, `voyage/`, `enseignant/`, `module/`, `nationalite/`, `transport/`, `sport/`, and `filiere/` each follow the same pattern: form page, save handler, list page, and delete handler.
  - Buttons route via `formaction` to handlers such as `enregistrer.php`, `afficher_php.php`, `liste.php`, and `supprimer.php`.
- Database and data model:
  - `database/database.sql` is the canonical schema + seed file; it is idempotent and should be re-imported when schema changes are needed.
  - Dropdowns such as nationalities, transport, sports, filières, and vehicle models are loaded from their respective tables.
  - Several fields are stored as comma-separated strings in a single column (for example `plateformes`, `applications`, `modules`, `travel_methodes`) rather than via join tables.
- Front-end validation:
  - `js/script.js` contains client-side validation for the forms.
  - PHP validation in the save handlers is the real gate; JavaScript is bypassable with `formnovalidate`.

## Key conventions and repo-specific rules

- Use `h()` for all output to prevent XSS.
- Use prepared statements for database queries; the codebase expects this pattern.
- Validation must be mirrored in both JavaScript and PHP; update both together when rules change.
- `post_liste()` in `includes/fonctions.php` whitelists checkbox values using entries from `includes/donnees.php`. When adding a new option, update both the whitelist file and the JS validator.
- File uploads must go through `enregistrer_image()`. Keep the `uploads/` directory writable and do not remove the `.htaccess` file that blocks PHP execution.
- Keep UI strings, comments, and validation messages in French.
- The `voyage/` module is intentionally separate from `personne/`; do not mix the traveler logic with the main student module.
- Special logic in modules:
  - `voyage/`: visible columns are limited to a subset, while extra student fields are hidden by default but still supported in the detailed sheet.
  - `bulletin/`: searching by student ID or student number, showing photo and summary, and computing the average across note entries.
- `config/database.php` is the only DB connection in the project; reuse it instead of creating new connections.
- Do not add a front controller or framework-style routing; this project is intentionally file-per-action and static.

## Working safely in this repo

- Prefer surgical edits in the relevant module folder instead of broad refactors.
- Keep the page contract intact so the shared header/menu continues to render correctly.
- When changing form fields or validation rules, update both the PHP handler and the JavaScript validator.
- Respect the project’s existing upload, prepared-statement, and XSS-safe patterns.
