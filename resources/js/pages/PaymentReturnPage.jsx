import React, { useEffect, useMemo, useState } from 'react';
import { CheckCircle2, Clock3, Home, LoaderCircle, ReceiptText, XCircle } from 'lucide-react';
import { Link } from 'react-router-dom';
import { api } from '../api';
import { useAuth } from '../context/AuthContext';

const RESULT_CONTENT = {
    checking: {
        icon: <LoaderCircle className="animate-spin text-amber-300" size={58} />,
        eyebrow: 'Đang xác nhận giao dịch',
        title: 'CineON đang kiểm tra thanh toán',
        description: 'Vui lòng chờ trong giây lát để hệ thống đồng bộ kết quả từ VNPAY.',
    },
    success: {
        icon: <CheckCircle2 className="text-emerald-400" size={58} />,
        eyebrow: 'Thanh toán thành công',
        title: 'Gói cước đã được kích hoạt',
        description: 'Cảm ơn bạn đã đăng ký. Bây giờ bạn có thể quay lại trang chủ và tiếp tục xem phim.',
    },
    failed: {
        icon: <XCircle className="text-red-400" size={58} />,
        eyebrow: 'Thanh toán thất bại',
        title: 'Giao dịch chưa thành công',
        description: 'Giao dịch bị hủy hoặc ngân hàng chưa chấp nhận thanh toán. Bạn có thể thử lại với gói cước mong muốn.',
    },
    pending: {
        icon: <Clock3 className="text-amber-300" size={58} />,
        eyebrow: 'Đang xử lý',
        title: 'Giao dịch đang chờ cập nhật',
        description: 'Hệ thống đã nhận thông tin giao dịch, nhưng trạng thái cuối cùng chưa sẵn sàng. Hãy kiểm tra lại tài khoản sau ít phút.',
    },
    invalid: {
        icon: <XCircle className="text-red-400" size={58} />,
        eyebrow: 'Dữ liệu không hợp lệ',
        title: 'Không thể xác minh giao dịch',
        description: 'Chữ ký hoặc thông tin trả về từ VNPAY không hợp lệ. Vui lòng tạo giao dịch mới để thử lại.',
    },
    unknown: {
        icon: <ReceiptText className="text-zinc-400" size={58} />,
        eyebrow: 'Không tìm thấy giao dịch',
        title: 'Chưa có dữ liệu thanh toán',
        description: 'Trang này cần thông tin trả về từ VNPAY. Hãy quay lại trang gói cước nếu bạn muốn thanh toán lại.',
    },
};

export default function PaymentReturnPage() {
    const { refreshUser } = useAuth();
    const [status, setStatus] = useState('checking');
    const [detail, setDetail] = useState(null);
    const returnParams = useMemo(() => collectVnPayReturnParams(), []);

    useEffect(() => {
        let isMounted = true;

        const verifyPayment = async () => {
            if (!returnParams.vnp_TxnRef) {
                setStatus('unknown');
                return;
            }

            try {
                const response = await api.verifyPaymentReturn(returnParams);
                const result = response.data?.data || {};

                if (!isMounted) return;

                setDetail({
                    txnRef: result.vnp_txn_ref || returnParams.vnp_TxnRef,
                    responseCode: result.vnp_response_code || returnParams.vnp_ResponseCode,
                    transactionStatus: result.vnp_transaction_status || returnParams.vnp_TransactionStatus,
                    transactionId: result.transaction_id,
                });

                if (result.is_valid === false) {
                    setStatus('invalid');
                    return;
                }

                if (result.is_success && result.status === 'success') {
                    sessionStorage.removeItem('cineon_payment_id');
                    setStatus('success');

                    refreshUser().catch(() => {
                        // Payment is already verified. User data can refresh on next page load.
                    });
                    return;
                }

                if (result.status === 'pending') {
                    setStatus('pending');
                    return;
                }

                setStatus('failed');
            } catch (error) {
                if (!isMounted) return;

                const responseCode = returnParams.vnp_ResponseCode;
                setDetail({
                    txnRef: returnParams.vnp_TxnRef,
                    responseCode,
                    transactionStatus: returnParams.vnp_TransactionStatus,
                });

                setStatus(responseCode === '00' ? 'pending' : 'failed');
            }
        };

        verifyPayment();

        return () => {
            isMounted = false;
        };
    }, [refreshUser, returnParams]);

    const content = RESULT_CONTENT[status] || RESULT_CONTENT.unknown;

    return (
        <div className="site-container flex min-h-[72vh] items-center justify-center py-12">
            <div className="panel w-full max-w-2xl overflow-hidden text-center">
                <div className="border-b border-white/10 bg-white/[0.03] px-8 py-10">
                    <div className="flex justify-center">{content.icon}</div>
                    <p className="mt-6 text-xs font-bold uppercase tracking-[0.35em] text-rose-300">
                        {content.eyebrow}
                    </p>
                    <h1 className="mt-3 text-3xl font-black text-white md:text-4xl">{content.title}</h1>
                    <p className="mx-auto mt-4 max-w-xl text-base leading-7 text-zinc-400">{content.description}</p>
                </div>

                {detail && (
                    <div className="px-8 py-6 text-left text-sm text-zinc-400">
                        <PaymentMeta label="Mã giao dịch" value={detail.txnRef} />
                    </div>
                )}

                <div className="flex flex-col gap-3 border-t border-white/10 px-8 py-7 sm:flex-row sm:justify-center">
                    <Link className="button button--primary inline-flex items-center justify-center gap-2" to="/">
                        <Home size={18} />
                        Về trang chủ
                    </Link>
                    <Link className="button button--ghost inline-flex items-center justify-center gap-2" to="/account">
                        Xem tài khoản
                    </Link>
                    {status !== 'success' && (
                        <Link className="button button--ghost inline-flex items-center justify-center gap-2" to="/plans">
                            Thử lại gói cước
                        </Link>
                    )}
                </div>
            </div>
        </div>
    );
}

function PaymentMeta({ label, value }) {
    return (
        <div className="rounded-2xl border border-white/10 bg-black/20 p-4">
            <p className="text-xs uppercase tracking-[0.2em] text-zinc-500">{label}</p>
            <p className="mt-2 break-all font-semibold text-white">{value}</p>
        </div>
    );
}

function collectVnPayReturnParams() {
    const params = new URLSearchParams(window.location.search);
    const hash = window.location.hash || '';
    const hashQueryIndex = hash.indexOf('?');

    if (hashQueryIndex >= 0) {
        new URLSearchParams(hash.slice(hashQueryIndex + 1)).forEach((value, key) => {
            params.set(key, value);
        });
    }

    return Object.fromEntries(params.entries());
}
