import React, { useState, useEffect } from 'react';
import api from '../api';
import {
    Plus, Search, Edit2, Trash2, Home, Building2, Landmark, Gem, Box, X, Upload, Download
} from 'lucide-react';

export default function Plans() {
    const [plans, setPlans] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [categoryFilter, setCategoryFilter] = useState('');
    const [showModal, setShowModal] = useState(false);
    const [editingPlan, setEditingPlan] = useState(null);

    const [form, setForm] = useState({
        category: 'HOGAR', name: '', description: '', price: '', speed: '', is_active: true
    });

    useEffect(() => { loadPlans(); }, [categoryFilter]);

    async function loadPlans() {
        setLoading(true);
        try {
            const res = await api.get('/plans', { params: { search, category: categoryFilter } });
            setPlans(res.data);
        } catch (err) { console.error(err); }
        finally { setLoading(false); }
    }

    function handleSearch(e) { e.preventDefault(); loadPlans(); }

    function openModal(plan = null) {
        if (plan) { setEditingPlan(plan); setForm({ ...plan }); }
        else {
            setEditingPlan(null);
            setForm({ category: categoryFilter || 'HOGAR', name: '', description: '', price: '', speed: '', is_active: true });
        }
        setShowModal(true);
    }

    async function handleSubmit(e) {
        e.preventDefault();
        try {
            if (editingPlan) await api.put(`/plans/${editingPlan.id}`, form);
            else await api.post('/plans', form);
            setShowModal(false); loadPlans();
        } catch { alert('Error al guardar el plan'); }
    }

    async function handleDelete(id) {
        if (!confirm('¿Eliminar este plan?')) return;
        try { await api.delete(`/plans/${id}`); loadPlans(); }
        catch { alert('Error al eliminar'); }
    }

    async function toggleActive(plan) {
        setPlans(prev => prev.map(p => p.id === plan.id ? { ...p, is_active: !p.is_active } : p));
        try { await api.post(`/plans/${plan.id}/toggle`); } catch { loadPlans(); }
    }

    async function handleImport(e) {
        const file = e.target.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('file', file);
        setLoading(true);
        try {
            const res = await api.post('/plans/import', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
            alert(res.data.message);
            loadPlans();
        } catch (err) { alert(err.response?.data?.message || 'Error al importar'); }
        finally { setLoading(false); e.target.value = ''; }
    }

    function downloadTemplate() {
        const csv = 'tipo,nombre,velocidad,precio,descripcion\nHOGAR,BASIC,100,399,Internet residencial\nHOGAR,STANDARD,200,449,Internet residencial alta velocidad\nNEGOCIO,BASIC,100,549,Internet empresarial';
        const blob = new Blob([csv], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'plantilla_planes.csv';
        document.body.appendChild(link); link.click(); document.body.removeChild(link);
    }

    const catIcons = { HOGAR: Home, NEGOCIO: Building2, PYME: Landmark, DEDICADO: Gem };

    return (
        <div className="fade-in">
            <div className="page-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <div>
                    <h1>Planes de Internet</h1>
                    <p>Catálogo de servicios disponibles para el Bot</p>
                </div>
                <div style={{ display: 'flex', gap: 8 }}>
                    <button className="btn btn-ghost" onClick={downloadTemplate}><Download size={16} /> Plantilla</button>
                    <button className="btn btn-secondary" onClick={() => document.getElementById('plan-import').click()}>
                        <Upload size={16} /> Importar CSV
                    </button>
                    <input type="file" id="plan-import" hidden accept=".csv,.xlsx,.xls" onChange={handleImport} />
                    <button className="btn btn-primary" onClick={() => openModal()}>
                        <Plus size={16} /> Nuevo Plan
                    </button>
                </div>
            </div>

            <div className="card" style={{ margin: '0 32px 20px', padding: '16px 20px' }}>
                <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
                    <form onSubmit={handleSearch} style={{ flex: 1, display: 'flex', gap: 8 }}>
                        <div style={{ flex: 1, position: 'relative' }}>
                            <Search size={16} style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                            <input className="form-input" style={{ paddingLeft: 36 }} placeholder="Buscar plan..." value={search} onChange={e => setSearch(e.target.value)} />
                        </div>
                        <button type="submit" className="btn btn-secondary">Buscar</button>
                    </form>
                    <select className="form-input" style={{ width: 200 }} value={categoryFilter} onChange={e => setCategoryFilter(e.target.value)}>
                        <option value="">Todas</option>
                        <option value="HOGAR">Hogar</option>
                        <option value="NEGOCIO">Negocio</option>
                        <option value="PYME">Pyme</option>
                        <option value="DEDICADO">Dedicado</option>
                    </select>
                </div>
            </div>

            {loading ? (
                <div className="loading-spinner"><div className="spinner"></div></div>
            ) : (
                <div className="table-container">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th>Nombre</th>
                                <th>Velocidad</th>
                                <th>Precio</th>
                                <th>Estado</th>
                                <th style={{ textAlign: 'right' }}>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {plans.length === 0 ? (
                                <tr>
                                    <td colSpan="6" className="text-center" style={{ padding: 60 }}>
                                        <div className="empty-state" style={{ padding: 0 }}>
                                            <Box size={36} />
                                            <p>No hay planes registrados</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : plans.map(plan => {
                                const CatIcon = catIcons[plan.category] || Box;
                                return (
                                    <tr key={plan.id}>
                                        <td>
                                            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                                <CatIcon size={14} color="var(--color-primary)" />
                                                <span className="badge" style={{ background: 'var(--bg-app)', color: 'var(--color-primary)' }}>{plan.category}</span>
                                            </div>
                                        </td>
                                        <td style={{ fontWeight: 600 }}>{plan.name}</td>
                                        <td style={{ color: 'var(--text-secondary)' }}>{plan.speed || '—'}</td>
                                        <td style={{ fontWeight: 600 }}>{plan.price > 0 ? `$${Number(plan.price).toLocaleString()}` : 'NEGOCIAR'}</td>
                                        <td>
                                            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                                <div className={`toggle-switch ${plan.is_active ? 'active' : ''}`} onClick={() => toggleActive(plan)}>
                                                    <div className="toggle-slider"></div>
                                                </div>
                                                <span style={{ fontSize: '0.7rem', fontWeight: 600, color: plan.is_active ? 'var(--color-success)' : 'var(--text-muted)' }}>
                                                    {plan.is_active ? 'Activo' : 'Inactivo'}
                                                </span>
                                            </div>
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            <div style={{ display: 'flex', gap: 4, justifyContent: 'flex-end' }}>
                                                <button className="btn-icon" onClick={() => openModal(plan)}><Edit2 size={16} /></button>
                                                <button className="btn-icon" style={{ color: 'var(--color-danger)' }} onClick={() => handleDelete(plan.id)}><Trash2 size={16} /></button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}

            {showModal && (
                <div className="modal-overlay" onClick={() => setShowModal(false)}>
                    <div className="modal" onClick={e => e.stopPropagation()}>
                        <div className="modal-header">
                            <h2>{editingPlan ? 'Editar Plan' : 'Nuevo Plan'}</h2>
                            <button className="btn-icon" onClick={() => setShowModal(false)}><X size={18} /></button>
                        </div>
                        <form onSubmit={handleSubmit}>
                            <div className="modal-body">
                                <div className="form-group">
                                    <label className="form-label">Categoría</label>
                                    <select className="form-input" value={form.category} onChange={e => setForm({ ...form, category: e.target.value })} required>
                                        <option value="HOGAR">Hogar</option>
                                        <option value="NEGOCIO">Negocio</option>
                                        <option value="PYME">Pyme</option>
                                        <option value="DEDICADO">Dedicado</option>
                                    </select>
                                </div>
                                <div className="form-group">
                                    <label className="form-label">Nombre del Plan</label>
                                    <input className="form-input" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} placeholder="Ej: HIGH 300 Mbps" required />
                                </div>
                                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                                    <div className="form-group">
                                        <label className="form-label">Velocidad</label>
                                        <input className="form-input" value={form.speed} onChange={e => setForm({ ...form, speed: e.target.value })} placeholder="Ej: 300 Mbps" />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Precio ($)</label>
                                        <input type="number" className="form-input" value={form.price} onChange={e => setForm({ ...form, price: e.target.value })} placeholder="0 = Negociar" required />
                                    </div>
                                </div>
                                <div className="form-group">
                                    <label className="form-label">Descripción</label>
                                    <textarea className="form-input" value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} placeholder="Detalles del plan..." rows={3} />
                                </div>
                                <label style={{ display: 'flex', alignItems: 'center', gap: 10, cursor: 'pointer' }}>
                                    <input type="checkbox" checked={form.is_active} onChange={e => setForm({ ...form, is_active: e.target.checked })} style={{ width: 16, height: 16, accentColor: 'var(--color-primary)' }} />
                                    <span style={{ fontSize: '0.85rem', fontWeight: 600, color: 'var(--text-secondary)' }}>Visible en el Bot</span>
                                </label>
                            </div>
                            <div className="modal-footer">
                                <button type="button" className="btn btn-ghost" onClick={() => setShowModal(false)}>Cancelar</button>
                                <button type="submit" className="btn btn-primary">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
