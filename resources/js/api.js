import axios from 'axios';

const TOKEN_KEY = 'cineon_token';

const client = axios.create({
    baseURL: '/api',
    timeout: 15000,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

client.interceptors.request.use((config) => {
    const token = localStorage.getItem(TOKEN_KEY);

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

client.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem(TOKEN_KEY);
            window.dispatchEvent(new CustomEvent('cineon:unauthorized'));
        }

        return Promise.reject(error);
    },
);

export function setToken(token) {
    if (token) {
        localStorage.setItem(TOKEN_KEY, token);
    } else {
        localStorage.removeItem(TOKEN_KEY);
    }
}

export function hasToken() {
    return Boolean(localStorage.getItem(TOKEN_KEY));
}

export function apiError(error, fallback = 'Có lỗi xảy ra. Vui lòng thử lại.') {
    const response = error.response?.data;

    if (response?.errors) {
        return Object.values(response.errors).flat()[0] || response.message || fallback;
    }

    return response?.message || fallback;
}

export const api = {
    register: (payload) => client.post('/auth/register', payload),
    verifyRegistrationOtp: (email, otp) => client.post('/auth/verify-registration-otp', { email, otp }),
    login: (payload) => client.post('/auth/login', payload),
    logout: () => client.post('/auth/logout'),
    me: () => client.get('/auth/me'),
    forgotPassword: (email) => client.post('/auth/forgot-password', { email }),
    verifyPasswordOtp: (email, otp) => client.post('/auth/verify-password-otp', { email, otp }),
    resetPassword: (payload) => client.post('/auth/reset-password', payload),

    featuredMovies: () => client.get('/movies/featured'),
    latestMovies: (params = {}) => client.get('/movies/new-updated', { params }),
    movies: (params = {}) => client.get('/movies', { params }),
    searchMovies: (params = {}) => client.get('/movies/search', { params }),
    movie: (id) => client.get(`/movies/${id}`),
    movieEpisodes: (id) => client.get(`/movies/${id}/episodes`),
    movieComments: (id, params = {}) => client.get(`/movies/${id}/comments`, { params }),
    movieRatings: (id) => client.get(`/movies/${id}/ratings`),
    movieTrailer: (id) => client.get(`/movies/${id}/trailer`),
    movieStream: (id, episodeId = null) => client.get(`/movies/${id}/stream`, {
        params: episodeId ? { episode_id: episodeId } : {},
    }),
    homepageBlocks: () => client.get('/homepage-blocks'),
    genres: () => client.get('/genres'),
    genreMovies: (id, params = {}) => client.get(`/genres/${id}/movies`, { params }),

    watchlist: () => client.get('/user/watchlist'),
    addWatchlist: (movieId) => client.post('/user/watchlist', { movie_id: movieId }),
    removeWatchlist: (movieId) => client.delete(`/user/watchlist/${movieId}`),
    watchHistory: () => client.get('/user/watch-history'),
    saveProgress: (payload) => client.post('/user/watch-history', payload),
    removeHistory: (movieId) => client.delete(`/user/watch-history/${movieId}`),

    postComment: (movieId, content) => client.post(`/movies/${movieId}/comments`, { content }),
    deleteComment: (commentId) => client.delete(`/comments/${commentId}`),
    rateMovie: (movieId, score) => client.post(`/movies/${movieId}/ratings`, { score }),

    aiChat: (message) => client.post('/ai/chat', { message }, { timeout: 50000 }),
    aiHistory: () => client.get('/ai/chat/history'),
    clearAiHistory: () => client.delete('/ai/chat/history'),

    plans: () => client.get('/subscription/plans'),
    subscription: () => client.get('/user/subscription'),
    cancelSubscription: (cancellationReason = '') => client.post('/subscription/cancel', {
        cancellation_reason: cancellationReason,
    }),
    createPayment: (payload) => client.post('/payment/vnpay/create', payload),
    verifyPaymentReturn: (params) => client.get('/payment/vnpay/return', { params }),
    paymentStatus: (id) => client.get(`/user/payments/${id}`),
    paymentHistory: (params = {}) => client.get('/user/payment-history', { params }),

    profile: () => client.get('/user/profile'),
    updateProfile: (payload) => client.put('/user/profile', payload),
    changePassword: (payload) => client.put('/user/change-password', payload),

    getAdminStats: () => client.get('/admin/dashboard/stats'),
    getAdminRevenue: (params = {}) => client.get('/admin/dashboard/revenue', { params }),
    getAdminSentiment: () => client.get('/admin/dashboard/sentiment'),
    getAdminTopMovies: (params = {}) => client.get('/admin/dashboard/top-movies', { params }),
    getAdminTopViewedMovies: (params = {}) => client.get('/admin/movies/top-views', { params }),

    getAdminMovies: (params = {}) => client.get('/admin/movies', { params }),
    getAdminMovie: (id) => client.get(`/admin/movies/${id}`),
    createAdminMovie: (payload) => client.post('/admin/movies', payload),
    updateAdminMovie: (id, payload) => client.put(`/admin/movies/${id}`, payload),
    deleteAdminMovie: (id) => client.delete(`/admin/movies/${id}`),
    approveMovie: (id) => client.patch(`/admin/movies/${id}/approve`),
    rejectMovie: (id) => client.patch(`/admin/movies/${id}/reject`),
    toggleMoviePremium: (id) => client.patch(`/admin/movies/${id}/premium`),
    toggleMoviePin: (id) => client.patch(`/admin/movies/${id}/pin`),
    getAdminCategories: () => client.get('/admin/categories'),
    updateAdminCategory: (id, payload) => client.put(`/admin/categories/${id}`, payload),

    getAdminHomepageBlocks: () => client.get('/admin/homepage-blocks'),
    getHomepageBlocks: () => client.get('/admin/homepage-blocks'),
    createHomepageBlock: (payload) => client.post('/admin/homepage-blocks', payload),
    updateHomepageBlock: (id, payload) => client.put(`/admin/homepage-blocks/${id}`, payload),
    deleteHomepageBlock: (id) => client.delete(`/admin/homepage-blocks/${id}`),

    getAdminUsers: (params = {}) => client.get('/admin/users', { params }),
    getAdminUser: (id) => client.get(`/admin/users/${id}`),
    updateUserRole: (id, payload) => client.patch(`/admin/users/${id}/role`, payload),
    updateUserStatus: (id, payload) => client.patch(`/admin/users/${id}/status`, payload),
    updateUserSubscription: (id, payload) => client.patch(`/admin/users/${id}/subscription`, payload),

    getAdminComments: (params = {}) => client.get('/admin/comments', { params }),
    approveComment: (id) => client.patch(`/admin/comments/${id}/approve`),
    hideComment: (id) => client.patch(`/admin/comments/${id}/hide`),
    restoreComment: (id) => client.patch(`/admin/comments/${id}/restore`),
    deleteAdminComment: (id) => client.delete(`/admin/comments/${id}`),
    getCommentSentiment: (id) => client.get(`/admin/comments/${id}/sentiment`),

    getAdminTransactions: (params = {}) => client.get('/admin/transactions', { params }),
    getAdminTransaction: (id) => client.get(`/admin/transactions/${id}`),
    syncOphimMovies: (page = 1) => client.post('/admin/sync/ophim/movies', { page }),
    syncOphimGenres: () => client.post('/admin/sync/ophim/genres'),
};
