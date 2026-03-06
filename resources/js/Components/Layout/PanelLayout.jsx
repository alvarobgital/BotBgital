import React, { useState, useEffect } from 'react';
import { Outlet, NavLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import api from '../../api';
import {
    LayoutDashboard, MessageSquare, GitBranch, Users,
    Settings, LogOut, Zap, Menu, X, Ticket, Globe, User, Package, Target
} from 'lucide-react';

export default function PanelLayout() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const [waitingCount, setWaitingCount] = useState(0);
    const [isMobileOpen, setIsMobileOpen] = useState(false);
    const [logoUrl, setLogoUrl] = useState(null);
    const [companyName, setCompanyName] = useState('BotBgital');

    useEffect(() => {
        loadSettings();
        loadWaitingCount();
        const interval = setInterval(loadWaitingCount, 15000);
        return () => clearInterval(interval);
    }, []);

    async function loadSettings() {
        try {
            const res = await api.get('/settings');
            if (res.data.company_logo) setLogoUrl(res.data.company_logo);
            if (res.data.empresa_nombre) setCompanyName(res.data.empresa_nombre);
        } catch { }
    }

    async function loadWaitingCount() {
        try {
            const res = await api.get('/dashboard/stats');
            setWaitingCount(res.data.waiting_agent || 0);
        } catch { }
    }

    async function handleLogout() {
        await logout();
        navigate('/login');
    }

    const navItems = [
        { to: '/panel', icon: LayoutDashboard, label: 'Dashboard', end: true },
        { to: '/panel/conversations', icon: MessageSquare, label: 'Conversaciones', badge: waitingCount },
        { to: '/panel/tickets', icon: Ticket, label: 'Tickets' },
        { to: '/panel/flows', icon: GitBranch, label: 'Flujos del Bot' },
        { to: '/panel/coverage', icon: Globe, label: 'Cobertura' },
        { to: '/panel/leads', icon: Target, label: 'Leads' },
        { to: '/panel/contacts', icon: Zap, label: 'Contactos' },
        { to: '/panel/customers', icon: Users, label: 'Clientes' },
        user?.role === 'admin' && { to: '/panel/users', icon: User, label: 'Usuarios' },
        { to: '/panel/plans', icon: Package, label: 'Planes' },
        { to: '/panel/settings', icon: Settings, label: 'Configuración' },
    ].filter(Boolean);

    return (
        <div className="app-layout">
            {/* Mobile Header */}
            <div className="mobile-header">
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    {logoUrl ? (
                        <img src={logoUrl} alt="Logo" style={{ height: 24, maxWidth: 80, objectFit: 'contain' }} />
                    ) : (
                        <Zap size={18} />
                    )}
                    <span style={{ fontWeight: 600, fontSize: '0.85rem' }}>{companyName}</span>
                </div>
                <button className="btn-icon" style={{ color: '#fff' }} onClick={() => setIsMobileOpen(!isMobileOpen)}>
                    {isMobileOpen ? <X size={22} /> : <Menu size={22} />}
                </button>
            </div>

            {/* Sidebar */}
            <aside className={`sidebar ${isMobileOpen ? 'open' : ''}`}>
                <div className="sidebar-header desktop-only">
                    <div className="sidebar-logo">
                        {logoUrl ? (
                            <img src={logoUrl} alt="Logo" style={{ height: 28, maxWidth: 100, objectFit: 'contain' }} />
                        ) : (
                            <Zap size={20} />
                        )}
                        <span style={{ fontWeight: 700, fontSize: '0.9rem' }}>{companyName}</span>
                    </div>
                </div>

                <nav className="sidebar-nav">
                    {navItems.map((item) => (
                        <NavLink
                            key={item.to}
                            to={item.to}
                            end={item.end}
                            onClick={() => setIsMobileOpen(false)}
                            className={({ isActive }) => `sidebar-link ${isActive ? 'active' : ''}`}
                        >
                            <item.icon size={18} />
                            <span>{item.label}</span>
                            {item.badge > 0 && (
                                <span className="sidebar-badge">{item.badge}</span>
                            )}
                        </NavLink>
                    ))}
                </nav>

                <div className="sidebar-footer">
                    <div style={{
                        display: 'flex', alignItems: 'center', gap: 10,
                        padding: '10px 12px', background: 'rgba(255,255,255,.06)',
                        borderRadius: '8px'
                    }}>
                        <div style={{
                            width: 32, height: 32, background: '#fff',
                            color: 'var(--color-primary)', borderRadius: '6px',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            fontWeight: 700, fontSize: '0.8rem', flexShrink: 0
                        }}>
                            {user?.name?.charAt(0) || 'U'}
                        </div>
                        <div style={{ flex: 1, minWidth: 0 }}>
                            <div style={{ fontSize: '0.8rem', fontWeight: 600, color: '#fff', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                {user?.name}
                            </div>
                            <div style={{ fontSize: '0.65rem', color: 'rgba(255,255,255,.45)', textTransform: 'uppercase', letterSpacing: '.5px' }}>
                                {user?.role || 'Agente'}
                            </div>
                        </div>
                        <button
                            className="btn-icon"
                            onClick={handleLogout}
                            style={{ color: 'rgba(255,255,255,.35)', padding: 4 }}
                        >
                            <LogOut size={16} />
                        </button>
                    </div>
                </div>
            </aside>

            {/* Main */}
            <main className="main-content">
                <Outlet />
            </main>

            {/* Mobile Overlay */}
            {isMobileOpen && <div className="mobile-overlay" onClick={() => setIsMobileOpen(false)} />}
        </div>
    );
}
