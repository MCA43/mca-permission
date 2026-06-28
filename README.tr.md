# mca/permission

[Türkçe](README.tr.md) | **English** ([README.md](README.md))

Laravel 13 için controller tabanlı izin sistemi: otomatik tarama, çok katmanlı yetkilendirme, hazır yönetim arayüzü.
## Özellikler

- **Controller tarama** — `Panel/` ve `Api/` altındaki metodları reflection ile bulur
- **Üç kurulum modu** — `basic`, `user`, `full` (kurulumda seçilir)
- **Grant zinciri** — kullanıcı → departman → rol (moda göre; herhangi biri yeterli)
- **Middleware** — `mca.permission` ile route koruması
- **Hazır UI** — `mca-perm-*` önekli CSS (Tailwind/Bootstrap ile çakışmaz)
- **CRUD** — user/full modda kullanıcı ve departman yönetimi dahil
- **Publish** — view, route, controller, request, asset özelleştirmesi
- **Çoklu dil** — `en` ve `tr` (yayınlanabilir çeviri dosyaları)
- İzin adları `panelDashboard.index` formatında üretilir (controller + method)

**Gereksinimler:** PHP 8.3+, Laravel 13+

---

## İçindekiler

- [Kurulum](#kurulum)
- [İzin modları](#izin-modları)
- [Yapılandırma](#yapılandırma)
- [Veritabanı](#veritabanı)
- [Kullanım](#kullanım)
- [Web arayüzü](#web-arayüzü)
- [Özelleştirme](#özelleştirme)
- [API referansı](#api-referansı)
- [Middleware](#middleware)
- [Helper fonksiyonlar](#helper-fonksiyonlar)
- [Publish](#publish)
- [Geliştirme](#geliştirme)
- [Sorun giderme](#sorun-giderme)

---

## Çoklu dil (i18n)

Yönetim arayüzü ve Artisan komutları Laravel çeviri dosyalarını kullanır.

| | |
|--|--|
| Namespace | `mca-permission::permission.*` |
| Helper | `mca_perm('nav.permissions')` |
| Config | `permission.locale` / `MCA_PERMISSION_LOCALE` |
| Varsayılan | Uygulama dili (`config('app.locale')`) |

```env
MCA_PERMISSION_LOCALE=tr
```

Çevirileri özelleştirmek için:

```bash
php artisan vendor:publish --tag=mca-permission-lang
```

Route grubunda `mca.permission.locale` middleware varsayılan olarak eklenir.

---

## Kurulum

### Composer

```bash
composer require mca/permission
```

Laravel auto-discovery ile `PermissionServiceProvider` yüklenir.

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

# Mod belirterek
php artisan mca:permission:install --mode=basic
php artisan mca:permission:install --mode=user
php artisan mca:permission:install --mode=full

# Mod yükseltme
php artisan mca:permission:install --mode=full --upgrade

# Sağlık kontrolü
php artisan mca:permission:doctor
```

Komut config publish, migration, asset publish ve (isteğe bağlı) rol seed işlemlerini yapar.

### User tablosu

`users` tablosunda rol kolonu gerekir (varsayılan: `role`):

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('role', 64)->default('editor');
    $table->boolean('is_active')->default(true);
});
```

Full mod için isteğe bağlı `department_id`:

```bash
php artisan vendor:publish --tag=mca-permission-departments-stub
php artisan migrate
```

---

## İzin modları

| Mod | Grant kaynakları | Ek tablolar | UI |
|-----|------------------|-------------|-----|
| `basic` | Rol | `permissions`, `roles`, `role_permission` | İzinler, tarayıcı, roller |
| `user` | Kullanıcı → rol | + `user_permission` | + kullanıcı CRUD ve ek izin |
| `full` | Kullanıcı → departman → rol | + `department_permission` | + departman CRUD ve ortak izin |

**Çözümleme:** Root kullanıcı her şeye erişir. Diğerleri için atanmış izinler birleşir (kullanıcı → departman → rol).

**Özel modlar (exclusive):**
- Kullanıcıda **«Sadece bu sayfadaki izinler geçerli olsun»** → rol ve departman izinleri devre dışı; yalnızca işaretlenen izinler geçerli
- Departmanda **«Üye kullanıcıların rol izinlerini yoksay»** → üyeler rol yerine departman + kişisel izin alır

**Kullanıcı izin ekranında göstergeler:**
| Gösterge | Anlamı |
|----------|--------|
| **Rol ✓** | İzin kullanıcının rolünden geliyor (checkbox boş olsa da erişimi vardır) |
| **Dept ✓** | İzin departmandan geliyor |
| **Ek izin** | Yalnızca bu kullanıcıya doğrudan atanmış |

Checkbox = ekstra izin vermek içindir; rolde zaten varsa **Rol ✓** etiketi görünür.

---

## Yapılandırma

Publish: `php artisan vendor:publish --tag=mca-permission-config`

### Ortam değişkenleri

```env
MCA_PERMISSION_ENABLED=true
MCA_PERMISSION_MODE=basic
MCA_PERMISSION_LOCALE=
MCA_PERMISSION_UI_ENABLED=true
MCA_PERMISSION_USER_MODEL=App\Models\User
MCA_PERMISSION_USER_ROLE_COLUMN=role
MCA_PERMISSION_USER_EXCLUSIVE_COLUMN=mca_permission_exclusive
MCA_PERMISSION_DEPARTMENT_MODEL=App\Models\Department
MCA_PERMISSION_DEPARTMENT_EXCLUSIVE_COLUMN=mca_permission_exclusive
```

### Önemli config anahtarları

| Anahtar | Açıklama |
|---------|----------|
| `mode` | `basic`, `user`, `full` |
| `user_model` / `user_role_column` | Auth modeli ve rol kolonu |
| `user.default_role` | Yeni kullanıcı varsayılan rolü |
| `user.exclusive_column` | «Sadece seçili izinler» kolonu (kullanıcı) |
| `department.*` | Full mod departman modeli ve pivot kolonu |
| `department.exclusive_column` | «Rol yoksay» kolonu (departman) |
| `controllers.web` | Controller override map |
| `routes.load_package_routes` | `false` + publish route ile özelleştirme |
| `scan.segments` | Taranacak controller klasörleri |
| `labels.controllers` | Modül Türkçe etiketleri |

---

## Veritabanı

| Tablo | Mod |
|-------|-----|
| `permissions` | Tümü |
| `roles` | Tümü |
| `role_permission` | Tümü |
| `user_permission` | user, full |
| `department_permission` | full |
| `users.mca_permission_exclusive` | user, full (özel mod) |
| `departments.mca_permission_exclusive` | full (departman özel mod) |

### İzin adı formatı

```
{folder}{ControllerAdi}.{method}
```

Örnek: `Panel` + `DashboardController` + `index` → `panelDashboard.index`

---

## Kullanım

### Route koruması

```php
Route::middleware(['auth', 'mca.permission'])
    ->prefix('panel')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
    });
```

### İzinleri senkronize etme

Root ile giriş yapın → `/mca/permission/scanner` → **Tara** veya **Tümünü senkronize et**.

### Blade

```blade
@if(mca_can(auth()->user(), 'Panel', 'DashboardController', 'index'))
    ...
@endif

@if(mca_is_root(auth()->user()))
    ...
@endif
```

---

## Web arayüzü

Root kullanıcı (`role = root`) için `/mca/permission` altında yönetim paneli.

| Mod | Özellikler |
|-----|------------|
| basic | İzin listesi, tarayıcı, rol CRUD, rol izinleri |
| user | + kullanıcı CRUD, kullanıcıya ek izin, özel mod |
| full | + departman CRUD, departman izinleri, departman özel mod |

### Kullanıcı izinleri ekranı

- **Normal mod:** Rol ve departman izinleri korunur; checkbox ile ek izin verilir
- **Özel mod:** Yalnızca işaretli izinler geçerlidir; kullanıcı listesinde **Özel mod** rozeti görünür
- Miras izinler mavi **Rol ✓** / turuncu **Dept ✓** etiketleriyle gösterilir

Stiller: `public/vendor/mca-permission/mca-permission.css` (`mca-perm-*` sınıfları).

```bash
php artisan vendor:publish --tag=mca-permission-assets --force
```

---

## Özelleştirme

```bash
php artisan vendor:publish --tag=mca-permission-config
php artisan vendor:publish --tag=mca-permission-lang
php artisan vendor:publish --tag=mca-permission-views
php artisan vendor:publish --tag=mca-permission-assets --force
php artisan vendor:publish --tag=mca-permission-routes
php artisan vendor:publish --tag=mca-permission-controllers
php artisan vendor:publish --tag=mca-permission-requests
php artisan vendor:publish --tag=mca-permission-departments-stub
```

- **View:** `resources/views/vendor/mca-permission/` — tasarımı buradan özelleştirin
- **Controller:** config map veya publish + extend
- **Route:** `routes.load_package_routes = false` + `routes/mca-permission.php`

---

## API referansı

Prefix: `/mca/permission/api` — `auth` + `mca.permission.root`

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/scanner` | Tarama sonucu |
| POST | `/permissions/sync-all` | Eksik izinleri ekle + etiket sync |
| POST | `/permissions/bulk` | Seçili izinleri ekle |
| POST | `/permissions/sync-labels` | Etiketleri güncelle |
| GET | `/permissions` | İzin listesi |
| GET | `/roles` | Rol listesi |
| PUT | `/roles/{slug}/permissions` | Role izin ata |

---

## Middleware

| Alias | Açıklama |
|-------|----------|
| `mca.permission` | Panel/API route koruması |
| `mca.permission.root` | Yönetim UI (yalnızca root) |

`mca.permission` kontrol sırası:

1. Giriş yapılmış mı?
2. Root mü? → geç
3. İzin kaydı var mı? → yoksa 403
4. `is_root_only` mi? → red
5. Moda göre grant zinciri (kullanıcı / departman / rol)

---

## Helper fonksiyonlar

```php
mca_can(?Authenticatable $user, string $folder, string $controller, ?string $method = null): bool
mca_is_root(?Authenticatable $user): bool
mca_perm(string $key, array $replace = []): string
```

---

## Publish

Tüm tag'ler yukarıdaki [Özelleştirme](#özelleştirme) bölümünde listelenmiştir.

---

## Geliştirme

```bash
cd packages/mca/permission
composer install
composer test
```

Host uygulamada:

```bash
composer dump-autoload
php artisan package:discover
php artisan mca:permission:doctor
php artisan route:list --path=mca
```

### Ayrı Git deposu

Paketi bağımsız repo olarak yayınlamak için `packages/mca/permission` klasörünü kök alın. `vendor/` ve `.phpunit.cache` commit edilmez (`.gitignore` hazır).

```bash
git init
git add .
git commit -m "feat: mca/permission v0.2.0"
git tag v0.2.0
```

---

## Sorun giderme

| Sorun | Çözüm |
|-------|-------|
| Route'lar yok | `php artisan package:discover` |
| 403 — izin tanımı yok | Tarayıcıda sync-all |
| 403 — yönetim UI | Yalnızca `root` rolü |
| Mod / tablo uyumsuz | `php artisan mca:permission:doctor` |
| Özel mod kaydedilmiyor | `php artisan migrate` (exclusive kolon migration'ları) |
| Miras rozeti görünmüyor | İlgili role/departmana izin atanmış mı kontrol edin |
| Yanlış dil | `MCA_PERMISSION_LOCALE` veya `APP_LOCALE` ayarlayın |
| Full mod departman | `mca-permission-departments-stub` publish + migrate |

---

## Lisans

[MIT](LICENSE)
