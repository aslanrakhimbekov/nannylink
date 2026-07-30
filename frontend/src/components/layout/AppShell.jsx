import React from 'react';
import { useAuth } from '../../context/AuthContext';
import { useTranslation } from 'react-i18next';
import BottomNav from './BottomNav';
import { LogOut, Globe } from 'lucide-react';
import { Link } from 'react-router-dom';
import styles from './AppShell.module.css';

export default function AppShell({ children }) {
  const { isAuthenticated, logout, user } = useAuth();
  const { t, i18n } = useTranslation();

  const toggleLanguage = () => {
    const nextLang = i18n.language === 'ru' ? 'kk' : 'ru';
    i18n.changeLanguage(nextLang);
    localStorage.setItem('nannylink_lang', nextLang);
  };

  return (
    <div className={styles.shell}>
      <header className={styles.header}>
        <Link to="/" className={styles.logoContainer} style={{ textDecoration: 'none' }}>
          <span className={styles.logoText}>Nanny<span className="gradient-text">Link</span></span>
        </Link>

        <div className={styles.actions}>
          <button className={styles.langBtn} onClick={toggleLanguage} title="Сменить язык / Тілді өзгерту">
            <Globe size={18} />
            <span className={styles.langLabel}>{i18n.language.toUpperCase()}</span>
          </button>

          {isAuthenticated && (
            <button className={styles.logoutBtn} onClick={logout} title={t('profile.logout')}>
              <LogOut size={18} />
            </button>
          )}
        </div>
      </header>

      <main className={styles.main}>
        <div className={styles.content}>
          {children}
        </div>
      </main>

      <BottomNav />
    </div>
  );
}
