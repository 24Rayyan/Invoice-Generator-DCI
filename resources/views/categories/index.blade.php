@extends('layouts.app')

@push('styles')
<style>
    .coa-page {
        --coa-ink: #172033;
        --coa-muted: #7b8494;
        --coa-line: #e9edf3;
        color: var(--coa-ink);
    }

    .coa-page .coa-title {
        font-size: clamp(1.55rem, 2.5vw, 2rem);
        letter-spacing: -0.045em;
    }

    .coa-page .coa-subtitle,
    .coa-page .coa-eyebrow {
        color: var(--coa-muted);
    }

    .coa-page .coa-panel {
        border: 0;
        border-radius: 17px;
        box-shadow: 0 5px 20px rgba(20, 34, 58, 0.04);
    }

    .coa-page .coa-kpi {
        min-height: 118px;
        border: 1px solid #eff1f5;
        border-radius: 16px;
        box-shadow: 0 5px 18px rgba(20, 34, 58, 0.035);
    }

    .coa-page .coa-table {
        min-width: 1000px;
    }

    .coa-page .coa-table thead th {
        background: #f8f9fb;
        color: #747e8d;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.035em;
        padding-top: 0.8rem;
        padding-bottom: 0.8rem;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .coa-page .coa-table tbody td {
        border-color: var(--coa-line);
        font-size: 0.76rem;
        padding-top: 0.85rem;
        padding-bottom: 0.85rem;
        vertical-align: middle;
    }

    .coa-page .coa-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .coa-page .coa-action-stack {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }

    .coa-page .coa-action-button {
        width: 42px;
        height: 30px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
    }

    .coa-page .coa-pagination .pagination {
        margin-bottom: 0;
    }

    .coa-page .coa-modal-body {
        max-height: min(70vh, 650px);
        overflow-y: auto;
    }

    @media (max-width: 575.98px) {
        .coa-page .coa-kpi {
            min-height: 106px;
        }

        .coa-page .coa-panel-header {
            padding: 1rem !important;
        }
    }
</style>
@endpush

@section('content')
<div class="coa-page">
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="coa-eyebrow small fw-semibold text-uppercase mb-1">Master Data Keuangan</div>
        <h1 class="coa-title fw-bold mb-1">Chart of Accounts & Kategori Pajak</h1>
        <p class="coa-subtitle small mb-0">Kelola klasifikasi akun dan perlakuan fiskal untuk pembukuan serta laporan pajak.</p>
    </div>
    <button type="button" class="btn btn-primary shadow-sm rounded-3 px-3 py-2" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
        <i class="fa-solid fa-plus me-1"></i> Tambah Akun Baru
    </button>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
    <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
    <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Quick Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card coa-kpi p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <div>
                    <span class="text-muted fw-semibold font-xs text-uppercase">Akun Sesuai Filter</span>
                    <h4 class="fw-bold text-dark mb-0 mt-1">{{ $stats['total'] }} Akun</h4>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3">
                    <i class="fa-solid fa-folder-tree fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card coa-kpi p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <div>
                    <span class="text-muted fw-semibold font-xs text-uppercase">Beban Fiskal (Deductible)</span>
                    <h4 class="fw-bold text-success mb-0 mt-1">
                        {{ $stats['deductible'] }} Akun
                    </h4>
                    <small class="text-muted font-xs">Pengurang Penghasilan Kena Pajak</small>
                </div>
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                    <i class="fa-solid fa-shield-halved fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card coa-kpi p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <div>
                    <span class="text-muted fw-semibold font-xs text-uppercase">Koreksi Fiskal Positif</span>
                    <h4 class="fw-bold text-warning mb-0 mt-1">
                        {{ $stats['non_deductible'] }} Akun
                    </h4>
                    <small class="text-muted font-xs">Non-Deductible pada SPT Tahunan</small>
                </div>
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3">
                    <i class="fa-solid fa-hand-holding-dollar fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card coa-panel mb-4">
    <div class="card-body p-3 p-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                <i class="fa-solid fa-filter"></i>
            </div>
            <div>
                <h2 class="h6 fw-bold mb-0">Cari & Filter Akun</h2>
                <p class="text-muted small mb-0">Temukan akun berdasarkan kode, nama, keterangan, atau tipe.</p>
            </div>
        </div>
        <form action="{{ route('categories.index') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-6 col-lg-7">
                <label for="coaSearch" class="form-label small fw-semibold text-secondary mb-1">Pencarian</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input id="coaSearch" type="search" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Cari kode, nama akun, atau keterangan...">
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label for="coaType" class="form-label small fw-semibold text-secondary mb-1">Tipe Akun</label>
                <select id="coaType" name="type" class="form-select smooth-dropdown" data-placeholder="Semua tipe akun">
                    <option value="">Semua tipe akun</option>
                    <option value="income" @selected(request('type') === 'income')>Pendapatan</option>
                    <option value="expense" @selected(request('type') === 'expense')>Beban / Biaya</option>
                </select>
            </div>
            <div class="col-12 col-md-2 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 rounded-3"><i class="fa-solid fa-filter me-1"></i>Filter</button>
                @if(request()->filled('search') || request()->filled('type'))
                    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary rounded-3" title="Reset filter" aria-label="Reset filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Main Table Card -->
<div class="card coa-panel">
    <div class="card-header bg-white border-0 p-3 p-lg-4 coa-panel-header">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
            <div>
                <h2 class="h6 fw-bold mb-1">Daftar Akun & Perlakuan Fiskal</h2>
                <p class="small text-muted mb-0">Tinjau klasifikasi akun dan pengaruhnya terhadap penghasilan kena pajak.</p>
            </div>
            <span class="badge rounded-pill bg-light text-secondary border px-3 py-2">{{ $categories->total() }} akun ditemukan</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 coa-table">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 120px;">Kode Akun</th>
                        <th>Nama Akun / Kategori</th>
                        <th style="width: 140px;">Tipe Akun</th>
                        <th style="width: 260px;">Status Fiskal SPT Tahunan</th>
                        <th>Keterangan</th>
                        <th class="text-center" style="width: 110px;">Transaksi</th>
                        <th class="text-center" style="width: 90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td class="ps-4">
                            <span class="badge bg-dark bg-opacity-75 font-mono px-2.5 py-1.5 fw-bold">
                                {{ $category->code }}
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold text-dark">{{ $category->name }}</span>
                            @if($category->code === '5-201')
                                <span class="badge bg-primary ms-1" style="font-size: 0.675rem;">Wajib Nominatif</span>
                            @endif
                        </td>
                        <td>
                            @if($category->type === 'income')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="fa-solid fa-arrow-down-left me-1"></i> Pendapatan
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                    <i class="fa-solid fa-arrow-up-right me-1"></i> Beban / Biaya
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($category->type === 'income')
                                <span class="text-muted font-xs">
                                    <i class="fa-solid fa-check text-success me-1"></i> Penghasilan Bruto
                                </span>
                            @elseif($category->is_tax_deductible)
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                    <i class="fa-solid fa-circle-check me-1"></i> Deductible (Biaya Fiskal)
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                                    <i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i> Non-Deductible (Koreksi Fiskal)
                                </span>
                            @endif
                        </td>
                        <td class="text-muted font-xs">
                            {{ $category->description ?? '-' }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
                                {{ $category->transactions_count }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="coa-action-stack">
                                <button type="button" class="btn btn-light text-primary border coa-action-button btn-edit-category"
                                    data-id="{{ $category->id }}"
                                    data-code="{{ $category->code }}"
                                    data-name="{{ $category->name }}"
                                    data-type="{{ $category->type }}"
                                    data-is_tax_deductible="{{ $category->is_tax_deductible ? '1' : '0' }}"
                                    data-description="{{ $category->description }}"
                                    title="Edit akun {{ $category->code }}" aria-label="Edit akun {{ $category->code }}">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>
                                <form action="{{ route('categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $category->code }} - {{ $category->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-light text-danger border coa-action-button"
                                        {{ $category->transactions_count > 0 ? 'disabled title=Tidak_bisa_dihapus_karena_ada_transaksi' : '' }}
                                        aria-label="Hapus akun {{ $category->code }}">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-folder-open fs-2 mb-2 d-block text-secondary"></i>
                            Belum ada akun kategori yang dibuat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-0 border-top p-3 p-lg-4 coa-pagination">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="small text-muted">
                @if($categories->total() > 0)
                    Menampilkan <strong>{{ $categories->firstItem() }}–{{ $categories->lastItem() }}</strong>
                    dari <strong>{{ $categories->total() }}</strong> akun
                @else
                    Tidak ada akun yang sesuai dengan filter.
                @endif
            </div>
            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                <form action="{{ route('categories.index') }}" method="GET" class="d-flex align-items-center gap-2">
                    @if(request()->filled('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request()->filled('type'))
                        <input type="hidden" name="type" value="{{ request('type') }}">
                    @endif
                    <label for="categoriesPerPage" class="small text-muted text-nowrap mb-0">Baris per halaman</label>
                    <select id="categoriesPerPage" name="per_page" class="form-select form-select-sm smooth-dropdown pagination-dropdown" data-placeholder="Baris" onchange="this.form.submit()">
                        @foreach([10, 25, 50] as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </form>
                @if($categories->hasPages())
                    {{ $categories->onEachSide(1)->links('pagination::bootstrap-5') }}
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Akun Kategori -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white rounded-top-4 px-4 py-3">
                <h5 class="modal-title fw-bold" id="createCategoryModalLabel">
                    <i class="fa-solid fa-plus-circle text-primary me-2"></i> Tambah Akun Perkiraan (COA)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('categories.store') }}" method="POST">
                @csrf
                <div class="modal-body coa-modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-bold text-dark font-sm">Kode Akun <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="Misal: 5-101" required>
                            <small class="text-muted font-xs">Format: [Kelompok]-[Nomor]</small>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-bold text-dark font-sm">Tipe Akun <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="expense">Beban / Pengeluaran (Expense)</option>
                                <option value="income">Pendapatan (Income)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-dark font-sm">Nama Akun <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Misal: Beban Alat Tulis Kantor" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-dark font-sm">Perlakuan Fiskal SPT Tahunan <span class="text-danger">*</span></label>
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="is_tax_deductible" id="deductibleYes" value="1" checked>
                                    <label class="form-check-label fw-semibold text-success font-sm" for="deductibleYes">
                                        <i class="fa-solid fa-check-circle me-1"></i> Deductible Expense (Dapat Mengurangi Penghasilan Kena Pajak)
                                    </label>
                                    <div class="text-muted font-xs ms-4">Biaya operasional 3M (Mendapatkan, Menagih, Memelihara penghasilan) yang sah secara pajak.</div>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_tax_deductible" id="deductibleNo" value="0">
                                    <label class="form-check-label fw-semibold text-warning-emphasis font-sm" for="deductibleNo">
                                        <i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i> Non-Deductible (Koreksi Fiskal Positif pada SPT)
                                    </label>
                                    <div class="text-muted font-xs ms-4">Biaya pribadi, denda pajak, sumbangan non-daftar, atau biaya tanpa bukti sah.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-dark font-sm">Keterangan / Catatan</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Catatan peruntukan akun ini..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 py-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1.5"></i> Simpan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Akun Kategori -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white rounded-top-4 px-4 py-3">
                <h5 class="modal-title fw-bold" id="editCategoryModalLabel">
                    <i class="fa-solid fa-pencil text-primary me-2"></i> Edit Akun Perkiraan (COA)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body coa-modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-bold text-dark font-sm">Kode Akun <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="edit_code" class="form-control" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-bold text-dark font-sm">Tipe Akun <span class="text-danger">*</span></label>
                            <select name="type" id="edit_type" class="form-select" required>
                                <option value="expense">Beban / Pengeluaran (Expense)</option>
                                <option value="income">Pendapatan (Income)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-dark font-sm">Nama Akun <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-dark font-sm">Perlakuan Fiskal SPT Tahunan <span class="text-danger">*</span></label>
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="is_tax_deductible" id="edit_deductibleYes" value="1">
                                    <label class="form-check-label fw-semibold text-success font-sm" for="edit_deductibleYes">
                                        <i class="fa-solid fa-check-circle me-1"></i> Deductible Expense (Dapat Mengurangi Penghasilan Kena Pajak)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_tax_deductible" id="edit_deductibleNo" value="0">
                                    <label class="form-check-label fw-semibold text-warning-emphasis font-sm" for="edit_deductibleNo">
                                        <i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i> Non-Deductible (Koreksi Fiskal Positif)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-dark font-sm">Keterangan / Catatan</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 py-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1.5"></i> Perbarui Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.btn-edit-category').on('click', function() {
            var id = $(this).data('id');
            var code = $(this).data('code');
            var name = $(this).data('name');
            var type = $(this).data('type');
            var isDeductible = $(this).data('is_tax_deductible');
            var description = $(this).data('description');

            $('#editCategoryForm').attr('action', '/categories/' + id);
            $('#edit_code').val(code);
            $('#edit_name').val(name);
            $('#edit_type').val(type);
            $('#edit_description').val(description);

            if (isDeductible == '1') {
                $('#edit_deductibleYes').prop('checked', true);
            } else {
                $('#edit_deductibleNo').prop('checked', true);
            }

            var modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            modal.show();
        });
    });
</script>
@endpush
