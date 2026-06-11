export function movieImage(movie, type = 'poster') {
    const value = type === 'backdrop'
        ? (movie?.poster_url || movie?.thumb_url)
        : (movie?.thumb_url || movie?.poster_url);

    if (!value) {
        return '';
    }

    if (/^https?:\/\//i.test(value)) {
        return value;
    }

    return `https://img.ophim.live/uploads/movies/${value.replace(/^\/+/, '')}`;
}

export function stripHtml(value = '') {
    const element = document.createElement('div');
    element.innerHTML = value;
    return element.textContent || element.innerText || '';
}

export function youtubeEmbedUrl(value = '') {
    if (!value) {
        return '';
    }

    try {
        const url = new URL(value);
        const host = url.hostname.replace(/^www\./, '');
        let videoId = '';

        if (host === 'youtu.be') {
            videoId = url.pathname.split('/').filter(Boolean)[0] || '';
        } else if (host.endsWith('youtube.com')) {
            videoId = url.searchParams.get('v') || url.pathname.match(/\/(?:embed|shorts)\/([^/?]+)/)?.[1] || '';
        }

        return videoId ? `https://www.youtube.com/embed/${videoId}?autoplay=1` : value;
    } catch {
        return value;
    }
}

export function formatMoney(value) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}

export function formatDate(value) {
    if (!value) {
        return 'Chưa cập nhật';
    }

    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(value));
}

export function formatTime(seconds = 0) {
    const total = Math.max(0, Number(seconds || 0));
    const hours = Math.floor(total / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const remainingSeconds = Math.floor(total % 60);

    return [hours, minutes, remainingSeconds]
        .filter((_, index) => index > 0 || hours > 0)
        .map((part) => String(part).padStart(2, '0'))
        .join(':');
}

export function paginatePayload(response) {
    return response?.data?.data || [];
}
