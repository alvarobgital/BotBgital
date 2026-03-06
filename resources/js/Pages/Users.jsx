import React, { useState, useEffect } from 'react';
import api from '../api';
import { UserPlus, Trash2, User, X } from 'lucide-react';

function UserModal({ onClose, onSave }) {
    const [form, setForm] = useState({ name: '', email: '', password: '', role: 'agent' });
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    async function handleSubmit(e) {
        e.preventDefault();
        setError(''); setSaving(true);
        try {
            await api.post('/users', form);
            onSave();
        } catch (err) { setError(err.response?.data?.message || 'Error al crear usuario'); }
        finally { setSaving(false); }
    }

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal" onClick={e => e.stopPropagation()}>
                <div className="modal-header">
                    <h2>Nuevo Usuario</h2>
                    <button className="btn-icon" onClick={onClose}><X size={18} /></button>
                </div>
                <form onSubmit={handleSubmit}>
                    <div className="modal-body">
                        {error && (
                            <div style={{ background: '#FEF2F2', color: '#DC2626', padding: '10px 14px', borderRadius: 8, fontSize: '0.85rem', marginBottom: 16, border: '1px solid #FECACA' }}>
                                {error}
                            </div>
                        )}
                        <div className="form-group">
                            <label className="form-label">Nombre Completo</label>
                            <input className="form-input" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} required />
                        </div>
                        <div className="form-group">
                            <label className="form-label">Correo Electrónico</label>
                            <input className="form-input" type="email" value={form.email} onChange={e => setForm({ ...form, email: e.target.value })} required />
                        </div>
                        <div className="form-group">
                            <label className="form-label">Contraseña</label>
                            <input className="form-input" type="password" value={form.password} onChange={e => setForm({ ...form, password: e.target.value })} required minLength={8} />
                        </div>
                        <div className="form-group">
                            <label className="form-label">Rol</label>
                            <select className="form-input" value={form.role} onChange={e => setForm({ ...form, role: e.target.value })}>
                                <option value="admin">Administrador</option>
                                <option value="agent">Agente</option>
                            </select>
                        </div>
                    </div>
                    <div className="modal-footer">
                        <button type="button" className="btn btn-ghost" onClick={onClose}>Cancelar</button>
                        <button type="submit" className="btn btn-primary" disabled={saving}>
                            {saving ? 'Creando...' : 'Crear Usuario'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

export default function Users() {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);

    useEffect(() => { loadUsers(); }, []);

    async function loadUsers() {
        try { const res = await api.get('/users'); setUsers(res.data); }
        catch { } finally { setLoading(false); }
    }

    async function deleteUser(id) {
        if (!confirm('¿Eliminar este usuario?')) return;
        try { await api.delete(`/users/${id}`); loadUsers(); }
        catch (err) { alert(err.response?.data?.message || 'Error al eliminar'); }
    }

    if (loading) return <div className="loading-spinner"><div className="spinner"></div></div>;

    return (
        <div className="fade-in">
            <div className="page-header" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <div>
                    <h1>Usuarios</h1>
                    <p>Gestiona quiénes tienen acceso al panel (Máximo 3)</p>
                </div>
                {users.length < 3 && (
                    <button className="btn btn-primary" onClick={() => setShowModal(true)}>
                        <UserPlus size={16} /> Nuevo Usuario
                    </button>
                )}
            </div>

            <div className="page-body">
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: 16 }}>
                    {users.map(u => (
                        <div key={u.id} className="card" style={{ padding: 20 }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 16 }}>
                                <div style={{ width: 40, height: 40, borderRadius: '50%', background: 'var(--bg-app)', color: 'var(--color-primary)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                    <User size={20} />
                                </div>
                                <div style={{ flex: 1, minWidth: 0 }}>
                                    <h4 style={{ margin: 0, fontSize: '0.95rem', fontWeight: 600 }}>{u.name}</h4>
                                    <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>{u.email}</span>
                                </div>
                                <span className={`badge badge-${u.role === 'admin' ? 'bot' : 'agent'}`}>
                                    {u.role}
                                </span>
                            </div>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>
                                    Desde: {new Date(u.created_at).toLocaleDateString()}
                                </span>
                                {users.length > 1 && (
                                    <button className="btn-icon" style={{ color: 'var(--color-danger)' }} onClick={() => deleteUser(u.id)}>
                                        <Trash2 size={14} />
                                    </button>
                                )}
                            </div>
                        </div>
                    ))}
                </div>

                {users.length >= 3 && (
                    <div style={{ marginTop: 16, padding: 16, background: '#EEF2FF', borderRadius: 8, border: '1px solid var(--border)', fontSize: '0.85rem', color: 'var(--color-primary)' }}>
                        Has alcanzado el límite de 3 usuarios. Elimina uno para agregar a alguien más.
                    </div>
                )}
            </div>

            {showModal && <UserModal onClose={() => setShowModal(false)} onSave={() => { setShowModal(false); loadUsers(); }} />}
        </div>
    );
}
