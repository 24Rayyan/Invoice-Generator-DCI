# Rekap Pengujian PRD Mini ERP

**Tanggal pengujian:** 16 Oktober 2026  
**Acuan:** `PRD dan Test Case - Sistem Mini ERP Berbasis Website.pdf`  
**Metode:** Menjalankan seluruh automated test Laravel yang tersedia dan mencocokkan cakupannya dengan 14 manual test case PRD. Skenario browser, pengukuran durasi, dan pengujian manual end-to-end tidak dijalankan.

## Ringkasan

| Hasil automated suite | Jumlah |
|---|---:|
| Passed | 29 |
| Failed | 10 |
| Total | 39 |

Perintah yang dijalankan: `php artisan test --no-progress`

### Kegagalan automated suite

| Test | Hasil aktual | Temuan |
|---|---|---|
| `CoretaxExportTest::test_export_coretax_validation_requires_document_ids` | Gagal: session tidak memiliki `errors` | Request diarahkan oleh middleware sebelum validasi. Test tidak melakukan login, sedangkan route ekspor dilindungi `auth` dan akses modul. |
| `CoretaxExportTest::test_export_coretax_successful_download` | Gagal: HTTP 302, bukan 2xx | Test tidak melakukan login; route ekspor Coretax dilindungi middleware. |
| `CoretaxExportTest::test_coretax_export_sheets_structure` | Gagal: mengharapkan `FAKTUR_KELUARAN`, menerima `Faktur` | Ekspektasi nama worksheet pada test tidak sesuai dengan judul sheet yang dikembalikan implementasi. Sheet kedua juga perlu diverifikasi terhadap skema/template Coretax yang berlaku. |
| `CoretaxXmlExportTest::test_export_coretax_xml_endpoint_download` | Gagal: HTTP 302, bukan 2xx | Test tidak melakukan login; endpoint dilindungi middleware. |
| `CoretaxXmlExportTest::test_export_xml_alias_endpoint` | Gagal: HTTP 302, bukan 2xx | Test tidak melakukan login; endpoint dilindungi middleware. |
| `CoretaxXmlExportTest::test_export_coretax_with_xml_type` | Gagal: HTTP 302, bukan 2xx | Test tidak melakukan login; endpoint dilindungi middleware. |
| `CoretaxXmlExportTest::test_export_xml_validation_fails_without_documents` | Gagal: session tidak memiliki `errors` | Request diarahkan oleh middleware sebelum validasi; test tidak melakukan login. |
| `ExampleTest::test_the_application_returns_a_successful_response` | Gagal: HTTP 302, bukan 200 | Test mengharapkan `/` mengembalikan 200, tetapi route aplikasi berada di dalam middleware `auth` dan mengarahkan pengguna yang belum login. |
| `FinancialErpTest::test_centralized_erp_settings_update` | Gagal: HTTP 302, bukan 200 | Test membuka halaman pengaturan dan mengirim perubahan tanpa login, padahal route dilindungi middleware. |
| `FinancialErpTest::test_erp_web_routes` | Gagal: HTTP 302, bukan 200 | Test mengakses halaman modul tanpa login; route memerlukan autentikasi dan akses modul. |

**Interpretasi:** sebagian besar kegagalan endpoint adalah masalah setup test terhadap proteksi akses yang memang diterapkan aplikasi, bukan bukti bahwa ekspor atau halaman tersebut gagal bagi pengguna yang terautentikasi. Test perlu membuat user ber-role yang sesuai dan melakukan `actingAs()` sebelum memverifikasi hasil fitur. Kegagalan nama worksheet adalah mismatch terukur antara ekspektasi test dan implementasi, dan masih perlu dicocokkan dengan skema impor Coretax; suite tidak membuktikan file ekspor tersebut sesuai atau tidak sesuai skema resmi.

## Pemetaan 14 Manual Test Case PRD

Status di bawah ini menggambarkan hasil automated coverage yang tersedia. `Belum diuji` berarti suite tidak menguji skenario tersebut secara memadai; status ini bukan klaim bahwa fitur pasti gagal.

