# Invoice-Generator-DCI

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Membuat Invoice dari PO

1. Buka menu PO, lalu pilih aksi **Buat Invoice dari PO** pada PO yang ingin ditagihkan.
2. Form Invoice menyalin pelanggan, nama barang, dan kuantitas dari PO. Pelanggan dan rincian tetap dapat diubah.
3. Isi harga satuan dan lengkapi data Invoice, lalu simpan.
4. Satu PO hanya dapat ditautkan ke satu Invoice. Setelah dibuat, aksi pada PO membuka Invoice tersebut.

## Data Demo Lokal

Untuk mengisi data demonstrasi customer, invoice, kategori akun pemasukan/pengeluaran, dan transaksi kas:

```bash
php artisan db:seed --class="Database\Seeders\DemoDataSeeder"
```

Seeder ini aman dijalankan ulang dan tidak menghapus data yang sudah ada. Data customer dan transaksi diberi penanda `DEMO`, menggunakan email domain `.test`, dan tidak memakai NPWP sungguhan. Data ini adalah simulasi realistis untuk skenario jasa IT/pengadaan perangkat, bukan catatan transaksi perusahaan sebenarnya. Akun yang dibuat mengikuti model kategori pemasukan/pengeluaran aplikasi saat ini, bukan COA akuntansi penuh.
# Pemeliharaan Sistem

## Mengaktifkan Fitur Pemeliharaan

Setelah pembaruan kode, terapkan migrasi secara aditif dengan:

```powershell
php artisan migrate --force
```

Migrasi menambahkan tabel pengaturan dan riwayat operasi, indeks Activity Log, serta izin
`system_maintenance`. Jangan gunakan `migrate:fresh` pada database aplikasi.

## Windows Task Scheduler untuk Backup Otomatis

Backup otomatis menggunakan konfigurasi di **Pemeliharaan Sistem**. Aktifkan backup otomatis,
pilih frekuensi, waktu, hari/tanggal (bila diperlukan), dan retensi. Scheduler Laravel hanya
membuat backup saat waktu yang tersimpan cocok; backup manual tetap tersedia saat jadwal mati.

Untuk menjalankannya di Windows, buat task di Task Scheduler yang berjalan setiap menit:

- **Program/script:** `C:\php\php.exe`
- **Add arguments:** `artisan schedule:run`
- **Start in:** `D:\Rayy\Kampus\Semester 7\Pemrograman Web Lanjut\Mini ERP`
- **Trigger:** Daily, repeat task every `1 minute`, for a duration of `1 day`
- Pilih **Run whether user is logged on or not** bila diperlukan dan gunakan akun Windows yang
  mempunyai akses tulis ke folder proyek dan `storage`.

Task Windows hanya memicu Laravel Scheduler. Status pada halaman tetap **Not Verified** sampai
heartbeat scheduler benar-benar tercatat. File backup lokal berada di
`storage\app\system\backups`; arsip log berada terpisah di `storage\app\system\archives`.
