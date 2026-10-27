@extends('layouts.app')

@section('content')
<!-- Header Page / Breadcrumb -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted small">Dashboard</a></li>
                <li class="breadcrumb-item active small fw-semibold" aria-current="page">Direktori Customer</li>
            </ol>
        </nav>
        <h3 class="fw-bold text-dark mb-0 tracking-tight">Direktori Customer & Lawan Transaksi</h3>
        <p class="text-muted small mb-0">Master data rekanan, kontak penagihan, serta identitas perpajakan untuk impor Coretax DJP.</p>
    </div>
    <div>
        <a href="{{ route('customers.create') }}" class="btn btn-primary px-3.5 py-2 shadow-sm fw-bold">
            <i class="fa-solid fa-plus me-1.5"></i> Tambah Customer Baru
        </a>
    </div>
</div>

<!-- Main Data Card -->
<div class="card border-0 shadow-sm overflow-hidden mb-4">
    <div class="card-body p-4">
        
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="customerTable">
                <thead>
                    <tr>
                        <th width="4%" class="text-center">No</th>
                        <th width="24%">Nama Customer / Perusahaan</th>
                        <th width="22%">Identitas Pajak (Coretax)</th>
                        <th width="20%">Kontak & WhatsApp</th>
                        <th width="20%">Alamat Penagihan</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $index => $c)
                    <tr>
                        <td class="text-center fw-semibold text-muted small">{{ $index + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-3 flex-shrink-0 d-flex align-items-center justify-content-center fw-bold text-primary bg-primary bg-opacity-10 rounded-circle" style="width: 38px; height: 38px; font-size: 0.9rem;">
                                    {{ strtoupper(substr($c->name, 0, 2)) }}
                                </div>
                                <div>
                                    <strong class="text-dark d-block fw-bold">{{ $c->name }}</strong>
                                    <span class="badge bg-light text-muted border font-xs mt-0.5">Kode FP: <strong>{{ $c->default_tax_transaction_code ?? '040' }}</strong></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($c->tax_id_number)
                                <div class="d-flex flex-column">
                                    <span class="font-monospace fw-semibold text-dark font-xs">
                                        <span class="badge bg-primary bg-opacity-10 text-primary me-1">{{ $c->tax_id_type ?? 'NPWP16' }}</span>
                                        {{ $c->tax_id_number }}
                                    </span>
                                    <small class="text-muted font-xs mt-1">NITKU: <code>{{ Str::limit($c->nitku ?? '0000000000000000000000', 12) }}...</code></small>
                                </div>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary font-xs">Non-NPWP</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                @if($c->phone)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $c->phone) }}" target="_blank" class="text-decoration-none text-dark font-xs fw-medium d-inline-flex align-items-center" title="Kirim Pesan WhatsApp">
                                        <i class="fa-brands fa-whatsapp text-success me-1.5 fs-6"></i> {{ $c->phone }}
                                    </a>
                                @endif
                                @if($c->email)
                                    <a href="mailto:{{ $c->email }}" class="text-decoration-none text-muted font-xs d-inline-flex align-items-center">
                                        <i class="fa-solid fa-envelope text-primary me-1.5 opacity-75"></i> {{ $c->email }}
                                    </a>
                                @endif
                                @if(!$c->phone && !$c->email)
                                    <span class="text-muted font-xs">-</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="text-muted small text-truncate d-block" style="max-width: 220px;" title="{{ $c->address }}">
                                {{ $c->address ?? '-' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                <!-- Tombol Edit -->
                                <a href="{{ route('customers.edit', $c->id) }}" class="btn btn-sm btn-light text-warning p-1.5 rounded-2 border" title="Edit Customer">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>

                                <!-- Form Hapus Customer -->
                                <form action="{{ route('customers.destroy', $c->id) }}" method="POST" class="d-inline form-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-light text-danger p-1.5 rounded-2 border btn-delete" title="Hapus Customer">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const customerTable = $('#customerTable').DataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Cari customer...",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ customer",
                infoEmpty: "Tidak ada data customer",
                zeroRecords: "Data customer tidak ditemukan",
                paginate: {
                    previous: "<i class='fa-solid fa-chevron-left'></i>",
                    next: "<i class='fa-solid fa-chevron-right'></i>"
                }
            }
        });

        const pageLengthSelect = customerTable.table().container().querySelector('.dataTables_length select');
        if (pageLengthSelect) {
            pageLengthSelect.classList.add('form-select', 'form-select-sm', 'smooth-dropdown', 'pagination-dropdown');
            window.initializeSmoothDropdowns(pageLengthSelect);
        }

        // SweetAlert Delete Confirmation
        $('.btn-delete').on('click', function(e) {
            e.preventDefault();
            let form = $(this).closest('form');
            
            Swal.fire({
                title: 'Hapus Customer?',
                text: "Customer ini beserta riwayat data terkait akan dihapus!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush