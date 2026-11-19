# Restrukturisasi Kode Sales & Alokasi Router MikroTik Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengubah pengelompokan kode sales menjadi per Router ID dengan pemetaan dinamis simple queue (LITE/GAMER, SEWAN/SKM) dan central PPPoE server (RO_ZTE) di ISP Auto-PSB.

**Architecture:** Menerapkan service-driven resolver context di `MikrotikProvisionerService` untuk mengarahkan alokasi slot IP dan penamaan queue secara dinamis ke 4 router (`RO_ZTE`, `RO_POS1`, `RO_FK`, `RO_GAYUH`), memperbarui database seeder 17 sales, serta menampilkan grouping sales per router di UI Admin NOC dan live preview form teknisi.

**Tech Stack:** Laravel 13, PHP 8.3+, MariaDB, RouterOS API, Blade, Tailwind CSS v4, Alpine.js, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-11-19-router-sales-grouping-design.md`

## Global Constraints
- Target platform: Google Antigravity. Jangan mengubah konfigurasi Kilo Code, Claude Code, Cursor, atau agent lain.
- Anti-slop: kode ringkas, tanpa komentar boilerplate, CSS konsisten dengan Ocean Blue Design System.
- Non-destructive: menjaga seluruh perbaikan security dan UX sebelumnya (private photo storage, CSRF/SSRF guard, rate limiting, atomic locking).

## Review Focus
1. Gayuh (tanpa sales) paket 10M, 15M, 20M harus dialokasikan ke `RO_POS1` dengan prefix `LITE`.
2. Gayuh (tanpa sales) paket 30M, 50M harus dialokasikan ke `RO_GAYUH` dengan prefix `GAMER`.
3. Habudin ODP `GSA` atau `GSB` harus menghasilkan prefix `SEWAN` di `RO_POS1`.
4. Habudin ODP `GSMA` harus menghasilkan prefix `SKM` di `RO_POS1`.
5. PAY (`PN`) dan PGB (`PGB`) harus dialokasikan ke `RO_FK` bersama `FK`, `SN`, `JB`, `JN`, `KD`.

---

### Task 1: Seeder Database & Fixtures Topologi 4 Router
**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/PsbProvisioningTest.php`

**Interfaces:**
- Produces: 4 routers (`RO_ZTE`, `RO_POS1`, `RO_FK`, `RO_GAYUH`), 17 sales codes (`01`–`17`), dan slots awal.

- [ ] **Step 1: Update `DatabaseSeeder.php`**
  Konfigurasikan 4 router:
  - `RO_ZTE`: `is_pppoe_server = true`
  - `RO_POS1`, `RO_FK`, `RO_GAYUH`: `is_pppoe_server = false`, `lan_interface = 'bridge-local'`
  Tambahkan slot IP untuk ketiga router distribusi.
  Tambahkan 17 data sales sesuai spesifikasi.
  Tambahkan ODP `GSA-01`, `GSB-01`, `GSMA-01` dengan `status_upgrade = 'belum_upgrade'`.

- [ ] **Step 2: Jalankan migration/seeder test**
  Run: `php artisan db:seed --class=DatabaseSeeder`

---

### Task 2: Dynamic Routing Resolver & Provisioning Engine
**Files:**
- Modify: `app/Services/MikrotikProvisionerService.php`
- Test: `tests/Feature/PsbProvisioningTest.php`

**Interfaces:**
- Produces: `resolveRoutingContext(PsbOrder $order): array{router: Router, queue_prefix: string}`
- Consumes: `$order->paket`, `$order->odp`, `$order->sales`

- [ ] **Step 1: Tulis unit/feature test untuk resolver dinamis di `tests/Feature/PsbProvisioningTest.php`**
  Tambahkan test case:
  - `test_gayuh_static_ip_routes_to_ro_pos1_with_lite_for_20m_and_below()`
  - `test_gayuh_static_ip_routes_to_ro_gayuh_with_gamer_for_above_20m()`
  - `test_habudin_static_ip_routes_to_sewan_and_skm_based_on_odp()`
  - `test_fk_router_handles_pay_pgb_and_other_fk_sales()`
  - `test_pppoe_provisioning_targets_ro_zte_with_sales_pppoe_code()`

