import React, { useEffect, useState } from 'react';
import { api } from '../../api';
import AdminTable from '../../components/admin/AdminTable';
import AdminBadge from '../../components/admin/AdminBadge';
import AdminModal from '../../components/admin/AdminModal';
import { useAdminToast } from './AdminLayout';
import { Edit2, Trash2, Plus, GripVertical } from 'lucide-react';

export default function HomepageBlockPage() {
    const { showToast } = useAdminToast();
    const [blocks, setBlocks] = useState([]);
    const [loading, setLoading] = useState(true);
    
    const [formModal, setFormModal] = useState({ isOpen: false, block: null });
    const [formData, setFormData] = useState({ title: '', source_type: 'latest', source_ref: '', is_visible: true, sort_order: 0 });

    const fetchBlocks = async () => {
        setLoading(true);
        try {
            const res = await api.getHomepageBlocks();
            setBlocks(res.data?.data || res.data || []);
        } catch (err) {
            showToast('error', 'Lỗi tải danh sách blocks');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchBlocks();
    }, []);

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (formModal.block) {
                await api.updateHomepageBlock(formModal.block.id, formData);
                showToast('success', 'Đã cập nhật block');
            } else {
                await api.createHomepageBlock(formData);
                showToast('success', 'Đã thêm block mới');
            }
            fetchBlocks();
            setFormModal({ isOpen: false, block: null });
        } catch (err) {
            showToast('error', err.response?.data?.message || 'Có lỗi xảy ra');
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm('Bạn có chắc muốn xóa block này?')) return;
        try {
            await api.deleteHomepageBlock(id);
            showToast('success', 'Đã xóa block');
            fetchBlocks();
        } catch (err) {
            showToast('error', 'Lỗi khi xóa block');
        }
    };

    const openModal = (block = null) => {
        if (block) {
            setFormData({
                title: block.title,
                source_type: block.source_type,
                source_ref: block.source_ref || '',
                is_visible: block.is_visible,
                sort_order: block.sort_order || 0
            });
        } else {
            setFormData({ title: '', source_type: 'latest', source_ref: '', is_visible: true, sort_order: blocks.length });
        }
        setFormModal({ isOpen: true, block });
    };

    const columns = [
        { 
            header: '', 
            accessor: 'drag', 
            className: 'w-10',
            render: () => <GripVertical className="text-zinc-600 cursor-grab" size={18} /> 
        },
        { header: 'Tiêu đề', accessor: 'title', render: (row) => <span className="font-medium text-white">{row.title}</span> },
        { header: 'Loại nguồn', accessor: 'source_type', render: (row) => <AdminBadge status="active" text={row.source_type} /> },
        { header: 'Tham chiếu', accessor: 'source_ref', render: (row) => <span className="text-zinc-400">{row.source_ref || '-'}</span> },
        { header: 'Thứ tự', accessor: 'sort_order', render: (row) => <span className="text-white">{row.sort_order}</span> },
        { header: 'Hiển thị', accessor: 'is_visible', render: (row) => <AdminBadge status={row.is_visible ? 'success' : 'failed'} text={row.is_visible ? 'Hiện' : 'Ẩn'} /> },
        { 
            header: 'Thao tác', 
            accessor: 'actions', 
            render: (row) => (
                <div className="flex items-center gap-2">
                    <button onClick={() => openModal(row)} className="p-1.5 text-zinc-400 hover:text-white hover:bg-white/5 rounded" title="Sửa">
                        <Edit2 size={16} />
                    </button>
                    <button onClick={() => handleDelete(row.id)} className="p-1.5 text-red-500 hover:bg-red-500/10 rounded" title="Xóa">
                        <Trash2 size={16} />
                    </button>
                </div>
            ) 
        }
    ];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold text-white">Homepage Blocks</h1>
                <button onClick={() => openModal()} className="bg-[#E50914] hover:bg-[#E50914]/90 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
                    <Plus size={18} />
                    Thêm Block
                </button>
            </div>

            <AdminTable columns={columns} data={blocks} loading={loading} />

            <AdminModal
                isOpen={formModal.isOpen}
                onClose={() => setFormModal({ isOpen: false, block: null })}
                title={formModal.block ? 'Sửa Block' : 'Thêm Block'}
            >
                <form id="block-form" onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-zinc-400 mb-1">Tiêu đề *</label>
                        <input 
                            required
                            type="text" 
                            value={formData.title} 
                            onChange={e => setFormData({...formData, title: e.target.value})}
                            className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#E50914]"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-zinc-400 mb-1">Loại nguồn *</label>
                        <select 
                            value={formData.source_type} 
                            onChange={e => setFormData({...formData, source_type: e.target.value})}
                            className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#E50914]"
                        >
                            <option value="latest">Mới cập nhật (latest)</option>
                            <option value="genre">Theo thể loại (genre)</option>
                            <option value="pinned">Đã ghim (pinned)</option>
                            <option value="custom">Tùy chỉnh (custom)</option>
                        </select>
                    </div>
                    {formData.source_type === 'genre' && (
                        <div>
                            <label className="block text-sm font-medium text-zinc-400 mb-1">ID Thể loại</label>
                            <input 
                                type="text" 
                                value={formData.source_ref} 
                                onChange={e => setFormData({...formData, source_ref: e.target.value})}
                                className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#E50914]"
                                placeholder="Nhập ID thể loại (ví dụ: 1)"
                            />
                        </div>
                    )}
                    <div className="flex gap-4">
                        <div className="flex-1">
                            <label className="block text-sm font-medium text-zinc-400 mb-1">Thứ tự</label>
                            <input 
                                type="number" 
                                value={formData.sort_order} 
                                onChange={e => setFormData({...formData, sort_order: Number(e.target.value)})}
                                className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#E50914]"
                            />
                        </div>
                        <div className="flex-1 flex items-center pt-6">
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    checked={formData.is_visible} 
                                    onChange={e => setFormData({...formData, is_visible: e.target.checked})}
                                    className="w-4 h-4 rounded bg-[#101521] border-white/10 text-[#E50914] focus:ring-[#E50914]"
                                />
                                <span className="text-sm font-medium text-white">Hiển thị</span>
                            </label>
                        </div>
                    </div>
                </form>
                <div className="mt-6 flex justify-end gap-3 pt-4 border-t border-white/10">
                    <button type="button" onClick={() => setFormModal({ isOpen: false, block: null })} className="px-4 py-2 rounded-lg text-sm font-medium text-zinc-300 hover:text-white hover:bg-white/5">
                        Hủy
                    </button>
                    <button type="submit" form="block-form" className="bg-[#E50914] hover:bg-[#E50914]/90 px-4 py-2 rounded-lg text-sm font-medium text-white transition-colors">
                        Lưu thay đổi
                    </button>
                </div>
            </AdminModal>
        </div>
    );
}
