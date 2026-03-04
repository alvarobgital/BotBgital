import React, { useState, useEffect } from 'react';
import api from '../api';
import {
    Plus, Edit2, Trash2, X, GitBranch
} from 'lucide-react';

function ChipsInput({ value, onChange, placeholder }) {
    const [inputValue, setInputValue] = useState('');

    function handleKeyDown(e) {
        if ((e.key === 'Enter' || e.key === ',') && inputValue.trim()) {
            e.preventDefault();
            const newChip = inputValue.trim().toLowerCase();
            if (!value.includes(newChip)) {
                onChange([...value, newChip]);
            }
            setInputValue('');
        }
        if (e.key === 'Backspace' && !inputValue && value.length > 0) {
            onChange(value.slice(0, -1));
        }
    }

    function removeChip(idx) {
        onChange(value.filter((_, i) => i !== idx));
    }

    return (
        <div className="chips-input-wrapper">
            {value.map((chip, i) => (
                <span key={i} className="chip">
                    {chip}
                    <button type="button" onClick={() => removeChip(i)}>×</button>
                </span>
            ))}
            <input
                value={inputValue}
                onChange={e => setInputValue(e.target.value)}
                onKeyDown={handleKeyDown}
                placeholder={value.length === 0 ? placeholder : ''}
            />
        </div>
    );
}

