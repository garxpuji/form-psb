# Instalasi Form PSB di Debian 13 dan Ubuntu 26.04 LTS

Panduan ini memakai VM baru, Nginx, PHP-FPM, dan MariaDB di VM yang sama. Jalankan perintah dari Bash sebagai pengguna SSH dengan akses `sudo`. Contoh memakai `/var/www/form-psb` dan `psb.example.com`. Ganti domain contoh di `.env`, konfigurasi Nginx, dan perintah HTTPS.

## 1. Siapkan VM dan paket

Gunakan VM `amd64` atau `arm64` yang dapat menjangkau GitHub, repositori paket, dan router yang dikelola. Untuk domain publik, arahkan DNS ke VM dan izinkan TCP 80 serta 443 masuk. Siapkan akses HTTPS ke `api.telegram.org` jika memakai laporan Telegram. MariaDB diakses dari dalam VM melalui `127.0.0.1`.

Periksa rilis dengan `cat /etc/os-release`. Pilih blok yang sesuai, lalu lanjutkan di sesi Bash yang sama.

**Debian 13 (Trixie)**

```bash
PHP_VERSION=8.4
sudo apt update
```

**Ubuntu 26.04 LTS (Resolute)**

```bash
PHP_VERSION=8.5
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository -y universe
sudo apt update
```

