import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { bookingsApi } from '../../api/bookings';
import NannyMap from '../../components/map/NannyMap';
import RadiusSlider from '../../components/map/RadiusSlider';
import Card from '../../components/common/Card';
import Button from '../../components/common/Button';
import SearchFilters from '../../components/search/SearchFilters';
import styles from './Search.module.css';

export const CITY_COORDINATES = {
  'Алматы': { latitude: 43.238949, longitude: 76.889709 },
  'Астана': { latitude: 51.169392, longitude: 71.449074 },
  'Шымкент': { latitude: 42.3417, longitude: 69.5901 },
  'Караганда': { latitude: 49.8019, longitude: 73.1021 },
  'Актобе': { latitude: 50.2839, longitude: 57.1670 },
  'Тараз': { latitude: 42.9000, longitude: 71.3667 },
  'Павлодар': { latitude: 52.2873, longitude: 76.9673 },
  'Усть-Каменогорск': { latitude: 49.9500, longitude: 82.6167 },
  'Семей': { latitude: 50.4111, longitude: 80.2275 },
  'Атырау': { latitude: 47.1167, longitude: 51.8833 },
  'Костанай': { latitude: 53.2144, longitude: 63.6246 },
  'Кызылорда': { latitude: 44.8479, longitude: 65.5093 },
  'Уральск': { latitude: 51.2333, longitude: 51.3667 },
  'Петропавловск': { latitude: 54.8753, longitude: 69.1625 },
};

