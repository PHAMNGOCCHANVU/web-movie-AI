import React, { useEffect, useState } from 'react';
import { CreditCard, KeyRound, Save, UserRound } from 'lucide-react';
import { Link, useSearchParams } from 'react-router-dom';
import { api, apiError } from '../api';
import Loading from '../components/Loading';
import { useAuth } from '../context/AuthContext';
import { useUi } from '../context/UiContext';
import { formatDate } from '../utils';

export default function AccountPage() {
    const { refreshUser } = useAuth();
    const { toast } = useUi();
    const [searchParams, setSearchParams] = useSearchParams();
    const requestedTab = searchParams.get('tab');
    const [tab, setTab] = useState(['profile', 'password', 'subscription'].includes(requestedTab) ? requestedTab : 'profile');
    const [profile, setProfile] = useState(null);
    const [subscription, setSubscription] = useState(null);
    const [loading, setLoading] = useState(true);
    const [passwords, setPasswords] = useState({
        current_password: '',
        new_password: '',
        new_password_confirmation: '',
    });

    const load = async () => {
        const [profileResponse, subscriptionResponse] = await Promise.all([api.profile(), api.subscription()]);
        setProfile(profileResponse.data.data);
        setSubscription(subscriptionResponse.data.data);
        setLoading(false);
    };

    useEffect(() => { load().catch((error) => toast(apiError(error), 'error')); }, []);
    useEffect(() => {
        if (['profile', 'password', 'subscription'].includes(requestedTab)) {
            setTab(requestedTab);
        }
    }, [requestedTab]);

    const selectTab = (nextTab) => {
        setTab(nextTab);
        setSearchParams({ tab: nextTab });
    };

    const updateProfile = async (event) => {
        event.preventDefault();
        try {
            const response = await api.updateProfile({
                name: profile.name,
                phone: profile.phone || null,
            });
            setProfile(response.data.data);
            await refreshUser();
            toast('Đã cập nhật thông tin cá nhân.');
        } catch (error) {
            toast(apiError(error), 'error');
        }
    };

    const changePassword = async (event) => {
        event.preventDefault();
        try {
            await api.changePassword(passwords);
            setPasswords({ current_password: '', new_password: '', new_password_confirmation: '' });
            toast('Đổi mật khẩu thành công.');
        } catch (error) {
            toast(apiError(error), 'error');
        }
    };

    const cancelSubscription = async () => {
        try {
            await api.cancelSubscription('Người dùng hủy từ trang tài khoản');
            await load();
            toast('Đã tắt tự động gia hạn.');
        } catch (error) {
            toast(apiError(error), 'error');
        }
    };

    if (loading || !profile) return <Loading />;

    return (
        <div className="site-container py-8">
            <div className="grid gap-6 lg:grid-cols-[260px_1fr]">
                <aside className="panel h-fit p-3">
                    <AccountTab active={tab === 'profile'} icon={<UserRound />} label="Thông tin cá nhân" onClick={() => selectTab('profile')} />
                    <AccountTab active={tab === 'password'} icon={<KeyRound />} label="Đổi mật khẩu" onClick={() => selectTab('password')} />
                    <AccountTab active={tab === 'subscription'} icon={<CreditCard />} label="Gói cước hiện tại" onClick={() => selectTab('subscription')} />
                </aside>

                <section className="panel p-6 md:p-8">
                    {tab === 'profile' && (
                        <form className="max-w-xl" onSubmit={updateProfile}>
                            <h2 className="text-2xl font-bold">Thông tin cá nhân</h2>
                            <div className="mt-7 space-y-5">
                                <label className="form-field"><span>Họ và tên</span><input className="input" value={profile.name} onChange={(event) => setProfile({ ...profile, name: event.target.value })} /></label>
                                <label className="form-field"><span>Email</span><input className="input opacity-60" disabled value={profile.email} /></label>
                                <label className="form-field"><span>Số điện thoại</span><input className="input" placeholder="Ví dụ: 0901234567" value={profile.phone || ''} onChange={(event) => setProfile({ ...profile, phone: event.target.value })} /></label>
                                <button className="button button--primary"><Save size={17} /> Lưu thay đổi</button>
                            </div>
                        </form>
                    )}

                    {tab === 'password' && (
                        <form className="max-w-xl" onSubmit={changePassword}>
                            <h2 className="text-2xl font-bold">Đổi mật khẩu</h2>
                            <div className="mt-7 space-y-5">
                                <label className="form-field"><span>Mật khẩu hiện tại</span><input className="input" required type="password" value={passwords.current_password} onChange={(event) => setPasswords({ ...passwords, current_password: event.target.value })} /></label>
                                <label className="form-field"><span>Mật khẩu mới</span><input className="input" required minLength="8" type="password" value={passwords.new_password} onChange={(event) => setPasswords({ ...passwords, new_password: event.target.value })} /></label>
                                <label className="form-field"><span>Nhập lại mật khẩu mới</span><input className="input" required minLength="8" type="password" value={passwords.new_password_confirmation} onChange={(event) => setPasswords({ ...passwords, new_password_confirmation: event.target.value })} /></label>
                                <button className="button button--primary"><KeyRound size={17} /> Đổi mật khẩu</button>
                            </div>
                        </form>
                    )}

                    {tab === 'subscription' && (
                        <div>
                            <h2 className="text-2xl font-bold">Gói cước hiện tại</h2>
                            <div className="subscription-summary mt-7">
                                <div>
                                    <span className="eyebrow">{subscription.is_vip ? 'VIP' : subscription.is_active ? 'STANDARD' : 'FREE'}</span>
                                    <h3 className="mt-2 text-3xl font-extrabold">
                                        {subscription.user.subscription_plan?.name || 'Tài khoản miễn phí'}
                                    </h3>
                                    <p className="mt-3 text-zinc-500">
                                        {subscription.is_active
                                            ? `Còn ${subscription.days_remaining} ngày sử dụng`
                                            : 'Đăng ký Standard hoặc VIP để xem phim full.'}
                                    </p>
                                </div>
                                <span className={`status-pill ${subscription.is_active ? 'status-pill--active' : ''}`}>
                                    {subscription.is_active ? 'Đang hoạt động' : 'Chưa đăng ký'}
                                </span>
                            </div>
                            {subscription.is_active && (
                                <dl className="info-list mt-7 max-w-xl">
                                    <div><dt>Ngày bắt đầu</dt><dd>{formatDate(subscription.user.subscription_starts_at)}</dd></div>
                                    <div><dt>Ngày hết hạn</dt><dd>{formatDate(subscription.user.subscription_expires_at)}</dd></div>
                                    <div><dt>Chu kỳ</dt><dd>{subscription.user.subscription_plan?.billing_cycle_type === 'yearly' ? 'Hàng năm' : 'Hàng tháng'}</dd></div>
                                    <div><dt>Tự động gia hạn</dt><dd>{subscription.user.auto_renew ? 'Bật' : 'Đã tắt'}</dd></div>
                                </dl>
                            )}
                            <div className="mt-8 flex flex-wrap gap-3">
                                <Link className="button button--vip" to="/plans">{subscription.is_vip ? 'Gia hạn gói' : 'Xem gói cước'}</Link>
                                {subscription.is_active && subscription.user.auto_renew && (
                                    <button className="button button--ghost" onClick={cancelSubscription}>Hủy gia hạn</button>
                                )}
                            </div>
                        </div>
                    )}
                </section>
            </div>
        </div>
    );
}

function AccountTab({ active, icon, label, onClick }) {
    return <button className={`account-tab ${active ? 'account-tab--active' : ''}`} onClick={onClick}>{icon}{label}</button>;
}
