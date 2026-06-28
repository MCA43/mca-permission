# Changelog

Tüm önemli değişiklikler bu dosyada belgelenir.

Format [Keep a Changelog](https://keepachangelog.com/) esas alınır.

## [Unreleased]

## [0.2.0] - 2026-06-28

### Added
- Built-in **English** and **Turkish** translations (`lang/en`, `lang/tr`)
- `mca_perm()` helper and `mca.permission.locale` middleware
- `mca-permission-lang` publish tag
- `permission.locale` / `MCA_PERMISSION_LOCALE` config
- Üç kurulum modu: `basic`, `user`, `full` (`MCA_PERMISSION_MODE`)
- Grant zinciri: kullanıcı → departman → rol (`GrantResolverRegistry`)
- `mca:permission:install` — mod seçimi, yükseltme (`--upgrade`), seed
- `mca:permission:doctor` — mod, tablo ve exclusive kolon kontrolü
- Kullanıcı CRUD ve kullanıcı izin atama (user/full mod)
- Departman CRUD ve departman izinleri (full mod)
- Özel mod (exclusive): kullanıcı ve departman için «yalnızca seçili izinler»
- Kullanıcı izin matrisi: **Rol ✓**, **Dept ✓**, **Ek izin** göstergeleri
- `userPermissionMatrix()`, `permissionIdsForRoleDisplay()` API'leri
- Mod bazlı migration klasörleri (`modes/user`, `modes/full`)
- Publish tag'leri: views, assets, routes, controllers, requests, departments-stub

### Changed
- İzin formu UX: anlaşılır Türkçe açıklamalar, miras ipuçları
- `syncUserPermissions` / `syncDepartmentPermissions` exclusive kaydı ve hata bildirimi
- README ve sorun giderme bölümü güncellendi

### Fixed
- Exclusive checkbox kaydedilip sayfada işaretli kalmaması (migration + `fresh()` yükleme)
- Root rolünde UI'da rol izinlerinin görünmemesi (`permissionIdsForRoleDisplay`)

## [0.1.0] - 2026-06-28

### Added
- İlk sürüm: `PermissionService`, `PermissionScannerService`
- `mca.permission` middleware
- JSON API (scanner, sync, roles, permissions)
- `mca_can()`, `mca_is_root()` helper'ları
- `McaRoleSeeder` (root, admin, editor)
- Migration: permissions, roles, role_permission
- Hazır web UI (`mca-perm-*` CSS)
