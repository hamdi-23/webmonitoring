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
- Perubahan status (up ↔ down) dicatat di log aplikasi.
- Riwayat lama dihapus otomatis (default 30 hari, `MONITORING_RETENTION_DAYS`).

## Instalasi

```bash
composer run setup
```

Perintah di atas meng-install dependensi, membuat `.env`, generate key, migrasi database (SQLite secara default), dan build aset frontend.

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
| `php artisan model:prune` | Hapus riwayat pengecekan yang lebih tua dari masa retensi |

## Konfigurasi (`.env`)

| Variabel | Default | Keterangan |
|---|---|---|
| `MONITORING_RETENTION_DAYS` | `30` | Lama riwayat pengecekan disimpan |
| `MONITORING_USER_AGENT` | `WebMonitoring/1.0 (+uptime check)` | User-Agent yang dikirim saat mengecek |

## Testing

```bash
composer test
```

## Catatan

Belum ada autentikasi, jadi siapa pun yang bisa membuka aplikasi dapat mengubah monitor. Jangan expose ke internet publik tanpa menambahkan login (misalnya Laravel starter kit) atau proteksi di level web server.
