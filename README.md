# mca/permission

**English** | [Türkçe](README.tr.md)

Laravel 13 controller-based permission system: flexible scanning, layered authorization, modal UI, and a ready-made admin panel.

## Features

- **Controller scanner** — reflection over configurable path segments (`Panel`, `Api`, modules, …)
- **Scan segments** — DB + panel CRUD; config defaults synced on boot
- **Permission CRUD** — manual create/edit/delete; sidebar form for edits
- **Three install modes** — `basic`, `user`, `full`
- **Grant chain** — user → department → role (any match grants access, unless exclusive)
- **Exclusive mode** — user or department can ignore inherited grants
- **Middleware** — `mca.permission` resolves `folder` from scan segments (module-aware)
- **McaUi** — modal, confirm, toast (`mca-ui.js`); no external UI library
- **i18n** — English and Turkish
- Permission names like `panelDashboard.index` (folder + controller + method)

**Requirements:** PHP 8.3+, Laravel 13+

---

## Quick start

```bash
composer require mca/permission
php artisan mca:permission:install
php artisan vendor:publish --tag=mca-permission-assets --force
php artisan mca:permission:doctor
```

Root user → `/mca/permission` → scanner → **Sync all**.

---

## Localization

| Item | Value |
|------|--------|
| Namespace | `mca-permission::permission.*` |
| Helper | `mca_perm('nav.permissions')` |
| Config | `permission.locale` / `MCA_PERMISSION_LOCALE` |

```bash
php artisan vendor:publish --tag=mca-permission-lang
```

---

## Modes

| Mode | Grant sources | Extra tables |
|------|---------------|--------------|
| `basic` | Role | `permissions`, `roles`, `role_permission` |
| `user` | User → role | + `user_permission` |
| `full` | User → dept → role | + `department_permission` |

```bash
php artisan mca:permission:install --mode=full --upgrade
```

---

## Configuration

```env
MCA_PERMISSION_ENABLED=true
MCA_PERMISSION_MODE=full
MCA_PERMISSION_LOCALE=tr
MCA_PERMISSION_USER_MODEL=App\Models\User
MCA_PERMISSION_USER_ROLE_COLUMN=role_id
MCA_PERMISSION_SYNC_SCAN_SEGMENTS=true
```

### Scan segments

Default segments live in `config/permission.php` under `scan.segments`. The panel can add more (e.g. modules):

| Field | Example |
|-------|---------|
| Folder label | `DenemeModule` |
| Path (under `app/`) | `Modules/DenemeModule/Controllers` |
| Namespace | `App\Modules\DenemeModule\Controllers` |

Wildcards in namespace are **not** supported. For all modules use path `Modules` + namespace `App\Modules`.

Boot sync: `scan.sync_segments_on_boot` (env `MCA_PERMISSION_SYNC_SCAN_SEGMENTS`).

---

## Users table

Use numeric `role_id` (recommended):

```bash
php artisan vendor:publish --tag=mca-permission-users-migration
php artisan migrate
```

Ensure `User` model `$fillable` includes `role_id`, `department_id`, `mca_permission_exclusive`.

---

## Route protection

```php
Route::middleware(['auth', 'mca.permission'])->prefix('panel')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// Module example
Route::middleware(['auth', 'mca.permission'])->prefix('deneme')->group(function () {
    Route::get('/', [MainController::class, 'index']);
});
```

Middleware flow:

1. Authenticated?
2. Root? → allow
3. Permission record exists for `folder` + `controller` + `method`?
4. `is_root_only`? → deny
5. Grant chain (user / department / role) — **skipped if user exclusive mode is on**

### Blade

```blade
@if(mca_can(auth()->user(), 'Panel', 'DashboardController', 'index'))
    ...
@endif
```

---

## Exclusive mode

| Context | Effect |
|---------|--------|
| User «Only permissions on this page» | Only checked user grants apply; role and department ignored |
| Department «Ignore role for members» | Members use department + personal grants only |

**Rol ✓** / **Dept ✓** badges show where a grant is defined. In user exclusive mode they are informational only (muted in UI).

---

## Assets (McaUi)

```bash
php artisan vendor:publish --tag=mca-permission-assets --force
```

| File | Purpose |
|------|---------|
| `mca-ui.css` / `mca-ui.js` | Shared design system, modal, toast |
| `mca-permission.css` / `mca-permission.js` | Permission module UI, scanner, permission list form |

After package updates, re-publish assets or copy from `vendor/mca/permission/resources/assets`.

---

## API (root)

Prefix: `/mca/permission/api`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/scanner` | Scan result |
| POST | `/permissions/sync-all` | Add missing + label sync |
| GET/POST/PUT/DELETE | `/scan-segments` | Segment CRUD |
| POST | `/scan-segments/sync-config` | Sync from config |
| PUT | `/roles/{role}/permissions` | Role permissions (`role` = id) |

Web routes also include permission store/update/destroy for the admin list.

---

## Publish tags

| Tag | Output |
|-----|--------|
| `mca-permission-config` | `config/permission.php` |
| `mca-permission-lang` | `lang/vendor/mca-permission/` |
| `mca-permission-views` | `resources/views/vendor/mca-permission/` |
| `mca-permission-assets` | `public/vendor/mca-permission/` |
| `mca-permission-migrations` | Package migrations |
| `mca-permission-users-migration` | `role_id` on users stub |
| `mca-permission-departments-stub` | Department model + migration |
| `mca-permission-routes` | `routes/mca-permission.php` |

---

## Development

```bash
cd packages/mca/permission
composer install
composer test
```

### Standalone Git repo

```bash
git add .
git commit -m "feat: release v0.3.0"
git tag v0.3.0
git push origin main --tags
```

Repository: [github.com/mca43/mca-permission](https://github.com/mca43/mca-permission)

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Routes missing | `php artisan package:discover` |
| 403 — no permission record | Scanner → sync-all |
| 403 — role/dept granted but user blocked | Turn off **user exclusive mode** on user permissions page |
| 403 — admin UI | Root role only |
| Module not scanned | Check segment path/namespace; no `*` wildcards |
| Modal / confirm not working | `vendor:publish --tag=mca-permission-assets --force` |
| `role_id` not saved on user | Add `role_id` to User `$fillable` |
| Mode / table mismatch | `php artisan mca:permission:doctor` |

---

## License

[MIT](LICENSE)
