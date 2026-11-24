# WebMonitoring

Aplikasi sederhana untuk memantau uptime dan waktu respons website, dibangun dengan **Laravel 13**, PHP 8.3+, Vite 8, dan Tailwind CSS 4.

## Fitur

- Tambah, edit, jeda, dan hapus monitor (URL yang dipantau).
- Pengecekan otomatis per monitor sesuai interval (1–1440 menit) lewat scheduler + queue.
- Aturan "up": status HTTP < 400, atau sama persis dengan **status yang diharapkan**; opsional **kata kunci** yang wajib ada di isi halaman.
- Mencatat status code, waktu respons (ms), dan pesan error tiap pengecekan.
- Dashboard: ringkasan total/up/down, rata-rata respons, uptime 24 jam per monitor.
- Halaman detail: uptime 24 jam / 7 hari / 30 hari, grafik waktu respons, riwayat pengecekan.
- Tombol **Cek sekarang** untuk pengecekan manual.
- **Notifikasi** saat website down dan saat pulih (beserta durasi down), lewat **email** ke semua pengguna dan/atau **Telegram**.
- **Login**: semua halaman butuh login, dengan pembatasan percobaan login (5x per menit). Tidak ada pendaftaran publik; akun dibuat lewat artisan.
- Perubahan status (up ↔ down) juga dicatat di log aplikasi.
- Riwayat lama dihapus otomatis (default 30 hari, `MONITORING_RETENTION_DAYS`).

## Instalasi

```bash
composer run setup
```

Perintah di atas meng-install dependensi, membuat `.env`, generate key, migrasi database (SQLite secara default), dan build aset frontend.

Lalu buat akun untuk login:

```bash
php artisan user:create
```

## Menjalankan

```bash
composer run dev
```

Pengecekan otomatis membutuhkan **scheduler** dan **queue worker** berjalan. Di server produksi:

```bash
# crontab
* * * * * cd /path/ke/project && php artisan schedule:run >> /dev/null 2>&1
```

```bash
php artisan queue:work
```

## Perintah artisan

| Perintah | Keterangan |
|---|---|
| `php artisan monitors:check` | Kirim job pengecekan untuk monitor yang sudah waktunya dicek |
| `php artisan monitors:check --all` | Cek semua monitor aktif, abaikan interval |
| `php artisan monitors:check --sync` | Jalankan langsung tanpa queue (cocok untuk debugging) |
| `php artisan user:create` | Buat akun pengguna (interaktif, atau pakai `--name --email --password`) |
| `php artisan monitors:test-alert` | Kirim notifikasi percobaan untuk mengetes email & Telegram |
| `php artisan model:prune` | Hapus riwayat pengecekan yang lebih tua dari masa retensi |

## Konfigurasi (`.env`)

| Variabel | Default | Keterangan |
|---|---|---|
| `MONITORING_RETENTION_DAYS` | `30` | Lama riwayat pengecekan disimpan |
| `MONITORING_USER_AGENT` | `WebMonitoring/1.0 (+uptime check)` | User-Agent yang dikirim saat mengecek |
| `MONITORING_NOTIFY_MAIL` | `true` | Kirim email ke semua pengguna saat status berubah |
| `TELEGRAM_BOT_TOKEN` | – | Token bot dari [@BotFather](https://t.me/BotFather) |
| `TELEGRAM_CHAT_ID` | – | ID chat/grup tujuan notifikasi |

Agar email benar-benar terkirim, isi juga pengaturan `MAIL_*` (SMTP, Mailgun, dll.). Default-nya `MAIL_MAILER=log`, sehingga email hanya ditulis ke `storage/logs/laravel.log`.

### Menyiapkan Telegram

1. Chat [@BotFather](https://t.me/BotFather), kirim `/newbot`, lalu salin token ke `TELEGRAM_BOT_TOKEN`.
2. Kirim pesan apa saja ke bot Anda (atau tambahkan bot ke grup).
3. Buka `https://api.telegram.org/bot<TOKEN>/getUpdates` dan salin nilai `chat.id` ke `TELEGRAM_CHAT_ID`.
4. Tes dengan `php artisan monitors:test-alert`.

Notifikasi dikirim lewat queue, jadi pastikan `queue:work` berjalan.

## Testing

```bash
composer test
```

## Catatan

Notifikasi dikirim begitu satu pengecekan gagal. Kalau website Anda sering timeout sesaat, naikkan nilai timeout pada monitor tersebut agar tidak terlalu banyak alarm palsu.
