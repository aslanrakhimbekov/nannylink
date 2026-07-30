import React from 'react';
import { NavLink } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';
import { Briefcase, Search, PlusCircle, FileText, Wallet, User, Calendar } from 'lucide-react';
import styles from './BottomNav.module.css';

export default function BottomNav() {
  const { role, isAuthenticated } = useAuth();
  const { t } = useTranslation();

  if (!isAuthenticated) return null;

  return (
    <nav className={styles.nav}>
      {role === 'parent' ? (
        <>
          <NavLink to="/parent/search" className={({ isActive }) => isActive ? `${styles.link} ${styles.active}` : styles.link}>
            <Search size={22} />
            <span>{t('nav.search')}</span>
          </NavLink>
          <NavLink to="/parent/bookings" className={({ isActive }) => isActive ? `${styles.link} ${styles.active}` : styles.link}>
            <Briefcase size={22} />
            <span>{t('nav.bookings')}</span>
          </NavLink>
        </>
      ) : (
        <>
          <NavLink to="/nanny/schedule" className={({ isActive }) => isActive ? `${styles.link} ${styles.active}` : styles.link}>
            <Calendar size={22} />
            <span>{t('nav.schedule')}</span>
          </NavLink>
          <NavLink to="/nanny/bookings" className={({ isActive }) => isActive ? `${styles.link} ${styles.active}` : styles.link}>
            <Briefcase size={22} />
            <span>{t('nav.bookings')}</span>
          </NavLink>
          <NavLink to="/nanny/documents" className={({ isActive }) => isActive ? `${styles.link} ${styles.active}` : styles.link}>
            <FileText size={22} />
            <span>{t('nav.documents')}</span>
          </NavLink>
          <NavLink to="/nanny/balance" className={({ isActive }) => isActive ? `${styles.link} ${styles.active}` : styles.link}>
            <Wallet size={22} />
            <span>{t('nav.balance')}</span>
          </NavLink>
        </>
      )}
      <NavLink to="/profile" className={({ isActive }) => isActive ? `${styles.link} ${styles.active}` : styles.link}>
        <User size={22} />
        <span>{t('nav.profile')}</span>
      </NavLink>
    </nav>
  );
}
