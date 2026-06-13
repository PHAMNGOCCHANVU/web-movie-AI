import React from 'react';
import { X, AlertTriangle } from 'lucide-react';

export default function AdminModal({ 
    isOpen, 
    onClose, 
    title, 
    children, 
    footer,
    maxWidth = 'max-w-md',
    type = 'default' // default, confirm, danger
}) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
            {/* Overlay */}
            <div 
                className="absolute inset-0 bg-black/70 backdrop-blur-sm"
                onClick={onClose}
            />
            
            {/* Modal Content */}
            <div className={`relative bg-[#151B27] border border-white/10 rounded-xl w-full mx-4 shadow-2xl ${maxWidth} animate-in fade-in zoom-in-95 duration-200`}>
                {/* Header */}
                <div className="flex items-center justify-between px-6 py-4 border-b border-white/10">
                    <div className="flex items-center gap-2">
                        {type === 'danger' && <AlertTriangle className="text-red-500 w-5 h-5" />}
                        <h3 className="text-lg font-semibold text-white">{title}</h3>
                    </div>
                    <button 
                        onClick={onClose}
                        className="text-zinc-400 hover:text-white transition-colors p-1"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Body */}
                <div className="px-6 py-4">
                    {children}
                </div>

                {/* Footer */}
                {footer && (
                    <div className="px-6 py-4 border-t border-white/10 flex justify-end gap-3 bg-white/[0.02] rounded-b-xl">
                        {footer}
                    </div>
                )}
            </div>
        </div>
    );
}
