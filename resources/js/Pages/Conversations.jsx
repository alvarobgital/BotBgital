import React, { useState, useEffect, useRef } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../api';
import {
    Search, MessageCircle, Send, UserCheck, Bot, XCircle,
    Phone, Clock, ArrowLeft
} from 'lucide-react';

function statusBadge(status) {
    const map = {
        bot_active: { cls: 'badge-bot', text: 'BOT ACTIVO' },
        waiting_agent: { cls: 'badge-waiting', text: 'ESPERANDO ASESOR' },
        agent_active: { cls: 'badge-agent', text: 'CON AGENTE' },
        closed: { cls: 'badge-closed', text: 'CERRADA' },
    };
    const s = map[status] || map.closed;
    return <span className={`badge ${s.cls}`}>{s.text}</span>;
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const diffMs = now - d;
    const diffH = diffMs / (1000 * 60 * 60);

    if (diffH < 24) {
        return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
    }
    if (diffH < 48) return 'Ayer';
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit' });
}

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}

function ContactTypeBadge({ type }) {
    if (type === 'lead') return <span className="badge" style={{ background: '#fef08a', color: '#854d0e' }}>Lead</span>;
    if (type === 'customer') return <span className="badge" style={{ background: '#bbf7d0', color: '#166534' }}>Cliente</span>;
    return null;
}

