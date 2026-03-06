import React, { useState, useEffect } from 'react';
import api from '../api';
import {
    Plus, Edit2, Trash2, X, GitBranch, ChevronDown, ChevronRight,
    MessageSquare, ListOrdered, Zap, ArrowRight, Copy, Shield
} from 'lucide-react';

/* ─── Chips Input for keywords ─── */
function ChipsInput({ value = [], onChange, placeholder }) {
    const [val, setVal] = useState('');
    function handleKey(e) {
        if ((e.key === 'Enter' || e.key === ',') && val.trim()) {
            e.preventDefault();
            const chip = val.trim().toLowerCase();
            if (!value.includes(chip)) onChange([...value, chip]);
            setVal('');
        }
        if (e.key === 'Backspace' && !val && value.length) onChange(value.slice(0, -1));
    }
    return (
        <div className="chips-input-wrapper">
            {value.map((c, i) => (
                <span key={i} className="chip">{c}<button type="button" onClick={() => onChange(value.filter((_, j) => j !== i))}>×</button></span>
            ))}
            <input value={val} onChange={e => setVal(e.target.value)} onKeyDown={handleKey} placeholder={value.length === 0 ? placeholder : ''} />
        </div>
    );
}

/* ─── Constants ─── */
const ACTION_LABELS = {
    '': 'Ninguna',
    validate_client: '🔐 Validar Cliente',
    check_coverage: '📍 Verificar Cobertura',
    show_plans: '📋 Mostrar Planes',
    show_plan_categories: '📂 Categorías de Planes',
    escalate_agent: '👨‍💻 Escalar a Asesor',
    create_lead: '📡 Crear Lead',
    notify_telegram: '📲 Notificar Telegram',
    close_conversation: '✅ Cerrar Conversación',
};
const RESPONSE_TYPES = {
    text: '💬 Texto',
    buttons: '🔘 Botones (máx 3)',
    list: '📋 Lista (máx 10)',
    input: '✏️ Esperar Texto',
    action_only: '⚡ Solo Acción',
};
const VALIDATION_TYPES = {
    '': 'Sin validación',
    none: 'Sin validación',
    name: 'Nombre (mín 3 chars)',
    zip_code: 'Código Postal (5 dígitos)',
    account_number: 'Número de Cuenta',
    phone: 'Teléfono (10 dígitos)',
    text: 'Texto libre',
};

