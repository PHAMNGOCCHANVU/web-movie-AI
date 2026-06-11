import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Filter, Search } from 'lucide-react';
import { useSearchParams } from 'react-router-dom';
import { api, apiError } from '../api';
import EmptyState from '../components/EmptyState';
import Loading from '../components/Loading';
import MovieCard from '../components/MovieCard';
import { useUi } from '../context/UiContext';
import { paginatePayload } from '../utils';

export default function SearchPage() {
    const { toast } = useUi();
    const [params, setParams] = useSearchParams();
    const [genres, setGenres] = useState([]);
    const [movies, setMovies] = useState([]);
    const [loading, setLoading] = useState(true);
    const requestId = useRef(0);
    const debounceTimer = useRef(null);
    const [form, setForm] = useState({
        keyword: params.get('keyword') || '',
        genre_id: params.get('genre_id') || '',
        year: params.get('year') || '',
    });

    useEffect(() => {
        api.genres().then((response) => setGenres(response.data.data || [])).catch(() => {});
    }, []);

    const search = useCallback(async (values) => {
        const currentRequest = ++requestId.current;
        setLoading(true);
        const query = Object.fromEntries(Object.entries(values).filter(([, value]) => value));
        setParams(query);

        try {
            const response = await api.searchMovies({ ...query, per_page: 24 });
            if (currentRequest === requestId.current) {
                setMovies(paginatePayload(response.data));
            }
        } catch (error) {
            if (currentRequest === requestId.current) {
                toast(apiError(error), 'error');
            }
        } finally {
            if (currentRequest === requestId.current) {
                setLoading(false);
            }
        }
    }, [setParams, toast]);

    useEffect(() => {
        window.clearTimeout(debounceTimer.current);
        debounceTimer.current = window.setTimeout(() => search(form), 400);

        return () => window.clearTimeout(debounceTimer.current);
    }, [form, search]);

    const submit = (event) => {
        event.preventDefault();
        window.clearTimeout(debounceTimer.current);
        search(form);
    };

    return (
        <div className="site-container py-8">
            <form className="search-panel" onSubmit={submit}>
                <label className="search-box">
                    <Search size={20} />
                    <input
                        placeholder="Nhập tên phim..."
                        value={form.keyword}
                        onChange={(event) => setForm({ ...form, keyword: event.target.value })}
                    />
                </label>
                <label className="filter-select">
                    <Filter size={17} />
                    <select value={form.genre_id} onChange={(event) => setForm({ ...form, genre_id: event.target.value })}>
                        <option value="">Tất cả thể loại</option>
                        {genres.map((genre) => <option key={genre.id} value={genre.id}>{genre.name}</option>)}
                    </select>
                </label>
                <label className="filter-select">
                    <select value={form.year} onChange={(event) => setForm({ ...form, year: event.target.value })}>
                        <option value="">Tất cả năm</option>
                        {Array.from({ length: 20 }, (_, index) => new Date().getFullYear() - index)
                            .map((year) => <option key={year}>{year}</option>)}
                    </select>
                </label>
                <button className="button button--primary">Tìm kiếm</button>
            </form>

            <div className="mt-10">
                <div className="mb-5 flex items-center justify-between">
                    <h2 className="text-xl font-bold">Kết quả tìm kiếm</h2>
                    <span className="text-sm text-zinc-500">{loading ? 'Đang tìm...' : `${movies.length} phim`}</span>
                </div>
                {loading ? <Loading /> : movies.length ? (
                    <div className="movie-grid">{movies.map((movie) => <MovieCard key={movie.id} movie={movie} />)}</div>
                ) : (
                    <EmptyState title="Không tìm thấy phim" message="Thử đổi từ khóa hoặc bỏ bớt bộ lọc." />
                )}
            </div>
        </div>
    );
}
