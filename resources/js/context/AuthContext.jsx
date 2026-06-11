import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { api, hasToken, setToken } from '../api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(hasToken());

    const refreshUser = useCallback(async () => {
        if (!hasToken()) {
            setUser(null);
            setLoading(false);
            return null;
        }

        try {
            const response = await api.me();
            setUser(response.data.data);
            return response.data.data;
        } catch {
            setToken(null);
            setUser(null);
            return null;
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        refreshUser();

        const handleUnauthorized = () => {
            setUser(null);
            setLoading(false);
        };

        window.addEventListener('cineon:unauthorized', handleUnauthorized);
        return () => window.removeEventListener('cineon:unauthorized', handleUnauthorized);
    }, [refreshUser]);

    const login = useCallback(async (credentials) => {
        const response = await api.login(credentials);
        setToken(response.data.data.token);
        setUser(response.data.data.user);
        return response.data.data.user;
    }, []);

    const register = useCallback(async (payload) => {
        const response = await api.register(payload);
        return response.data.data;
    }, []);

    const verifyRegistrationOtp = useCallback(async (email, otp) => {
        const response = await api.verifyRegistrationOtp(email, otp);
        setToken(response.data.data.token);
        setUser(response.data.data.user);
        return response.data.data.user;
    }, []);

    const logout = useCallback(async () => {
        try {
            await api.logout();
        } finally {
            setToken(null);
            setUser(null);
        }
    }, []);

    const value = useMemo(() => ({
        user,
        loading,
        isAuthenticated: Boolean(user),
        login,
        register,
        verifyRegistrationOtp,
        logout,
        refreshUser,
    }), [user, loading, login, register, verifyRegistrationOtp, logout, refreshUser]);

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
    return useContext(AuthContext);
}