/* ─── Step Modal ─── */
function StepModal({ step, flow, allFlows, onClose, onSave }) {
    const isEdit = !!step?.id;
    const existingKeys = (flow.steps || []).filter(s => s.id !== step?.id).map(s => s.step_key);

    const [form, setForm] = useState({
        step_key: step?.step_key || '',
        message_text: step?.message_text || '',
        response_type: step?.response_type || 'text',
        options: step?.options || [],
        action_type: step?.action_type || '',
        action_config: step?.action_config || {},
        next_step_default: step?.next_step_default || '',
        input_validation: step?.input_validation || '',
        retry_limit: step?.retry_limit || 0,
        is_entry_point: step?.is_entry_point || false,
        sort_order: step?.sort_order || ((flow.steps?.length || 0) + 1),
        media_file: null,
        remove_media: false,
    });
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    function set(k, v) { setForm(prev => ({ ...prev, [k]: v })); }

    function addOption() {
        set('options', [...form.options, { id: `opt_${Date.now()}`, title: '', description: '', next_step: '', next_flow: '', action: '' }]);
    }
    function updateOption(i, field, value) {
        const opts = [...form.options];
        opts[i] = { ...opts[i], [field]: value };
        set('options', opts);
    }
    function removeOption(i) { set('options', form.options.filter((_, j) => j !== i)); }

    async function handleSubmit(e) {
        e.preventDefault();
        if (!form.step_key.match(/^[a-z0-9_]+$/)) { setError('El identificador solo puede tener letras minúsculas, números y guión bajo'); return; }
        setError(''); setSaving(true);

        const formData = new FormData();
        formData.append('step_key', form.step_key);
        formData.append('message_text', form.message_text);
        formData.append('response_type', form.response_type);
        formData.append('options', JSON.stringify(form.options));
        if (form.action_type) formData.append('action_type', form.action_type);
        if (form.action_config) formData.append('action_config', JSON.stringify(form.action_config));
        if (form.next_step_default) formData.append('next_step_default', form.next_step_default);
        if (form.input_validation) formData.append('input_validation', form.input_validation);
        formData.append('retry_limit', form.retry_limit);
        formData.append('is_entry_point', form.is_entry_point ? 1 : 0);
        formData.append('sort_order', form.sort_order);

        if (form.media_file) {
            formData.append('media_file', form.media_file);
        }
        if (form.remove_media) {
            formData.append('remove_media', 'true');
        }

        try {
            const config = { headers: { 'Content-Type': 'multipart/form-data' } };
            if (isEdit) {
                formData.append('_method', 'PUT');
                await api.post(`/flow-steps/${step.id}`, formData, config);
            } else {
                await api.post(`/flows/${flow.id}/steps`, formData, config);
            }
            onSave();
        } catch (err) { setError(err.response?.data?.message || 'Error al guardar'); }
        finally { setSaving(false); }
    }

    const maxOptions = form.response_type === 'list' ? 10 : form.response_type === 'buttons' ? 3 : 0;
    const stepKeys = (flow.steps || []).map(s => s.step_key);

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal" onClick={e => e.stopPropagation()} style={{ maxWidth: 680, maxHeight: '90vh', overflow: 'auto' }}>
                <div className="modal-header">
                    <h2>{isEdit ? 'Editar Paso' : 'Nuevo Paso'}</h2>
                    <button className="btn-icon" onClick={onClose}><X size={18} /></button>
                </div>
                <form onSubmit={handleSubmit}>
                    <div className="modal-body">
                        {error && <div style={{ background: '#FEF2F2', color: '#DC2626', padding: '10px 14px', borderRadius: 8, fontSize: '0.85rem', marginBottom: 16, border: '1px solid #FECACA' }}>{error}</div>}

                        {/* Row 1: Key + Entry + Sort */}
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr auto auto', gap: 12, marginBottom: 16 }}>
                            <div className="form-group" style={{ marginBottom: 0 }}>
                                <label className="form-label">Identificador del Paso</label>
                                <input className="form-input" value={form.step_key} onChange={e => set('step_key', e.target.value.toLowerCase().replace(/[^a-z0-9_]/g, ''))} placeholder="ej: saludo, pedir_nombre" required />
                                <span style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>Solo letras, números y _</span>
                            </div>
                            <div className="form-group" style={{ marginBottom: 0 }}>
                                <label className="form-label">Orden</label>
                                <input className="form-input" type="number" value={form.sort_order} onChange={e => set('sort_order', parseInt(e.target.value) || 0)} style={{ width: 60 }} />
                            </div>
                            <div className="form-group" style={{ display: 'flex', alignItems: 'flex-end', marginBottom: 0, paddingBottom: 6 }}>
                                <label style={{ display: 'flex', alignItems: 'center', gap: 6, cursor: 'pointer', whiteSpace: 'nowrap' }}>
                                    <input type="checkbox" checked={form.is_entry_point} onChange={e => set('is_entry_point', e.target.checked)} style={{ accentColor: 'var(--color-primary)' }} />
                                    <span style={{ fontSize: '0.8rem', fontWeight: 600 }}>Paso Inicial</span>
                                </label>
                            </div>
                        </div>

                        {/* Message */}
                        <div className="form-group">
                            <label className="form-label">Mensaje del Bot</label>
                            <textarea className="form-input" value={form.message_text} onChange={e => set('message_text', e.target.value)} rows={4} placeholder="El mensaje que el bot enviará al usuario...&#10;Usa {{variable}} para datos dinámicos" required />
                            <span style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>Variables: {'{{customer_name}}'} {'{{zip_code}}'} {'{{plan_name}}'} {'{{coverage_zones}}'}</span>
                        </div>

                        {/* Media Attachment */}
                        <div className="form-group" style={{ background: 'var(--bg-app)', padding: 12, borderRadius: 8, border: '1px solid var(--border)' }}>
                            <label className="form-label" style={{ display: 'block', marginBottom: 8 }}>Archivo Adjunto (Opcional)</label>
                            {step?.media_path && !form.remove_media ? (
                                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                    {step.media_type === 'image' ? (
                                        <img src={step.media_path} alt="adjunto" style={{ width: 40, height: 40, objectFit: 'cover', borderRadius: 4 }} />
                                    ) : (
                                        <div style={{ padding: '8px 12px', background: 'white', borderRadius: 4, fontSize: '0.8rem', fontWeight: 600 }}>PDF / Doc</div>
                                    )}
                                    <button type="button" className="btn btn-ghost btn-sm" style={{ color: 'var(--color-danger)' }} onClick={() => set('remove_media', true)}>Quitar archivo</button>
                                </div>
                            ) : (
                                <div>
                                    <input type="file" className="form-input" accept="image/*,.pdf" onChange={e => { set('media_file', e.target.files[0]); set('remove_media', false); }} />
                                    <span style={{ fontSize: '0.7rem', color: 'var(--text-muted)', display: 'block', marginTop: 4 }}>Puedes subir imágenes o PDFs. Max 10MB.</span>
                                </div>
                            )}
                        </div>

                        {/* Response type + Action */}
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 16 }}>
                            <div className="form-group" style={{ marginBottom: 0 }}>
                                <label className="form-label">Tipo de Respuesta</label>
                                <select className="form-input" value={form.response_type} onChange={e => set('response_type', e.target.value)}>
                                    {Object.entries(RESPONSE_TYPES).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select>
                            </div>
                            <div className="form-group" style={{ marginBottom: 0 }}>
                                <label className="form-label">Acción Automática</label>
                                <select className="form-input" value={form.action_type} onChange={e => set('action_type', e.target.value)}>
                                    {Object.entries(ACTION_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select>
                            </div>
                        </div>

                        {/* Input validation + retry */}
                        {(form.response_type === 'input' || form.response_type === 'text') && (
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr auto', gap: 12, marginBottom: 16 }}>
                                <div className="form-group" style={{ marginBottom: 0 }}>
                                    <label className="form-label">Validación de Entrada</label>
                                    <select className="form-input" value={form.input_validation} onChange={e => set('input_validation', e.target.value)}>
                                        {Object.entries(VALIDATION_TYPES).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                    </select>
                                </div>
                                <div className="form-group" style={{ marginBottom: 0 }}>
                                    <label className="form-label">Reintentos</label>
                                    <input className="form-input" type="number" value={form.retry_limit} onChange={e => set('retry_limit', parseInt(e.target.value) || 0)} min={0} max={10} style={{ width: 70 }} />
                                </div>
                            </div>
                        )}

                        {/* Next step default */}
                        <div className="form-group">
                            <label className="form-label">Siguiente Paso (por defecto)</label>
                            <select className="form-input" value={form.next_step_default} onChange={e => set('next_step_default', e.target.value)}>
                                <option value="">— Ninguno —</option>
                                {stepKeys.filter(k => k !== form.step_key).map(k => <option key={k} value={k}>{k}</option>)}
                            </select>
                            <span style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>Se usa cuando el usuario escribe texto libre (sin botones/lista)</span>
                        </div>

                        {/* Options / Buttons */}
                        {(form.response_type === 'buttons' || form.response_type === 'list') && (
                            <div className="form-group">
                                <label className="form-label">Opciones del Usuario {form.response_type === 'buttons' ? '(máx 3)' : '(máx 10)'}</label>
                                {form.options.map((opt, i) => (
                                    <div key={i} style={{ background: 'var(--bg-app)', borderRadius: 8, padding: 12, marginBottom: 8 }}>
                                        <div style={{ display: 'grid', gridTemplateColumns: '1fr auto', gap: 8, marginBottom: 8 }}>
                                            <input className="form-input" value={opt.title} onChange={e => updateOption(i, 'title', e.target.value)} placeholder="Título del botón" maxLength={form.response_type === 'buttons' ? 20 : 24} />
                                            <button type="button" className="btn-icon" onClick={() => removeOption(i)} style={{ color: 'var(--color-danger)' }}><Trash2 size={14} /></button>
                                        </div>
                                        {form.response_type === 'list' && (
                                            <input className="form-input" value={opt.description || ''} onChange={e => updateOption(i, 'description', e.target.value)} placeholder="Descripción (opcional)" maxLength={72} style={{ marginBottom: 8, fontSize: '0.8rem' }} />
                                        )}
                                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8 }}>
                                            <div>
                                                <label style={{ fontSize: '0.7rem', color: 'var(--text-muted)', display: 'block', marginBottom: 2 }}>→ Ir a paso:</label>
                                                <select className="form-input" style={{ fontSize: '0.8rem' }} value={opt.next_step || ''} onChange={e => updateOption(i, 'next_step', e.target.value)}>
                                                    <option value="">— Mismo flujo —</option>
                                                    {stepKeys.filter(k => k !== form.step_key).map(k => <option key={k} value={k}>{k}</option>)}
                                                </select>
                                            </div>
                                            <div>
                                                <label style={{ fontSize: '0.7rem', color: 'var(--text-muted)', display: 'block', marginBottom: 2 }}>→ Ir a flujo:</label>
                                                <select className="form-input" style={{ fontSize: '0.8rem' }} value={opt.next_flow || ''} onChange={e => updateOption(i, 'next_flow', e.target.value)}>
                                                    <option value="">— Ninguno —</option>
                                                    {allFlows.filter(f => f.id !== flow.id && f.flow_type === 'main').map(f => <option key={f.slug} value={f.slug}>{f.category}</option>)}
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                {form.options.length < maxOptions && (
                                    <button type="button" className="btn btn-secondary btn-sm" onClick={addOption}><Plus size={14} /> Agregar Opción</button>
                                )}
                            </div>
                        )}

                        {/* Telegram config */}
                        {form.action_type === 'notify_telegram' && (
                            <div className="form-group">
                                <label className="form-label">Mensaje Telegram</label>
                                <textarea className="form-input" value={form.action_config?.message || ''} onChange={e => set('action_config', { ...form.action_config, message: e.target.value })} rows={3} placeholder="Mensaje a enviar. Usa {{phone}}, {{contact_name}}, etc." />
                            </div>
                        )}
                        {form.action_type === 'show_plans' && (
                            <div className="form-group">
                                <label className="form-label">Categoría de Planes</label>
                                <input className="form-input" value={form.action_config?.category || ''} onChange={e => set('action_config', { ...form.action_config, category: e.target.value.toUpperCase() })} placeholder="HOGAR, NEGOCIO, PYME, DEDICADO (vacío = detectar automáticamente)" />
                            </div>
                        )}
                        {form.action_type === 'escalate_agent' && (
                            <div className="form-group">
                                <label className="form-label">Razón de Escalamiento</label>
                                <input className="form-input" value={form.action_config?.reason || ''} onChange={e => set('action_config', { ...form.action_config, reason: e.target.value })} placeholder="ej: Solicita asesor de ventas" />
                            </div>
                        )}
                    </div>
                    <div className="modal-footer">
                        <button type="button" className="btn btn-ghost" onClick={onClose}>Cancelar</button>
                        <button type="submit" className="btn btn-primary" disabled={saving}>{saving ? 'Guardando...' : isEdit ? 'Actualizar' : 'Crear Paso'}</button>
                    </div>
                </form>
            </div>
        </div>
    );
}

/* ─── Flow Modal (create/edit flow metadata) ─── */
function FlowModal({ flow, onClose, onSave }) {
    const isEdit = !!flow?.id;
    const [form, setForm] = useState({
        category: flow?.category || '',
        slug: flow?.slug || '',
        flow_type: flow?.flow_type || 'keyword',
        description: flow?.description || '',
        trigger_keywords: flow?.trigger_keywords || [],
        response_text: flow?.response_text || '',
        response_type: flow?.response_type || 'text',
        response_buttons: flow?.response_buttons || [],
        is_active: flow?.is_active ?? true,
        sort_order: flow?.sort_order || 0,
        flow_priority: flow?.flow_priority || (flow?.flow_type === 'main' ? 100 : 10),
    });
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    function set(k, v) { setForm(prev => ({ ...prev, [k]: v })); }

    async function handleSubmit(e) {
        e.preventDefault();
        setError(''); setSaving(true);
        const payload = { ...form, trigger_keywords: form.trigger_keywords };
        if (!payload.slug && payload.category) {
            payload.slug = payload.category.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/_+$/, '');
        }
        try {
            if (isEdit) await api.put(`/flows/${flow.id}`, payload);
            else await api.post('/flows', payload);
            onSave();
        } catch (err) { setError(err.response?.data?.message || 'Error al guardar'); }
        finally { setSaving(false); }
    }

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal" onClick={e => e.stopPropagation()} style={{ maxWidth: 520 }}>
                <div className="modal-header">
                    <h2>{isEdit ? 'Editar Flujo' : 'Nuevo Flujo'}</h2>
                    <button className="btn-icon" onClick={onClose}><X size={18} /></button>
                </div>
                <form onSubmit={handleSubmit}>
                    <div className="modal-body">
                        {error && <div style={{ background: '#FEF2F2', color: '#DC2626', padding: '10px 14px', borderRadius: 8, fontSize: '0.85rem', marginBottom: 16 }}>{error}</div>}
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                            <div className="form-group">
                                <label className="form-label">Nombre del Flujo</label>
                                <input className="form-input" value={form.category} onChange={e => set('category', e.target.value)} placeholder="Ej: Bienvenida" required />
                            </div>
                            <div className="form-group">
                                <label className="form-label">Tipo</label>
                                <select className="form-input" value={form.flow_type} onChange={e => { set('flow_type', e.target.value); set('flow_priority', e.target.value === 'main' ? 100 : 10); }}>
                                    <option value="main">🔄 Flujo Principal</option>
                                    <option value="keyword">⚡ Respuesta Rápida</option>
                                </select>
                            </div>
                        </div>
                        <div className="form-group">
                            <label className="form-label">Descripción (opcional)</label>
                            <input className="form-input" value={form.description} onChange={e => set('description', e.target.value)} placeholder="¿Qué hace este flujo?" />
                        </div>
                        <div className="form-group">
                            <label className="form-label">Palabras Clave (Enter para agregar)</label>
                            <ChipsInput value={form.trigger_keywords} onChange={v => set('trigger_keywords', v)} placeholder="Palabras que activan este flujo..." />
                        </div>

                        {form.flow_type === 'keyword' && (
                            <div className="form-group">
                                <label className="form-label">Respuesta del Bot</label>
                                <textarea className="form-input" value={form.response_text} onChange={e => set('response_text', e.target.value)} rows={4} placeholder="Mensaje de respuesta rápida..." />
                            </div>
                        )}

                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr auto', gap: 12, alignItems: 'end' }}>
                            <div className="form-group" style={{ marginBottom: 0 }}>
                                <label className="form-label">Orden</label>
                                <input className="form-input" type="number" value={form.sort_order} onChange={e => set('sort_order', parseInt(e.target.value) || 0)} />
                            </div>
                            <div className="form-group" style={{ marginBottom: 0 }}>
                                <label className="form-label">Prioridad</label>
                                <input className="form-input" type="number" value={form.flow_priority} onChange={e => set('flow_priority', parseInt(e.target.value) || 0)} />
                            </div>
                            <label style={{ display: 'flex', alignItems: 'center', gap: 6, cursor: 'pointer', paddingBottom: 12 }}>
                                <input type="checkbox" checked={form.is_active} onChange={e => set('is_active', e.target.checked)} style={{ accentColor: 'var(--color-primary)' }} />
                                <span style={{ fontSize: '0.8rem', fontWeight: 600 }}>Activo</span>
                            </label>
                        </div>
                    </div>
                    <div className="modal-footer">
                        <button type="button" className="btn btn-ghost" onClick={onClose}>Cancelar</button>
                        <button type="submit" className="btn btn-primary" disabled={saving}>{saving ? 'Guardando...' : isEdit ? 'Actualizar' : 'Crear Flujo'}</button>
                    </div>
                </form>
            </div>
        </div>
    );
}

/* ─── Step Card (inside expanded flow) ─── */
function StepCard({ step, flow, allFlows, onEdit, onDelete }) {
    const typeBadge = RESPONSE_TYPES[step.response_type] || step.response_type;
    const actionBadge = step.action_type ? (ACTION_LABELS[step.action_type] || step.action_type) : null;
    const preview = step.message_text.length > 120 ? step.message_text.slice(0, 120) + '...' : step.message_text;

    return (
        <div style={{ background: 'white', border: '1px solid var(--border)', borderRadius: 10, padding: 16, position: 'relative' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 8 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
                    {step.is_entry_point && <span style={{ background: '#EEF2FF', color: '#4338CA', padding: '2px 8px', borderRadius: 6, fontSize: '0.65rem', fontWeight: 700 }}>INICIO</span>}
                    <span style={{ fontFamily: 'monospace', fontSize: '0.8rem', fontWeight: 600, color: 'var(--color-primary)' }}>{step.step_key}</span>
                    <span style={{ background: 'var(--bg-app)', padding: '2px 8px', borderRadius: 6, fontSize: '0.65rem', fontWeight: 600, color: 'var(--text-secondary)' }}>{typeBadge}</span>
                    {actionBadge && <span style={{ background: '#FFF7ED', color: '#C2410C', padding: '2px 8px', borderRadius: 6, fontSize: '0.65rem', fontWeight: 600 }}>{actionBadge}</span>}
                </div>
                <div style={{ display: 'flex', gap: 4 }}>
                    <button className="btn-icon" onClick={() => onEdit(step)}><Edit2 size={13} /></button>
                    <button className="btn-icon" style={{ color: 'var(--color-danger)' }} onClick={() => onDelete(step)}><Trash2 size={13} /></button>
                </div>
            </div>
            <p style={{ fontSize: '0.8rem', color: 'var(--text-secondary)', margin: '0 0 8px', whiteSpace: 'pre-line', lineHeight: 1.4 }}>{preview}</p>
            {step.options && step.options.length > 0 && (
                <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                    {step.options.map((opt, i) => (
                        <span key={i} style={{ background: 'var(--bg-app)', border: '1px solid var(--border)', padding: '3px 10px', borderRadius: 6, fontSize: '0.7rem', fontWeight: 600, display: 'flex', alignItems: 'center', gap: 4 }}>
                            {opt.title}
                            {(opt.next_step || opt.next_flow) && <ArrowRight size={10} style={{ color: 'var(--color-primary)' }} />}
                            {opt.next_step && <span style={{ color: 'var(--color-primary)', fontFamily: 'monospace' }}>{opt.next_step}</span>}
                            {opt.next_flow && <span style={{ color: '#7C3AED', fontFamily: 'monospace' }}>{opt.next_flow}</span>}
                        </span>
                    ))}
                </div>
            )}
            {step.next_step_default && !step.options?.length && (
                <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)', display: 'flex', alignItems: 'center', gap: 4 }}>
                    <ArrowRight size={10} /> Siguiente: <span style={{ fontFamily: 'monospace', color: 'var(--color-primary)' }}>{step.next_step_default}</span>
                </div>
            )}
        </div>
    );
}

/* ─── Flow Card (expandable) ─── */
function FlowCard({ flow, allFlows, onUpdate, onToggle, onDelete }) {
    const [expanded, setExpanded] = useState(false);
    const [stepModal, setStepModal] = useState(null); // null | {} (new) | step (edit)
    const [editFlow, setEditFlow] = useState(false);
    const stepCount = flow.steps?.length || 0;
    const isMain = flow.flow_type === 'main';

    async function deleteStep(step) {
        if (!confirm(`¿Eliminar paso "${step.step_key}"?`)) return;
        try { await api.delete(`/flow-steps/${step.id}`); onUpdate(); } catch { }
    }

    return (
        <div style={{ background: 'white', border: '1px solid var(--border)', borderRadius: 12, overflow: 'hidden', opacity: flow.is_active ? 1 : 0.5 }}>
            <div style={{ padding: '14px 20px', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }} onClick={() => isMain && setExpanded(!expanded)}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                    {isMain ? (expanded ? <ChevronDown size={16} /> : <ChevronRight size={16} />) : <Zap size={14} style={{ color: '#F59E0B' }} />}
                    <div>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                            <span style={{ fontWeight: 700, fontSize: '0.9rem' }}>{flow.category}</span>
                            {flow.is_system_flow && <Shield size={12} style={{ color: 'var(--text-muted)' }} title="Flujo del Sistema" />}
                        </div>
                        {flow.description && <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{flow.description}</span>}
                    </div>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }} onClick={e => e.stopPropagation()}>
                    {isMain && <span style={{ fontSize: '0.7rem', color: 'var(--text-muted)', fontWeight: 600 }}>{stepCount} pasos</span>}
                    {flow.trigger_keywords?.length > 0 && (
                        <div style={{ display: 'flex', gap: 4, maxWidth: 200, overflow: 'hidden' }}>
                            {flow.trigger_keywords.slice(0, 3).map((kw, i) => (
                                <span key={i} className="keyword-chip" style={{ fontSize: '0.65rem' }}>{kw}</span>
                            ))}
                            {flow.trigger_keywords.length > 3 && <span style={{ fontSize: '0.65rem', color: 'var(--text-muted)' }}>+{flow.trigger_keywords.length - 3}</span>}
                        </div>
                    )}
                    <div className={`toggle-switch ${flow.is_active ? 'active' : ''}`} onClick={() => onToggle(flow)} style={{ flexShrink: 0 }}>
                        <div className="toggle-slider"></div>
                    </div>
                    <button className="btn-icon" onClick={() => setEditFlow(true)}><Edit2 size={14} /></button>
                    {!flow.is_system_flow && <button className="btn-icon" style={{ color: 'var(--color-danger)' }} onClick={() => onDelete(flow)}><Trash2 size={14} /></button>}
                </div>
            </div>

            {/* Keyword response preview (non-main) */}
            {!isMain && flow.response_text && (
                <div style={{ padding: '0 20px 14px', fontSize: '0.8rem', color: 'var(--text-secondary)' }}>
                    {flow.response_text.length > 100 ? flow.response_text.slice(0, 100) + '...' : flow.response_text}
                </div>
            )}

            {/* Expanded steps (main flows) */}
            {isMain && expanded && (
                <div style={{ padding: '0 20px 16px', borderTop: '1px solid var(--border)' }}>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 8, paddingTop: 16 }}>
                        {(flow.steps || []).map(step => (
                            <StepCard key={step.id} step={step} flow={flow} allFlows={allFlows} onEdit={s => setStepModal(s)} onDelete={deleteStep} />
                        ))}
                        {stepCount === 0 && (
                            <div style={{ textAlign: 'center', padding: 24, color: 'var(--text-muted)', fontSize: '0.85rem' }}>
                                Sin pasos configurados. Agrega el primer paso.
                            </div>
                        )}
                        <button className="btn btn-secondary btn-sm" style={{ alignSelf: 'flex-start' }} onClick={() => setStepModal({})}>
                            <Plus size={14} /> Agregar Paso
                        </button>
                    </div>
                </div>
            )}

            {stepModal !== null && <StepModal step={stepModal.id ? stepModal : null} flow={flow} allFlows={allFlows} onClose={() => setStepModal(null)} onSave={() => { setStepModal(null); onUpdate(); }} />}
            {editFlow && <FlowModal flow={flow} onClose={() => setEditFlow(false)} onSave={() => { setEditFlow(false); onUpdate(); }} />}
        </div>
    );
}

