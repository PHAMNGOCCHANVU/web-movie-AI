import React, { useEffect, useState } from 'react';
import { Clock3, Play, Trash2 } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { api, apiError } from '../api';
import EmptyState from '../components/EmptyState';
import Loading from '../components/Loading';
import MovieCard from '../components/MovieCard';
import { useUi } from '../context/UiContext';
import { formatDate, formatTime, movieImage } from '../utils';

export default function LibraryPage() {
    const { toast } = useUi();
    const navigate = useNavigate();
    const [tab, setTab] = useState('watchlist');
    const [watchlist, setWatchlist] = useState([]);
    const [history, setHistory] = useState([]);
    const [loading, setLoading] = useState(true);

    const load = async () => {
        setLoading(true);
        const [watchlistResult, historyResult] = await Promise.allSettled([api.watchlist(), api.watchHistory()]);
        if (watchlistResult.status === 'fulfilled') setWatchlist(watchlistResult.value.data.data || []);
        if (historyResult.status === 'fulfilled') setHistory(historyResult.value.data.data || []);
        setLoading(false);
    };

    useEffect(() => { load(); }, []);

    const removeWatchlist = async (movieId) => {
        try {
            await api.removeWatchlist(movieId);
            setWatchlist((items) => items.filter((item) => item.id !== movieId));
            toast('Đã xóa phim khỏi Xem sau.');
        } catch (error) {
            toast(apiError(error), 'error');
        }
    };

    const removeHistory = async (movieId) => {
        try {
            await api.removeHistory(movieId);
            setHistory((items) => items.filter((item) => item.id !== movieId));
            toast('Đã xóa lịch sử xem.');
        } catch (error) {
            toast(apiError(error), 'error');
        }
    };

    return (
        <div className="site-container py-8">
            <div className="tabs">
                <button className={tab === 'watchlist' ? 'tab tab--active' : 'tab'} onClick={() => setTab('watchlist')}>
                    Xem sau <span>{watchlist.length}</span>
                </button>
                <button className={tab === 'history' ? 'tab tab--active' : 'tab'} onClick={() => setTab('history')}>
                    Lịch sử xem <span>{history.length}</span>
                </button>
            </div>

            {loading ? <Loading /> : tab === 'watchlist' ? (
                watchlist.length ? (
                    <div className="movie-grid mt-8">
                        {watchlist.map((movie) => (
                            <MovieCard key={movie.id} movie={movie} showWatchlist={false} onRemoved={removeWatchlist} />
                        ))}
                    </div>
                ) : (
                    <EmptyState
                        action={{ to: '/', label: 'Quay về Trang chủ' }}
                        message="Hãy thêm phim từ Trang chủ hoặc Tìm kiếm."
                        title="Bạn chưa có phim nào trong Xem sau"
                    />
                )
            ) : history.length ? (
                <div className="mt-8 space-y-4">
                    {history.map((movie) => (
                        <article className="history-card" key={movie.id}>
                            <div className="h-28 w-20 shrink-0 overflow-hidden rounded-xl bg-zinc-900 md:h-36 md:w-24">
                                {movieImage(movie) && <img className="h-full w-full object-cover" src={movieImage(movie)} alt={movie.name} />}
                            </div>
                            <div className="min-w-0 flex-1">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <Link className="text-lg font-bold hover:text-red-400" to={`/movies/${movie.id}`}>{movie.name}</Link>
                                        <p className="mt-2 text-sm text-zinc-500">
                                            {movie.current_episode?.name ? `Tập ${movie.current_episode.name} • ` : ''}
                                            Tiến độ {formatTime(movie.pivot?.watch_progress_seconds)}
                                            {movie.pivot?.duration_seconds > 0 ? ` / ${formatTime(movie.pivot.duration_seconds)}` : ''}
                                        </p>
                                        <p className="mt-1 flex items-center gap-2 text-xs text-zinc-600">
                                            <Clock3 size={14} /> Cập nhật {formatDate(movie.pivot?.updated_at)}
                                        </p>
                                    </div>
                                    <span className={`plan-badge static ${movie.is_premium ? 'plan-badge--vip' : 'plan-badge--standard'}`}>
                                        {movie.is_premium ? 'VIP' : 'STANDARD'}
                                    </span>
                                </div>
                                <div className="mt-5 flex flex-wrap gap-3">
                                    <button
                                        className="button button--primary button--small"
                                        onClick={() => navigate(`/watch/${movie.id}`, {
                                            state: {
                                                episodeId: movie.pivot?.episode_id,
                                                progressSeconds: movie.pivot?.watch_progress_seconds,
                                            },
                                        })}
                                    >
                                        <Play size={16} /> Tiếp tục xem
                                    </button>
                                    <button className="button button--ghost button--small" onClick={() => removeHistory(movie.id)}>
                                        <Trash2 size={16} /> Xóa lịch sử
                                    </button>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            ) : (
                <EmptyState action={{ to: '/', label: 'Khám phá phim' }} title="Chưa có lịch sử xem" />
            )}
        </div>
    );
}
