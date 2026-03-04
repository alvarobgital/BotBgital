import React, { useState, useEffect } from 'react';
import { Outlet, NavLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import api from '../../api';
import {
    LayoutDashboard, MessageSquare, GitBranch, Users,
    Settings, LogOut, Bot, Zap, Menu, X, Ticket
} from 'lucide-react';

export default function PanelLayout() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const [botEnabled, setBotEnabled] = useState(true);
    const [waitingCount, setWaitingCount] = useState(0);
    const [isMobileOpen, setIsMobileOpen] = useState(false);
    const [logoUrl, setLogoUrl] = useState(null);

    useEffect(() => {
        loadBotStatus();
        loadWaitingCount();
        const interval = setInterval(loadWaitingCount, 15000);
        return () => clearInterval(interval);
    }, []);

    async function loadBotStatus() {
        try {
            const res = await api.get('/settings');
            setBotEnabled(res.data.bot_enabled === 'true');
            if (res.data.company_logo) {
                setLogoUrl(res.data.company_logo);
            }
        } catch { }
    }

    async function loadWaitingCount() {
        try {
            const res = await api.get('/dashboard/stats');
            setWaitingCount(res.data.waiting_agent || 0);
        } catch { }
    }

    async function toggleBot() {
        try {
            const res = await api.post('/settings/toggle-bot');
            setBotEnabled(res.data.bot_enabled === 'true');
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
        { to: '/panel/contacts', icon: Users, label: 'Contactos' },
        { to: '/panel/settings', icon: Settings, label: 'Configuración' },
    ];

    return (
        <div className="app-layout">
            <div className="mobile-header">
                <div className="sidebar-logo">
                    {logoUrl ? <img src={logoUrl} alt="Logo" className="app-logo" style={{ height: 24 }} /> : <><Zap size={22} /> BotBgital</>}
                </div>
                <button className="btn-icon" onClick={() => setIsMobileOpen(!isMobileOpen)}>
                    {isMobileOpen ? <X size={24} /> : <Menu size={24} />}
                </button>
            </div>

            <aside className={`sidebar ${isMobileOpen ? 'open' : ''}`}>
                <div className="sidebar-header desktop-only">
                    <div className="sidebar-logo">
                        {logoUrl ? <img src={logoUrl} alt="Logo" className="app-logo" style={{ height: 32 }} /> : <><Zap size={22} /> BotBgital</>}
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
                            <item.icon />
                            {item.label}
                            {item.badge > 0 && (
                                <span className="sidebar-badge">{item.badge}</span>
                            )}
                        </NavLink>
                    ))}
                </nav>

                <div className="sidebar-footer">
                    <div className="bot-toggle">
                        <div className="bot-toggle-label">
                            <Bot size={16} />
                            Bot {botEnabled ? 'Activo' : 'Inactivo'}
                        </div>
                        <label className="toggle-switch">
                            <input
                                type="checkbox"
                                checked={botEnabled}
                                onChange={toggleBot}
                            />
                            <span className="toggle-slider"></span>
                        </label>
                    </div>

                    <div style={{ marginTop: 12, display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                        <span style={{ fontSize: '0.78rem', opacity: 0.7 }}>
                            {user?.name}
                        </span>
                        <button className="btn-ghost" onClick={handleLogout} style={{ padding: '4px 8px', color: 'rgba(255,255,255,0.6)' }}>
                            <LogOut size={16} />
                        </button>
                    </div>
                </div>
            </aside>

            <main className="main-content">
                <Outlet />
            </main>

            {/* Mobile Overlay */}
            {isMobileOpen && <div className="mobile-overlay" onClick={() => setIsMobileOpen(false)}></div>}
        </div>
    );
}
