# mca/permission

**Türkçe** | [English](README.md)

Laravel 13 için controller tabanlı izin sistemi: esnek tarama, çok katmanlı yetkilendirme, modal arayüz ve hazır yönetim paneli.

## Özellikler

- **Controller tarama** — yapılandırılabilir tarama yolları (`Panel`, `Api`, modüller, …)
- **Tarama segmentleri** — DB + panel CRUD; config varsayılanları boot'ta senkron
- **İzin CRUD** — manuel ekleme/düzenleme/silme; düzenleme soldaki formda
- **Üç kurulum modu** — `basic`, `user`, `full`
- **Grant zinciri** — kullanıcı → departman → rol (özel mod hariç herhangi biri yeterli)
- **Özel mod (exclusive)** — kullanıcı veya departman miras izinleri yok sayabilir
- **Middleware** — `mca.permission` segment namespace'inden `folder` çözümler (modül uyumlu)
- **McaUi** — modal, onay, toast (`mca-ui.js`); harici UI kütüphanesi yok
- **Çoklu dil** — `en` ve `tr`
- İzin adları `panelDashboard.index` formatında (klasör + controller + metod)

**Gereksinimler:** PHP 8.3+, Laravel 13+

---

## İçindekiler

- [Hızlı başlangıç](#hızlı-başlangıç)
- [Kurulum](#kurulum)
- [İzin modları](#izin-modları)
- [Yapılandırma](#yapılandırma)
- [Tarama segmentleri](#tarama-segmentleri)
- [Veritabanı](#veritabanı)
- [Kullanım](#kullanım)
- [Özel mod](#özel-mod)
- [Web arayüzü ve assetler](#web-arayüzü-ve-assetler)
- [API referansı](#api-referansı)
- [Publish](#publish)
- [Geliştirme ve Git](#geliştirme-ve-git)
- [Sorun giderme](#sorun-giderme)

---

## Hızlı başlangıç

```bash
composer require mca/permission
php artisan mca:permission:install
php artisan vendor:publish --tag=mca-permission-assets --force
php artisan mca:permission:doctor
```

Root ile giriş → `/mca/permission` → tarayıcı → **Tümünü senkronize et**.

---

## Kurulum

### Composer

```bash
composer require mca/permission
```

### Yerel geliştirme (path repository)

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/mca/permission",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "mca/permission": "@dev"
    }
}
```

### Kurulum komutu

```bash
php artisan mca:permission:install
php artisan mca:permission:install --mode=full --upgrade
php artisan mca:permission:doctor
```

### User tablosu (`role_id`)

```bash
php artisan vendor:publish --tag=mca-permission-users-migration
php artisan migrate
```

`User` modelinde `$fillable`: `role_id`, `department_id`, `mca_permission_exclusive`.

Full mod için departman:

```bash
php artisan vendor:publish --tag=mca-permission-departments-stub
php artisan migrate
```

---

## İzin modları

| Mod | Grant kaynakları | Ek tablolar | UI |
|-----|------------------|-------------|-----|
| `basic` | Rol | `permissions`, `roles`, `role_permission` | İzinler, tarayıcı, roller |
| `user` | Kullanıcı → rol | + `user_permission` | + kullanıcı CRUD, ek izin |
| `full` | Kullanıcı → departman → rol | + `department_permission`, `permission_scan_segments` | + departman CRUD |

**Çözümleme:** Root her şeye erişir. Diğerleri için grant zinciri birleşir (kullanıcı → departman → rol).

---

## Yapılandırma

```env
MCA_PERMISSION_ENABLED=true
MCA_PERMISSION_MODE=full
MCA_PERMISSION_LOCALE=tr
MCA_PERMISSION_USER_ROLE_COLUMN=role_id
MCA_PERMISSION_SYNC_SCAN_SEGMENTS=true
```

| Anahtar | Açıklama |
|---------|----------|
| `mode` | `basic`, `user`, `full` |
| `user_role_column` | Varsayılan `role_id` |
| `scan.segments` | Config varsayılan tarama yolları |
| `scan.sync_segments_on_boot` | Config → DB segment senkronu |
| `ui.assets.ui_js` | `mca-ui.js` yolu |

Çeviriler: `php artisan vendor:publish --tag=mca-permission-lang`

---

## Tarama segmentleri

Tarayıcı panelinde **Tarama yolları** bölümünden veya `config/permission.php` → `scan.segments` ile yönetilir.

| Alan | Örnek (tek modül) |
|------|-------------------|
| Klasör etiketi | `DenemeModule` |
| Yol (`app/` altı) | `Modules/DenemeModule/Controllers` |
| Namespace | `App\Modules\DenemeModule\Controllers` |

Tüm modüller için: yol `Modules`, namespace `App\Modules` (wildcard `*` **desteklenmez**).

Controller `App\Http\Controllers\Controller` extend etmeli. Dosya adı ile sınıf adı PSR-4 uyumlu olmalı.

---

## Veritabanı

| Tablo | Mod |
|-------|-----|
| `permissions` | Tümü |
| `roles` | Tümü |
| `role_permission` (`role_id`) | Tümü |
| `user_permission` | user, full |
| `department_permission` | full |
| `permission_scan_segments` | full (tarama yolları) |
| `users.role_id` | Tümü (önerilen) |
| `users.mca_permission_exclusive` | user, full |

### İzin adı

```
{folder küçük}{ControllerAdi}.{method}
```

Örnek: `DenemeModule` + `MainController` + `index` → `denememoduleMain.index`

---

## Kullanım

### Route koruması

```php
Route::middleware(['auth', 'mca.permission'])->prefix('panel')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

Route::middleware(['auth', 'mca.permission'])->prefix('deneme')->group(function () {
    Route::get('/', [MainController::class, 'index']);
});
```

Middleware sırası:

1. Giriş var mı?
2. Root mü?
3. İzin kaydı var mı? (`folder` segment'ten çözülür)
4. `is_root_only` mü?
5. Grant zinciri (özel modda yalnızca kullanıcı izinleri)

### Blade

```blade
@if(mca_can(auth()->user(), 'DenemeModule', 'MainController', 'index'))
    ...
@endif

{{ mca_perm('nav.permissions') }}
```

### Helper'lar

```php
mca_can(?Authenticatable $user, string $folder, string $controller, ?string $method = null): bool
mca_is_root(?Authenticatable $user): bool
mca_perm(string $key, array $replace = []): string
```

---

## Özel mod

| Bağlam | Etki |
|--------|------|
| Kullanıcı: «Sadece bu sayfadaki izinler geçerli olsun» | Rol ve departman **sayılmaz**; yalnızca işaretli kutular geçerli |
| Departman: «Üye kullanıcıların rol izinlerini yoksay» | Üyeler rol yerine departman + kişisel izin alır |

**Rol ✓** / **Dept ✓** rozetleri iznin nerede tanımlı olduğunu gösterir. Kullanıcı özel modundayken erişim vermez (UI'da soluk + uyarı metni).

Kullanıcı listesinde **Özel mod** rozeti görünür. Departman ataması mor badge ile gösterilir.

---

## Web arayüzü ve assetler

Root (`is_root`) → `/mca/permission`

| Mod | Özellikler |
|-----|------------|
| basic | İzin listesi (CRUD), tarayıcı, roller |
| user | + kullanıcılar, kullanıcı izinleri |
| full | + departmanlar, segment yönetimi |

```bash
php artisan vendor:publish --tag=mca-permission-assets --force
```

| Dosya | Açıklama |
|-------|----------|
| `mca-ui.css` / `mca-ui.js` | Ortak tasarım, modal, toast |
| `mca-permission.css` / `mca-permission.js` | İzin modülü, tarayıcı, liste formu |

Paket güncellemesinden sonra asset'leri yeniden yayınlayın.

---

## API referansı

Prefix: `/mca/permission/api` — `auth` + `mca.permission.root`

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/scanner` | Tarama sonucu |
| POST | `/permissions/sync-all` | Eksik izinler + etiket sync |
| POST | `/permissions/bulk` | Seçili izinleri ekle |
| GET | `/permissions` | İzin listesi (JSON) |
| GET/POST/PUT/DELETE | `/scan-segments` | Segment CRUD |
| POST | `/scan-segments/sync-config` | Config'ten senkron |
| PUT | `/roles/{role}/permissions` | Rol izinleri (`role` = id) |

Web: `POST/PUT/DELETE` `/mca/permission/permissions` — izin listesi CRUD.

---

## Publish

```bash
php artisan vendor:publish --tag=mca-permission-config
php artisan vendor:publish --tag=mca-permission-lang
php artisan vendor:publish --tag=mca-permission-views
php artisan vendor:publish --tag=mca-permission-assets --force
php artisan vendor:publish --tag=mca-permission-migrations
php artisan vendor:publish --tag=mca-permission-users-migration
php artisan vendor:publish --tag=mca-permission-departments-stub
php artisan vendor:publish --tag=mca-permission-routes
```

---

## Geliştirme ve Git

```bash
cd packages/mca/permission
composer install
composer test
```

### GitHub

Depo: [github.com/MCA43/mca-permission](https://github.com/MCA43/mca-permission)

```bash
git add .
git commit -m "feat: release v0.3.0"
git tag v0.3.0
git push origin main
git push origin v0.3.0
```

### Sürüm notları

Detaylar için [CHANGELOG.md](CHANGELOG.md).

---

## Sorun giderme

| Sorun | Çözüm |
|-------|-------|
| Route'lar yok | `php artisan package:discover` |
| 403 — izin kaydı yok | Tarayıcı → sync-all |
| Rol/departman izni var ama 403 | Kullanıcı **özel mod** kapalı mı? |
| Modül taranmıyor | Segment path/namespace; wildcard kullanmayın |
| Modal/onay çalışmıyor | `vendor:publish --tag=mca-permission-assets --force` |
| `role_id` kaydedilmiyor | User `$fillable` içinde `role_id` |
| Tablo/mod uyumsuz | `php artisan mca:permission:doctor` |
| Yanlış dil | `MCA_PERMISSION_LOCALE=tr` |

---

## Lisans

[MIT](LICENSE)
