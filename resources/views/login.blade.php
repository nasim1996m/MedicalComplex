@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-[#0f172a] text-white p-8 rounded-3xl shadow-2xl border border-indigo-900 space-y-6 text-right">
        <div class="text-center space-y-2">
            <div class="inline-flex p-3 bg-indigo-600/30 rounded-2xl border border-indigo-400/30">
                <svg class="w-10 h-10 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <h2 class="text-2xl font-extrabold text-white">المجمع الطبي التخصصي</h2>
            <p class="text-xs text-indigo-300">نظام إدارة مجمع طبي متكامل واللوحات الموحدة</p>
        </div>

        <div class="bg-indigo-950/80 p-4 rounded-2xl border border-indigo-800 text-xs space-y-2 text-indigo-200">
            <p class="font-bold text-sky-300">🔒 قيد المصادقة بحساب Google:</p>
            <p className="leading-relaxed">لا يتم دخول المنظومة إلا بحساب Google معتمد. يرجى اختيار الحساب للمباشرة الفورية.</p>
        </div>

        <div className="space-y-3">
            <label class="block text-xs font-bold text-indigo-300">اختر حساب Google للتجربة والمباشرة:</label>
            <div class="space-y-2">
                @foreach($users as $u)
                <a href="{{ route('web.login_as', $u->id) }}" class="flex items-center justify-between p-3 rounded-xl bg-indigo-900/50 hover:bg-indigo-800 border border-indigo-700/40 transition">
                    <div class="flex items-center gap-2">
                        <img src="{{ $u->avatar ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde' }}" class="w-7 h-7 rounded-full" />
                        <div>
                            <p class="text-xs font-bold text-white">{{ $u->name }}</p>
                            <p class="text-[10px] text-indigo-300">{{ $u->email }}</p>
                        </div>
                    </div>
                    <span class="text-[10px] bg-sky-500/20 text-sky-300 font-bold px-2 py-0.5 rounded-md">{{ $u->role }}</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
