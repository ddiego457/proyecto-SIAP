# AGENTS.md - SIAP

SIAP is a PHP MVC web application for managing university budget requests (Sistema Integrado de Anteproyecto Presupuestario - UPTAEB). This document provides repo-specific guidance for AI agents working in this codebase.

## Architecture (MVC + Front Controller)

- **Routing**: Custom Front Controller in `app/controller/FrontController.php`. Routes via `$_GET['url']` parameter - maps `?url=X` to `app/controller/XController.php`. Default/empty routes go to `loginDesingController.php`.
- **Pattern**: MVC with clear separation: `app/controllers/*Controller.php`, `app/models/*Model.php`, `app/views/*/`.
- **Entry point**: `index.php` at repo root - initializes session and instantiates `FrontController`. Requires `vendor/autoload.php` (Composer PSR-4).
- **Namespace**: `EquipoSiap\Siap\` autoloaded from `app/` (see composer.json).
- **Response flow**: User → View (HTML/JS) → Controller (via GET/POST with ?url=type=...) → Model (extends ConnectDB) → DB → Controller → View (JSON for AJAX/DataTables or HTML).

## Key Files & Locations

- `app/config/Connect/ConnectDB.php`: Abstract PDO base class. Hardcoded DB creds (`host=localhost, dbname=siap_db, user=root, pass=''`, utf8mb4). Models extend this.
- `app/helpers/SessionTrait.php`: Session bootstrap trait (`EquipoSiap\Siap\helpers\SessionTrait`) - starts session, enforces auth (redirects to `?url=inicio` if not logged in) and module authorization per role via `$modulosPorRol` (Administrador = all modules; Coordinador = `proveedor`, `productosServicios`, `reporte`, `periodo`; Usuario = `reporte`, `requerimiento`). Used by `FrontController`, which includes module controllers inside its methods, so controllers call `$this->requireLogin()` / `$this->requireModule('X')`. Note: the old `app/config/session.php` documented previously does NOT exist.
- `app/controller/FrontController.php`: URL dispatcher, includes 404/error handling with detailed exception info.
- `app/view/layout/`: Shared layout - `head.php`, `sidebar.php`, `foot.php`. Views include layout files.
- `assets/`: Frontend assets (CSS, JS, images). JS files named after modules (e.g., `requerimiento.js`).
- `vendor/`: Composer dependencies (mpdf ^8.3, phpspreadsheet ^5.9). Do not modify.

## Data Layer

- **Database**: MySQL/MariaDB (`siap_db.sql` is schema + seed data). Connection via PDO with ERRMODE_EXCEPTION, FETCH_ASSOC.
- **Models**: Extend `ConnectDB`. Most follow pattern: constructor sets `$this->conex = $this->getConnection()`, public methods wrap private execution methods (e.g., `getAll()` calls `executeSelectAll()`). 
- **Important tables**: `dependencias`, `partidas`, `items_partida`, `requerimientos`, `detalle_req`, `anio_fiscal`, `periodos_entrega`, `proveedores/contactos`, `responsables/cargo`, `tasa_bcv`, `roles`.
- **Roles**: Seeded as 1=Administrador, 2=Usuario, 3=Coordinador (identified by `roles.descripcion` text, matched in `SessionTrait` and `sidebar.php`). Roles have a full CRUD in the UI (`?url=responsable&type=main` → "Roles" button); users (`responsables`) need an active `cargo` (dependency) to log in. Login redirects per role via `SessionTrait::$homeUrlPorRol`.

## Development & Execution

- **Environment**: Designed for XAMPP/LAMPP (Apache + PHP + MySQL). Working dir `/opt/lampp/htdocs/SIAP` suggests this setup.
- **No build step**: This is vanilla PHP - no npm build, no webpack. JS/CSS served directly from `assets/`.
- **Dependencies**: Run `composer install` if `vendor/` is missing. `composer.json` defines PSR-4 and deps. Vendor is gitignored.
- **Database setup**: Import `siap_db.sql` into MySQL database named `siap_db` (default root/no password). Update `ConnectDB.php` if credentials differ in your env.
- **Running**: Access via web server (e.g., http://localhost/SIAP). No CLI entrypoint for app logic beyond PHP syntax check.
- **Syntax check**: `php -l <file.php>` to verify syntax.

## Code Conventions & Patterns

- **File naming**: Controllers end with `Controller.php`, Models with `Model.php`. Class names often match filename (case-sensitive in includes).
- **URL pattern**: `?url=<controller>&type=<action>` (e.g., `requerimiento&type=main`, `requerimiento&type=register`).
- **AJAX endpoints**: Controllers check `$_POST` for flags (e.g., `isset($_POST['getAll'])`, `isset($_POST['guardarPartida'])`) and return JSON via `echo json_encode(...)` then `die()`.
- **Sessions**: Store `id_dep`, `rol`, `usuario`, `id_responsable`. Role check uses the `roles.descripcion` string (e.g., "Administrador", "Coordinador" - note case); `$_SESSION['rol']` is set from the DB at login.
- **Error handling**: FrontController catches exceptions and shows `app/view/errorView.php` with detailed error. Some controllers return JSON error responses.
- **DataTables**: Used extensively in views; JS posts to controller endpoints returning `{"data": [...]}`.
- **Spanish UI/comments**: Codebase is primarily in Spanish (comments, strings, table names). Follow existing language/style.

## Module Organization

Controllers/Models/Views grouped by domain:
- `requerimiento` (main module) - budget requirements
- `dependencia`, `responsable`, `proveedor` - entities
- `productosServicios`, `partidas` - catalogs
- `anioFiscal`, `periodo`, `tasaBCV` - fiscal configuration
- `reporte` - reports (Excel/PDF via PhpSpreadsheet/mpdf)
- `loginDesing`, `logout` - auth

## Testing & Quality

- **No test framework** found (no phpunit configs outside vendor, no test files). Do not add tests unless explicitly requested.
- **No lint/format tools configured** (no phpcs, phpstan, prettier config visible). Follow existing code style and conventions.
- **CI**: Only workflows in `vendor/` packages, none in repo root.

## Important Constraints & Gotchas

- **Case sensitivity**: PHP includes are case-sensitive on Linux. Filenames like `loginDesingController.php` (capital D/S) match exactly.
- **Session auth**: `SessionTrait::requireModule()` blocks modules not allowed for the current role - be aware when testing endpoints (each module controller calls it with its module name).
- **Hardcoded DB config**: Credentials in `ConnectDB.php` are environment-specific (localhost/root/no pass). Not suitable for production as-is.
- **UTF-8 handling**: Connection sets `SET NAMES utf8mb4` to avoid encoding issues.
- **AJAX responses**: Controllers often `die()` after echoing JSON - don't add extra output.
- **XSS prevention**: Views use `htmlspecialchars()` in places (see sidebar, errorView). Be consistent when outputting user data.
- **Transaction usage**: Models like `requerimientoModel` use `beginTransaction()`/`commit()`/`rollBack()` for multi-step operations.

## Agent Guidance

- **Trust executable sources**: If docs conflict with code/config, trust the code (especially FrontController, SessionTrait, ConnectDB).
- **Minimal changes**: Preserve existing patterns, naming, and Spanish terminology. This is a working production system.
- **Path references**: Use paths relative to repo root (`/opt/lampp/htdocs/SIAP/`).
- **When editing**: Use edit tool for targeted changes; preserve exact indentation. PHP files mix spaces/tabs as-is.
- **Investigate before changes**: Controllers call models; models extend ConnectDB; views expect specific vars - trace data flow when modifying.
- **Security note**: This is an internal university system with basic auth - don't introduce breaking changes to session/role logic without understanding impact.