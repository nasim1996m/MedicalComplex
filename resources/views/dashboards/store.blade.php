@extends('layouts.app')

@section('content')
<div class="space-y-6 text-right">
    <div class="bg-gradient-to-r from-[#1e1b4b] to-[#0f172a] text-white p-6 rounded-2xl shadow-xl flex justify-between items-center">
        <div>
            <h2 class="text-lg font-bold">المخزن الرئيسي للمجمع الطبي</h2>
            <p class="text-xs text-indigo-200">إدارة المخزون وتزويد الصيدلية والمختبر بالأدوية والمستلزمات</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 text-xs">
        <div class="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-3">
            <h3 class="font-bold text-slate-900 text-sm border-b pb-2">مخزون المستلزمات الطبية والمختبر</h3>
            @foreach($items as $item)
            <div class="bg-slate-50 p-3 rounded-xl border flex justify-between items-center">
                <div>
                    <h4 class="font-bold text-slate-900">{{ $item->name }}</h4>
                    <p class="text-slate-500">الوحدة: {{ $item->unit }}</p>
                </div>
                <span class="font-bold px-2.5 py-1 rounded bg-emerald-100 text-emerald-800">{{ $item->quantity }} {{ $item->unit }}</span>
            </div>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-3">
            <h3 class="font-bold text-slate-900 text-sm border-b pb-2">مخزون الأدوية الرئيسي بالمخزن</h3>
            @foreach($medicines as $med)
            <div class="bg-slate-50 p-3 rounded-xl border flex justify-between items-center">
                <div>
                    <h4 class="font-bold text-slate-900">{{ $med->name }}</h4>
                    <p class="text-slate-500">فئة: {{ $med->category }} | انتهاء: {{ $med->expiry_date }}</p>
                </div>
                <span class="font-bold px-2.5 py-1 rounded bg-emerald-100 text-emerald-800">{{ $med->quantity }} قطعة</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
