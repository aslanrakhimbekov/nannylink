import React, { useState, useEffect, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';
import { profileApi } from '../../api/profile';
import { slotsApi } from '../../api/slots';
import { showToast } from '../../components/common/Toast';
import Card from '../../components/common/Card';
import Button from '../../components/common/Button';
import MiniCalendar from '../../components/calendar/MiniCalendar';
import SlotChip from '../../components/calendar/SlotChip';
import styles from './Schedule.module.css';

export default function Schedule() {
  const { t } = useTranslation();
  const { user, updateUser } = useAuth();
  const [isActive, setIsActive] = useState(user?.profile?.is_active ?? true);

  const [slots, setSlots] = useState([]);
  const [selectedDate, setSelectedDate] = useState(null);
  const [startTime, setStartTime] = useState('09:00');
  const [endTime, setEndTime] = useState('18:00');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const handleToggleActive = async (newVal) => {
    if (!user?.profile?.is_verified && newVal) {
      showToast('error', 'Для включения показа пройдите верификацию всех 5 документов');
      return;
    }
    try {
      const res = await profileApi.updateProfile({ is_active: newVal });
      updateUser(res.user);
      setIsActive(newVal);
      showToast('success', newVal ? 'Профиль и график видны на карте!' : 'Профиль и график скрыты от родителей.');
    } catch (err) {
      showToast('error', err.message || 'Ошибка обновления статуса');
    }
  };

  const timeOptions = useMemo(() => {
    const times = [];
    for (let h = 0; h < 24; h++) {
      const hh = String(h).padStart(2, '0');
      times.push(`${hh}:00`);
      times.push(`${hh}:30`);
    }
    return times;
  }, []);

  const fetchSlots = async () => {
    try {
      const response = await slotsApi.getNannySlots();
      setSlots(response || []);
    } catch (err) {
      console.error(err);
    }
  };

  useEffect(() => {
    fetchSlots();
  }, []);

  // Build a Set of YYYY-MM-DD strings that have slots
  const highlightedDates = useMemo(() => {
    const dates = new Set();
    slots.forEach(slot => {
      const d = new Date(slot.start_time);
      const dateStr = d.getFullYear() + '-' +
        String(d.getMonth() + 1).padStart(2, '0') + '-' +
        String(d.getDate()).padStart(2, '0');
      dates.add(dateStr);
    });
    return dates;
  }, [slots]);

  // Get slots for the selected date
  const slotsForSelectedDate = useMemo(() => {
    if (!selectedDate) return [];
    const selDateStr = selectedDate.getFullYear() + '-' +
      String(selectedDate.getMonth() + 1).padStart(2, '0') + '-' +
      String(selectedDate.getDate()).padStart(2, '0');
    return slots.filter(slot => {
      const d = new Date(slot.start_time);
      const slotDateStr = d.getFullYear() + '-' +
        String(d.getMonth() + 1).padStart(2, '0') + '-' +
        String(d.getDate()).padStart(2, '0');
      return slotDateStr === selDateStr;
    });
  }, [slots, selectedDate]);

  const formatSelectedDate = () => {
    if (!selectedDate) return '';
    return selectedDate.toLocaleDateString('ru-RU', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
    });
  };

  const handleAddSlot = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    if (!selectedDate) {
      setError('Выберите дату на календаре.');
      setLoading(false);
      return;
    }

    const dateStr = selectedDate.getFullYear() + '-' +
      String(selectedDate.getMonth() + 1).padStart(2, '0') + '-' +
      String(selectedDate.getDate()).padStart(2, '0');

    const startDateTime = `${dateStr}T${startTime}:00`;
    const endDateTime = `${dateStr}T${endTime}:00`;

    // Client-side min 2h check
    const start = new Date(startDateTime);
    const end = new Date(endDateTime);
    const diffMins = (end - start) / 1000 / 60;

    if (diffMins < 120) {
      setError(t('schedule.error_min_duration'));
      setLoading(false);
      return;
    }

    try {
      await slotsApi.createNannySlot(startDateTime, endDateTime);
      setSuccess(t('schedule.success'));
      setStartTime('09:00');
      setEndTime('18:00');
      fetchSlots();
    } catch (err) {
      setError(err.errors?.start_time?.[0] || err.errors?.end_time?.[0] || err.message || t('errors.validation'));
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteSlot = async (id) => {
    if (!window.confirm(t('common.delete') + '?')) return;
    try {
      await slotsApi.deleteNannySlot(id);
      fetchSlots();
    } catch (err) {
      setError(t('common.error'));
    }
  };

  return (
    <div className="container">
      <Card className={styles.scheduleCard}>
        <h2>{t('schedule.title')}</h2>
        <p className={styles.subtitle}>Выберите дату на календаре и добавьте свободные часы</p>

        {/* Profile Visibility & Work Switch */}
        <div style={{ padding: '16px 20px', borderRadius: '16px', background: isActive && user?.profile?.is_verified ? 'rgba(16, 185, 129, 0.08)' : 'rgba(239, 68, 68, 0.08)', border: '1px solid ' + (isActive && user?.profile?.is_verified ? 'rgba(16, 185, 129, 0.25)' : 'rgba(239, 68, 68, 0.25)'), margin: '16px 0 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '12px' }}>
          <div>
            <strong style={{ fontSize: '0.95rem', color: 'var(--color-text-primary)', display: 'block' }}>
              {isActive && user?.profile?.is_verified ? '🟢 Профиль активен (Виден на карте)' : '🔴 Профиль скрыт'}
            </strong>
            <span style={{ fontSize: '0.82rem', color: 'var(--color-text-muted)', display: 'block', marginTop: '2px' }}>
              {!user?.profile?.is_verified
                ? '⚠️ Для включения требуется верификация всех 5 документов'
                : isActive 
                ? 'Ваш график и профиль видны родителям на карте' 
                : 'Вы временно скрыли профиль от родителей (например, заболели)'}
            </span>
          </div>

          <label style={{ position: 'relative', display: 'inline-block', width: '52px', height: '28px', cursor: user?.profile?.is_verified ? 'pointer' : 'not-allowed', flexShrink: 0 }}>
            <input 
              type="checkbox" 
              checked={isActive && !!user?.profile?.is_verified} 
              disabled={!user?.profile?.is_verified}
              onChange={(e) => handleToggleActive(e.target.checked)}
              style={{ opacity: 0, width: 0, height: 0 }}
            />
            <span style={{
              position: 'absolute', cursor: user?.profile?.is_verified ? 'pointer' : 'not-allowed', top: 0, left: 0, right: 0, bottom: 0,
              backgroundColor: isActive && user?.profile?.is_verified ? '#10B981' : '#CBD5E1',
              transition: '.3s', borderRadius: '34px'
            }}>
              <span style={{
                position: 'absolute', content: '""', height: '20px', width: '20px', left: isActive && user?.profile?.is_verified ? '26px' : '4px', bottom: '4px',
                backgroundColor: 'white', transition: '.3s', borderRadius: '50%', boxShadow: '0 2px 4px rgba(0,0,0,0.2)'
              }} />
            </span>
          </label>
        </div>

        <MiniCalendar
          value={selectedDate}
          onChange={(date) => { setSelectedDate(date); setError(''); setSuccess(''); }}
          highlightedDates={highlightedDates}
          minDate={new Date()}
          disableNonHighlighted={false}
        />

        {selectedDate && (
          <>
            <div className={styles.selectedDateLabel}>
              📅 {formatSelectedDate()}
            </div>

            {slotsForSelectedDate.length > 0 && (
              <div className={styles.slotsForDay}>
                <label className={styles.sectionLabel}>Слоты на этот день:</label>
                <div className={styles.chipList}>
                  {slotsForSelectedDate.map(slot => (
                    <SlotChip
                      key={slot.id}
                      startTime={slot.start_time}
                      endTime={slot.end_time}
                      status={slot.status}
                      onDelete={slot.status !== 'booked' ? () => handleDeleteSlot(slot.id) : null}
                    />
                  ))}
                </div>
              </div>
            )}

            <form onSubmit={handleAddSlot} className={styles.form}>
              <label className={styles.sectionLabel}>➕ Добавить свободные часы:</label>
              <div className={styles.row}>
                <div className={styles.inputGroup}>
                  <label>С</label>
                  <select required value={startTime} onChange={(e) => setStartTime(e.target.value)}>
                    {timeOptions.map(t => (
                      <option key={t} value={t}>{t}</option>
                    ))}
                  </select>
                </div>
                <div className={styles.inputGroup}>
                  <label>До</label>
                  <select required value={endTime} onChange={(e) => setEndTime(e.target.value)}>
                    {timeOptions.map(t => (
                      <option key={t} value={t}>{t}</option>
                    ))}
                  </select>
                </div>
              </div>

              {error && <div className="error-message">{error}</div>}
              {success && <div className="success-message">{success}</div>}

              <Button type="submit" disabled={loading}>
                {loading ? t('common.loading') : t('schedule.add_slot')}
              </Button>
            </form>
          </>
        )}
      </Card>
    </div>
  );
}