/* ═══════════════════════════════════════
   MAIN FLOW EDITOR PAGE
   ═══════════════════════════════════════ */
export default function FlowEditor() {
    const [flows, setFlows] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showNewFlow, setShowNewFlow] = useState(false);

    useEffect(() => { loadFlows(); }, []);

    async function loadFlows() {
        try { const res = await api.get('/flows'); setFlows(res.data); }
        catch { } finally { setLoading(false); }
    }

    async function deleteFlow(flow) {
        if (flow.is_system_flow) { alert('No se puede eliminar un flujo del sistema.'); return; }
        if (!confirm(`¿Eliminar flujo "${flow.category}"?`)) return;
        try { await api.delete(`/flows/${flow.id}`); loadFlows(); } catch { }
    }

    async function toggleFlow(flow) {
        try { await api.post(`/flows/${flow.id}/toggle`); loadFlows(); } catch { }
    }

    const mainFlows = flows.filter(f => f.flow_type === 'main');
    const kwFlows = flows.filter(f => f.flow_type === 'keyword');

    if (loading) return <div className="loading-spinner"><div className="spinner"></div></div>;

    return (
        <div className="fade-in">
            <div className="page-header" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <div>
                    <h1>Flujos del Bot</h1>
                    <p>Diseña las conversaciones del chatbot de forma visual</p>
                </div>
                <button className="btn btn-primary" onClick={() => setShowNewFlow(true)}>
                    <Plus size={16} /> Nuevo Flujo
                </button>
            </div>

            <div style={{ padding: '0 32px 32px' }}>
                {/* Main Flows Section */}
                <div style={{ marginBottom: 32 }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 16 }}>
                        <GitBranch size={18} style={{ color: 'var(--color-primary)' }} />
                        <h2 style={{ fontSize: '1rem', fontWeight: 700, margin: 0 }}>Flujos Principales</h2>
                        <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)', background: 'var(--bg-app)', padding: '2px 8px', borderRadius: 10 }}>{mainFlows.length}</span>
                    </div>
                    <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginBottom: 16, marginTop: 0 }}>
                        Flujos conversacionales con múltiples pasos. Haz clic para expandir y editar los pasos.
                    </p>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                        {mainFlows.length === 0 ? (
                            <div style={{ textAlign: 'center', padding: 40, color: 'var(--text-muted)' }}>
                                <GitBranch size={32} style={{ marginBottom: 8 }} />
                                <p>No hay flujos principales</p>
                            </div>
                        ) : mainFlows.map(flow => (
                            <FlowCard key={flow.id} flow={flow} allFlows={flows} onUpdate={loadFlows} onToggle={toggleFlow} onDelete={deleteFlow} />
                        ))}
                    </div>
                </div>

                {/* Keyword Flows Section */}
                <div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 16 }}>
                        <Zap size={18} style={{ color: '#F59E0B' }} />
                        <h2 style={{ fontSize: '1rem', fontWeight: 700, margin: 0 }}>Respuestas Automáticas</h2>
                        <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)', background: 'var(--bg-app)', padding: '2px 8px', borderRadius: 10 }}>{kwFlows.length}</span>
                    </div>
                    <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginBottom: 16, marginTop: 0 }}>
                        Respuestas rápidas activadas por palabras clave. No tienen pasos, solo respuesta directa.
                    </p>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                        {kwFlows.length === 0 ? (
                            <div style={{ textAlign: 'center', padding: 40, color: 'var(--text-muted)' }}>
                                <Zap size={32} style={{ marginBottom: 8 }} />
                                <p>No hay respuestas automáticas</p>
                            </div>
                        ) : kwFlows.map(flow => (
                            <FlowCard key={flow.id} flow={flow} allFlows={flows} onUpdate={loadFlows} onToggle={toggleFlow} onDelete={deleteFlow} />
                        ))}
                    </div>
                </div>
            </div>

            {showNewFlow && <FlowModal flow={null} onClose={() => setShowNewFlow(false)} onSave={() => { setShowNewFlow(false); loadFlows(); }} />}
        </div>
    );
}
