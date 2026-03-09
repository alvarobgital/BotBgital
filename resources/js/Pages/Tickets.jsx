import React, { useState, useEffect } from 'react';
import api from '../api';
import { Ticket, Search, CheckCircle, Clock, AlertTriangle, Plus, Share2, X } from 'lucide-react';

function TicketModal({ onClose, onSave, contacts }) {
    const [form, setForm] = useState({
        contact_id: '',
        subject: '',
        description: '',
        priority: 'medium',
        status: 'open',
    });
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true); setError('');
        try {
            await api.post('/tickets', form);
            onSave();
        } catch (err) {
            setError(err.response?.data?.message || 'Error al crear ticket');
        } finally { setSaving(false); }
    }

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal" onClick={e => e.stopPropagation()}>
                <div className="modal-header">
                    <h2>Nuevo Ticket</h2>
                    <button className="btn-icon" onClick={onClose}><X size={18} /></button>
                </div>
                <form onSubmit={handleSubmit}>
                    <div className="modal-body">
                        {error && <div style={{ background: '#FEF2F2', color: '#DC2626', padding: '10px', borderRadius: 8, marginBottom: 16 }}>{error}</div>}

                        <div className="form-group">
                            <label className="form-label">Cliente (Contacto de WhatsApp)</label>
                            <select className="form-input" value={form.contact_id} onChange={e => setForm({ ...form, contact_id: e.target.value })} required>
                                <option value="">-- Seleccionar cliente --</option>
                                {contacts.map(c => (
                                    <option key={c.id} value={c.id}>{c.name || c.phone}</option>
                                ))}
                            </select>
                        </div>

                        <div className="form-group">
                            <label className="form-label">Asunto (Problema Principal)</label>
                            <input className="form-input" value={form.subject} onChange={e => setForm({ ...form, subject: e.target.value })} required placeholder="Ej: Falla masiva zona norte" />
                        </div>

                        <div className="form-group">
                            <label className="form-label">Descripción Detallada</label>
                            <textarea className="form-input" rows="4" value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} required placeholder="Especifique dirección, contexto y pruebas realizadas..." />
                        </div>

                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                            <div className="form-group">
                                <label className="form-label">Prioridad</label>
                                <select className="form-input" value={form.priority} onChange={e => setForm({ ...form, priority: e.target.value })}>
                                    <option value="low">Baja</option>
                                    <option value="medium">Media</option>
                                    <option value="high">Alta</option>
                                    <option value="urgent">Urgente 🚨</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div className="modal-footer">
                        <button type="button" className="btn btn-ghost" onClick={onClose}>Cancelar</button>
                        <button type="submit" className="btn btn-primary" disabled={saving}>
                            {saving ? 'Guardando...' : 'Crear Ticket'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

export default function Tickets() {
    const [tickets, setTickets] = useState([]);
    const [contacts, setContacts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [filter, setFilter] = useState('all');
    const [showModal, setShowModal] = useState(false);

    useEffect(() => { loadData(); }, []);

    async function loadData() {
        try {
            const [tRes, cRes] = await Promise.all([
                api.get('/tickets'),
                api.get('/contacts?limit=100') // Assume we fetch recent contacts to assign
            ]);
            setTickets(tRes.data);
            setContacts(cRes.data.data || cRes.data || []);
        } catch { } finally { setLoading(false); }
    }

    async function updateStatus(id, newStatus) {
        try {
            const res = await api.put(`/tickets/${id}`, { status: newStatus });
            setTickets(tickets.map(t => t.id === id ? { ...t, ...res.data } : t));
        } catch { }
    }

    const filteredTickets = tickets.filter(t => {
        if (filter !== 'all' && t.status !== filter) return false;
        if (search && !t.subject?.toLowerCase().includes(search.toLowerCase()) && !t.contact?.name?.toLowerCase().includes(search.toLowerCase())) return false;
        return true;
    });

    const statusMap = {
        open: { label: 'Abierto', color: 'var(--color-danger)', bg: '#FEF2F2', icon: AlertTriangle },
        in_progress: { label: 'En Progreso', color: 'var(--color-warning)', bg: '#FFFBEB', icon: Clock },
        resolved: { label: 'Resuelto', color: 'var(--color-success)', bg: '#ECFDF5', icon: CheckCircle },
        closed: { label: 'Cerrado', color: 'var(--text-muted)', bg: '#F1F5F9', icon: CheckCircle },
    };

    function shareTicket(ticket) {
        const url = `https://wa.me/?text=${encodeURIComponent(
            `🛠️ *TICKET SOPORTE #${ticket.id}*\n` +
            `⚠️ *Asunto:* ${ticket.subject}\n` +
            `🚨 *Prioridad:* ${ticket.priority?.toUpperCase()}\n` +
            `👤 *Cliente:* ${ticket.contact?.name || ticket.contact?.phone}\n` +
            `📱 *WhatsApp:* ${ticket.contact?.phone || 'Desconocido'}\n` +
            `📍 *Dirección/Contexto:* \n${ticket.description}`
        )}`;
        window.open(url, '_blank');
    }

    return (
        <div className="fade-in">
            <div className="page-header" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <div>
                    <h1>Tickets de Soporte</h1>
                    <p>Reportes técnicos generados por los usuarios del bot o asesores</p>
                </div>
                <button className="btn btn-primary" onClick={() => setShowModal(true)}>
                    <Plus size={16} /> Crear Ticket
                </button>
            </div>

            <div className="page-body">
                <div className="card" style={{ padding: '16px 20px', marginBottom: 20 }}>
                    <div style={{ display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
                        <div style={{ flex: 1, minWidth: 250, position: 'relative' }}>
                            <Search size={16} style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                            <input
                                className="form-input"
                                style={{ paddingLeft: 36 }}
                                placeholder="Buscar tickets..."
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                            />
                        </div>
                        <select className="form-input" style={{ width: 180 }} value={filter} onChange={e => setFilter(e.target.value)}>
                            <option value="all">Todos los estados</option>
                            <option value="open">Abiertos</option>
                            <option value="in_progress">En Progreso</option>
                            <option value="resolved">Resueltos</option>
                            <option value="closed">Cerrados</option>
                        </select>
                    </div>
                </div>

                {loading ? (
                    <div className="loading-spinner"><div className="spinner"></div></div>
                ) : filteredTickets.length === 0 ? (
                    <div className="empty-state">
                        <Ticket size={48} />
                        <p>No se encontraron tickets</p>
                    </div>
                ) : (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                        {filteredTickets.map(ticket => {
                            const st = statusMap[ticket.status] || statusMap.closed;
                            const StatusIcon = st.icon;
                            return (
                                <div key={ticket.id} className="card" style={{ padding: 20, borderLeft: `3px solid ${st.color}` }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: 16, flexWrap: 'wrap' }}>
                                        <div style={{ flex: 1 }}>
                                            <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 8, flexWrap: 'wrap' }}>
                                                <h3 style={{ fontSize: '0.95rem', fontWeight: 600, margin: 0 }}>#{ticket.id} {ticket.subject}</h3>
                                                <span className="badge" style={{ background: st.bg, color: st.color }}>
                                                    <StatusIcon size={12} />
                                                    {st.label}
                                                </span>
                                                <span className="badge" style={{ background: '#F1F5F9', color: '#475569' }}>
                                                    {ticket.priority?.toUpperCase()}
                                                </span>
                                            </div>
                                            <p style={{ color: 'var(--text-secondary)', fontSize: '0.85rem', marginBottom: 4 }}>
                                                <strong>Cliente:</strong> {ticket.contact?.name || ticket.contact?.phone || 'Desconocido'}
                                            </p>
                                            <p style={{ color: 'var(--text-secondary)', fontSize: '0.85rem', marginBottom: 12 }}>
                                                <strong>Fecha:</strong> {new Date(ticket.created_at).toLocaleString('es-MX')}
                                            </p>
                                            <div style={{ background: 'var(--bg-app)', padding: 14, borderRadius: 8, fontSize: '0.85rem', whiteSpace: 'pre-wrap', lineHeight: 1.5 }}>
                                                {ticket.description}
                                            </div>
                                        </div>

                                        <div style={{ display: 'flex', flexDirection: 'column', gap: 6, minWidth: 140 }}>
                                            {ticket.status === 'open' && (
                                                <button className="btn btn-secondary btn-sm btn-block" onClick={() => updateStatus(ticket.id, 'in_progress')}>
                                                    En Progreso
                                                </button>
                                            )}
                                            {(ticket.status === 'open' || ticket.status === 'in_progress') && (
                                                <button className="btn btn-success btn-sm btn-block" onClick={() => updateStatus(ticket.id, 'resolved')}>
                                                    Resuelto
                                                </button>
                                            )}
                                            {ticket.status !== 'closed' && (
                                                <button className="btn btn-ghost btn-sm btn-block" style={{ color: 'var(--color-danger)' }} onClick={() => updateStatus(ticket.id, 'closed')}>
                                                    Cerrar
                                                </button>
                                            )}
                                            <button className="btn btn-ghost btn-sm btn-block" style={{ color: 'var(--color-primary)' }} onClick={() => shareTicket(ticket)}>
                                                <Share2 size={14} style={{ marginRight: 6 }} /> Compartir (WA)
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

            {showModal && (
                <TicketModal onClose={() => setShowModal(false)} onSave={() => { setShowModal(false); loadData(); }} contacts={contacts} />
            )}
        </div>
    );
}
