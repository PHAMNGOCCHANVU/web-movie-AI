import React from 'react';
import { Film } from 'lucide-react';
import { Link } from 'react-router-dom';

export default function EmptyState({
    title = 'Chưa có dữ liệu',
    message = 'Nội dung sẽ xuất hiện khi backend có dữ liệu phù hợp.',
    action,
}) {
    return (
        <div className="empty-state">
            <Film size={36} />
            <h3>{title}</h3>
            <p>{message}</p>
            {action && <Link className="button button--primary mt-4" to={action.to}>{action.label}</Link>}
        </div>
    );
}
