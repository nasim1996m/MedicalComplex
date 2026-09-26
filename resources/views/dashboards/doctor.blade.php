@extends('layouts.app')

@section('content')
<div class="space-y-6 text-right">
    <!-- Header with Add Patient Button -->
    <div class="bg-gradient-to-r from-[#1e1b4b] to-[#0f172a] text-white p-6 rounded-2xl shadow-xl flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h2 class="text-lg font-bold">عيادة التشخيص الطبي التخصصية</h2>
            <p class="text-xs text-indigo-200">فحص المرضى، كتابة طلبات الفحوصات والوصفات يدوياً، والسجل المشترك</p>
        </div>

        <!-- Add Patient Button -->
        <button onclick="document.getElementById('addPatientModal').classList.remove('hidden')" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-extrabold px-4 py-3 rounded-xl shadow-lg transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>+ إضافة مريض جديد بالعيادة</span>
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Patient Queue -->
        <div class="bg-white rounded-2xl shadow-md border border-slate-200 p-5 space-y-3">
            <div class="flex items-center justify-between border-b pb-2">
                <h3 class="font-bold text-slate-900 text-sm">قائمة المرضى والمراجعين اليوم</h3>
                <span class="text-xs bg-indigo-100 text-indigo-800 font-bold px-2 py-0.5 rounded-full">{{ count($patients) }} مريض</span>
            </div>
            @foreach($patients as $patient)
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1 cursor-pointer">
                <div class="flex justify-between items-start">
                    <h4 class="font-bold text-xs text-slate-900">{{ $patient->name }}</h4>
                    <span class="text-[10px] bg-sky-100 text-sky-800 font-bold px-2 py-0.5 rounded">{{ $patient->patient_code }}</span>
                </div>
                <p class="text-[11px] text-slate-500">العمر: {{ $patient->age }} سنة | {{ $patient->gender }}</p>
                <p class="text-[10px] text-rose-700 font-bold">سابق: {{ $patient->medical_history }}</p>
            </div>
            @endforeach
        </div>

        <!-- Consultation & Manual Input Forms -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4 text-xs">
            <h3 class="font-bold text-slate-900 text-sm border-b pb-2">تشخيص حالة المريض وكتابة الطلبات والوصفات يدوياً</h3>
            
            <div>
                <label class="block font-bold text-slate-700 mb-1">التشخيص الطبي والتوصية:</label>
                <textarea rows="3" placeholder="أدخل التقييم والتشخيص الطبي التفصيلي..." class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-indigo-600 focus:outline-none"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Manual Lab & Scan Request Box -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2.5">
                    <h4 class="font-bold text-indigo-950 flex items-center gap-1.5">
                        <span>🧪 كتابة طلب الفحوصات يدوياً (دم / أشعة / إيكو / تخطيط)</span>
                    </h4>
                    <input
                        type="text"
                        placeholder="أدخل اسم الفحص المطلوب يدوياً (مثال: فحص دم شامل CBC، أشعة صدر X-Ray...)"
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white text-xs font-bold text-slate-900 focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                    />
                    <button type="button" onclick="alert('تم إرسال طلب الفحص المكتوب يدوياً إلى المختبر بنجاح!')" class="w-full bg-[#1e1b4b] hover:bg-[#312e81] text-white font-bold py-2.5 rounded-lg text-xs transition">
                        إرسال الطلب للمختبر والأشعة
                    </button>
                </div>

                <!-- Manual Prescription Box -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2.5">
                    <h4 class="font-bold text-emerald-950 flex items-center gap-1.5">
                        <span>💊 كتابة وصفة الدواء يدوياً للصيدلية</span>
                    </h4>
                    <input
                        type="text"
                        placeholder="أدخل اسم الدواء والعلاج يدوياً (مثال: Amoxicillin 500mg)..."
                        class="w-full border border-slate-300 rounded-lg p-2.5 bg-white text-xs font-bold text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:outline-none"
                    />
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" placeholder="الجرعة (مثال: كبسولة كل 8 ساعات)" class="border border-slate-300 rounded-lg p-2 bg-white text-xs" />
                        <input type="text" placeholder="المدة (مثال: 7 أيام)" class="border border-slate-300 rounded-lg p-2 bg-white text-xs" />
                    </div>
                    <button type="button" onclick="alert('تم إرسال الوصفة المكتوبة يدوياً للصيدلية بنجاح!')" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-bold py-2.5 rounded-lg text-xs transition">
                        إرسال الوصفة للصيدلية
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <div id="addPatientModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-indigo-900/40 text-right">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    <span>إضافة وتسجيل مريض جديد بالعيادة</span>
                </h3>
                <button onclick="document.getElementById('addPatientModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕ إغلاق</button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">اسم المريض الثلاثي:</label>
                    <input type="text" placeholder="أدخل اسم المريض الكامل..." class="w-full border border-slate-300 rounded-xl p-2.5 bg-white text-xs font-bold text-slate-900" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">العمر:</label>
                        <input type="number" placeholder="العمر بالسنين" class="w-full border border-slate-300 rounded-xl p-2.5 bg-white text-xs font-bold" />
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">الجندر:</label>
                        <select class="w-full border border-slate-300 rounded-xl p-2.5 bg-white text-xs font-bold">
                            <option value="male">ذكر</option>
                            <option value="female">أنثى</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">تاريخ المرض والأمراض المزمنة (إن وجد):</label>
                    <input type="text" placeholder="مثال: ضغط دم، سكري، حساسية..." class="w-full border border-slate-300 rounded-xl p-2.5 bg-white text-xs" />
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">التشخيص الحالي والتوصية الطبيّة:</label>
                    <textarea rows="3" placeholder="أدخل تشخيصك للحالة التوصيات..." class="w-full border border-slate-300 rounded-xl p-2.5 bg-white text-xs"></textarea>
                </div>

                <button type="button" onclick="alert('تم تسجيل المريض وتثبيت كوده وإضافته لقائمة المراجعين بنجاح!'); document.getElementById('addPatientModal').classList.add('hidden');" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl transition shadow-md flex items-center justify-center gap-2">
                    <span>تأكيد وحفظ المريض الجديد</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
