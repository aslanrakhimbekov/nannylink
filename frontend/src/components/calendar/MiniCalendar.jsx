import React from 'react';
import Calendar from 'react-calendar';
import 'react-calendar/dist/Calendar.css';
import styles from './MiniCalendar.module.css';

export default function MiniCalendar({ value, onChange, highlightedDates = new Set(), minDate, disableNonHighlighted = true }) {
  const tileClassName = ({ date, view }) => {
    if (view !== 'month') return null;
    const dateStr = date.getFullYear() + '-' +
      String(date.getMonth() + 1).padStart(2, '0') + '-' +
      String(date.getDate()).padStart(2, '0');
    if (highlightedDates.has(dateStr)) {
      return styles.highlightedDay;
    }
    return null;
  };

  const tileDisabled = ({ date, view }) => {
    if (view !== 'month') return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (date < today) return true;
    if (disableNonHighlighted && highlightedDates.size > 0) {
      const dateStr = date.getFullYear() + '-' +
        String(date.getMonth() + 1).padStart(2, '0') + '-' +
        String(date.getDate()).padStart(2, '0');
      if (!highlightedDates.has(dateStr)) return true;
    }
    return false;
  };

  return (
    <div className={styles.calendarWrapper}>
      <Calendar
        onChange={onChange}
        value={value}
        tileClassName={tileClassName}
        tileDisabled={tileDisabled}
        minDate={minDate || new Date()}
        locale="ru-RU"
        next2Label={null}
        prev2Label={null}
      />
    </div>
  );
}
