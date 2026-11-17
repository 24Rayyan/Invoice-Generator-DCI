@extends('layouts.app')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Ringkasan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pemeliharaan Sistem</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold mb-1">Pemeliharaan Sistem</h1>
        <p class="text-muted mb-0">Kelola backup database lokal, pemulihan, dan arsip log aktivitas.</p>
    </div>
</div>

@foreach(['success' => 'success', 'error' => 'danger'] as $message => $variant)
    @if(session($message))
        <div class="alert alert-{{ $variant }} alert-dismissible fade show" role="alert">
            {{ session($message) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
@endforeach
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Pengaturan belum disimpan.</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="card border-0 shadow-sm mb-4" aria-labelledby="system-overview-title">
    <div class="card-header bg-white py-3"><h2 class="h5 mb-0" id="system-overview-title">Ringkasan Sistem</h2></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-lg-3"><span class="text-muted small d-block">Database</span><strong>{{ $databaseStatus }}</strong></div>
            <div class="col-6 col-lg-3"><span class="text-muted small d-block">Ukuran database</span><strong>{{ number_format($databaseBytes / 1048576, 2, ',', '.') }} MB</strong></div>
            <div class="col-6 col-lg-3"><span class="text-muted small d-block">Integritas SQLite</span><strong>{{ $databaseIntegrity }}</strong></div>
            <div class="col-6 col-lg-3"><span class="text-muted small d-block">Journal mode</span><strong>{{ $journalMode }}</strong></div>
            <div class="col-6 col-lg-3"><span class="text-muted small d-block">Log aktif</span><strong>{{ number_format($activeLogCount, 0, ',', '.') }}</strong></div>
            <div class="col-6 col-lg-3"><span class="text-muted small d-block">Log arsip</span><strong>{{ number_format($archiveCount, 0, ',', '.') }}</strong></div>
            <div class="col-6 col-lg-3"><span class="text-muted small d-block">Berkas backup</span><strong>{{ count($backupFiles) }}</strong></div>
            <div class="col-6 col-lg-3"><span class="text-muted small d-block">Berkas arsip</span><strong>{{ count($archivePeriods) }}</strong></div>
            <div class="col-12"><span class="text-muted small d-block">Penyimpanan backup + arsip</span><strong>{{ number_format(($backupBytes + $archiveBytes) / 1048576, 2, ',', '.') }} MB</strong></div>
        </div>
    </div>
</section>

<section class="card border-0 shadow-sm mb-4" aria-labelledby="backup-settings-title">
    <div class="card-header bg-white py-3"><h2 class="h5 mb-0" id="backup-settings-title">Backup Database Penuh</h2></div>
    <div class="card-body">
        <p class="text-muted">Snapshot mencakup seluruh tabel dan data dalam database SQLite aplikasi. Semua berkas disimpan lokal di <code>storage/app/system/backups</code>, di luar folder publik.</p>
        <div class="row g-3 mb-4">
            <div class="col-md-4"><span class="text-muted small d-block">Backup otomatis</span><strong>{{ $settings->automatic_backup_enabled ? 'Enabled' : 'Disabled' }}</strong></div>
            <div class="col-md-4"><span class="text-muted small d-block">Scheduler</span><strong>{{ $schedulerStatus }}</strong></div>
            <div class="col-md-4"><span class="text-muted small d-block">Backup berikutnya</span><strong>{{ $nextBackupAt?->format('d/m/Y H:i') ?? 'Tidak dijadwalkan' }}</strong></div>
            <div class="col-md-4"><span class="text-muted small d-block">Frekuensi dan waktu</span><strong>{{ ucfirst($settings->backup_frequency) }}, {{ $settings->backup_time }}</strong></div>
            @if($settings->backup_frequency === 'weekly')
                <div class="col-md-4"><span class="text-muted small d-block">Hari backup</span><strong>{{ ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$settings->backup_weekly_day] }}</strong></div>
            @elseif($settings->backup_frequency === 'monthly')
                <div class="col-md-4"><span class="text-muted small d-block">Tanggal backup</span><strong>Tanggal {{ $settings->backup_monthly_day }} (atau hari terakhir bulan)</strong></div>
            @endif
            <div class="col-md-4"><span class="text-muted small d-block">Retensi backup</span><strong>{{ $settings->backup_retention }} berkas</strong></div>
            <div class="col-md-4"><span class="text-muted small d-block">Backup sukses terakhir</span><strong>{{ $settings->last_backup_success_at?->format('d/m/Y H:i:s') ?? 'Never Executed' }}</strong></div>
            <div class="col-md-4"><span class="text-muted small d-block">Status terakhir</span><strong>{{ $settings->last_backup_status ?? 'Never Executed' }}</strong></div>
            <div class="col-md-4"><span class="text-muted small d-block">Scheduler terakhir terdeteksi</span><strong>{{ $settings->last_scheduler_seen_at?->format('d/m/Y H:i:s') ?? 'Not Verified' }}</strong></div>
        </div>

        <form action="{{ route('system-maintenance.backup') }}" method="POST" class="mb-4" data-confirm="Buat snapshot penuh dari database aktif sekarang?" data-confirm-title="Buat Backup Database?" data-confirm-button="Ya, Buat Backup">
            @csrf
            <button type="submit" class="btn btn-primary">Buat Backup Sekarang</button>
        </form>

        <form action="{{ route('system-maintenance.settings') }}" method="POST" data-confirm="Perbarui jadwal backup otomatis dan retensi log sistem?" data-confirm-title="Simpan Pengaturan Pemeliharaan?" data-confirm-button="Simpan">
            @csrf
            <h3 class="h6 fw-bold">Pengaturan Penjadwalan</h3>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="backup_frequency">Frekuensi</label>
                    <select class="form-select" name="backup_frequency" id="backup_frequency">
                        @foreach(['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('backup_frequency', $settings->backup_frequency) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="backup_time">Waktu backup</label>
                    <input class="form-control" type="time" name="backup_time" id="backup_time" value="{{ old('backup_time', $settings->backup_time) }}" required>
                </div>
                <div class="col-md-4" id="weekly-schedule">
                    <label class="form-label" for="backup_weekly_day">Hari dalam minggu</label>
                    <select class="form-select" name="backup_weekly_day" id="backup_weekly_day">
                        @foreach(['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $day => $label)
                            <option value="{{ $day }}" @selected((int) old('backup_weekly_day', $settings->backup_weekly_day) === $day)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4" id="monthly-schedule">
                    <label class="form-label" for="backup_monthly_day">Tanggal dalam bulan</label>
                    <input class="form-control" type="number" name="backup_monthly_day" id="backup_monthly_day" min="1" max="31" value="{{ old('backup_monthly_day', $settings->backup_monthly_day) }}">
                    <small class="form-text text-muted">Jika tanggal tidak tersedia, backup berjalan pada hari terakhir bulan.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="backup_retention">Jumlah backup yang disimpan</label>
                    <input class="form-control" type="number" name="backup_retention" id="backup_retention" min="1" max="365" value="{{ old('backup_retention', $settings->backup_retention) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="active_log_retention_days">Retensi log aktif (hari)</label>
                    <input class="form-control" type="number" name="active_log_retention_days" id="active_log_retention_days" min="30" max="3650" value="{{ old('active_log_retention_days', $settings->active_log_retention_days) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="archive_retention_days">Retensi berkas arsip (hari)</label>
                    <input class="form-control" type="number" name="archive_retention_days" id="archive_retention_days" min="30" max="36500" value="{{ old('archive_retention_days', $settings->archive_retention_days) }}" placeholder="Kosong = simpan selamanya">
                </div>
                <div class="col-12 d-flex flex-column flex-sm-row gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="automatic_backup_enabled" value="1" id="automatic_backup_enabled" @checked(old('automatic_backup_enabled', $settings->automatic_backup_enabled))>
                        <label class="form-check-label" for="automatic_backup_enabled">Aktifkan backup otomatis</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="automatic_archive_enabled" value="1" id="automatic_archive_enabled" @checked(old('automatic_archive_enabled', $settings->automatic_archive_enabled))>
                        <label class="form-check-label" for="automatic_archive_enabled">Arsipkan log otomatis tiap tanggal 1</label>
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-outline-primary" type="submit">Simpan Pengaturan</button>
                </div>
            </div>
        </form>
        @if($settings->last_backup_message)
            <p class="small text-muted mt-3 mb-0">{{ $settings->last_backup_message }}</p>
        @endif
    </div>
</section>

<section class="card border-0 shadow-sm mb-4" aria-labelledby="backup-history-title">
    <div class="card-header bg-white py-3"><h2 class="h5 mb-0" id="backup-history-title">Riwayat Backup dan Pemulihan</h2></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 m-cards">
            <thead><tr><th>Berkas</th><th>Dibuat</th><th>Ukuran</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($backupFiles as $backup)
                    <tr>
                        <td class="mc-title" data-label="Berkas">{{ $backup['name'] }} @if($backup['safety'])<span class="badge text-bg-warning">Backup keselamatan</span>@endif</td>
                        <td class="mc-half" data-label="Dibuat">{{ $backup['created_at']->format('d/m/Y H:i:s') }}</td>
                        <td class="mc-half" data-label="Ukuran">{{ number_format($backup['size'] / 1048576, 2, ',', '.') }} MB</td>
                        <td class="text-end mc-foot mc-actions" data-label="Aksi">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('system-maintenance.backups.download', $backup['name']) }}">Unduh</a>
                            <form action="{{ route('system-maintenance.backups.restore', $backup['name']) }}" method="POST" class="d-inline" data-confirm="Restore akan mengganti database aktif. Database sekarang disalin terlebih dahulu, lalu aplikasi dihentikan sementara untuk pergantian. Lanjutkan?" data-confirm-title="Pulihkan Database?" data-confirm-button="Ya, Pulihkan" data-confirm-variant="danger">
                                @csrf
                                <input type="hidden" name="confirmation" value="1">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Pulihkan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr class="mc-empty"><td colspan="4" class="text-center text-muted py-4">Belum ada backup database.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="card border-0 shadow-sm mb-4" aria-labelledby="archive-title">
    <div class="card-header bg-white py-3"><h2 class="h5 mb-0" id="archive-title">Arsip Log Aktivitas</h2></div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4"><span class="text-muted small d-block">Arsip otomatis</span><strong>{{ $settings->automatic_archive_enabled ? 'Enabled, bulanan tanggal 1' : 'Disabled' }}</strong></div>
            <div class="col-md-4"><span class="text-muted small d-block">Retensi log aktif</span><strong>{{ $settings->active_log_retention_days }} hari</strong></div>
            <div class="col-md-4"><span class="text-muted small d-block">Arsip berikutnya</span><strong>{{ $nextArchiveAt?->format('d/m/Y') ?? 'Tidak dijadwalkan' }}</strong></div>
            <div class="col-md-4"><span class="text-muted small d-block">Arsip sukses terakhir</span><strong>{{ $settings->last_archive_success_at?->format('d/m/Y H:i:s') ?? 'Never Executed' }}</strong></div>
            <div class="col-md-4"><span class="text-muted small d-block">Retensi berkas arsip</span><strong>{{ $settings->archive_retention_days ? $settings->archive_retention_days.' hari' : 'Indefinite' }}</strong></div>
            <div class="col-md-4"><span class="text-muted small d-block">Penyimpanan lokal</span><strong>storage/app/system/archives</strong></div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            @foreach($archivePeriods as $period)
                <a class="btn btn-sm btn-outline-primary" href="{{ route('activity_logs.index', ['source' => 'archive', 'period' => $period]) }}">{{ $period }}</a>
            @endforeach
            @if($archivePeriods === [])
                <span class="text-muted small">Belum ada periode yang diarsipkan.</span>
            @endif
        </div>
        <form action="{{ route('system-maintenance.archive') }}" method="POST" data-confirm="Log yang lebih lama dari masa retensi akan dipindahkan ke SQLite arsip. Setelah salinan diverifikasi, log aktif dihapus. Lanjutkan?" data-confirm-title="Arsipkan Log Lama?" data-confirm-button="Ya, Arsipkan" data-confirm-variant="warning">
            @csrf
            <input type="hidden" name="confirmation" value="1">
            <button class="btn btn-outline-warning" type="submit">Arsipkan Sekarang</button>
        </form>
        @if($settings->last_archive_message)
            <p class="small text-muted mt-3 mb-0">{{ $settings->last_archive_message }}</p>
        @endif
    </div>
</section>

<section class="card border-0 shadow-sm mb-4" aria-labelledby="scheduler-title">
    <div class="card-header bg-white py-3"><h2 class="h5 mb-0" id="scheduler-title">Jalankan Scheduler di Windows</h2></div>
    <div class="card-body">
        <p class="mb-2">Status scheduler: <strong>{{ $schedulerStatus }}</strong>. Task Scheduler Windows memicu Laravel setiap menit; Laravel membaca jadwal terbaru dari database dan hanya menjalankan backup saat waktunya cocok.</p>
        <ol class="mb-3">
            <li>Buka Windows Task Scheduler dan buat task baru dengan trigger harian, ulangi setiap 1 menit selama 1 hari.</li>
            <li>Action: Program/script <code>{{ $phpExecutable }}</code>.</li>
            <li>Arguments: <code>artisan schedule:run</code>.</li>
            <li>Start in: <code>{{ $projectDirectory }}</code>.</li>
            <li>Aktifkan task, kemudian tunggu heartbeat scheduler terlihat sebagai <strong>Verified</strong>. Hingga saat itu status tetap <strong>Not Verified</strong>.</li>
        </ol>
    </div>
</section>

<section class="card border-0 shadow-sm" aria-labelledby="operations-title">
    <div class="card-header bg-white py-3"><h2 class="h5 mb-0" id="operations-title">Status Operasi Terakhir</h2></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 m-cards">
            <thead><tr><th>Waktu</th><th>Operasi</th><th>Status</th><th>Berkas / pesan</th></tr></thead>
            <tbody>
                @forelse($operations as $operation)
                    <tr><td class="mc-title" data-label="Waktu">{{ $operation->created_at->format('d/m/Y H:i:s') }}</td><td class="mc-half" data-label="Operasi">{{ $operation->operation }}</td><td class="mc-half" data-label="Status">{{ $operation->status }}</td><td data-label="Berkas / pesan">{{ $operation->file_name ?: $operation->message }}</td></tr>
                @empty
                    <tr class="mc-empty"><td colspan="4" class="text-center text-muted py-4">Belum ada operasi pemeliharaan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const frequency = document.getElementById('backup_frequency');
        const weekly = document.getElementById('weekly-schedule');
        const monthly = document.getElementById('monthly-schedule');
        const weeklyDay = document.getElementById('backup_weekly_day');
        const monthlyDay = document.getElementById('backup_monthly_day');
        function updateScheduleFields() {
            weekly.classList.toggle('d-none', frequency.value !== 'weekly');
            monthly.classList.toggle('d-none', frequency.value !== 'monthly');
            weeklyDay.disabled = frequency.value !== 'weekly';
            monthlyDay.disabled = frequency.value !== 'monthly';
        }
        frequency.addEventListener('change', updateScheduleFields);
        updateScheduleFields();
    });
</script>
@endpush