- [ ] **Step 2: Jalankan test untuk memverifikasi kegagalan awal**
  Run: `php artisan test --filter=PsbProvisioningTest`

- [ ] **Step 3: Implementasikan `resolveRoutingContext()` di `MikrotikProvisionerService.php`**
  Implementasikan logika seleksi router dan prefix queue.
  Integrasikan ke `provisionStaticIp()` untuk mengklaim slot IP dari `$targetRouter`.
  Pastikan `provisionPppoe()` selalu menggunakan router `RO_ZTE` dan menyertakan kode sales 2 digit.

- [ ] **Step 4: Jalankan test untuk memverifikasi kelolosan test**
  Run: `php artisan test --filter=PsbProvisioningTest`

---

### Task 3: Admin UI - Master Data Router Pengelompokan Sales per Router ID
**Files:**
- Modify: `resources/views/admin/routers/index.blade.php`
- Modify: `app/Http/Controllers/Admin/RouterController.php`

- [ ] **Step 1: Perbarui Controller dan View Router**
  Tampilkan daftar badge kode sales dan prefix simple queue yang dialokasikan pada setiap router:
  - `RO_ZTE`: badge PPPoE Server Utama (semua ODP Upgrade, sales 01–17)
  - `RO_POS1`: badges `PS (04)`, `SALES (07, 13)`, `BOY (11)`, `ND (12)`, `LITE (01 - ≤20M)`, `SANURI (10)`, `SEWAN (09 - GSA/GSB)`, `SKM (09 - GSMA)`, `RK (05)`, `KN (06)`
  - `RO_FK`: badges `FK (16)`, `SN (15)`, `JB (02)`, `JN (03)`, `KD (17)`, `PN (08)`, `PGB (14)`
  - `RO_GAYUH`: badge `GAMER (01 - >20M)`

---

### Task 4: Admin UI - Master Data Sales & Pemetaan Router
**Files:**
- Modify: `resources/views/admin/sales/index.blade.php`

- [ ] **Step 1: Perbarui View Master Sales**
  Pastikan tabel menampilkan kolom Kode PPPoE (01–17), Kode Simple Queue, dan nama router distribusi dengan badge dinamis untuk GAYUH dan HABUDIN.

---

### Task 5: Form PSB Teknisi & Live Preview Telegram
**Files:**
- Modify: `resources/views/psb/create.blade.php`

- [ ] **Step 1: Sinkronkan Form Teknisi & Live Preview**
  Pastikan dropdown sales mencakup 17 opsi sales.
  Perbarui JavaScript `liveTelegramPreview()` agar preview otomatis menampilkan:
  - Untuk ODP Upgrade: `ODP_01_XX_00001_NAMA` dengan kode PPPoE sales yang dipilih.
  - Untuk Static IP Tanpa Sales: `LITE-xxxxx-NAMA` jika paket <= 20M, `GAMER-xxxxx-NAMA` jika paket > 20M.
  - Untuk Static IP Habudin: `SEWAN-xxxxx-NAMA` jika ODP GSA/GSB, `SKM-xxxxx-NAMA` jika ODP GSMA.
  - Badge router target di preview (`RO_POS1`, `RO_FK`, `RO_GAYUH`, atau `RO_ZTE`).

---

### Task 6: Verifikasi End-to-End, Asset Compilation & Code Styling
**Files:**
- All touched files

- [ ] **Step 1: Jalankan seluruh test suite**
  Run: `php artisan test`

- [ ] **Step 2: Format kode dengan Pint**
  Run: `php vendor/bin/pint --dirty`

- [ ] **Step 3: Compile production assets**
  Run: `npm run build`
