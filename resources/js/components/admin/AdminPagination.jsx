import React from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export default function AdminPagination({ meta, onPageChange }) {
    if (!meta || !meta.last_page || meta.last_page <= 1) return null;

    const currentPage = meta.current_page;
    const lastPage = meta.last_page;

    const getPages = () => {
        let pages = [];
        const maxVisible = 5;
        
        if (lastPage <= maxVisible) {
            for (let i = 1; i <= lastPage; i++) pages.push(i);
        } else {
            if (currentPage <= 3) {
                pages = [1, 2, 3, 4, '...', lastPage];
            } else if (currentPage >= lastPage - 2) {
                pages = [1, '...', lastPage - 3, lastPage - 2, lastPage - 1, lastPage];
            } else {
                pages = [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', lastPage];
            }
        }
        return pages;
    };

    return (
        <div className="flex items-center justify-between mt-4">
            <div className="text-sm text-zinc-400">
                Hiển thị từ <span className="text-white font-medium">{meta.from || 0}</span> đến <span className="text-white font-medium">{meta.to || 0}</span> trong số <span className="text-white font-medium">{meta.total || 0}</span> kết quả
            </div>
            <div className="flex items-center gap-1">
                <button
                    onClick={() => onPageChange(currentPage - 1)}
                    disabled={currentPage === 1}
                    className="p-2 rounded-md bg-[#151B27] border border-white/10 text-zinc-400 hover:text-white hover:bg-white/5 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    <ChevronLeft className="w-4 h-4" />
                </button>

                {getPages().map((page, idx) => (
                    <button
                        key={idx}
                        onClick={() => typeof page === 'number' && onPageChange(page)}
                        disabled={page === '...'}
                        className={`min-w-[32px] h-8 px-2 rounded-md border flex items-center justify-center text-sm transition-colors ${
                            page === currentPage
                                ? 'bg-[rgba(229,9,20,0.1)] border-[#E50914] text-[#E50914] font-medium'
                                : page === '...'
                                ? 'border-transparent text-zinc-500 cursor-default'
                                : 'bg-[#151B27] border-white/10 text-zinc-400 hover:text-white hover:bg-white/5'
                        }`}
                    >
                        {page}
                    </button>
                ))}

                <button
                    onClick={() => onPageChange(currentPage + 1)}
                    disabled={currentPage === lastPage}
                    className="p-2 rounded-md bg-[#151B27] border border-white/10 text-zinc-400 hover:text-white hover:bg-white/5 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    <ChevronRight className="w-4 h-4" />
                </button>
            </div>
        </div>
    );
}
