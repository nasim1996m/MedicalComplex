<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المجمع الطبي التخصصي - نظام الداشبوردات المتكامل</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8fafc;
        }
    </style>
</head>
<body class="min-h-screen bg-[#f8fafc] text-slate-900 flex flex-col">
    <!-- Navbar Header -->
    <header class="sticky top-0 z-40 w-full bg-[#1e1b4b] text-white shadow-lg border-b border-indigo-900/50" style="background-color: #1e1b4b;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="bg-indigo-600/30 p-2 rounded-xl border border-indigo-400/20">
                    <svg class="w-7 h-7 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div>
                    <h1 class="font-bold text-lg text-white leading-tight">المجمع الطبي التخصصي</h1>
                    <p class="text-xs text-indigo-300">نظام لوحات التحكم الموحد والدخول عبر Google</p>
                </div>
            </div>

            @auth
            @php $u = auth()->user(); @endphp
            <div class="flex items-center gap-4">
                @if($u->isAdmin())
                <div class="relative group">
                    <button class="bg-indigo-900 text-sky-200 text-xs px-3.5 py-2 rounded-xl border border-indigo-500/30 flex items-center gap-1.5 font-bold">
                        <span>التحويل بين الداشبوردات (الادمن)</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="hidden group-hover:block absolute left-0 mt-1 w-52 bg-[#0f172a] text-white rounded-2xl shadow-2xl p-2 z-50 border border-indigo-900">
                        <a href="{{ route('web.switch', 'admin') }}" class="block px-3 py-2 rounded-xl text-xs hover:bg-indigo-800">1. لوحة الأدمن</a>
                        <a href="{{ route('web.switch', 'doctor') }}" class="block px-3 py-2 rounded-xl text-xs hover:bg-indigo-800">2. لوحة الطبيب</a>
                        <a href="{{ route('web.switch', 'pharmacy') }}" class="block px-3 py-2 rounded-xl text-xs hover:bg-indigo-800">3. لوحة الصيدلية</a>
                        <a href="{{ route('web.switch', 'lab_tech') }}" class="block px-3 py-2 rounded-xl text-xs hover:bg-indigo-800">4. لوحة المختبر والأشعة</a>
                        <a href="{{ route('web.switch', 'storekeeper') }}" class="block px-3 py-2 rounded-xl text-xs hover:bg-indigo-800">5. لوحة المخزن</a>
                        <a href="{{ route('web.switch', 'accountant') }}" class="block px-3 py-2 rounded-xl text-xs hover:bg-indigo-800">6. لوحة المحاسب</a>
                        <a href="{{ route('web.switch', 'hr') }}" class="block px-3 py-2 rounded-xl text-xs hover:bg-indigo-800">7. لوحة الموارد البشرية</a>
                    </div>
                </div>
                @endif

                <div class="flex items-center gap-2">
                    <img src="{{ $u->avatar ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde' }}" referrerpolicy="no-referrer" class="w-9 h-9 rounded-full ring-2 ring-sky-400" />
                    <div class="hidden md:block text-right text-white">
                        <p class="text-xs font-bold">{{ $u->name }}</p>
                        <p class="text-[10px] text-indigo-300">{{ $u->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('web.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-xs text-rose-300 hover:text-rose-100 bg-rose-950/60 px-3 py-1.5 rounded-xl border border-rose-800/40 mr-2 font-bold">خروج</button>
                    </form>
                </div>
            </div>
            @endauth
        </div>
    </header>

    <!-- Main Content Body -->
    <div class="flex flex-1">
        @if(auth()->check() && auth()->user()->isAdmin())
        <aside class="w-64 bg-[#0f172a] text-white p-4 hidden md:flex flex-col justify-between border-l border-indigo-900/40">
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-indigo-400 px-2">لوحات التحكم بالنظام</h3>
                <nav class="space-y-1.5 text-xs">
                    <a href="{{ route('web.switch', 'admin') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl font-bold bg-indigo-900/40 text-sky-200 border border-indigo-700/30">
                        <span>🛡️ لوحة Admin الأدمن</span>
                    </a>
                    <a href="{{ route('web.switch', 'doctor') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl font-bold hover:bg-indigo-950 text-indigo-200">
                        <span>🩺 لوحة الطبيب والعيادة</span>
                    </a>
                    <a href="{{ route('web.switch', 'pharmacy') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl font-bold hover:bg-indigo-950 text-indigo-200">
                        <span>💊 لوحة الصيدلية والدواء</span>
                    </a>
                    <a href="{{ route('web.switch', 'lab_tech') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl font-bold hover:bg-indigo-950 text-indigo-200">
                        <span>🔬 لوحة المختبر والأشعة</span>
                    </a>
                    <a href="{{ route('web.switch', 'storekeeper') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl font-bold hover:bg-indigo-950 text-indigo-200">
                        <span>📦 لوحة المخزن الرئيسي</span>
                    </a>
                    <a href="{{ route('web.switch', 'accountant') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl font-bold hover:bg-indigo-950 text-indigo-200">
                        <span>🧮 لوحة المحاسب والمالية</span>
                    </a>
                    <a href="{{ route('web.switch', 'hr') }}" class="flex items-center gap-2 px-3 py-2.5 rounded-xl font-bold hover:bg-indigo-950 text-indigo-200">
                        <span>👥 لوحة HR والبصمة والحرس</span>
                    </a>
                </nav>
            </div>
            <div class="bg-indigo-950/80 p-3 rounded-xl border border-indigo-800/40 text-xs text-indigo-300">
                <p class="font-bold text-emerald-400">⚡ النظام متصل وشغال</p>
                <p class="text-[10px] text-slate-400 mt-1">نظام لوحات التحكم الموحدة</p>
            </div>
        </aside>
        @endif

        <main class="flex-1 p-6 max-w-7xl mx-auto w-full">
            @yield('content')
        </main>
    </div>
</body>
</html>
