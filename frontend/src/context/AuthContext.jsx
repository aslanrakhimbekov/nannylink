import React, { createContext, useState, useEffect, useContext } from 'react';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [token, setToken] = useState(() => localStorage.getItem('nannylink_token'));
  const [user, setUser] = useState(() => {
    const saved = localStorage.getItem('nannylink_user');
    try {
      return saved ? JSON.parse(saved) : null;
    } catch {
      return null;
    }
  });
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // Sync state and finish loading
    setIsLoading(false);

    const handleLogout = () => {
      setToken(null);
      setUser(null);
    };

    window.addEventListener('nannylink_logout', handleLogout);
    return () => window.removeEventListener('nannylink_logout', handleLogout);
  }, []);

  const login = (newToken, newUser) => {
    localStorage.setItem('nannylink_token', newToken);
    localStorage.setItem('nannylink_user', JSON.stringify(newUser));
    setToken(newToken);
    setUser(newUser);
  };

  const logout = () => {
    localStorage.removeItem('nannylink_token');
    localStorage.removeItem('nannylink_user');
    setToken(null);
    setUser(null);
  };

  const updateUser = (userData) => {
    const updatedUser = { ...user, ...userData };
    localStorage.setItem('nannylink_user', JSON.stringify(updatedUser));
    setUser(updatedUser);
  };

  const role = user?.role;
  const isAuthenticated = !!token;

  return (
    <AuthContext.Provider
      value={{
        token,
        user,
        role,
        isAuthenticated,
        isLoading,
        login,
        logout,
        updateUser,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
