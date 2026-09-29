'use client';

import React, { useState } from 'react';
import { useAuth } from '@/context/AuthContext';
import { UserRole } from '@/types/medical';
import {
  Bell,
  Building2,
  ChevronDown,
  LogOut,
  ShieldCheck,
  User,
  Stethoscope,
  Pill,
  FlaskConical,
  Boxes,
  Calculator,
  Users,
  CheckCircle,
} from 'lucide-react';

const roleLabels: Record<UserRole, { label: string; icon: React.ElementType }> = {
  admin: { label: 'لوحة الادمن (المسؤول)', icon: ShieldCheck },
  doctor: { label: 'لوحة الطبيب', icon: Stethoscope },
  pharmacist: { label: 'لوحة الصيدلية', icon: Pill },
  lab_tech: { label: 'لوحة المختبر والأشعة', icon: FlaskConical },
  storekeeper: { label: 'لوحة المخزن', icon: Boxes },
  accountant: { label: 'لوحة المحاسب', icon: Calculator },
  hr: { label: 'لوحة الموارد البشرية', icon: Users },
  pending: { label: 'طلب حساب جديد', icon: User },
};

export default function Navbar() {
  const { user, activeRole, notifications, switchRole, logout, markNotificationAsRead } = useAuth();
  const [showNotifications, setShowNotifications] = useState(false);
  const [showRoleSwitcher, setShowRoleSwitcher] = useState(false);

  const unreadCount = notifications.filter((n) => !n.is_read).length;

  return (
    <header className="sticky top-0 z-40 w-full bg-[#1e1b4b] text-white shadow-lg border-b border-indigo-900/50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        {/* Brand Header */}
        <div className="flex items-center gap-3">
          <div className="bg-indigo-600/30 p-2 rounded-xl border border-indigo-400/20 backdrop-blur-md">
            <Building2 className="w-7 h-7 text-sky-400" />
          </div>
          <div>
            <h1 className="font-bold text-lg leading-tight tracking-wide text-white">المجمع الطبي التخصصي</h1>
            <p className="text-xs text-indigo-300">نظام الإدارة الشامل واللوحات الموحدة</p>
          </div>
        </div>

        {/* Action Controls & Role Switcher */}
        {user && (
          <div className="flex items-center gap-4">
            {/* Admin Switcher (Note: Admin can view ALL dashboards!) */}
            {user.role === 'admin' && (
              <div className="relative">
                <button
                  onClick={() => setShowRoleSwitcher(!showRoleSwitcher)}
                  className="flex items-center gap-2 bg-indigo-900/80 hover:bg-indigo-800 text-sky-200 text-xs px-3.5 py-2 rounded-xl border border-indigo-500/30 transition shadow-inner"
                >
                  <ShieldCheck className="w-4 h-4 text-sky-400" />
                  <span>التحويل بين الداشبوردات</span>
                  <ChevronDown className="w-3.5 h-3.5" />
                </button>

                {showRoleSwitcher && (
                  <div className="absolute left-0 mt-2 w-56 bg-[#0f172a] text-white rounded-2xl shadow-2xl border border-indigo-900/60 p-2 z-50 animate-in fade-in slide-in-from-top-2">
                    <p className="text-[11px] font-semibold text-indigo-400 px-3 py-1.5 border-b border-indigo-900/50">
                      لوحة الادمن تحكم كامل بكل الأقسام:
                    </p>
                    {(Object.keys(roleLabels) as UserRole[])
                      .filter((r) => r !== 'pending')
                      .map((role) => {
                        const Icon = roleLabels[role].icon;
                        return (
                          <button
                            key={role}
                            onClick={() => {
                              switchRole(role);
                              setShowRoleSwitcher(false);
                            }}
                            className={`w-full text-right flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium transition ${
                              activeRole === role
                                ? 'bg-indigo-600 text-white font-bold'
                                : 'hover:bg-indigo-900/50 text-indigo-200'
                            }`}
                          >
                            <Icon className="w-4 h-4 text-sky-400" />
                            <span>{roleLabels[role].label}</span>
                          </button>
                        );
                      })}
                  </div>
                )}
              </div>
            )}

            {/* Current Active Role Badge */}
            <div className="hidden md:flex items-center gap-2 bg-indigo-950/80 px-3 py-1.5 rounded-xl border border-indigo-800/40 text-xs text-indigo-200 font-medium">
              <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>{roleLabels[activeRole]?.label || activeRole}</span>
            </div>

            {/* Notifications Dropdown */}
            <div className="relative">
              <button
                onClick={() => setShowNotifications(!showNotifications)}
                className="relative p-2 rounded-xl bg-indigo-900/50 hover:bg-indigo-800 text-indigo-200 transition border border-indigo-700/30"
              >
                <Bell className="w-5 h-5 text-indigo-200" />
                {unreadCount > 0 && (
                  <span className="absolute -top-1 -right-1 w-5 h-5 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-[#1e1b4b]">
                    {unreadCount}
                  </span>
                )}
              </button>

              {showNotifications && (
                <div className="absolute left-0 mt-2 w-80 bg-[#0f172a] text-white rounded-2xl shadow-2xl border border-indigo-900/80 p-3 z-50">
                  <div className="flex items-center justify-between border-b border-indigo-900/60 pb-2 mb-2">
                    <h4 className="text-xs font-bold text-sky-400">التنبيهات والإشعارات الفورية</h4>
                    <span className="text-[10px] bg-indigo-900 text-indigo-200 px-2 py-0.5 rounded-full">
                      {notifications.length} إشعار
                    </span>
                  </div>
                  <div className="max-h-64 overflow-y-auto space-y-2">
                    {notifications.length === 0 ? (
                      <p className="text-xs text-slate-400 text-center py-4">لا توجد إشعارات جديدة</p>
                    ) : (
                      notifications.map((n) => (
                        <div
                          key={n.id}
                          onClick={() => markNotificationAsRead(n.id)}
                          className={`p-2.5 rounded-xl text-xs cursor-pointer border transition ${
                            n.is_read
                              ? 'bg-indigo-950/30 border-indigo-900/20 text-slate-400'
                              : 'bg-indigo-900/40 border-indigo-500/30 text-white font-medium hover:bg-indigo-900/60'
                          }`}
                        >
                          <div className="flex items-center justify-between mb-1">
                            <span className="font-bold text-sky-300 text-[11px]">{n.title}</span>
                            <span className="text-[9px] text-slate-400">{n.created_at}</span>
                          </div>
                          <p className="text-[11px] leading-relaxed text-indigo-100">{n.message}</p>
                        </div>
                      ))
                    )}
                  </div>
                </div>
              )}
            </div>

            {/* Profile Avatar & Logout */}
            <div className="flex items-center gap-2.5 pl-2 border-r border-indigo-800/50 pr-4">
              <img
                src={user.avatar || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&q=80&w=200'}
                alt={user.name}
                className="w-9 h-9 rounded-full ring-2 ring-sky-400/50 object-cover"
              />
              <div className="hidden lg:block text-right">
                <p className="text-xs font-bold text-white leading-tight">{user.name}</p>
                <p className="text-[10px] text-indigo-300">{user.email}</p>
              </div>
              <button
                onClick={logout}
                title="تسجيل الخروج"
                className="p-2 rounded-xl text-indigo-300 hover:text-rose-400 hover:bg-rose-950/40 transition"
              >
                <LogOut className="w-4 h-4" />
              </button>
            </div>
          </div>
        )}
      </div>
    </header>
  );
}
