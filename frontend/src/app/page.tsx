'use client';

import React, { useState } from 'react';
import { useAuth } from '@/context/AuthContext';
import Navbar from '@/components/layout/Navbar';
import Sidebar from '@/components/layout/Sidebar';
import LoginView from '@/components/auth/LoginView';
import RequestRoleView from '@/components/auth/RequestRoleView';

import AdminDashboard from '@/components/dashboards/AdminDashboard';
import DoctorDashboard from '@/components/dashboards/DoctorDashboard';
import PharmacyDashboard from '@/components/dashboards/PharmacyDashboard';
import LabDashboard from '@/components/dashboards/LabDashboard';
import StoreDashboard from '@/components/dashboards/StoreDashboard';
import AccountantDashboard from '@/components/dashboards/AccountantDashboard';
import HrDashboard from '@/components/dashboards/HrDashboard';

export default function Home() {
  const { user, activeRole } = useAuth();
  const [activeTab, setActiveTab] = useState<string>('overview');

  // If user is not logged in -> Show Google OAuth Login Screen
  if (!user) {
    return <LoginView />;
  }

  // If user role is pending or user status is pending -> Show Request Role screen
  if (user.role === 'pending' || user.status === 'pending') {
    return (
      <div className="min-h-screen flex flex-col bg-[#f8fafc]">
        <Navbar />
        <RequestRoleView />
      </div>
    );
  }

  const renderActiveDashboard = () => {
    switch (activeRole) {
      case 'admin':
        return <AdminDashboard activeTab={activeTab} />;
      case 'doctor':
        return <DoctorDashboard />;
      case 'pharmacist':
        return <PharmacyDashboard />;
      case 'lab_tech':
        return <LabDashboard />;
      case 'storekeeper':
        return <StoreDashboard />;
      case 'accountant':
        return <AccountantDashboard />;
      case 'hr':
        return <HrDashboard />;
      default:
        return <AdminDashboard activeTab={activeTab} />;
    }
  };

  return (
    <div className="min-h-screen flex flex-col bg-[#f8fafc]">
      <Navbar />
      <div className="flex flex-1">
        <Sidebar activeTab={activeTab} setActiveTab={setActiveTab} />
        <main className="flex-1 p-6 overflow-y-auto max-w-7xl mx-auto">
          {renderActiveDashboard()}
        </main>
      </div>
    </div>
  );
}
