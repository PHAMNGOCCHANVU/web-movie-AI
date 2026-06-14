import React, { useEffect, useRef, useState } from 'react';
import Hls from 'hls.js';
import { ArrowLeft, Bookmark, BookmarkCheck, Eye, MessageCircle, Star } from 'lucide-react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { api, apiError } from '../api';
import Loading from '../components/Loading';
import MovieSection from '../components/MovieSection';
import { useAuth } from '../context/AuthContext';
import { useUi } from '../context/UiContext';
import { stripHtml } from '../utils';

export default function WatchPage() {
    const { movieId } = useParams();
    const location = useLocation();
    const { user } = useAuth();
    const { toast } = useUi();
    const videoRef = useRef(null);
    const lastSaved = useRef(0);
    const progressRef = useRef(location.state?.progressSeconds || 0);
    const episodeRef = useRef(location.state?.episodeId || null);
    const [movie, setMovie] = useState(null);
    const [stream, setStream] = useState(location.state?.stream || null);
    const [episodes, setEpisodes] = useState({});
    const [episodeId, setEpisodeId] = useState(location.state?.episodeId || null);
    const [loading, setLoading] = useState(!stream);
    const [comments, setComments] = useState([]);
    const [comment, setComment] = useState('');
    const [ratings, setRatings] = useState(null);
    const [recommendations, setRecommendations] = useState([]);
    const [saved, setSaved] = useState(false);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        Promise.all([
            api.movie(movieId),
            api.movieEpisodes(movieId),
            api.movieComments(movieId),
            api.movieRatings(movieId),
            api.watchlist(),
        ])
            .then(async ([movieResponse, episodeResponse, commentResponse, ratingResponse, watchlistResponse]) => {
                const movieData = movieResponse.data.data;
                setMovie(movieData);
                setEpisodes(episodeResponse.data.data?.episodes || {});
                setComments(commentResponse.data.data?.data || []);
                setRatings(ratingResponse.data.data);
                setSaved((watchlistResponse.data.data || []).some((item) => Number(item.id) === Number(movieId)));

                const genreId = movieData.genres?.[0]?.id;
                if (genreId) {
                    const response = await api.genreMovies(genreId, { per_page: 12 });
                    setRecommendations(
                        (response.data.data?.movies?.data || [])
                            .filter((item) => Number(item.id) !== Number(movieId))
                            .slice(0, 10),
                    );
                }
            })
            .catch((error) => toast(apiError(error), 'error'));
    }, [movieId, toast]);

    const submitComment = async (event) => {
        event.preventDefault();
        if (!comment.trim()) return;

        try {
            const response = await api.postComment(movieId, comment.trim());
            setComments((items) => [response.data.data, ...items]);
            setComment('');
            toast('Đã đăng bình luận.');
        } catch (error) {
            toast(apiError(error), 'error');
        }
    };

    const toggleWatchlist = async () => {
        setSaving(true);
        try {
            if (saved) {
                await api.removeWatchlist(movieId);
                setSaved(false);
                toast('Đã bỏ phim khỏi Xem sau.');
            } else {
                await api.addWatchlist(movieId);
                setSaved(true);
                toast('Đã lưu phim vào Xem sau.');
            }
        } catch (error) {
            toast(apiError(error), 'error');
        } finally {
            setSaving(false);
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

    useEffect(() => {
        if (stream) return;
        setLoading(true);
        api.movieStream(movieId, episodeId)
            .then((response) => setStream(response.data.data))
            .catch((error) => toast(apiError(error), 'error'))
            .finally(() => setLoading(false));
    }, [movieId, episodeId, stream, toast]);

    const chooseEpisode = async (id) => {
        setLoading(true);
        try {
            const response = await api.movieStream(movieId, id);
            setEpisodeId(id);
            episodeRef.current = id;
            progressRef.current = 0;
            setStream(response.data.data);
            lastSaved.current = 0;
        } catch (error) {
            toast(apiError(error), 'error');
        } finally {
            setLoading(false);
        }
    };

    const saveProgress = async (force = false) => {
        const video = videoRef.current;
        if (!video || (!force && video.currentTime - lastSaved.current < 20)) return;
        lastSaved.current = video.currentTime;

        try {
            await api.saveProgress({
                movie_id: Number(movieId),
                episode_id: episodeRef.current || stream?.episode_id || null,
                progress_seconds: Math.floor(video.currentTime),
                duration_seconds: Number.isFinite(video.duration) ? Math.floor(video.duration) : 0,
                season_id: null,
            });
        } catch {
            // Progress sync should not interrupt playback.
        }
    };

    useEffect(() => {
        episodeRef.current = episodeId || stream?.episode_id || null;
        if (!episodeId && stream?.episode_id) {
            setEpisodeId(stream.episode_id);
        }
    }, [episodeId, stream?.episode_id]);

    useEffect(() => {
        const saveBeforeLeave = () => saveProgress(true);
        window.addEventListener('beforeunload', saveBeforeLeave);
        return () => {
            window.removeEventListener('beforeunload', saveBeforeLeave);
            saveProgress(true);
        };
    }, []);

    const isEmbed = stream?.stream_type === 'embed';

    useEffect(() => {
        const video = videoRef.current;
        if (!video || !stream?.stream_url || isEmbed) return undefined;

        if (stream.stream_type === 'm3u8' && Hls.isSupported()) {
            const hls = new Hls();
            hls.loadSource(stream.stream_url);
            hls.attachMedia(video);
            return () => hls.destroy();
        }

        video.src = stream.stream_url;
        return undefined;
    }, [stream, isEmbed]);

    return (
        <div className="site-container py-8">
            <Link className="mb-5 inline-flex items-center gap-2 text-sm text-zinc-400 hover:text-white" to={`/movies/${movieId}`}>
                <ArrowLeft size={17} /> Quay lại chi tiết
            </Link>

            {loading ? <Loading label="Đang chuẩn bị nguồn phát..." /> : (
                <div className="overflow-hidden rounded-2xl border border-white/10 bg-black shadow-2xl">
                    {isEmbed ? (
                        <iframe
                            allow="autoplay; fullscreen; picture-in-picture"
                            className="aspect-video w-full"
                            src={stream?.stream_url}
                            title={movie?.name || 'CineON player'}
                        />
                    ) : (
                        <video
                            controls
                            autoPlay
                            className="aspect-video w-full bg-black"
                            onPause={() => saveProgress(true)}
                            onEnded={() => saveProgress(true)}
                            onLoadedMetadata={(event) => {
                                if (progressRef.current > 0 && progressRef.current < event.currentTarget.duration) {
                                    event.currentTarget.currentTime = progressRef.current;
                                    lastSaved.current = progressRef.current;
                                    progressRef.current = 0;
                                }
                            }}
                            onTimeUpdate={() => saveProgress(false)}
                            ref={videoRef}
                        />
                    )}
                </div>
            )}

            <section className="watch-info">
                <div className="watch-info__main">
                    <span className="eyebrow">Đang xem</span>
                    <h1>{movie?.name}</h1>
                    {movie?.origin_name && <p className="watch-info__origin">{movie.origin_name}</p>}
                    <div className="watch-info__meta">
                        <span>{movie?.year || 'Đang cập nhật'}</span>
                        <span>{movie?.quality || 'HD'}</span>
                        <span>{movie?.lang || 'Phụ đề'}</span>
                        <span>{movie?.episode_current || movie?.time || 'Đang cập nhật'}</span>
                        <span className="text-amber-300"><Star size={15} fill="currentColor" /> {ratings?.average || 0}/10</span>
                        <span><Eye size={15} /> {Number(movie?.view_count || 0).toLocaleString('vi-VN')} lượt xem</span>
                    </div>
                    <p className="watch-info__summary">{stripHtml(movie?.content || '')}</p>
                </div>

                <aside className="watch-info__side">
                    <button className={`watch-save ${saved ? 'watch-save--active' : ''}`} disabled={saving} onClick={toggleWatchlist}>
                        {saved ? <BookmarkCheck size={24} /> : <Bookmark size={24} />}
                        <strong>{saved ? 'Đã thêm vào Xem sau' : 'Thêm vào Xem sau'}</strong>
                    </button>
                    <dl className="detail-facts">
                        <div><dt>Diễn viên</dt><dd>{movie?.actor?.slice(0, 8).join(', ') || 'Đang cập nhật'}</dd></div>
                        <div><dt>Đạo diễn</dt><dd>{movie?.director?.join(', ') || 'Đang cập nhật'}</dd></div>
                        <div><dt>Thể loại</dt><dd>{movie?.genres?.map((genre) => genre.name).join(', ') || 'Đang cập nhật'}</dd></div>
                    </dl>
                </aside>
            </section>

            {Object.keys(episodes).length > 0 && (
                <section className="detail-section">
                    <div className="detail-section-heading">
                        <h2>Danh sách tập</h2>
                    </div>
                    {Object.entries(episodes).map(([server, items]) => (
                        <div className="mt-5" key={server}>
                            <p className="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-500">{server}</p>
                            <div className="flex flex-wrap gap-2">
                                {items.map((episode) => (
                                    <button
                                        className={`episode-button ${Number(episodeId) === episode.id ? 'episode-button--active' : ''}`}
                                        key={episode.id}
                                        onClick={() => chooseEpisode(episode.id)}
                                    >
                                        Tập {episode.name}
                                    </button>
                                ))}
                            </div>
                        </div>
                    ))}
                </section>
            )}

            {recommendations.length > 0 && (
                <MovieSection
                    compact
                    movies={recommendations}
                    title="Đề xuất cho bạn"
                />
            )}

            <section className="panel mt-10 p-6">
                <div className="flex items-center gap-3">
                    <MessageCircle className="text-red-400" />
                    <h2 className="text-xl font-bold">Bình luận</h2>
                </div>
                <form className="mt-5 flex gap-3" onSubmit={submitComment}>
                    <input
                        className="input flex-1"
                        placeholder="Chia sẻ cảm nhận về bộ phim..."
                        value={comment}
                        onChange={(event) => setComment(event.target.value)}
                    />
                    <button className="button button--primary">Đăng</button>
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
                    )) : <p className="text-sm text-zinc-500">Chưa có bình luận.</p>}
                </div>
            </section>
        </div>
    );
}
