'use client';

import React, { useState } from 'react';
import { mockData } from '@/services/api';
import { Prescription, Medicine } from '@/types/medical';
import {
  Pill,
  CheckCircle2,
  AlertTriangle,
  Boxes,
  Send,
  Clock,
  DollarSign,
  Calendar,
} from 'lucide-react';

export default function PharmacyDashboard() {
  const [prescriptions, setPrescriptions] = useState<Prescription[]>(mockData.prescriptions);
  const [medicines, setMedicines] = useState<Medicine[]>(mockData.medicines);
  const [notificationMsg, setNotificationMsg] = useState<string | null>(null);

  const showNotification = (msg: string) => {
    setNotificationMsg(msg);
    setTimeout(() => setNotificationMsg(null), 3500);
  };

  const handleDispense = (id: number) => {
    setPrescriptions((prev) =>
      prev.map((p) => (p.id === id ? { ...p, status: 'dispensed' } : p))
    );

    // Deduct quantity in medicines & add income voucher to mockData
    setMedicines((prev) =>
      prev.map((m) => {
        if (m.id === 2) {
          const newQty = Math.max(0, m.quantity - 1);
          return { ...m, quantity: newQty };
        }
        return m;
      })
    );

    showNotification('تم صرف الوصفة الطبية وتحديث الكمية بالمخزن وتسجيل الإيراد للمحاسبة!');
  };

  const handleAlertAdminStock = (medName: string, qty: number) => {
    mockData.notifications.unshift({
      id: Date.now(),
      target_role: 'admin',
      title: 'تنبيه نقص دواء عاجل (<= 15%)',
      message: `انخفض دواء (${medName}) إلى الكمية الحرجية: ${qty} قطعة. يرجى التجهيز من المخزن الرئيسي.`,
      type: 'stock_alert',
      is_read: false,
      created_at: 'الآن',
    });
    showNotification(`تم إرسال إشعار وتنبيه عاجل للأدمن لإعادة تزويد دواء (${medName})!`);
  };

  return (
    <div className="space-y-6">
      {notificationMsg && (
        <div className="bg-emerald-900/90 text-emerald-100 p-4 rounded-2xl border border-emerald-500/40 shadow-xl flex items-center gap-3 animate-in fade-in">
          <CheckCircle2 className="w-5 h-5 text-emerald-400" />
          <p className="text-xs font-bold">{notificationMsg}</p>
        </div>
      )}

      {/* Pharmacy Banner Header */}
      <div className="bg-gradient-to-r from-[#1e1b4b] via-[#312e81] to-[#0f172a] text-white p-6 rounded-2xl shadow-xl flex items-center justify-between border border-indigo-900/60">
        <div className="flex items-center gap-4">
          <div className="p-3 bg-emerald-500/20 rounded-2xl border border-emerald-400/30">
            <Pill className="w-8 h-8 text-emerald-400" />
          </div>
          <div>
            <h2 className="text-lg font-bold text-white">صيدلية المجمع الطبي التخصصية</h2>
            <p className="text-xs text-indigo-200">استقبال وصفات الطبيب، صرف الدواء، متابعة التواريخ والأسعار وتنبيهات النقص</p>
          </div>
        </div>

        <div className="bg-indigo-950/80 px-4 py-2 rounded-xl border border-indigo-800/40 text-xs text-indigo-200">
          <span>الوصفات المعلقة: </span>
          <span className="font-bold text-amber-400">
            {prescriptions.filter((p) => p.status === 'pending').length} وصفة
          </span>
        </div>
      </div>

      {/* Incoming Prescriptions Section */}
      <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-6">
        <h3 className="font-bold text-slate-900 text-sm mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
          <Clock className="w-4 h-4 text-amber-500" />
          <span>الوصفات الطبية الواردة من العيادات</span>
        </h3>

        <div className="space-y-4">
          {prescriptions.map((rx) => (
            <div key={rx.id} className="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
              <div className="space-y-1">
                <div className="flex items-center gap-3">
                  <h4 className="font-bold text-slate-900 text-sm">{rx.patient_name}</h4>
                  <span className="text-xs bg-indigo-100 text-indigo-800 font-bold px-2.5 py-0.5 rounded-full">
                    {rx.patient_code}
                  </span>
                  <span className="text-[11px] text-slate-500">من الطبيب: {rx.doctor_name}</span>
                </div>
                <div className="pt-2">
                  <p className="text-xs font-bold text-slate-700">الأدوبة المطلوبة بالوصفة:</p>
                  <ul className="mt-1 space-y-1">
                    {rx.items.map((item, idx) => (
                      <li key={idx} className="text-xs text-indigo-950 bg-white px-3 py-1.5 rounded-lg border border-slate-200 inline-block ml-2 mb-1">
                        <span className="font-bold">{item.medicine_name}</span> - {item.dosage} ({item.duration})
                      </li>
                    ))}
                  </ul>
                </div>
              </div>

              <div className="shrink-0 flex items-center gap-3">
                {rx.status === 'pending' ? (
                  <button
                    onClick={() => handleDispense(rx.id)}
                    className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md transition flex items-center gap-1.5"
                  >
                    <CheckCircle2 className="w-4 h-4" />
                    <span>تجهيز وصرف الدواء للمريض</span>
                  </button>
                ) : (
                  <span className="bg-slate-200 text-slate-700 text-xs font-bold px-3 py-1.5 rounded-xl">
                    تم الصرف والمكافأة
                  </span>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Medicines Inventory & Low Stock Alerts */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 bg-white rounded-2xl shadow-md border border-slate-200 p-6">
          <h3 className="font-bold text-slate-900 text-sm mb-4 flex items-center gap-2">
            <Boxes className="w-4 h-4 text-indigo-600" />
            <span>جدول مخزون الأدوية وتواريخ الصلاحية والأسعار</span>
          </h3>

          <div className="overflow-x-auto">
            <table className="w-full text-right text-xs">
              <thead className="bg-[#0f172a] text-white rounded-xl">
                <tr>
                  <th className="p-3">اسم الدواء</th>
                  <th className="p-3">الفئة</th>
                  <th className="p-3">السعر الفردي</th>
                  <th className="p-3">الكمية المتوفرة</th>
                  <th className="p-3">تاريخ الانتهاء</th>
                  <th className="p-3">رقم الشحنة (Batch)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {medicines.map((med) => (
                  <tr key={med.id} className="hover:bg-slate-50 transition">
                    <td className="p-3 font-bold text-slate-800">{med.name}</td>
                    <td className="p-3 text-slate-600">{med.category}</td>
                    <td className="p-3 font-bold text-emerald-700">{med.unit_price.toLocaleString()} د.ع</td>
                    <td className="p-3">
                      <span className={`font-bold px-2.5 py-1 rounded-lg ${
                        med.quantity <= med.min_threshold
                          ? 'bg-rose-100 text-rose-800 animate-pulse'
                          : 'bg-emerald-100 text-emerald-800'
                      }`}>
                        {med.quantity} قطعة
                      </span>
                    </td>
                    <td className="p-3 text-slate-600 font-mono">{med.expiry_date}</td>
                    <td className="p-3 text-slate-500 font-mono text-[11px]">{med.batch_number}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Low Stock Alerts & Send Notification to Admin */}
        <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
          <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
            <AlertTriangle className="w-4 h-4 text-rose-600" />
            <span>تنبيهات كميات النقص (أقل من 15%) والانتهاء</span>
          </h3>

          <div className="space-y-3">
            {medicines
              .filter((m) => m.quantity <= m.min_threshold)
              .map((med) => (
                <div key={med.id} className="bg-rose-50 border border-rose-200 p-3.5 rounded-xl space-y-2">
                  <div className="flex justify-between items-center text-xs">
                    <span className="font-bold text-rose-950">{med.name}</span>
                    <span className="bg-rose-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                      متبقي: {med.quantity}
                    </span>
                  </div>
                  <p className="text-[11px] text-rose-800">
                    تنبيه: وصل المخزون لأقل من 15%. يرجى إرسال طلب تزويد إلى الأدمن لإكمال النقص من المخزن الرئيسي.
                  </p>
                  <button
                    onClick={() => handleAlertAdminStock(med.name, med.quantity)}
                    className="w-full bg-rose-700 hover:bg-rose-800 text-white font-bold py-2 rounded-lg text-xs transition flex items-center justify-center gap-1.5 shadow-sm"
                  >
                    <Send className="w-3.5 h-3.5" />
                    <span>إرسال تنبيه فوري للأدمن للمخزن</span>
                  </button>
                </div>
              ))}
          </div>
        </div>
      </div>
    </div>
  );
}
