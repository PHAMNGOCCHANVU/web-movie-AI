import React, { useEffect, useState } from 'react';
import {
    Bot,
    Crown,
    Home,
    Library,
    LogOut,
    LogIn,
    Menu,
    Search,
    UserRound,
    X,
} from 'lucide-react';
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import Brand from './Brand';

const navItems = [
    { to: '/', label: 'Trang chủ', icon: Home },
    { to: '/library', label: 'Tủ phim', icon: Library },
    { to: '/search', label: 'Tìm kiếm', icon: Search },
    { to: '/ai', label: 'Chat AI', icon: Bot },
];

export default function Layout() {
    const { user, isAuthenticated, logout } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const [menuOpen, setMenuOpen] = useState(false);
    const [accountOpen, setAccountOpen] = useState(false);

    useEffect(() => {
        setMenuOpen(false);
        setAccountOpen(false);
    }, [location.pathname, location.search]);

    const handleLogout = async () => {
        await logout();
        navigate('/login');
    };

    return (
        <div className="min-h-screen bg-[#0b0f19] text-white">
            <header className="site-header">
                <div className="header-container flex h-16 items-center gap-6">
                    <Brand />

                    <nav className="hidden items-center gap-1 lg:flex">
                        {navItems.map(({ to, label, icon: Icon }) => (
                            <NavLink
                                className={({ isActive }) => `nav-link ${isActive ? 'nav-link--active' : ''}`}
                                end={to === '/'}
                                key={to}
                                to={to}
                            >
                                <Icon size={17} />
                                {label}
                            </NavLink>
                        ))}
                    </nav>

                    <div className="ml-auto hidden items-center gap-3 lg:flex">
                        <NavLink className="vip-button" to="/plans">
                            <Crown size={17} />
                            Đăng ký VIP
                        </NavLink>
                        <div className="relative">
                            <button className="account-button" onClick={() => setAccountOpen((open) => !open)}>
                                <span className={`avatar ${!isAuthenticated ? 'avatar--guest' : ''}`}>
                                    {isAuthenticated ? user?.name?.charAt(0)?.toUpperCase() : <UserRound size={18} />}
                                </span>
                                <span className="max-w-28 truncate">{isAuthenticated ? user?.name : 'Tài khoản'}</span>
                            </button>
                            {accountOpen && (
                                <div className="account-dropdown">
                                    {isAuthenticated ? (
                                        <>
                                            <NavLink to="/account"><UserRound size={17} /> Hồ sơ</NavLink>
                                            <button onClick={handleLogout}><LogOut size={17} /> Đăng xuất</button>
                                        </>
                                    ) : (
                                        <>
                                            <NavLink to="/login"><LogIn size={17} /> Đăng nhập</NavLink>
                                            <NavLink to="/register"><UserRound size={17} /> Đăng ký</NavLink>
                                        </>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>

                    <button className="mobile-menu-button icon-button ml-auto" onClick={() => setMenuOpen(true)}>
                        <Menu size={22} />
                    </button>
                </div>
            </header>

            {menuOpen && (
                <div className="fixed inset-0 z-[70] bg-black/70 lg:hidden" onClick={() => setMenuOpen(false)}>
                    <aside className="ml-auto h-full w-72 bg-[#101521] p-5" onClick={(event) => event.stopPropagation()}>
                        <div className="mb-8 flex items-center justify-between">
                            <Brand />
                            <button className="icon-button" onClick={() => setMenuOpen(false)}><X /></button>
                        </div>
                        <div className="flex flex-col gap-2">
                            {navItems.map(({ to, label, icon: Icon }) => (
                                <NavLink className="mobile-nav-link" key={to} to={to}>
                                    <Icon size={19} /> {label}
                                </NavLink>
                            ))}
                            <NavLink className="mobile-nav-link text-amber-300" to="/plans">
                                <Crown size={19} /> Đăng ký VIP
                            </NavLink>
                            {isAuthenticated ? (
                                <>
                                    <NavLink className="mobile-nav-link" to="/account">
                                        <UserRound size={19} /> Tài khoản
                                    </NavLink>
                                    <button className="mobile-nav-link text-left text-red-300" onClick={handleLogout}>
                                        <LogOut size={19} /> Đăng xuất
                                    </button>
                                </>
                            ) : (
                                <>
                                    <NavLink className="mobile-nav-link" to="/login"><LogIn size={19} /> Đăng nhập</NavLink>
                                    <NavLink className="mobile-nav-link" to="/register"><UserRound size={19} /> Đăng ký</NavLink>
                                </>
                            )}
                        </div>
                    </aside>
                </div>
            )}

            <main className="min-h-[calc(100vh-4rem)] pt-16">
                <Outlet />
            </main>
        </div>
    );
}
