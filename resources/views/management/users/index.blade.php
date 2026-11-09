@extends('layouts.app')

@section('content')
<div class="container-fluid p-0">

    <!-- Banner & Header -->
    <div class="card bg-dark text-white rounded-4 p-4 shadow-lg border-0 mb-4 position-relative overflow-hidden" style="background-color: #1E293B !important;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 position-relative z-1">
            <div>
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-secondary bg-opacity-25 border border-secondary border-opacity-50 rounded-pill text-xs font-semibold text-info mb-2" style="font-size: 0.75rem;">
                    <i class="fa-solid fa-shield-halved"></i> Kontrol Akses & Identitas Pengguna
                </div>
                <h1 class="h3 fw-bold tracking-tight text-white m-0">Manajemen Pengguna</h1>
                <p class="text-secondary small mt-1 mb-0" style="max-width: 600px;">
                    Kelola kredensial akun, tetapkan peran, dan atur hak akses modul.
                </p>
            </div>

            <a href="{{ route('management.roles.index') }}" 
               class="btn btn-primary rounded-pill px-4 py-2 text-xs font-semibold shadow-sm d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-sliders"></i> Kelola Peran & Modul
                <i class="fa-solid fa-arrow-right small ms-1"></i>
            </a>
        </div>
    </div>

    <!-- Notifications -->
    @if(session('success'))
        <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success rounded-4 p-3 mb-4 d-flex align-items-center justify-content-between shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-25 d-flex align-items-center justify-content-center text-success flex-shrink-0" style="width: 36px; height: 36px;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <span class="small font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-4 p-3 mb-4 d-flex align-items-center justify-content-between shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-danger bg-opacity-25 d-flex align-items-center justify-content-center text-danger flex-shrink-0" style="width: 36px; height: 36px;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <span class="small font-medium">{{ session('error') }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Form Create User -->
    <div class="card border-0 rounded-4 p-4 shadow-sm mb-4 bg-white">
        <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div>
                    <h2 class="h6 fw-bold text-dark m-0">Buat Pengguna Baru</h2>
                    <p class="small text-muted mb-0">Tambahkan anggota tim baru ke dalam ekosistem sistem.</p>
                </div>
            </div>
        </div>

        <form action="{{ route('management.users.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <label class="form-label text-uppercase fw-bold text-secondary small tracking-wider mb-1" style="font-size: 0.7rem;">
                        Nama Pengguna <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="username" value="{{ old('username') }}" required 
                        class="form-control form-control-sm rounded-3 py-2 px-3 @error('username') is-invalid @enderror">
                    @error('username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label text-uppercase fw-bold text-secondary small tracking-wider mb-1" style="font-size: 0.7rem;">
                        Email Perusahaan <span class="text-danger">*</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}" required 
                        class="form-control form-control-sm rounded-3 py-2 px-3 @error('email') is-invalid @enderror">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label text-uppercase fw-bold text-secondary small tracking-wider mb-1" style="font-size: 0.7rem;">
                        Password <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="password" id="newUserPassword" name="password" required 
                            class="form-control rounded-start-3 py-2 px-3 @error('password') is-invalid @enderror">
                        <button type="button" class="btn btn-outline-secondary rounded-end-3 px-3 border-start-0" onclick="togglePasswordVisibility('newUserPassword', 'iconTogglePassword')">
                            <i class="fa-solid fa-eye" id="iconTogglePassword"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="text-danger mt-1" style="font-size: 0.75rem;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label text-uppercase fw-bold text-secondary small tracking-wider mb-1" style="font-size: 0.7rem;">
                        Peran / Hak Akses <span class="text-danger">*</span>
                    </label>
                    <select name="role_id" required class="form-select form-select-sm rounded-3 py-2 px-3 @error('role_id') is-invalid @enderror">
                        <option value="">-- Pilih Peran --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->display_name ?? ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 text-sm fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-plus small"></i> Tambah Pengguna Baru
                </button>
            </div>
        </form>
    </div>

    <!-- Table Users -->
    <div class="card border-0 rounded-4 shadow-sm overflow-hidden bg-white mb-4">
        <div class="p-4 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-light d-flex align-items-center justify-content-center text-dark fw-bold" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <h3 class="h6 fw-bold text-dark m-0">Daftar Pengguna Aktif</h3>
                    <p class="small text-muted mb-0">Semua pengguna yang terdaftar dalam sistem.</p>
                </div>
            </div>
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill font-semibold">
                Total: {{ $users->count() }} pengguna
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="text-uppercase text-secondary fw-bold border-bottom" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                        <th class="py-3 px-4">Pengguna</th>
                        <th class="py-3 px-4">Email Perusahaan</th>
                        <th class="py-3 px-4">Akses Peran</th>
                        <th class="py-3 px-4">Status Akun</th>
                        <th class="py-3 px-4 text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="border-top-0 small">
                    @forelse($users as $user)
                        <tr>
                            <td class="py-3 px-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; font-size: 0.8rem;">
                                        {{ strtoupper(substr($user->username ?? $user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $user->username ?? $user->name }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Display: {{ $user->name }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3 px-4 text-secondary font-medium">
                                {{ $user->email }}
                            </td>

                            <td class="py-3 px-4">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1.5 rounded-pill fw-bold" style="font-size: 0.7rem;">
                                    <i class="fa-solid fa-shield me-1"></i>
                                    {{ $user->role ? ($user->role->display_name ?? $user->role->name) : 'Tanpa Peran' }}
                                </span>
                            </td>

                            <td class="py-3 px-4">
                                <span class="badge {{ ($user->is_active ?? true) ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' }} px-3 py-1.5 rounded-pill fw-bold d-inline-flex align-items-center gap-1" style="font-size: 0.7rem;">
                                    <span class="rounded-circle d-inline-block {{ ($user->is_active ?? true) ? 'bg-success' : 'bg-danger' }}" style="width: 6px; height: 6px;"></span>
                                    {{ ($user->is_active ?? true) ? 'ACTIVE' : 'INACTIVE' }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-center">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <!-- Tombol Trigger Modal Edit -->
                                    <button type="button" 
                                            onclick="openEditModal({{ $user->id }}, '{{ addslashes($user->username ?? $user->name) }}', '{{ $user->role_id ?? ($user->role ? $user->role->id : '') }}')" 
                                            class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1" 
                                            title="Edit pengguna / peran" style="font-size: 0.75rem;">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <form action="{{ route('management.users.toggle-status', $user) }}" method="POST" class="d-inline"
                                          data-confirm="{{ ($user->is_active ?? true) ? 'Akun ini tidak akan bisa login sampai diaktifkan kembali.' : 'Akun ini akan bisa login kembali.' }}"
                                          data-confirm-title="{{ ($user->is_active ?? true) ? 'Nonaktifkan pengguna?' : 'Aktifkan pengguna?' }}"
                                          data-confirm-subject="{{ $user->username }}"
                                          data-confirm-button="{{ ($user->is_active ?? true) ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}"
                                          data-confirm-variant="warning">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                                class="btn btn-sm {{ ($user->is_active ?? true) ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-pill px-2.5 py-1" 
                                                title="{{ ($user->is_active ?? true) ? 'Nonaktifkan User' : 'Aktifkan User' }}" style="font-size: 0.75rem;">
                                            <i class="fa-solid {{ ($user->is_active ?? true) ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                        </button>
                                    </form>

                                    <!-- Tombol Trigger Modal Reset Password -->
                                    <button type="button" 
                                            onclick="openResetModal({{ $user->id }}, '{{ addslashes($user->username ?? $user->name) }}')" 
                                            class="btn btn-sm btn-outline-info rounded-pill px-2.5 py-1 fw-semibold shadow-sm" 
                                            title="Atur Ulang Kata Sandi Khusus" style="font-size: 0.75rem;">
                                        <i class="fa-solid fa-key"></i>
                                    </button>

                                    <form action="{{ route('management.users.destroy', $user) }}" method="POST" class="d-inline" data-confirm="User akan dihapus permanen dan tidak dapat login lagi." data-confirm-impact="Riwayat aktivitas user tetap tersimpan di log aktivitas." data-confirm-title="Hapus User?" data-confirm-subject="{{ $user->username }}" data-confirm-button="Ya, Hapus">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1" title="Hapus pengguna" style="font-size: 0.75rem;">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-5 text-center text-muted">
                                <i class="fa-solid fa-users-slash fs-2 mb-2 d-block text-secondary opacity-50"></i>
                                Belum ada data user terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- POP-UP MODAL 1: EDIT USER -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <form id="editUserForm" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="modal-header border-bottom p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 38px; height: 38px;">
                                <i class="fa-solid fa-user-pen"></i>
                            </div>
                            <div>
                                <h5 class="modal-title h6 fw-bold text-dark m-0" id="editUserModalLabel">Edit Data Pengguna</h5>
                                <p class="small text-muted mb-0">Ubah username atau penetapan role user.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-uppercase fw-bold text-secondary small tracking-wider mb-1" style="font-size: 0.7rem;">
                                Nama Pengguna <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="edit_username" name="username" required class="form-control rounded-3 py-2 px-3">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-uppercase fw-bold text-secondary small tracking-wider mb-1" style="font-size: 0.7rem;">
                                Peran / Hak Akses <span class="text-danger">*</span>
                            </label>
                            <select id="edit_role_id" name="role_id" required class="form-select rounded-3 py-2 px-3">
                                <option value="" disabled>-- Pilih Peran --</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}">{{ $r->display_name ?? ucfirst($r->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer border-top p-3 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light rounded-pill px-4 text-sm font-medium" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 text-sm font-semibold shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="fa-solid fa-floppy-disk small"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- POP-UP MODAL 2: RESET PASSWORD -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <form id="resetPasswordForm" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    <div class="modal-header border-bottom p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 bg-warning bg-opacity-10 d-flex align-items-center justify-content-center text-warning fw-bold" style="width: 38px; height: 38px;">
                                <i class="fa-solid fa-key"></i>
                            </div>
                            <div>
                                <h5 class="modal-title h6 fw-bold text-dark m-0" id="resetPasswordModalLabel">Atur Ulang Kata Sandi</h5>
                                <p class="small text-muted mb-0">Ubah password untuk user: <strong class="text-primary" id="reset_username_text"></strong></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-uppercase fw-bold text-secondary small tracking-wider mb-1" style="font-size: 0.7rem;">
                                Password Baru Custom <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" id="customResetPassword" name="password" required minlength="8" 
                                       placeholder="Masukkan minimal 8 karakter" class="form-control rounded-start-3 py-2 px-3">
                                <button type="button" class="btn btn-outline-secondary rounded-end-3 px-3 border-start-0" onclick="togglePasswordVisibility('customResetPassword', 'iconResetPassword')">
                                    <i class="fa-solid fa-eye" id="iconResetPassword"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-uppercase fw-bold text-secondary small tracking-wider mb-1" style="font-size: 0.7rem;">
                                Konfirmasi Password Baru <span class="text-danger">*</span>
                            </label>
                            <input type="password" name="password_confirmation" required minlength="8" 
                                   placeholder="Ketik ulang password baru" class="form-control rounded-3 py-2 px-3">
                        </div>
                    </div>

                    <div class="modal-footer border-top p-3 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light rounded-pill px-4 text-sm font-medium" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4 text-sm font-semibold shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="fa-solid fa-check small"></i> Simpan Kata Sandi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
function openEditModal(id, username, roleId) {
    const form = document.getElementById('editUserForm');
    form.action = "{{ url('management/users') }}/" + id;

    document.getElementById('edit_username').value = username;
    document.getElementById('edit_role_id').value = roleId;

    const modalEl = document.getElementById('editUserModal');
    if (modalEl) {
        const instance = bootstrap.Modal.getOrCreateInstance(modalEl);
        instance.show();
    }
}

function openResetModal(id, username) {
    const form = document.getElementById('resetPasswordForm');
    form.action = "{{ url('management/users') }}/" + id + "/reset-password";

    document.getElementById('reset_username_text').innerText = username;

    const modalEl = document.getElementById('resetPasswordModal');
    if (modalEl) {
        const instance = bootstrap.Modal.getOrCreateInstance(modalEl);
        instance.show();
    }
}

function togglePasswordVisibility(inputId, iconId) {
    const passwordInput = document.getElementById(inputId);
    const icon = document.getElementById(iconId);

    if (passwordInput && icon) {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}
</script>
@endsection