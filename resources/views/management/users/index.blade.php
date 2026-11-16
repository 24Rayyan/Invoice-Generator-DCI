@extends('layouts.app')

@section('content')
@php
    $activeCount = $users->filter(fn ($u) => $u->is_active ?? true)->count();
    $inactiveCount = $users->count() - $activeCount;
    $roleLabel = fn ($role) => $role ? ($role->display_name ?? $role->name) : 'Tanpa peran';
    // Modal tambah dibuka kembali bila pengiriman form tambah gagal validasi.
    $reopenCreate = $errors->any() && (old('email') !== null || old('password') !== null);
@endphp

<div class="um-page">

    {{-- Header --}}
    <header class="um-head">
        <div>
            <h1>Manajemen Pengguna</h1>
            <p>Kelola akun, peran, dan status akses pengguna sistem.</p>
        </div>
        <div class="um-head-actions">
            <a href="{{ route('management.roles.index') }}" class="btn btn-sm btn-outline-secondary">Kelola peran dan modul</a>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">Tambah pengguna</button>
        </div>
    </header>

    {{-- Umpan balik --}}
    @if(session('success'))
        <div class="alert alert-success d-flex justify-content-between align-items-start gap-3" role="status">
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex justify-content-between align-items-start gap-3" role="alert">
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Perubahan belum tersimpan:</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Daftar pengguna --}}
    <section class="um-panel" aria-labelledby="umList">
        <div class="um-panel-head">
            <div>
                <h2 id="umList">Daftar pengguna</h2>
                <span class="meta" id="umSummary">{{ $users->count() }} pengguna · {{ $activeCount }} aktif · {{ $inactiveCount }} nonaktif</span>
            </div>
            <div class="um-tools">
                <div>
                    <label for="umSearch" class="visually-hidden">Cari pengguna</label>
                    <input type="search" id="umSearch" class="form-control form-control-sm" placeholder="Cari nama pengguna atau email" autocomplete="off">
                </div>
                <div>
                    <label for="umRoleFilter" class="visually-hidden">Filter peran</label>
                    <select id="umRoleFilter" class="form-select form-select-sm">
                        <option value="">Semua peran</option>
                        @foreach($roles as $role)
                            <option value="{{ $roleLabel($role) }}">{{ $roleLabel($role) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="um-table" id="umTable">
                <thead>
                    <tr>
                        <th scope="col">Pengguna</th>
                        <th scope="col">Email</th>
                        <th scope="col">Peran</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="um-th-actions">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        @php
                            $isActive = $user->is_active ?? true;
                            $isSelf = auth()->id() === $user->id;
                            $uname = $user->username ?? $user->name;
                        @endphp
                        <tr class="{{ $isActive ? '' : 'is-inactive' }}"
                            data-search="{{ \Illuminate\Support\Str::lower($uname . ' ' . $user->email) }}"
                            data-role="{{ $roleLabel($user->role) }}">
                            <td data-label="Pengguna">
                                <span class="um-user">
                                    <span class="um-avatar" aria-hidden="true">{{ strtoupper(mb_substr($uname, 0, 2)) }}</span>
                                    <span class="um-user-name">{{ $uname }}</span>
                                    @if($isSelf)<span class="um-self">Anda</span>@endif
                                </span>
                            </td>
                            <td data-label="Email" class="um-email">{{ $user->email }}</td>
                            <td data-label="Peran">{{ $roleLabel($user->role) }}</td>
                            <td data-label="Status">
                                <span class="um-status {{ $isActive ? 'on' : 'off' }}">{{ $isActive ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td data-label="Tindakan" class="um-actions">
                                <button type="button" class="um-act js-edit-user"
                                        data-id="{{ $user->id }}" data-username="{{ $uname }}" data-role-id="{{ $user->role_id }}"
                                        title="Ubah" aria-label="Ubah {{ $uname }}"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>

                                <button type="button" class="um-act js-reset-user"
                                        data-id="{{ $user->id }}" data-username="{{ $uname }}"
                                        title="Atur ulang kata sandi" aria-label="Atur ulang kata sandi {{ $uname }}"><i class="fa-solid fa-key" aria-hidden="true"></i></button>

                                @unless($isSelf)
                                    <form action="{{ route('management.users.toggle-status', $user) }}" method="POST" class="d-inline"
                                          data-confirm="{{ $isActive ? 'Akun ini tidak akan bisa login sampai diaktifkan kembali.' : 'Akun ini akan bisa login kembali.' }}"
                                          data-confirm-title="{{ $isActive ? 'Nonaktifkan pengguna?' : 'Aktifkan pengguna?' }}"
                                          data-confirm-subject="{{ $user->username }}"
                                          data-confirm-button="{{ $isActive ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}"
                                          data-confirm-variant="warning">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="um-act warn" title="{{ $isActive ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $isActive ? 'Nonaktifkan' : 'Aktifkan' }} {{ $uname }}"><i class="fa-solid {{ $isActive ? 'fa-user-slash' : 'fa-user-check' }}" aria-hidden="true"></i></button>
                                    </form>

                                    <form action="{{ route('management.users.destroy', $user) }}" method="POST" class="d-inline"
                                          data-confirm="User akan dihapus permanen dan tidak dapat login lagi."
                                          data-confirm-impact="Riwayat aktivitas user tetap tersimpan di log aktivitas."
                                          data-confirm-title="Hapus User?" data-confirm-subject="{{ $user->username }}" data-confirm-button="Ya, Hapus">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="um-act danger" title="Hapus" aria-label="Hapus {{ $uname }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                    <tr class="um-empty-row" id="umEmptyAll" @if($users->count() > 0) hidden @endif>
                        <td colspan="5">
                            <div class="um-empty">
                                <strong>Belum ada pengguna terdaftar</strong>
                                Gunakan tombol Tambah pengguna untuk membuat akun pertama.
                            </div>
                        </td>
                    </tr>
                    <tr class="um-empty-row" id="umEmptyFilter" hidden>
                        <td colspan="5">
                            <div class="um-empty">
                                <strong>Tidak ada pengguna yang cocok</strong>
                                Ubah kata kunci atau pilihan peran.
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('modals')
    {{-- MODAL: TAMBAH PENGGUNA --}}
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('management.users.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="createUserModalLabel">Tambah pengguna</h5>
                            <p class="small text-muted mb-0">Akun baru langsung aktif dan bisa login.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="newUserName" class="form-label">Nama pengguna <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" id="newUserName" name="username" value="{{ old('username') }}" required maxlength="50" autocomplete="off" class="form-control @error('username') is-invalid @enderror">
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="newUserEmail" class="form-label">Email perusahaan <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="email" id="newUserEmail" name="email" value="{{ old('email') }}" required autocomplete="off" class="form-control @error('email') is-invalid @enderror">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="newUserPassword" class="form-label">Kata sandi <span class="text-danger" aria-hidden="true">*</span></label>
                            <div class="input-group has-validation">
                                <input type="password" id="newUserPassword" name="password" required minlength="6" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
                                <button type="button" class="btn btn-outline-secondary js-toggle-pass" data-target="newUserPassword" aria-label="Tampilkan kata sandi" aria-pressed="false">Lihat</button>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-text">Minimal 6 karakter.</div>
                        </div>
                        <div>
                            <label for="newUserRole" class="form-label">Peran <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="newUserRole" name="role_id" required class="form-select @error('role_id') is-invalid @enderror">
                                <option value="">Pilih peran</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ $role->display_name ?? ucfirst($role->name) }}</option>
                                @endforeach
                            </select>
                            @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Peran menentukan modul apa saja yang bisa dibuka.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary">Simpan pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: UBAH PENGGUNA --}}
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editUserForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="editUserModalLabel">Ubah pengguna</h5>
                            <p class="small text-muted mb-0">Ganti nama pengguna atau peran.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Nama pengguna <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="text" id="edit_username" name="username" required class="form-control" autocomplete="off">
                        </div>
                        <div>
                            <label for="edit_role_id" class="form-label">Peran <span class="text-danger" aria-hidden="true">*</span></label>
                            <select id="edit_role_id" name="role_id" required class="form-select">
                                <option value="" disabled>Pilih peran</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}">{{ $r->display_name ?? ucfirst($r->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary">Simpan perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: ATUR ULANG KATA SANDI --}}
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="resetPasswordForm" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="resetPasswordModalLabel">Atur ulang kata sandi</h5>
                            <p class="small text-muted mb-0">Untuk pengguna <strong id="reset_username_text"></strong>. Kata sandi lama langsung tidak berlaku.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="customResetPassword" class="form-label">Kata sandi baru <span class="text-danger" aria-hidden="true">*</span></label>
                            <div class="input-group">
                                <input type="password" id="customResetPassword" name="password" required minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter" class="form-control">
                                <button type="button" class="btn btn-outline-secondary js-toggle-pass" data-target="customResetPassword" aria-label="Tampilkan kata sandi" aria-pressed="false">Lihat</button>
                            </div>
                        </div>
                        <div>
                            <label for="resetPasswordConfirm" class="form-label">Ulangi kata sandi baru <span class="text-danger" aria-hidden="true">*</span></label>
                            <input type="password" id="resetPasswordConfirm" name="password_confirmation" required minlength="8" autocomplete="new-password" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary">Simpan kata sandi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('styles')
