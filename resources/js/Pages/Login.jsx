import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { Zap, Mail, Lock, LogIn } from 'lucide-react';

export default function Login() {
    const { login } = useAuth();
    const navigate = useNavigate();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    async function handleSubmit(e) {
        e.preventDefault();
        setError('');
        setLoading(true);

        try {
            await login(email, password);
            navigate('/panel');
        } catch (err) {
            setError(err.response?.data?.message || 'Error al iniciar sesión');
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="login-page">
            <div className="login-card">
                <div className="login-logo">
                    <Zap size={28} style={{ display: 'inline', verticalAlign: 'middle' }} /> BotBgital
                </div>
                <h1>Iniciar Sesión</h1>
                <p className="login-subtitle">Panel de administración</p>

                <form onSubmit={handleSubmit}>
                    {error && (
                        <div style={{
                            background: 'rgba(239,68,68,0.08)',
                            color: '#dc2626',
                            padding: '10px 14px',
                            borderRadius: 'var(--radius-md)',
                            fontSize: '0.85rem',
                            marginBottom: 18,
                        }}>
                            {error}
                        </div>
                    )}

                    <div className="form-group">
                        <label className="form-label">Correo electrónico</label>
                        <div style={{ position: 'relative' }}>
                            <Mail size={16} style={{
                                position: 'absolute', left: 12, top: '50%',
                                transform: 'translateY(-50%)', color: 'rgba(26,21,48,0.3)'
                            }} />
                            <input
                                type="email"
                                className="form-input"
                                style={{ paddingLeft: 38 }}
                                placeholder="admin@bgital.mx"
                                value={email}
                                onChange={e => setEmail(e.target.value)}
                                required
                            />
                        </div>
                    </div>

                    <div className="form-group">
                        <label className="form-label">Contraseña</label>
                        <div style={{ position: 'relative' }}>
                            <Lock size={16} style={{
                                position: 'absolute', left: 12, top: '50%',
                                transform: 'translateY(-50%)', color: 'rgba(26,21,48,0.3)'
                            }} />
                            <input
                                type="password"
                                className="form-input"
                                style={{ paddingLeft: 38 }}
                                placeholder="••••••••"
                                value={password}
                                onChange={e => setPassword(e.target.value)}
                                required
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        className="btn btn-primary btn-lg btn-block"
                        disabled={loading}
                        style={{ marginTop: 8 }}
                    >
                        {loading ? (
                            <div className="spinner" style={{ width: 20, height: 20, borderWidth: 2 }}></div>
                        ) : (
                            <>
                                <LogIn size={18} />
                                Entrar al Panel
                            </>
                        )}
                    </button>
                </form>
            </div>
        </div>
    );
}
