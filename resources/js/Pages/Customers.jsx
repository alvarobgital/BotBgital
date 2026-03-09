import React, { useState, useEffect } from 'react';
import api from '../api';
import { Users, Plus, Search, Trash2, Edit2, X, User, ChevronDown, ChevronUp, Package } from 'lucide-react';

function ServiceModal({ customer, service, coverageAreas = [], plans = [], onClose, onSave }) {
    const isEdit = !!service;
    const [form, setForm] = useState({
        account_number: service?.account_number || '',
        plan_name: service?.plan_name || '',
        label: service?.label || '',
        address: service?.address || '',
        zip_code: '',
        neighborhood: '',
    });
    const uniqueZips = [...new Set(coverageAreas.map(c => c.zip_code))].filter(Boolean);
    const availableNeighborhoods = form.zip_code
        ? coverageAreas.filter(c => c.zip_code === form.zip_code).map(c => c.neighborhood)
        : [];

    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true); setError('');
        try {
            if (isEdit) {
                await api.put(`/customer-services/${service.id}`, {
                    account_number: form.account_number,
                    plan_name: form.plan_name,
                    label: form.label,
                    address: form.address
                });
            } else {
                await api.post(`/customers/${customer.id}/services`, {
                    account_number: form.account_number,
                    plan_name: form.plan_name,
                    label: form.label,
                    address: form.address
                });
            }
            onSave();
        } catch (err) {
            setError(err.response?.data?.message || 'Error al guardar');
        } finally { setSaving(false); }
    }

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal" onClick={e => e.stopPropagation()} style={{ maxWidth: 650 }}>
                <div className="modal-header">
                    <h2>{isEdit ? 'Editar Servicio' : 'Agregar Servicio'}</h2>
                    <button className="btn-icon" onClick={onClose}><X size={18} /></button>
                </div>
                <form onSubmit={handleSubmit}>
                    <div className="modal-body">
                        {error && (
                            <div style={{ background: '#FEF2F2', color: '#DC2626', padding: '10px 14px', borderRadius: 8, fontSize: '0.85rem', marginBottom: 16, border: '1px solid #FECACA' }}>
                                {error}
                            </div>
                        )}
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                            <div className="form-group">
                                <label className="form-label">Número de Cuenta</label>
                                <input className="form-input" value={form.account_number} onChange={e => setForm({ ...form, account_number: e.target.value })} required placeholder="Ej: BG-100" />
                            </div>
                            <div className="form-group">
                                <label className="form-label">Plan del Servicio</label>
                                <select className="form-input" value={form.plan_name} onChange={e => setForm({ ...form, plan_name: e.target.value })}>
                                    <option value="">-- Seleccionar Plan --</option>
                                    {plans.map(p => (
                                        <option key={p.id} value={p.name}>{p.name} ({p.speed})</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <div style={{ display: 'grid', gridTemplateColumns: '120px 1fr', gap: 16 }}>
                            <div className="form-group">
                                <label className="form-label">Código Postal</label>
                                <input className="form-input" list="zip-list-svc" placeholder="Escribir CP..." value={form.zip_code} onChange={e => setForm({ ...form, zip_code: e.target.value, neighborhood: '' })} />
                                <datalist id="zip-list-svc">
                                    {uniqueZips.map(zip => <option key={zip} value={zip} />)}
                                </datalist>
                            </div>
                            <div className="form-group">
                                <label className="form-label">Colonia (Rellena la dirección automáticamente)</label>
                                <select className="form-input" value={form.neighborhood} onChange={e => {
                                    const col = e.target.value;
                                    setForm({
                                        ...form,
                                        neighborhood: col,
                                        address: form.address ? `${form.address}, Col. ${col}` : `Col. ${col}`
                                    });
                                }} disabled={!form.zip_code}>
                                    <option value="">-- Seleccionar Colonia --</option>
                                    {availableNeighborhoods.map(n => <option key={n} value={n}>{n}</option>)}
                                </select>
                            </div>
                        </div>
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                            <div className="form-group">
                                <label className="form-label">Dirección Completa (Calle, No, Colonia)</label>
                                <input className="form-input" value={form.address} onChange={e => setForm({ ...form, address: e.target.value })} placeholder="Ej: Av. Juárez #123, Col. Centro" />
                            </div>
                            <div className="form-group">
                                <label className="form-label">Etiqueta</label>
                                <input className="form-input" value={form.label} onChange={e => setForm({ ...form, label: e.target.value })} placeholder="Ej: Casa, Oficina..." />
                            </div>
                        </div>
                    </div>
                    <div className="modal-footer">
                        <button type="button" className="btn btn-ghost" onClick={onClose}>Cancelar</button>
                        <button type="submit" className="btn btn-primary" disabled={saving}>
                            {saving ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Agregar')}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function CustomerModal({ customer, coverageAreas = [], plans = [], onClose, onSave }) {
    const isEdit = !!customer;
    const [form, setForm] = useState({
        name: customer?.name || '',
        phone: customer?.phone || '',
        address: customer?.address || '',
        zip_code: customer?.zip_code || '',
        neighborhood: '',
        account_number: '',
        plan_name: '',
        label: '',
    });
    const uniqueZips = [...new Set(coverageAreas.map(c => c.zip_code))].filter(Boolean);
    const availableNeighborhoods = form.zip_code
        ? coverageAreas.filter(c => c.zip_code === form.zip_code).map(c => c.neighborhood)
        : [];

    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true); setError('');
        try {
            if (isEdit) {
                await api.put(`/customers/${customer.id}`, { name: form.name, phone: form.phone, address: form.address, zip_code: form.zip_code });
            } else {
                await api.post('/customers', form);
            }
            onSave();
        } catch (err) {
            setError(err.response?.data?.message || 'Error al guardar');
        } finally { setSaving(false); }
    }

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal" onClick={e => e.stopPropagation()} style={{ maxWidth: 650 }}>
                <div className="modal-header">
                    <h2>{isEdit ? 'Editar Cliente' : 'Nuevo Cliente'}</h2>
                    <button className="btn-icon" onClick={onClose}><X size={18} /></button>
                </div>
                <form onSubmit={handleSubmit}>
                    <div className="modal-body">
                        {error && (
                            <div style={{ background: '#FEF2F2', color: '#DC2626', padding: '10px 14px', borderRadius: 8, fontSize: '0.85rem', marginBottom: 16, border: '1px solid #FECACA' }}>
                                {error}
                            </div>
                        )}
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                            <div className="form-group">
                                <label className="form-label">Nombre Completo</label>
                                <input className="form-input" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} required placeholder="Titular" />
                            </div>
                            <div className="form-group">
                                <label className="form-label">WhatsApp / Teléfono</label>
                                <input className="form-input" value={form.phone} onChange={e => setForm({ ...form, phone: e.target.value })} required placeholder="521722..." />
                            </div>
                        </div>
                        <div style={{ display: 'grid', gridTemplateColumns: '120px 1fr', gap: 16 }}>
                            <div className="form-group">
                                <label className="form-label">Código Postal</label>
                                <input className="form-input" list="zip-list-cust" placeholder="Escribir CP..." value={form.zip_code} onChange={e => setForm({ ...form, zip_code: e.target.value, neighborhood: '' })} required={!isEdit} />
                                <datalist id="zip-list-cust">
                                    {uniqueZips.map(zip => <option key={zip} value={zip} />)}
                                </datalist>
                            </div>
                            <div className="form-group">
                                <label className="form-label">Colonia (Rellena la dirección automáticamente)</label>
                                <select className="form-input" value={form.neighborhood} onChange={e => {
                                    const col = e.target.value;
                                    setForm({
                                        ...form,
                                        neighborhood: col,
                                        address: form.address ? `${form.address}, Col. ${col}` : `Col. ${col}`
                                    });
                                }} disabled={!form.zip_code}>
                                    <option value="">-- Seleccionar Colonia --</option>
                                    {availableNeighborhoods.map(n => <option key={n} value={n}>{n}</option>)}
                                </select>
                            </div>
                        </div>
                        <div className="form-group">
                            <label className="form-label">Dirección Completa (Calle, No, Colonia)</label>
                            <input className="form-input" value={form.address} onChange={e => setForm({ ...form, address: e.target.value })} placeholder="Ej: Calle Principal #123, Col. Centro" />
                        </div>
                        {!isEdit && (
                            <>
                                <div style={{ borderTop: '1px solid var(--border)', margin: '8px 0 20px', paddingTop: 16 }}>
                                    <p style={{ fontSize: '0.8rem', fontWeight: 600, color: 'var(--text-secondary)', marginBottom: 12 }}>PRIMER SERVICIO</p>
                                </div>
                                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                                    <div className="form-group">
                                        <label className="form-label">Número de Cuenta</label>
                                        <input className="form-input" value={form.account_number} onChange={e => setForm({ ...form, account_number: e.target.value })} required placeholder="BG-100" />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Plan del Servicio</label>
                                        <select className="form-input" value={form.plan_name} onChange={e => setForm({ ...form, plan_name: e.target.value })}>
                                            <option value="">-- Seleccionar Plan --</option>
                                            {plans.map(p => (
                                                <option key={p.id} value={p.name}>{p.name} ({p.speed})</option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                                <div className="form-group">
                                    <label className="form-label">Etiqueta (Casa, Oficina...)</label>
                                    <input className="form-input" value={form.label} onChange={e => setForm({ ...form, label: e.target.value })} placeholder="Ej: Mi Casa" />
                                </div>
                            </>
                        )}
                    </div>
                    <div className="modal-footer">
                        <button type="button" className="btn btn-ghost" onClick={onClose}>Cancelar</button>
                        <button type="submit" className="btn btn-primary" disabled={saving}>
                            {saving ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Crear Cliente')}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

export default function Customers() {
    const [customers, setCustomers] = useState([]);
    const [coverageAreas, setCoverageAreas] = useState([]);
    const [plans, setPlans] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [expanded, setExpanded] = useState({});
    const [showCustomerModal, setShowCustomerModal] = useState(false);
    const [editingCustomer, setEditingCustomer] = useState(null);
    const [showServiceModal, setShowServiceModal] = useState(false);
    const [serviceTarget, setServiceTarget] = useState({ customer: null, service: null });

    useEffect(() => {
        const timer = setTimeout(() => loadCustomers(), 400);
        return () => clearTimeout(timer);
    }, [search]);

    async function loadCustomers() {
        setLoading(true);
        try {
            const [custRes, covRes, planRes] = await Promise.all([
                api.get('/customers', { params: { search } }),
                api.get('/coverage?limit=500&active=1'),
                api.get('/plans')
            ]);
            setCustomers(custRes.data.data || custRes.data);
            setCoverageAreas(covRes.data.data || covRes.data || []);
            setPlans(planRes.data.data || planRes.data || []);
        } catch { } finally { setLoading(false); }
    }

    async function deleteCustomer(id) {
        if (!confirm('¿Eliminar este cliente y todos sus servicios?')) return;
        try { await api.delete(`/customers/${id}`); loadCustomers(); } catch { }
    }

    async function toggleService(service) {
        try {
            await api.put(`/customer-services/${service.id}`, { is_active: !service.is_active });
            loadCustomers();
        } catch { }
    }

    async function removeService(service) {
        if (!confirm('¿Eliminar este servicio?')) return;
        try { await api.delete(`/customer-services/${service.id}`); loadCustomers(); } catch { }
    }

    function toggleExpand(id) {
        setExpanded(prev => ({ ...prev, [id]: !prev[id] }));
    }

    return (
        <div className="fade-in">
            <div className="page-header" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <div>
                    <h1>Clientes</h1>
                    <p>Gestión de clientes y sus servicios activos</p>
                </div>
                <button className="btn btn-primary" onClick={() => { setEditingCustomer(null); setShowCustomerModal(true); }}>
                    <Plus size={16} /> Nuevo Cliente
                </button>
            </div>

            <div className="card" style={{ margin: '0 32px 20px', padding: '16px 20px' }}>
                <div style={{ maxWidth: 400, position: 'relative' }}>
                    <Search size={16} style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                    <input className="form-input" style={{ paddingLeft: 36 }} placeholder="Buscar por nombre, teléfono o cuenta..." value={search} onChange={e => setSearch(e.target.value)} />
                </div>
            </div>

            <div className="page-body" style={{ paddingTop: 0 }}>
                {loading ? (
                    <div className="loading-spinner"><div className="spinner"></div></div>
                ) : customers.length === 0 ? (
                    <div className="empty-state">
                        <Users size={48} />
                        <p>No hay clientes registrados</p>
                    </div>
                ) : (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                        {customers.map(c => {
                            const isOpen = expanded[c.id];
                            const activeServices = c.services?.filter(s => s.is_active).length || 0;
                            const totalServices = c.services?.length || 0;

                            return (
                                <div key={c.id} className="card" style={{ overflow: 'hidden' }}>
                                    {/* Customer row */}
                                    <div style={{ padding: '16px 20px', display: 'flex', alignItems: 'center', gap: 14, cursor: 'pointer' }} onClick={() => toggleExpand(c.id)}>
                                        <div style={{ width: 40, height: 40, borderRadius: '50%', background: 'var(--bg-app)', color: 'var(--color-primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                                            <User size={18} />
                                        </div>
                                        <div style={{ flex: 1, minWidth: 0 }}>
                                            <div style={{ fontWeight: 600, fontSize: '0.95rem' }}>{c.name}</div>
                                            <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>{c.phone}</div>
                                        </div>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                            <span className="badge" style={{ background: activeServices > 0 ? '#ECFDF5' : '#FEF2F2', color: activeServices > 0 ? '#059669' : '#DC2626' }}>
                                                {activeServices}/{totalServices} servicios
                                            </span>
                                            <div style={{ display: 'flex', gap: 4 }}>
                                                <button className="btn-icon" onClick={(e) => { e.stopPropagation(); setEditingCustomer(c); setShowCustomerModal(true); }}><Edit2 size={14} /></button>
                                                <button className="btn-icon" style={{ color: 'var(--color-danger)' }} onClick={(e) => { e.stopPropagation(); deleteCustomer(c.id); }}><Trash2 size={14} /></button>
                                            </div>
                                            {isOpen ? <ChevronUp size={16} color="var(--text-muted)" /> : <ChevronDown size={16} color="var(--text-muted)" />}
                                        </div>
                                    </div>

                                    {/* Services expandable */}
                                    {isOpen && (
                                        <div style={{ borderTop: '1px solid var(--border)', background: '#fafbfc' }}>
                                            {c.services?.length > 0 ? (
                                                <table className="table" style={{ margin: 0 }}>
                                                    <thead>
                                                        <tr>
                                                            <th>Cuenta</th>
                                                            <th>Plan</th>
                                                            <th>Etiqueta</th>
                                                            <th>Estado</th>
                                                            <th style={{ textAlign: 'right' }}>Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {c.services.map(s => (
                                                            <tr key={s.id}>
                                                                <td style={{ fontWeight: 600, color: 'var(--color-primary)' }}>{s.account_number}</td>
                                                                <td>{s.plan_name || '—'}</td>
                                                                <td style={{ color: 'var(--text-secondary)' }}>{s.label || '—'}</td>
                                                                <td>
                                                                    <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                                                        <div className={`toggle-switch ${s.is_active ? 'active' : ''}`} onClick={() => toggleService(s)}>
                                                                            <div className="toggle-slider"></div>
                                                                        </div>
                                                                        <span style={{ fontSize: '0.7rem', fontWeight: 600, color: s.is_active ? 'var(--color-success)' : 'var(--text-muted)' }}>
                                                                            {s.is_active ? 'Activo' : 'Suspendido'}
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td style={{ textAlign: 'right' }}>
                                                                    <div style={{ display: 'flex', gap: 4, justifyContent: 'flex-end' }}>
                                                                        <button className="btn-icon" onClick={() => { setServiceTarget({ customer: c, service: s }); setShowServiceModal(true); }}><Edit2 size={14} /></button>
                                                                        <button className="btn-icon" style={{ color: 'var(--color-danger)' }} onClick={() => removeService(s)}><Trash2 size={14} /></button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            ) : (
                                                <div style={{ padding: '20px', textAlign: 'center', color: 'var(--text-muted)', fontSize: '0.85rem' }}>
                                                    Sin servicios registrados
                                                </div>
                                            )}
                                            <div style={{ padding: '12px 20px', borderTop: '1px solid var(--border)' }}>
                                                <button className="btn btn-secondary btn-sm" onClick={() => { setServiceTarget({ customer: c, service: null }); setShowServiceModal(true); }}>
                                                    <Plus size={14} /> Agregar Servicio
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

            {showCustomerModal && (
                <CustomerModal customer={editingCustomer} coverageAreas={coverageAreas} plans={plans} onClose={() => setShowCustomerModal(false)} onSave={() => { setShowCustomerModal(false); loadCustomers(); }} />
            )}

            {showServiceModal && (
                <ServiceModal customer={serviceTarget.customer} service={serviceTarget.service} coverageAreas={coverageAreas} plans={plans} onClose={() => setShowServiceModal(false)} onSave={() => { setShowServiceModal(false); loadCustomers(); }} />
            )}
        </div>
    );
}
