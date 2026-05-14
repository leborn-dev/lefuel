# LeFuel Architecture

LeFuel is a fork of FuelPHP 1.8 targeting PHP 8.1+. This document describes every top-level folder and its purpose.

---

## Root

| Path | Purpose |
|------|---------|
| `composer.json` | Dependency manifest. Packages install into `fuel/vendor/` (third-party) and `fuel/core/`, `fuel/packages/*/` (FuelPHP packages). |
| `oil` | CLI entry-point. `php oil <command>` runs generators, migrations, tasks, and the built-in test runner. |
| `Dockerfile` | Single-stage `php:8.3-cli` image that serves the app on port 8000. |
| `docker-compose.yml` | Defines the `app` service (this image) and a `mysql:8.0` service with a persistent named volume. |
| `.env.example` | Template for the `.env` file that each environment copies and fills in. Never committed. |
| `.github/workflows/ci.yml` | GitHub Actions matrix CI — runs PHPUnit against PHP 8.1, 8.2, and 8.3 with a MySQL 8 sidecar. |

---

## `fuel/`

The entire PHP runtime lives here. Only `fuel/app/` is committed; everything else is Composer-installed and git-ignored.

### `fuel/app/`

Application code you write and own.

| Sub-path | Purpose |
|----------|---------|
| `bootstrap.php` | Application bootstrap. Loaded by `public/index.php`; sets the app path and environment, then hands off to the FuelPHP kernel. |
| `classes/controller/` | HTTP controllers. Each file is a class named `Controller_<Name>` extending `Controller` (or a sub-type). Methods prefixed `action_` map to URL segments. |
| `classes/model/` | Data-layer models. Extend `\Orm\Model` for Active Record, or use raw `DB::` calls. |
| `classes/presenter/` | Presenter/ViewModel layer. Sits between controllers and views, keeps logic out of templates. |
| `config/` | Configuration files. `config.php` is the master config; `db.php` holds database DSNs. Sub-folders (`development/`, `production/`, `staging/`, `test/`) override settings per `FUEL_ENV`. `routes.php` maps URL patterns to controller/action pairs. |
| `lang/` | i18n strings, keyed by locale (`en/`, etc.). |
| `logs/` | Runtime log files (git-ignored below day level). |
| `cache/` | File-based cache output (git-ignored). |
| `migrations/` | Numbered migration files run via `php oil r migrate`. |
| `modules/` | Self-contained feature modules. Each module mirrors the `app/` structure and is loaded on demand. |
| `tasks/` | CLI task classes run via `php oil r <task>`. `robots.php` is the bundled example. |
| `tests/` | PHPUnit test suites mirroring `classes/` (controller, model, presenter, view sub-directories). |
| `themes/` | Theme asset sets for the Theme package (optional). |
| `tmp/` | Transient working files (e.g. upload staging). Git-ignored. |
| `vendor/` | App-level Composer packages (distinct from `fuel/vendor/`). Rarely used directly. |
| `views/` | PHP view templates. Organised as `views/<controller>/<action>.php`. |

### `fuel/core/` *(git-ignored, Composer-installed)*

The FuelPHP kernel: autoloader, base classes, Input, Output, Request, Response, Session, Security, and all core helpers. Installed to `fuel/core/` via the `composer/installers` path rule.

### `fuel/packages/` *(git-ignored, Composer-installed)*

Optional first-party packages installed alongside core:

| Package | Purpose |
|---------|---------|
| `auth/` | Authentication (login, ACL, hashing). |
| `email/` | Email sending abstraction. |
| `oil/` | CLI tool internals (generators, scaffolding). |
| `orm/` | Object-Relational Mapper (Active Record pattern). |
| `parser/` | Template engine bridge (Twig, Smarty, Mustache, etc.). |

### `fuel/vendor/` *(git-ignored, Composer-installed)*

All Composer third-party dependencies (e.g. `phpunit/phpunit`, `fuelphp/upload`). Managed entirely by Composer; never edit manually.

---

## `public/`

The web root. Point your web server's `document_root` here.

| Sub-path | Purpose |
|----------|---------|
| `index.php` | Front controller. Sets `DOCROOT`, loads `fuel/app/bootstrap.php`, and dispatches the request. |
| `.htaccess` | Apache rewrite rules that funnel all requests to `index.php`. |
| `web.config` | IIS equivalent of `.htaccess`. |
| `assets/` | Static assets served directly: Bootstrap CSS/JS and Glyphicon fonts. Organised as `assets/{css,js,fonts,img}/`. |
| `favicon.ico` | Default favicon. |

---

## Request Lifecycle (summary)

```
Browser → public/index.php
         → fuel/app/bootstrap.php   (env, paths)
         → fuel/core (Request/Router)
         → fuel/app/config/routes.php
         → Controller_<Name>::action_<name>()
         → View::forge('folder/template')
         → Response → Browser
```
