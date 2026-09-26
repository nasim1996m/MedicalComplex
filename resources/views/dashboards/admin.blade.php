@extends('layouts.app')

@section('content')
<div class="space-y-6 text-right">
    <!-- Top Stats Banner -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-[#1e1b4b] to-[#0f172a] text-white p-5 rounded-2xl shadow-lg border border-indigo-900/60">
            <p class="text-xs font-medium text-indigo-300">إجمالي المرضى المسجلين</p>
            <h3 class="text-2xl font-extrabold text-white mt-1">{{ $totalPatients }}</h3>
            <p class="text-[11px] text-indigo-300/80 mt-2">تغطية شاملة بالمجمع الطبي</p>
        </div>

        <div class="bg-gradient-to-br from-[#1e1b4b] to-[#0f172a] text-white p-5 rounded-2xl shadow-lg border border-indigo-900/60">
            <p class="text-xs font-medium text-indigo-300">طلبات الحسابات المعلقة</p>
            <h3 class="text-2xl font-extrabold text-amber-400 mt-1">{{ count($pendingRequests) }}</h3>
            <p class="text-[11px] text-indigo-300/80 mt-2">بانتظار موافقة وتفعيل الأدمن</p>
        </div>

        <div class="bg-gradient-to-br from-[#1e1b4b] to-[#0f172a] text-white p-5 rounded-2xl shadow-lg border border-indigo-900/60">
            <p class="text-xs font-medium text-indigo-300">إجمالي مقبوضات المجمع</p>
            <h3 class="text-2xl font-extrabold text-emerald-400 mt-1">{{ number_format($totalIncome) }} د.ع</h3>
            <p class="text-[11px] text-indigo-300/80 mt-2">إيرادات الكشوفات والصيدلية والمختبر</p>
        </div>

        <div class="bg-gradient-to-br from-[#1e1b4b] to-[#0f172a] text-white p-5 rounded-2xl shadow-lg border border-indigo-900/60">
            <p class="text-xs font-medium text-indigo-300">صافي الأرباح التشغيلية</p>
            <h3 class="text-2xl font-extrabold text-white mt-1">{{ number_format($totalIncome - $totalExpense) }} د.ع</h3>
            <p class="text-[11px] text-indigo-300/80 mt-2">بعد خصم المصاريف والخدمات</p>
        </div>
    </div>

    <!-- Pending Account Requests -->
    <div class="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
        <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-3">طلبات انضمام الموظفين الجدد (موافقة وتفعيل الأدمن)</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-[#0f172a] text-white rounded-xl">
                    <tr>
                        <th class="p-3">الموظف المتقدم</th>
                        <th class="p-3">البريد (Google)</th>
                        <th class="p-3">الداشبورد المطلوب</th>
                        <th class="p-3">التخصص</th>
                        <th class="p-3">الحالة</th>
                        <th class="p-3 text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($pendingRequests as $req)
                    <tr>
                        <td class="p-3 font-bold text-slate-800">{{ $req->user_name }}</td>
                        <td class="p-3 text-slate-600 font-mono">{{ $req->user_email }}</td>
                        <td class="p-3"><span class="bg-indigo-100 text-indigo-800 font-bold px-2 py-0.5 rounded">{{ $req->requested_role }}</span></td>
                        <td class="p-3 text-slate-700">{{ $req->requested_specialty ?? '-' }}</td>
                        <td class="p-3"><span class="bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded">قيد الانتظار</span></td>
                        <td class="p-3 text-center space-x-1 space-x-reverse">
                            <a href="{{ route('web.role_requests.approve', $req->id) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1 rounded-lg text-xs">موافقة وتفعيل</a>
                            <a href="{{ route('web.role_requests.reject', $req->id) }}" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-3 py-1 rounded-lg text-xs">رفض</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Doctors Directory -->
    <div class="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-3">
        <h3 class="font-bold text-slate-900 text-sm">كادر الأطباء المباشرين في المجمع الطبي</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($doctors as $doc)
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-slate-900 text-xs">{{ $doc->name }}</h4>
                    <p class="text-[11px] text-indigo-600">{{ $doc->specialty }}</p>
                </div>
                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full">نشط ومباشر</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