<style>
    /* Token --erp-* dari erp.css. Satu aksen biru; hijau hanya untuk status aktif, merah hanya untuk hapus. */
    .um-page { color: var(--erp-text); }

    .um-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: .9rem 1.5rem; margin-bottom: 1rem; }
    .um-head h1 { font-size: clamp(1.3rem, 1.1rem + .8vw, 1.65rem); font-weight: 700; letter-spacing: -.03em; margin: 0 0 .2rem; }
    .um-head p { margin: 0; font-size: .86rem; color: var(--erp-text-3); }
    .um-head-actions { display: flex; flex-wrap: wrap; gap: .5rem; }

    .um-panel { background: var(--erp-surface); border: 1px solid var(--erp-line); border-radius: var(--erp-radius); overflow: hidden; }
    .um-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem 1rem; padding: .85rem 1.1rem; border-bottom: 1px solid var(--erp-line); }
    .um-panel-head h2 { font-size: .95rem; font-weight: 700; margin: 0; }
    .um-panel-head .meta { font-size: .8rem; color: var(--erp-text-3); font-variant-numeric: tabular-nums; }
    .um-tools { display: flex; flex-wrap: wrap; gap: .5rem; }
    .um-tools input { min-width: 250px; }
    .um-tools select { min-width: 150px; }

    .um-table { width: 100%; border-collapse: collapse; font-size: .86rem; }
    .um-table thead th { background: var(--erp-surface-2); color: var(--erp-text-3); font-size: .72rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; padding: .65rem 1rem; text-align: left; white-space: nowrap; border-bottom: 1px solid var(--erp-line-strong); }
    .um-table tbody td { padding: .7rem 1rem; border-bottom: 1px solid var(--erp-line); vertical-align: middle; }
    .um-table tbody tr:last-child td { border-bottom: 0; }
    .um-table tbody tr:not(.um-empty-row):hover > td { background: #f6f8fc; }
    .um-table .um-th-actions { text-align: right; }
    .um-table tr.is-inactive .um-user-name, .um-table tr.is-inactive .um-email { color: var(--erp-text-3); }
    .um-table tr[hidden] { display: none; }

    .um-user { display: inline-flex; align-items: center; gap: .6rem; min-width: 0; }
    .um-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--erp-primary-soft); color: var(--erp-primary-hover); display: grid; place-items: center; font-size: .72rem; font-weight: 700; flex-shrink: 0; }
    .um-user-name { font-weight: 600; color: var(--erp-ink); overflow-wrap: anywhere; }
    .um-self { font-size: .7rem; font-weight: 600; color: var(--erp-text-2); background: #eef1f6; border-radius: 5px; padding: .05rem .4rem; }
    .um-email { color: var(--erp-text-2); overflow-wrap: anywhere; }

    .um-status { display: inline-flex; align-items: center; gap: .4rem; font-size: .8rem; font-weight: 600; }
    .um-status::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--erp-line-strong); }
    .um-status.on { color: var(--erp-success); }
    .um-status.on::before { background: var(--erp-success); }
    .um-status.off { color: var(--erp-text-3); }

    .um-actions { text-align: right; white-space: nowrap; }
    .um-actions > * { vertical-align: middle; }
    .um-actions form { margin: 0; }
    .um-act { appearance: none; background: transparent; border: 0; font-size: .85rem; color: var(--erp-text-2); width: 34px; height: 34px; display: inline-grid; place-items: center; border-radius: 6px; cursor: pointer; transition: background-color .15s ease, color .15s ease; }
    .um-act:hover { background: var(--erp-primary-soft); color: var(--erp-primary-hover); }
    .um-act.warn:hover { background: var(--erp-warning-soft); color: var(--erp-warning); }
    .um-act:active { background: #dfe8ff; }
    .um-act:focus-visible { outline: 2px solid var(--erp-primary); outline-offset: 1px; }
    .um-act.danger { color: var(--erp-text-2); }
    .um-act.danger:hover { background: var(--erp-danger-soft); color: var(--erp-danger); }

    .um-empty { padding: 2.25rem 1rem; text-align: center; color: var(--erp-text-3); font-size: .86rem; }
    .um-empty strong { display: block; color: var(--erp-ink); font-size: .92rem; margin-bottom: .2rem; }

    @media (max-width: 767.98px) {
        .um-head-actions { width: 100%; }
        .um-head-actions .btn { flex: 1 1 auto; }
        .um-tools { width: 100%; }
        .um-tools > div { flex: 1 1 100%; }
        .um-tools input, .um-tools select { min-width: 0; width: 100%; }

        /* Baris menjadi kartu bertumpuk, label kolom dipertahankan lewat data-label */
        .um-table thead { display: none; }
        .um-table, .um-table tbody { display: block; }
        .um-table tbody tr:not([hidden]) { display: block; padding: .75rem 1rem; border-bottom: 1px solid var(--erp-line); }
        .um-table tbody tr:last-child { border-bottom: 0; }
        .um-table tbody td { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .3rem 0; border: 0; text-align: right; }
        .um-table tbody td::before { content: attr(data-label); font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--erp-text-3); text-align: left; flex-shrink: 0; }
        .um-table tbody td.um-actions { flex-wrap: wrap; justify-content: flex-start; white-space: normal; padding-top: .55rem; margin-top: .3rem; border-top: 1px solid var(--erp-line); gap: .25rem; }
        .um-table tbody td.um-actions::before { display: none; }
        .um-act { width: 44px; height: 44px; font-size: .95rem; }
        .um-empty-row td { display: block; }
        .um-empty-row td::before { display: none; }
    }
