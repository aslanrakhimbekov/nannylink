import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../context/AuthContext';
import { profileApi } from '../api/profile';
import { showToast } from '../components/common/Toast';
import { Baby, UserCheck } from 'lucide-react';
import Input from '../components/common/Input';
import Button from '../components/common/Button';
import PageTransition from '../components/layout/PageTransition';
import styles from './Onboarding.module.css';

const CITIES = [
  'Алматы',
  'Астана',
  'Шымкент',
  'Караганда',
  'Актобе',
  'Тараз',
  'Павлодар',
  'Усть-Каменогорск',
  'Семей',
  'Атырау',
  'Костанай',
  'Кызылорда',
  'Уральск',
  'Петропавловск',
];

export default function Onboarding() {
  const { t } = useTranslation();
  const { user, updateUser } = useAuth();
  const navigate = useNavigate();

  const [firstName, setFirstName] = useState(user?.profile?.first_name || '');
  const [lastName, setLastName] = useState(user?.profile?.last_name || '');
  const [city, setCity] = useState(user?.profile?.city || 'Алматы');
  const [selectedRole, setSelectedRole] = useState(user?.role || 'parent');
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});

  const handleOnboard = async (e) => {
    e.preventDefault();
    setErrors({});

    const newErrors = {};
    if (!firstName.trim()) newErrors.firstName = 'Имя обязательно для заполнения';
    if (!lastName.trim()) newErrors.lastName = 'Фамилия обязательна для заполнения';

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    setLoading(true);
    try {
      const response = await profileApi.updateProfile({
        role: selectedRole,
        first_name: firstName,
        last_name: lastName,
        city: city,
      });
      updateUser(response.user);
      showToast('success', 'Профиль успешно настроен!');
      
      // Route based on finalized onboarding role selection
      if (selectedRole === 'parent') {
        navigate('/parent/orders');
      } else {
        navigate('/nanny/search');
      }
    } catch (err) {
      showToast('error', err.message || 'Ошибка обновления профиля');
    } finally {
      setLoading(false);
    }
  };

  return (
    <PageTransition>
      <div className={styles.container}>
        <div className={styles.header}>
          <h1 className="gradient-text">{t('onboarding.title')}</h1>
          <p>{t('onboarding.subtitle')}</p>
        </div>

        <form onSubmit={handleOnboard} className={styles.form}>
          <div className={styles.roleGrid}>
            <div
              className={`${styles.roleCard} ${selectedRole === 'parent' ? styles.activeRole : ''} glass`}
              onClick={() => setSelectedRole('parent')}
            >
              <Baby size={32} className={styles.roleIcon} />
              <h3>{t('onboarding.parent_title')}</h3>
              <p>{t('onboarding.parent_desc')}</p>
            </div>

            <div
              className={`${styles.roleCard} ${selectedRole === 'nanny' ? styles.activeRole : ''} glass`}
              onClick={() => setSelectedRole('nanny')}
            >
              <UserCheck size={32} className={styles.roleIcon} />
              <h3>{t('onboarding.nanny_title')}</h3>
              <p>{t('onboarding.nanny_desc')}</p>
            </div>
          </div>

          <div className={`${styles.infoCard} glass`}>
            <Input
              label={t('profile.first_name')}
              value={firstName}
              onChange={(e) => setFirstName(e.target.value)}
              error={errors.firstName}
              required
            />

            <Input
              label={t('profile.last_name')}
              value={lastName}
              onChange={(e) => setLastName(e.target.value)}
              error={errors.lastName}
              required
            />

            <div style={{ marginBottom: '16px' }}>
              <label style={{ display: 'block', fontSize: '0.85rem', fontWeight: 600, marginBottom: '6px', color: 'var(--color-text-primary)' }}>
                🏙️ Город
              </label>
              <select
                value={city}
                onChange={(e) => setCity(e.target.value)}
                style={{
                  width: '100%',
                  padding: '12px 14px',
                  borderRadius: '12px',
                  border: '1px solid var(--color-border)',
                  background: 'var(--color-bg-card)',
                  color: 'var(--color-text-primary)',
                  fontSize: '0.95rem',
                  fontWeight: 500,
                  outline: 'none',
                  cursor: 'pointer',
                }}
              >
                {CITIES.map((c) => (
                  <option key={c} value={c}>
                    {c}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <Button type="submit" fullWidth loading={loading}>
            {t('common.save')}
          </Button>
        </form>
      </div>
    </PageTransition>
  );
}
