import React, { useState, useEffect } from 'react';
import api from '../api';
import { Ticket, Search, CheckCircle, Clock, AlertTriangle } from 'lucide-react';

export default function Tickets() {
    const [tickets, setTickets] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [filter, setFilter] = useState('all');

    useEffect(() => {
        loadTickets();
    }, []);

    async function loadTickets() {
        try {
            const res = await api.get('/tickets');
            setTickets(res.data);
        } catch {
        } finally {
            setLoading(false);
        }
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
        open: { label: 'Abierto', color: 'var(--color-danger)', icon: AlertTriangle },
        in_progress: { label: 'En Progreso', color: 'var(--color-warning)', icon: Clock },
        resolved: { label: 'Resuelto', color: 'var(--color-success)', icon: CheckCircle },
        closed: { label: 'Cerrado', color: 'rgba(26,21,48,0.5)', icon: CheckCircle },
    };

    return (
        <div className="flex flex-col h-full bg-[var(--bg-primary)]">
            <div className="page-header">
                <h1>Reportes Técnicos</h1>
                <p>Gestiona los tickets generados por los usuarios desde el bot.</p>
            </div>

            <div className="page-body">
                <div style={{ display: 'flex', gap: '12px', marginBottom: '24px', flexWrap: 'wrap' }}>
                    <div style={{
                        display: 'flex', alignItems: 'center', background: 'white',
                        padding: '8px 12px', borderRadius: 'var(--radius-md)',
                        border: '1px solid rgba(0,0,0,0.1)', flex: 1, minWidth: '250px'
                    }}>
                        <Search size={18} style={{ opacity: 0.5, marginRight: '8px' }} />
                        <input
                            type="text"
                            placeholder="Buscar tickets..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            style={{ border: 'none', background: 'transparent', outline: 'none', width: '100%' }}
                        />
                    </div>

                    <select
                        value={filter}
                        onChange={(e) => setFilter(e.target.value)}
                        style={{ padding: '8px 12px', borderRadius: 'var(--radius-md)', border: '1px solid rgba(0,0,0,0.1)' }}
                    >
                        <option value="all">Todos los estados</option>
                        <option value="open">Abiertos</option>
                        <option value="in_progress">En Progreso</option>
                        <option value="resolved">Resueltos</option>
                        <option value="closed">Cerrados</option>
                    </select>
                </div>

                {loading ? (
                    <div style={{ textAlign: 'center', padding: '40px' }}><div className="spinner" style={{ margin: '0 auto' }}></div></div>
                ) : filteredTickets.length === 0 ? (
                    <div className="empty-state">
                        <Ticket size={48} />
                        <p>No se encontraron tickets.</p>
                    </div>
                ) : (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                        {filteredTickets.map(ticket => {
                            const StatusIcon = statusMap[ticket.status].icon;
                            return (
                                <div key={ticket.id} style={{
                                    background: 'white', borderRadius: 'var(--radius-md)',
                                    padding: '20px', boxShadow: 'var(--shadow-sm)',
                                    borderLeft: `4px solid ${statusMap[ticket.status].color}`
                                }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: '12px' }}>
                                        <div>
                                            <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '8px' }}>
                                                <h3 style={{ fontSize: '1.1rem', margin: 0 }}>#{ticket.id} {ticket.subject}</h3>
                                                <span className="badge" style={{
                                                    background: statusMap[ticket.status].color + '22',
                                                    color: statusMap[ticket.status].color
                                                }}>
                                                    <StatusIcon size={12} style={{ marginRight: '4px' }} />
                                                    {statusMap[ticket.status].label}
                                                </span>
                                                <span className="badge" style={{ background: '#eee', color: '#666' }}>
                                                    {ticket.priority.toUpperCase()}
                                                </span>
                                            </div>
                                            <p style={{ color: 'rgba(0,0,0,0.6)', fontSize: '0.9rem', marginBottom: '4px' }}>
                                                <strong>Cliente:</strong> {ticket.contact?.name || ticket.contact?.phone || 'Desconocido'}
                                            </p>
                                            <p style={{ color: 'rgba(0,0,0,0.6)', fontSize: '0.9rem', marginBottom: '16px' }}>
                                                <strong>Fecha:</strong> {new Date(ticket.created_at).toLocaleString('es-MX')}
                                            </p>
                                            <div style={{ background: 'var(--bg-primary)', padding: '12px', borderRadius: '8px', fontSize: '0.9rem', whiteSpace: 'pre-wrap' }}>
                                                {ticket.description}
                                            </div>
                                        </div>

                                        <div style={{ display: 'flex', gap: '8px', minWidth: '140px', flexDirection: 'column' }}>
                                            {ticket.status === 'open' && (
                                                <button className="btn btn-secondary btn-sm btn-block" onClick={() => updateStatus(ticket.id, 'in_progress')}>
                                                    Marcar En Progreso
                                                </button>
                                            )}
                                            {(ticket.status === 'open' || ticket.status === 'in_progress') && (
                                                <button className="btn btn-success btn-sm btn-block" onClick={() => updateStatus(ticket.id, 'resolved')}>
                                                    Marcar Resuelto
                                                </button>
                                            )}
                                            {ticket.status !== 'closed' && (
                                                <button className="btn btn-ghost btn-sm btn-block" onClick={() => updateStatus(ticket.id, 'closed')} style={{ color: 'var(--color-danger)' }}>
                                                    Cerrar Ticket
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </div>
    );
}
