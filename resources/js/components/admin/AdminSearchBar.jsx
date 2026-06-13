import React from 'react';
import { Search } from 'lucide-react';

export default function AdminSearchBar({ value, onChange, placeholder = "Tìm kiếm...", className = "" }) {
    return (
        <div className={`relative ${className}`}>
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Search className="h-4 w-4 text-zinc-500" />
            </div>
            <input
                type="text"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                className="block w-full pl-10 pr-3 py-2 bg-[#101521] border border-white/10 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:border-[#E50914] focus:ring-1 focus:ring-[#E50914] transition-colors sm:text-sm"
            />
        </div>
    );
}
