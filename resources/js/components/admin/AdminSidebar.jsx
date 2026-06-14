import React from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import {
    LayoutDashboard,
    Film,
    Users,
    MessageSquare,
    CreditCard,
    LayoutTemplate,
    RefreshCw,
    LogOut
} from 'lucide-react';

const MENU_ITEMS = [
    { name: 'Dashboard', path: '/admin/dashboard', icon: LayoutDashboard },
    { name: 'Phim', path: '/admin/movies', icon: Film },
    { name: 'Người dùng', path: '/admin/users', icon: Users },
    { name: 'Bình luận', path: '/admin/comments', icon: MessageSquare },
    { name: 'Giao dịch', path: '/admin/transactions', icon: CreditCard },
    { name: 'Homepage Blocks', path: '/admin/homepage-blocks', icon: LayoutTemplate },
];

export default function AdminSidebar() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();

    const handleLogout = async () => {
        await logout();
        navigate('/login');
    };

    return (
        <aside className="w-[240px] flex-shrink-0 h-screen sticky top-0 bg-[#070A12] border-r border-white/10 flex flex-col">
            {/* Logo */}
            <div className="h-16 flex items-center px-6 border-b border-white/10">
                <span className="text-xl font-bold tracking-wider text-white">
                    CINEON<span className="text-[#E50914]">.</span>
                </span>
                <span className="ml-2 text-xs font-semibold text-zinc-500 tracking-widest uppercase">Admin</span>
            </div>

            {/* Navigation */}
            <div className="flex-1 overflow-y-auto py-6 px-3 flex flex-col gap-1">
                {MENU_ITEMS.map((item) => {
                    const Icon = item.icon;
                    return (
                        <NavLink
                            key={item.path}
                            to={item.path}
                            className={({ isActive }) =>
                                `flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors ${
                                    isActive
                                        ? 'bg-[rgba(229,9,20,0.1)] text-white border-l-2 border-[#E50914]'
                                        : 'text-zinc-400 hover:text-white hover:bg-white/5 border-l-2 border-transparent'
                                }`
                            }
                        >
                            <Icon size={18} />
                            {item.name}
                        </NavLink>
                    );
                })}
            </div>

            {/* User & Logout */}
            <div className="p-4 border-t border-white/10">
                <div className="flex items-center gap-3 mb-4 px-2">
                    <div className="w-8 h-8 rounded-full bg-zinc-800 flex items-center justify-center text-sm font-bold text-white">
                        {user?.name?.charAt(0).toUpperCase() || 'A'}
                    </div>
                    <div className="flex-1 overflow-hidden">
                        <p className="text-sm font-medium text-white truncate">{user?.name || 'Admin User'}</p>
                        <p className="text-xs text-zinc-500 truncate">{user?.email || 'admin@example.com'}</p>
                    </div>
                </div>
                <button
                    onClick={handleLogout}
                    className="w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium text-zinc-400 hover:text-white hover:bg-white/5 transition-colors"
                >
                    <LogOut size={18} />
                    Đăng xuất
                </button>
            </div>
        </aside>
    );
}
