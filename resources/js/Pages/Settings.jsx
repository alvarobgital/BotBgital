import React, { useState, useEffect } from 'react';
import api from '../api';
import { Save, Globe, Clock, Bot, Mail } from 'lucide-react';

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
            if (logoFile) {
                formData.append('company_logo', logoFile);
            }
            formData.append('_method', 'PUT');

            await api.post('/settings', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            setSaved(true);
            setLogoFile(null);
            loadSettings(); // Reload to get new logo URL
            setTimeout(() => setSaved(false), 3000);
        } catch { } finally { setSaving(false); }
    }

    if (loading) return <div className="loading-spinner"><div className="spinner"></div></div>;

    return (
        <>
            <div className="page-header" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <div>
                    <h1>Configuración</h1>
                    <p>Ajustes generales del sistema BotBgital</p>
                </div>
                <button className="btn btn-primary" onClick={handleSave} disabled={saving}>
                    <Save size={16} />
                    {saving ? 'Guardando...' : saved ? '✓ Guardado' : 'Guardar Cambios'}
                </button>
            </div>
            <div className="page-body">
                <div className="settings-card">
                    <h3><Globe size={16} style={{ display: 'inline', verticalAlign: 'middle', marginRight: 8 }} />Información de la Empresa</h3>
                    <div className="form-group">
                        <label className="form-label">Logo de la Empresa</label>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
                            {settings.company_logo && (
                                <img
                                    src={settings.company_logo}
                                    alt="Logo"
                                    style={{ width: '48px', height: '48px', objectFit: 'contain', background: '#fff', borderRadius: '8px', border: '1px solid #ddd' }}
                                />
                            )}
                            <input
                                type="file"
                                className="form-input"
                                accept="image/*"
                                onChange={e => {
                                    setLogoFile(e.target.files[0]);
                                    setSaved(false);
                                }}
                                style={{ padding: '8px', flex: 1 }}
                            />
                        </div>
                    </div>
                    <div className="form-group">
                        <label className="form-label">Nombre de la Empresa</label>
                        <input className="form-input" value={settings.empresa_nombre || ''}
                            onChange={e => setField('empresa_nombre', e.target.value)} />
                    </div>
                    <div className="form-group">
                        <label className="form-label">Sitio Web</label>
                        <input className="form-input" value={settings.empresa_web || ''}
                            onChange={e => setField('empresa_web', e.target.value)} />
                    </div>
                </div>

                <div className="settings-card">
                    <h3><Clock size={16} style={{ display: 'inline', verticalAlign: 'middle', marginRight: 8 }} />Horario de Atención</h3>
                    <div className="form-group">
                        <label className="form-label">Horario</label>
                        <input className="form-input" value={settings.horario_atencion || ''}
                            onChange={e => setField('horario_atencion', e.target.value)} />
                    </div>
                </div>

                <div className="settings-card">
                    <h3><Bot size={16} style={{ display: 'inline', verticalAlign: 'middle', marginRight: 8 }} />Bot de Telegram</h3>
                    <div className="form-group">
                        <label className="form-label">Token del Bot de Telegram</label>
                        <input className="form-input" value={settings.telegram_bot_token || ''}
                            onChange={e => setField('telegram_bot_token', e.target.value)}
                            placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11" />
                    </div>
                    <div className="form-group">
                        <label className="form-label">ID del Grupo de Notificaciones</label>
                        <input className="form-input" value={settings.telegram_notify_group_id || ''}
                            onChange={e => setField('telegram_notify_group_id', e.target.value)}
                            placeholder="-100123456789" />
                    </div>
                </div>

                <div className="settings-card">
                    <h3><Mail size={16} style={{ display: 'inline', verticalAlign: 'middle', marginRight: 8 }} />Estado del Bot</h3>
                    <div className="form-group">
                        <label className="form-label">Bot Habilitado</label>
                        <select className="form-input" value={settings.bot_enabled || 'true'}
                            onChange={e => setField('bot_enabled', e.target.value)}>
                            <option value="true">Sí — Bot respondiendo automáticamente</option>
                            <option value="false">No — Bot pausado</option>
                        </select>
                    </div>
                </div>
            </div>
        </>
    );
}
