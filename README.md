# mca/permission

**English** | [Türkçe](README.tr.md)

Laravel 13 controller-based permission system: automatic scanning, layered authorization, and a ready-made admin UI.

## Features

- **Controller scanner** — discovers methods under `Panel/` and `Api/` via reflection
- **Three install modes** — `basic`, `user`, `full` (chosen at install time)
- **Grant chain** — user → department → role (mode-dependent; any match grants access)
- **Middleware** — protect routes with `mca.permission`
- **Ready UI** — `mca-perm-*` prefixed CSS (no clash with Tailwind/Bootstrap)
- **CRUD** — user and department management in `user` / `full` modes
- **i18n** — built-in **English** and **Turkish** (`lang/en`, `lang/tr`)
- **Publish** — customize views, routes, controllers, requests, assets, translations
- Permission names like `panelDashboard.index` (controller + method)

**Requirements:** PHP 8.3+, Laravel 13+

---

## Localization

The admin UI and Artisan commands use Laravel translations.

| Item | Value |
|------|--------|
| Namespace | `mca-permission::permission.*` |
| Helper | `mca_perm('nav.permissions')` |
| Config | `permission.locale` / `MCA_PERMISSION_LOCALE` |
| Default | App locale (`config('app.locale')`) |

```env
# Force Turkish UI regardless of app locale
MCA_PERMISSION_LOCALE=tr

# Or leave empty to follow APP_LOCALE
MCA_PERMISSION_LOCALE=
```

Publish translations to override:

```bash
php artisan vendor:publish --tag=mca-permission-lang
# → lang/vendor/mca-permission/{en,tr}/permission.php
```

Middleware `mca.permission.locale` is included in the default web route stack.

---

## Installation

```bash
composer require mca/permission
php artisan mca:permission:install
php artisan mca:permission:doctor
```

### Modes

| Mode | Grant sources | Extra tables |
|------|---------------|--------------|
| `basic` | Role | `permissions`, `roles`, `role_permission` |
| `user` | User → role | + `user_permission` |
| `full` | User → dept → role | + `department_permission` |

```bash
php artisan mca:permission:install --mode=full --upgrade
```

### Environment

```env
MCA_PERMISSION_ENABLED=true
MCA_PERMISSION_MODE=basic
MCA_PERMISSION_LOCALE=
MCA_PERMISSION_UI_ENABLED=true
MCA_PERMISSION_USER_MODEL=App\Models\User
MCA_PERMISSION_USER_ROLE_COLUMN=role
MCA_PERMISSION_USER_EXCLUSIVE_COLUMN=mca_permission_exclusive
MCA_PERMISSION_DEPARTMENT_MODEL=App\Models\Department
```

---

## Usage

### Route protection

```php
Route::middleware(['auth', 'mca.permission'])
    ->prefix('panel')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
    });
```

### Blade helpers

```blade
@if(mca_can(auth()->user(), 'Panel', 'DashboardController', 'index'))
    ...
@endif

{{ mca_perm('nav.permissions') }}
```

### Web UI

Root user (`role = root`) → `/mca/permission`

```bash
php artisan vendor:publish --tag=mca-permission-assets --force
```

**Exclusive mode:** user can ignore role/department grants and use only checked permissions. Inherited grants show **Role ✓** / **Dept ✓** badges.

---

## Publish tags

| Tag | Output |
|-----|--------|
| `mca-permission-config` | `config/permission.php` |
| `mca-permission-lang` | `lang/vendor/mca-permission/` |
| `mca-permission-views` | `resources/views/vendor/mca-permission/` |
| `mca-permission-assets` | `public/vendor/mca-permission/` |
| `mca-permission-routes` | `routes/mca-permission.php` |
| `mca-permission-controllers` | `app/Http/Controllers/Vendor/McaPermission/` |
| `mca-permission-requests` | `app/Http/Requests/McaPermission/` |
| `mca-permission-departments-stub` | Department model + migration |

---

## Development

```bash
cd packages/mca/permission
composer install
composer test
```

### Standalone Git repo

```bash
git init && git add . && git commit -m "feat: mca/permission v0.2.0"
```

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Routes missing | `php artisan package:discover` |
| 403 — no permission record | Run scanner sync-all |
| 403 — admin UI | Root role only |
| Mode / table mismatch | `php artisan mca:permission:doctor` |
| Exclusive mode not saving | `php artisan migrate` |
| UI wrong language | Set `MCA_PERMISSION_LOCALE` or `APP_LOCALE` |

---

## License

[MIT](LICENSE)
