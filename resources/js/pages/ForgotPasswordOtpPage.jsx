import React, { useState } from 'react';
import { ArrowLeft, ArrowRight, KeyRound, LockKeyhole, Mail, ShieldCheck } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { api, apiError } from '../api';
import Brand from '../components/Brand';
import { useUi } from '../context/UiContext';

const initialForm = {
    email: '',
    otp: '',
    reset_token: '',
    password: '',
    password_confirmation: '',
};

export default function ForgotPasswordOtpPage() {
    const navigate = useNavigate();
    const { toast } = useUi();
    const [step, setStep] = useState('email');
    const [form, setForm] = useState(initialForm);
    const [loading, setLoading] = useState(false);

    const update = (event) => {
        const { name, value } = event.target;
        setForm((current) => ({
            ...current,
            [name]: name === 'otp' ? value.replace(/\D/g, '').slice(0, 6) : value,
        }));
    };

    const sendOtp = async () => {
        await api.forgotPassword(form.email);
        setStep('otp');
        toast('Nếu email tồn tại, mã OTP đã được gửi.');
    };

    const verifyOtp = async () => {
        const response = await api.verifyPasswordOtp(form.email, form.otp);
        setForm((current) => ({
            ...current,
            reset_token: response.data.data.reset_token,
        }));
        setStep('reset');
        toast('Xác thực OTP thành công.');
    };

    const resetPassword = async () => {
        await api.resetPassword({
            email: form.email,
            reset_token: form.reset_token,
            password: form.password,
            password_confirmation: form.password_confirmation,
        });
        toast('Đặt lại mật khẩu thành công.');
        navigate('/login');
    };

    const submit = async (event) => {
        event.preventDefault();
        setLoading(true);

        try {
            if (step === 'email') await sendOtp();
            if (step === 'otp') await verifyOtp();
            if (step === 'reset') await resetPassword();
        } catch (error) {
            toast(apiError(error), 'error');
        } finally {
            setLoading(false);
        }
    };

    const resend = async () => {
        setLoading(true);
        try {
            await sendOtp();
            setForm((current) => ({ ...current, otp: '' }));
        } catch (error) {
            toast(apiError(error), 'error');
        } finally {
            setLoading(false);
        }
    };

    const copy = {
        email: ['Quên mật khẩu?', 'Nhập email đã đăng ký để nhận mã OTP 6 số.', 'Gửi mã OTP'],
        otp: ['Xác thực OTP', 'Nhập mã đã gửi đến email. Mã có hiệu lực trong 10 phút.', 'Xác thực mã'],
        reset: ['Tạo mật khẩu mới', 'Mật khẩu mới cần có ít nhất 8 ký tự.', 'Đặt lại mật khẩu'],
    }[step];

    return (
        <main className="auth-switch-page">
            <div className="auth-orb auth-orb--one" />
            <div className="auth-orb auth-orb--two" />
            <form className="auth-reset-card" onSubmit={submit}>
                <Brand />
                <span className="auth-kicker mt-8 block">Bảo mật tài khoản</span>
                <h1>{copy[0]}</h1>
                <p>{copy[1]}</p>

                <div className="auth-form-fields mt-8">
                    <AuthField icon={<Mail size={20} />}>
                        <input
                            required
                            autoComplete="email"
                            name="email"
                            placeholder="Email của bạn"
                            readOnly={step !== 'email'}
                            type="email"
                            value={form.email}
                            onChange={update}
                        />
                    </AuthField>

                    {step === 'otp' && (
                        <AuthField icon={<KeyRound size={20} />}>
                            <input
                                required
                                autoComplete="one-time-code"
                                inputMode="numeric"
                                maxLength="6"
                                minLength="6"
                                name="otp"
                                pattern="[0-9]{6}"
                                placeholder="Nhập mã OTP gồm 6 số"
                                value={form.otp}
                                onChange={update}
                            />
                        </AuthField>
                    )}

                    {step === 'reset' && (
                        <>
                            <AuthField icon={<LockKeyhole size={20} />}>
                                <input
                                    required
                                    autoComplete="new-password"
                                    minLength="8"
                                    name="password"
                                    placeholder="Mật khẩu mới"
                                    type="password"
                                    value={form.password}
                                    onChange={update}
                                />
                            </AuthField>
                            <AuthField icon={<ShieldCheck size={20} />}>
                                <input
                                    required
                                    autoComplete="new-password"
                                    minLength="8"
                                    name="password_confirmation"
                                    placeholder="Xác nhận mật khẩu mới"
                                    type="password"
                                    value={form.password_confirmation}
                                    onChange={update}
                                />
                            </AuthField>
                        </>
                    )}

                    {step === 'otp' && (
                        <button className="auth-back-button" disabled={loading} type="button" onClick={resend}>
                            Gửi lại mã OTP
                        </button>
                    )}

                    <button className="auth-submit-button" disabled={loading}>
                        {loading ? 'Đang xử lý...' : copy[2]} {!loading && <ArrowRight size={18} />}
                    </button>
                    <Link className="auth-back-button" to="/login">
                        <ArrowLeft size={16} /> Quay lại đăng nhập
                    </Link>
                </div>
            </form>
        </main>
    );
}

function AuthField({ icon, children }) {
    return (
        <label className="auth-input">
            <span className="auth-input__icon">{icon}</span>
            {children}
        </label>
    );
}
