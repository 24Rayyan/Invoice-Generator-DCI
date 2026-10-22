<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mini ERP DCI</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js untuk Micro-interactions -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        /* Pattern Grid Background khas Aplikasi Enterprise */
        .bg-grid-pattern {
            background-size: 30px 30px;
            background-image: 
                linear-gradient(to right, rgba(226, 232, 240, 0.6) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(226, 232, 240, 0.6) 1px, transparent 1px);
        }
    </style>
</head>
<body class="h-full bg-slate-50 bg-grid-pattern antialiased text-slate-800 flex items-center justify-center p-4">

    <!-- Card Wrapper -->
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-200/80 p-8 sm:p-10 transition-all">
        
    <!-- Header & Branding -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center mb-3 min-h-[48px]">
            @if(isset($globalSetting) && $globalSetting->company_logo && file_exists(public_path('storage/' . $globalSetting->company_logo)))
                <!-- Logo dari Upload User (Sesuaikan height agar proporsional seperti di Navbar) -->
                <img src="{{ asset('storage/' . $globalSetting->company_logo) }}" alt="Logo" class="h-10 sm:h-12 w-auto object-contain">
            @else
                <img src="{{ asset('images/company-growth.png') }}" alt="Ilustrasi perusahaan" class="h-16 w-16 object-contain">
            @endif
        </div>
        
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">
            {{ $globalSetting->company_name ?? 'Mini ERP' }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">Masuk ke akun Anda untuk mengakses sistem</p>
    </div>
        <!-- Alert Error Laravel -->
        @if ($errors->any())
            <div class="mb-6 p-3.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs sm:text-sm rounded-xl flex items-start gap-3">
                <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Form Section dengan Alpine State untuk Loading & Password Toggle -->
        <form action="{{ route('login') }}" method="POST" x-data="{ submitting: false, showPassword: false }" @submit="submitting = true">
            @csrf
            
            <!-- Input Username -->
            <div class="mb-5">
                <label for="username" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                    Username
                </label>
                <div class="relative rounded-xl shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        id="username"
                        name="username" 
                        value="{{ old('username') }}" 
                        autocomplete="off"
                        required 
                        autofocus 
                        placeholder="Masukkan username" 
                        class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent transition-all duration-150 placeholder:text-slate-400"
                    >
                </div>
            </div>

            <!-- Input Password -->
            <div class="mb-5">
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                    Password
                </label>
                <div class="relative rounded-xl shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input 
                        :type="showPassword ? 'text' : 'password'" 
                        id="password"
                        name="password" 
                        autocomplete="new-password"
                        required 
                        placeholder="••••••••" 
                        class="w-full pl-10 pr-10 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent transition-all duration-150 placeholder:text-slate-400"
                    >
                    <!-- Toggle Password Visibility -->
                    <button 
                        type="button" 
                        @click="showPassword = !showPassword" 
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                        tabindex="-1"
                    >
                        <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a8.962 8.962 0 012.122-.198c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between mb-6">
                <label class="flex items-center cursor-pointer select-none">
                    <input 
                        type="checkbox" 
                        name="remember" 
                        class="w-4 h-4 text-slate-900 border-slate-300 rounded focus:ring-slate-900 transition cursor-pointer"
                    >
                    <span class="ml-2.5 text-xs sm:text-sm text-slate-600 font-medium">Remember Me</span>
                </label>
            </div>

            <!-- Submit Button dengan Loading State -->
            <button 
                type="submit" 
                :disabled="submitting"
                class="w-full bg-slate-900 hover:bg-slate-800 active:bg-slate-950 text-white font-medium py-2.5 px-4 rounded-xl shadow-md shadow-slate-900/10 transition-all duration-150 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed"
            >
                <svg x-show="submitting" x-cloak class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="submitting ? 'Memproses...' : 'Sign In'">Sign In</span>
                <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            </button>
        </form>

        <!-- Footer Copyright Ringkas -->
        <div class="mt-8 pt-6 border-t border-slate-100 text-center">
            <p class="text-[11px] text-slate-400">
                &copy; {{ date('Y') }} Mini ERP
            </p>
        </div>

    </div>

</body>
</html>