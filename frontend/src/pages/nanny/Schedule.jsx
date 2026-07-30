import React, { useState, useEffect, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { slotsApi } from '../../api/slots';
import Card from '../../components/common/Card';
import Button from '../../components/common/Button';
import MiniCalendar from '../../components/calendar/MiniCalendar';
import SlotChip from '../../components/calendar/SlotChip';
import styles from './Schedule.module.css';

export default function Schedule() {
  const { t } = useTranslation();
  const [slots, setSlots] = useState([]);
  const [selectedDate, setSelectedDate] = useState(null);
  const [startTime, setStartTime] = useState('09:00');
  const [endTime, setEndTime] = useState('18:00');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

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
