@extends('layouts.app')

@section('content')
<div class="min-h-[75vh] flex items-center justify-center p-4">
    <div class="max-w-lg w-full bg-white rounded-3xl p-8 shadow-2xl border border-slate-200 text-right space-y-6">
        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <div class="p-3 bg-amber-500/20 text-amber-600 rounded-2xl">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-900">تقديم طلب فتح حساب وداشبورد للأدمن</h2>
                <p class="text-xs text-slate-500">تم تسجيل الدخول بـ Google ({{ $user->email }}). اختر المهنة لإرسال الطلب.</p>
            </div>
        </div>

        @if($user->status === 'rejected')
        <div class="p-4 bg-rose-100 border border-rose-300 text-rose-800 rounded-2xl text-xs font-bold text-center">
            تم رفض طلبك السابق. يمكنك تقديم طلب جديد.
        </div>
        @endif

        @if($errors->any())
        <div class="p-4 bg-rose-100 border border-rose-300 text-rose-800 rounded-2xl text-xs font-bold">
            {{ $errors->first() }}
        </div>
        @endif

        @if(session('success'))
        <div class="p-4 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-2xl text-xs font-bold text-center">
            {{ session('success') }}
        </div>
        @endif

        <form action="{{ route('web.submit_role_request') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 mb-1">المهنة والداشبورد المطلوب:</label>
                <select name="requested_role" class="w-full border border-slate-300 rounded-xl p-3 bg-white font-bold text-slate-900">
                    <option value="doctor">طبيب (داشبورد التشخيص والتحاليل والوصفات)</option>
                    <option value="pharmacist">صيدلاني (داشبورد الوصفات وصرف الدواء وتنبيهات المخزون)</option>
                    <option value="lab_tech">فني مختبر وأشعة (داشبورد التحاليل ورفع التقارير)</option>
                    <option value="storekeeper">أمين مخزن (داشبورد المخزون وتجهيز الأدوية)</option>
                    <option value="accountant">محاسب (داشبورد القيود والخدمات والرواتب والميزانية)</option>
                    <option value="hr">مسؤول موارد بشرية (داشبورد البصمة وجدول الحرس والأمن)</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">التخصص والفرع الطبي:</label>
                <input type="text" name="requested_specialty" placeholder="مثال: أطباء أطفال، باطنية وغدد..." class="w-full border border-slate-300 rounded-xl p-3" />
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">ملاحظات للأدمن:</label>
                <textarea name="notes" rows="2" placeholder="ملاحظات إضافية..." class="w-full border border-slate-300 rounded-xl p-3"></textarea>
            </div>

            <button type="submit" class="w-full bg-[#1e1b4b] hover:bg-[#312e81] text-white font-bold py-3.5 rounded-xl shadow-xl transition text-xs">
                <span>إرسال الطلب وإبلاغ الأدمن فوراً</span>
            </button>
        </form>
    </div>
</div>
@endsection
