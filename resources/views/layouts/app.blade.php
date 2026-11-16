<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $globalSetting = \App\Models\Setting::getSetting();
    @endphp

    <title>{{ $title ?? 'Mini ERP' }}</title>
    
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

    
    <link rel="stylesheet" href="{{ asset('css/erp.css') }}?v={{ @filemtime(public_path('css/erp.css')) }}">

    <style>
        /* Navbar "floating island" — identitas visual aplikasi, dipertahankan. */
        body { display: flex; flex-direction: column; }
        /* --- FLOATING ISLAND NAVBAR STYLE --- */
        .floating-navbar-wrapper {
            position: sticky;
            top: 0;
            z-index: 1030;
            padding: 16px 20px 8px 20px;
        }

        .navbar-floating {
            background-color: rgba(255, 255, 255, 0.92) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 9999px;
            padding: 7px 16px !important;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.03);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }

        .navbar-brand-logo {
            max-height: 26px;
            width: auto;
            object-fit: contain;
        }

        /* Keep the wide item table scrollable without widening the mobile page. */
        .document-items-table-container {
            contain: paint;
        }

        .brand-text {
            font-weight: 800;
            letter-spacing: -0.4px;
            font-size: 0.98rem;
            color: #0f172a;
        }

        /* Nav Links */
        .nav-link-custom {
            color: #475569 !important;
            font-weight: 600;
            font-size: 0.84rem;
            padding: 0.45rem 1.1rem !important;
            min-height: 44px;
            border-radius: 9999px;
            transition: color 0.2s ease, background-color 0.2s ease;
            margin: 0 2px;
            display: inline-flex;
            align-items: center;
        }

        .nav-link-custom:hover {
            color: #0f172a !important;
            background-color: #f1f5f9;
        }

        .nav-link-custom:active,
        .dropdown-menu-custom .dropdown-item:active {
            transform: translateY(1px);
        }

        .nav-link-custom.active {
            color: #ffffff !important;
            background-color: #2563eb;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .navbar-floating .text-info {
            color: #055160 !important;
        }

        .navbar-floating .text-warning {
            color: #805600 !important;
        }

        .bg-success-subtle.text-success {
            color: #146c43 !important;
        }

        .bg-primary-subtle.text-primary {
            color: #084298 !important;
        }

        .action-buttons-wrapper .nav-link-custom.active {
            color: #2563eb !important;
            background-color: #ffffff !important;
        }

        /* Dropdown Custom Styling */
        .dropdown-menu-custom {
            background-color: #ffffff !important;
            border-radius: 16px !important;
            box-shadow: 0 20px 30px -10px rgba(15, 23, 42, 0.12), 0 8px 15px -4px rgba(15, 23, 42, 0.06) !important;
            border: 1px solid #e2e8f0 !important;
            padding: 8px !important;
            margin-top: 10px !important;
        }

        .dropdown-menu-custom .dropdown-item {
            color: #334155;
            font-size: 0.83rem;
            font-weight: 600;
            border-radius: 10px;
            padding: 8px 14px;
            transition: color 0.15s ease, background-color 0.15s ease;
        }

        .dropdown-menu-custom .dropdown-item:hover {
            background-color: #f1f5f9;
            color: #2563eb;
        }

        .dropdown-menu-custom .dropdown-item.active {
            background-color: #eff6ff !important;
            color: #2563eb !important;
        }

        /* Responsive Navbar Mobile Adjustments */
        @media (max-width: 991.98px) {
            .navbar-floating {
                border-radius: 20px;
                padding: 10px 16px !important;
            }
            .nav-link-custom {
                border-radius: 10px;
                margin: 2px 0;
            }
        }
        .navbar-floating { background-color: rgba(255,255,255,.92) !important; }
        @media (min-width: 992px) and (max-width: 1399.98px) {
            .navbar-brand > span.fw-semibold { display: none; }
            .nav-link-custom { padding: 0.45rem 0.7rem !important; font-size: 0.8rem; margin: 0; }
            .nav-link-custom i.me-2 { margin-right: 0.4rem !important; }
            .navbar-floating .navbar-nav { gap: 0 !important; }
        }
    </style>

    @stack('styles')
