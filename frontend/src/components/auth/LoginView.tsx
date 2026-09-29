'use client';

import React, { useState } from 'react';
import { useGoogleLogin } from '@react-oauth/google';
import { useAuth } from '@/context/AuthContext';
import { Building2, Loader2, UserCheck, Shield } from 'lucide-react';
import { mockData } from '@/services/api';
import { apiErrorMessage, backendDownMessage } from '@/services/medicalApi';
import { User, UserRole } from '@/types/medical';
import axios from 'axios';

export default function LoginView() {
  const { setAuthUser } = useAuth();
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  // دالة موحدة وسليمة لمعالجة تسجيل الدخول عبر جوجل وإرسالها للباك اند
  const handleGoogleSuccess = async (tokenResponse: any) => {
    setError(null);
    setLoading(true);
    try {
      const accessToken = tokenResponse.access_token || tokenResponse.credential;
      const userEmail = tokenResponse.email;

      if (!accessToken) {
        throw new Error('لم يتم استلام التوكن من Google');
      }

      // إرسال الطلب عبر الرابط الموحد /api/v1
      const res = await axios.post('/api/v1/auth/google', {
        access_token: accessToken,
        email: userEmail,
        name: tokenResponse.name,
        avatar: tokenResponse.picture,
      });

      console.log('تم تسجيل الدخول بنجاح', res.data);

      const loggedUser: User = res.data.data?.user || res.data.user;
      const authToken: string = res.data.data?.token || res.data.token;

      if (loggedUser && authToken) {
        setAuthUser(loggedUser, authToken);
      } else {
        throw new Error('لم يتم استلام بيانات المستخدم بشكل صحيح');
      }
    } catch (err: unknown) {
      let message = 'حدث خطأ أثناء تسجيل الدخول';
      if (axios.isAxiosError(err)) {
        if (!err.response) {
          message = backendDownMessage();
        } else {
          const data = err.response.data;
          // Laravel returns JSON; anything else means the request never reached Laravel
          message = apiErrorMessage(err.response.status, data && typeof data === 'object' ? data : null);
        }
      } else if (err instanceof Error) {
        message = err.message;
      }
      setError(message);
    } finally {
      setLoading(false);
    }
  };

  const googleLoginTrigger = useGoogleLogin({
    onSuccess: handleGoogleSuccess,
    onError: () => setError('فشل تسجيل الدخول عبر جوجل'),
  });

  // تسجيل دخول تجريبي مباشر للمطور والإدارة
  const handleQuickLogin = (demoUser: User) => {
    setAuthUser(demoUser, 'demo-token-' + demoUser.id);
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-950 text-white p-4">
      <div className="w-full max-w-md p-8 space-y-6 bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl text-right">
        <div className="text-center">
          <div className="inline-flex p-3 bg-indigo-600/20 text-indigo-400 rounded-2xl border border-indigo-500/20 mb-3">
            <Building2 className="h-10 w-10 text-sky-400" />
          </div>
          <h2 className="text-2xl font-extrabold tracking-tight">المجمع الطبي التخصصي</h2>
          <p className="text-xs text-slate-400 mt-1">نظام إدارة اللوحات والتحكم الشامل الموحد</p>
        </div>

        {error && (
          <div className="p-3 text-xs text-red-300 bg-red-950/60 border border-red-800/60 rounded-xl leading-relaxed">
            {error}
          </div>
        )}

        <div className="space-y-4">
          <button
            onClick={() => googleLoginTrigger()}
            disabled={loading}
            className="w-full flex items-center justify-center gap-3 py-3.5 px-4 bg-white text-gray-900 font-bold rounded-2xl hover:bg-slate-100 transition duration-200 shadow-md text-sm cursor-pointer disabled:opacity-50"
          >
            {loading ? (
              <Loader2 className="w-5 h-5 animate-spin text-indigo-600" />
            ) : (
              <>
                <svg className="w-5 h-5" viewBox="0 0 24 24">
                  <path
                    fill="#4285F4"
                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                  />
                  <path
                    fill="#34A853"
                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                  />
                  <path
                    fill="#FBBC05"
                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
                  />
                  <path
                    fill="#EA4335"
                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
                  />
                </svg>
                <span>تسجيل الدخول عبر Google</span>
              </>
            )}
          </button>
        </div>

        {/* حسابات تجريبية سريعة للاختبار والموافقة */}
        <div className="pt-4 border-t border-slate-800 space-y-3">
          <div className="flex items-center justify-between text-xs text-slate-400">
            <span className="font-bold flex items-center gap-1.5 text-indigo-300">
              <Shield className="w-3.5 h-3.5" />
              <span>دخول مباشر لتجربة الأدوار وإدارة الطلبات:</span>
            </span>
          </div>

          <div className="grid grid-cols-2 gap-2 text-xs">
            <button
              onClick={() => handleQuickLogin(mockData.users[0])}
              className="p-2.5 rounded-xl bg-indigo-950/80 hover:bg-indigo-900 border border-indigo-800/60 flex items-center gap-2 transition text-right"
            >
              <img src={mockData.users[0].avatar} alt="" className="w-6 h-6 rounded-full" />
              <div className="truncate">
                <p className="font-bold text-white text-[11px] truncate">الأدمن (مدير)</p>
                <p className="text-[10px] text-indigo-300">موافقة الطلبات</p>
              </div>
            </button>

            <button
              onClick={() => handleQuickLogin(mockData.users[1])}
              className="p-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-slate-700/60 flex items-center gap-2 transition text-right"
            >
              <img src={mockData.users[1].avatar} alt="" className="w-6 h-6 rounded-full" />
              <div className="truncate">
                <p className="font-bold text-white text-[11px] truncate">طبيب</p>
                <p className="text-[10px] text-slate-400">كشوفات ووصفات</p>
              </div>
            </button>

            <button
              onClick={() => handleQuickLogin(mockData.users[2])}
              className="p-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-slate-700/60 flex items-center gap-2 transition text-right"
            >
              <img src={mockData.users[2].avatar} alt="" className="w-6 h-6 rounded-full" />
              <div className="truncate">
                <p className="font-bold text-white text-[11px] truncate">صيدلاني</p>
                <p className="text-[10px] text-slate-400">صرف أدوية</p>
              </div>
            </button>

            <button
              onClick={() => handleQuickLogin(mockData.users[3])}
              className="p-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-slate-700/60 flex items-center gap-2 transition text-right"
            >
              <img src={mockData.users[3].avatar} alt="" className="w-6 h-6 rounded-full" />
              <div className="truncate">
                <p className="font-bold text-white text-[11px] truncate">مختبر وأشعة</p>
                <p className="text-[10px] text-slate-400">تقارير تحاليل</p>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

