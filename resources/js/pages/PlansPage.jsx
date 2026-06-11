import React, { useEffect, useMemo, useState } from 'react';
import { Check, Crown, ShieldCheck, Sparkles, X } from 'lucide-react';
import { api, apiError } from '../api';
import Loading from '../components/Loading';
import { useAuth } from '../context/AuthContext';
import { useUi } from '../context/UiContext';
import { formatMoney } from '../utils';

export default function PlansPage() {
    const { user } = useAuth();
    const { toast, openModal, closeModal } = useUi();
    const [plans, setPlans] = useState([]);
    const [subscription, setSubscription] = useState(null);
    const [cycle, setCycle] = useState('monthly');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        Promise.all([api.plans(), api.subscription()])
            .then(([plansResponse, subscriptionResponse]) => {
                setPlans(plansResponse.data.data || []);
                setSubscription(subscriptionResponse.data.data);
            })
            .catch((error) => toast(apiError(error), 'error'))
            .finally(() => setLoading(false));
    }, [toast]);

    const visiblePlans = useMemo(() => ({
        standard: plans.find((plan) => plan.plan_code === `standard_${cycle}`),
        vip: plans.find((plan) => plan.plan_code === `vip_${cycle}`),
    }), [plans, cycle]);

    const transactionType = (plan) => {
        if (!subscription?.is_active) return 'purchase';
        if (subscription?.user?.subscription_plan_id === plan.id) return 'renewal';
        const currentCode = subscription?.user?.subscription_plan?.plan_code || '';
        if (currentCode.startsWith('standard') && plan.plan_code.startsWith('vip')) return 'upgrade';
        return null;
    };

    const checkout = (plan) => {
        const type = transactionType(plan);
        if (!type) {
            toast('Backend chỉ hỗ trợ gia hạn đúng gói hiện tại hoặc nâng cấp từ Standard lên VIP.', 'error');
            return;
        }
        openModal(
            <div>
                <span className="eyebrow">Xác nhận thanh toán</span>
                <h2 className="mt-3 text-2xl font-bold">{plan.name}</h2>
                <dl className="info-list mt-6">
                    <div><dt>Chu kỳ</dt><dd>{cycle === 'monthly' ? 'Hàng tháng' : 'Hàng năm'}</dd></div>
                    <div><dt>Số tiền</dt><dd className="text-amber-300">{formatMoney(plan.price)}</dd></div>
                    <div><dt>Thời hạn</dt><dd>{plan.duration_days} ngày</dd></div>
                    <div><dt>Thanh toán</dt><dd>VNPay Sandbox</dd></div>
                </dl>
                <button
                    className="button button--vip mt-7 w-full"
                    onClick={async () => {
                        try {
                            const response = await api.createPayment({
                                plan_code: plan.plan_code,
                                billing_cycle: cycle,
                                transaction_type: type,
                            });
                            sessionStorage.setItem('cineon_payment_id', response.data.data.transaction_id);
                            window.location.href = response.data.data.payment_url;
                        } catch (error) {
                            closeModal();
                            toast(apiError(error), 'error');
                        }
                    }}
                >
                    Thanh toán VNPay
                </button>
            </div>,
        );
    };

    if (loading) return <Loading label="Đang tải gói cước..." />;

    return (
        <div className="site-container py-10">
            <div className="mx-auto max-w-2xl text-center">
                <span className="eyebrow">Mở khóa trải nghiệm</span>
                <h1 className="page-title">Chọn gói xem phim</h1>
                <p className="page-subtitle">Standard cho phim thường, VIP cho toàn bộ kho phim premium.</p>
            </div>

            <div className="cycle-toggle mx-auto mt-8">
                <button className={cycle === 'monthly' ? 'active' : ''} onClick={() => setCycle('monthly')}>Hàng tháng</button>
                <button className={cycle === 'yearly' ? 'active' : ''} onClick={() => setCycle('yearly')}>Hàng năm <span>-20%</span></button>
            </div>

            <div className="mx-auto mt-10 grid max-w-5xl gap-6 md:grid-cols-2">
                <PlanCard
                    icon={<ShieldCheck />}
                    plan={visiblePlans.standard}
                    type="standard"
                    current={user.subscription_plan_id === visiblePlans.standard?.id}
                    features={[
                        ['Xem toàn bộ phim thường', true],
                        ['Chat AI gợi ý phim', true],
                        ['Tủ phim và lịch sử xem', true],
                        ['Xem phim VIP', false],
                    ]}
                    onChoose={checkout}
                />
                <PlanCard
                    featured
                    icon={<Crown />}
                    plan={visiblePlans.vip}
                    type="vip"
                    current={user.subscription_plan_id === visiblePlans.vip?.id}
                    features={[
                        ['Xem toàn bộ phim', true],
                        ['Mở khóa phim VIP', true],
                        ['Chat AI gợi ý phim', true],
                        ['Tủ phim và lịch sử xem', true],
                    ]}
                    onChoose={checkout}
                />
            </div>

            <div className="mx-auto mt-10 max-w-5xl rounded-2xl border border-white/8 bg-white/[0.025] p-5 text-sm text-zinc-500">
                <Sparkles className="mr-2 inline text-amber-300" size={17} />
                Tài khoản hiện tại: <strong className="text-white">{subscription?.is_active ? subscription.user.subscription_plan?.name : 'Free'}</strong>.
                Thanh toán được thực hiện qua môi trường VNPay Sandbox.
            </div>
        </div>
    );
}

function PlanCard({ plan, type, icon, features, featured = false, current = false, onChoose }) {
    if (!plan) return null;

    return (
        <article className={`plan-card ${featured ? 'plan-card--featured' : ''}`}>
            {featured && <span className="plan-card__popular">Phổ biến nhất</span>}
            <div className={`plan-icon plan-icon--${type}`}>{icon}</div>
            <p className="mt-6 text-sm font-bold uppercase tracking-[0.2em] text-zinc-400">{type}</p>
            <h2 className="mt-3 text-3xl font-extrabold">{plan.name}</h2>
            <div className="mt-5"><strong className="text-4xl">{formatMoney(plan.price)}</strong><span className="text-zinc-500"> / {plan.billing_cycle_type === 'monthly' ? 'tháng' : 'năm'}</span></div>
            <ul className="mt-8 space-y-4">
                {features.map(([label, enabled]) => (
                    <li className="flex items-center gap-3 text-sm text-zinc-300" key={label}>
                        {enabled ? <Check className="text-emerald-400" size={18} /> : <X className="text-zinc-600" size={18} />}
                        {label}
                    </li>
                ))}
            </ul>
            <button className={`button mt-9 w-full ${featured ? 'button--vip' : 'button--secondary'}`} disabled={current} onClick={() => onChoose(plan)}>
                {current ? 'Gói hiện tại' : `Chọn ${type === 'vip' ? 'VIP' : 'Standard'}`}
            </button>
        </article>
    );
}