Pasang paket. Ekstensi `gd` dipakai aplikasi untuk memproses foto PSB. [Paket PHP-FPM Debian](https://packages.debian.org/trixie/php8.4-fpm) · [Paket PHP Ubuntu](https://packages.ubuntu.com/resolute/php/) · [Persyaratan Laravel](https://laravel.com/docs/13.x/deployment)

```bash
sudo apt install -y git curl ca-certificates openssl unzip xz-utils acl nano \
  nginx mariadb-server composer cron \
  "php${PHP_VERSION}-cli" "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-mysql" \
  "php${PHP_VERSION}-curl" "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml" \
  "php${PHP_VERSION}-zip" "php${PHP_VERSION}-gd" "php${PHP_VERSION}-intl"

sudo systemctl enable --now nginx mariadb "php${PHP_VERSION}-fpm"
php -v
composer --version
```

## 2. Pasang Node.js untuk Vite

Gunakan Node.js 24 LTS. Blok ini memakai versi `v24.22.3` dan memeriksa arsip dengan checksum dari Node.js. Jika ada patch 24 LTS yang lebih baru, sesuaikan `NODE_VERSION`. [Rilis Node.js](https://nodejs.org/en/about/previous-releases) · [Arsip v24.22.3](https://nodejs.org/en/download/archive/v24.22.3)

```bash
(
  set -eu
  NODE_VERSION=v24.22.3
  case "$(uname -m)" in
    x86_64) NODE_ARCH=x64 ;;
    aarch64) NODE_ARCH=arm64 ;;
    *) printf 'Gunakan VM amd64 atau arm64.\n' >&2; exit 1 ;;
  esac

  node_dir="node-${NODE_VERSION}-linux-${NODE_ARCH}"
  archive="${node_dir}.tar.xz"
  work_dir=$(mktemp -d)
  trap 'rm -rf -- "$work_dir"' EXIT
  cd "$work_dir"
  curl -fsSLO "https://nodejs.org/dist/${NODE_VERSION}/${archive}"
  curl -fsSLO "https://nodejs.org/dist/${NODE_VERSION}/SHASUMS256.txt"
  awk -v file="$archive" '$2 == file' SHASUMS256.txt | sha256sum -c -
  mkdir -p "$HOME/.local/share/form-psb-node"
  tar -xJf "$archive" -C "$HOME/.local/share/form-psb-node"
  ln -sfn "$HOME/.local/share/form-psb-node/$node_dir" "$HOME/.local/share/form-psb-node/current"
)

export PATH="$HOME/.local/share/form-psb-node/current/bin:$PATH"
node --version
npm --version
```

Jalankan kembali baris `export PATH` jika build dimulai dari sesi SSH baru.

## 3. Buat database dan konfigurasi aplikasi

Buat kata sandi database dengan `openssl rand -hex 24`. Buka konsol dengan `sudo env MYSQL_HISTFILE=/dev/null mariadb`. Ganti `GANTI_PASSWORD_DB` dengan kata sandi tersebut sebelum menjalankan SQL ini.

```sql
CREATE DATABASE form_psb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'form_psb'@'127.0.0.1' IDENTIFIED BY 'GANTI_PASSWORD_DB';
GRANT ALL PRIVILEGES ON form_psb.* TO 'form_psb'@'127.0.0.1';
EXIT;
```

Uji akses dengan `mariadb -h 127.0.0.1 -u form_psb -p form_psb -e 'SELECT 1;'`. Kata sandi diminta lewat prompt.

Siapkan akses GitHub untuk akun deploy jika repositori bersifat privat.

```bash
sudo install -d -o "$(id -un)" -g www-data -m 0755 /var/www/form-psb
git clone https://github.com/garxpuji/form-psb.git /var/www/form-psb
cd /var/www/form-psb
install -m 600 .env.example .env
nano .env
```

Sesuaikan nilai di `.env` dan tambahkan `SESSION_SECURE_COOKIE`. Isi kata sandi database dan kata sandi PPPoE yang Anda tentukan. Masukkan token bot resmi jika memakai Telegram. Nilai webhook dapat diisi saat mendaftarkan `/cekid`.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://psb.example.com
LOG_LEVEL=warning

DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=form_psb
DB_USERNAME=form_psb
DB_PASSWORD=GANTI_PASSWORD_DB

SESSION_SECURE_COOKIE=true
FILESYSTEM_DISK=local

TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_SECRET=
PPPOE_DEFAULT_PASSWORD=GANTI_PASSWORD_PPPOE
```

## 4. Build aplikasi dan buat admin

Jalankan Composer dan npm sebagai pemilik checkout. PHP-FPM membutuhkan akses baca ke `.env` dan akses tulis ke direktori runtime Laravel. ACL bawaan menjaga akses kedua akun saat file baru dibuat.

```bash
cd /var/www/form-psb
sudo chgrp www-data .env
chmod 640 .env
sudo chmod -R o-rwx storage bootstrap/cache
sudo setfacl -R -m "u:$(id -un):rwX,u:www-data:rwX" storage bootstrap/cache
sudo find storage bootstrap/cache -type d \
  -exec setfacl -m "d:u:$(id -un):rwx,d:u:www-data:rwx" {} +

composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
php artisan key:generate --force
npm ci --include=dev
npm run build
test -s public/build/manifest.json
sudo -u www-data php artisan migrate --force
```

Jalankan `key:generate` sekali pada instalasi baru. Cadangkan `.env` karena kredensial router terenkripsi bergantung pada `APP_KEY`. Migrasi membuat tabel kosong, siap diisi dengan akun dan konfigurasi asli. Seeder proyek berisi akun dan alamat jaringan contoh untuk pengembangan. [Seeder proyek](../database/seeders/DatabaseSeeder.php)

Blok ini meminta username dan kata sandi melalui prompt. Model `User` akan menyimpan hash kata sandi.

```bash
read -r -p 'Username admin: ' admin_username
read -r -s -p 'Kata sandi admin: ' admin_password
printf '\n'

printf '%s\n%s\n' "$admin_username" "$admin_password" | php -r '
  require "vendor/autoload.php";
  $app = require "bootstrap/app.php";
  $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
  $username = strtolower(trim((string) fgets(STDIN)));
  $password = rtrim((string) fgets(STDIN), "\r\n");
  if ($username === "" || mb_strlen($username) > 100 || mb_strlen($password) < 12 || strlen($password) > 72) {
      fwrite(STDERR, "Gunakan username 1–100 karakter dan kata sandi minimal 12 karakter, maksimal 72 byte.\n");
      exit(1);
  }
  App\Models\User::create([
      "name" => "Admin NOC",
      "username" => $username,
      "password" => $password,
      "role" => "admin",
      "is_active" => true,
  ]);
  echo "Akun admin dibuat.\n";
'
unset admin_username admin_password
```

## 5. Konfigurasi Nginx dan HTTPS

Form PSB menerima lima foto dengan batas 10 MB per file. Atur batas ukuran permintaan gabungan ke 64 MB di PHP-FPM dan Nginx. [Validasi foto PSB](../app/Http/Requests/StorePsbOrderRequest.php)

```bash
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
sudo tee "/etc/php/${PHP_VERSION}/fpm/conf.d/99-form-psb.ini" >/dev/null <<'INI'
upload_max_filesize = 12M
post_max_size = 64M
INI
sudo systemctl restart "php${PHP_VERSION}-fpm"
ls -l /run/php/*.sock
sudo nano /etc/nginx/sites-available/form-psb
```

Isi konfigurasi berikut. Ganti `server_name` dan cocokkan `fastcgi_pass` dengan socket yang tampil. Ubuntu 26.04 memakai `/run/php/php8.5-fpm.sock`. Direktori `public/` menjadi document root. Aplikasi menyajikan dokumen PSB yang tersimpan di `storage/app/private` melalui rute yang memeriksa akses pengguna. [Konfigurasi penyimpanan](../config/filesystems.php)

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name psb.example.com;
    root /var/www/form-psb/public;
    index index.php;
    client_max_body_size 64m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root/index.php;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~ \.php(?:/|$) {
        return 404;
    }

    location ~ /\.(?!well-known/) {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/form-psb /etc/nginx/sites-enabled/form-psb
sudo nginx -t
sudo systemctl reload nginx
curl -fsS -H 'Host: psb.example.com' http://127.0.0.1/up
```

Untuk domain publik, gunakan Certbot setelah DNS dan port 80 dapat dijangkau. Untuk VM internal, gunakan sertifikat dari CA yang dipercaya perangkat teknisi. Mulai login dan unggah data pelanggan setelah HTTPS berfungsi. [Panduan Certbot](https://eff-certbot.readthedocs.io/en/stable/using.html)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx --redirect -d psb.example.com
curl -fsS https://psb.example.com/up
sudo certbot renew --dry-run
```

## 6. Isi data operasional dan uji integrasi

Masuk melalui `https://psb.example.com/login`, lalu isi data operasional berikut.

1. Di **Master Router**, masukkan alamat yang dapat dijangkau VM melalui jaringan privat atau VPN, beserta port dan kredensial API. Tandai router pusat PPPoE dan isi antarmuka LAN pada router distribusi yang memakai Static ARP. Jalankan **Tes Koneksi** per router. Form PSB memilih API-SSL saat port tersimpan `8729`. [Layanan RouterOS](https://help.mikrotik.com/docs/spaces/ROS/pages/103841820/Services) · [Pemilihan transport aplikasi](../app/Services/MikrotikProvisionerService.php)
2. Di **Master Sales**, **Master ODP**, dan **Master Paket**, isi pemetaan router distribusi, status upgrade ODP, profil PPPoE, dan limit Simple Queue sesuai jaringan Anda.
3. Di **Grup Telegram**, masukkan `chat_id` serta `thread_id` untuk grup yang memakai topik. Tandai satu grup utama **ALL TEAM PSB**. Beri bot akses kirim ke grup tujuan. Setelah mengisi `TELEGRAM_BOT_TOKEN`, jalankan `sudo -u www-data php artisan config:cache`, lalu klik **Tes Koneksi Bot** untuk memeriksa identitas bot.
4. Di **Manajemen User**, buat akun teknisi dan tetapkan grup Telegram. Di **Slot Monitor IP**, klik **Sync Router Ini** untuk satu router distribusi dan periksa waktu pembaruan serta daftar slot.

### Opsional untuk perintah Telegram `/cekid`

Jika bot juga menerima `/cekid`, buat nilai `TELEGRAM_WEBHOOK_SECRET` dengan `openssl rand -hex 32`, simpan di `.env`, dan jalankan `sudo -u www-data php artisan config:cache`. Daftarkan webhook setelah URL HTTPS dapat dijangkau Telegram. Ganti domain contoh dan masukkan token serta secret saat diminta.

```bash
read -r -s -p 'Token bot Telegram: ' bot_token
printf '\n'
read -r -s -p 'Secret webhook dari .env: ' webhook_secret
printf '\n'
curl --fail-with-body --silent --show-error --config - <<EOF
url = "https://api.telegram.org/bot${bot_token}/setWebhook"
data-urlencode = "url=https://psb.example.com/api/telegram/webhook"
data-urlencode = "secret_token=${webhook_secret}"
EOF
unset bot_token webhook_secret
```

Setelah Telegram mengembalikan `ok: true`, kirim `/cekid` di grup yang Anda kelola untuk memperoleh ID chat dan ID topik jika tersedia. [Telegram `setWebhook`](https://core.telegram.org/bots/api#setwebhook)

## 7. Aktifkan sinkronisasi terjadwal

Setelah tes manual berhasil, pasang satu entri cron. Laravel memeriksa jadwal setiap menit. Aplikasi menjadwalkan sinkronisasi slot tiap 15 menit. [Penjadwal Laravel](https://laravel.com/docs/13.x/scheduling) · [Jadwal proyek](../routes/console.php)

```bash
cd /var/www/form-psb
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan schedule:list

sudo tee /etc/cron.d/form-psb >/dev/null <<'CRON'
* * * * * www-data cd /var/www/form-psb && /usr/bin/php artisan schedule:run >/dev/null 2>&1
CRON
sudo chmod 644 /etc/cron.d/form-psb
sudo systemctl enable --now cron
```

## Pembaruan dan pemecahan masalah

Sebelum pembaruan, cadangkan MariaDB, `storage/app/`, dan `.env` ke penyimpanan yang aksesnya terbatas. Uji versi yang akan dipasang dan jadwalkan waktu pemeliharaan.

```bash
cd /var/www/form-psb
export PATH="$HOME/.local/share/form-psb-node/current/bin:$PATH"
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
sudo -u www-data php artisan down
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci --include=dev
npm run build
test -s public/build/manifest.json
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:clear
sudo systemctl reload "php${PHP_VERSION}-fpm"
sudo -u www-data php artisan up
curl -fsS https://psb.example.com/up
```

| Gejala | Pemeriksaan |
| --- | --- |
| `502 Bad Gateway` | Cocokkan `fastcgi_pass` dengan socket di `/run/php/`. Periksa status unit PHP-FPM dengan `systemctl`. |
| `Vite manifest not found` | Jalankan `npm ci --include=dev` dan `npm run build`. Pastikan `public/build/manifest.json` terbentuk di checkout yang dipakai Nginx. |
| HTTP `413` atau foto gagal diunggah | Periksa `client_max_body_size`, `post_max_size`, dan ukuran setiap file. Restart PHP-FPM setelah mengubah INI dan uji Nginx dengan `sudo nginx -t` sebelum reload. |
| Laravel gagal menulis log atau sesi | Periksa ACL `storage` dan `bootstrap/cache`. Cek koneksi database dan status tabel dengan `sudo -u www-data php artisan migrate:status`. |
| Router gagal sinkron | Periksa hasil **Tes Koneksi** serta port dan izin API. Jalankan sync per router, lalu lihat `storage/logs/laravel.log`. |
| Bot gagal terhubung | Periksa `TELEGRAM_BOT_TOKEN`, jalankan `sudo -u www-data php artisan config:cache`, lalu ulangi **Tes Koneksi Bot**. |
