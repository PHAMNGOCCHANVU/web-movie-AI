import React from 'react';
import { createRoot } from 'react-dom/client';
import { Navigate, Outlet, Route, Routes, useLocation } from 'react-router-dom';
import { HashRouter } from 'react-router-dom';
import Layout from './components/Layout';
import Loading from './components/Loading';
import { AuthProvider, useAuth } from './context/AuthContext';
import { UiProvider } from './context/UiContext';
import AccountPage from './pages/AccountPage';
import AiChatPage from './pages/AiChatPage';
import AuthPage from './pages/AuthPage';
import ForgotPasswordOtpPage from './pages/ForgotPasswordOtpPage';
import HomePage from './pages/HomePage';
import LibraryPage from './pages/LibraryPage';
import MovieDetailPage from './pages/MovieDetailPage';
import NotFoundPage from './pages/NotFoundPage';
import PaymentReturnPage from './pages/PaymentReturnPage';
import PlansPage from './pages/PlansPage';
import SearchPage from './pages/SearchPage';
import WatchPage from './pages/WatchPage';

function ProtectedRoute() {
    const { isAuthenticated, loading } = useAuth();
    const location = useLocation();

    if (loading) {
        return <div className="min-h-screen bg-[#0b0f19] pt-24"><Loading label="Đang xác thực tài khoản..." /></div>;
    }

    return isAuthenticated
        ? <Outlet />
        : <Navigate replace state={{ from: location }} to="/login" />;
}

export default function App() {
    return (
        <Routes>
            <Route path="/login" element={<AuthPage />} />
            <Route path="/register" element={<AuthPage mode="register" />} />
            <Route path="/forgot-password" element={<ForgotPasswordOtpPage />} />
            <Route path="/reset-password" element={<ForgotPasswordOtpPage />} />
            <Route element={<Layout />}>
                <Route index element={<HomePage />} />
                <Route path="/search" element={<SearchPage />} />
                <Route path="/movies/:movieId" element={<MovieDetailPage />} />
                <Route path="/payment/return" element={<PaymentReturnPage />} />
                <Route element={<ProtectedRoute />}>
                    <Route path="/library" element={<LibraryPage />} />
                    <Route path="/ai" element={<AiChatPage />} />
                    <Route path="/plans" element={<PlansPage />} />
                    <Route path="/account" element={<AccountPage />} />
                    <Route path="/watch/:movieId" element={<WatchPage />} />
                </Route>
            </Route>
            <Route path="*" element={<NotFoundPage />} />
        </Routes>
    );
}

const root = document.getElementById('app');

if (root) {
    createRoot(root).render(
        <React.StrictMode>
            <HashRouter>
                <UiProvider>
                    <AuthProvider>
                        <App />
                    </AuthProvider>
                </UiProvider>
            </HashRouter>
        </React.StrictMode>,
    );
}
