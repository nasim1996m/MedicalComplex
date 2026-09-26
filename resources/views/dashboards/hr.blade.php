@extends('layouts.app')

@section('content')
<div class="space-y-6 text-right">
    <div class="bg-gradient-to-r from-[#1e1b4b] to-[#0f172a] text-white p-6 rounded-2xl shadow-xl flex justify-between items-center">
        <div>
            <h2 class="text-lg font-bold">إدارة الموارد البشرية والحرس والأمن</h2>
            <p class="text-xs text-indigo-200">جهاز البصمة الإلكتروني وجدول الحرس واللزام والوجبات الأمنية</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 text-xs">
        <div class="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-3">
            <h3 class="font-bold text-slate-900 text-sm border-b pb-2">سجل قراءات البصمة اليومية</h3>
            @foreach($attendances as $att)
            <div class="bg-emerald-50 border border-emerald-200 p-3 rounded-xl space-y-1">
                <p class="font-bold text-emerald-950">{{ $att->employee_name }} ({{ $att->fingerprint_id }})</p>
                <p class="text-emerald-700 text-[11px]">{{ $att->job_title }}</p>
                <span class="bg-emerald-600 text-white font-bold text-[10px] px-2 py-0.5 rounded">دخول: {{ $att->check_in }}</span>
            </div>
            @endforeach
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
            <h3 class="font-bold text-slate-900 text-sm border-b pb-2">جدول الحرس واللزام والوجبات الليلية والنهارية</h3>
            <table class="w-full text-right">
                <thead class="bg-[#0f172a] text-white">
                    <tr>
                        <th class="p-3">اسم الموظف / الحارس</th>
                        <th class="p-3">العنوان الوظيفي</th>
                        <th class="p-3">الوجبة (الخفر)</th>
                        <th class="p-3">الموقع</th>
                        <th class="p-3">التاريخ</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($rosters as $r)
                    <tr>
                        <td class="p-3 font-bold">{{ $r->employee_name }}</td>
                        <td class="p-3 text-slate-600">{{ $r->job_title }}</td>
                        <td class="p-3"><span class="bg-indigo-100 text-indigo-900 font-bold px-2 py-0.5 rounded">{{ $r->shift === 'night' ? 'خفر ليلي' : 'صباحي/مسائي' }}</span></td>
                        <td class="p-3 text-slate-700">{{ $r->location }}</td>
                        <td class="p-3 font-mono text-[11px]">{{ $r->date }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
