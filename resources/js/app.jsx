import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate, Outlet, useLocation } from 'react-router-dom';
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

// Admin imports
import AdminLayout from './pages/admin/AdminLayout';
import DashboardPage from './pages/admin/DashboardPage';
import MovieManagementPage from './pages/admin/MovieManagementPage';
import MovieFormPage from './pages/admin/MovieFormPage';
import UserManagementPage from './pages/admin/UserManagementPage';
import UserDetailPage from './pages/admin/UserDetailPage';
import TransactionPage from './pages/admin/TransactionPage';
import CommentModerationPage from './pages/admin/CommentModerationPage';
import HomepageBlockPage from './pages/admin/HomepageBlockPage';

const container = document.getElementById('app');
const root = window.root || createRoot(container);
if (!window.root) window.root = root;

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
            <Route path="/admin" element={<AdminLayout />}>
                <Route index element={<Navigate to="/admin/dashboard" />} />
                <Route path="dashboard" element={<DashboardPage />} />
                <Route path="movies" element={<MovieManagementPage />} />
                <Route path="movies/new" element={<MovieFormPage />} />
                <Route path="movies/:id/edit" element={<MovieFormPage />} />
                <Route path="users" element={<UserManagementPage />} />
                <Route path="users/:id" element={<UserDetailPage />} />
                <Route path="transactions" element={<TransactionPage />} />
                <Route path="comments" element={<CommentModerationPage />} />
                <Route path="homepage-blocks" element={<HomepageBlockPage />} />
            </Route>
            <Route path="*" element={<NotFoundPage />} />
        </Routes>
    );
}

//const root = document.getElementById('app');

root.render(
    <React.StrictMode>
        <BrowserRouter>
            <UiProvider>
                <AuthProvider>
                    <App />
                </AuthProvider>
            </UiProvider>
        </BrowserRouter>
    </React.StrictMode>
);