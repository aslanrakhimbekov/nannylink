import React from 'react';
import styles from './SlotChip.module.css';

export default function SlotChip({ startTime, endTime, onDelete, onClick, selected, status }) {
  const formatTime = (isoString) => {
    const d = new Date(isoString);
    return d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
  };

  const durationHours = () => {
    const start = new Date(startTime);
    const end = new Date(endTime);
    const hours = (end - start) / (1000 * 60 * 60);
    return hours;
  };

  const isBooked = status === 'booked';

  return (
    <div
      className={`${styles.chip} ${selected ? styles.selected : ''} ${isBooked ? styles.booked : ''} ${onClick ? styles.clickable : ''}`}
      onClick={!isBooked && onClick ? onClick : undefined}
    >
      <div className={styles.timeRange}>
        <span className={styles.dot}>{isBooked ? '🔴' : '🟢'}</span>
        <span className={styles.time}>{formatTime(startTime)} — {formatTime(endTime)}</span>
        <span className={styles.duration}>{durationHours()}ч</span>
      </div>
      {onDelete && !isBooked && (
        <button
          className={styles.deleteBtn}
          onClick={(e) => { e.stopPropagation(); onDelete(); }}
          title="Удалить"
        >
          ✕
        </button>
      )}
    </div>
  );
}
