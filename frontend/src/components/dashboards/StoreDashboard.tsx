'use client';

import React, { useState } from 'react';
import { mockData } from '@/services/api';
import { InventoryItem, Medicine } from '@/types/medical';
import {
  Boxes,
  Plus,
  CheckCircle2,
  Clock,
  AlertTriangle,
  Send,
  Building2,
  RefreshCw,
} from 'lucide-react';

export default function StoreDashboard() {
  const [items, setItems] = useState<InventoryItem[]>(mockData.inventoryItems);
  const [medicines, setMedicines] = useState<Medicine[]>(mockData.medicines);
  const [selectedRestockItem, setSelectedRestockItem] = useState<{ id: number; name: string; type: 'inventory' | 'medicine' } | null>(null);
  const [addQty, setAddQty] = useState<number>(50);
  const [notificationMsg, setNotificationMsg] = useState<string | null>(null);

  const showNotification = (msg: string) => {
    setNotificationMsg(msg);
    setTimeout(() => setNotificationMsg(null), 3500);
  };

  const handleRestock = () => {
    if (!selectedRestockItem) return;

    if (selectedRestockItem.type === 'inventory') {
      setItems((prev) =>
        prev.map((i) => (i.id === selectedRestockItem.id ? { ...i, quantity: i.quantity + addQty } : i))
      );
    } else {
      setMedicines((prev) =>
        prev.map((m) => (m.id === selectedRestockItem.id ? { ...m, quantity: m.quantity + addQty } : m))
      );
    }

    setSelectedRestockItem(null);
    showNotification(`تم التزويد وإضافة ${addQty} قطعة إلى المخزون بنجاح!`);
  };

  return (
    <div className="space-y-6">
      {notificationMsg && (
        <div className="bg-emerald-900/90 text-emerald-100 p-4 rounded-2xl border border-emerald-500/40 shadow-xl flex items-center gap-3 animate-in fade-in">
          <CheckCircle2 className="w-5 h-5 text-emerald-400" />
          <p className="text-xs font-bold">{notificationMsg}</p>
        </div>
      )}

      {/* Store Banner Header */}
      <div className="bg-gradient-to-r from-[#1e1b4b] via-[#312e81] to-[#0f172a] text-white p-6 rounded-2xl shadow-xl flex items-center justify-between border border-indigo-900/60">
        <div className="flex items-center gap-4">
          <div className="p-3 bg-amber-500/20 rounded-2xl border border-amber-400/30">
            <Boxes className="w-8 h-8 text-amber-400" />
          </div>
          <div>
            <h2 className="text-lg font-bold text-white">المخزن الرئيسي للمجمع الطبي</h2>
            <p className="text-xs text-indigo-200">إدارة المخزون، تزويد الصيدلية والمختبر بالأدوية والمستلزمات، والتجهيز الدوري</p>
          </div>
        </div>

        <div className="bg-indigo-950/80 px-4 py-2 rounded-xl border border-indigo-800/40 text-xs text-indigo-200">
          <span>إجمالي الأصناف: </span>
          <span className="font-bold text-sky-400">{items.length + medicines.length} صنف</span>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Medical Inventory Supplies */}
        <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
          <h3 className="font-bold text-slate-900 text-sm flex items-center justify-between border-b border-slate-100 pb-3">
            <span className="flex items-center gap-2">
              <Boxes className="w-4 h-4 text-indigo-600" />
              <span>مخزون المستلزمات الطبية والمختبر</span>
            </span>
          </h3>

          <div className="space-y-3">
            {items.map((item) => (
              <div key={item.id} className="bg-slate-50 border border-slate-200 p-3.5 rounded-xl flex justify-between items-center text-xs">
                <div>
                  <h4 className="font-bold text-slate-900">{item.name}</h4>
                  <p className="text-[11px] text-slate-500">الوحدة: {item.unit}</p>
                </div>
                <div className="flex items-center gap-3">
                  <span className={`font-bold px-2.5 py-1 rounded-lg ${item.quantity <= item.min_threshold
                      ? 'bg-rose-100 text-rose-800 animate-pulse'
                      : 'bg-emerald-100 text-emerald-800'
                    }`}>
                    {item.quantity} {item.unit}
                  </span>
                  <button
                    onClick={() => setSelectedRestockItem({ id: item.id, name: item.name, type: 'inventory' })}
                    className="bg-indigo-900 hover:bg-indigo-800 text-white font-bold p-2 rounded-lg transition"
                    title="تزويد وإعادة تعبئة"
                  >
                    <Plus className="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Medicines Stock Main Warehouse */}
        <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-6 space-y-4">
          <h3 className="font-bold text-slate-900 text-sm flex items-center justify-between border-b border-slate-100 pb-3">
            <span className="flex items-center gap-2">
              <Boxes className="w-4 h-4 text-emerald-600" />
              <span>مخزون الأدوية الرئيسي للمجمع</span>
            </span>
          </h3>

          <div className="space-y-3">
            {medicines.map((med) => (
              <div key={med.id} className="bg-slate-50 border border-slate-200 p-3.5 rounded-xl flex justify-between items-center text-xs">
                <div>
                  <h4 className="font-bold text-slate-900">{med.name}</h4>
                  <p className="text-[11px] text-slate-500">الفئة: {med.category} | انتهاء: {med.expiry_date}</p>
                </div>
                <div className="flex items-center gap-3">
                  <span className={`font-bold px-2.5 py-1 rounded-lg ${med.quantity <= med.min_threshold
                      ? 'bg-rose-100 text-rose-800 animate-pulse'
                      : 'bg-emerald-100 text-emerald-800'
                    }`}>
                    {med.quantity} قطعة
                  </span>
                  <button
                    onClick={() => setSelectedRestockItem({ id: med.id, name: med.name, type: 'medicine' })}
                    className="bg-emerald-900 hover:bg-emerald-800 text-white font-bold p-2 rounded-lg transition"
                    title="تزويد وإعادة تعبئة"
                  >
                    <Plus className="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Restock Modal */}
      {selectedRestockItem && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-indigo-900/40">
            <div className="flex justify-between items-center border-b pb-3">
              <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                <RefreshCw className="w-4 h-4 text-indigo-600" />
                <span>إعادة تزويد: {selectedRestockItem.name}</span>
              </h3>
              <button onClick={() => setSelectedRestockItem(null)} className="text-slate-400 hover:text-slate-600 text-xs font-bold">
                ✕ إغلاق
              </button>
            </div>

            <div className="space-y-3 text-xs">
              <div>
                <label className="block font-bold text-slate-700 mb-1">الكمية الإضافية المجهزة للمخزن:</label>
                <input
                  type="number"
                  value={addQty}
                  onChange={(e) => setAddQty(Number(e.target.value))}
                  className="w-full border border-slate-300 rounded-xl p-2.5 focus:ring-2 focus:ring-indigo-600 focus:outline-none font-bold text-indigo-950"
                />
              </div>
              <button
                onClick={handleRestock}
                className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl transition shadow-md flex items-center justify-center gap-2"
              >
                <CheckCircle2 className="w-4 h-4" />
                <span>إضافة للمخزون وتأكيد التزويد</span>
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
