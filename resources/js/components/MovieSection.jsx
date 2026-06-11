import React, { useEffect, useState } from 'react';
import { ChevronRight, X } from 'lucide-react';
import { Link } from 'react-router-dom';
import EmptyState from './EmptyState';
import MovieCard from './MovieCard';

export default function MovieSection({
    title,
    subtitle,
    movies = [],
    moreTo,
    onLoadAll,
    compact = false,
}) {
    const [expanded, setExpanded] = useState(false);
    const [allMovies, setAllMovies] = useState([]);
    const [loadingAll, setLoadingAll] = useState(false);

    useEffect(() => {
        if (!expanded) return undefined;

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = previousOverflow;
        };
    }, [expanded]);

    const toggleAll = async () => {
        if (!allMovies.length && onLoadAll) {
            setLoadingAll(true);
            try {
                setAllMovies(await onLoadAll());
            } finally {
                setLoadingAll(false);
            }
        }
        setExpanded(true);
    };

    return (
        <section className="section-block">
            <div className="mb-5 flex items-end justify-between gap-4">
                <div>
                    {subtitle && <span className="eyebrow">{subtitle}</span>}
                    <h2 className="section-title">{title}</h2>
                </div>
                {onLoadAll ? (
                    <button className="section-more-button" disabled={loadingAll} onClick={toggleAll}>
                        {loadingAll ? 'Đang tải...' : 'Xem tất cả'}
                        <ChevronRight size={17} />
                    </button>
                ) : moreTo && (
                    <Link className="flex items-center gap-1 text-sm font-semibold text-zinc-400 hover:text-white" to={moreTo}>
                        Xem tất cả <ChevronRight size={17} />
                    </Link>
                )}
            </div>

            {movies.length ? (
                <div className={compact ? 'movie-row' : 'movie-grid'}>
                    {movies.map((movie) => <MovieCard key={movie.id} movie={movie} />)}
                </div>
            ) : (
                <EmptyState message="Backend chưa có phim phù hợp để hiển thị." />
            )}

            {expanded && (
                <div className="section-overlay" role="dialog" aria-modal="true">
                    <div className="section-overlay__backdrop" onClick={() => setExpanded(false)} />
                    <div className="section-overlay__panel">
                        <header className="section-overlay__header">
                            <div>
                                {subtitle && <span className="eyebrow">{subtitle}</span>}
                                <h2>{title}</h2>
                                <p>{allMovies.length} phim</p>
                            </div>
                            <button className="section-overlay__close" onClick={() => setExpanded(false)}>
                                <X size={21} /> Đóng
                            </button>
                        </header>
                        <div className="section-overlay__content">
                            <div className="movie-grid">
                                {allMovies.map((movie) => <MovieCard key={movie.id} movie={movie} />)}
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </section>
    );
}
