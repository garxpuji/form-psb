# Design Specification: Restrukturisasi Kode Sales & Alokasi Router MikroTik

- **Tanggal**: 2026-11-19
- **Status**: Proposed (Menunggu Persetujuan Pengguna)
- **Modul**: Master Router, Master Sales, Provisioning Engine, PSB Order Form

---

## 1. Ringkasan & Tujuan
Mengubah arsitektur pemetaan kode sales, penamaan simple queue, dan alokasi router distribusi MikroTik per **Router ID**, serta mengintegrasikan pemetaan dinamis berbasis kecepatan bandwidth (paket) dan prefix ODP untuk mode Static IP, dengan satu router pusat PPPoE Server untuk seluruh ODP Upgrade.

---

## 2. Matriks Pemetaan Router & Kode Sales

### A. MikroTik Router Topologi
| Nama Router | Peran | Alokasi Kode Simple Queue / Target |
|---|---|---|
| **RO_ZTE** | Central PPPoE Server (`is_pppoe_server = true`) | Seluruh ODP status `upgrade` (Sales 01–17) |
| **RO_POS1** | Router Distribusi Simple Queue | `PS`, `SALES`, `BOY`, `ND`, `LITE`, `SANURI`, `SEWAN`, `SKM`, `RK`, `KN` |
| **RO_FK** | Router Distribusi Simple Queue | `FK`, `SN`, `JB`, `JN`, `KD`, `PN`, `PGB` *(PN & PGB dialokasikan ke RO_FK sesuai konfirmasi)* |
| **RO_GAYUH** | Router Distribusi Simple Queue | `GAMER` (Paket > 20M untuk Gayuh / Tanpa Sales) |

---

### B. Matriks 17 Sales & Aturan Prefix Queue
| No | Nama Sales | Kode PPPoE | Kode Simple Queue | Target Router | Aturan / Kondisi Dinamis |
|:---:|---|:---:|:---:|:---:|---|
| 1 | **GAYUH (Tanpa Sales)** | `01` | `LITE` / `GAMER` | `RO_POS1` / `RO_GAYUH` | Paket 10M–20M: Prefix `LITE` di `RO_POS1`.<br>Paket > 20M (30M, 50M): Prefix `GAMER` di `RO_GAYUH`. |
| 2 | **MIFTAH** | `02` | `JB` | `RO_FK` | Statis ke `RO_FK` |
| 3 | **JABRIK** | `03` | `JN` | `RO_FK` | Statis ke `RO_FK` |
| 4 | **MAMAT S** | `04` | `PS` | `RO_POS1` | Statis ke `RO_POS1` |
| 5 | **ROKI** | `05` | `RK` | `RO_POS1` | Statis ke `RO_POS1` |
| 6 | **SUBUR** | `06` | `KN` | `RO_POS1` | Statis ke `RO_POS1` |
| 7 | **MALIK** | `07` | `SALES` | `RO_POS1` | Statis ke `RO_POS1` |
| 8 | **PAY** | `08` | `PN` | `RO_FK` | Statis ke `RO_FK` |
| 9 | **HABUDIN** | `09` | `SEWAN` / `SKM` | `RO_POS1` | ODP `GSA` atau `GSB`: Prefix `SEWAN`.<br>ODP `GSMA`: Prefix `SKM`. Default: `SEWAN`. |
| 10 | **SANURI** | `10` | `SANURI` | `RO_POS1` | Statis ke `RO_POS1` |
| 11 | **RW BOY** | `11` | `BOY` | `RO_POS1` | Statis ke `RO_POS1` |
| 12 | **NADIH** | `12` | `ND` | `RO_POS1` | Statis ke `RO_POS1` |
| 13 | **MALIK-KOLE** | `13` | `SALES` | `RO_POS1` | Statis ke `RO_POS1` |
| 14 | **PGB** | `14` | `PGB` | `RO_FK` | Statis ke `RO_FK` |
| 15 | **SN** | `15` | `SN` | `RO_FK` | Statis ke `RO_FK` |
| 16 | **FKDS** | `16` | `FK` | `RO_FK` | Statis ke `RO_FK` |
| 17 | **SUMARDI** | `17` | `KD` | `RO_FK` | Statis ke `RO_FK` |

---

## 3. Detail Arsitektur & Implementasi

