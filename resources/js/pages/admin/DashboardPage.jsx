import React, { useEffect, useState } from 'react';
import { Users, Film, DollarSign, MessageSquare, RefreshCw } from 'lucide-react';
import { api } from '../../api';
import AdminTable from '../../components/admin/AdminTable';
import AdminBadge from '../../components/admin/AdminBadge';
import { useAdminToast } from './AdminLayout';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell } from 'recharts';

export default function DashboardPage() {
    const { showToast } = useAdminToast();
    const [stats, setStats] = useState({
        total_revenue: 0,
        total_users: 0,
        total_movies: 0,
        total_comments: 0
    });
    const [pendingComments, setPendingComments] = useState(0);
    const [topMovies, setTopMovies] = useState([]);
    const [loading, setLoading] = useState(true);
    const [syncPage, setSyncPage] = useState(1);
    const [syncing, setSyncing] = useState(false);

    useEffect(() => {
        const fetchData = async () => {
            setLoading(true); // Bắt đầu load
            try {
                // Chạy song song cả 2 để tối ưu
                const [statsRes, commentsRes] = await Promise.all([
                    api.getAdminStats(),
                    api.getAdminComments({ status: 'pending_review', limit: 1 })
                ]);

                setStats(statsRes.data?.data || statsRes.data);
                setPendingComments(commentsRes.data?.data?.meta?.total || 0);

                // Lấy riêng top movies
                const topRes = await api.getAdminTopViewedMovies({ limit: 5 });
                setTopMovies(topRes.data?.data || []);

            } catch (err) {
                showToast('error', 'Lỗi tải dữ liệu Dashboard');
            } finally {
                setLoading(false); // <--- QUAN TRỌNG: Phải tắt loading ở đây
            }
        };

        fetchData();
    }, [showToast]);

    const handleSyncOphim = async () => {
        if (!syncPage) return;
        setSyncing(true);
        try {
            const res = await api.syncOphimMovies(syncPage);
            showToast('success', `Đồng bộ trang ${syncPage} thành công.`);
        } catch (err) {
            showToast('error', err.response?.data?.message || 'Lỗi đồng bộ Ophim');
        } finally {
            setSyncing(false);
        }
    };

    const statCards = [
        { title: 'Doanh thu', value: new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(stats.total_revenue || 0), icon: DollarSign, color: 'text-green-500', bg: 'bg-green-500/10' },
        { title: 'Người dùng', value: stats.total_users || 0, icon: Users, color: 'text-blue-500', bg: 'bg-blue-500/10' },
        { title: 'Phim', value: stats.total_movies || 0, icon: Film, color: 'text-purple-500', bg: 'bg-purple-500/10' },
        { title: 'Bình luận chờ duyệt', value: pendingComments || 0, icon: MessageSquare, color: 'text-yellow-500', bg: 'bg-yellow-500/10' },
    ];

    const movieColumns = [
        { header: 'Tên phim', accessor: 'name', render: (row) => <div className="font-medium text-white">{row.name || row.title}</div> },
        { header: 'Lượt xem', accessor: 'views', render: (row) => new Intl.NumberFormat('vi-VN').format(row.views || 0) },
        { header: 'Trạng thái', accessor: 'status', render: (row) => <AdminBadge status={row.status} /> }
    ];

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold text-white">Dashboard</h1>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {statCards.map((card, idx) => {
                    const Icon = card.icon;
                    return (
                        <div key={idx} className="bg-[#151B27] border border-white/10 rounded-xl p-6 flex items-center gap-4">
                            <div className={`p-3 rounded-lg ${card.bg} ${card.color}`}>
                                <Icon size={24} />
                            </div>
                            <div>
                                <p className="text-sm font-medium text-zinc-400">{card.title}</p>
                                <p className="text-2xl font-bold text-white mt-1">{loading ? '...' : card.value}</p>
                            </div>
                        </div>
                    );
                })}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* <div className="lg:col-span-2 bg-[#151B27] border border-white/10 rounded-xl p-6">
                    <h2 className="text-lg font-semibold text-white mb-4">Top Phim Xem Nhiều</h2>
                    <AdminTable
                        columns={movieColumns}
                        data={topMovies}
                        loading={loading}
                    />
                </div> */}
                <div className="h-64 w-full bg-[#151B27] border border-white/10 rounded-xl p-6">
                    <h2 className="text-lg font-semibold text-white mb-4">Top 5 Phim Nhiều View Nhất</h2>
                    {topMovies && topMovies.length > 0 ? (
                        // Tìm đoạn mã BarChart trong DashboardPage.jsx
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={topMovies} margin={{ top: 5, right: 20, left: 0, bottom: 5 }}>
                                <XAxis dataKey="name" hide />
                                <YAxis hide />
                                <Tooltip
                                    contentStyle={{ backgroundColor: '#101521', border: '1px solid #ffffff10', borderRadius: '8px' }}
                                    itemStyle={{ color: '#fff' }}
                                />
                                {/* Đổi dataKey ở đây từ 'views' thành 'view_count' */}
                                <Bar dataKey="view_count" fill="#E50914" radius={[4, 4, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    ) : (
                        <div className="h-full flex items-center justify-center text-zinc-500">
                            Chưa có dữ liệu lượt xem
                        </div>
                    )}
                </div>

                <div className="bg-[#151B27] border border-white/10 rounded-xl p-6 flex flex-col">
                    <h2 className="text-lg font-semibold text-white mb-4">Đồng bộ từ Ophim</h2>
                    <p className="text-sm text-zinc-400 mb-4">Nhập số trang để tiến hành đồng bộ danh sách phim từ API Ophim.</p>
                    <div className="flex gap-2 mt-auto">
                        <input
                            type="number"
                            min="1"
                            value={syncPage}
                            onChange={(e) => setSyncPage(Number(e.target.value))}
                            className="flex-1 bg-[#101521] border border-white/10 rounded-lg px-4 py-2 text-white focus:border-[#E50914] focus:outline-none"
                            placeholder="Số trang"
                        />
                        <button
                            onClick={handleSyncOphim}
                            disabled={syncing}
                            className="bg-[#E50914] hover:bg-[#E50914]/90 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2 disabled:opacity-50 transition-colors"
                        >
                            <RefreshCw size={18} className={syncing ? 'animate-spin' : ''} />
                            Đồng bộ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