</head>
<body>

    <!-- FLOATING ISLAND HEADER BAR -->
    <div class="floating-navbar-wrapper">
        <div class="container-fluid px-lg-5">
            <nav class="navbar navbar-expand-lg navbar-light navbar-floating position-relative">
                
                <!-- 1. Brand & Logo (Sisi Kiri) -->
                <a class="navbar-brand d-flex align-items-center gap-2 me-0 ms-2" href="{{ route('dashboard') }}">
                    @if(isset($globalSetting) && $globalSetting->company_logo && file_exists(public_path('storage/' . $globalSetting->company_logo)))
                        <img src="{{ asset('storage/' . $globalSetting->company_logo) }}" alt="Logo" class="navbar-brand-logo">
                    @else
                        <img src="{{ asset('images/navbar-user.png') }}" alt="Profil pengguna" class="navbar-brand-logo" style="width: 32px; height: 32px;">
                    @endif
                    <span class="fw-semibold text-dark fs-6 ms-3">
                        Halo {{ Auth::user()->username ?? 'Pengguna' }}!
                    </span>
                </a>

                <!-- Mobile Toggle -->
                <button class="navbar-toggler border-0 shadow-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- 2. Main Content Wrapper -->
                <div class="collapse navbar-collapse w-100" id="mainNavbar">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between w-100">
                        
                        <div class="d-none d-lg-block" style="flex: 1;"></div>

                        <!-- Menu Utama -->
                        <ul class="navbar-nav justify-content-center align-items-center gap-lg-2 my-2 my-lg-0">
                            
                            @if(auth()->user()->hasAccess('dashboard'))
                                <li class="nav-item">
                                    <a class="nav-link nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                        <i class="fa-solid fa-chart-pie me-2"></i> Ringkasan
                                    </a>
                                </li>
                            @endif

                            @if(auth()->user()->hasAccess('invoices') || auth()->user()->hasAccess('customers') || auth()->user()->hasAccess('projects'))
                                    <li class="nav-item dropdown">
                                        <a class="nav-link nav-link-custom dropdown-toggle {{ request()->routeIs('documents.*') || request()->routeIs('purchase-orders.*') || request()->routeIs('customers.*') || request()->routeIs('projects.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-file-invoice-dollar me-2"></i> Invoice & PO
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-custom border-0 shadow-lg" style="min-width: 250px;">
                                            @if(auth()->user()->hasAccess('invoices'))
                                                <li>
                                                    <a class="dropdown-item py-2 {{ request()->routeIs('documents.index') || (request()->routeIs('documents.show', 'documents.edit', 'documents.duplicate') && (request()->route('document')?->type === 'INVOICE' || (isset($sourceDoc) && $sourceDoc->type === 'INVOICE'))) ? 'active' : '' }}" href="{{ route('documents.index') }}">
                                                        <i class="fa-solid fa-file-invoice me-2 text-warning"></i> Kelola Invoice
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2 {{ request()->routeIs('purchase-orders.*') || (request()->routeIs('documents.show', 'documents.edit', 'documents.duplicate') && (request()->route('document')?->type === 'PO' || (isset($sourceDoc) && $sourceDoc->type === 'PO'))) ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}">
                                                        <i class="fa-solid fa-clipboard-list me-2 text-info"></i> Kelola PO
                                                    </a>
                                                </li>
                                            @endif
                                            @if(auth()->user()->hasAccess('customers'))
                                                <li>
                                                    <a class="dropdown-item py-2 {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                                                        <i class="fa-solid fa-users me-2 text-success"></i> Data Pelanggan
                                                    </a>
                                                </li>
                                            @endif
                                            @if(auth()->user()->hasAccess('projects'))
                                                <li>
                                                    <a class="dropdown-item py-2 {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}">
                                                        <i class="fa-solid fa-diagram-project me-2 text-info"></i> Manajemen Proyek
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                            @endif

                            @if(auth()->user()->hasAccess('expenses') || auth()->user()->hasAccess('categories'))
                                <li class="nav-item dropdown">
                                    <a class="nav-link nav-link-custom dropdown-toggle {{ request()->routeIs('transactions.*') || request()->routeIs('categories.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-money-bill-transfer me-2"></i> Buku Kas & Transaksi
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-custom border-0 shadow-lg" style="min-width: 260px;">
                                        @if(auth()->user()->hasAccess('expenses'))
                                            <li>
                                                <a class="dropdown-item py-2 {{ request()->routeIs('transactions.index') ? 'active' : '' }}" href="{{ route('transactions.index') }}">
                                                    <i class="fa-solid fa-receipt me-2 text-info"></i> Jurnal Transaksi Kas
                                                </a>
                                            </li>
                                        @endif
                                        @if(auth()->user()->hasAccess('categories'))
                                            <li><hr class="dropdown-divider border-secondary opacity-10 my-1"></li>
                                            <li>
                                                <a class="dropdown-item py-2 {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                                                    <i class="fa-solid fa-tags me-2 text-warning"></i> Master Akun & COA
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </li>
                            @endif

                            @if(auth()->user()->hasAccess('financial_reports') || auth()->user()->hasAccess('profit_loss') || auth()->user()->hasAccess('project_margin'))
                                <li class="nav-item dropdown">
                                    <a class="nav-link nav-link-custom dropdown-toggle {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-chart-line me-2"></i> Laporan Keuangan
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-custom border-0 shadow-lg" style="min-width: 280px;">
                                        @if(auth()->user()->hasAccess('financial_reports'))
                                            <li>
                                                <a class="dropdown-item py-2 {{ request()->routeIs('reports.financial_statement*') ? 'active' : '' }}" href="{{ route('reports.financial_statement') }}">
                                                    <i class="fa-solid fa-file-contract me-2 text-info"></i> Laporan Keuangan
                                                </a>
                                            </li>
                                        @endif
                                        @if(auth()->user()->hasAccess('profit_loss'))
                                            <li>
                                                <a class="dropdown-item py-2 {{ request()->routeIs('reports.profit_loss*') ? 'active' : '' }}" href="{{ route('reports.profit_loss') }}">
                                                    <i class="fa-solid fa-scale-balanced me-2 text-primary"></i> Laba Rugi Komersial & Fiskal
                                                </a>
                                            </li>
                                        @endif
                                        @if(auth()->user()->hasAccess('project_margin'))
                                            <li>
                                                <a class="dropdown-item py-2 {{ request()->routeIs('reports.project_margin') ? 'active' : '' }}" href="{{ route('reports.project_margin') }}">
                                                    <i class="fa-solid fa-chart-pie me-2 text-success"></i> Margin Proyek / Invoice
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </li>
                            @endif


                            @if(auth()->user()->hasAccess('user_management') || auth()->user()->hasAccess('activity_logs') || auth()->user()->hasAccess('system_maintenance'))
                                <li class="nav-item dropdown">
                                    <a class="nav-link nav-link-custom dropdown-toggle {{ request()->routeIs('management.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-user-gear me-2"></i> Pengelolaan System
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-custom border-0 shadow-lg" style="min-width: 220px;">
                                        @if(auth()->user()->hasAccess('user_management'))
                                            <li>
                                                <a class="dropdown-item py-2 {{ request()->routeIs('management.users.*') ? 'active' : '' }}" href="{{ route('management.users.index') }}">
                                                    <i class="fa-solid fa-users-gear me-2 text-primary"></i> Kelola Pengguna
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 {{ request()->routeIs('management.roles.*') ? 'active' : '' }}" href="{{ route('management.roles.index') }}">
                                                    <i class="fa-solid fa-shield-halved me-2 text-warning"></i> Kelola Peran & Hak Akses
                                                </a>
                                            </li>
                                        @endif
                                        @if(auth()->user()->hasAccess('activity_logs'))
                                            <li>
                                                <a class="dropdown-item py-2 {{ request()->routeIs('activity_logs.*') ? 'active' : '' }}" href="{{ route('activity_logs.index') }}">
                                                    <i class="fa-solid fa-clock-rotate-left me-2"></i> Log Aktivitas
                                                </a>
                                            </li>
                                        @endif
                                        @if(auth()->user()->hasAccess('system_maintenance'))
                                            <li>
                                                <a class="dropdown-item py-2 {{ request()->routeIs('system-maintenance.*') ? 'active' : '' }}" href="{{ route('system-maintenance.index') }}">
                                                    <i class="fa-solid fa-database me-2 text-secondary"></i> Database &amp; Pemeliharaan
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </li>
                            @endif

                        </ul>

                        <!-- Navigasi Aksi: Settings & Logout (Sisi Kanan) -->
                        <div class="d-flex align-items-center justify-content-center justify-content-lg-end" style="flex: 1;">
                            <div class="action-buttons-wrapper d-flex align-items-center bg-light border rounded-pill p-1 gap-1 shadow-sm">
                                
                                @if(auth()->user()->hasAccess('settings'))
                                    <a class="nav-link nav-link-custom rounded-circle d-flex align-items-center justify-content-center {{ request()->routeIs('settings.*') ? 'active bg-white text-primary shadow-sm' : 'text-secondary' }}" 
                                    href="{{ route('settings.index') }}" 
                                    style="width: 44px; height: 44px; transition: color 0.2s ease, background-color 0.2s ease;"
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="bottom" 
                                    title="Pengaturan Perusahaan">
                                        <i class="fa-solid fa-sliders fs-6"></i>
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                                    @csrf
                                    <button type="submit" 
                                            class="btn btn-link nav-link-custom rounded-circle d-flex align-items-center justify-content-center text-danger border-0 p-0" 
                                            style="width: 44px; height: 44px; transition: color 0.2s ease, background-color 0.2s ease;"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="bottom" 
                                            title="Keluar">
                                        <i class="fa-solid fa-right-from-bracket fs-6"></i>
                                    </button>
                                </form>

                            </div>
                        </div>

                    </div>
                </div>

            </nav>
        </div>
    </div>

    <!-- MAIN CONTENT CONTAINER -->
    <main class="container-fluid px-lg-5 py-4 app-content" id="main-content">
        @yield('content')
    </main>

    <!-- Core Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    
    <script>
        // Setup CSRF Token secara otomatis untuk semua request AJAX jQuery
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Inisialisasi Bootstrap Tooltips
        document.addEventListener("DOMContentLoaded", function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>

    @include('partials.smooth_dropdown_script')
    @include('partials.ui_feedback')
    @stack('scripts')
</body>
</html>
