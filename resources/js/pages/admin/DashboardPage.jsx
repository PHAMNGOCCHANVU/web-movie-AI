import React, { useEffect, useState } from 'react';
import { Users, Film, DollarSign, MessageSquare, RefreshCw, Activity, TrendingUp } from 'lucide-react';
import { api } from '../../api';
import AdminTable from '../../components/admin/AdminTable';
import AdminBadge from '../../components/admin/AdminBadge';
import { useAdminToast } from './AdminLayout';
import {
    BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer,
    AreaChart, Area, CartesianGrid, PieChart, Pie, Cell, Legend
} from 'recharts';

export default function DashboardPage() {
    const { showToast } = useAdminToast();
    const [stats, setStats] = useState({ total_revenue: 0, total_users: 0, total_movies: 0, total_comments: 0 });
    const [pendingComments, setPendingComments] = useState(0);
    const [topMovies, setTopMovies] = useState([]); // Top view_count
    const [hotMovies, setHotMovies] = useState([]); // Top comments_count
    const [revenueData, setRevenueData] = useState([]);
    const [sentimentData, setSentimentData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [syncPage, setSyncPage] = useState(1);
    const [syncing, setSyncing] = useState(false);

    useEffect(() => {
        const fetchData = async () => {
            setLoading(true);

            // 1. Gọi các API nhẹ trước để có giao diện nhanh
            try {
                const statsRes = await api.getAdminStats();
                setStats(statsRes.data?.data || statsRes.data);
            } catch (err) { console.error("Lỗi stats:", err); }

            try {
                const commentsRes = await api.getAdminComments({ status: 'pending_review', limit: 1 });
                setPendingComments(commentsRes.data?.data?.meta?.total || 0);
            } catch (err) { console.error("Lỗi comments:", err); }

            try {
                const topViewsRes = await api.getAdminTopViewedMovies({ limit: 5 });
                setTopMovies(topViewsRes.data?.data || []);
            } catch (err) { console.error("Lỗi top views:", err); }

            // 2. Gọi các API tính toán nặng sau
            try {
                const revenueRes = await api.getAdminRevenue({ period: 'monthly' });
                const rawRevenue = revenueRes.data?.data || {};
                setRevenueData(Object.keys(rawRevenue).map(key => ({ period: key, amount: rawRevenue[key] })));
            } catch (err) { console.error("Lỗi doanh thu:", err); }

            try {
                const sentimentRes = await api.getAdminSentiment();
                const rawSentiment = sentimentRes.data?.data || {};
                setSentimentData([
                    { name: 'Tích cực / An toàn', value: rawSentiment.positive_safe || 0, color: '#10B981' },
                    { name: 'Cảnh báo / Nghi ngờ', value: rawSentiment.neutral_warning || 0, color: '#F59E0B' },
                    { name: 'Độc hại / Nguy hiểm', value: rawSentiment.toxic_danger || 0, color: '#EF4444' }
                ]);
            } catch (err) { console.error("Lỗi sentiment:", err); }

            try {
                const hotMoviesRes = await api.getAdminTopMovies({ limit: 5 });
                setHotMovies(hotMoviesRes.data?.data || []);
            } catch (err) { console.error("Lỗi hot movies:", err); }

            setLoading(false);
        };

        fetchData();
    }, [showToast]);

    const handleSyncOphim = async () => {
        if (!syncPage) return;
        setSyncing(true);
        try {
            await api.syncOphimMovies(syncPage);
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
        { title: 'Phim trên hệ thống', value: stats.total_movies || 0, icon: Film, color: 'text-purple-500', bg: 'bg-purple-500/10' },
        { title: 'Bình luận chờ duyệt', value: pendingComments || 0, icon: MessageSquare, color: 'text-yellow-500', bg: 'bg-yellow-500/10' },
    ];

    const hotMovieColumns = [
        { header: 'Tên phim', accessor: 'name', render: (row) => <div className="font-medium text-white max-w-[180px] truncate">{row.name}</div> },
        { header: 'Số bình luận', accessor: 'comments_count', render: (row) => <div className="text-zinc-400 text-center font-semibold">{row.comments_count || 0}</div> },
        { header: 'Premium', accessor: 'is_premium', render: (row) => <AdminBadge status={row.is_premium ? 'premium' : 'free'} /> }
    ];

    return (
        <div className="space-y-6 pb-10">
            <h1 className="text-2xl font-bold text-white">Dashboard Tổng Quan</h1>

            {/* HÀNG 1: Thẻ thống kê */}
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

            {/* HÀNG 2: Biểu đồ doanh thu nâng cao & AI Sentiment */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Khối biểu đồ doanh thu */}
                <div className="lg:col-span-2 bg-[#151B27] border border-white/10 rounded-xl p-6 flex flex-col justify-between">
                    <div className="flex items-center justify-between mb-4">
                        <div>
                            <h2 className="text-lg font-semibold text-white flex items-center gap-2">
                                <TrendingUp size={20} className="text-green-500" /> Biểu Đồ Doanh Thu Hệ Thống
                            </h2>
                            <p className="text-xs text-zinc-500">Dữ liệu tổng hợp doanh thu thành công theo tháng</p>
                        </div>
                    </div>
                    <div className="h-64 w-full">
                        {revenueData.length > 0 ? (
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={revenueData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                                    <defs>
                                        <linearGradient id="revenueGradient" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#10B981" stopOpacity={0.3} />
                                            <stop offset="95%" stopColor="#10B981" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#ffffff05" />
                                    <XAxis dataKey="period" stroke="#71717a" fontSize={12} />
                                    <YAxis stroke="#71717a" fontSize={12} />
                                    <Tooltip
                                        contentStyle={{ backgroundColor: '#101521', border: '1px solid #ffffff10', borderRadius: '8px' }}
                                        formatter={(value) => [new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value), 'Doanh thu']}
                                    />
                                    <Area type="monotone" dataKey="amount" stroke="#10B981" strokeWidth={2} fillOpacity={1} fill="url(#revenueGradient)" />
                                </AreaChart>
                            </ResponsiveContainer>
                        ) : (
                            <div className="h-full flex items-center justify-center text-zinc-500 text-sm">Chưa có phát sinh doanh thu</div>
                        )}
                    </div>
                </div>

                {/* Khối biểu đồ tròn AI Sentiment */}
                <div className="bg-[#151B27] border border-white/10 rounded-xl p-6 flex flex-col justify-between">
                    <div>
                        <h2 className="text-lg font-semibold text-white flex items-center gap-2">
                            <Activity size={20} className="text-purple-500" /> AI Kiểm Duyệt Bình Luận
                        </h2>
                        <p className="text-xs text-zinc-500 mb-2">Phân tách sắc thái dựa trên điểm độc hại từ Gemini AI</p>
                    </div>
                    <div className="h-52 w-full flex items-center justify-center">
                        {/* Sửa điều kiện: Đảm bảo có ít nhất 1 dữ liệu phân tích lớn hơn 0 thì mới vẽ PieChart */}
                        {sentimentData.some(item => item.value > 0) ? (
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie data={sentimentData} cx="50%" cy="45%" innerRadius={60} outerRadius={80} paddingAngle={4} dataKey="value" >
                                        {sentimentData.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={entry.color} />
                                        ))}
                                    </Pie>
                                    <Tooltip contentStyle={{ backgroundColor: '#101521', border: '1px solid #ffffff10', borderRadius: '8px' }} />
                                    <Legend verticalAlign="bottom" height={36} iconType="circle" wrapperStyle={{ fontSize: '11px', color: '#fff' }} />
                                </PieChart>
                            </ResponsiveContainer>
                        ) : (
                            <div className="text-zinc-500 text-sm text-center">
                                <p>Chưa có bình luận được quét AI</p>
                                <p className="text-xs text-zinc-600 mt-1">(Dữ liệu biểu đồ đang trống)</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* HÀNG 3: Biểu đồ Top View & Bảng xếp hạng tương tác phim */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Biểu đồ Cột Top 5 View (Đã sửa khoảng trống đáy) */}
                <div className="bg-[#151B27] border border-white/10 rounded-xl p-6 flex flex-col justify-between">
                    <div>
                        <h2 className="text-lg font-semibold text-white mb-1">Top 5 Phim Nhiều View Nhất</h2>
                        <p className="text-xs text-zinc-500 mb-4">Thống kê xếp hạng theo lượt truy cập thực tế</p>
                    </div>
                    <div className="h-56 w-full flex items-end">
                        {topMovies.length > 0 ? (
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={topMovies} margin={{ top: 10, right: 10, left: 10, bottom: 0 }}>
                                    <defs>
                                        <linearGradient id="barGradient" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stopColor="#E50914" stopOpacity={1} />
                                            <stop offset="100%" stopColor="#E50914" stopOpacity={0.3} />
                                        </linearGradient>
                                    </defs>
                                    <XAxis dataKey="name" hide />
                                    <YAxis hide />
                                    <Tooltip
                                        contentStyle={{ backgroundColor: '#101521', border: '1px solid #ffffff10', borderRadius: '8px' }}
                                        itemStyle={{ color: '#fff' }}
                                        formatter={(value) => [`${new Intl.NumberFormat('vi-VN').format(value)} lượt xem`, 'Lượt xem']}
                                    />
                                    <Bar dataKey="view_count" fill="url(#barGradient)" radius={[4, 4, 0, 0]} minPointSize={8} maxBarSize={45} />
                                </BarChart>
                            </ResponsiveContainer>
                        ) : (
                            <div className="h-full w-full flex items-center justify-center text-zinc-500 text-sm">Chưa có dữ liệu lượt xem</div>
                        )}
                    </div>
                </div>

                {/* Bảng Top Phim Hoạt Động Sôi Nổi (Theo Comment) & Khối đồng bộ */}
                <div className="space-y-6 flex flex-col justify-between">
                    <div className="bg-[#151B27] border border-white/10 rounded-xl p-6 flex-1">
                        <h2 className="text-lg font-semibold text-white mb-1">Top Phim Tương Tác Cao</h2>
                        <p className="text-xs text-zinc-500 mb-4">Xếp hạng các bộ phim có chuỗi hội thoại bình luận nhiều nhất</p>
                        <AdminTable columns={hotMovieColumns} data={hotMovies} loading={loading} />
                    </div>

                    {/* Khối đồng bộ từ Ophim */}
                    <div className="bg-[#151B27] border border-white/10 rounded-xl p-6">
                        <h2 className="text-lg font-semibold text-white mb-2">Đồng bộ từ hệ thống Ophim</h2>
                        <div className="flex gap-2 items-center">
                            <input
                                type="number"
                                min="1"
                                value={syncPage}
                                onChange={(e) => setSyncPage(Number(e.target.value))}
                                className="w-28 bg-[#101521] border border-white/10 rounded-lg px-4 py-2 text-white focus:border-[#E50914] focus:outline-none"
                                placeholder="Trang"
                            />
                            <button
                                onClick={handleSyncOphim}
                                disabled={syncing}
                                className="flex-1 bg-[#E50914] hover:bg-[#E50914]/90 text-white px-4 py-2 rounded-lg font-medium flex items-center justify-center gap-2 disabled:opacity-50 transition-colors"
                            >
                                <RefreshCw size={18} className={syncing ? 'animate-spin' : ''} />
                                Thực hiện đồng bộ API
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}