### 3.1. Provisioning Engine (`MikrotikProvisionerService`)
- Metode resolver baru: `resolveRoutingContext(PsbOrder $order): array{router: Router, queue_prefix: string}`:
  1. Mengecek sales code & nama sales.
  2. Untuk Gayuh (sales `01` atau order tanpa sales):
     - Membaca bandwidth dari paket (`nama_paket` atau `queue_max_limit`).
     - Jika <= 20M: Prefix `LITE`, target router `RO_POS1`.
     - Jika > 20M: Prefix `GAMER`, target router `RO_GAYUH`.
  3. Untuk Habudin (sales `09`):
     - Membaca kode/nama ODP.
     - Jika ODP diawali/mengandung `GSA` atau `GSB`: Prefix `SEWAN`.
     - Jika ODP diawali/mengandung `GSMA`: Prefix `SKM`.
     - Target router selalu `RO_POS1`.
  4. Untuk sales lainnya:
     - Mengambil prefix dari `kode_sales_ip`.
     - Mengambil router dari relasi `ipRouter` (yang dipetakan ke `RO_POS1` atau `RO_FK`).
- Di dalam `provisionStaticIp()`:
  - Mengambil slot IP kosong dari `$targetRouter->id`.
  - Format antrean Simple Queue: `{$queue_prefix}-{$octet34}-{$cleanName}`.
  - Memasang entri ARP static pada LAN Interface `$targetRouter->lan_interface`.
- Di dalam `provisionPppoe()`:
  - Target router selalu `RO_ZTE` (`is_pppoe_server = true`).
  - Format username PPPoE: `{$kode_odp}_01_{$kode_sales_pppoe}_{$seq}_{$cleanName}` (di mana `$kode_sales_pppoe` bernilai `01` s.d. `17`).

### 3.2. Database Seeder & Data Master
- Memperbarui `database/seeders/DatabaseSeeder.php`:
  - 4 Router: `RO_ZTE`, `RO_POS1`, `RO_FK`, `RO_GAYUH`.
  - Slot IP awal yang valid untuk ketiga router distribusi.
  - 17 entri Sales dengan relasi router default dan kode PPPoE `01`–`17`.
  - Contoh ODP dengan prefix `GSA-01`, `GSB-01`, `GSMA-01` untuk pengujian otomatis aturan Habudin.

### 3.3. Antarmuka Admin Master Router (`resources/views/admin/routers/index.blade.php`)
- Menampilkan pengelompokan kode sales per Router ID:
  - Di tabel router, menampilkan badge sales code & prefix queue yang dilayani oleh router tersebut.
  - Memberikan indikator jelas peran router (`RO_ZTE`: PPPoE Server, `RO_POS1`: Dist-1, `RO_FK`: Dist-2, `RO_GAYUH`: Dist-Gamer).

### 3.4. Antarmuka Admin Master Sales (`resources/views/admin/sales/index.blade.php`)
- Menampilkan kolom Kode PPPoE (2 digit: 01–17), Kode Simple Queue, dan Router Distribusi terkait (termasuk tag Dinamis untuk GAYUH dan HABUDIN).

### 3.5. Form PSB Teknisi (`resources/views/psb/create.blade.php`)
- Dropdown kode sales memuat seluruh 17 pilihan (dengan Gayuh sebagai Tanpa Sales / Kantor).
- Live preview JavaScript di drawer bawah secara otomatis menyimulasikan prefix `LITE` vs `GAMER` saat paket diubah, dan `SEWAN` vs `SKM` saat ODP diubah.

---

## 4. Rencana Pengujian (Test-Driven Development)
1. **Test Gayuh (01)**:
   - Paket 10M / 15M / 20M -> prefix `LITE`, klaim slot di `RO_POS1`.
   - Paket 30M / 50M -> prefix `GAMER`, klaim slot di `RO_GAYUH`.
2. **Test Habudin (09)**:
   - ODP GSA-01 -> prefix `SEWAN`, klaim slot di `RO_POS1`.
   - ODP GSMA-01 -> prefix `SKM`, klaim slot di `RO_POS1`.
3. **Test Sales Statis**:
   - Miftah (02) -> prefix `JB` di `RO_FK`.
   - Pay (08) -> prefix `PN` di `RO_FK`.
   - PGB (14) -> prefix `PGB` di `RO_FK`.
   - Mamat S (04) -> prefix `PS` di `RO_POS1`.
4. **Test PPPoE di RO_ZTE**:
   - Seluruh sales pada ODP upgrade di-provision ke `RO_ZTE` dengan format `ODP_01_XX_SEQ_NAME`.
5. **Verifikasi UI & Pint**:
   - Seluruh halaman Ocean Blue render normal tanpa regression.
   - Format kode lolos `php vendor/bin/pint --dirty`.