export default function Conversations() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [conversations, setConversations] = useState([]);
    const [activeConv, setActiveConv] = useState(null);
    const [messages, setMessages] = useState([]);
    const [search, setSearch] = useState('');
    const [filter, setFilter] = useState('all');
    const [newMessage, setNewMessage] = useState('');
    const [sending, setSending] = useState(false);
    const [loading, setLoading] = useState(true);
    const messagesEndRef = useRef(null);
    const pollRef = useRef(null);

    useEffect(() => {
        loadConversations();
        const interval = setInterval(loadConversations, 10000);
        return () => clearInterval(interval);
    }, [filter, search]);

    useEffect(() => {
        if (id) loadConversation(id);
    }, [id]);

    useEffect(() => {
        if (activeConv) {
            loadMessages(activeConv.id);
            if (pollRef.current) clearInterval(pollRef.current);
            pollRef.current = setInterval(() => loadMessages(activeConv.id), 5000);
        }
        return () => { if (pollRef.current) clearInterval(pollRef.current); };
    }, [activeConv?.id]);

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    function scrollToBottom() {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }

    async function loadConversations() {
        try {
            const params = {};
            if (filter !== 'all') params.status = filter;
            if (search) params.search = search;
            const res = await api.get('/conversations', { params });
            setConversations(res.data.data || []);
        } catch { } finally { setLoading(false); }
    }

    async function loadConversation(convId) {
        try {
            const res = await api.get(`/conversations/${convId}`);
            setActiveConv(res.data);
            setMessages(res.data.messages || []);
        } catch { }
    }

    async function loadMessages(convId) {
        try {
            const res = await api.get(`/conversations/${convId}`);
            setMessages(res.data.messages || []);
        } catch { }
    }

    async function sendMessage(e) {
        e.preventDefault();
        if (!newMessage.trim() || !activeConv || sending) return;
        setSending(true);
        try {
            const res = await api.post(`/conversations/${activeConv.id}/messages`, {
                content: newMessage,
            });
            setMessages(prev => [...prev, res.data]);
            setNewMessage('');
        } catch { } finally { setSending(false); }
    }

    async function takeOver() {
        if (!activeConv) return;
        try {
            const res = await api.post(`/conversations/${activeConv.id}/take-over`);
            setActiveConv(res.data);
            loadConversations();
        } catch { }
    }

    async function reactivateBot() {
        if (!activeConv) return;
        try {
            const res = await api.post(`/conversations/${activeConv.id}/reactivate-bot`);
            setActiveConv(res.data);
            loadConversations();
        } catch { }
    }

    async function closeConversation() {
        if (!activeConv) return;
        try {
            const res = await api.post(`/conversations/${activeConv.id}/close`);
            setActiveConv(res.data);
            loadConversations();
        } catch { }
    }

    function selectConversation(conv) {
        setActiveConv(conv);
        navigate(`/panel/conversations/${conv.id}`, { replace: true });
    }

    const filters = [
        { value: 'all', label: 'Todas' },
        { value: 'bot_active', label: 'Bot Activo' },
        { value: 'waiting_agent', label: 'Esperando' },
        { value: 'agent_active', label: 'Con Agente' },
        { value: 'closed', label: 'Cerradas' },
    ];

    function handleKeyDown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage(e);
        }
    }

    return (
        <div className={`conversations-layout ${activeConv ? 'mobile-chat-active' : ''}`}>
            {/* Left panel — conversation list */}
            <div className="conversation-list-panel">
                <div className="conversation-list-header">
                    <div className="conversation-search">
                        <Search />
                        <input
                            placeholder="Buscar conversación..."
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                        />
                    </div>
                </div>

                <div className="conversation-filters">
                    {filters.map(f => (
                        <button
                            key={f.value}
                            className={`filter-chip ${filter === f.value ? 'active' : ''}`}
                            onClick={() => setFilter(f.value)}
                        >
                            {f.label}
                        </button>
                    ))}
                </div>

                <div className="conversation-list">
                    {loading ? (
                        <div className="loading-spinner"><div className="spinner"></div></div>
                    ) : conversations.length === 0 ? (
                        <div className="empty-state">
                            <MessageCircle />
                            <p>Sin conversaciones</p>
                        </div>
                    ) : (
                        conversations.map(conv => (
                            <div
                                key={conv.id}
                                className={`conversation-item ${activeConv?.id === conv.id ? 'active' : ''}`}
                                onClick={() => selectConversation(conv)}
                            >
                                <div className="conversation-avatar">
                                    {getInitials(conv.contact?.name || conv.contact?.phone)}
                                </div>
                                <div className="conversation-info">
                                    <div className="conversation-name">
                                        <div style={{ display: 'flex', gap: 6, alignItems: 'center' }}>
                                            <span>{conv.contact?.name || conv.contact?.phone || 'Desconocido'}</span>
                                            <ContactTypeBadge type={conv.contact?.type} />
                                        </div>
                                        <span className="conversation-time">
                                            {formatTime(conv.latest_message?.created_at || conv.updated_at)}
                                        </span>
                                    </div>
                                    <div className="conversation-preview">
                                        <span className="conversation-preview-text">
                                            {conv.latest_message?.content || 'Sin mensajes'}
                                        </span>
                                        {statusBadge(conv.status)}
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>

            {/* Right panel — chat */}
            {activeConv ? (
                <div className="chat-panel">
                    {/* Chat Header / Toolbar */}
                    <div className="chat-header">
                        <div className="chat-header-info">
                            <button
                                className="btn-icon mobile-only"
                                style={{ marginRight: 8, color: 'var(--color-primary)' }}
                                onClick={() => {
                                    setActiveConv(null);
                                    navigate('/panel/conversations');
                                }}
                            >
                                <ArrowLeft size={20} />
                            </button>
                            <div className="conversation-avatar" style={{ width: 38, height: 38, fontSize: '0.85rem' }}>
                                {getInitials(activeConv.contact?.name || activeConv.contact?.phone)}
                            </div>
                            <div>
                                <h3 style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                                    {activeConv.contact?.name || 'Desconocido'}
                                    <ContactTypeBadge type={activeConv.contact?.type} />
                                </h3>
                                <p>
                                    <Phone size={12} style={{ display: 'inline', verticalAlign: 'middle', marginRight: 4 }} />
                                    {activeConv.contact?.phone}
                                    {' · '}
                                    {statusBadge(activeConv.status)}
                                </p>
                            </div>
                        </div>
                        <div className="chat-header-actions" style={{ display: 'flex', alignItems: 'center', gap: '15px' }}>
                            {/* Explicit Bot Toggle */}
                            {(activeConv.status === 'waiting_agent' || activeConv.status === 'bot_active' || activeConv.status === 'agent_active') && (
                                <div className="bot-toggle" style={{ padding: '6px 10px', background: 'transparent', border: '1px solid rgba(0,0,0,0.1)', color: 'var(--color-text)' }}>
                                    <span style={{ marginRight: 8, fontSize: '0.75rem' }}>Bot</span>
                                    <label className="toggle-switch" style={{ transform: 'scale(0.85)' }}>
                                        <input
                                            type="checkbox"
                                            checked={activeConv.status === 'bot_active'}
                                            onChange={(e) => {
                                                if (e.target.checked) reactivateBot();
                                                else takeOver();
                                            }}
                                        />
                                        <span className="toggle-slider" style={{ background: activeConv.status === 'bot_active' ? 'var(--color-success)' : '#ccc' }}></span>
                                    </label>
                                </div>
                            )}

                            {activeConv.status !== 'closed' && (
                                <button className="btn btn-ghost btn-sm" onClick={closeConversation} style={{ color: 'var(--color-danger)' }}>
                                    <XCircle size={14} />
                                    Cerrar
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Messages */}
                    <div className="chat-messages">
                        {messages.map(msg => (
                            <div
                                key={msg.id}
                                className={`message-bubble ${msg.direction} ${msg.sender_type}`}
                            >
                                {msg.direction === 'outbound' && (
                                    <div className="message-sender-tag">
                                        {msg.sender_type === 'bot' ? '🤖 Bot' : '👤 Agente'}
                                    </div>
                                )}
                                <div style={{ whiteSpace: 'pre-wrap' }}>{msg.content}</div>
                                <div className="message-meta">
                                    <Clock size={10} />
                                    {formatTime(msg.created_at)}
                                </div>
                            </div>
                        ))}
                        <div ref={messagesEndRef} />
                    </div>

                    {/* Input area — only if agent_active or waiting_agent */}
                    {(activeConv.status === 'agent_active' || activeConv.status === 'waiting_agent') && (
                        <form className="chat-input-area" onSubmit={sendMessage}>
                            <textarea
                                placeholder="Escribe un mensaje..."
                                value={newMessage}
                                onChange={e => setNewMessage(e.target.value)}
                                onKeyDown={handleKeyDown}
                                rows={1}
                            />
                            <button
                                type="submit"
                                className="chat-send-btn"
                                disabled={!newMessage.trim() || sending}
                            >
                                <Send size={18} />
                            </button>
                        </form>
                    )}
                </div>
            ) : (
                <div className="chat-panel chat-panel-empty">
                    <MessageCircle />
                    <p>Selecciona una conversación</p>
                </div>
            )}
        </div>
    );
}