function FlowModal({ flow, onClose, onSave }) {
    const isEdit = !!flow?.id;
    const [form, setForm] = useState({
        category: flow?.category || '',
        trigger_keywords: flow?.trigger_keywords || [],
        response_text: flow?.response_text || '',
        response_type: flow?.response_type || 'text',
        response_buttons: flow?.response_buttons || [],
        is_active: flow?.is_active ?? true,
        sort_order: flow?.sort_order || 0,
        media_file: null,
    });
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    function setField(key, value) {
        setForm(prev => ({ ...prev, [key]: value }));
    }

    function addButton() {
        if (form.response_buttons.length >= 3) return;
        setField('response_buttons', [
            ...form.response_buttons,
            { id: `btn_${Date.now()}`, title: '' }
        ]);
    }

    function updateButton(idx, title) {
        const btns = [...form.response_buttons];
        btns[idx] = { ...btns[idx], title, id: `btn_${title.toLowerCase().replace(/\s+/g, '_')}` };
        setField('response_buttons', btns);
    }

    function removeButton(idx) {
        setField('response_buttons', form.response_buttons.filter((_, i) => i !== idx));
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setError('');
        setSaving(true);

        const formData = new FormData();
        formData.append('category', form.category);
        formData.append('response_text', form.response_text);
        formData.append('response_type', form.response_type);
        formData.append('is_active', form.is_active ? 1 : 0);
        formData.append('sort_order', form.sort_order);
        formData.append('trigger_keywords', JSON.stringify(form.trigger_keywords));

        if (form.response_type === 'buttons') {
            formData.append('response_buttons', JSON.stringify(form.response_buttons));
        }

        if (form.media_file) {
            formData.append('media_file', form.media_file);
        }

        if (isEdit) {
            // Laravel needs _method=PUT when sending FormData
            formData.append('_method', 'PUT');
        }

        try {
            if (isEdit) {
                await api.post(`/flows/${flow.id}`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
            } else {
                await api.post('/flows', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
            }
            onSave();
        } catch (err) {
            setError(err.response?.data?.message || 'Error al guardar');
        } finally {
            setSaving(false);
        }
    }

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal" onClick={e => e.stopPropagation()}>
                <div className="modal-header">
                    <h2>{isEdit ? 'Editar Flujo' : 'Nuevo Flujo'}</h2>
                    <button className="btn btn-ghost btn-sm" onClick={onClose}><X size={18} /></button>
                </div>
                <form onSubmit={handleSubmit}>
                    <div className="modal-body">
                        {error && (
                            <div style={{ background: 'rgba(239,68,68,0.08)', color: '#dc2626', padding: '10px 14px', borderRadius: 'var(--radius-md)', fontSize: '0.85rem', marginBottom: 16 }}>
                                {error}
                            </div>
                        )}

                        <div className="form-group">
                            <label className="form-label">Categoría</label>
                            <input className="form-input" value={form.category}
                                onChange={e => setField('category', e.target.value)}
                                placeholder="ej: ventas, soporte, info..." required />
                        </div>

                        <div className="form-group">
                            <label className="form-label">Palabras Clave (Enter para agregar)</label>
                            <ChipsInput
                                value={form.trigger_keywords}
                                onChange={v => setField('trigger_keywords', v)}
                                placeholder="Escribe una palabra y presiona Enter..."
                            />
                        </div>

                        <div className="form-group">
                            <label className="form-label">Texto de Respuesta</label>
                            <textarea className="form-input" value={form.response_text}
                                onChange={e => setField('response_text', e.target.value)}
                                placeholder="El mensaje que el bot enviará..." required rows={4} />
                        </div>

                        <div className="form-group">
                            <label className="form-label">Tipo de Respuesta</label>
                            <select className="form-input" value={form.response_type}
                                onChange={e => setField('response_type', e.target.value)}>
                                <option value="text">Texto simple</option>
                                <option value="buttons">Con botones</option>
                                <option value="list">Lista/Menú interactivo</option>
                                <option value="ticket_creation">Generar Ticket (Soporte)</option>
                                <option value="handoff">Escalar a asesor</option>
                            </select>
                        </div>

                        <div className="form-group">
                            <label className="form-label">Archivo Multimedia (Opcional)</label>
                            <input
                                type="file"
                                className="form-input"
                                onChange={e => setField('media_file', e.target.files[0])}
                                accept="image/*,video/*,application/pdf"
                                style={{ padding: '8px' }}
                            />
                            {flow?.media_path && !form.media_file && (
                                <p style={{ fontSize: '0.75rem', marginTop: '4px', opacity: 0.7 }}>
                                    Archivo actual: {flow.media_path.split('/').pop()}
                                </p>
                            )}
                        </div>

                        {form.response_type === 'buttons' && (
                            <div className="form-group">
                                <label className="form-label">Botones (máx. 3)</label>
                                {form.response_buttons.map((btn, i) => (
                                    <div key={i} style={{ display: 'flex', gap: 8, marginBottom: 8 }}>
                                        <input className="form-input" value={btn.title}
                                            onChange={e => updateButton(i, e.target.value)}
                                            placeholder={`Botón ${i + 1}`}
                                            maxLength={20} style={{ flex: 1 }} />
                                        <button type="button" className="btn btn-ghost btn-sm"
                                            onClick={() => removeButton(i)}>
                                            <X size={14} />
                                        </button>
                                    </div>
                                ))}
                                {form.response_buttons.length < 3 && (
                                    <button type="button" className="btn btn-secondary btn-sm" onClick={addButton}>
                                        <Plus size={14} /> Agregar botón
                                    </button>
                                )}
                            </div>
                        )}

                        <div style={{ display: 'flex', gap: 16 }}>
                            <div className="form-group" style={{ flex: 1 }}>
                                <label className="form-label">Orden</label>
                                <input className="form-input" type="number" value={form.sort_order}
                                    onChange={e => setField('sort_order', parseInt(e.target.value) || 0)} />
                            </div>
                            <div className="form-group" style={{ display: 'flex', alignItems: 'flex-end', paddingBottom: 4 }}>
                                <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', fontFamily: 'var(--font-display)', fontSize: '0.8rem', fontWeight: 600 }}>
                                    <input type="checkbox" checked={form.is_active}
                                        onChange={e => setField('is_active', e.target.checked)}
                                        style={{ width: 18, height: 18 }} />
                                    Activo
                                </label>
                            </div>
                        </div>
                    </div>
                    <div className="modal-footer">
                        <button type="button" className="btn btn-secondary" onClick={onClose}>Cancelar</button>
                        <button type="submit" className="btn btn-primary" disabled={saving}>
                            {saving ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Crear Flujo')}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

export default function FlowEditor() {
    const [flows, setFlows] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editFlow, setEditFlow] = useState(null);

    useEffect(() => { loadFlows(); }, []);

    async function loadFlows() {
        try {
            const res = await api.get('/flows');
            setFlows(res.data);
        } catch { } finally { setLoading(false); }
    }

    function openNew() {
        setEditFlow(null);
        setShowModal(true);
    }

    function openEdit(flow) {
        setEditFlow(flow);
        setShowModal(true);
    }

    async function deleteFlow(flow) {
        if (!confirm('¿Eliminar este flujo?')) return;
        try {
            await api.delete(`/flows/${flow.id}`);
            loadFlows();
        } catch { }
    }

    async function toggleActive(flow) {
        try {
            await api.post(`/flows/${flow.id}/toggle`);
            loadFlows();
        } catch { }
    }

    function handleSave() {
        setShowModal(false);
        loadFlows();
    }

    const typeLabels = { text: 'Texto', buttons: 'Botones', handoff: 'Escalar', list: 'Lista', ticket_creation: 'Ticket' };

    if (loading) return <div className="loading-spinner"><div className="spinner"></div></div>;

    return (
        <>
            <div className="page-header" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <div>
                    <h1>Flujos del Bot</h1>
                    <p>Gestiona las respuestas automáticas sin tocar código</p>
                </div>
                <button className="btn btn-primary" onClick={openNew}>
                    <Plus size={16} /> Nuevo Flujo
                </button>
            </div>

            <div className="page-body">
                {flows.length === 0 ? (
                    <div className="empty-state">
                        <GitBranch />
                        <p>No hay flujos creados</p>
                        <button className="btn btn-primary btn-sm mt-4" onClick={openNew}>
                            Crear el primero
                        </button>
                    </div>
                ) : (
                    <div className="flows-grid">
                        {flows.map(flow => (
                            <div key={flow.id} className="flow-card" style={{ opacity: flow.is_active ? 1 : 0.5 }}>
                                <div className="flow-card-header">
                                    <span className="flow-category">{flow.category}</span>
                                    <div className="flow-card-actions">
                                        <button className="btn btn-ghost btn-sm" onClick={() => openEdit(flow)}>
                                            <Edit2 size={14} />
                                        </button>
                                        <button className="btn btn-ghost btn-sm" onClick={() => deleteFlow(flow)}
                                            style={{ color: 'var(--color-danger)' }}>
                                            <Trash2 size={14} />
                                        </button>
                                    </div>
                                </div>

                                <div className="flow-keywords">
                                    {flow.trigger_keywords.map((kw, i) => (
                                        <span key={i} className="keyword-chip">{kw}</span>
                                    ))}
                                </div>

                                <div className="flow-response-preview">
                                    {flow.response_text}
                                </div>

                                <div className="flow-card-footer">
                                    <span className={`flow-type-badge ${flow.response_type}`}>
                                        {typeLabels[flow.response_type] || flow.response_type}
                                    </span>
                                    <label className="toggle-switch" style={{ transform: 'scale(0.8)' }}>
                                        <input type="checkbox" checked={flow.is_active}
                                            onChange={() => toggleActive(flow)} />
                                        <span className="toggle-slider"
                                            style={{ background: flow.is_active ? 'var(--color-success)' : 'var(--bg-secondary)' }}></span>
                                    </label>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {showModal && (
                <FlowModal
                    flow={editFlow}
                    onClose={() => setShowModal(false)}
                    onSave={handleSave}
                />
            )}
        </>
    );
}
