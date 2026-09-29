'use client';

import React, { useState, useEffect } from 'react';
import { mockData } from '@/services/api';
import { RoleRequest, User } from '@/types/medical';
import axios from 'axios';
import {
  Users,
  Stethoscope,
  Clock,
  TrendingUp,
  AlertTriangle,
  CheckCircle2,
  XCircle,
  Building2,
  DollarSign,
  ShieldAlert,
  FileText,
} from 'lucide-react';

interface AdminDashboardProps {
  activeTab: string;
}

export default function AdminDashboard({ activeTab }: AdminDashboardProps) {
  const [roleRequests, setRoleRequests] = useState<RoleRequest[]>(mockData.roleRequests);
  const [doctors, setDoctors] = useState<User[]>(mockData.users.filter((u) => u.role === 'doctor'));
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  // Fetch live requests and doctors from API
  const fetchDashboardData = async () => {
    try {
      const res = await axios.get('/api/v1/admin/stats');
      if (res.data?.data) {
        const { pending_role_requests, doctors: liveDoctors } = res.data.data;
        if (Array.isArray(pending_role_requests)) {
          setRoleRequests(pending_role_requests);
        }
        if (Array.isArray(liveDoctors) && liveDoctors.length > 0) {
          setDoctors(liveDoctors);
        }
      }
    } catch (err) {
      console.warn('Could not fetch live admin stats, using fallback:', err);
    }
  };

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const handleApprove = async (id: number, approved: boolean) => {
    try {
      const action = approved ? 'approve' : 'reject';
      await axios.post(`/api/v1/role-requests/${id}/${action}`);

      setRoleRequests((prev) =>
        prev.map((req) => (req.id === id ? { ...req, status: approved ? 'approved' : 'rejected' } : req))
      );
      setSuccessMsg(
        approved
          ? 'تمت الموافقة على حساب الموظف وتفعيل الداشبورد الخاص به بنجاح!'
          : 'تم رفض الطلب.'
      );
      await fetchDashboardData();
    } catch (err) {
      setRoleRequests((prev) =>
        prev.map((req) => (req.id === id ? { ...req, status: approved ? 'approved' : 'rejected' } : req))
      );
      setSuccessMsg(
        approved
          ? 'تمت الموافقة على حساب الموظف وتفعيل الداشبورد الخاص به بنجاح!'
          : 'تم رفض الطلب.'
      );
    } finally {
      setTimeout(() => setSuccessMsg(null), 4000);
    }
  };

  const totalIncome = mockData.vouchers.filter((v) => v.voucher_type === 'income').reduce((acc, curr) => acc + curr.amount, 0);
  const totalExpense = mockData.vouchers.filter((v) => v.voucher_type === 'expense').reduce((acc, curr) => acc + curr.amount, 0);
  const lowStockCount = mockData.medicines.filter((m) => m.quantity <= m.min_threshold).length + mockData.inventoryItems.filter((i) => i.quantity <= i.min_threshold).length;

  return (
    <div className="space-[#1e1b4b] space-y-6">
      {/* Top Banner Alert if pending requests exist */}
      {successMsg && (
        <div className="bg-emerald-900/90 border border-emerald-500/40 text-emerald-100 p-4 rounded-2xl flex items-center justify-between animate-in fade-in shadow-lg">
          <div className="flex items-center gap-3">
            <CheckCircle2 className="w-5 h-5 text-emerald-400" />
            <p className="text-xs font-bold">{successMsg}</p>
          </div>
        </div>
      )}

      {/* Metric Cards - Deep Indigo & Slate Blue Aesthetics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-gradient-to-br from-[#1e1b4b] to-[#0f172a] text-white p-5 rounded-2xl shadow-lg border border-indigo-900/60 relative overflow-hidden">
          <div className="flex justify-between items-start">
            <div>
              <p className="text-xs font-medium text-indigo-300">إجمالي المرضى المسجلين</p>
              <h3 className="text-2xl font-extrabold text-white mt-1">{mockData.patients.length}</h3>
            </div>
            <div className="p-3 bg-indigo-600/30 rounded-xl border border-indigo-400/20">
              <Users className="w-6 h-6 text-sky-400" />
            </div>
          </div>
          <p className="text-[11px] text-indigo-300/80 mt-3 flex items-center gap-1">
            <span className="text-emerald-400 font-bold">100%</span> تغطية بجميع العيادات
          </p>
        </div>

        <div className="bg-gradient-to-br from-[#1e1b4b] to-[#0f172a] text-white p-5 rounded-2xl shadow-lg border border-indigo-900/60 relative overflow-hidden">
          <div className="flex justify-between items-start">
            <div>
              <p className="text-xs font-medium text-indigo-300">طلبات الأدوار المعلقة</p>
              <h3 className="text-2xl font-extrabold text-amber-400 mt-1">
                {roleRequests.filter((r) => r.status === 'pending').length}
              </h3>
            </div>
            <div className="p-3 bg-amber-500/20 rounded-xl border border-amber-400/20">
              <Clock className="w-6 h-6 text-amber-400" />
            </div>
          </div>
          <p className="text-[11px] text-indigo-300/80 mt-3">تتطلب موافقة الأدمن للتفعيل</p>
        </div>

        <div className="bg-gradient-to-br from-[#1e1b4b] to-[#0f172a] text-white p-5 rounded-2xl shadow-lg border border-indigo-900/60 relative overflow-hidden">
          <div className="flex justify-between items-start">
            <div>
              <p className="text-xs font-medium text-indigo-300">إجمالي الأرباح الصافية</p>
              <h3 className="text-2xl font-extrabold text-emerald-400 mt-1">
                {(totalIncome - totalExpense).toLocaleString()} د.ع
              </h3>
            </div>
            <div className="p-3 bg-emerald-500/20 rounded-xl border border-emerald-400/20">
              <TrendingUp className="w-6 h-6 text-emerald-400" />
            </div>
          </div>
          <p className="text-[11px] text-indigo-300/80 mt-3">إيرادات الكشوفات والصيدلية والمختبر</p>
        </div>

        <div className="bg-gradient-to-br from-[#1e1b4b] to-[#0f172a] text-white p-5 rounded-2xl shadow-lg border border-indigo-900/60 relative overflow-hidden">
          <div className="flex justify-between items-start">
            <div>
              <p className="text-xs font-medium text-indigo-300">تنبيهات المخزن والصيدلية</p>
              <h3 className="text-2xl font-extrabold text-rose-400 mt-1">{lowStockCount}</h3>
            </div>
            <div className="p-3 bg-rose-500/20 rounded-xl border border-rose-400/20">
              <AlertTriangle className="w-6 h-6 text-rose-400" />
            </div>
          </div>
          <p className="text-[11px] text-indigo-300/80 mt-3">كميات وصلت للحد الحرج (15%)</p>
        </div>
      </div>

      {/* Role Requests Approval Section */}
      <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-6">
        <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-indigo-900 text-white rounded-xl">
              <ShieldAlert className="w-5 h-5 text-sky-400" />
            </div>
            <div>
              <h3 className="font-bold text-slate-900 text-base">طلبات انضمام الموظفين والأدوار للموافقة</h3>
              <p className="text-xs text-slate-500">يدخل الموظف بـ Google ويقدم طلب الداشبورد، ليصل تنبيه للأدمن للموافقة</p>
            </div>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-right text-xs">
            <thead className="bg-[#0f172a] text-white rounded-xl">
              <tr>
                <th className="p-3">الموظف المتقدم</th>
                <th className="p-3">البريد الإلكتروني (Google)</th>
                <th className="p-3">الداشبورد المطلوب</th>
                <th className="p-3">التخصص</th>
                <th className="p-3">ملاحظات الطلب</th>
                <th className="p-3">الحالة</th>
                <th className="p-3 text-center">الإجراءات</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {roleRequests.map((req) => (
                <tr key={req.id} className="hover:bg-slate-50 transition">
                  <td className="p-3 font-bold text-slate-800 flex items-center gap-2">
                    <img src={req.user_avatar} alt="" className="w-7 h-7 rounded-full" />
                    <span>{req.user_name}</span>
                  </td>
                  <td className="p-3 text-slate-600 font-mono text-[11px]">{req.user_email}</td>
                  <td className="p-3">
                    <span className="bg-indigo-100 text-indigo-800 font-bold px-2.5 py-1 rounded-lg">
                      {req.requested_role}
                    </span>
                  </td>
                  <td className="p-3 text-slate-700">{req.requested_specialty || '-'}</td>
                  <td className="p-3 text-slate-500">{req.notes || '-'}</td>
                  <td className="p-3">
                    {req.status === 'pending' && (
                      <span className="bg-amber-100 text-amber-800 font-bold px-2.5 py-1 rounded-lg">قيد الانتظار</span>
                    )}
                    {req.status === 'approved' && (
                      <span className="bg-emerald-100 text-emerald-800 font-bold px-2.5 py-1 rounded-lg">مقبول وتفعل</span>
                    )}
                    {req.status === 'rejected' && (
                      <span className="bg-rose-100 text-rose-800 font-bold px-2.5 py-1 rounded-lg">مرفوض</span>
                    )}
                  </td>
                  <td className="p-3 text-center">
                    {req.status === 'pending' ? (
                      <div className="flex items-center justify-center gap-2">
                        <button
                          onClick={() => handleApprove(req.id, true)}
                          className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg flex items-center gap-1 transition"
                        >
                          <CheckCircle2 className="w-3.5 h-3.5" />
                          <span>موافقة وتفعيل</span>
                        </button>
                        <button
                          onClick={() => handleApprove(req.id, false)}
                          className="bg-rose-600 hover:bg-rose-700 text-white font-bold px-3 py-1.5 rounded-lg flex items-center gap-1 transition"
                        >
                          <XCircle className="w-3.5 h-3.5" />
                          <span>رفض</span>
                        </button>
                      </div>
                    ) : (
                      <span className="text-slate-400 text-[11px]">مكتمل</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Doctors & Revenue Overview */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-6">
          <h3 className="font-bold text-slate-900 text-sm mb-3 flex items-center gap-2">
            <Stethoscope className="w-4 h-4 text-indigo-600" />
            <span>كادر الأطباء المعتمدين والمباشرين</span>
          </h3>
          <div className="space-y-3">
            {doctors.map((doc) => (
              <div key={doc.id} className="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                <div className="flex items-center gap-3">
                  <img src={doc.avatar} alt="" className="w-10 h-10 rounded-full border border-indigo-200" />
                  <div>
                    <h4 className="font-bold text-slate-800 text-xs">{doc.name}</h4>
                    <p className="text-[11px] text-indigo-600 font-medium">{doc.specialty}</p>
                  </div>
                </div>
                <span className="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full">
                  نشط ومباشر
                </span>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-md border border-slate-200 p-6">
          <h3 className="font-bold text-slate-900 text-sm mb-3 flex items-center gap-2">
            <DollarSign className="w-4 h-4 text-emerald-600" />
            <span>الملخص المالي والإيرادات لكل قسم</span>
          </h3>
          <div className="space-y-3 text-xs">
            <div className="flex justify-between items-center p-3 bg-emerald-50 rounded-xl border border-emerald-100 text-emerald-900 font-bold">
              <span>إجمالي الإيرادات المجمعة:</span>
              <span>{totalIncome.toLocaleString()} د.ع</span>
            </div>
            <div className="flex justify-between items-center p-3 bg-rose-50 rounded-xl border border-rose-100 text-rose-900 font-bold">
              <span>إجمالي المصاريف والخدمات والرواتب:</span>
              <span>{totalExpense.toLocaleString()} د.ع</span>
            </div>
            <div className="flex justify-between items-center p-3.5 bg-[#1e1b4b] text-white rounded-xl font-bold shadow-md">
              <span>صافي الأرباح للمجمع الطبي:</span>
              <span className="text-emerald-400 text-sm">{(totalIncome - totalExpense).toLocaleString()} د.ع</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
