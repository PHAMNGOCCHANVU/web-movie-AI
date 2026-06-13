import React, { useEffect, useMemo, useState } from 'react';
import {
    Bookmark,
    BookmarkCheck,
    CirclePlay,
    Eye,
    Film,
    Star,
    UserRound,
    Video,
} from 'lucide-react';
import { useNavigate, useParams } from 'react-router-dom';
import { api, apiError } from '../api';
import Loading from '../components/Loading';
import MovieSection from '../components/MovieSection';
import { useAuth } from '../context/AuthContext';
import { useUi } from '../context/UiContext';
import { movieImage, stripHtml, youtubeEmbedUrl } from '../utils';

export default function MovieDetailPage() {
    const { movieId } = useParams();
    const navigate = useNavigate();
    const { user, isAuthenticated } = useAuth();
    const { toast, openModal, closeModal } = useUi();
    const [movie, setMovie] = useState(null);
    const [episodes, setEpisodes] = useState({});
    const [comments, setComments] = useState([]);
    const [ratings, setRatings] = useState(null);
    const [recommendations, setRecommendations] = useState([]);
    const [trailerUrl, setTrailerUrl] = useState('');
    const [comment, setComment] = useState('');
    const [saved, setSaved] = useState(false);
    const [saving, setSaving] = useState(false);
    const [loading, setLoading] = useState(true);

    const loadComments = async () => {
        const response = await api.movieComments(movieId);
        setComments(response.data.data?.data || []);
    };

    useEffect(() => {
        let active = true;

        const load = async () => {
            setLoading(true);
            try {
                const [movieResponse, episodeResponse, ratingResponse, commentResponse, trailerResponse] = await Promise.all([
                    api.movie(movieId),
                    api.movieEpisodes(movieId),
                    api.movieRatings(movieId),
                    api.movieComments(movieId),
                    api.movieTrailer(movieId),
                ]);

                if (!active) return;
                const movieData = movieResponse.data.data;
                setMovie(movieData);
                setEpisodes(episodeResponse.data.data?.episodes || {});
                setRatings(ratingResponse.data.data);
                setComments(commentResponse.data.data?.data || []);
                setTrailerUrl(trailerResponse.data.data?.trailer_url || '');

                if (isAuthenticated) {
                    const watchlistResponse = await api.watchlist();
                    if (active) {
                        setSaved((watchlistResponse.data.data || []).some((item) => Number(item.id) === Number(movieId)));
                    }
                } else {
                    setSaved(false);
                }

                const genreId = movieData.genres?.[0]?.id;
                if (genreId) {
                    const recommendationResponse = await api.genreMovies(genreId, { per_page: 12 });
                    if (active) {
                        setRecommendations(
                            (recommendationResponse.data.data?.movies?.data || [])
                                .filter((item) => Number(item.id) !== Number(movieId))
                                .slice(0, 10),
                        );
                    }
                } else {
                    setRecommendations([]);
                }
            } catch (error) {
                if (active) toast(apiError(error), 'error');
            } finally {
                if (active) setLoading(false);
            }
        };

        load();
        return () => { active = false; };
    }, [movieId, toast, isAuthenticated]);

    const servers = useMemo(() => Object.entries(episodes), [episodes]);
    const episodeCount = useMemo(
        () => servers.reduce((total, [, items]) => total + items.length, 0),
        [servers],
    );

    const play = async (episodeId = null) => {
        if (!isAuthenticated) {
            navigate('/login', { state: { from: { pathname: `/movies/${movieId}` } } });
            return;
        }
        try {
            const response = await api.movieStream(movieId, episodeId);
            navigate(`/watch/${movieId}`, { state: { stream: response.data.data, episodeId } });
        } catch (error) {
            const code = error.response?.data?.error;
            if (['require_subscription', 'require_vip_upgrade'].includes(code)) {
                openModal(
                    <div>
                        <span className="eyebrow">Quyền xem phim</span>
                        <h2 className="mt-3 text-2xl font-bold">
                            {code === 'require_vip_upgrade' ? 'Phim dành cho VIP' : 'Bạn chưa có gói xem phim'}
                        </h2>
                        <p className="mt-3 text-zinc-400">{error.response.data.message}</p>
                        <button className="button button--vip mt-6 w-full" onClick={() => { closeModal(); navigate('/plans'); }}>
                            Chọn gói cước
                        </button>
                    </div>,
                );
                return;
            }
            toast(apiError(error), 'error');
        }
    };

    const toggleWatchlist = async () => {
        if (!isAuthenticated) {
            navigate('/login', { state: { from: { pathname: `/movies/${movieId}` } } });
            return;
        }
        setSaving(true);
        try {
            if (saved) {
                await api.removeWatchlist(movie.id);
                setSaved(false);
                toast('Đã bỏ phim khỏi Xem sau.');
            } else {
                await api.addWatchlist(movie.id);
                setSaved(true);
                toast('Đã lưu phim vào Xem sau.');
            }
        } catch (error) {
            toast(apiError(error), 'error');
        } finally {
            setSaving(false);
        }
    };

    const showTrailer = () => {
        const embedUrl = youtubeEmbedUrl(trailerUrl);
        if (!embedUrl) {
            toast('Phim này chưa có trailer.', 'error');
            return;
        }

        openModal(
            <div className="pt-7">
                <h2 className="mb-4 text-xl font-bold">Trailer: {movie.name}</h2>
                <iframe
                    allow="autoplay; encrypted-media; picture-in-picture"
                    allowFullScreen
                    className="aspect-video w-full rounded-xl bg-black"
                    src={embedUrl}
                    title={`Trailer ${movie.name}`}
                />
            </div>,
        );
    };

    const rate = async (score) => {
        try {
            await api.rateMovie(movie.id, score);
            const response = await api.movieRatings(movie.id);
            setRatings(response.data.data);
            toast(`Đã đánh giá ${score}/10.`);
        } catch (error) {
            toast(apiError(error), 'error');
        }
    };

    const openRating = () => {
        if (!isAuthenticated) {
            navigate('/login', { state: { from: { pathname: `/movies/${movieId}` } } });
            return;
        }
        openModal(
            <div className="rating-modal">
                <span className="eyebrow">Đánh giá phim</span>
                <h2>Bạn cảm thấy nội dung này như thế nào?</h2>
                <p>Chọn điểm từ 1 đến 10 cho {movie.name}.</p>
                <div className="rating-modal__scores">
                    {Array.from({ length: 10 }, (_, index) => index + 1).map((score) => (
                        <button
                            className={ratings?.user_score === score ? 'active' : ''}
                            key={score}
                            onClick={async () => {
                                await rate(score);
                                closeModal();
                            }}
                        >
                            <Star size={19} fill="currentColor" />
                            <span>{score}</span>
                        </button>
                    ))}
                </div>
            </div>,
        );
    };

    const submitComment = async (event) => {
        event.preventDefault();
        if (!isAuthenticated) {
            navigate('/login', { state: { from: { pathname: `/movies/${movieId}` } } });
            return;
        }
        if (!comment.trim()) return;
        try {
            await api.postComment(movie.id, comment.trim());
            setComment('');
            await loadComments();
            toast('Đã đăng bình luận.');
        } catch (error) {
            toast(apiError(error), 'error');
        }
    };

    const removeComment = async (commentId) => {
        try {
            await api.deleteComment(commentId);
            setComments((items) => items.filter((item) => item.id !== commentId));
            toast('Đã xóa bình luận.');
        } catch (error) {
            toast(apiError(error), 'error');
        }
    };

    if (loading || !movie) return <Loading label="Đang tải chi tiết phim..." />;

    const genres = movie.genres?.map((genre) => genre.name).join(', ') || 'Đang cập nhật';
    const countries = movie.country?.map((item) => item.name || item).join(', ') || 'Đang cập nhật';

    return (
        <div className="movie-detail-page">
            <section className="movie-detail-hero" style={{ backgroundImage: `url("${movieImage(movie, 'backdrop')}")` }}>
                <div className="movie-detail-hero__shade" />
                <div className="site-container movie-detail-hero__content">
                    <div className="movie-detail-poster">
                        {movieImage(movie) ? (
                            <img src={movieImage(movie)} alt={movie.name} />
                        ) : (
                            <div className="poster-placeholder">{movie.name?.charAt(0)}</div>
                        )}
                    </div>

                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-3">
                            <span className={`plan-badge static ${movie.is_premium ? 'plan-badge--vip' : 'plan-badge--standard'}`}>
                                {movie.is_premium ? 'VIP' : 'STANDARD'}
                            </span>
                            {movie.episode_current && <span className="detail-status">{movie.episode_current}</span>}
                        </div>
                        <h1 className="movie-detail-title">{movie.name}</h1>
                        {movie.origin_name && <p className="movie-detail-origin">{movie.origin_name}</p>}

                        <div className="movie-detail-meta">
                            <span>{movie.year || 'Đang cập nhật'}</span>
                            <span>{movie.quality || 'HD'}</span>
                            <span>{movie.lang || 'Phụ đề'}</span>
                            <span>{movie.time || `${episodeCount} tập`}</span>
                            <button className="detail-rating-trigger" onClick={openRating}>
                                <Star size={16} fill="currentColor" /> {ratings?.average || 0}/10
                                <small>({ratings?.count || 0})</small>
                            </button>
                            <span><Eye size={16} /> {Number(movie.view_count || 0).toLocaleString('vi-VN')} lượt xem</span>
                        </div>

                        <p className="movie-detail-summary">{stripHtml(movie.content) || 'Nội dung phim đang được cập nhật.'}</p>

                        <div className="movie-detail-actions">
                            <button className="button button--primary" onClick={() => play()}>
                                <CirclePlay size={21} /> Xem phim
                            </button>
                            <button className="button button--secondary" onClick={showTrailer}>
                                <Video size={19} /> Trailer
                            </button>
                            <button className={`button ${saved ? 'button--saved' : 'button--ghost'}`} disabled={saving} onClick={toggleWatchlist}>
                                {saved ? <BookmarkCheck size={19} /> : <Bookmark size={19} />}
                                {saved ? 'Đã thêm vào Xem sau' : 'Thêm vào Xem sau'}
                            </button>
                        </div>
                    </div>

                    <aside className="movie-detail-side">
                        <h2 className="movie-detail-side__title">Thông tin phim</h2>
                        <dl className="detail-facts">
                            <div><dt>Diễn viên</dt><dd>{movie.actor?.slice(0, 8).join(', ') || 'Đang cập nhật'}</dd></div>
                            <div><dt>Đạo diễn</dt><dd>{movie.director?.join(', ') || 'Đang cập nhật'}</dd></div>
                            <div><dt>Thể loại</dt><dd>{genres}</dd></div>
                            <div><dt>Quốc gia</dt><dd>{countries}</dd></div>
                        </dl>
                    </aside>
                </div>
            </section>

            <div className="site-container movie-detail-content">
                {servers.length > 0 && (
                    <section className="detail-section">
                        <div className="detail-section-heading">
                            <h2>Danh sách tập</h2>
                            <span>{episodeCount} tập</span>
                        </div>
                        <div className="space-y-5">
                            {servers.map(([server, items]) => (
                                <div key={server}>
                                    <p className="mb-3 text-sm font-semibold text-zinc-500">{server}</p>
                                    <div className="flex flex-wrap gap-2">
                                        {items.map((episode) => (
                                            <button className="episode-button" key={episode.id} onClick={() => play(episode.id)}>
                                                <Film size={14} /> Tập {episode.name}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                )}

                {recommendations.length > 0 && (
                    <MovieSection
                        compact
                        movies={recommendations}
                        moreTo={`/search?genre_id=${movie.genres?.[0]?.id}`}
                        title="Đề xuất cho bạn"
                    />
                )}

                <section className="detail-comments detail-comments--single">
                    <div className="detail-comments-main">
                        <div className="detail-section-heading">
                            <h2>Bình luận</h2>
                            <span>{comments.length} bình luận</span>
                        </div>
                        <form className="detail-comment-form" onSubmit={submitComment}>
                            <div className={`avatar ${!isAuthenticated ? 'avatar--guest' : ''}`}>
                                {isAuthenticated ? user?.name?.charAt(0)?.toUpperCase() : <UserRound size={18} />}
                            </div>
                            <input
                                className="input flex-1"
                                placeholder={isAuthenticated ? 'Thêm bình luận...' : 'Đăng nhập để bình luận...'}
                                value={comment}
                                onChange={(event) => setComment(event.target.value)}
                                onFocus={() => {
                                    if (!isAuthenticated) {
                                        navigate('/login', { state: { from: { pathname: `/movies/${movieId}` } } });
                                    }
                                }}
                            />
                            <button className="button button--primary">{isAuthenticated ? 'Đăng' : 'Đăng nhập'}</button>
                        </form>
                        <div className="mt-7 space-y-5">
                            {comments.length ? comments.map((item) => (
                                <article className="comment" key={item.id}>
                                    <div className="avatar">{item.user?.name?.charAt(0)?.toUpperCase()}</div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center justify-between gap-3">
                                            <strong>{item.user?.name}</strong>
                                            {item.user_id === user?.id && (
                                                <button className="text-xs text-red-400" onClick={() => removeComment(item.id)}>Xóa</button>
                                            )}
                                        </div>
                                        <p className="mt-2 text-sm leading-6 text-zinc-400">{item.content}</p>
                                    </div>
                                </article>
                            )) : <p className="text-sm text-zinc-500">Chưa có bình luận. Hãy chia sẻ cảm nhận đầu tiên.</p>}
                        </div>
                    </div>

                </section>
            </div>
        </div>
    );
}