| ID | Skenario | Status | Bukti / catatan |
|---|---|---|---|
| TC-AUTH-01 | Login dengan kredensial valid dan diarahkan sesuai role | **Belum diuji** | Tidak ditemukan test feature untuk alur login valid dan landing page per role. |
| TC-AUTH-02 | Login dengan kredensial salah menampilkan error | **Belum diuji** | Tidak ditemukan test feature untuk penolakan kredensial salah. |
| TC-AUTH-03 | Pembatasan akses menu berdasarkan role | **Sebagian diuji** | Test audit log membuktikan role `finance` ditolak dari halaman audit log (403), tetapi akses Staff Operasional ke menu laporan/manajemen user seperti di PRD belum dites. |
| TC-DOC-01 | Membuat transaksi dan otomatis menghasilkan Invoice, Kwitansi, Surat Jalan, PPN, serta nomor dokumen | **Belum diuji** | Test yang ada menguji sinkronisasi transaksi saat invoice dibayar, bukan keseluruhan alur pembuatan dokumen dan seluruh expected result ini. |
| TC-DOC-02 | Validasi field dokumen wajib yang kosong | **Belum diuji** | Tidak ditemukan test feature untuk submit form dokumen dengan field wajib kosong. |
| TC-DOC-03 | Menolak kuantitas/harga nol atau negatif | **Belum diuji** | Tidak ditemukan test feature untuk validasi nilai item dokumen nol/negatif. |
| TC-DOC-04 | PDF berhasil dibuat dalam waktu kurang dari 3 detik | **Belum diuji** | Tidak ditemukan test unduh PDF yang mengukur waktu respons; batas 3 detik memerlukan pengukuran runtime. |
| TC-TAX-01 | Ekspor template Coretax berhasil, sesuai skema, kurang dari 3 detik | **Gagal / belum terverifikasi end-to-end** | Endpoint Excel/XML gagal di automated suite karena test tidak login. Test struktur Excel gagal pada nama worksheet; kecocokan dengan skema resmi dan ambang waktu belum diverifikasi. |
| TC-TAX-02 | Peringatan ketika identitas pajak customer belum lengkap | **Belum diuji** | Test yang ada memeriksa data customer/XML pada kondisi data tersedia, bukan alur peringatan untuk identitas yang tidak lengkap. |
| TC-PETTY-01 | Mencatat kas kecil, menghubungkan invoice, dan mengunggah bukti JPG/PNG/PDF <= 5 MB | **Sebagian diuji** | Pembuatan transaksi dengan invoice dan validasi tautan invoice diuji. Unggah bukti yang valid dan batas ukuran/format belum diuji. |
| TC-PETTY-02 | Menolak bukti dengan format salah atau ukuran > 5 MB | **Belum diuji** | Tidak ditemukan test upload bukti dengan ekstensi tidak didukung atau ukuran di atas batas. |
| TC-ADM-01 | CRUD akun pengguna dan menonaktifkan akun | **Belum diuji** | Tidak ditemukan test feature untuk tambah, ubah, nonaktifkan, dan reset password akun pengguna. |
| TC-ADM-02 | Konfirmasi sebelum menghapus atau menonaktifkan akun | **Gagal pada inspeksi UI** | Form hapus memiliki konfirmasi JavaScript. Form nonaktifkan langsung submit tanpa konfirmasi, sehingga bagian nonaktifkan tidak memenuhi expected result. Belum ada test otomatis untuk perilaku ini. |
| TC-ADM-03 | Audit trail mencatat dan menampilkan pelaku serta waktu aktivitas | **Sebagian diuji** | Test memverifikasi pencatatan CRUD, detail log, akses super admin, dan retensi log setelah user dihapus. Skenario login/logout, pembuatan invoice, penghapusan transaksi kas, dan pemeriksaan perilaku real-time belum seluruhnya diuji. |

## Coverage tambahan yang lulus

Suite yang tersedia juga menguji dan meluluskan antara lain:

- Perhitungan margin invoice, biaya langsung, laba bersih, dan pembagian dengan revenue nol.
- Attach/detach biaya invoice, pembatasan agar transaksi terikat satu invoice, serta unlink transaksi ketika invoice dihapus.
- Pengecualian pengeluaran umum dari margin proyek dan sinkronisasi kas saat invoice dibayar.
- Render halaman detail invoice dan laporan margin proyek.
- Pencarian, filter, serta pagination manajemen arus kas; render laporan keuangan profesional dan tautan ekspornya.
- Beberapa perhitungan laporan fiskal, struktur/escaping XML Coretax, dan aktivitas audit trail.

Coverage tambahan ini tidak menggantikan skenario PRD yang tercatat sebagai belum diuji.

## Tindak lanjut yang disarankan

1. Perbaiki setup test route terlindungi dengan user dan role yang sesuai, lalu jalankan kembali endpoint ekspor, halaman settings, serta smoke test routes.
2. Pastikan nama worksheet Excel terhadap skema impor Coretax resmi; selaraskan test dan implementasi setelah format yang benar dikonfirmasi.
3. Tambahkan automated tests untuk autentikasi, validasi dokumen, unduhan PDF, ekspor dan peringatan Coretax, upload bukti kas, serta CRUD dan confirmation user management.
4. Tambahkan konfirmasi sebelum aksi menonaktifkan akun dan test UI/regresi untuk memastikan prompt tampil.
5. Jalankan manual/browser test untuk alur end-to-end serta ukur target performa PDF/Coretax (< 3 detik) pada lingkungan yang representatif.
