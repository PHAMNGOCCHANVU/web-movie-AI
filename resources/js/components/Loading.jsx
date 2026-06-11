import React from 'react';

export default function Loading({ label = 'Đang tải dữ liệu...' }) {
    return (
        <div className="flex min-h-52 flex-col items-center justify-center gap-4 text-zinc-400">
            <div className="loader" />
            <span className="text-sm">{label}</span>
        </div>
    );
}
