import React, { useMemo, useState } from 'react';
import { ArrowRight, LockKeyhole, Mail, ShieldCheck } from 'lucide-react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { api, apiError } from '../api';
import Brand from '../components/Brand';
import { useUi } from '../context/UiContext';

export default function ResetPasswordPage() {
    const [params] = useSearchParams();
    const navigate = useNavigate();
    const { toast } = useUi();
    const initial = useMemo(() => ({
        token: params.get('token') || '',
        email: params.get('email') || '',
        password: '',
        password_confirmation: '',
    }), [params]);
    const [form, setForm] = useState(initial);
    const [loading, setLoading] = useState(false);

    const submit = async (event) => {
        event.preventDefault();
        setLoading(true);
        try {
            await api.resetPassword(form);
            toast('Đặt lại mật khẩu thành công.');
            navigate('/login');
        } catch (error) {
            toast(apiError(error), 'error');
        } finally {
            setLoading(false);
        }
    };

    return (
        <main className="auth-switch-page">
            <div className="auth-orb auth-orb--one" />
            <div className="auth-orb auth-orb--two" />
            <form className="auth-reset-card" onSubmit={submit}>
                <Brand />
                <span className="auth-kicker mt-8 block">Bảo mật tài khoản</span>
                <h1>Đặt lại mật khẩu</h1>
                <p>Tạo mật khẩu mới có ít nhất 8 ký tự để tiếp tục sử dụng CineON.</p>
                <div className="auth-form-fields mt-8">
                    <label className="auth-input">
                        <span className="auth-input__icon"><Mail size={20} /></span>
                        <input required type="email" value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} />
                    </label>
                    <label className="auth-input">
                        <span className="auth-input__icon"><LockKeyhole size={20} /></span>
                        <input required minLength="8" placeholder="Mật khẩu mới" type="password" value={form.password} onChange={(event) => setForm({ ...form, password: event.target.value })} />
                    </label>
                    <label className="auth-input">
                        <span className="auth-input__icon"><ShieldCheck size={20} /></span>
                        <input required minLength="8" placeholder="Xác nhận mật khẩu" type="password" value={form.password_confirmation} onChange={(event) => setForm({ ...form, password_confirmation: event.target.value })} />
                    </label>
                    <button className="auth-submit-button" disabled={loading}>
                        {loading ? 'Đang xử lý...' : 'Đặt lại mật khẩu'} {!loading && <ArrowRight size={18} />}
                    </button>
                </div>
            </form>
        </main>
    );
}
