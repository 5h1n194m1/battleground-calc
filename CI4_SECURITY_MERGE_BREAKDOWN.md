# Breakdown Merge Patch CI4 - Battleground Calc

Baseline ZIP: `battleground-calc.zip`  
Patch sumber: pasted markdown terbaru tentang security/session/CSRF hardening.

## Ringkasan keputusan

Patch yang digabung adalah lapisan security hardening. Logic bisnis existing seperti Team Manager, score workspace, roster, import tournament context, autosave pot, dan global CMS tidak diganti.

Yang paling penting: `pagecache` dihapus dari global/required filters karena aplikasi ini stateful, memakai login, session, CSRF, dan form POST. Cache halaman di level global berisiko menyajikan halaman/form lama dengan token CSRF basi.

## File diganti penuh

- `.env`
- `app/Config/App.php`
- `app/Config/Filters.php`
- `app/Config/Security.php`
- `app/Config/Session.php`
- `app/Config/ContentSecurityPolicy.php`
- `app/Views/layouts/main.php`
- `public/assets/js/app-shell.js`

## File baru

- `app/Filters/LocalOnlyFilter.php`
- `app/Filters/AppSecurityHeadersFilter.php`
- `app/Filters/AuthThrottleFilter.php`
- `app/Filters/GracefulCsrfFilter.php`
- `public/uploads/.htaccess`
- `public/uploads/index.html`

## File dipatch sebagian

- `app/Config/Auth.php`
  - `allowRegistration = false`
  - `allowMagicLinkLogins = false`
  - `minimumPasswordLength = 12`

- `app/Views/auth/layout.php`
  - Bootstrap CDN diberi SRI.
  - Inline script diberi `{csp-script-nonce}`.

- `app/Views/auth/login.php`
  - Query `expired=1` dan `throttled=1` ditangani sebagai pesan user-friendly.
  - Link register dan magic link tidak lagi ditampilkan karena fitur dimatikan.

- `app/Controllers/ImportController.php`
  - Import dibatasi `MAX_IMPORT_ROWS = 500`.
  - XLSX XML dibatasi `MAX_XML_BYTES = 4_000_000`.
  - Validasi upload ditambah `mime_in`, `ext_in`, dan `max_size` lebih ketat.
  - XML parser memakai `LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING`.
  - Panjang team/player/contact/notes dibatasi.

- `app/Controllers/PotController.php`
  - Upload directory dibuat lewat `ensureUploadDirectory()`.
  - `public/uploads/.htaccess` dan `index.html` dibuat otomatis jika belum ada.

- `app/Views/dashboard/index.php`
- `app/Views/pots/_manager.php`
- `app/Views/tournaments/_table.php`
- `app/Views/teams/_table.php`
- `app/Views/teams/roster_index.php`
  - Inline confirm diganti dari `onsubmit/onclick` ke `data-confirm` / `data-confirm-click`.

- `app/Views/imports/teams.php`
- `app/Views/teams/export_template.php`
- `app/Views/teams/roster_index.php`
  - Inline `<script>` diberi `{csp-script-nonce}`.

- `public/robots.txt`
  - Semua crawling diblokir untuk mode lokal/private.

- `konteks.txt`
  - Ditambahkan ringkasan patch tanggal 2026-04-24.

## Catatan deployment lokal

Setelah copy paste file patch, jalankan:

```bash
php spark cache:clear
composer dump-autoload
```

Lalu restart server lokal.

## Risiko yang perlu diketahui

`LocalOnlyFilter` hanya mengizinkan akses dari `localhost` / `127.0.0.1`. Jika nanti aplikasi dibuka dari HP/device lain di LAN, atau dihosting publik, hapus atau sesuaikan filter `localonly` dari `app/Config/Filters.php` bagian `$required['before']`.
