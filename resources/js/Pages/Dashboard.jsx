import React, { useState, useEffect } from 'react';
import api from '../api';
import { MessageSquare, Users, Zap, Bot, Ticket, Package, AlertTriangle, TrendingUp } from 'lucide-react';

export default function Dashboard() {
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadStats();
        const interval = setInterval(loadStats, 30000);
        return () => clearInterval(interval);
    }, []);

    async function loadStats() {
        try {
            const res = await api.get('/dashboard/stats');
            setStats(res.data);
        } catch { } finally { setLoading(false); }
    }

    if (loading) return <div className="loading-spinner"><div className="spinner"></div></div>;

    const maxChartValue = Math.max(...(stats?.conversations_by_day?.map(d => d.count) || [1]), 1);

    return (
        <div className="fade-in">
            <div className="page-header">
                <h1>Dashboard</h1>
                <p>Resumen en tiempo real del sistema</p>
            </div>

            {/* Stat Cards — Row 1 */}
            <div className="dashboard-grid">
                <div className="dashboard-card" style={{ cursor: 'pointer' }} onClick={() => window.location.href = '/panel/conversations'}>
                    <div className="card-icon-box">
                        <MessageSquare size={24} />
                    </div>
                    <div className="card-content">
                        <div className="card-label">Conversaciones Hoy</div>
                        <div className="card-value">{stats?.conversations_today ?? 0}</div>
                    </div>
                </div>

                <div className="dashboard-card" style={{ cursor: 'pointer' }} onClick={() => window.location.href = '/panel/conversations?status=bot_active'}>
                    <div className="card-icon-box" style={{ background: '#ECFDF5', color: 'var(--color-success)' }}>
                        <Bot size={24} />
                    </div>
                    <div className="card-content">
                        <div className="card-label">Resueltas por Bot</div>
                        <div className="card-value">{stats?.bot_handled ?? 0}</div>
                    </div>
                </div>

                <div className="dashboard-card" style={{ cursor: 'pointer' }} onClick={() => window.location.href = '/panel/conversations?status=waiting_agent'}>
                    <div className="card-icon-box" style={{ background: '#FFF7ED', color: 'var(--color-warning)' }}>
                        <AlertTriangle size={24} />
                    </div>
                    <div className="card-content">
                        <div className="card-label">Esperando Agente</div>
                        <div className="card-value">{stats?.waiting_agent ?? 0}</div>
                    </div>
                </div>

                <div className="dashboard-card" style={{ cursor: 'pointer' }} onClick={() => window.location.href = '/panel/leads'}>
                    <div className="card-icon-box" style={{ background: '#EEF2FF', color: '#4338CA' }}>
                        <Zap size={24} />
                    </div>
                    <div className="card-content">
                        <div className="card-label">Nuevos Leads Hoy</div>
                        <div className="card-value">{stats?.new_leads_today ?? 0}</div>
                    </div>
                </div>

                <div className="dashboard-card" style={{ cursor: 'pointer' }} onClick={() => window.location.href = '/panel/customers'}>
                    <div className="card-icon-box" style={{ background: '#F0FDF4', color: '#16A34A' }}>
                        <Users size={24} />
                    </div>
                    <div className="card-content">
                        <div className="card-label">Clientes Activos</div>
                        <div className="card-value">{stats?.total_customers ?? 0}</div>
                        <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)', marginTop: 2 }}>
                            {stats?.active_services ?? 0} servicios activos
                        </div>
                    </div>
                </div>

                <div className="dashboard-card" style={{ cursor: 'pointer' }} onClick={() => window.location.href = '/panel/tickets'}>
                    <div className="card-icon-box" style={{ background: '#FEF2F2', color: 'var(--color-danger)' }}>
                        <Ticket size={24} />
                    </div>
                    <div className="card-content">
                        <div className="card-label">Tickets Abiertos</div>
                        <div className="card-value">{stats?.tickets_open ?? 0}</div>
                        {stats?.tickets_in_progress > 0 && (
                            <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)', marginTop: 2 }}>
                                {stats.tickets_in_progress} pendientes
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Activity Chart + Recent Conversations */}
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20, padding: '0 32px 28px' }}>
                {/* 7-Day Activity Chart */}
                <div className="card" style={{ padding: 24 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 20 }}>
                        <TrendingUp size={18} color="var(--color-primary)" />
                        <h3 style={{ fontSize: '0.95rem', fontWeight: 600 }}>Actividad (Últimos 7 días)</h3>
                    </div>
                    <div style={{ display: 'flex', alignItems: 'flex-end', gap: 8, height: 140 }}>
                        {stats?.conversations_by_day?.map((day, i) => (
                            <div key={i} style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 6 }}>
                                <span style={{ fontSize: '0.7rem', fontWeight: 600, color: 'var(--text-primary)' }}>
                                    {day.count}
                                </span>
                                <div style={{
                                    width: '100%',
                                    height: `${Math.max((day.count / maxChartValue) * 100, 4)}px`,
                                    background: 'var(--color-primary)',
                                    borderRadius: '4px 4px 0 0',
                                    minHeight: 4,
                                    transition: 'height .3s ease',
                                }} />
                                <span style={{ fontSize: '0.65rem', color: 'var(--text-muted)' }}>{day.date}</span>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Quick Stats Summary */}
                <div className="card" style={{ padding: 24 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 20 }}>
                        <Package size={18} color="var(--color-primary)" />
                        <h3 style={{ fontSize: '0.95rem', fontWeight: 600 }}>Resumen del Sistema</h3>
                    </div>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
                        {[
                            { label: 'Total Conversaciones', value: stats?.total_conversations },
                            { label: 'Contactos Registrados', value: stats?.total_contacts },
                            { label: 'Mensajes Hoy', value: stats?.messages_today },
                            { label: 'Servicios Activos', value: stats?.active_services },
                            { label: 'Servicios Suspendidos', value: stats?.suspended_services, color: 'var(--color-danger)' },
                        ].map((item, i) => (
                            <div key={i} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '8px 0', borderBottom: i < 4 ? '1px solid var(--border)' : 'none' }}>
                                <span style={{ fontSize: '0.85rem', color: 'var(--text-secondary)' }}>{item.label}</span>
                                <span style={{ fontSize: '1rem', fontWeight: 700, color: item.color || 'var(--text-primary)' }}>{item.value ?? 0}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Recent Activity Table */}
            {stats?.recent_conversations?.length > 0 && (
                <div className="table-container">
                    <div style={{ padding: '16px 20px', borderBottom: '1px solid var(--border)' }}>
                        <h3 style={{ fontSize: '0.95rem', fontWeight: 600 }}>Actividad Reciente</h3>
                    </div>
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Contacto</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th>Último Mensaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            {stats.recent_conversations.map(conv => {
                                const statusMap = {
                                    bot_active: { cls: 'badge-bot', text: 'Bot' },
                                    waiting_agent: { cls: 'badge-waiting', text: 'Esperando' },
                                    agent_active: { cls: 'badge-agent', text: 'Agente' },
                                    closed: { cls: 'badge-closed', text: 'Cerrada' },
                                };
                                const s = statusMap[conv.status] || statusMap.closed;
                                return (
                                    <tr key={conv.id}>
                                        <td style={{ fontWeight: 600 }}>{conv.contact?.name || 'Sin nombre'}</td>
                                        <td style={{ color: 'var(--text-secondary)' }}>{conv.contact?.phone}</td>
                                        <td><span className={`badge ${s.cls}`}>{s.text}</span></td>
                                        <td style={{ maxWidth: 280, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', color: 'var(--text-secondary)' }}>
                                            {conv.latest_message?.content || '—'}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
