import React from 'react';

export default function AdminTable({ columns, data, loading, emptyMessage = "Không có dữ liệu" }) {
    return (
        <div className="w-full overflow-x-auto rounded-lg border border-white/10 bg-[#151B27]">
            <table className="w-full text-left text-sm text-zinc-300">
                <thead className="bg-[#0B0F19] text-xs uppercase text-zinc-500 border-b border-white/10">
                    <tr>
                        {columns.map((col, idx) => (
                            <th key={idx} className={`px-4 py-3 font-semibold ${col.className || ''}`}>
                                {col.header}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-white/10">
                    {loading ? (
                        Array.from({ length: 5 }).map((_, idx) => (
                            <tr key={idx}>
                                <td colSpan={columns.length} className="px-4 py-3">
                                    <div className="animate-pulse h-10 bg-white/5 rounded-md w-full" />
                                </td>
                            </tr>
                        ))
                    ) : data && data.length > 0 ? (
                        data.map((row, rowIndex) => (
                            <tr key={rowIndex} className="hover:bg-white/[0.03] transition-colors">
                                {columns.map((col, colIndex) => (
                                    <td key={colIndex} className={`px-4 py-3 ${col.cellClassName || ''}`}>
                                        {col.render ? col.render(row) : row[col.accessor]}
                                    </td>
                                ))}
                            </tr>
                        ))
                    ) : (
                        <tr>
                            <td colSpan={columns.length} className="px-4 py-8 text-center text-zinc-500">
                                {emptyMessage}
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}
