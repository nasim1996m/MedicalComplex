@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-[#0f172a] text-white p-8 rounded-3xl shadow-2xl border border-indigo-900 space-y-6 text-right">
        <div class="text-center space-y-2">
            <div class="inline-flex p-3 bg-indigo-600/30 rounded-2xl border border-indigo-400/30">
                <svg class="w-10 h-10 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <h2 class="text-2xl font-extrabold text-white">المجمع الطبي التخصصي</h2>
            <p class="text-xs text-indigo-300">تسجيل الدخول إلى نظام إدارة المجمع</p>
        </div>

        @if($errors->any())
        <div role="alert" class="p-3 bg-rose-950/70 border border-rose-700 text-rose-200 rounded-xl text-xs font-bold">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('web.login.submit') }}" class="space-y-3 text-xs">
            @csrf
            <label class="block font-bold text-indigo-300">
                البريد الإلكتروني
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required
                       class="mt-1 w-full rounded-xl bg-indigo-950/80 border border-indigo-700 p-3 text-white focus:outline-none focus:border-sky-400" />
            </label>
            <label class="block font-bold text-indigo-300">
                كلمة المرور
                <input type="password" name="password" autocomplete="current-password" required
                       class="mt-1 w-full rounded-xl bg-indigo-950/80 border border-indigo-700 p-3 text-white focus:outline-none focus:border-sky-400" />
            </label>
            <button type="submit" class="w-full bg-sky-600 hover:bg-sky-500 text-white font-bold py-3 rounded-xl">دخول</button>
        </form>

        @if($googleClientId)
        <div class="flex items-center gap-3 text-[11px] text-indigo-400">
            <span class="flex-1 h-px bg-indigo-900"></span> أو <span class="flex-1 h-px bg-indigo-900"></span>
        </div>
        {{-- Google returns a signed ID token to the callback, which posts it through a same-origin
             form (with the CSRF token); the server verifies the token before signing anyone in. --}}
        <form id="googleForm" method="POST" action="{{ route('web.login.google') }}" class="hidden">
            @csrf
            <input type="hidden" name="credential" id="googleCredential" />
        </form>
        <script>
            function onGoogleCredential(response) {
                document.getElementById('googleCredential').value = response.credential;
                document.getElementById('googleForm').submit();
            }
        </script>
        <script src="https://accounts.google.com/gsi/client" async></script>
        <div id="g_id_onload"
             data-client_id="{{ $googleClientId }}"
             data-callback="onGoogleCredential"
             data-auto_prompt="false"></div>
        <div class="flex justify-center">
            <div class="g_id_signin" data-type="standard" data-size="large" data-text="signin_with" data-locale="ar"></div>
        </div>
        <p class="text-[11px] text-indigo-400 text-center">الحساب الجديد يحتاج موافقة الأدمن قبل الوصول إلى أي لوحة.</p>
        @endif
    </div>
</div>
@endsection
