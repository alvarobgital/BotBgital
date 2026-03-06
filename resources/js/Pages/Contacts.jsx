import React, { useState, useEffect } from 'react';
import api from '../api';
import { Search, Zap, Phone, Calendar } from 'lucide-react';

export default function Contacts() {
    const [contacts, setContacts] = useState([]);
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(true);

    useEffect(() => { loadContacts(); }, [search]);

    async function loadContacts() {
        try {
            const params = {};
            if (search) params.search = search;
            const res = await api.get('/contacts', { params });
            setContacts(res.data.data || []);
        } catch { } finally { setLoading(false); }
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        return new Date(dateStr).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    return (
        <div className="fade-in">
            <div className="page-header">
                <h1>Leads / Contactos</h1>
                <p>Personas que han interactuado con el bot</p>
            </div>

            <div className="card" style={{ margin: '0 32px 20px', padding: '16px 20px' }}>
                <div style={{ maxWidth: 400, position: 'relative' }}>
                    <Search size={16} style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                    <input className="form-input" style={{ paddingLeft: 36 }} placeholder="Buscar por nombre o teléfono..." value={search} onChange={e => setSearch(e.target.value)} />
                </div>
            </div>

            {loading ? (
                <div className="loading-spinner"><div className="spinner"></div></div>
            ) : contacts.length === 0 ? (
                <div className="empty-state">
                    <Zap size={48} />
                    <p>No hay contactos registrados</p>
                </div>
            ) : (
                <div className="table-container">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Teléfono</th>
                                <th>Plataforma</th>
                                <th>Última Conversación</th>
                                <th>Registrado</th>
                            </tr>
                        </thead>
                        <tbody>
                            {contacts.map(contact => (
                                <tr key={contact.id}>
                                    <td style={{ fontWeight: 600 }}>
                                        {contact.name || 'Desconocido'}
                                    </td>
                                    <td style={{ color: 'var(--text-secondary)' }}>
                                        <Phone size={12} style={{ display: 'inline', marginRight: 4, verticalAlign: 'middle', opacity: .5 }} />
                                        {contact.phone}
                                    </td>
                                    <td>
                                        <span className="badge badge-bot">{contact.platform?.toUpperCase()}</span>
                                    </td>
                                    <td>
                                        {contact.latest_conversation
                                            ? <span className={`badge badge-${contact.latest_conversation.status === 'bot_active' ? 'bot' : contact.latest_conversation.status === 'waiting_agent' ? 'waiting' : contact.latest_conversation.status === 'agent_active' ? 'agent' : 'closed'}`}>
                                                {contact.latest_conversation.status.replace('_', ' ').toUpperCase()}
                                            </span>
                                            : '—'
                                        }
                                    </td>
                                    <td style={{ color: 'var(--text-secondary)' }}>
                                        <Calendar size={12} style={{ display: 'inline', marginRight: 4, verticalAlign: 'middle', opacity: .5 }} />
                                        {formatDate(contact.created_at)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
