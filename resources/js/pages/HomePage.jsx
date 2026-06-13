import React, { useEffect, useMemo, useState } from 'react';
import { BookmarkPlus, CirclePlay, Info, Star, Video } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { api, apiError } from '../api';
import Loading from '../components/Loading';
import MovieSection from '../components/MovieSection';
import { useAuth } from '../context/AuthContext';
import { useUi } from '../context/UiContext';
import { movieImage, paginatePayload, stripHtml, youtubeEmbedUrl } from '../utils';

const HOME_GENRES = ['hanh-dong', 'tinh-cam', 'co-trang', 'kinh-di'];

export default function HomePage() {
    const { toast, openModal, closeModal } = useUi();
    const { isAuthenticated } = useAuth();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [featured, setFeatured] = useState([]);
    const [latest, setLatest] = useState([]);
    const [genreSections, setGenreSections] = useState([]);
    const [heroRating, setHeroRating] = useState(null);
    const [activeSlide, setActiveSlide] = useState(0);

    useEffect(() => {
        let active = true;

        const loadHome = async () => {
            try {
                const [featuredResult, latestResult, genresResult] = await Promise.allSettled([
                    api.featuredMovies(),
                    api.latestMovies({ per_page: 12 }),
                    api.genres(),
                ]);

                if (!active) return;
                if (featuredResult.status === 'fulfilled') setFeatured(featuredResult.value.data.data || []);
                if (latestResult.status === 'fulfilled') setLatest(paginatePayload(latestResult.value.data));
                setLoading(false);

                if (genresResult.status === 'fulfilled') {
                    const allGenres = genresResult.value.data.data || [];
                    const genres = HOME_GENRES
                        .map((slug) => allGenres.find((genre) => genre.slug === slug))
                        .filter(Boolean);
                    const results = await Promise.allSettled(
                        genres.map((genre) => api.genreMovies(genre.id, { per_page: 12 })),
                    );

                    if (!active) return;
                    setGenreSections(results.flatMap((result, index) => {
                        if (result.status !== 'fulfilled') return [];
                        const movies = result.value.data.data?.movies?.data || [];
                        return movies.length ? [{ genre: genres[index], movies }] : [];
                    }));
                }
            } finally {
                if (active) setLoading(false);
            }
        };

        loadHome();

        return () => {
            active = false;
        };
    }, []);

    useEffect(() => {
        if (featured.length < 2) return undefined;
        const interval = window.setInterval(() => {
            setActiveSlide((index) => (index + 1) % featured.length);
        }, 7000);
        return () => window.clearInterval(interval);
    }, [featured.length]);

    const hero = useMemo(() => featured[activeSlide] || latest[0], [featured, latest, activeSlide]);

    useEffect(() => {
        setHeroRating(null);
        if (!hero?.id) return;
        api.movieRatings(hero.id)
            .then((response) => setHeroRating(response.data.data))
            .catch(() => {});
    }, [hero?.id]);

    const showTrailer = () => {
        const trailerUrl = youtubeEmbedUrl(hero?.trailer_url);
        if (!trailerUrl) {
            toast('Phim này chưa có trailer.', 'error');
            return;
        }

        openModal(
            <div className="pt-7">
                <h2 className="mb-4 text-xl font-bold">Trailer: {hero.name}</h2>
                <iframe
                    allow="autoplay; encrypted-media; picture-in-picture"
                    allowFullScreen
                    className="aspect-video w-full rounded-xl bg-black"
                    src={trailerUrl}
                    title={`Trailer ${hero.name}`}
                />
            </div>,
        );
    };

    const play = async () => {
        if (!hero) return;
        if (!isAuthenticated) {
            navigate('/login', { state: { from: { pathname: `/movies/${hero.id}` } } });
            return;
        }
        try {
            const response = await api.movieStream(hero.id);
            navigate(`/watch/${hero.id}`, { state: { stream: response.data.data } });
        } catch (error) {
            const code = error.response?.data?.error;
            if (['require_subscription', 'require_vip_upgrade'].includes(code)) {
                openModal(
                    <div>
                        <span className="eyebrow">Quyền xem phim</span>
                        <h2 className="mt-3 text-2xl font-bold">
                            {code === 'require_vip_upgrade' ? 'Nâng cấp VIP để tiếp tục' : 'Đăng ký gói để xem phim'}
                        </h2>
                        <p className="mt-3 text-zinc-400">{error.response.data.message}</p>
                        <button className="button button--vip mt-6 w-full" onClick={() => { closeModal(); navigate('/plans'); }}>
                            Xem gói cước
                        </button>
                    </div>,
                );
                return;
            }
            toast(apiError(error), 'error');
        }
    };

    const addWatchlist = async () => {
        if (!isAuthenticated) {
            navigate('/login', { state: { from: { pathname: '/' } } });
            return;
        }
        try {
            await api.addWatchlist(hero.id);
            toast('Đã thêm phim vào Xem sau.');
        } catch (error) {
            toast(apiError(error), 'error');
        }
    };

    if (loading) return <Loading label="Đang tải trang chủ..." />;

    return (
        <>
            {hero && (
                <section className="hero" style={{ backgroundImage: `url("${movieImage(hero, 'backdrop')}")` }}>
                    <div className="hero__shade" />
                    <div className="site-container relative z-10 flex min-h-[72vh] items-end pb-16 pt-24 md:items-center md:pb-10">
                        <div className="max-w-2xl">
                            <div className="flex items-center gap-3">
                                <span className="eyebrow">Phim nổi bật</span>
                                <span className={`plan-badge static ${hero.is_premium ? 'plan-badge--vip' : 'plan-badge--standard'}`}>
                                    {hero.is_premium ? 'VIP' : 'STANDARD'}
                                </span>
                            </div>
                            <h1 className="mt-4 text-4xl font-extrabold leading-tight md:text-6xl">{hero.name}</h1>
                            <div className="mt-5 flex flex-wrap items-center gap-3 text-sm text-zinc-300">
                                <span>{hero.year || 'Đang cập nhật'}</span>
                                <span>•</span>
                                <span>{hero.genres?.map((genre) => genre.name).join(', ') || hero.quality || hero.type}</span>
                                <span>•</span>
                                <span className="flex items-center gap-1 text-amber-300">
                                    <Star size={15} fill="currentColor" /> {heroRating?.average || 0}/10
                                </span>
                            </div>
                            <p className="mt-5 line-clamp-3 max-w-xl text-base leading-7 text-zinc-300 md:text-lg">
                                {stripHtml(hero.content) || 'Khám phá nội dung phim và bắt đầu trải nghiệm xem phim trên CineON.'}
                            </p>
                            <div className="mt-8 flex flex-wrap gap-3">
                                <button className="button button--primary" onClick={play}><CirclePlay size={20} /> Xem phim</button>
                                <button className="button button--secondary" onClick={showTrailer}><Video size={19} /> Trailer</button>
                                <Link className="button button--secondary" to={`/movies/${hero.id}`}><Info size={19} /> Chi tiết</Link>
                                <button className="button button--ghost" onClick={addWatchlist}><BookmarkPlus size={19} /> Xem sau</button>
                            </div>
                        </div>
                    </div>
                    {featured.length > 1 && (
                        <div className="absolute bottom-7 right-6 z-20 flex gap-2 md:right-12">
                            {featured.map((movie, index) => (
                                <button
                                    aria-label={`Chuyển đến ${movie.name}`}
                                    className={`h-1.5 rounded-full transition-all ${index === activeSlide ? 'w-9 bg-red-500' : 'w-4 bg-white/35'}`}
                                    key={movie.id}
                                    onClick={() => setActiveSlide(index)}
                                />
                            ))}
                        </div>
                    )}
                </section>
            )}

            <div className="site-container py-12">
                <MovieSection
                    compact
                    movies={latest}
                    onLoadAll={async () => {
                        const response = await api.latestMovies({ per_page: 100 });
                        return paginatePayload(response.data);
                    }}
                    title="Phim mới cập nhật"
                />
                {genreSections.map(({ genre, movies }) => (
                    <MovieSection
                        compact
                        key={genre.id}
                        movies={movies}
                        onLoadAll={async () => {
                            const response = await api.genreMovies(genre.id, { per_page: 100 });
                            return response.data.data?.movies?.data || [];
                        }}
                        title={genre.name}
                    />
                ))}
            </div>
        </>
    );
}
