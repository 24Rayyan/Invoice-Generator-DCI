@extends('layouts.app')

@section('content')
<div class="container-fluid p-0">

    <!-- 1. Executive Summary / Dark Hero Banner Container -->
    <div class="card bg-dark text-white rounded-4 p-4 p-md-4 shadow-lg border-0 mb-4 position-relative overflow-hidden" style="background-color: #1E293B !important;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 position-relative z-1">
            <div>
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-secondary bg-opacity-25 border border-secondary border-opacity-50 rounded-pill text-xs font-semibold text-info mb-2" style="font-size: 0.75rem;">
                    <i class="fa-solid fa-key"></i> RBAC Security Architecture
                </div>
                <h1 class="h3 fw-bold tracking-tight text-white m-0">Role & Module Access Matrix</h1>
                <p class="text-secondary small mt-1 mb-0" style="max-width: 600px;">
                    Atur role dan izin akses setiap modul aplikasi secara terpisah, termasuk invoice, proyek, transaksi, dan laporan.
                </p>
            </div>

            <a href="{{ route('management.users.index') }}" 
               class="btn btn-outline-light border-secondary border-opacity-50 rounded-pill px-4 py-2 text-xs font-semibold shadow-sm d-inline-flex align-items-center gap-2 text-white">
                <i class="fa-solid fa-arrow-left small"></i> Kembali ke Kelola User
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
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
                <span class="small fw-medium">{{ session('error') }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-4 p-3 mb-4 shadow-sm">
            <div class="fw-semibold small mb-1">Periksa kembali data role:</div>
            <ul class="small mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card h-100 border-0 rounded-4 p-3 shadow-sm bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Role</div>
                        <div class="h5 fw-bold text-dark mb-0">{{ $roles->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card h-100 border-0 rounded-4 p-3 shadow-sm bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fa-solid fa-cubes"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Modul yang Dapat Dikelola</div>
                        <div class="h5 fw-bold text-dark mb-0">{{ $modules->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Form Card: Tambah Role Baru -->
    <div class="card border-0 rounded-4 p-4 shadow-sm mb-4 bg-white">
        <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 38px; height: 38px;">
                    <img src="{{ asset('images/business-management.png') }}" alt="" width="24" height="24" class="object-fit-contain">
                </div>
                <div>
                    <h2 class="h6 fw-bold text-dark m-0">Tambah Role Baru</h2>
                    <p class="small text-muted mb-0">Buat tingkatan atau kategori hak akses baru untuk staf.</p>
                </div>
            </div>
        </div>

        <form action="{{ route('management.roles.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                
                <!-- Key Role Field -->
                <div class="col-12 col-md-4">
                    <label class="form-label text-uppercase fw-bold text-secondary small tracking-wider mb-1" style="font-size: 0.7rem;">
                        Key Role <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: manager" required 
                        class="form-control form-control-sm rounded-3 py-2 px-3 @error('name') is-invalid @enderror">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Display Name Field -->
                <div class="col-12 col-md-4">
                    <label class="form-label text-uppercase fw-bold text-secondary small tracking-wider mb-1" style="font-size: 0.7rem;">
                        Display Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="display_name" value="{{ old('display_name') }}" placeholder="Contoh: Operational Manager" required 
                        class="form-control form-control-sm rounded-3 py-2 px-3 @error('display_name') is-invalid @enderror">
                    @error('display_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Description Field -->
                <div class="col-12 col-md-4">
                    <label class="form-label text-uppercase fw-bold text-secondary small tracking-wider mb-1" style="font-size: 0.7rem;">
                        Deskripsi Peran
                    </label>
                    <input type="text" name="description" value="{{ old('description') }}" placeholder="Catatan tugas/otoritas role" 
                        class="form-control form-control-sm rounded-3 py-2 px-3 @error('description') is-invalid @enderror">
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

            </div>

            <div class="mt-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 text-sm fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-plus small"></i> Simpan Role
                </button>
            </div>
        </form>
    </div>

    <!-- 3. Mapping Grant Modul per Role Card -->
    <div class="card border-0 rounded-4 p-4 shadow-sm bg-white mb-4">
        <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
            <div>
                <h2 class="h6 fw-bold text-dark m-0">Matrix Otoritas Modul</h2>
                <p class="small text-muted mb-0">Atur alokasi hak akses modul sistem per kasta peran.</p>
            </div>
        </div>

        <div class="d-flex flex-column gap-4">
            @foreach($roles as $role)
                @php
                    // Pastikan dikonversi menjadi array integer secara murni
                    $assignedModuleIds = array_map('intval', $role->modules->pluck('id')->toArray());
                    $allModuleIds = array_map('intval', $modules->pluck('id')->toArray());
                    $isFullAccess = $role->hasFullAccess();
                    $isSystemRole = $role->hasFullAccess();
                @endphp

                <div class="card border rounded-4 shadow-sm overflow-hidden bg-white" 
                     x-data="{
                         selectedModules: {{ json_encode($assignedModuleIds) }},
                         allModules: {{ json_encode($allModuleIds) }},
                         isFullAccess: {{ $isFullAccess ? 'true' : 'false' }},
                         isOpen: false,
                         
                         toggleAll() {
                             if (this.isFullAccess) return;
                             if (this.selectedModules.length === this.allModules.length) {
                                 this.selectedModules = [];
                             } else {
                                 this.selectedModules = [...this.allModules];
                             }
                         },
                         
                         isGranted(id) {
                             return this.isFullAccess || this.selectedModules.map(Number).includes(Number(id));
                         },

                         toggleModule(id) {
                             if (this.isFullAccess) return;
                             const numId = Number(id);
                             if (this.isGranted(numId)) {
                                 this.selectedModules = this.selectedModules.filter(item => Number(item) !== numId);
                             } else {
                                 this.selectedModules.push(numId);
                             }
                         }
                     }">
                    
                    <!-- Role Header Bar -->
                    <div class="card-header bg-light bg-opacity-50 border-bottom p-3 p-md-4">
                        <div class="d-flex flex-column flex-xl-row align-items-start align-items-xl-center justify-content-between gap-3">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <h3 class="h6 fw-bold text-dark mb-0">{{ $role->display_name }}</h3>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border font-monospace" style="font-size: 0.68rem;">{{ $role->name }}</span>
                                    @if($isSystemRole)
                                        <span class="badge bg-dark bg-opacity-10 text-dark rounded-pill" style="font-size: 0.66rem;">Role Sistem</span>
                                    @endif
                                </div>
                                <p class="text-muted small mb-0 mt-1">{{ $role->description ?? 'Tidak ada deskripsi peran.' }}</p>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2">
                            @if($isFullAccess)
                                <span class="badge bg-dark text-white px-3 py-2 rounded-pill fw-bold" style="font-size: 0.72rem;">
                                    Full Access Guaranteed
                                </span>
                            @else
                                <!-- Counter Badge -->
                                <div class="px-3 py-1.5 rounded-pill bg-white border shadow-sm text-xs font-semibold text-dark">
                                    <span x-text="selectedModules.length"></span> / <span x-text="allModules.length"></span> Modul Aktif
                                </div>

                                <!-- Toggle All Button -->
                                <button type="button" 
                                        @click="toggleAll()" 
                                        class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 font-medium text-xs">
                                    <span x-text="selectedModules.length === allModules.length ? 'Batal Pilih Semua' : 'Pilih Semua'"></span>
                                </button>
                            @endif

                                <span class="badge bg-white border text-secondary rounded-pill px-3 py-2" title="Jumlah user dengan role ini">
                                    <i class="fa-solid fa-users me-1"></i>{{ $role->users_count }} User
                                </span>

                                @if(!$isSystemRole)
                                    @if($role->users_count > 0)
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-2" disabled
                                                title="Pindahkan user yang menggunakan role ini sebelum menghapusnya">
                                            <i class="fa-solid fa-lock me-1"></i>Role Digunakan
                                        </button>
                                    @else
                                        <form action="{{ route('management.roles.destroy', $role) }}" method="POST"
                                              onsubmit="return confirm('Hapus role ini secara permanen? Hak akses modul yang terkait juga akan dihapus.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-2">
                                                <i class="fa-solid fa-trash-can me-1"></i>Hapus Role
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>

                            <button type="button"
                                    class="btn btn-sm btn-outline-primary rounded-pill px-3 d-inline-flex align-items-center gap-2"
                                    @click="isOpen = !isOpen"
                                    :aria-expanded="isOpen.toString()"
                                    aria-controls="role-modules-{{ $role->id }}">
                                <span x-text="isOpen ? 'Tutup Akses' : 'Atur Akses'"></span>
                                <i class="fa-solid fa-chevron-down transition-transform"
                                   :class="{ 'rotate-180': isOpen }"
                                   aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Form & Interactive Module Selection Grid -->
                    <div id="role-modules-{{ $role->id }}"
                         x-show="isOpen"
                         x-transition.duration.200ms
                         style="display: none;">
                    <form action="{{ route('management.roles.grant-modules', $role) }}" method="POST">
                        @csrf
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex flex-column gap-4">
                                @foreach($moduleGroups as $group)
                                    <section>
                                        <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-3">
                                            <span class="rounded-2 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                                <i class="fa-solid {{ $group['icon'] }} small"></i>
                                            </span>
                                            <h4 class="h6 fw-bold text-dark mb-0">{{ $group['name'] }}</h4>
                                            <span class="badge bg-light text-muted border rounded-pill">{{ $group['modules']->count() }}</span>
                                        </div>
                                        <div class="row g-3">
                                            @foreach($group['modules'] as $module)
                                                @php
                                                    $modId = (int) $module->id;
                                                    $isAssigned = in_array($modId, $assignedModuleIds, true);
                                                @endphp
                                                <div class="col-12 col-sm-6 col-lg-3">
                                                    <label for="mod_{{ $role->id }}_{{ $modId }}"
                                                           class="w-100 h-100 p-3 rounded-3 border user-select-none cursor-pointer transition-all d-block"
                                                           :class="{
                                                               'border-primary bg-primary bg-opacity-10': isGranted({{ $modId }}),
                                                               'border-light-subtle bg-white': !isGranted({{ $modId }})
                                                           }">
                                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                                            <h5 class="fw-bold text-dark m-0" style="font-size: 0.88rem;">{{ $module->name }}</h5>
                                                            <input type="checkbox"
                                                                   id="mod_{{ $role->id }}_{{ $modId }}"
                                                                   name="modules[]"
                                                                   value="{{ $modId }}"
                                                                   class="form-check-input mt-0 fs-5 cursor-pointer"
                                                                   :checked="isGranted({{ $modId }})"
                                                                   @change="toggleModule({{ $modId }})"
                                                                   {{ $isAssigned || $isFullAccess ? 'checked' : '' }}
                                                                   {{ $isFullAccess ? 'disabled' : '' }}>
                                                        </div>
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <span class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $module->key }}</span>
                                                            <span class="badge rounded-pill fw-bold"
                                                                  :class="isGranted({{ $modId }}) ? 'bg-primary text-white' : 'bg-secondary bg-opacity-10 text-muted'"
                                                                  style="font-size: 0.65rem;"
                                                                  x-text="isGranted({{ $modId }}) ? 'AKTIF' : 'NONAKTIF'">
                                                            </span>
                                                        </div>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </section>
                                @endforeach
                            </div>
                        </div>

                        <!-- Card Footer: Save Button -->
                        @if(!$isFullAccess)
                            <div class="card-footer bg-white border-top p-3 d-flex align-items-center justify-content-end">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                                    <i class="fa-solid fa-floppy-disk small"></i> Simpan Hak Akses Peran
                                </button>
                            </div>
                        @endif
                    </form>
                    </div>

                </div>
            @endforeach
        </div>
    </div>

    <style>
    .cursor-pointer {
        cursor: pointer;
    }
    .transition-all {
        transition: all 0.15s ease-in-out;
    }
    </style>

</div>
@endsection