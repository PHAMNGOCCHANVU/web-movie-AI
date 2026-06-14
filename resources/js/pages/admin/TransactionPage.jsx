import React, { useEffect, useState } from 'react';
import { api } from '../../api';
import AdminTable from '../../components/admin/AdminTable';
import AdminBadge from '../../components/admin/AdminBadge';
import AdminPagination from '../../components/admin/AdminPagination';
import AdminModal from '../../components/admin/AdminModal';
import { useAdminToast } from './AdminLayout';

export default function TransactionPage() {
    const { showToast } = useAdminToast();
    const [transactions, setTransactions] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [statusFilter, setStatusFilter] = useState('');
    const [page, setPage] = useState(1);

    const [detailModal, setDetailModal] = useState({ isOpen: false, transaction: null });

    const fetchTransactions = async () => {
        setLoading(true);
        try {
            const res = await api.getAdminTransactions({ page, status: statusFilter, limit: 10 });
            setTransactions(res.data?.data?.data || []);
            setMeta(res.data?.data?.meta || res.data?.data || null);
        } catch (err) {
            showToast('error', 'Lỗi tải danh sách giao dịch');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchTransactions();
    }, [page, statusFilter]);

    const handleViewDetail = async (id) => {
        try {
            const res = await api.getAdminTransaction(id);
            setDetailModal({ isOpen: true, transaction: res.data?.data || res.data });
        } catch (err) {
            showToast('error', 'Lỗi tải chi tiết giao dịch');
        }
    };

    const columns = [
        { header: 'Mã GD', accessor: 'transaction_id', render: (row) => <span className="font-mono text-sm">{row.transaction_id || row.id}</span> },
        {
            header: 'Người dùng',
            accessor: 'user',
            render: (row) => (
                <div>
                    <div className="font-medium text-white">{row.user?.name}</div>
                    <div className="text-xs text-zinc-500">{row.user?.email}</div>
                </div>
            )
        },
        {
            header: 'Số tiền',
            accessor: 'amount',
            render: (row) => <span className="text-[#10B981] font-medium">{new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(row.amount)}</span>
        },
        { header: 'Trạng thái', accessor: 'status', render: (row) => <AdminBadge status={row.status} /> },
        { header: 'Phương thức', accessor: 'payment_method', render: (row) => <span className="uppercase text-xs font-semibold bg-white/10 px-2 py-1 rounded">{row.payment_method || 'VNPAY'}</span> },
        { header: 'Ngày tạo', accessor: 'created_at', render: (row) => new Date(row.created_at).toLocaleString('vi-VN') },
        {
            header: 'Chi tiết',
            accessor: 'actions',
            render: (row) => (
                <button onClick={() => handleViewDetail(row.id)} className="text-sm text-[#E50914] hover:text-white transition-colors underline">
                    Xem
                </button>
            )
        }
    ];

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold text-white">Quản lý Giao dịch</h1>

            <div className="flex gap-4 items-center">
                <div className="w-full sm:w-1/4">
                    <select
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                        className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#E50914]"
                    >
                        <option value="">Tất cả trạng thái</option>
                        <option value="success">Thành công</option>
                        <option value="pending">Đang xử lý</option>
                        <option value="failed">Thất bại</option>
                    </select>
                </div>
            </div>

            <AdminTable columns={columns} data={transactions} loading={loading} />
            <AdminPagination meta={meta} onPageChange={setPage} />

            <AdminModal
                isOpen={detailModal.isOpen}
                onClose={() => setDetailModal({ isOpen: false, transaction: null })}
                title="Chi tiết giao dịch"
            >
                {detailModal.transaction && (
                    <div className="space-y-4">
                        <div className="grid grid-cols-2 gap-4 text-sm">
                            <div className="bg-[#101521] p-3 rounded border border-white/10">
                                <span className="text-zinc-500 block mb-1">Mã GD hệ thống</span>
                                <span className="text-white font-mono">{detailModal.transaction.id}</span>
                            </div>
                            <div className="bg-[#101521] p-3 rounded border border-white/10">
                                <span className="text-zinc-500 block mb-1">Mã đối tác (VNPAY)</span>
                                <span className="text-white font-mono">{detailModal.transaction.transaction_id || 'N/A'}</span>
                            </div>
                            <div className="bg-[#101521] p-3 rounded border border-white/10">
                                <span className="text-zinc-500 block mb-1">Số tiền</span>
                                <span className="text-[#10B981] font-bold">{new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(detailModal.transaction.amount)}</span>
                            </div>
                            <div className="bg-[#101521] p-3 rounded border border-white/10">
                                <span className="text-zinc-500 block mb-1">Trạng thái</span>
                                <AdminBadge status={detailModal.transaction.status} />
                            </div>
                        </div>
                        <div className="bg-[#101521] p-3 rounded border border-white/10 text-sm">
                            <span className="text-zinc-500 block mb-1">Nội dung chuyển khoản</span>
                            <span className="text-white break-words">{detailModal.transaction.description || 'Không có'}</span>
                        </div>
                    </div>
                )}
            </AdminModal>
        </div>
    );
}
