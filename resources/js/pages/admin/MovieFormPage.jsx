import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { api } from '../../api';
import { useAdminToast } from './AdminLayout';
import { ArrowLeft, Save } from 'lucide-react';
import { movieImage } from '../../utils';

export default function MovieFormPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { showToast } = useAdminToast();

    const [loading, setLoading] = useState(false);
    const [categories, setCategories] = useState([]);

    const [formData, setFormData] = useState({
        name: '',
        origin_name: '',
        content: '',
        poster_url: '',
        thumb_url: '',
        trailer_url: '',
        year: new Date().getFullYear(),
        time: '',
        is_premium: false,
        is_pinned: false,
        status: 'pending',
        genres: []
    });

    useEffect(() => {
        const fetchInitialData = async () => {
            try {
                const catRes = await api.getAdminCategories ? api.getAdminCategories() : api.genres();
                setCategories(catRes.data?.data || catRes.data || []);

                if (id) {
                    const movieRes = await api.getAdminMovie(id);
                    const movie = movieRes.data?.data || movieRes.data;
                    setFormData({
                        name: movie.name || '',
                        origin_name: movie.origin_name || '',
                        content: movie.content || '',
                        poster_url: movie.poster_url || '',
                        thumb_url: movie.thumb_url || '',
                        trailer_url: movie.trailer_url || '',
                        year: movie.year || new Date().getFullYear(),
                        time: movie.time || '',
                        is_premium: movie.is_premium || false,
                        is_pinned: movie.is_pinned || false,
                        status: movie.status || 'pending',
                        genres: movie.genres?.map(g => g.id) || []
                    });
                }
            } catch (err) {
                showToast('error', 'Lỗi tải dữ liệu');
            }
        };
        fetchInitialData();
    }, [id]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        try {
            if (id) {
                await api.updateAdminMovie(id, formData);
                showToast('success', 'Cập nhật phim thành công');
            } else {
                await api.createAdminMovie(formData);
                showToast('success', 'Thêm phim mới thành công');
                navigate('/admin/movies');
            }
        } catch (err) {
            showToast('error', err.response?.data?.message || 'Có lỗi xảy ra');
        } finally {
            setLoading(false);
        }
    };

    const handleGenreChange = (genreId) => {
        setFormData(prev => {
            const genres = prev.genres.includes(genreId)
                ? prev.genres.filter(id => id !== genreId)
                : [...prev.genres, genreId];
            return { ...prev, genres };
        });
    };

    return (
        <div className="max-w-4xl mx-auto space-y-6">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <button onClick={() => navigate(-1)} className="p-2 bg-[#151B27] border border-white/10 rounded-lg text-zinc-400 hover:text-white transition-colors">
                        <ArrowLeft size={20} />
                    </button>
                    <h1 className="text-2xl font-bold text-white">{id ? 'Sửa Phim' : 'Thêm Phim Mới'}</h1>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="bg-[#151B27] border border-white/10 rounded-xl p-6 space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="block text-sm font-medium text-zinc-400 mb-1">Tên phim *</label>
                        <input required type="text" value={formData.name} onChange={e => setFormData({...formData, name: e.target.value})} className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:border-[#E50914] outline-none" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-zinc-400 mb-1">Tên gốc</label>
                        <input type="text" value={formData.origin_name} onChange={e => setFormData({...formData, origin_name: e.target.value})} className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:border-[#E50914] outline-none" />
                    </div>
                    <div className="md:col-span-2">
                        <label className="block text-sm font-medium text-zinc-400 mb-1">Mô tả nội dung</label>
                        <textarea rows="4" value={formData.content} onChange={e => setFormData({...formData, content: e.target.value})} className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:border-[#E50914] outline-none resize-y" />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-zinc-400 mb-1">Poster URL *</label>
                        <input required type="text" value={formData.poster_url} onChange={e => setFormData({...formData, poster_url: e.target.value})} className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:border-[#E50914] outline-none mb-2" />
                        {formData.poster_url && <img src={movieImage({ poster_url: formData.poster_url }, 'backdrop')} alt="Preview" className="h-40 rounded object-cover" />}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-zinc-400 mb-1">Trailer URL (YouTube embed)</label>
                        <input type="url" value={formData.trailer_url} onChange={e => setFormData({...formData, trailer_url: e.target.value})} className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:border-[#E50914] outline-none" />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-zinc-400 mb-1">Năm phát hành</label>
                        <input type="number" value={formData.year} onChange={e => setFormData({...formData, year: Number(e.target.value)})} className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:border-[#E50914] outline-none" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-zinc-400 mb-1">Thời lượng</label>
                        <input type="text" value={formData.time} onChange={e => setFormData({...formData, time: e.target.value})} className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:border-[#E50914] outline-none" placeholder="VD: 120 phút" />
                    </div>

                    <div className="md:col-span-2">
                        <label className="block text-sm font-medium text-zinc-400 mb-2">Thể loại</label>
                        <div className="flex flex-wrap gap-2">
                            {categories.map(cat => (
                                <label key={cat.id} className={`px-3 py-1.5 rounded-full border text-sm cursor-pointer transition-colors ${formData.genres.includes(cat.id) ? 'bg-[#E50914]/10 border-[#E50914] text-[#E50914]' : 'bg-[#101521] border-white/10 text-zinc-400 hover:text-white'}`}>
                                    <input type="checkbox" className="hidden" checked={formData.genres.includes(cat.id)} onChange={() => handleGenreChange(cat.id)} />
                                    {cat.name}
                                </label>
                            ))}
                        </div>
                    </div>

                    <div className="md:col-span-2 flex gap-8 p-4 bg-[#101521] rounded-lg border border-white/10">
                        <label className="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" checked={formData.is_premium} onChange={e => setFormData({...formData, is_premium: e.target.checked})} className="w-5 h-5 rounded bg-[#151B27] border-white/10 text-amber-500 focus:ring-amber-500" />
                            <span className="font-medium text-amber-500">Phim Premium</span>
                        </label>
                        <label className="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" checked={formData.is_pinned} onChange={e => setFormData({...formData, is_pinned: e.target.checked})} className="w-5 h-5 rounded bg-[#151B27] border-white/10 text-blue-500 focus:ring-blue-500" />
                            <span className="font-medium text-blue-400">Ghim trang chủ</span>
                        </label>
                    </div>
                </div>

                <div className="flex justify-end gap-4 pt-4 border-t border-white/10">
                    <button type="button" onClick={() => navigate(-1)} className="px-6 py-2.5 rounded-lg font-medium text-zinc-300 hover:text-white hover:bg-white/5 transition-colors">
                        Hủy
                    </button>
                    <button type="submit" disabled={loading} className="bg-[#E50914] hover:bg-[#E50914]/90 text-white px-6 py-2.5 rounded-lg font-medium flex items-center gap-2 disabled:opacity-50 transition-colors">
                        <Save size={18} />
                        {loading ? 'Đang lưu...' : 'Lưu Phim'}
                    </button>
                </div>
            </form>
        </div>
    );
}
