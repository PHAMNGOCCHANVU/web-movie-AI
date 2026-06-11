import React, { useState } from 'react';
import { BookmarkPlus, CirclePlay, Info, Star } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { api, apiError } from '../api';
import { useAuth } from '../context/AuthContext';
import { useUi } from '../context/UiContext';
import { movieImage } from '../utils';

export default function MovieCard({ movie, showWatchlist = true, onRemoved }) {
    const { toast, openModal, closeModal } = useUi();
    const { isAuthenticated } = useAuth();
    const navigate = useNavigate();
    const [saving, setSaving] = useState(false);
    const poster = movieImage(movie);

    const addToWatchlist = async () => {
        if (!isAuthenticated) {
            navigate('/login', { state: { from: { pathname: `/movies/${movie.id}` } } });
            return;
        }
        setSaving(true);
        try {
            await api.addWatchlist(movie.id);
            toast('Đã thêm phim vào Xem sau.');
        } catch (error) {
            toast(apiError(error), 'error');
        } finally {
            setSaving(false);
        }
    };

    const play = async () => {
        if (!isAuthenticated) {
            navigate('/login', { state: { from: { pathname: `/movies/${movie.id}` } } });
            return;
        }
        try {
            const response = await api.movieStream(movie.id);
            navigate(`/watch/${movie.id}`, { state: { stream: response.data.data } });
        } catch (error) {
            const code = error.response?.data?.error;
            if (code === 'require_subscription' || code === 'require_vip_upgrade') {
                openModal(
                    <div>
                        <span className="eyebrow">Quyền xem phim</span>
                        <h2 className="mt-2 text-2xl font-bold">
                            {code === 'require_vip_upgrade' ? 'Cần nâng cấp VIP' : 'Cần đăng ký gói'}
                        </h2>
                        <p className="mt-3 text-zinc-400">{error.response.data.message}</p>
                        <button
                            className="button button--vip mt-6 w-full"
                            onClick={() => {
                                closeModal();
                                navigate('/plans');
                            }}
                        >
                            Xem các gói cước
                        </button>
                    </div>,
                );
                return;
            }
            toast(apiError(error), 'error');
        }
    };

    return (
        <article className="movie-card">
            <div className="movie-card__poster">
                {poster ? (
                    <img src={poster} alt={movie.name} loading="lazy" />
                ) : (
                    <div className="poster-placeholder">{movie.name?.charAt(0)}</div>
                )}
                <span className={`plan-badge ${movie.is_premium ? 'plan-badge--vip' : 'plan-badge--standard'}`}>
                    {movie.is_premium ? 'VIP' : 'STANDARD'}
                </span>
                <div className="movie-card__overlay">
                    <button className="round-action round-action--primary" title="Xem phim" onClick={play}>
                        <CirclePlay size={22} />
                    </button>
                    <Link className="round-action" title="Chi tiết" to={`/movies/${movie.id}`}>
                        <Info size={20} />
                    </Link>
                    {showWatchlist && (
                        <button
                            className="round-action"
                            disabled={saving}
                            title="Thêm vào Xem sau"
                            onClick={addToWatchlist}
                        >
                            <BookmarkPlus size={20} />
                        </button>
                    )}
                </div>
            </div>
            <div className="movie-card__body">
                <Link className="line-clamp-1 font-semibold hover:text-red-400" to={`/movies/${movie.id}`}>
                    {movie.name}
                </Link>
                <div className="mt-2 flex items-center justify-between text-xs text-zinc-500">
                    <span>{movie.year || 'Đang cập nhật'}</span>
                    <span className="flex items-center gap-1 text-amber-300">
                        <Star size={12} fill="currentColor" /> {Number(movie.ratings_avg_score || 0).toFixed(1)}
                    </span>
                </div>
                <p className="mt-2 line-clamp-1 text-xs text-zinc-600">
                    {movie.genres?.map((genre) => genre.name).join(', ') || movie.quality || 'Đang cập nhật thể loại'}
                </p>
                {onRemoved && (
                    <button className="mt-3 text-xs font-semibold text-red-400" onClick={() => onRemoved(movie.id)}>
                        Xóa khỏi danh sách
                    </button>
                )}
            </div>
        </article>
    );
}
