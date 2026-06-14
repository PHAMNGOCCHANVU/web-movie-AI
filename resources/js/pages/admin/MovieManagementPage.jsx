import React, { useEffect, useState } from 'react';
import { api } from '../../api';
import AdminTable from '../../components/admin/AdminTable';
import AdminBadge from '../../components/admin/AdminBadge';
import AdminSearchBar from '../../components/admin/AdminSearchBar';
import AdminPagination from '../../components/admin/AdminPagination';
import AdminModal from '../../components/admin/AdminModal';
import { useAdminToast } from './AdminLayout';
import { Link } from 'react-router-dom';
import { Edit2, Trash2, CheckCircle, XCircle, Pin, Crown } from 'lucide-react';

export default function MovieManagementPage() {
    const { showToast } = useAdminToast();
    const [movies, setMovies] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [page, setPage] = useState(1);

    const [confirmModal, setConfirmModal] = useState({ isOpen: false, action: null, movie: null });

    const fetchMovies = async () => {
        setLoading(true);
        try {
            const res = await api.getAdminMovies({ page, search, status: statusFilter, limit: 10 }); console.log("Dữ liệu nhận từ API:", res.data); // Kiểm tra tab Console trên trình duyệt
            // Cấu trúc response của Laravel Paginate thường là: { data: { data: [...], current_page: 1, ... } }
            const responseData = res.data?.data;

            if (responseData) {
                setMovies(responseData.data || []);

                // Tách meta: lấy tất cả thông tin trừ cái mảng 'data' phim ra
                const { data, ...paginationMeta } = responseData;
                setMeta(paginationMeta);
            }
        } catch (err) {
            showToast('error', 'Lỗi tải danh sách phim');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        const timer = setTimeout(() => { fetchMovies(); }, 300);
        return () => clearTimeout(timer);
    }, [page, search, statusFilter]);

    const handleAction = async (action, movie) => {
        try {
            if (action === 'approve') {
                await api.approveMovie(movie.id);
                showToast('success', 'Đã duyệt phim thành công');
            } else if (action === 'reject') {
                await api.rejectMovie(movie.id);
                showToast('success', 'Đã từ chối phim');
            } else if (action === 'delete') {
                await api.deleteAdminMovie(movie.id);
                showToast('success', 'Đã xóa phim');
            } else if (action === 'toggle_premium') {
                await api.toggleMoviePremium(movie.id);
                showToast('success', 'Đã cập nhật Premium');
            } else if (action === 'toggle_pin') {
                await api.toggleMoviePin(movie.id);
                showToast('success', 'Đã cập nhật ghim');
            }
            fetchMovies();
        } catch (err) {
            showToast('error', err.response?.data?.message || 'Có lỗi xảy ra');
        } finally {
            setConfirmModal({ isOpen: false, action: null, movie: null });
        }
    };

    const confirmAction = (action, movie) => {
        if (action === 'toggle_premium' || action === 'toggle_pin') {
            handleAction(action, movie);
            return;
        }
        setConfirmModal({ isOpen: true, action, movie });
    };

    const columns = [
        {
            header: 'Phim',
            accessor: 'name',
            render: (row) => (
                <div className="flex items-center gap-3">
                    <img src={row.poster_url} alt={row.name} className="w-10 h-14 object-cover rounded" />
                    <div>
                        <div className="font-medium text-white">{row.name}</div>
                        <div className="text-xs text-zinc-500">{row.origin_name}</div>
                    </div>
                </div>
            )
        },
        { header: 'Trạng thái', accessor: 'status', render: (row) => <AdminBadge status={row.status} /> },
        {
            header: 'Phân loại',
            accessor: 'flags',
            render: (row) => (
                <div className="flex gap-2">
                    {row.is_premium && <AdminBadge variant="premium" text="Premium" />}
                    {row.is_pinned && <AdminBadge variant="active" text="Pinned" />}
                </div>
            )
        },
        { header: 'Ngày tạo', accessor: 'created_at', render: (row) => new Date(row.created_at).toLocaleDateString('vi-VN') },
        {
            header: 'Thao tác',
            accessor: 'actions',
            render: (row) => (
                <div className="flex items-center gap-2">
                    {row.status === 'pending' && (
                        <>
                            <button onClick={() => confirmAction('approve', row)} className="p-1.5 text-green-500 hover:bg-green-500/10 rounded" title="Duyệt">
                                <CheckCircle size={16} />
                            </button>
                            <button onClick={() => confirmAction('reject', row)} className="p-1.5 text-red-500 hover:bg-red-500/10 rounded" title="Từ chối">
                                <XCircle size={16} />
                            </button>
                        </>
                    )}
                    <button onClick={() => confirmAction('toggle_premium', row)} className={`p-1.5 rounded ${row.is_premium ? 'text-amber-500 hover:bg-amber-500/10' : 'text-zinc-500 hover:bg-white/5'}`} title="Premium">
                        <Crown size={16} />
                    </button>
                    <button onClick={() => confirmAction('toggle_pin', row)} className={`p-1.5 rounded ${row.is_pinned ? 'text-blue-500 hover:bg-blue-500/10' : 'text-zinc-500 hover:bg-white/5'}`} title="Ghim">
                        <Pin size={16} />
                    </button>
                    <Link to={`/admin/movies/${row.id}/edit`} className="p-1.5 text-zinc-400 hover:text-white hover:bg-white/5 rounded" title="Sửa">
                        <Edit2 size={16} />
                    </Link>
                    <button onClick={() => confirmAction('delete', row)} className="p-1.5 text-red-500 hover:bg-red-500/10 rounded" title="Xóa">
                        <Trash2 size={16} />
                    </button>
                </div>
            )
        }
    ];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold text-white">Quản lý Phim</h1>
                <Link to="/admin/movies/new" className="bg-[#E50914] hover:bg-[#E50914]/90 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    Thêm Phim Mới
                </Link>
            </div>

            <div className="flex flex-col sm:flex-row gap-4 items-center">
                <div className="w-full sm:w-1/3">
                    <AdminSearchBar value={search} onChange={setSearch} placeholder="Tìm kiếm phim..." />
                </div>
                <div className="w-full sm:w-1/4">
                    <select
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                        className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#E50914]"
                    >
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending">Chờ duyệt</option>
                        <option value="approved">Đã duyệt</option>
                    </select>
                </div>
            </div>

            <AdminTable
                columns={columns}
                data={movies}
                loading={loading}
            />

            <AdminPagination meta={meta} onPageChange={setPage} />

            <AdminModal
                isOpen={confirmModal.isOpen}
                onClose={() => setConfirmModal({ isOpen: false, action: null, movie: null })}
                title="Xác nhận thao tác"
                type={confirmModal.action === 'delete' || confirmModal.action === 'reject' ? 'danger' : 'default'}
                footer={
                    <>
                        <button
                            onClick={() => setConfirmModal({ isOpen: false, action: null, movie: null })}
                            className="px-4 py-2 rounded-lg text-sm font-medium text-zinc-300 hover:text-white hover:bg-white/5"
                        >
                            Hủy
                        </button>
                        <button
                            onClick={() => handleAction(confirmModal.action, confirmModal.movie)}
                            className={`px-4 py-2 rounded-lg text-sm font-medium text-white ${confirmModal.action === 'delete' || confirmModal.action === 'reject' ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'
                                }`}
                        >
                            Xác nhận
                        </button>
                    </>
                }
            >
                <p className="text-zinc-300">
                    Bạn có chắc chắn muốn {confirmModal.action === 'delete' ? 'xóa' : confirmModal.action === 'reject' ? 'từ chối' : 'duyệt'} phim <strong className="text-white">"{confirmModal.movie?.name}"</strong>?
                </p>
                {confirmModal.action === 'delete' && <p className="text-red-400 text-sm mt-2">Hành động này không thể hoàn tác.</p>}
            </AdminModal>
        </div>
    );
}
