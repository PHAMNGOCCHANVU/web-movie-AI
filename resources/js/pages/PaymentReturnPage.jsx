import React, { useEffect, useState } from 'react';
import { CheckCircle2, LoaderCircle, XCircle } from 'lucide-react';
import { Link } from 'react-router-dom';
import { api } from '../api';
import { useAuth } from '../context/AuthContext';

export default function PaymentReturnPage() {
    const { refreshUser } = useAuth();
    const [status, setStatus] = useState('checking');

    useEffect(() => {
        const returnParams = collectVnPayReturnParams();
        const transactionId = sessionStorage.getItem('cineon_payment_id');
        if (!transactionId && !returnParams.vnp_TxnRef) {
            setStatus('unknown');
            return;
        }

        let attempts = 0;
        const check = async () => {
            attempts += 1;
            try {
                if (attempts === 1 && returnParams.vnp_TxnRef) {
                    const verifyResponse = await api.verifyPaymentReturn(returnParams);
                    const result = verifyResponse.data.data;
                    if (result.transaction_id) {
                        sessionStorage.setItem('cineon_payment_id', result.transaction_id);
                    }
                    if (result.is_success && result.status === 'success') {
                        await refreshUser();
                        setStatus('success');
                        sessionStorage.removeItem('cineon_payment_id');
                        return;
                    }
                    if (!result.is_success || result.status === 'failed') {
                        setStatus('failed');
                        return;
                    }
                }

                const currentTransactionId = sessionStorage.getItem('cineon_payment_id') || transactionId;
                if (!currentTransactionId) {
                    setStatus('unknown');
                    return;
                }

                const response = await api.paymentStatus(currentTransactionId);
                const paymentStatus = response.data.data.status;
                if (paymentStatus === 'success') {
                    await refreshUser();
                    setStatus('success');
                    sessionStorage.removeItem('cineon_payment_id');
                    return;
                }
                if (paymentStatus === 'failed' || attempts >= 8) {
                    setStatus(paymentStatus === 'failed' ? 'failed' : 'pending');
                    return;
                }
                window.setTimeout(check, 2000);
            } catch {
                setStatus('unknown');
            }
        };
        check();
    }, [refreshUser]);

    const content = {
        checking: [<LoaderCircle className="animate-spin text-amber-300" size={54} />, 'Đang xác nhận giao dịch', 'CineON đang đồng bộ trạng thái từ VNPay.'],
        success: [<CheckCircle2 className="text-emerald-400" size={54} />, 'Thanh toán thành công', 'Gói cước đã được kích hoạt cho tài khoản của bạn.'],
        failed: [<XCircle className="text-red-400" size={54} />, 'Thanh toán thất bại', 'Giao dịch không thành công hoặc đã bị hủy.'],
        pending: [<LoaderCircle className="text-amber-300" size={54} />, 'Giao dịch đang xử lý', 'Vui lòng kiểm tra lại trong lịch sử thanh toán.'],
        unknown: [<XCircle className="text-zinc-500" size={54} />, 'Không tìm thấy giao dịch', 'Hãy mở trang gói cước hoặc tài khoản để kiểm tra trạng thái.'],
    }[status];

    return (
        <div className="site-container flex min-h-[70vh] items-center justify-center py-12">
            <div className="panel max-w-lg p-10 text-center">
                <div className="flex justify-center">{content[0]}</div>
                <h1 className="mt-6 text-3xl font-bold">{content[1]}</h1>
                <p className="mt-3 text-zinc-500">{content[2]}</p>
                <div className="mt-8 flex justify-center gap-3">
                    <Link className="button button--primary" to="/">Về Trang chủ</Link>
                    <Link className="button button--ghost" to="/account">Xem tài khoản</Link>
                </div>
            </div>
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
