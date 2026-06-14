import React, { useEffect, useState } from 'react';
import { api } from '../../api';
import AdminTable from '../../components/admin/AdminTable';
import AdminBadge from '../../components/admin/AdminBadge';
import AdminPagination from '../../components/admin/AdminPagination';
import AdminModal from '../../components/admin/AdminModal';
import { useAdminToast } from './AdminLayout';
import { CheckCircle, EyeOff, Trash2, Brain } from 'lucide-react';

export default function CommentModerationPage() {
    const { showToast } = useAdminToast();
    const [comments, setComments] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [statusTab, setStatusTab] = useState('pending_review');
    const [page, setPage] = useState(1);

    const [aiModal, setAiModal] = useState({ isOpen: false, sentiment: null, loading: false });
    const [confirmModal, setConfirmModal] = useState({ isOpen: false, action: null, comment: null });

    const fetchComments = async () => {
        setLoading(true);
        try {
            const res = await api.getAdminComments({ page, status: statusTab !== 'all' ? statusTab : '', limit: 10 });
            setComments(res.data?.data?.data || []);
            setMeta(res.data?.data?.meta || res.data?.data || null);
        } catch (err) {
            showToast('error', 'Lỗi tải danh sách bình luận');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchComments();
    }, [page, statusTab]);

    const handleAction = async (action, comment) => {
        try {
            if (action === 'approve') {
                await api.approveComment(comment.id);
                showToast('success', 'Đã duyệt bình luận');
            } else if (action === 'hide') {
                await api.hideComment(comment.id);
                showToast('success', 'Đã ẩn bình luận');
            } else if (action === 'delete') {
                await api.deleteAdminComment(comment.id);
                showToast('success', 'Đã xóa bình luận');
            }
            fetchComments();
        } catch (err) {
            showToast('error', err.response?.data?.message || 'Có lỗi xảy ra');
        } finally {
            setConfirmModal({ isOpen: false, action: null, comment: null });
        }
    };

    const handleAnalyzeAI = async (comment) => {
        setAiModal({ isOpen: true, loading: true, sentiment: null });
        try {
            const res = await api.getCommentSentiment(comment.id);
            setAiModal({ isOpen: true, loading: false, sentiment: res.data?.data || res.data });
        } catch (err) {
            showToast('error', 'Lỗi phân tích AI');
            setAiModal({ isOpen: false, loading: false, sentiment: null });
        }
    };

    const columns = [
        {
            header: 'Nội dung',
            accessor: 'content',
            className: 'w-1/3',
            render: (row) => (
                <div className="text-white text-sm line-clamp-2" title={row.content}>
                    {row.content}
                </div>
            )
        },
        {
            header: 'Phim / Người dùng',
            accessor: 'info',
            render: (row) => (
                <div>
                    <div className="text-sm text-white font-medium truncate w-40" title={row.movie?.name}>{row.movie?.name || 'Unknown Movie'}</div>
                    <div className="text-xs text-zinc-500">{row.user?.name || 'Unknown User'}</div>
                </div>
            )
        },
        {
            header: 'Điểm độc hại',
            accessor: 'toxic_score',
            render: (row) => {
                const score = row.toxic_score || 0;
                let colorClass = 'bg-green-500';
                if (score > 0.4) colorClass = 'bg-yellow-500';
                if (score > 0.7) colorClass = 'bg-red-500';

                return (
                    <div className="w-24">
                        <div className="flex justify-between text-xs text-zinc-400 mb-1">
                            <span>{Math.round(score * 100)}%</span>
                        </div>
                        <div className="h-1.5 w-full bg-white/10 rounded-full overflow-hidden">
                            <div className={`h-full ${colorClass}`} style={{ width: `${score * 100}%` }}></div>
                        </div>
                    </div>
                );
            }
        },
        { header: 'Trạng thái', accessor: 'status', render: (row) => <AdminBadge status={row.status} /> },
        {
            header: 'Thao tác',
            accessor: 'actions',
            render: (row) => (
                <div className="flex items-center gap-2">
                    <button onClick={() => handleAnalyzeAI(row)} className="p-1.5 text-purple-500 hover:bg-purple-500/10 rounded" title="Phân tích AI">
                        <Brain size={16} />
                    </button>
                    {row.status !== 'approved' && (
                        <button onClick={() => setConfirmModal({ isOpen: true, action: 'approve', comment: row })} className="p-1.5 text-green-500 hover:bg-green-500/10 rounded" title="Duyệt">
                            <CheckCircle size={16} />
                        </button>
                    )}
                    {row.status !== 'hidden' && (
                        <button onClick={() => setConfirmModal({ isOpen: true, action: 'hide', comment: row })} className="p-1.5 text-yellow-500 hover:bg-yellow-500/10 rounded" title="Ẩn">
                            <EyeOff size={16} />
                        </button>
                    )}
                    <button onClick={() => setConfirmModal({ isOpen: true, action: 'delete', comment: row })} className="p-1.5 text-red-500 hover:bg-red-500/10 rounded" title="Xóa">
                        <Trash2 size={16} />
                    </button>
                </div>
            )
        }
    ];

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold text-white">Kiểm duyệt Bình luận</h1>

            <div className="flex gap-2 border-b border-white/10">
                {[
                    { id: 'pending_review', label: 'Chờ duyệt' },
                    { id: 'hidden', label: 'Đã ẩn' },
                    { id: 'all', label: 'Tất cả' }
                ].map(tab => (
                    <button
                        key={tab.id}
                        onClick={() => { setStatusTab(tab.id); setPage(1); }}
                        className={`px-4 py-3 text-sm font-medium transition-colors border-b-2 ${
                            statusTab === tab.id
                                ? 'border-[#E50914] text-[#E50914]'
                                : 'border-transparent text-zinc-400 hover:text-white'
                        }`}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            <AdminTable columns={columns} data={comments} loading={loading} />
            <AdminPagination meta={meta} onPageChange={setPage} />

            <AdminModal
                isOpen={confirmModal.isOpen}
                onClose={() => setConfirmModal({ isOpen: false, action: null, comment: null })}
                title="Xác nhận thao tác"
                type={confirmModal.action === 'delete' ? 'danger' : 'default'}
                footer={
                    <>
                        <button onClick={() => setConfirmModal({ isOpen: false, action: null, comment: null })} className="px-4 py-2 rounded-lg text-sm font-medium text-zinc-300 hover:text-white hover:bg-white/5">
                            Hủy
                        </button>
                        <button onClick={() => handleAction(confirmModal.action, confirmModal.comment)} className={`px-4 py-2 rounded-lg text-sm font-medium text-white ${confirmModal.action === 'delete' ? 'bg-red-500' : confirmModal.action === 'hide' ? 'bg-yellow-600' : 'bg-green-500'}`}>
                            Xác nhận
                        </button>
                    </>
                }
            >
                <p className="text-zinc-300">
                    Bạn có chắc muốn {confirmModal.action === 'delete' ? 'xóa' : confirmModal.action === 'hide' ? 'ẩn' : 'duyệt'} bình luận này?
                </p>
                <div className="mt-4 p-3 bg-white/5 rounded border border-white/10 text-sm text-zinc-400 italic">
                    "{confirmModal.comment?.content}"
                </div>
            </AdminModal>

            <AdminModal
                isOpen={aiModal.isOpen}
                onClose={() => setAiModal({ isOpen: false, sentiment: null, loading: false })}
                title="Kết quả phân tích AI"
            >
                {aiModal.loading ? (
                    <div className="flex flex-col items-center justify-center py-8 gap-3">
                        <Brain className="w-8 h-8 text-purple-500 animate-pulse" />
                        <p className="text-sm text-zinc-400">AI đang phân tích...</p>
                    </div>
                ) : aiModal.sentiment ? (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between p-4 bg-[#101521] rounded-lg border border-white/10">
                            <span className="text-zinc-400">Nhãn AI:</span>
                            <AdminBadge variant={aiModal.sentiment.label === 'toxic' ? 'danger' : 'success'} text={aiModal.sentiment.label?.toUpperCase() || 'UNKNOWN'} />
                        </div>
                        <div className="p-4 bg-[#101521] rounded-lg border border-white/10">
                            <div className="flex justify-between text-sm mb-2">
                                <span className="text-zinc-400">Điểm độc hại:</span>
                                <span className="text-white font-medium">{Math.round((aiModal.sentiment.toxic_score || 0) * 100)}%</span>
                            </div>
                            <div className="h-2 w-full bg-white/10 rounded-full overflow-hidden">
                                <div
                                    className={`h-full ${aiModal.sentiment.toxic_score > 0.7 ? 'bg-red-500' : aiModal.sentiment.toxic_score > 0.4 ? 'bg-yellow-500' : 'bg-green-500'}`}
                                    style={{ width: `${(aiModal.sentiment.toxic_score || 0) * 100}%` }}
                                />
                            </div>
                        </div>
                    </div>
                ) : (
                    <p className="text-red-400 text-sm">Không thể phân tích.</p>
                )}
            </AdminModal>
        </div>
    );
}
