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

    
    <style>
        :root {
            --erp-primary: #2563eb;
            --erp-primary-hover: #1d4ed8;
            --erp-primary-light: #eff6ff;
            --erp-secondary: #0f172a;
            --erp-bg: #f8fafc;
            --erp-surface: #ffffff;
            --erp-border: #e2e8f0;
            --erp-text-main: #1e293b;
            --erp-text-muted: #64748b;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--erp-bg);
            color: var(--erp-text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }

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
            transition: all 0.3s ease;
        }

        .navbar-brand-logo {
            max-height: 26px;
            width: auto;
            object-fit: contain;
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
            border-radius: 9999px;
            transition: all 0.2s ease-in-out;
            margin: 0 2px;
            display: inline-flex;
            align-items: center;
        }

        .nav-link-custom:hover {
            color: #0f172a !important;
            background-color: #f1f5f9;
        }

        .nav-link-custom.active {
            color: #ffffff !important;
            background-color: #2563eb;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
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
            transition: all 0.15s ease;
        }

        .dropdown-menu-custom .dropdown-item:hover {
            background-color: #f1f5f9;
            color: #2563eb;
        }

        .dropdown-menu-custom .dropdown-item.active {
            background-color: #eff6ff !important;
            color: #2563eb !important;
        }

        /* Pulse Indicator */
        .pulse-dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-green 2s infinite;
        }

        @keyframes pulse-green {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
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

        /* --- GLOBAL CARD & CONTAINER STYLING --- */
        .card {
            border: 1px solid var(--erp-border);
            border-radius: 14px;
            background: var(--erp-surface);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid var(--erp-border);
            padding: 1rem 1.25rem;
            font-weight: 700;
        }

        /* Modern Form Controls */
        .form-control, .form-select {
            border-color: #cbd5e1;
            padding: 0.6rem 0.85rem;
            font-size: 0.875rem;
            border-radius: 8px;
            color: #0f172a;
            transition: all 0.15s ease-in-out;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            outline: none;
        }

        .smooth-dropdown + .ts-wrapper {
            width: 100%;
            background-image: none !important;
        }

        .pagination-dropdown + .ts-wrapper {
            width: 76px;
            flex: 0 0 76px;
        }

        .item-type-select {
            width: 100%;
            min-width: 120px;
            height: 34px;
            min-height: 34px;
            box-sizing: border-box;
            padding-right: 2.25rem;
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
            background-position: right 0.75rem center;
            white-space: nowrap;
        }

        .ts-wrapper.single .ts-control {
            min-height: 42px;
            display: flex;
            align-items: center;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            padding: 0.55rem 2.25rem 0.55rem 0.85rem;
            color: #0f172a;
            background: #fff;
            background-image: none !important;
            box-shadow: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .ts-wrapper.single .ts-control > .item {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ts-wrapper.single.focus .ts-control,
        .ts-wrapper.single.dropdown-active .ts-control {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .ts-wrapper.single .ts-control input {
            color: #0f172a;
            font: inherit;
        }

        .ts-wrapper.single .ts-control input::placeholder {
            color: #94a3b8;
        }

        select.is-invalid + .ts-wrapper .ts-control {
            border-color: #dc3545;
        }

        select.smooth-dropdown.form-select-sm + .ts-wrapper.single .ts-control {
            min-height: 34px;
            padding: 0.3rem 2rem 0.3rem 0.65rem;
            font-size: 0.8125rem;
        }

        .ts-wrapper.single .ts-control::after {
            content: "";
            position: absolute;
            top: 50%;
            right: 0.85rem;
            width: 0.45rem;
            height: 0.45rem;
            border-top: 0;
            border-left: 0;
            border-right: 1.5px solid #64748b;
            border-bottom: 1.5px solid #64748b;
            transform: translateY(-70%) rotate(45deg);
            transition: transform 0.15s ease;
        }

        .ts-wrapper.single.dropdown-active .ts-control::after {
            transform: translateY(-25%) rotate(225deg);
        }

        .ts-dropdown {
            z-index: 1070;
            overflow: hidden;
            margin-top: 6px;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.14);
            animation: smooth-dropdown-in 0.14s ease-out;
        }

        .ts-dropdown .dropdown-input-wrap {
            padding: 9px;
            border-bottom: 1px solid #eef2f7;
            background: #fff;
        }

        .ts-dropdown .dropdown-input {
            min-height: 36px;
            border: 1px solid #dbe4f0;
            border-radius: 8px;
            padding: 7px 10px;
            outline: none;
            box-shadow: none;
        }

        .ts-dropdown .dropdown-input:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .ts-dropdown-content {
            max-height: min(280px, 45vh);
            padding: 5px;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        .ts-dropdown .option,
        .ts-dropdown .no-results {
            margin: 2px 0;
            border-radius: 8px;
            padding: 8px 10px;
            color: #334155;
            font-size: 0.84rem;
            line-height: 1.4;
        }

        .ts-dropdown .active {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .ts-dropdown .selected {
            color: #1d4ed8;
            font-weight: 600;
        }

        .category-filter-dropdown-menu .option.disabled {
            display: none;
        }

        .ts-dropdown .customer-option {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .ts-dropdown .customer-option-name {
            overflow: hidden;
            font-size: 0.84rem;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ts-dropdown .customer-option-meta {
            overflow: hidden;
            color: #64748b;
            font-size: 0.72rem;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @keyframes smooth-dropdown-in {
            from {
                opacity: 0;
                transform: translateY(-3px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.5rem 1.15rem;
            font-size: 0.875rem;
            transition: all 0.15s ease-in-out;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .btn-primary {
            background-color: var(--erp-primary);
            border-color: var(--erp-primary);
        }

        .btn-primary:hover {
            background-color: var(--erp-primary-hover);
            border-color: var(--erp-primary-hover);
        }

        /* Modern Tables */
        .table {
            font-size: 0.85rem;
            color: #1e293b;
        }

        .table thead th {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.725rem;
            letter-spacing: 0.6px;
            color: #64748b;
            background-color: #f8fafc;
            border-bottom: 1px solid var(--erp-border);
            padding: 0.75rem 1rem;
        }

        .table tbody td {
            padding: 0.85rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Sticky Footer */
        footer {
            margin-top: auto;
            background-color: #ffffff;
            border-top: 1px solid var(--erp-border);
            color: #64748b;
            font-size: 0.8rem;
        }

        /* Minimalist Scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
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
                        <img src="{{ asset('images/navbar-user.png') }}" alt="User" class="navbar-brand-logo" style="width: 32px; height: 32px;">
                    @endif
                    <span class="fw-semibold text-dark fs-6 ms-3">
                        Hello {{ Auth::user()->username ?? 'User' }} !
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
                                        <i class="fa-solid fa-chart-pie me-2"></i> Dashboard
                                    </a>
                                </li>
                            @endif

                            @if(auth()->user()->hasAccess('invoices') || auth()->user()->hasAccess('customers') || auth()->user()->hasAccess('projects'))
                                    <li class="nav-item dropdown">
                                        <a class="nav-link nav-link-custom dropdown-toggle {{ request()->routeIs('documents.*') || request()->routeIs('purchase-orders.*') || request()->routeIs('customers.*') || request()->routeIs('projects.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-file-invoice-dollar me-2"></i> Invoicing & PO
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
                                                        <i class="fa-solid fa-users me-2 text-success"></i> Master Customer
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


                            @if(auth()->user()->hasAccess('user_management') || auth()->user()->hasAccess('activity_logs'))
                                <li class="nav-item dropdown">
                                    <a class="nav-link nav-link-custom dropdown-toggle {{ request()->routeIs('management.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-user-gear me-2"></i> User Management
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-custom border-0 shadow-lg" style="min-width: 220px;">
                                        @if(auth()->user()->hasAccess('user_management'))
                                            <li>
                                                <a class="dropdown-item py-2 {{ request()->routeIs('management.users.*') ? 'active' : '' }}" href="{{ route('management.users.index') }}">
                                                    <i class="fa-solid fa-users-gear me-2 text-primary"></i> Kelola User
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 {{ request()->routeIs('management.roles.*') ? 'active' : '' }}" href="{{ route('management.roles.index') }}">
                                                    <i class="fa-solid fa-shield-halved me-2 text-warning"></i> Kelola Role & Hak Akses
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
                                    style="width: 36px; height: 36px; transition: all 0.2s ease;" 
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
                                            style="width: 36px; height: 36px; transition: all 0.2s ease;" 
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="bottom" 
                                            title="Keluar / Logout">
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
    <main class="container-fluid px-lg-5 py-4">
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
    @stack('scripts')
</body>
</html>
