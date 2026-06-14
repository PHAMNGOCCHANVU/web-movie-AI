import React, { useEffect, useState } from 'react';
import { api } from '../../api';
import AdminTable from '../../components/admin/AdminTable';
import AdminBadge from '../../components/admin/AdminBadge';
import AdminSearchBar from '../../components/admin/AdminSearchBar';
import AdminPagination from '../../components/admin/AdminPagination';
import AdminModal from '../../components/admin/AdminModal';
import { useAdminToast } from './AdminLayout';
import { Link } from 'react-router-dom';
import { Eye, Lock, Unlock } from 'lucide-react';

export default function UserManagementPage() {
    const { showToast } = useAdminToast();
    const [users, setUsers] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [roleFilter, setRoleFilter] = useState('');
    const [page, setPage] = useState(1);

    const [confirmModal, setConfirmModal] = useState({ isOpen: false, user: null, newStatus: '' });

    const fetchUsers = async () => {
        setLoading(true);
        try {
            const res = await api.getAdminUsers({ page, search, role: roleFilter, limit: 10 });
            setUsers(res.data?.data?.data || []);
            setMeta(res.data?.data?.meta || res.data?.data || null);
        } catch (err) {
            showToast('error', 'Lỗi tải danh sách người dùng');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        const timer = setTimeout(() => { fetchUsers(); }, 300);
        return () => clearTimeout(timer);
    }, [page, search, roleFilter]);

    const handleToggleStatus = async () => {
        try {
            await api.updateUserStatus(confirmModal.user.id, { status: confirmModal.newStatus });
            showToast('success', `Đã ${confirmModal.newStatus === 'locked' ? 'khóa' : 'mở khóa'} người dùng`);
            fetchUsers();
        } catch (err) {
            showToast('error', err.response?.data?.message || 'Có lỗi xảy ra');
        } finally {
            setConfirmModal({ isOpen: false, user: null, newStatus: '' });
        }
    };

    const columns = [
        {
            header: 'Người dùng',
            accessor: 'name',
            render: (row) => (
                <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-full bg-zinc-800 flex items-center justify-center text-sm font-bold text-white">
                        {row.name?.charAt(0).toUpperCase() || 'U'}
                    </div>
                    <div>
                        <div className="font-medium text-white">{row.name}</div>
                        <div className="text-xs text-zinc-500">{row.email}</div>
                    </div>
                </div>
            )
        },
        { header: 'Vai trò', accessor: 'role', render: (row) => <AdminBadge status={row.role?.name === 'admin' ? 'premium' : 'free'} text={row.role?.name || 'user'} /> },
        { header: 'Trạng thái', accessor: 'status', render: (row) => <AdminBadge status={row.status || 'active'} /> },
        { header: 'Ngày tham gia', accessor: 'created_at', render: (row) => new Date(row.created_at).toLocaleDateString('vi-VN') },
        {
            header: 'Thao tác',
            accessor: 'actions',
            render: (row) => {
                const isLocked = row.status === 'locked';
                return (
                    <div className="flex items-center gap-2">
                        <Link to={`/admin/users/${row.id}`} className="p-1.5 text-zinc-400 hover:text-white hover:bg-white/5 rounded" title="Xem chi tiết">
                            <Eye size={16} />
                        </Link>
                        {row.role?.name !== 'admin' && (
                            <button
                                onClick={() => setConfirmModal({ isOpen: true, user: row, newStatus: isLocked ? 'active' : 'locked' })}
                                className={`p-1.5 rounded ${isLocked ? 'text-green-500 hover:bg-green-500/10' : 'text-red-500 hover:bg-red-500/10'}`}
                                title={isLocked ? 'Mở khóa' : 'Khóa'}
                            >
                                {isLocked ? <Unlock size={16} /> : <Lock size={16} />}
                            </button>
                        )}
                    </div>
                );
            }
        }
    ];

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold text-white">Quản lý Người dùng</h1>

            <div className="flex flex-col sm:flex-row gap-4 items-center">
                <div className="w-full sm:w-1/3">
                    <AdminSearchBar value={search} onChange={setSearch} placeholder="Tìm kiếm tên, email..." />
                </div>
                <div className="w-full sm:w-1/4">
                    <select
                        value={roleFilter}
                        onChange={(e) => setRoleFilter(e.target.value)}
                        className="w-full bg-[#101521] border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#E50914]"
                    >
                        <option value="">Tất cả vai trò</option>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <AdminTable columns={columns} data={users} loading={loading} />
            <AdminPagination meta={meta} onPageChange={setPage} />

            <AdminModal
                isOpen={confirmModal.isOpen}
                onClose={() => setConfirmModal({ isOpen: false, user: null, newStatus: '' })}
                title={confirmModal.newStatus === 'locked' ? 'Khóa người dùng' : 'Mở khóa người dùng'}
                type={confirmModal.newStatus === 'locked' ? 'danger' : 'default'}
                footer={
                    <>
                        <button
                            onClick={() => setConfirmModal({ isOpen: false, user: null, newStatus: '' })}
                            className="px-4 py-2 rounded-lg text-sm font-medium text-zinc-300 hover:text-white hover:bg-white/5"
                        >
                            Hủy
                        </button>
                        <button
                            onClick={handleToggleStatus}
                            className={`px-4 py-2 rounded-lg text-sm font-medium text-white ${
                                confirmModal.newStatus === 'locked' ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'
                            }`}
                        >
                            Xác nhận
                        </button>
                    </>
                }
            >
                <p className="text-zinc-300">
                    Bạn có chắc chắn muốn {confirmModal.newStatus === 'locked' ? 'khóa' : 'mở khóa'} người dùng <strong className="text-white">"{confirmModal.user?.name}"</strong>?
                </p>
            </AdminModal>
        </div>
    );
}
