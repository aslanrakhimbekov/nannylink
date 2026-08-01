import React, { useState, useEffect, useMemo } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { slotsApi } from '../../api/slots';
import { bookingsApi } from '../../api/bookings';
import { reviewsApi } from '../../api/reviews';
import Card from '../../components/common/Card';
import Button from '../../components/common/Button';
import Input from '../../components/common/Input';
import MiniCalendar from '../../components/calendar/MiniCalendar';
import SlotChip from '../../components/calendar/SlotChip';
import LocationPicker from '../../components/map/LocationPicker';
import styles from './NannyDetail.module.css';

function StarRating({ rating, size = '1rem' }) {
  const stars = [];
  for (let i = 1; i <= 5; i++) {
    if (i <= Math.floor(rating)) {
      stars.push(<span key={i} style={{ color: '#f59e0b', fontSize: size }}>★</span>);
    } else if (i - 0.5 <= rating) {
      stars.push(<span key={i} style={{ color: '#f59e0b', fontSize: size }}>★</span>);
    } else {
      stars.push(<span key={i} style={{ color: '#d1d5db', fontSize: size }}>★</span>);
    }
  }
  return <span>{stars}</span>;
}

export default function NannyDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { t, i18n } = useTranslation();

  const [nanny, setNanny] = useState(null);
  const [slots, setSlots] = useState([]);
  const [selectedDate, setSelectedDate] = useState(null);
  const [selectedSlotId, setSelectedSlotId] = useState(null);
  const [startTime, setStartTime] = useState('');
  const [endTime, setEndTime] = useState('');
  const [address, setAddress] = useState('');
  const [location, setLocation] = useState({ latitude: 43.238949, longitude: 76.889709 });
  const [loading, setLoading] = useState(true);
  const [bookingLoading, setBookingLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Reviews
  const [reviewData, setReviewData] = useState({ average_rating: 4.5, total_reviews: 0, reviews: [] });

  const fetchNannyAndSlots = async () => {
    try {
      const slotsResponse = await slotsApi.getNannyPublicSlots(id);
      const fetchedSlots = slotsResponse || [];
      setSlots(fetchedSlots);

      if (fetchedSlots.length > 0 && fetchedSlots[0].nanny) {
        setNanny(fetchedSlots[0].nanny);
      } else {
        const nearbyResponse = await bookingsApi.getNearbyNannies(43.238949, 76.889709, 100);
        const match = nearbyResponse?.find(item => String(item.user_id) === String(id));
        if (match) setNanny(match);
      }
    } catch (err) {
      setError(t('common.error'));
    } finally {
      setLoading(false);
    }
  };

  const fetchReviews = async () => {
    try {
      const data = await reviewsApi.getNannyReviews(id);
      if (data) setReviewData(data);
    } catch (err) {
      console.error('Failed to fetch reviews', err);
    }
  };

  useEffect(() => {
    fetchNannyAndSlots();
    fetchReviews();
  }, [id]);

  // Build highlighted dates set
  const highlightedDates = useMemo(() => {
    const dates = new Set();
    slots.forEach(slot => {
      const d = new Date(slot.start_time);
      dates.add(d.getFullYear() + '-' +
        String(d.getMonth() + 1).padStart(2, '0') + '-' +
        String(d.getDate()).padStart(2, '0'));
    });
    return dates;
  }, [slots]);

  // Get slots for selected date
  const slotsForDate = useMemo(() => {
    if (!selectedDate) return [];
    const selStr = selectedDate.getFullYear() + '-' +
      String(selectedDate.getMonth() + 1).padStart(2, '0') + '-' +
      String(selectedDate.getDate()).padStart(2, '0');
    return slots.filter(slot => {
      const d = new Date(slot.start_time);
      return (d.getFullYear() + '-' +
        String(d.getMonth() + 1).padStart(2, '0') + '-' +
        String(d.getDate()).padStart(2, '0')) === selStr;
    });
  }, [slots, selectedDate]);

  const selectedSlot = slotsForDate.find(s => s.id === selectedSlotId);

  // Generate time options within the selected slot
  const generateTimeOptions = (startStr, endStr) => {
    const options = [];
    const current = new Date(startStr);
    const end = new Date(endStr);
    const mins = current.getMinutes();
    if (mins > 0 && mins < 30) current.setMinutes(30);
    else if (mins > 30) { current.setHours(current.getHours() + 1); current.setMinutes(0); }
    current.setSeconds(0); current.setMilliseconds(0);
    while (current <= end) {
      options.push(current.toTimeString().slice(0, 5));
      current.setMinutes(current.getMinutes() + 30);
    }
    return options;
  };

  const timeOptions = useMemo(() => {
    return selectedSlot ? generateTimeOptions(selectedSlot.start_time, selectedSlot.end_time) : [];
  }, [selectedSlot]);

  const startTimeOptions = useMemo(() => {
    return timeOptions.filter((_, idx) => idx <= timeOptions.length - 5);
  }, [timeOptions]);

  const endTimeOptions = useMemo(() => {
    const startIdx = timeOptions.indexOf(startTime);
    return startIdx !== -1 ? timeOptions.slice(startIdx + 4) : [];
  }, [timeOptions, startTime]);

  useEffect(() => {
    if (startTime && endTimeOptions.length > 0) {
      const currentEndIdx = timeOptions.indexOf(endTime);
      const startIdx = timeOptions.indexOf(startTime);
      if (currentEndIdx === -1 || currentEndIdx < startIdx + 4) {
        setEndTime(endTimeOptions[0]);
      }
    }
  }, [startTime, endTimeOptions, timeOptions, endTime]);

  // Calculate total price
  const totalPrice = useMemo(() => {
    if (!startTime || !endTime || !nanny) return 0;
    const nannyProfile = nanny?.profile || nanny;
    const rate = nannyProfile?.hourly_rate || 0;
    const [sh, sm] = startTime.split(':').map(Number);
    const [eh, em] = endTime.split(':').map(Number);
    const hours = (eh * 60 + em - sh * 60 - sm) / 60;
    return Math.ceil(hours * rate);
  }, [startTime, endTime, nanny]);

  const handleBooking = async (e) => {
    e.preventDefault();
    setError(''); setSuccess('');
    if (!selectedSlot) { setError('Выберите слот.'); return; }

    const slotDate = new Date(selectedSlot.start_time);
    const dateStr = slotDate.getFullYear() + '-' +
      String(slotDate.getMonth() + 1).padStart(2, '0') + '-' +
      String(slotDate.getDate()).padStart(2, '0');
    const startDateTime = `${dateStr}T${startTime}:00`;
    const endDateTime = `${dateStr}T${endTime}:00`;

    const start = new Date(startDateTime);
    const end = new Date(endDateTime);
    if ((end - start) / 1000 / 60 < 120) {
      setError(t('schedule.error_min_duration')); return;
    }

    setBookingLoading(true);
    try {
      await bookingsApi.createBooking({
        nanny_id: id,
        start_time: startDateTime,
        end_time: endDateTime,
        address_string: address,
        latitude: location.latitude,
        longitude: location.longitude,
      });
      setSuccess(t('search.respond_success'));
      setTimeout(() => navigate('/parent/bookings'), 1500);
    } catch (err) {
      setError(err.errors?.start_time?.[0] || err.errors?.end_time?.[0] || err.message || t('errors.validation'));
    } finally {
      setBookingLoading(false);
    }
  };

  if (loading) return <div className="loading">{t('common.loading')}</div>;

  const nannyProfile = nanny?.profile || nanny;

  return (
    <div className="container">
        {nannyProfile?.is_new_nanny && (
          <div style={{ padding: '14px 16px', borderRadius: '16px', background: 'linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(5, 150, 105, 0.18) 100%)', border: '1px solid rgba(16, 185, 129, 0.35)', marginBottom: '16px' }}>
            <div style={{ fontWeight: 700, color: '#059669', fontSize: '0.92rem', display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '4px' }}>
              {t('promo.banner_title')}
            </div>
            <p style={{ margin: 0, fontSize: '0.84rem', color: 'var(--color-text-primary)', lineHeight: 1.4 }}>
              {t('promo.banner_desc')}
            </p>
          </div>
        )}

        {/* Profile Card */}
        <Card className={styles.profileCard}>
          <div className={styles.header}>
            <div className={styles.avatarPlaceholder} style={{ padding: 0, overflow: 'hidden' }}>
              {nannyProfile?.avatar_url ? (
                <img src={nannyProfile.avatar_url} alt="" style={{ width: '100%', height: '100%', borderRadius: '50%', objectFit: 'cover' }} />
              ) : (
                nannyProfile?.first_name?.[0] || 'Н'
              )}
            </div>
            <div>
              <h2>{nannyProfile?.first_name} {nannyProfile?.last_name}</h2>
              <div className={styles.ratingRow}>
                <StarRating rating={reviewData.average_rating} />
                <span className={styles.ratingText}>
                  {reviewData.average_rating} ({reviewData.total_reviews} {reviewData.total_reviews === 1 ? 'отзыв' : 'отзывов'})
                </span>
              </div>
              {nannyProfile?.is_new_nanny ? (
                <div style={{ display: 'flex', alignItems: 'baseline', gap: '8px', marginTop: '4px' }}>
                  <span style={{ textDecoration: 'line-through', opacity: 0.5, fontSize: '0.9rem' }}>{nannyProfile.original_hourly_rate || nannyProfile.hourly_rate} {t('common.per_hour')}</span>
                  <span className={styles.rate} style={{ color: '#10B981', fontWeight: 800 }}>{nannyProfile.effective_hourly_rate} {t('common.per_hour')}</span>
                </div>
              ) : (
                <span className={styles.rate}>{nannyProfile?.hourly_rate} {t('common.per_hour')}</span>
              )}
            </div>
          </div>
        <p className={styles.bio}>
          {(i18n.language === 'kk' && nannyProfile?.bio_kk) ? nannyProfile.bio_kk : (nannyProfile?.bio || 'Сипаттамасы жоқ.')}
        </p>
        <div className={styles.experience}>
          <strong>{t('profile.experience_years')}:</strong> {nannyProfile?.experience_years} {t('common.years')}
        </div>

        {nannyProfile?.languages && nannyProfile.languages.length > 0 && (
          <div style={{ marginTop: '0.5rem' }}>
            <strong style={{ fontSize: '0.85rem' }}>{t('filters.language')}: </strong>
            <span style={{ fontSize: '0.85rem' }}>
              {nannyProfile.languages.map(l => t(`languages_list.${l}`, l)).join(', ')}
            </span>
          </div>
        )}

        {nannyProfile?.skills && nannyProfile.skills.length > 0 && (
          <div style={{ marginTop: '0.5rem', display: 'flex', gap: '4px', flexWrap: 'wrap' }}>
            {nannyProfile.skills.map(sk => (
              <span key={sk} style={{ fontSize: '0.75rem', padding: '3px 10px', borderRadius: '12px', background: 'rgba(255,122,89,0.12)', color: 'var(--color-primary)', fontWeight: 600 }}>
                {t(`skills.${sk}`, sk)}
              </span>
            ))}
          </div>
        )}

        {nannyProfile?.compliments_summary && Object.keys(nannyProfile.compliments_summary).length > 0 && (
          <div style={{ marginTop: '0.75rem', paddingTop: '0.75rem', borderTop: '1px solid var(--color-border)' }}>
            <strong style={{ fontSize: '0.85rem', display: 'block', marginBottom: '0.35rem' }}>{t('compliments.title')}</strong>
            <div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap' }}>
              {Object.entries(nannyProfile.compliments_summary).map(([tag, count]) => (
                <span key={tag} style={{ fontSize: '0.75rem', padding: '4px 10px', borderRadius: '14px', background: 'var(--color-surface)', border: '1px solid var(--color-border)', fontWeight: 600 }}>
                  {t(`compliments.${tag}`, tag)} <strong style={{ color: 'var(--color-primary)' }}>×{count}</strong>
                </span>
              ))}
            </div>
          </div>
        )}
      </Card>

      {/* Booking Card */}
      <Card className={styles.bookingCard}>
        <h3>{t('search.book_nanny')}</h3>
        <p className={styles.subtitle}>{t('search.min_booking')}</p>

        {slots.length === 0 ? (
          <p className="text-center text-muted" style={{ padding: '1.5rem 0' }}>
            У этой няни сейчас нет свободных слотов.
          </p>
        ) : (
          <>
            <MiniCalendar
              value={selectedDate}
              onChange={(date) => { setSelectedDate(date); setSelectedSlotId(null); setStartTime(''); setEndTime(''); setError(''); }}
              highlightedDates={highlightedDates}
            />

            {selectedDate && slotsForDate.length > 0 && (
              <div className={styles.slotsSection}>
                <label className={styles.sectionLabel}>
                  Доступные слоты {selectedDate.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })}:
                </label>
                <div className={styles.slotsList}>
                  {slotsForDate.map(slot => (
                    <SlotChip
                      key={slot.id}
                      startTime={slot.start_time}
                      endTime={slot.end_time}
                      status={slot.status}
                      selected={selectedSlotId === slot.id}
                      onClick={() => {
                        setSelectedSlotId(slot.id);
                        const opts = generateTimeOptions(slot.start_time, slot.end_time);
                        if (opts.length >= 5) { setStartTime(opts[0]); setEndTime(opts[opts.length - 1]); }
                        else { setStartTime(''); setEndTime(''); }
                      }}
                    />
                  ))}
                </div>
              </div>
            )}

            {selectedSlot && (
              <form onSubmit={handleBooking} className={styles.form}>
                <div className={styles.row}>
                  <div className={styles.inputGroup}>
                    <label>Время начала</label>
                    <select required value={startTime} onChange={(e) => setStartTime(e.target.value)} className={styles.select}>
                      {startTimeOptions.map(t => (<option key={t} value={t}>{t}</option>))}
                    </select>
                  </div>
                  <div className={styles.inputGroup}>
                    <label>Время окончания</label>
                    <select required value={endTime} onChange={(e) => setEndTime(e.target.value)} className={styles.select}>
                      {endTimeOptions.map(t => (<option key={t} value={t}>{t}</option>))}
                    </select>
                  </div>
                </div>

                {totalPrice > 0 && (
                  <div className={styles.pricePreview}>
                    💰 Итого: <strong>{totalPrice} ₸</strong>
                  </div>
                )}

                <div className={styles.inputGroup}>
                  <label>Адрес</label>
                  <Input type="text" required placeholder="Например, мкр. Самал-2, д. 12" value={address} onChange={(e) => setAddress(e.target.value)} />
                </div>

                <div className={styles.inputGroup}>
                  <label>Укажите местоположение на карте</label>
                  <LocationPicker value={location} onChange={setLocation} height="200px" />
                </div>

                {error && <div className="error-message">{error}</div>}
                {success && <div className="success-message">{success}</div>}

                <Button type="submit" disabled={bookingLoading}>
                  {bookingLoading ? t('common.loading') : `${t('search.book')} — ${totalPrice} ₸`}
                </Button>
              </form>
            )}
          </>
        )}
      </Card>

      {/* Reviews Card */}
      {reviewData.reviews.length > 0 && (
        <Card className={styles.reviewsCard}>
          <h3>Отзывы</h3>
          <div className={styles.reviewsSummary}>
            <StarRating rating={reviewData.average_rating} size="1.3rem" />
            <span className={styles.ratingBig}>{reviewData.average_rating}</span>
            <span className={styles.reviewCount}>{reviewData.total_reviews} отзывов</span>
          </div>
          <div className={styles.reviewsList}>
            {reviewData.reviews.map(review => (
              <div key={review.id} className={styles.reviewItem}>
                <div className={styles.reviewHeader}>
                  <span className={styles.reviewAuthor}>{review.author}</span>
                  <StarRating rating={review.rating} size="0.85rem" />
                </div>
                <p className={styles.reviewComment}>{review.comment}</p>
                <span className={styles.reviewDate}>
                  {new Date(review.created_at).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })}
                </span>
              </div>
            ))}
          </div>
        </Card>
      )}
    </div>
  );
}
