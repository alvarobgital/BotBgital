import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { Zap, Mail, Lock, LogIn, Eye, EyeOff } from 'lucide-react';

export default function Login() {
    const { login } = useAuth();
    const navigate = useNavigate();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
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
            setError(err.response?.data?.message || 'Credenciales incorrectas');
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="login-page">
            <div className="login-card">
                <div className="login-logo">
                    <Zap size={22} /> BotBgital
                </div>
                <h1>Iniciar Sesión</h1>
                <p className="login-subtitle">Accede al panel de administración</p>

                <form onSubmit={handleSubmit}>
                    {error && (
                        <div style={{
                            background: '#FEF2F2', color: '#DC2626',
                            padding: '10px 14px', borderRadius: '8px',
                            fontSize: '0.85rem', marginBottom: 20,
                            border: '1px solid #FECACA'
                        }}>
                            {error}
                        </div>
                    )}

                    <div className="form-group">
                        <label className="form-label">Correo electrónico</label>
                        <div style={{ position: 'relative' }}>
                            <Mail size={16} style={{
                                position: 'absolute', left: 12, top: '50%',
                                transform: 'translateY(-50%)', color: 'var(--text-muted)'
                            }} />
                            <input
                                type="email"
                                className="form-input"
                                style={{ paddingLeft: 36 }}
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
                                transform: 'translateY(-50%)', color: 'var(--text-muted)'
                            }} />
                            <input
                                type={showPassword ? 'text' : 'password'}
                                className="form-input"
                                style={{ paddingLeft: 36, paddingRight: 40 }}
                                placeholder="••••••••"
                                value={password}
                                onChange={e => setPassword(e.target.value)}
                                required
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword(!showPassword)}
                                style={{
                                    position: 'absolute', right: 8, top: '50%',
                                    transform: 'translateY(-50%)', background: 'none',
                                    border: 'none', cursor: 'pointer', padding: 4,
                                    color: 'var(--text-muted)', display: 'flex',
                                    alignItems: 'center'
                                }}
                            >
                                {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        className="btn btn-primary btn-block btn-lg"
                        disabled={loading}
                        style={{ marginTop: 8 }}
                    >
                        {loading ? (
                            <div className="spinner" style={{ width: 20, height: 20, borderWidth: 2 }} />
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
