'use client';

import React from 'react';
import { useAuth } from '@/context/AuthContext';
import { UserRole } from '@/types/medical';
import {
  ShieldCheck,
  Stethoscope,
  Pill,
  FlaskConical,
  Boxes,
  Calculator,
  Users,
  FileText,
  Activity,
  AlertTriangle,
  Clock,
  CheckCircle2,
  TrendingUp,
  UserPlus,
} from 'lucide-react';

interface SidebarProps {
  activeTab: string;
  setActiveTab: (tab: string) => void;
}

export default function Sidebar({ activeTab, setActiveTab }: SidebarProps) {
  const { activeRole } = useAuth();

  const getRoleMenuItems = (role: UserRole) => {
    switch (role) {
      case 'admin':
        return [
          { id: 'overview', label: 'الملخص والإحصائيات الشاملة', icon: ShieldCheck },
          { id: 'requests', label: 'طلبات الحسابات والأدوار المعلقة', icon: UserPlus },
          { id: 'doctors_revenue', label: 'كشوفات وإيرادات الأطباء', icon: Stethoscope },
          { id: 'inventory_monitor', label: 'مراقبة الصيدلية والمخازن', icon: Boxes },
          { id: 'hr_guards', label: 'إدارة الموظفين والحرس والأمن', icon: Users },
        ];
      case 'doctor':
        return [
          { id: 'queue', label: 'قائمة انتظار المرضى والكشفية', icon: Stethoscope },
          { id: 'diagnosis', label: 'التشخيص والفحوصات والوصفة', icon: Activity },
          { id: 'history', label: 'السجل الطبي التشاركي للمريض', icon: FileText },
        ];
      case 'pharmacist':
        return [
          { id: 'prescriptions', label: 'الوصفات الواردة وصرف الدواء', icon: Pill },
          { id: 'medicines_stock', label: 'مخزون الأدوية والأسعار', icon: Boxes },
          { id: 'expiry_alerts', label: 'تنبيهات الصلاحية والنقص (<=15%)', icon: AlertTriangle },
        ];
      case 'lab_tech':
        return [
          { id: 'test_requests', label: 'طلبات التحاليل والأشعة الإيكو والتخطيط', icon: FlaskConical },
          { id: 'consumables', label: 'مستلزمات الفحص (إبر/قطن/كحول)', icon: Boxes },
        ];
      case 'storekeeper':
        return [
          { id: 'inventory_list', label: 'مخزون المواد والأدوية الرئيسي', icon: Boxes },
          { id: 'restock_requests', label: 'طلبات إعادة التزويد والتجهيز', icon: Clock },
        ];
      case 'accountant':
        return [
          { id: 'journal_vouchers', label: 'تسجيل القيود والسندات اليومية', icon: Calculator },
          { id: 'expenses', label: 'مصاريف الماء والكهرباء والخدمات', icon: TrendingUp },
          { id: 'financial_report', label: 'تقرير الأرباح والميزانية الشاملة', icon: FileText },
        ];
      case 'hr':
        return [
          { id: 'biometric_log', label: 'جهاز البصمة الإلكتروني اليومي', icon: Clock },
          { id: 'guards_roster', label: 'جدول الحرس واللزام والواجبات', icon: Users },
          { id: 'salaries', label: 'إدارة الموظفين والرواتب', icon: Calculator },
        ];
      default:
        return [{ id: 'pending', label: 'حالة طلب الانضمام', icon: Clock }];
    }
  };

  const menuItems = getRoleMenuItems(activeRole);

  return (
    <aside className="w-64 bg-[#0f172a] text-[#f8fafc] border-l border-indigo-900/40 p-4 min-h-[calc(100vh-4rem)] flex flex-col justify-between">
      <div className="space-y-6">
        <div>
          <h3 className="text-xs font-semibold text-indigo-400 uppercase tracking-wider px-3 mb-3">
            قائمة تحكم القسم
          </h3>
          <nav className="space-y-1.5">
            {menuItems.map((item) => {
              const Icon = item.icon;
              const isActive = activeTab === item.id;
              return (
                <button
                  key={item.id}
                  onClick={() => setActiveTab(item.id)}
                  className={`w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all ${
                    isActive
                      ? 'bg-gradient-to-r from-indigo-900 to-indigo-800 text-white font-bold border border-indigo-500/40 shadow-md'
                      : 'text-slate-300 hover:bg-indigo-950/60 hover:text-white'
                  }`}
                >
                  <Icon className={`w-4 h-4 ${isActive ? 'text-sky-400' : 'text-indigo-400'}`} />
                  <span className="truncate">{item.label}</span>
                </button>
              );
            })}
          </nav>
        </div>
      </div>

      <div className="bg-indigo-950/60 p-3.5 rounded-2xl border border-indigo-800/30 text-xs">
        <div className="flex items-center gap-2 text-emerald-400 font-bold mb-1">
          <CheckCircle2 className="w-4 h-4" />
          <span>النظام متصل وشغال</span>
        </div>
        <p className="text-[11px] text-slate-400">قاعدة البيانات: PHP MySQL REST API جاهز</p>
      </div>
    </aside>
  );
}
