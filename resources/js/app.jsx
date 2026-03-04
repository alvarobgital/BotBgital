import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Login from './Pages/Login';
import PanelLayout from './Components/Layout/PanelLayout';
import Dashboard from './Pages/Dashboard';
import Conversations from './Pages/Conversations';
import FlowEditor from './Pages/FlowEditor';
import Contacts from './Pages/Contacts';
import Settings from './Pages/Settings';
import Tickets from './Pages/Tickets';
import { AuthProvider, useAuth } from './context/AuthContext';

function ProtectedRoute({ children }) {
    const { user, loading } = useAuth();
    if (loading) return <div className="loading-spinner"><div className="spinner"></div></div>;
    if (!user) return <Navigate to="/login" replace />;
    return children;
}

function App() {
    return (
        <AuthProvider>
            <BrowserRouter>
                <Routes>
                    <Route path="/login" element={<Login />} />
                    <Route path="/panel" element={
                        <ProtectedRoute>
                            <PanelLayout />
                        </ProtectedRoute>
                    }>
                        <Route index element={<Dashboard />} />
                        <Route path="conversations" element={<Conversations />} />
                        <Route path="conversations/:id" element={<Conversations />} />
                        <Route path="tickets" element={<Tickets />} />
                        <Route path="flows" element={<FlowEditor />} />
                        <Route path="contacts" element={<Contacts />} />
                        <Route path="settings" element={<Settings />} />
                    </Route>
                    <Route path="*" element={<Navigate to="/panel" replace />} />
                </Routes>
            </BrowserRouter>
        </AuthProvider>
    );
}

const container = document.getElementById('app');
const root = createRoot(container);
root.render(<App />);
