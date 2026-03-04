import React, { useState, useEffect } from 'react';
import api from '../api';
import { Search, Users, Phone, Calendar } from 'lucide-react';

export default function Contacts() {
    const [contacts, setContacts] = useState([]);
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadContacts();
    }, [search]);

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
        return new Date(dateStr).toLocaleDateString('es-MX', {
            day: '2-digit', month: 'short', year: 'numeric'
        });
    }

    return (
        <>
            <div className="page-header">
                <h1>Contactos</h1>
                <p>Todos los contactos que han interactuado con el bot</p>
            </div>
            <div className="page-body">
                <div style={{ marginBottom: 16 }}>
                    <div className="conversation-search" style={{ maxWidth: 400, background: 'var(--color-white)', border: '1.5px solid var(--bg-secondary)', borderRadius: 'var(--radius-md)' }}>
                        <Search size={18} />
                        <input placeholder="Buscar por nombre o teléfono..."
                            value={search} onChange={e => setSearch(e.target.value)} />
                    </div>
                </div>

                {loading ? (
                    <div className="loading-spinner"><div className="spinner"></div></div>
                ) : contacts.length === 0 ? (
                    <div className="empty-state">
                        <Users />
                        <p>No hay contactos</p>
                    </div>
                ) : (
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Teléfono</th>
                                <th>Plataforma</th>
                                <th>Última conversación</th>
                                <th>Registrado</th>
                            </tr>
                        </thead>
                        <tbody>
                            {contacts.map(contact => (
                                <tr key={contact.id}>
                                    <td style={{ fontWeight: 600, fontFamily: 'var(--font-display)' }}>
                                        {contact.name || 'Desconocido'}
                                    </td>
                                    <td>
                                        <Phone size={12} style={{ display: 'inline', marginRight: 4, verticalAlign: 'middle', opacity: 0.5 }} />
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
                                    <td>
                                        <Calendar size={12} style={{ display: 'inline', marginRight: 4, verticalAlign: 'middle', opacity: 0.5 }} />
                                        {formatDate(contact.created_at)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </>
    );
}
