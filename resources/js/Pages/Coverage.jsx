import React, { useState, useEffect } from 'react';
import api from '../api';
import { MapPin, Plus, Search, Trash2, Edit2, X, Globe, Upload, Download } from 'lucide-react';

export default function Coverage() {
    const [areas, setAreas] = useState([]);
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingArea, setEditingArea] = useState(null);

    useEffect(() => { loadAreas(); }, [search]);

    async function loadAreas() {
        try {
            const res = await api.get('/coverage', { params: { search } });
            setAreas(res.data.data || []);
        } catch { } finally { setLoading(false); }
    }

    async function deleteArea(id) {
        if (!confirm('¿Eliminar esta zona de cobertura?')) return;
        try { await api.delete(`/coverage/${id}`); loadAreas(); } catch { }
    }

    async function toggleActive(area) {
        const newStatus = !area.is_active;
        setAreas(prev => prev.map(a => a.id === area.id ? { ...a, is_active: newStatus } : a));
        try {
            await api.put(`/coverage/${area.id}`, { is_active: newStatus });
        } catch {
            setAreas(prev => prev.map(a => a.id === area.id ? { ...a, is_active: !newStatus } : a));
        }
    }

    async function handleImport(e) {
        const file = e.target.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('file', file);
        setLoading(true);
        try {
            const res = await api.post('/coverage/import', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
            alert(res.data.message);
            loadAreas();
        } catch (err) { alert(err.response?.data?.message || 'Error al importar'); }
        finally { setLoading(false); e.target.value = ''; }
    }

    function downloadTemplate() {
        const csv = "ciudad,colonia,codigo_postal,activo\nToluca,Centro,50000,si\nToluca,San Sebastian,50010,si";
        const blob = new Blob([csv], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'plantilla_cobertura.csv';
        document.body.appendChild(link); link.click(); document.body.removeChild(link);
    }

    async function clearAll() {
        if (!confirm('¿Borrar TODAS las zonas de cobertura?')) return;
        if (!confirm('Confirma por segunda vez. Esta acción no se puede deshacer.')) return;
        setLoading(true);
        try { await api.delete('/coverage/clear-all'); loadAreas(); } catch { } finally { setLoading(false); }
    }

    if (loading && areas.length === 0) return <div className="loading-spinner"><div className="spinner"></div></div>;

    return (
        <div className="fade-in">
            <div className="page-header" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <div>
                    <h1>Zonas de Cobertura</h1>
                    <p>Gestiona las áreas donde se ofrece servicio</p>
                </div>
                <div style={{ display: 'flex', gap: 8 }}>
                    <button className="btn btn-secondary" onClick={() => document.getElementById('csv-import').click()}>
                        <Upload size={16} /> Importar CSV
                    </button>
                    <input type="file" id="csv-import" hidden accept=".csv" onChange={handleImport} />
                    <button className="btn btn-primary" onClick={() => { setEditingArea(null); setShowModal(true); }}>
                        <Plus size={16} /> Nueva Zona
                    </button>
                </div>
            </div>

            <div className="card" style={{ margin: '0 32px 20px', padding: '16px 20px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12 }}>
                    <div style={{ flex: 1, maxWidth: 400, position: 'relative' }}>
                        <Search size={16} style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                        <input className="form-input" style={{ paddingLeft: 36 }} placeholder="Buscar por CP o colonia..." value={search} onChange={e => setSearch(e.target.value)} />
                    </div>
                    <div style={{ display: 'flex', gap: 8 }}>
                        <button onClick={downloadTemplate} className="btn btn-ghost btn-sm"><Download size={14} /> Plantilla</button>
                        <button onClick={clearAll} className="btn btn-ghost btn-sm" style={{ color: 'var(--color-danger)' }}><Trash2 size={14} /> Borrar Todo</button>
                    </div>
                </div>
            </div>

            <div className="table-container">
                <table className="table">
                    <thead>
                        <tr>
                            <th>Colonia</th>
                            <th>CP</th>
                            <th>Ciudad</th>
                            <th>Estado</th>
                            <th style={{ textAlign: 'right' }}>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {areas.length === 0 ? (
                            <tr>
                                <td colSpan="5" className="text-center" style={{ padding: 60 }}>
                                    <div className="empty-state" style={{ padding: 0 }}>
                                        <Globe size={36} />
                                        <p>No hay áreas de cobertura registradas</p>
                                    </div>
                                </td>
                            </tr>
                        ) : areas.map(area => (
                            <tr key={area.id}>
                                <td style={{ fontWeight: 600 }}>{area.neighborhood}</td>
                                <td style={{ fontWeight: 600, color: 'var(--color-primary)' }}>{area.zip_code}</td>
                                <td style={{ color: 'var(--text-secondary)' }}>{area.city}</td>
                                <td>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                        <div className={`toggle-switch ${area.is_active ? 'active' : ''}`} onClick={() => toggleActive(area)}>
                                            <div className="toggle-slider"></div>
                                        </div>
                                        <span style={{ fontSize: '0.7rem', fontWeight: 600, color: area.is_active ? 'var(--color-success)' : 'var(--text-muted)' }}>
                                            {area.is_active ? 'Activa' : 'Inactiva'}
                                        </span>
                                    </div>
                                </td>
                                <td style={{ textAlign: 'right' }}>
                                    <div style={{ display: 'flex', gap: 4, justifyContent: 'flex-end' }}>
                                        <button className="btn-icon" onClick={() => { setEditingArea(area); setShowModal(true); }}><Edit2 size={16} /></button>
                                        <button className="btn-icon" style={{ color: 'var(--color-danger)' }} onClick={() => deleteArea(area.id)}><Trash2 size={16} /></button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {showModal && (
                <div className="modal-overlay" onClick={() => setShowModal(false)}>
                    <div className="modal" onClick={e => e.stopPropagation()}>
                        <div className="modal-header">
                            <h2>{editingArea ? 'Editar Zona' : 'Nueva Zona'}</h2>
                            <button className="btn-icon" onClick={() => setShowModal(false)}><X size={18} /></button>
                        </div>
                        <form onSubmit={async (e) => {
                            e.preventDefault();
                            const formData = {
                                city: e.target.city.value,
                                neighborhood: e.target.neighborhood.value,
                                zip_code: e.target.zip_code.value,
                                streets: e.target.streets.value,
                            };
                            try {
                                if (editingArea) await api.put(`/coverage/${editingArea.id}`, formData);
                                else await api.post('/coverage', formData);
                                setShowModal(false); loadAreas();
                            } catch { alert('Error al guardar'); }
                        }}>
                            <div className="modal-body">
                                <div className="form-group">
                                    <label className="form-label">Ciudad / Municipio</label>
                                    <input name="city" className="form-input" defaultValue={editingArea?.city || ''} required />
                                </div>
                                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                                    <div className="form-group">
                                        <label className="form-label">Colonia</label>
                                        <input name="neighborhood" className="form-input" defaultValue={editingArea?.neighborhood} required />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Código Postal</label>
                                        <input name="zip_code" className="form-input" defaultValue={editingArea?.zip_code} required maxLength={5} />
                                    </div>
                                </div>
                                <div className="form-group">
                                    <label className="form-label">Calles / Notas (Opcional)</label>
                                    <textarea name="streets" className="form-input" defaultValue={editingArea?.streets} rows={3} />
                                </div>
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
