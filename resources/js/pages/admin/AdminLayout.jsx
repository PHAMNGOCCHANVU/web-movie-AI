import React, { createContext, useContext, useState, useCallback } from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import AdminSidebar from '../../components/admin/AdminSidebar';

const AdminToastContext = createContext(null);

export const useAdminToast = () => useContext(AdminToastContext);

export default function AdminLayout() {
    const { user, isAuthenticated, loading } = useAuth();
    const [toast, setToast] = useState(null);

    const showToast = useCallback((type, message) => {
        setToast({ type, message });
        setTimeout(() => setToast(null), 3000);
    }, []);

    if (loading) {
        return <div className="min-h-screen bg-[#0B0F19] flex items-center justify-center text-white">Loading...</div>;
    }

    if (!isAuthenticated || user?.role?.name !== 'admin') {
        return <Navigate to="/" replace />;
    }

    return (
        <AdminToastContext.Provider value={{ showToast }}>
            <div className="min-h-screen bg-[#0B0F19] text-white flex">
                <AdminSidebar />
                <main className="flex-1 flex flex-col h-screen overflow-hidden relative">
                    <div className="flex-1 overflow-y-auto p-8">
                        <Outlet />
                    </div>

                    {/* Toast Notification */}
                    {toast && (
                        <div className="absolute top-8 right-8 z-50 animate-in fade-in slide-in-from-top-4">
                            <div className={`px-4 py-3 rounded-md shadow-lg flex items-center gap-3 border ${
                                toast.type === 'success'
                                    ? 'bg-[#151B27] border-[#10B981] text-[#10B981]'
                                    : 'bg-[#151B27] border-[#EF4444] text-[#EF4444]'
                            }`}>
                                <span className="font-medium">{toast.message}</span>
                            </div>
                        </div>
                    )}
                </main>
            </div>
        </AdminToastContext.Provider>
    );
}
