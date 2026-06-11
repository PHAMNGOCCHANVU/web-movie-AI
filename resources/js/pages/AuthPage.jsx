import React, { useEffect, useState } from 'react';
import {
    ArrowLeft,
    ArrowRight,
    Clapperboard,
    Eye,
    EyeOff,
    LockKeyhole,
    Mail,
    ShieldCheck,
    Sparkles,
    UserRound,
} from 'lucide-react';
import { Navigate, useLocation, useNavigate } from 'react-router-dom';
import { api, apiError } from '../api';
import Brand from '../components/Brand';
import { useAuth } from '../context/AuthContext';
import { useUi } from '../context/UiContext';

export default function AuthPage({ mode = 'login' }) {
    const { isAuthenticated, login, register, verifyRegistrationOtp } = useAuth();
    const { toast } = useUi();
    const navigate = useNavigate();
    const location = useLocation();
    const [activeMode, setActiveMode] = useState(mode);
    const [forgotMode, setForgotMode] = useState(false);
    const [loading, setLoading] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [registrationOtpStep, setRegistrationOtpStep] = useState(false);
    const [form, setForm] = useState({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        otp: '',
    });

    useEffect(() => {
        setActiveMode(mode);
        setForgotMode(false);
        setRegistrationOtpStep(false);
    }, [mode]);

    if (isAuthenticated) {
        return <Navigate replace to="/" />;
    }

    const isLogin = activeMode === 'login';

    const switchMode = (nextMode) => {
        setForgotMode(false);
        setRegistrationOtpStep(false);
        setActiveMode(nextMode);
        window.setTimeout(() => navigate(nextMode === 'login' ? '/login' : '/register', { replace: true }), 350);
    };

    const update = (event) => {
        setForm((current) => ({ ...current, [event.target.name]: event.target.value }));
    };

    const submit = async (event) => {
        event.preventDefault();
        setLoading(true);

        try {
            if (forgotMode) {
                await api.forgotPassword(form.email);
                toast('Đã gửi hướng dẫn đặt lại mật khẩu. Hãy kiểm tra email.');
                setForgotMode(false);
                return;
            }

            if (isLogin) {
                await login({ email: form.email, password: form.password });
                toast('Đăng nhập thành công.');
            } else if (registrationOtpStep) {
                await verifyRegistrationOtp(form.email, form.otp);
                toast('Xác thực email và đăng ký thành công.');
            } else {
                await register(form);
                setRegistrationOtpStep(true);
                toast('Mã OTP đã được gửi đến email của bạn.');
                return;
            }

            navigate(location.state?.from?.pathname || '/');
        } catch (error) {
            toast(apiError(error), 'error');
        } finally {
            setLoading(false);
        }
    };

    const resendRegistrationOtp = async () => {
        setLoading(true);

        try {
            await register(form);
            toast('Đã gửi lại mã OTP mới.');
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

            <section className={`auth-switch-card ${isLogin ? '' : 'auth-switch-card--register'}`}>
                <div className="auth-form-track">
                    <AuthForm
                        forgotMode={forgotMode}
                        form={form}
                        isLogin={isLogin}
                        loading={loading}
                        registrationOtpStep={registrationOtpStep}
                        showPassword={showPassword}
                        onBack={() => setForgotMode(false)}
                        onForgot={() => navigate('/forgot-password')}
                        onChangeRegistrationEmail={() => {
                            setRegistrationOtpStep(false);
                            setForm((current) => ({ ...current, otp: '' }));
                        }}
                        onResendRegistrationOtp={resendRegistrationOtp}
                        onShowPassword={() => setShowPassword((value) => !value)}
                        onSubmit={submit}
                        onUpdate={update}
                        onSwitch={switchMode}
                    />
                </div>

                <div className="auth-switch-overlay">
                    <div className={`auth-overlay-content ${isLogin ? 'auth-overlay-content--visible' : 'auth-overlay-content--hidden-right'}`}>
                        <div className="auth-overlay-icon"><Sparkles size={30} /></div>
                        <span className="auth-kicker">Bắt đầu hành trình</span>
                        <h1>Chưa có tài khoản?</h1>
                        <p>
                            Tạo tài khoản CineON để lưu tủ phim, trò chuyện cùng AI và khám phá thế giới điện ảnh của riêng bạn.
                        </p>
                        <button className="auth-outline-button" onClick={() => switchMode('register')}>
                            Đăng ký ngay <ArrowRight size={17} />
                        </button>
                    </div>

                    <div className={`auth-overlay-content ${isLogin ? 'auth-overlay-content--hidden-left' : 'auth-overlay-content--visible'}`}>
                        <div className="auth-overlay-icon"><Clapperboard size={30} /></div>
                        <span className="auth-kicker">Chào mừng trở lại</span>
                        <h1>Bạn đã sẵn sàng?</h1>
                        <p>
                            Đăng nhập để tiếp tục phim đang xem, quản lý gói cước và nhận gợi ý mới từ trợ lý CineON.
                        </p>
                        <button className="auth-outline-button" onClick={() => switchMode('login')}>
                            <ArrowLeft size={17} /> Đăng nhập
                        </button>
                    </div>
                </div>
            </section>
        </main>
    );
}

function AuthForm({
    isLogin,
    forgotMode,
    form,
    loading,
    registrationOtpStep,
    showPassword,
    onUpdate,
    onSubmit,
    onForgot,
    onBack,
    onChangeRegistrationEmail,
    onResendRegistrationOtp,
    onShowPassword,
    onSwitch,
}) {
    return (
        <div className="auth-form-panel">
            <div className="auth-mobile-brand"><Brand /></div>

            <div className="auth-form-heading">
                <Brand />
                <h2>
                    {registrationOtpStep ? 'Xác thực email' : forgotMode ? 'Quên mật khẩu' : isLogin ? 'Đăng nhập CineON' : 'Tạo tài khoản'}
                </h2>
                <p>
                    {registrationOtpStep
                        ? `Nhập mã OTP 6 số đã gửi đến ${form.email}.`
                        : forgotMode
                        ? 'Nhập email đã đăng ký để nhận đường dẫn tạo mật khẩu mới.'
                        : isLogin
                            ? 'Đăng nhập để tiếp tục trải nghiệm xem phim.'
                            : 'Tạo tài khoản miễn phí và bắt đầu khám phá.'}
                </p>
            </div>

            <form className="auth-form-fields" onSubmit={onSubmit}>
                {!isLogin && !forgotMode && !registrationOtpStep && (
                    <AuthInput
                        autoComplete="name"
                        icon={<UserRound size={20} />}
                        name="name"
                        placeholder="Họ và tên"
                        value={form.name}
                        onChange={onUpdate}
                    />
                )}

                {!registrationOtpStep && (
                    <AuthInput
                        autoComplete="email"
                        icon={<Mail size={20} />}
                        name="email"
                        placeholder="Email của bạn"
                        type="email"
                        value={form.email}
                        onChange={onUpdate}
                    />
                )}

                {!forgotMode && !registrationOtpStep && (
                    <AuthInput
                        autoComplete={isLogin ? 'current-password' : 'new-password'}
                        icon={<LockKeyhole size={20} />}
                        minLength="8"
                        name="password"
                        placeholder="Mật khẩu"
                        type={showPassword ? 'text' : 'password'}
                        value={form.password}
                        onChange={onUpdate}
                        suffix={(
                            <button aria-label="Hiện hoặc ẩn mật khẩu" type="button" onClick={onShowPassword}>
                                {showPassword ? <EyeOff size={19} /> : <Eye size={19} />}
                            </button>
                        )}
                    />
                )}

                {!isLogin && !forgotMode && !registrationOtpStep && (
                    <AuthInput
                        autoComplete="new-password"
                        icon={<ShieldCheck size={20} />}
                        minLength="8"
                        name="password_confirmation"
                        placeholder="Xác nhận mật khẩu"
                        type={showPassword ? 'text' : 'password'}
                        value={form.password_confirmation}
                        onChange={onUpdate}
                    />
                )}

                {registrationOtpStep && (
                    <>
                        <AuthInput
                            autoComplete="one-time-code"
                            icon={<ShieldCheck size={20} />}
                            inputMode="numeric"
                            maxLength="6"
                            minLength="6"
                            name="otp"
                            pattern="[0-9]{6}"
                            placeholder="Nhập mã OTP 6 số"
                            value={form.otp}
                            onChange={onUpdate}
                        />
                        <button className="auth-back-button" type="button" onClick={onChangeRegistrationEmail}>
                            <ArrowLeft size={16} /> Đổi email đăng ký
                        </button>
                        <button className="auth-back-button" disabled={loading} type="button" onClick={onResendRegistrationOtp}>
                            Gửi lại mã OTP
                        </button>
                    </>
                )}

                {isLogin && !forgotMode && (
                    <div className="auth-form-options">
                        <label><input type="checkbox" /> Ghi nhớ đăng nhập</label>
                        <button type="button" onClick={onForgot}>Quên mật khẩu?</button>
                    </div>
                )}

                {forgotMode && (
                    <button className="auth-back-button" type="button" onClick={onBack}>
                        <ArrowLeft size={16} /> Quay lại đăng nhập
                    </button>
                )}

                <button className="auth-submit-button" disabled={loading}>
                    {loading
                        ? 'Đang xử lý...'
                        : registrationOtpStep
                            ? 'Xác thực và đăng ký'
                            : forgotMode
                            ? 'Gửi hướng dẫn'
                            : isLogin
                                ? 'Đăng nhập'
                                : 'Đăng ký'}
                    {!loading && <ArrowRight size={18} />}
                </button>
            </form>

            {!forgotMode && (
                <div className="auth-mobile-switch">
                    <span>{isLogin ? 'Chưa có tài khoản?' : 'Đã có tài khoản?'}</span>
                    <button onClick={() => onSwitch(isLogin ? 'register' : 'login')}>
                        {isLogin ? 'Đăng ký ngay' : 'Đăng nhập'}
                    </button>
                </div>
            )}
        </div>
    );
}

function AuthInput({ icon, suffix, ...inputProps }) {
    return (
        <label className="auth-input">
            <span className="auth-input__icon">{icon}</span>
            <input required {...inputProps} />
            {suffix && <span className="auth-input__suffix">{suffix}</span>}
        </label>
    );
}
