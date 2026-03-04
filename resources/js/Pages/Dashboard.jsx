import React, { useState, useEffect } from 'react';
import api from '../api';
import {
    MessageSquare, Users, Clock, AlertTriangle,
    CheckCircle, TrendingUp, Zap
} from 'lucide-react';

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
        } catch { } finally {
            setLoading(false);
        }
    }

    if (loading) return <div className="loading-spinner"><div className="spinner"></div></div>;

    const cards = [
        { icon: MessageSquare, color: 'purple', value: stats?.total_conversations || 0, label: 'Conversaciones Totales' },
        { icon: Zap, color: 'green', value: stats?.active_bot || 0, label: 'Bot Activo' },
        { icon: AlertTriangle, color: 'yellow', value: stats?.waiting_agent || 0, label: 'Esperando Asesor' },
        { icon: Users, color: 'blue', value: stats?.agent_active || 0, label: 'Con Agente' },
        { icon: CheckCircle, color: 'purple', value: stats?.closed || 0, label: 'Cerradas' },
        { icon: Users, color: 'green', value: stats?.total_contacts || 0, label: 'Contactos' },
        { icon: TrendingUp, color: 'blue', value: stats?.messages_today || 0, label: 'Mensajes Hoy' },
    ];

    return (
        <>
            <div className="page-header">
                <h1>Dashboard</h1>
                <p>Resumen general de BotBgital</p>
            </div>
            <div className="page-body">
                <div className="stats-grid">
                    {cards.map((card, i) => (
                        <div className="stat-card" key={i}>
                            <div className={`stat-card-icon ${card.color}`}>
                                <card.icon size={20} />
                            </div>
                            <h3>{card.value}</h3>
                            <p>{card.label}</p>
                        </div>
                    ))}
                </div>

                {stats?.recent_conversations?.length > 0 && (
                    <div className="settings-card">
                        <h3>Conversaciones Recientes</h3>
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Contacto</th>
                                    <th>Teléfono</th>
                                    <th>Estado</th>
                                    <th>Último mensaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                {stats.recent_conversations.map(conv => (
                                    <tr key={conv.id}>
                                        <td>{conv.contact?.name || 'Desconocido'}</td>
                                        <td style={{ fontFamily: 'var(--font-body)', fontSize: '0.82rem' }}>
                                            {conv.contact?.phone}
                                        </td>
                                        <td>
                                            <span className={`badge badge-${conv.status === 'bot_active' ? 'bot' : conv.status === 'waiting_agent' ? 'waiting' : conv.status === 'agent_active' ? 'agent' : 'closed'}`}>
                                                {conv.status === 'bot_active' ? 'BOT ACTIVO' :
                                                    conv.status === 'waiting_agent' ? 'ESPERANDO' :
                                                        conv.status === 'agent_active' ? 'CON AGENTE' : 'CERRADA'}
                                            </span>
                                        </td>
                                        <td style={{ maxWidth: 200, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                                            {conv.latest_message?.content || '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
