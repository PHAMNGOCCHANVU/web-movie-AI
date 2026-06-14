import React, { useEffect, useRef, useState } from 'react';
import { Bot, Send, Sparkles, Trash2, UserRound } from 'lucide-react';
import { Link } from 'react-router-dom';
import { api, apiError } from '../api';
import Loading from '../components/Loading';
import { useUi } from '../context/UiContext';
import { movieImage } from '../utils';

const prompts = [
    'Gợi ý phim hành động hấp dẫn',
    'Hôm nay mình hơi buồn',
    'Có phim mới cập nhật nào đáng xem?',
];

function cleanChatText(value = '') {
    return value
        .replace(/\*\*(.*?)\*\*/g, '$1')
        .replace(/^\s*[-*#]+\s*/gm, '')
        .trim();
}

export default function AiChatPage() {
    const { toast } = useUi();
    const bottomRef = useRef(null);
    const sendingRef = useRef(false);
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(true);
    const [sending, setSending] = useState(false);

    useEffect(() => {
        api.aiHistory().then((response) => {
            const history = (response.data.data || []).slice().reverse().flatMap((item) => [
                { role: 'user', content: item.message, id: `u-${item.id}` },
                {
                    role: 'assistant',
                    content: cleanChatText(item.response),
                    movies: item.recommended_movies || [],
                    suggestions: item.suggested_replies || [],
                    sources: item.grounding_sources || [],
                    source: item.response_source,
                    model: item.ai_model,
                    id: `a-${item.id}`,
                },
            ]);
            setMessages(history);
        }).catch(() => {}).finally(() => setLoading(false));
    }, []);

    useEffect(() => bottomRef.current?.scrollIntoView({ behavior: 'smooth' }), [messages, sending]);

    const send = async (message = input) => {
        const value = message.trim();
        if (!value || sendingRef.current) return;

        sendingRef.current = true;
        setMessages((items) => [...items, { role: 'user', content: value, id: crypto.randomUUID() }]);
        setInput('');
        setSending(true);

        try {
            const response = await api.aiChat(value);
            setMessages((items) => [...items, {
                role: 'assistant',
                content: cleanChatText(response.data.data.response),
                movies: response.data.data.movies || [],
                suggestions: response.data.data.suggestions || [],
                sources: response.data.data.sources || [],
                source: response.data.data.source,
                model: response.data.data.model,
                id: crypto.randomUUID(),
            }]);
        } catch (error) {
            const fallback = error.code === 'ECONNABORTED'
                ? 'Gemini đang phản hồi chậm. Bạn vui lòng gửi lại sau ít phút nhé.'
                : apiError(error, 'Không thể kết nối với Gemini lúc này. Bạn vui lòng thử lại.');
            setMessages((items) => [...items, {
                role: 'assistant',
                content: fallback,
                movies: [],
                suggestions: [],
                source: 'fallback',
                id: crypto.randomUUID(),
            }]);
        } finally {
            sendingRef.current = false;
            setSending(false);
        }
    };

    const clear = async () => {
        try {
            await api.clearAiHistory();
            setMessages([]);
            toast('Đã xóa lịch sử Chat AI.');
        } catch (error) {
            toast(apiError(error), 'error');
        }
    };

    return (
        <div className="site-container py-8">
            <div className="mb-3 flex justify-end">
                <button className="button button--ghost button--small" onClick={clear}><Trash2 size={16} /> Xóa lịch sử</button>
            </div>
            <div className="chat-shell">
                <div className="chat-messages">
                    {loading ? <Loading /> : (
                        <>
                            <div className="chat-message chat-message--assistant">
                                <div className="chat-avatar"><Bot size={20} /></div>
                                <div className="chat-bubble">
                                    Chào bạn, hôm nay bạn muốn xem phim theo tâm trạng nào? Mình có thể cùng bạn chọn từ từ.
                                </div>
                            </div>
                            {messages.map((message) => (
                                <div className={`chat-message chat-message--${message.role}`} key={message.id}>
                                    <div className="chat-avatar">{message.role === 'assistant' ? <Bot size={20} /> : <UserRound size={20} />}</div>
                                    <div className="chat-response">
                                        <div className="chat-bubble whitespace-pre-wrap">{message.content}</div>
                                        {message.role === 'assistant' && message.movies?.length > 0 && (
                                            <div className="chat-movie-row">
                                                {message.movies.map((movie) => (
                                                    <Link className="chat-movie-card" key={movie.id} to={`/movies/${movie.id}`}>
                                                        <div className="chat-movie-card__poster">
                                                            {movieImage(movie) ? (
                                                                <img src={movieImage(movie)} alt={movie.name} />
                                                            ) : (
                                                                <span>{movie.name?.charAt(0)}</span>
                                                            )}
                                                            <small className={movie.is_premium ? 'vip' : ''}>
                                                                {movie.is_premium ? 'VIP' : 'STANDARD'}
                                                            </small>
                                                        </div>
                                                        <strong>{movie.name}</strong>
                                                        {movie.recommendation_reason && (
                                                            <span className="chat-movie-card__reason">
                                                                {movie.recommendation_reason}
                                                            </span>
                                                        )}
                                                        <p>{movie.year || 'Đang cập nhật'} · {movie.quality || 'HD'}</p>
                                                    </Link>
                                                ))}
                                            </div>
                                        )}
                                        {message.role === 'assistant' && message.sources?.length > 0 && (
                                            <div className="chat-sources">
                                                <span>Nguồn AI tham khảo:</span>
                                                {message.sources.map((source, index) => (
                                                    <a
                                                        href={source.url}
                                                        key={`${source.url}-${index}`}
                                                        rel="noreferrer"
                                                        target="_blank"
                                                    >
                                                        {source.title || `Nguồn ${index + 1}`}
                                                    </a>
                                                ))}
                                            </div>
                                        )}
                                        {message.role === 'assistant' && message.suggestions?.length > 0 && (
                                            <div className="chat-followups">
                                                {message.suggestions.map((suggestion) => (
                                                    <button key={suggestion} onClick={() => send(suggestion)}>
                                                        {suggestion}
                                                    </button>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))}
                            {sending && (
                                <div className="chat-message chat-message--assistant">
                                    <div className="chat-avatar"><Bot size={20} /></div>
                                    <div className="chat-bubble"><span className="typing"><i /><i /><i /></span></div>
                                </div>
                            )}
                            <div ref={bottomRef} />
                        </>
                    )}
                </div>

                <div className="chat-composer">
                    <div className="mb-4 flex flex-wrap gap-2">
                        {prompts.map((prompt) => (
                            <button className="prompt-chip" key={prompt} onClick={() => send(prompt)}>
                                <Sparkles size={14} /> {prompt}
                            </button>
                        ))}
                    </div>
                    <form className="flex gap-3" onSubmit={(event) => { event.preventDefault(); send(); }}>
                        <textarea
                            className="input min-h-14 flex-1 resize-none"
                            maxLength="2000"
                            placeholder="Nhập nội dung cần hỏi..."
                            rows="2"
                            value={input}
                            onChange={(event) => setInput(event.target.value)}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter' && !event.shiftKey) {
                                    event.preventDefault();
                                    send();
                                }
                            }}
                        />
                        <button className="button button--primary self-end" disabled={sending || !input.trim()}><Send size={18} /> Gửi</button>
                    </form>
                </div>
            </div>
        </div>
    );
}
