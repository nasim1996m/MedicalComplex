@extends('layouts.app')

@section('content')
<div class="space-y-6 text-right">
    <div class="bg-gradient-to-r from-[#1e1b4b] to-[#0f172a] text-white p-6 rounded-2xl shadow-xl flex justify-between items-center">
        <div>
            <h2 class="text-lg font-bold">صيدلية المجمع الطبي التخصصية</h2>
            <p class="text-xs text-indigo-200">استقبال وصفات الطبيب، صرف الدواء، تواريخ الصلاحية، وتنبيهات النقص</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
        <h3 class="font-bold text-slate-900 text-sm border-b pb-2">الوصفات الطبية الواردة من العيادات</h3>
        @foreach($prescriptions as $p)
        <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4 text-xs">
            <div>
                <h4 class="font-bold text-slate-900">{{ $p->patient_name }} ({{ $p->patient_code }})</h4>
                <p class="text-slate-500">الطبيب المعالج: {{ $p->doctor_name }}</p>
                <div class="pt-2">
                    <p class="font-bold text-slate-700">الأدوية المطلوبة:</p>
                    <ul class="mt-1">
                        @foreach($p->items as $item)
                        <li class="bg-white p-1.5 rounded border inline-block ml-2 mb-1 font-bold">{{ $item->medicine_name }} - {{ $item->dosage }} ({{ $item->duration }})</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <a href="{{ route('web.index') }}" class="bg-emerald-600 text-white font-bold px-4 py-2 rounded-xl">صرف الدواء للمريض</a>
        </div>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
        <h3 class="font-bold text-slate-900 text-sm border-b pb-2">مخزون الأدوية وتواريخ الصلاحية والأسعار</h3>
        <table class="w-full text-right text-xs">
            <thead class="bg-[#0f172a] text-white">
                <tr>
                    <th class="p-3">اسم الدواء</th>
                    <th class="p-3">الفئة</th>
                    <th class="p-3">السعر</th>
                    <th class="p-3">الكمية المتوفرة</th>
                    <th class="p-3">تاريخ الانتهاء</th>
                    <th class="p-3">رقم الشحنة</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($medicines as $med)
                <tr>
                    <td class="p-3 font-bold">{{ $med->name }}</td>
                    <td class="p-3">{{ $med->category }}</td>
                    <td class="p-3 font-bold text-emerald-700">{{ number_format($med->unit_price) }} د.ع</td>
                    <td class="p-3"><span class="font-bold px-2 py-0.5 rounded {{ $med->quantity <= $med->min_threshold ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $med->quantity }} قطعة</span></td>
                    <td class="p-3 font-mono">{{ $med->expiry_date }}</td>
                    <td class="p-3 font-mono text-[11px]">{{ $med->batch_number }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
