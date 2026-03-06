import React, { useState, useEffect } from 'react';
import api from '../api';
import { Save, Globe, Clock, Bot, AlertTriangle } from 'lucide-react';

export default function Settings() {
    const [settings, setSettings] = useState({});
    const [logoFile, setLogoFile] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [saved, setSaved] = useState(false);

    useEffect(() => { loadSettings(); }, []);

    async function loadSettings() {
        try {
            const res = await api.get('/settings');
            setSettings(res.data);
        } catch { } finally { setLoading(false); }
    }

    function setField(key, value) {
        setSettings(prev => ({ ...prev, [key]: value }));
        setSaved(false);
    }

    async function handleSave() {
        setSaving(true);
        try {
            const formData = new FormData();
            formData.append('settings', JSON.stringify(settings));
            if (logoFile) formData.append('company_logo', logoFile);
            formData.append('_method', 'PUT');
            await api.post('/settings', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
            setSaved(true);
            setLogoFile(null);
            loadSettings();
            setTimeout(() => setSaved(false), 3000);
        } catch { } finally { setSaving(false); }
    }

    if (loading) return <div className="loading-spinner"><div className="spinner"></div></div>;

    return (
        <div className="fade-in">
            <div className="page-header" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <div>
                    <h1>Configuración</h1>
                    <p>Ajustes generales del sistema</p>
                </div>
                <button className="btn btn-primary" onClick={handleSave} disabled={saving}>
                    <Save size={16} />
                    {saving ? 'Guardando...' : saved ? 'Guardado' : 'Guardar Cambios'}
                </button>
            </div>

            <div className="page-body">
                <div className="settings-card">
                    <h3><Globe size={16} /> Información de la Empresa</h3>
                    <div className="form-group">
                        <label className="form-label">Logo de la Empresa</label>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
                            {settings.company_logo && (
                                <img src={settings.company_logo} alt="Logo" style={{ width: 48, height: 48, objectFit: 'contain', background: '#fff', borderRadius: 8, border: '1px solid var(--border)' }} />
                            )}
                            <input type="file" className="form-input" accept="image/*" onChange={e => { setLogoFile(e.target.files[0]); setSaved(false); }} style={{ flex: 1 }} />
                        </div>
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                        <div className="form-group">
                            <label className="form-label">Nombre de la Empresa</label>
                            <input className="form-input" value={settings.empresa_nombre || ''} onChange={e => setField('empresa_nombre', e.target.value)} />
                        </div>
                        <div className="form-group">
                            <label className="form-label">Sitio Web</label>
                            <input className="form-input" value={settings.empresa_web || ''} onChange={e => setField('empresa_web', e.target.value)} />
                        </div>
                    </div>
                </div>

                <div className="settings-card">
                    <h3><Clock size={16} /> Horario de Atención</h3>
                    <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginBottom: 16 }}>
                        Horario en el que hay personal para atender derivaciones de Telegram.
                    </p>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                        <div className="form-group">
                            <label className="form-label">Inicio de Jornada</label>
                            <input type="time" className="form-input" value={settings.work_hours_start || '09:20'} onChange={e => setField('work_hours_start', e.target.value)} />
                        </div>
                        <div className="form-group">
                            <label className="form-label">Fin de Jornada</label>
                            <input type="time" className="form-input" value={settings.work_hours_end || '18:00'} onChange={e => setField('work_hours_end', e.target.value)} />
                        </div>
                    </div>
                </div>

                <div className="settings-card">
                    <h3><Bot size={16} /> Bot de Telegram</h3>
                    <div className="form-group">
                        <label className="form-label">Token del Bot</label>
                        <input className="form-input" value={settings.telegram_bot_token || ''} onChange={e => setField('telegram_bot_token', e.target.value)} placeholder="123456:ABC-DEF1234ghIkl..." />
                    </div>
                    <div className="form-group">
                        <label className="form-label">ID del Grupo de Notificaciones</label>
                        <input className="form-input" value={settings.telegram_notify_group_id || ''} onChange={e => setField('telegram_notify_group_id', e.target.value)} placeholder="-100123456789" />
                    </div>
                </div>

                <div className="settings-card">
                    <h3><Bot size={16} /> Estado del Bot</h3>
                    <div className="form-group">
                        <label className="form-label">Bot Habilitado</label>
                        <select className="form-input" value={settings.bot_enabled || 'true'} onChange={e => setField('bot_enabled', e.target.value)}>
                            <option value="true">Sí — Bot respondiendo automáticamente</option>
                            <option value="false">No — Bot pausado</option>
                        </select>
                    </div>
                </div>

                <div className="settings-card" style={{ borderLeft: '3px solid var(--color-danger)' }}>
                    <h3 style={{ color: 'var(--color-danger)' }}><AlertTriangle size={16} /> Estado de la Red (Falla Masiva)</h3>
                    <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginBottom: 16 }}>
                        Si hay una falla general, el bot avisará automáticamente a los clientes.
                    </p>
                    <div className="form-group">
                        <label className="form-label">¿Hay una falla masiva activa?</label>
                        <select className="form-input" value={settings.is_outage_active || 'false'} onChange={e => setField('is_outage_active', e.target.value)}>
                            <option value="false">No — Red Operando Normal</option>
                            <option value="true">Sí — Falla Masiva Detectada</option>
                        </select>
                    </div>
                    {settings.is_outage_active === 'true' && (
                        <div className="form-group">
                            <label className="form-label">Mensaje de Falla</label>
                            <textarea className="form-input" value={settings.outage_message || ''} onChange={e => setField('outage_message', e.target.value)} placeholder="Ej: Lo sentimos, hay una falla de fibra óptica en la zona Centro." rows={3} />
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
