import React from 'react';

export default function AdminBadge({ status, text, variant }) {
    const baseClasses = "inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border";
    
    let colorClasses = "";
    
    // Auto map from status string if variant isn't explicitly provided
    const v = variant || status;

    switch (v?.toLowerCase()) {
        case 'approved':
        case 'active':
        case 'success':
            colorClasses = "bg-[#10B981]/10 text-[#10B981] border-[#10B981]/20";
            break;
        case 'pending':
        case 'pending_review':
            colorClasses = "bg-[#FACC15]/10 text-[#FACC15] border-[#FACC15]/20";
            break;
        case 'hidden':
        case 'locked':
        case 'rejected':
        case 'failed':
            colorClasses = "bg-[#EF4444]/10 text-[#EF4444] border-[#EF4444]/20";
            break;
        case 'free':
            colorClasses = "bg-zinc-500/10 text-zinc-400 border-zinc-500/20";
            break;
        case 'premium':
        case 'vip':
            colorClasses = "bg-gradient-to-r from-amber-500/20 to-yellow-500/20 text-amber-500 border-amber-500/30";
            break;
        default:
            colorClasses = "bg-zinc-500/10 text-zinc-400 border-zinc-500/20";
    }

    return (
        <span className={`${baseClasses} ${colorClasses}`}>
            {text || status}
        </span>
    );
}
