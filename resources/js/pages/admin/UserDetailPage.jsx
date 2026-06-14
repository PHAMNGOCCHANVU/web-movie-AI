import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { api } from '../../api';
import { useAdminToast } from './AdminLayout';
import AdminBadge from '../../components/admin/AdminBadge';
import { ArrowLeft, User as UserIcon, Calendar, CreditCard, Shield } from 'lucide-react';

export default function UserDetailPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { showToast } = useAdminToast();

    const [user, setUser] = useState(null);
    const [plans, setPlans] = useState([]);
    const [loading, setLoading] = useState(true);

    const [subForm, setSubForm] = useState({ plan_code: '', status: 'active', expires_at: '', auto_renew: false });
    const [roleForm, setRoleForm] = useState('');
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        const fetchUser = async () => {
            try {
                const [res, plansRes] = await Promise.all([api.getAdminUser(id), api.plans()]);
                const data = res.data?.data || res.data;
                setUser(data);
                setPlans(plansRes.data?.data || []);

                setRoleForm(data.role?.name || 'user');

                if (data.subscription) {
                    setSubForm({
                        plan_code: data.subscription.plan_code || '',
                        status: data.subscription.status || 'active',
                        expires_at: data.subscription.expires_at ? new Date(data.subscription.expires_at).toISOString().slice(0,16) : '',
                        auto_renew: data.subscription.auto_renew || false
                    });
                }
            } catch (err) {
                showToast('error', 'Lỗi tải thông tin người dùng');
            } finally {
                setLoading(false);
            }
        };
        fetchUser();
    }, [id]);

    const handleUpdateRole = async () => {
        try {
            setSaving(true);
            await api.updateUserRole(id, { role: roleForm });
            showToast('success', 'Đã cập nhật vai trò');
        } catch (err) {
            showToast('error', err.response?.data?.message || 'Có lỗi xảy ra');
        } finally {
            setSaving(false);
        }
    };

    const handleUpdateSubscription = async (e) => {
        e.preventDefault();
        try {
            setSaving(true);
            await api.updateUserSubscription(id, subForm);
            showToast('success', 'Đã cập nhật gói cước');
        } catch (err) {
            showToast('error', err.response?.data?.message || 'Có lỗi xảy ra');
        } finally {
            setSaving(false);
        }
    };

    if (loading) return <div className="text-white">Loading...</div>;
    if (!user) return <div className="text-white">Không tìm thấy người dùng</div>;

    return (
        <div className="max-w-4xl mx-auto space-y-6">
            <div className="flex items-center gap-4">
                <button onClick={() => navigate(-1)} className="p-2 bg-[#151B27] border border-white/10 rounded-lg text-zinc-400 hover:text-white transition-colors">
                    <ArrowLeft size={20} />
                </button>
                <h1 className="text-2xl font-bold text-white">Chi tiết Người dùng</h1>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {/* User Info */}
                <div className="md:col-span-1 space-y-6">
                    <div className="bg-[#151B27] border border-white/10 rounded-xl p-6 flex flex-col items-center text-center">
                        <div className="w-24 h-24 rounded-full bg-zinc-800 flex items-center justify-center text-4xl font-bold text-white mb-4">
                            {user.name?.charAt(0).toUpperCase() || 'U'}
                        </div>
                        <h2 className="text-xl font-bold text-white">{user.name}</h2>
                        <p className="text-zinc-400 mb-4">{user.email}</p>
                        <div className="flex gap-2">
                            <AdminBadge status={user.role?.name === 'admin' ? 'premium' : 'free'} text={user.role?.name?.toUpperCase()} />
                            <AdminBadge status={user.status} />
                        </div>
                    </div>

                    <div className="bg-[#151B27] border border-white/10 rounded-xl p-6">
                        <h3 className="text-lg font-semibold text-white mb-4 flex items-center gap-2"><Shield size={18}/> Đổi vai trò</h3>
                        <div className="flex gap-2">
                            <select
                                value={roleForm}
                                onChange={(e) => setRoleForm(e.target.value)}
                                className="flex-1 bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#E50914]"
                            >
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                            <button
                                onClick={handleUpdateRole}
                                disabled={saving}
                                className="bg-[#E50914] hover:bg-[#E50914]/90 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                            >
                                Lưu
                            </button>
                        </div>
                    </div>
                </div>

                {/* Subscription Info */}
                <div className="md:col-span-2 space-y-6">
                    <div className="bg-[#151B27] border border-white/10 rounded-xl p-6">
                        <h3 className="text-lg font-semibold text-white mb-4 flex items-center gap-2"><CreditCard size={18}/> Gói cước hiện tại</h3>
                        {user.subscription ? (
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <span className="block text-sm text-zinc-500">Gói</span>
                                    <span className="font-medium text-white">{user.subscription.plan?.name || user.subscription.plan_code}</span>
                                </div>
                                <div>
                                    <span className="block text-sm text-zinc-500">Trạng thái</span>
                                    <AdminBadge status={user.subscription.status} />
                                </div>
                                <div>
                                    <span className="block text-sm text-zinc-500">Ngày hết hạn</span>
                                    <span className="text-white">{new Date(user.subscription.expires_at).toLocaleString('vi-VN')}</span>
                                </div>
                            </div>
                        ) : (
                            <p className="text-zinc-500">Người dùng chưa đăng ký gói cước nào.</p>
                        )}
                    </div>

                    <div className="bg-[#151B27] border border-white/10 rounded-xl p-6">
                        <h3 className="text-lg font-semibold text-white mb-4">Ghi đè Gói cước (Admin Override)</h3>
                        <form onSubmit={handleUpdateSubscription} className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-zinc-400 mb-1">Mã gói cước</label>
                                    <select
                                        value={subForm.plan_code}
                                        onChange={(e) => setSubForm({...subForm, plan_code: e.target.value})}
                                        className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#E50914]"
                                    >
                                        <option value="">-- Không chọn gói --</option>
                                        {plans.map((plan) => (
                                            <option key={plan.id} value={plan.plan_code}>
                                                {plan.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-zinc-400 mb-1">Trạng thái</label>
                                    <select
                                        value={subForm.status}
                                        onChange={(e) => setSubForm({...subForm, status: e.target.value})}
                                        className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#E50914]"
                                    >
                                        <option value="active">Active</option>
                                        <option value="expired">Expired</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-zinc-400 mb-1">Ngày hết hạn</label>
                                    <input
                                        type="datetime-local"
                                        value={subForm.expires_at}
                                        onChange={(e) => setSubForm({...subForm, expires_at: e.target.value})}
                                        className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#E50914]"
                                    />
                                </div>
                                <div className="flex items-center pt-6">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={subForm.auto_renew}
                                            onChange={(e) => setSubForm({...subForm, auto_renew: e.target.checked})}
                                            className="w-4 h-4 rounded bg-[#101521] border-white/10 text-[#E50914] focus:ring-[#E50914]"
                                        />
                                        <span className="text-sm font-medium text-white">Tự động gia hạn</span>
                                    </label>
                                </div>
                            </div>
                            <div className="pt-4 border-t border-white/10 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={saving}
                                    className="bg-[#10B981] hover:bg-[#10B981]/90 text-white px-6 py-2.5 rounded-lg font-medium transition-colors"
                                >
                                    Cập nhật Gói cước
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}
