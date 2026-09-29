'use client';

import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { User, UserRole, SystemNotification } from '@/types/medical';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || '/api/v1';

interface AuthContextType {
  user: User | null;
  token: string | null;
  activeRole: UserRole;
  notifications: SystemNotification[];
  isLoading: boolean;
  loginWithGoogle: (idToken: string) => Promise<void>;
  setAuthUser: (user: User, token: string) => void;
  requestRole: (role: UserRole, specialty: string, notes: string) => Promise<void>;
  switchRole: (role: UserRole) => void;
  logout: () => void;
  markNotificationAsRead: (id: number) => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [activeRole, setActiveRole] = useState<UserRole>('pending');
  const [notifications, setNotifications] = useState<SystemNotification[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  // Restore session from localStorage on mount
  useEffect(() => {
    try {
      const savedToken = localStorage.getItem('auth_token');
      const savedUser = localStorage.getItem('auth_user');
      if (savedToken && savedUser) {
        const parsedUser: User = JSON.parse(savedUser);
        setToken(savedToken);
        setUser(parsedUser);
        setActiveRole(parsedUser.role as UserRole);
      }
    } catch {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');
    } finally {
      setIsLoading(false);
    }
  }, []);

  const setAuthUser = useCallback((loggedUser: User, authToken: string) => {
    setUser(loggedUser);
    setToken(authToken);
    setActiveRole(loggedUser.role as UserRole);
    localStorage.setItem('auth_token', authToken);
    localStorage.setItem('auth_user', JSON.stringify(loggedUser));
  }, []);

  const loginWithGoogle = useCallback(async (idToken: string) => {
    setIsLoading(true);
    try {
      const res = await fetch(`${API_BASE}/auth/google`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ token: idToken }),
      });

      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.message || 'فشل تسجيل الدخول');
      }

      const loggedUser: User = data.data?.user || data.user;
      const authToken: string = data.data?.token || data.token;

      setAuthUser(loggedUser, authToken);
    } catch (err) {
      console.error('[Google Login Error]', err);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [setAuthUser]);

  const requestRole = useCallback(
    async (role: UserRole, specialty: string, notes: string) => {
      if (!user) return;
      setIsLoading(true);
      try {
        const res = await fetch(`${API_BASE}/auth/request-role`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
          },
          body: JSON.stringify({
            user_id: user.id,
            requested_role: role,
            requested_specialty: specialty,
            notes,
          }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'فشل تقديم الطلب');

        const updatedUser: User = { ...user, role: 'pending', status: 'pending' };
        setUser(updatedUser);
        setActiveRole('pending');
        localStorage.setItem('auth_user', JSON.stringify(updatedUser));
      } catch (err) {
        console.error('[Request Role Error]', err);
        throw err;
      } finally {
        setIsLoading(false);
      }
    },
    [user, token]
  );

  const switchRole = (role: UserRole) => setActiveRole(role);

  const logout = useCallback(() => {
    setUser(null);
    setToken(null);
    setActiveRole('pending');
    setNotifications([]);
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
  }, []);

  const markNotificationAsRead = useCallback((id: number) => {
    setNotifications((prev) => prev.map((n) => (n.id === id ? { ...n, is_read: true } : n)));
  }, []);

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        activeRole,
        notifications,
        isLoading,
        loginWithGoogle,
        setAuthUser,
        requestRole,
        switchRole,
        logout,
        markNotificationAsRead,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
