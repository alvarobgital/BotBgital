import React, { useState, useEffect } from 'react';
import api from '../api';
import { Target, Search, ChevronDown, Trash2, User, Building2, MapPin, Phone, Package, Clock } from 'lucide-react';

const STATUS_CONFIG = {
    pending: { label: 'Pendiente', color: '#F59E0B', bg: '#FFF7ED' },
    contacted: { label: 'Contactado', color: '#3B82F6', bg: '#EFF6FF' },
    qualified: { label: 'Calificado', color: '#8B5CF6', bg: '#F5F3FF' },
    quoted: { label: 'Cotizado', color: '#06B6D4', bg: '#ECFEFF' },
    contracted: { label: 'Contratado', color: '#10B981', bg: '#ECFDF5' },
    lost: { label: 'Perdido', color: '#EF4444', bg: '#FEF2F2' },
};

export default function Leads() {
    const [leads, setLeads] = useState([]);
    const [stats, setStats] = useState({});
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [filterStatus, setFilterStatus] = useState('');
    const [filterType, setFilterType] = useState('');

    useEffect(() => {
        const timer = setTimeout(() => loadLeads(), 300);
        return () => clearTimeout(timer);
    }, [search, filterStatus, filterType]);

    useEffect(() => { loadStats(); }, []);

    async function loadLeads() {
        setLoading(true);
        try {
            const params = { search };
            if (filterStatus) params.status = filterStatus;
            if (filterType) params.client_type = filterType;
            const res = await api.get('/leads', { params });
            setLeads(res.data.data || []);
        } catch { } finally { setLoading(false); }
    }

    async function loadStats() {
        try { const res = await api.get('/leads/stats'); setStats(res.data); } catch { }
    }

    async function updateStatus(lead, newStatus) {
        try {
            await api.put(`/leads/${lead.id}`, { status: newStatus });
            setLeads(prev => prev.map(l => l.id === lead.id ? { ...l, status: newStatus } : l));
            loadStats();
        } catch { }
    }

    async function deleteLead(id) {
        if (!confirm('¿Eliminar este lead?')) return;
        try { await api.delete(`/leads/${id}`); loadLeads(); loadStats(); } catch { }
    }

    function timeAgo(dateStr) {
        const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
        if (diff < 60) return 'Ahora';
        if (diff < 3600) return `${Math.floor(diff / 60)}m`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h`;
        return `${Math.floor(diff / 86400)}d`;
    }

    return (
        <div className="fade-in">
            <div className="page-header" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <div>
                    <h1>Leads</h1>
                    <p>Clientes interesados y seguimiento de ventas</p>
                </div>
            </div>

            {/* Pipeline stats */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(6, 1fr)', gap: 10, padding: '0 32px 20px' }}>
                {Object.entries(STATUS_CONFIG).map(([key, cfg]) => (
                    <div key={key} className="card" style={{ padding: '14px 16px', cursor: 'pointer', border: filterStatus === key ? `2px solid ${cfg.color}` : '1px solid var(--border)' }}
                        onClick={() => setFilterStatus(filterStatus === key ? '' : key)}>
                        <div style={{ fontSize: '0.7rem', fontWeight: 600, color: cfg.color, textTransform: 'uppercase', marginBottom: 4 }}>{cfg.label}</div>
                        <div style={{ fontSize: '1.4rem', fontWeight: 700 }}>{stats[key] ?? 0}</div>
                    </div>
                ))}
            </div>

            {/* Search & filters */}
            <div className="card" style={{ margin: '0 32px 20px', padding: '14px 20px' }}>
                <div style={{ display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
                    <div style={{ flex: 1, minWidth: 240, position: 'relative' }}>
                        <Search size={16} style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                        <input className="form-input" style={{ paddingLeft: 36 }} placeholder="Buscar por nombre, teléfono, empresa..." value={search} onChange={e => setSearch(e.target.value)} />
                    </div>
                    <select className="form-input" style={{ width: 'auto', minWidth: 140 }} value={filterType} onChange={e => setFilterType(e.target.value)}>
                        <option value="">Todos los tipos</option>
                        <option value="residencial">🏠 Residencial</option>
                        <option value="empresa">🏢 Empresa</option>
                    </select>
                    {(filterStatus || filterType || search) && (
                        <button className="btn btn-ghost btn-sm" onClick={() => { setSearch(''); setFilterStatus(''); setFilterType(''); }}>Limpiar</button>
                    )}
                </div>
            </div>

            {/* Leads table */}
            <div className="table-container">
                <table className="table">
                    <thead>
                        <tr>
                            <th>Contacto</th>
                            <th>Tipo</th>
                            <th>Plan de Interés</th>
                            <th>CP</th>
                            <th>Fuente</th>
                            <th>Estado</th>
                            <th>Tiempo</th>
                            <th style={{ textAlign: 'right' }}>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr><td colSpan="8" style={{ textAlign: 'center', padding: 60 }}><div className="spinner" style={{ margin: '0 auto' }}></div></td></tr>
                        ) : leads.length === 0 ? (
                            <tr><td colSpan="8" style={{ textAlign: 'center', padding: 60 }}>
                                <div className="empty-state" style={{ padding: 0 }}><Target size={36} /><p>No hay leads registrados</p></div>
                            </td></tr>
                        ) : leads.map(lead => {
                            const sc = STATUS_CONFIG[lead.status] || STATUS_CONFIG.pending;
                            const contactName = lead.contact?.name || lead.phone || '—';
                            const contactPhone = lead.phone || lead.contact?.phone || '—';
                            return (
                                <tr key={lead.id}>
                                    <td>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                                            <div style={{ width: 34, height: 34, borderRadius: '50%', background: 'var(--bg-app)', color: 'var(--color-primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                                                {lead.client_type === 'empresa' ? <Building2 size={15} /> : <User size={15} />}
                                            </div>
                                            <div>
                                                <div style={{ fontWeight: 600, fontSize: '0.85rem' }}>{contactName}</div>
                                                <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{contactPhone}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span className="badge" style={{ background: lead.client_type === 'empresa' ? '#EEF2FF' : '#F0FDF4', color: lead.client_type === 'empresa' ? '#4338CA' : '#16A34A', fontSize: '0.7rem' }}>
                                            {lead.client_type === 'empresa' ? '🏢 Empresa' : '🏠 Casa'}
                                        </span>
                                    </td>
                                    <td style={{ fontWeight: 600, color: 'var(--color-primary)' }}>{lead.plan_interest || '—'}</td>
                                    <td style={{ color: 'var(--text-secondary)' }}>{lead.zip_code || '—'}</td>
                                    <td style={{ color: 'var(--text-secondary)', fontSize: '0.8rem' }}>{lead.source || 'whatsapp'}</td>
                                    <td>
                                        <div style={{ position: 'relative', display: 'inline-block' }}>
                                            <select
                                                value={lead.status}
                                                onChange={e => updateStatus(lead, e.target.value)}
                                                style={{ appearance: 'none', background: sc.bg, color: sc.color, border: 'none', padding: '4px 24px 4px 10px', borderRadius: 6, fontWeight: 600, fontSize: '0.75rem', cursor: 'pointer' }}
                                            >
                                                {Object.entries(STATUS_CONFIG).map(([k, v]) => (
                                                    <option key={k} value={k}>{v.label}</option>
                                                ))}
                                            </select>
                                            <ChevronDown size={12} style={{ position: 'absolute', right: 6, top: '50%', transform: 'translateY(-50%)', pointerEvents: 'none', color: sc.color }} />
                                        </div>
                                    </td>
                                    <td style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}><Clock size={12} /> {timeAgo(lead.created_at)}</div>
                                    </td>
                                    <td style={{ textAlign: 'right' }}>
                                        <button className="btn-icon" style={{ color: 'var(--color-danger)' }} onClick={() => deleteLead(lead.id)}><Trash2 size={14} /></button>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