</style>
@endpush

@push('scripts')
<script>
    function openEditModal(id, username, roleId) {
        const form = document.getElementById('editUserForm');
        form.action = "{{ url('management/users') }}/" + id;

        document.getElementById('edit_username').value = username;
        document.getElementById('edit_role_id').value = roleId;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editUserModal')).show();
    }

    function openResetModal(id, username) {
        const form = document.getElementById('resetPasswordForm');
        form.action = "{{ url('management/users') }}/" + id + "/reset-password";

        document.getElementById('reset_username_text').innerText = username;
        form.reset();

        bootstrap.Modal.getOrCreateInstance(document.getElementById('resetPasswordModal')).show();
    }

    (function () {
        // Tombol baris memakai atribut data, bukan string inline, sehingga nama pengguna dengan tanda kutip aman.
        document.querySelectorAll('.js-edit-user').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openEditModal(btn.dataset.id, btn.dataset.username, btn.dataset.roleId);
            });
        });
        document.querySelectorAll('.js-reset-user').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openResetModal(btn.dataset.id, btn.dataset.username);
            });
        });

        // Lihat / sembunyikan kata sandi
        document.querySelectorAll('.js-toggle-pass').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.dataset.target);
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.textContent = show ? 'Sembunyikan' : 'Lihat';
                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                btn.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            });
        });

        // Pencarian dan filter peran (sisi klien, atas data yang sudah dimuat)
        var search = document.getElementById('umSearch');
        var roleFilter = document.getElementById('umRoleFilter');
        var rows = Array.prototype.slice.call(document.querySelectorAll('#umTable tbody tr[data-search]'));
        var emptyFilter = document.getElementById('umEmptyFilter');
        var summary = document.getElementById('umSummary');
        var baseSummary = summary.textContent;

        function applyFilter() {
            var q = search.value.trim().toLowerCase();
            var role = roleFilter.value;
            var visible = 0;
            rows.forEach(function (row) {
                var match = (!q || row.dataset.search.indexOf(q) !== -1) && (!role || row.dataset.role === role);
                row.hidden = !match;
                if (match) visible++;
            });
            emptyFilter.hidden = !(rows.length > 0 && visible === 0);
            summary.textContent = (q || role) ? (visible + ' dari ' + rows.length + ' pengguna ditampilkan') : baseSummary;
        }
        search.addEventListener('input', applyFilter);
        roleFilter.addEventListener('change', applyFilter);

        @if($reopenCreate)
        bootstrap.Modal.getOrCreateInstance(document.getElementById('createUserModal')).show();
        @endif
    })();
</script>
@endpush
