import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../context/AuthContext';
import { profileApi } from '../api/profile';
import { showToast } from '../components/common/Toast';
import { ShieldCheck, ShieldAlert, LogOut, CheckCircle } from 'lucide-react';
import Input from '../components/common/Input';
import Button from '../components/common/Button';
import Card from '../components/common/Card';
import PageTransition from '../components/layout/PageTransition';
import styles from './Profile.module.css';

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

export default function Profile() {
  const { t, i18n } = useTranslation();
  const { user, updateUser, logout } = useAuth();

  const [firstName, setFirstName] = useState(user?.profile?.first_name || '');
  const [lastName, setLastName] = useState(user?.profile?.last_name || '');
  const [city, setCity] = useState(user?.profile?.city || 'Алматы');
  const [iin, setIin] = useState(user?.profile?.iin || '');
  const [avatarUrl, setAvatarUrl] = useState(user?.profile?.avatar_url || '');
  const [bio, setBio] = useState(user?.profile?.bio || '');
  const [bioKk, setBioKk] = useState(user?.profile?.bio_kk || '');
  const [hourlyRate, setHourlyRate] = useState(user?.profile?.hourly_rate || '');
  const [experienceYears, setExperienceYears] = useState(user?.profile?.experience_years || '');
  const [loading, setLoading] = useState(false);

  const [latitude, setLatitude] = useState(user?.profile?.latitude || null);
  const [longitude, setLongitude] = useState(user?.profile?.longitude || null);

  // Capture geolocal location bounds dynamically
  useEffect(() => {
    if (navigator.geolocation && !latitude && !longitude) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          setLatitude(position.coords.latitude);
          setLongitude(position.coords.longitude);
        },
        (error) => console.log('Geolocation permission denied:', error),
        { enableHighAccuracy: true }
      );
    }
  }, [latitude, longitude]);

  const [languages, setLanguages] = useState(user?.profile?.languages || ['ru']);
  const [skills, setSkills] = useState(user?.profile?.skills || []);

  const toggleLang = (code) => {
    setLanguages((prev) => 
      prev.includes(code) ? prev.filter((c) => c !== code) : [...prev, code]
    );
  };

  const toggleSkill = (code) => {
    setSkills((prev) => 
      prev.includes(code) ? prev.filter((c) => c !== code) : [...prev, code]
    );
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setLoading(true);

    const payload = {
      first_name: firstName,
      last_name: lastName,
      city: city,
      iin: iin || null,
      avatar_url: avatarUrl || null,
      bio: bio || null,
      bio_kk: bioKk || null,
      hourly_rate: hourlyRate ? parseInt(hourlyRate) : null,
      experience_years: experienceYears ? parseInt(experienceYears) : null,
      languages,
      skills,
    };

    if (latitude && longitude) {
      payload.latitude = parseFloat(latitude);
      payload.longitude = parseFloat(longitude);
    }

    try {
      const response = await profileApi.updateProfile(payload);
      updateUser(response.user);
      showToast('success', 'Профиль успешно сохранен!');
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
          <h1>{t('profile.title')}</h1>
          <span className={styles.phoneLabel}>{user?.phone}</span>
        </div>

        {user?.role === 'nanny' && (
          <div className={`${styles.statusCard} ${user.profile?.is_verified ? styles.verified : styles.unverified} glass`}>
            {user.profile?.is_verified ? (
              <>
                <ShieldCheck size={28} className={styles.verifiedIcon} />
                <div>
                  <h4>{t('profile.verified')}</h4>
                  <p>Документы проверены модератором.</p>
                </div>
              </>
            ) : (
              <>
                <ShieldAlert size={28} className={styles.unverifiedIcon} />
                <div>
                  <h4>{t('profile.not_verified')}</h4>
                  <p>Загрузите документы для получения заказов.</p>
                </div>
              </>
            )}
          </div>
        )}

        <form onSubmit={handleSave} className={styles.form}>
          <Card>
            <div style={{ display: 'flex', alignItems: 'center', gap: '16px', marginBottom: '20px' }}>
              <div style={{ width: '64px', height: '64px', borderRadius: '50%', background: 'linear-gradient(135deg, #FF7A59 0%, #FF5252 100%)', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 'bold', fontSize: '1.5rem', overflow: 'hidden', flexShrink: 0, boxShadow: '0 4px 12px rgba(255, 122, 89, 0.3)' }}>
                {avatarUrl ? (
                  <img src={avatarUrl} alt="Avatar" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                ) : (
                  firstName?.[0] || 'П'
                )}
              </div>
              {user?.role === 'nanny' && (
                <div style={{ flex: 1 }}>
                  <Input
                    label="URL фотографии / аватара"
                    value={avatarUrl}
                    onChange={(e) => setAvatarUrl(e.target.value)}
                    placeholder="https://..."
                  />
                </div>
              )}
            </div>

            <Input
              label={t('profile.first_name')}
              value={firstName}
              onChange={(e) => setFirstName(e.target.value)}
              required
            />
            <Input
              label={t('profile.last_name')}
              value={lastName}
              onChange={(e) => setLastName(e.target.value)}
              required
            />
            <div style={{ marginBottom: '16px' }}>
              <label style={{ display: 'block', fontSize: '0.85rem', fontWeight: 600, marginBottom: '6px', color: 'var(--color-text-primary)' }}>
                {t('profile.city')}
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

            {user?.role === 'nanny' && (
              <>
                <Input
                  label={t('profile.iin')}
                  value={iin}
                  onChange={(e) => {
                    const val = e.target.value.replace(/\D/g, '').slice(0, 12);
                    setIin(val);
                  }}
                  placeholder="123456789012"
                />
                <Input
                  label={t('profile.bio_ru')}
                  value={bio}
                  onChange={(e) => setBio(e.target.value)}
                  multiline
                  rows={3}
                />
                <Input
                  label={t('profile.bio_kk')}
                  value={bioKk}
                  onChange={(e) => setBioKk(e.target.value)}
                  multiline
                  rows={3}
                />
              </>
            )}

            {user?.role === 'nanny' && (
              <>
                <div className={styles.row}>
                  <Input
                    label={t('profile.hourly_rate')}
                    type="number"
                    value={hourlyRate}
                    onChange={(e) => setHourlyRate(e.target.value)}
                  />
                  <Input
                    label={t('profile.experience')}
                    type="number"
                    value={experienceYears}
                    onChange={(e) => setExperienceYears(e.target.value)}
                  />
                </div>

                <div style={{ marginTop: '1rem' }}>
                  <label style={{ display: 'block', fontSize: '0.8rem', fontWeight: 600, marginBottom: '0.5rem', color: 'var(--color-text-muted)' }}>
                    Языки общения
                  </label>
                  <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                    {[
                      { code: 'kk', label: 'Қазақша' },
                      { code: 'ru', label: 'Русский' },
                      { code: 'en', label: 'English' },
                    ].map((lang) => (
                      <button
                        key={lang.code}
                        type="button"
                        onClick={() => toggleLang(lang.code)}
                        style={{
                          padding: '0.4rem 0.75rem',
                          borderRadius: '20px',
                          fontSize: '0.8rem',
                          border: '1px solid ' + (languages.includes(lang.code) ? 'var(--color-primary)' : 'var(--color-border)'),
                          background: languages.includes(lang.code) ? 'rgba(255,122,89,0.15)' : 'var(--color-surface)',
                          color: languages.includes(lang.code) ? 'var(--color-primary)' : 'var(--color-text)',
                          fontWeight: languages.includes(lang.code) ? '700' : '500',
                          cursor: 'pointer',
                        }}
                      >
                        {lang.label}
                      </button>
                    ))}
                  </div>
                </div>

                <div style={{ marginTop: '1rem' }}>
                  <label style={{ display: 'block', fontSize: '0.8rem', fontWeight: 600, marginBottom: '0.5rem', color: 'var(--color-text-muted)' }}>
                    {t('skills.title')}
                  </label>
                  <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                    {['first_aid', 'infants', 'lessons', 'montessori'].map((code) => (
                      <button
                        key={code}
                        type="button"
                        onClick={() => toggleSkill(code)}
                        style={{
                          padding: '0.4rem 0.75rem',
                          borderRadius: '20px',
                          fontSize: '0.8rem',
                          border: '1px solid ' + (skills.includes(code) ? 'var(--color-primary)' : 'var(--color-border)'),
                          background: skills.includes(code) ? 'rgba(255,122,89,0.15)' : 'var(--color-surface)',
                          color: skills.includes(code) ? 'var(--color-primary)' : 'var(--color-text)',
                          fontWeight: skills.includes(code) ? '700' : '500',
                          cursor: 'pointer',
                        }}
                      >
                        {t(`skills.${code}`)}
                      </button>
                    ))}
                  </div>
                </div>
              </>
            )}
          </Card>

          <Button type="submit" fullWidth loading={loading}>
            {t('profile.save')}
          </Button>

          <Button type="button" variant="danger" fullWidth onClick={logout}>
            <LogOut size={16} />
            {t('profile.logout')}
          </Button>
        </form>
      </div>
    </PageTransition>
  );
}