export default function Search() {
  const { t, i18n } = useTranslation();
  const navigate = useNavigate();
  const { user } = useAuth();

  const userCity = user?.profile?.city || 'Алматы';

  const [nannies, setNannies] = useState([]);
  const [radiusKm, setRadiusKm] = useState(2);
  const [filters, setFilters] = useState({ city: userCity });
  const [loading, setLoading] = useState(false);

  const defaultCoords = CITY_COORDINATES[filters.city] || CITY_COORDINATES[userCity] || CITY_COORDINATES['Алматы'];
  const [userLocation, setUserLocation] = useState(defaultCoords);
  const [error, setError] = useState('');

  // Update userLocation when selected city in filters changes
  useEffect(() => {
    const targetCity = filters.city || userCity;
    const coords = CITY_COORDINATES[targetCity] || CITY_COORDINATES['Алматы'];
    setUserLocation(coords);
  }, [filters.city, userCity]);

  const handleFilterChange = (key, value) => {
    setFilters((prev) => ({ ...prev, [key]: value }));
  };

  const handleFilterReset = () => {
    setFilters({ city: userCity });
  };

  const searchNannies = async () => {
    if (!userLocation) return;
    setLoading(true);
    setError('');
    try {
      const response = await bookingsApi.getNearbyNannies(
        userLocation.latitude,
        userLocation.longitude,
        radiusKm,
        filters
      );
      setNannies(response || []);
    } catch (err) {
      setError(t('common.error'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (userLocation) {
      searchNannies();
    }
  }, [userLocation, radiusKm, filters]);

  return (
    <div className="container">
      <div className={styles.searchHeader}>
        <h2>{t('search.title')}</h2>
        <RadiusSlider value={radiusKm} onChange={setRadiusKm} />
      </div>

      <SearchFilters
        filters={filters}
        onChange={handleFilterChange}
        onReset={handleFilterReset}
      />

      <div className={styles.mapContainer}>
        <NannyMap
          userLocation={userLocation}
          radiusKm={radiusKm}
          nannies={nannies}
          onNannyClick={(nanny) => navigate(`/parent/nanny/${nanny.user_id}`)}
          onUserLocationChange={setUserLocation}
          height="320px"
        />
      </div>

      {error && <div className="error-message">{error}</div>}

      <div className={styles.list}>
        {loading ? (
          <div className="text-center">{t('common.loading')}</div>
        ) : nannies.length === 0 ? (
          <div className="text-center text-muted" style={{ padding: '2rem' }}>
            {t('search.no_nannies')}
          </div>
        ) : (
          nannies.map((nanny) => (
            <Card
              key={nanny.id}
              className={styles.nannyCard}
              onClick={() => navigate(`/parent/nanny/${nanny.user_id}`)}
            >
              <div className={styles.avatar}>
                {nanny.avatar_url ? (
                  <img src={nanny.avatar_url} alt="Avatar" />
                ) : (
                  <div className={styles.avatarPlaceholder}>
                    {nanny.first_name[0]}
                  </div>
                )}
              </div>

              <div className={styles.info}>
                <h3>{nanny.first_name} {nanny.last_name}</h3>
                <div className={styles.ratingInline}>
                  <span style={{ color: '#f59e0b' }}>★</span>
                  <span className={styles.ratingValue}>{nanny.average_rating || 4.5}</span>
                </div>
                <p className={styles.bio}>
                  {(i18n.language === 'kk' && nanny.bio_kk) ? nanny.bio_kk : (nanny.bio || 'Сипаттамасы жоқ.')}
                </p>

                {nanny.languages && nanny.languages.length > 0 && (
                  <div style={{ display: 'flex', gap: '4px', flexWrap: 'wrap', marginTop: '6px' }}>
                    {nanny.languages.map((lang) => (
                      <span key={lang} style={{ fontSize: '0.7rem', padding: '2px 8px', borderRadius: '10px', background: 'rgba(255,255,255,0.06)', border: '1px solid rgba(255,255,255,0.1)' }}>
                        {lang === 'kk' ? '🇰🇿 Қаз' : lang === 'ru' ? '🇷🇺 Рус' : '🇬🇧 Eng'}
                      </span>
                    ))}
                  </div>
                )}

                {nanny.compliments_summary && Object.keys(nanny.compliments_summary).length > 0 && (
                  <div style={{ display: 'flex', gap: '4px', flexWrap: 'wrap', marginTop: '6px' }}>
                    {Object.entries(nanny.compliments_summary).slice(0, 2).map(([tag, count]) => (
                      <span key={tag} style={{ fontSize: '0.7rem', padding: '2px 8px', borderRadius: '10px', background: 'rgba(255, 122, 89, 0.1)', color: 'var(--color-primary)', fontWeight: 600 }}>
                        {t(`compliments.${tag}`, tag)} ×{count}
                      </span>
                    ))}
                  </div>
                )}

                {nanny.is_new_nanny && (
                  <div style={{ marginTop: '8px', display: 'inline-flex', alignItems: 'center', gap: '4px', padding: '3px 10px', borderRadius: '12px', background: 'linear-gradient(135deg, #10B981 0%, #059669 100%)', color: '#ffffff', fontSize: '0.74rem', fontWeight: 700, boxShadow: '0 2px 6px rgba(16, 185, 129, 0.25)' }}>
                    <span>{t('promo.new_nanny_badge')}</span>
                  </div>
                )}

                <div className={styles.meta}>
                  <span>{nanny.experience_years} {t('profile.experience_years_label')}</span>
                  {nanny.is_new_nanny ? (
                    <span className={styles.rate} style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', lineHeight: 1.2 }}>
                      <span style={{ textDecoration: 'line-through', opacity: 0.5, fontSize: '0.8rem', fontWeight: 500 }}>{nanny.original_hourly_rate || nanny.hourly_rate} {t('common.per_hour')}</span>
                      <span style={{ color: '#10B981', fontWeight: 800 }}>{nanny.effective_hourly_rate} {t('common.per_hour')}</span>
                    </span>
                  ) : (
                    <span className={styles.rate}>{nanny.hourly_rate} {t('common.per_hour')}</span>
                  )}
                </div>
              </div>

              <Button
                onClick={(e) => {
                  e.stopPropagation();
                  navigate(`/parent/nanny/${nanny.user_id}`);
                }}
                variant="primary"
                className={styles.bookBtn}
              >
                {t('search.book')}
              </Button>
            </Card>
          ))
        )}
      </div>
    </div>
  );
}
