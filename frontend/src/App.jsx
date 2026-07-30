import React from 'react';
import { Routes, Route, Navigate, useLocation } from 'react-router-dom';
import { useAuth } from './context/AuthContext';
import AppShell from './components/layout/AppShell';
import Toast from './components/common/Toast';
import Landing from './pages/Landing';
import Auth from './pages/Auth';
import Onboarding from './pages/Onboarding';
import Profile from './pages/Profile';

// Parent pages
import ParentSearch from './pages/parent/Search';
import NannyDetail from './pages/parent/NannyDetail';
import ParentBookings from './pages/parent/Bookings';

// Nanny pages
import NannySchedule from './pages/nanny/Schedule';
import NannyBookings from './pages/nanny/Bookings';
import NannyDocuments from './pages/nanny/Documents';
import NannyBalance from './pages/nanny/Balance';

// Route guard for authenticated users
function RequireAuth({ children, allowedRole }) {
  const { isAuthenticated, isLoading, user } = useAuth();
  const location = useLocation();

  if (isLoading) return <div className="container" style={{ padding: '40px', textAlign: 'center' }}>Загрузка...</div>;

  if (!isAuthenticated) {
    return <Navigate to="/auth" state={{ from: location }} replace />;
  }

  // Force onboarding if user hasn't filled profile name yet (except on onboarding page itself)
  const isProfileIncomplete = !user?.profile?.first_name;
  if (isProfileIncomplete && location.pathname !== '/onboarding') {
    return <Navigate to="/onboarding" replace />;
  }

  // Check role authorization
  if (allowedRole && user?.role !== allowedRole) {
    return <Navigate to={user?.role === 'parent' ? '/parent/search' : '/nanny/schedule'} replace />;
  }

  return children;
}

export default function App() {
  const { isAuthenticated, user } = useAuth();

  return (
    <AppShell>
      <Routes>
        {/* Public Routes */}
        <Route 
          path="/" 
          element={
            isAuthenticated 
              ? <Navigate to={user?.role === 'parent' ? '/parent/search' : '/nanny/schedule'} replace />
              : <Landing />
          } 
        />
        <Route 
          path="/auth" 
          element={
            isAuthenticated 
              ? <Navigate to={user?.role === 'parent' ? '/parent/search' : '/nanny/schedule'} replace />
              : <Auth />
          } 
        />

        {/* Complete Onboarding (Requires Login) */}
        <Route 
          path="/onboarding" 
          element={
            <RequireAuth>
              <Onboarding />
            </RequireAuth>
          } 
        />

        {/* Profile Settings (Requires Login) */}
        <Route 
          path="/profile" 
          element={
            <RequireAuth>
              <Profile />
            </RequireAuth>
          } 
        />

        {/* Parent Specific Search & Booking Routes */}
        <Route 
          path="/parent/search" 
          element={
            <RequireAuth allowedRole="parent">
              <ParentSearch />
            </RequireAuth>
          } 
        />
        <Route 
          path="/parent/nanny/:id" 
          element={
            <RequireAuth allowedRole="parent">
              <NannyDetail />
            </RequireAuth>
          } 
        />
        <Route 
          path="/parent/bookings" 
          element={
            <RequireAuth allowedRole="parent">
              <ParentBookings />
            </RequireAuth>
          } 
        />

        {/* Nanny Specific Schedule & Request Routes */}
        <Route 
          path="/nanny/schedule" 
          element={
            <RequireAuth allowedRole="nanny">
              <NannySchedule />
            </RequireAuth>
          } 
        />
        <Route 
          path="/nanny/bookings" 
          element={
            <RequireAuth allowedRole="nanny">
              <NannyBookings />
            </RequireAuth>
          } 
        />
        <Route 
          path="/nanny/documents" 
          element={
            <RequireAuth allowedRole="nanny">
              <NannyDocuments />
            </RequireAuth>
          } 
        />
        <Route 
          path="/nanny/balance" 
          element={
            <RequireAuth allowedRole="nanny">
              <NannyBalance />
            </RequireAuth>
          } 
        />

        {/* Fallback to main page */}
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>

      <Toast />
    </AppShell>
  );
}
