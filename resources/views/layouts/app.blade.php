<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CV Screening • NLP + Random Forest')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <script>
        // Apply saved theme ASAP to avoid flash
        (function(){
            try {
                const t = localStorage.getItem('theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
        <style type="text/tailwindcss">
            @custom-variant dark (&:where(.dark, .dark *));
        </style>
    @endif

    <style>
        .gradient-bg { background: linear-gradient(120deg, #0ea5e9 0%, #7c3aed 50%, #0ea5e9 100%); background-size: 200% 200%; animation: gradientShift 12s ease infinite; }
        @keyframes gradientShift { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        .glass { background: rgba(255,255,255,0.65); backdrop-filter: blur(8px); }
        .tooltip { position: relative; cursor: help; }
        .tooltip[data-tip]:hover::after {
            content: attr(data-tip);
            position: absolute; left: 50%; transform: translateX(-50%);
            bottom: 125%; background: #0f172a; color: #fff;
            padding: 6px 10px; font-size: 12px; border-radius: 8px;
            white-space: nowrap; z-index: 50; box-shadow: 0 8px 20px rgba(0,0,0,.15);
        }
        .tooltip[data-tip]:hover::before {
            content: '';
            position: absolute; left: 50%; transform: translateX(-50%);
            bottom: 115%; border: 6px solid transparent; border-top-color: #0f172a;
        }
        @keyframes scaleIn { from { opacity: 0; transform: translateY(8px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
    </style>
    @stack('head')
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100 text-slate-800 dark:from-slate-900 dark:to-slate-950 dark:text-slate-100 antialiased">
    <header class="sticky top-0 z-40 shadow-sm">
        <div class="gradient-bg text-white">
            <div class="max-w-7xl mx-auto px-4 py-3">
                <div class="flex items-center justify-between">
                    <a href="{{ route('candidates.index') }}" class="inline-flex items-center gap-2 font-semibold text-base md:text-lg tracking-tight">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/20">
                            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5"><path d="M4 7h16M4 12h12M4 17h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <span>CV Screening</span>
                    </a>
                    <button id="mobileMenuBtn" class="md:hidden inline-flex items-center justify-center h-9 w-9 rounded-md bg-white/10 hover:bg-white/15 ring-1 ring-white/20" aria-label="Toggle menu">
                        <svg id="mobileMenuIcon" viewBox="0 0 24 24" fill="none" class="h-5 w-5"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                    <nav class="hidden md:flex items-center gap-1 text-sm">
                        <a href="{{ route('candidates.index') }}" class="px-3 py-1.5 rounded-md hover:bg-white/10 ring-1 ring-white/0 hover:ring-white/10 transition">Dashboard</a>
                        <a href="{{ route('candidates.create') }}" class="px-3 py-1.5 rounded-md bg-white/10 hover:bg-white/15 ring-1 ring-white/20 transition">Upload CV</a>
                        <button type="button" onclick="openModal('modal-help')" class="px-3 py-1.5 rounded-md hover:bg-white/10 ring-1 ring-white/0 hover:ring-white/10 transition">Bantuan</button>
                        <button id="themeToggle" data-theme-toggle type="button" class="ml-1 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md bg-white/10 hover:bg-white/15 ring-1 ring-white/20" title="Toggle theme">
                            <svg id="themeIcon" viewBox="0 0 24 24" fill="none" class="w-4 h-4"><path d="M12 4v2m0 12v2m8-8h-2M6 12H4m12.364 6.364l-1.414-1.414M7.05 7.05 5.636 5.636m12.728 0-1.414 1.414M7.05 16.95l-1.414 1.414" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
                        </button>
                    </nav>
                </div>
                <nav id="mobileMenu" class="md:hidden hidden mt-3 space-y-1">
                    <a href="{{ route('candidates.index') }}" class="block rounded-lg px-3 py-2 bg-white/10 ring-1 ring-white/10">Dashboard</a>
                    <a href="{{ route('candidates.create') }}" class="block rounded-lg px-3 py-2 bg-white/10 ring-1 ring-white/10">Upload CV</a>
                    <button type="button" onclick="openModal('modal-help')" class="w-full text-left rounded-lg px-3 py-2 bg-white/10 ring-1 ring-white/10">Bantuan</button>
                    <button type="button" data-theme-toggle class="w-full text-left rounded-lg px-3 py-2 bg-white/10 ring-1 ring-white/10">Toggle Tema</button>
                </nav>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        @if(session('success'))
            <div class="mb-4 rounded-lg ring-1 ring-emerald-200/70 bg-emerald-50 text-emerald-800 px-4 py-3 flex items-start gap-3" data-animate>
                <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-white text-xs">✓</span>
                <div>{{ session('success') }}</div>
            </div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-lg ring-1 ring-rose-200/70 bg-rose-50 text-rose-800 px-4 py-3" data-animate>
                <ul class="list-disc list-inside text-sm space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer class="border-t bg-white/80 backdrop-blur dark:bg-slate-900/70 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-4 py-6 text-xs text-slate-500 flex items-center justify-center">
            <span>&copy; {{ date('Y') }} CV Screening. All rights reserved.</span>
        </div>
    </footer>

    <!-- Help modal -->
    <div id="modal-help" class="fixed inset-0 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeModal('modal-help')"></div>
        <div class="relative max-w-lg mx-auto mt-24 bg-white dark:bg-slate-900 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 shadow-xl p-6 animate-[scaleIn_.25s_ease]" role="dialog" aria-modal="true" data-animate>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Tentang Aplikasi</h3>
                <button class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" onclick="closeModal('modal-help')" aria-label="Close">✕</button>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300">Aplikasi ini membantu menilai CV secara otomatis dengan NLP dan Random Forest. Gunakan tombol Upload untuk menambah kandidat, dan buka detail untuk melihat rekomendasi.</p>
            <ul class="list-disc list-inside text-sm text-slate-700 dark:text-slate-200 space-y-1 mt-3">
                <li>Tip: Tekan Esc untuk menutup modal.</li>
                <li>Tip: Gunakan kolom pencarian untuk memfilter kandidat.</li>
            </ul>
        </div>
    </div>

    @if (!(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))))
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    @endif
    @stack('scripts')
    <script>
        // Page micro-animations
        document.querySelectorAll('[data-animate]')?.forEach(el => {
            el.classList.add('transition-all','duration-300','ease-out');
            el.style.transform = 'translateY(6px)';
            el.style.opacity = '0';
            requestAnimationFrame(() => {
                el.style.transform = 'translateY(0)';
                el.style.opacity = '1';
            });
        });

        // Mobile menu
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        mobileBtn?.addEventListener('click', () => {
            mobileMenu?.classList.toggle('hidden');
        });

        // Modal helpers with body scroll lock
        function toggleModal(id, show) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.toggle('hidden', !show);
            document.body.style.overflow = show ? 'hidden' : '';
        }
        window.openModal = id => toggleModal(id, true);
        window.closeModal = id => toggleModal(id, false);

        // Close modals on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('[id^="modal-"]').forEach(el => {
                    if (!el.classList.contains('hidden')) closeModal(el.id);
                });
            }
        });

        // Theme toggle with persistence
        const root = document.documentElement;
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme === 'dark' || (!storedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            root.classList.add('dark');
        }
        document.querySelectorAll('[data-theme-toggle], #themeToggle').forEach(btn => {
            btn.addEventListener('click', () => {
                root.classList.toggle('dark');
                localStorage.setItem('theme', root.classList.contains('dark') ? 'dark' : 'light');
            });
        });
    </script>
</body>
</html>


