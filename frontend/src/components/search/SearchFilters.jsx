import React, { useState } from 'react';
import styles from './SearchFilters.module.css';

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

export default function SearchFilters({ filters, onChange, onReset }) {
  const [expanded, setExpanded] = useState(false);

  return (
    <div className={styles.filterCard}>
      <div className={styles.header} onClick={() => setExpanded(!expanded)}>
        <span className={styles.title}>🔍 Фильтры и подбор нянь ({filters.city || 'Все города'})</span>
        <span className={styles.toggle}>{expanded ? '▲ Свернуть' : '▼ Развернуть'}</span>
      </div>

      {expanded && (
        <div className={styles.body}>
          <div className={styles.row}>
            <div className={styles.group}>
              <label>🏙️ Город поиска</label>
              <select
                value={filters.city || ''}
                onChange={(e) => onChange('city', e.target.value)}
              >
                {CITIES.map((c) => (
                  <option key={c} value={c}>
                    {c}
                  </option>
                ))}
              </select>
            </div>

            <div className={styles.group}>
              <label>Язык общения</label>
              <select
                value={filters.language || ''}
                onChange={(e) => onChange('language', e.target.value)}
              >
                <option value="">Все языки</option>
                <option value="kk">Казахский (Қазақша)</option>
                <option value="ru">Русский</option>
                <option value="en">Английский (English)</option>
              </select>
            </div>
          </div>

          <div className={styles.row}>
            <div className={styles.group}>
              <label>Специальный навык</label>
              <select
                value={filters.skill || ''}
                onChange={(e) => onChange('skill', e.target.value)}
              >
                <option value="">Все навыки</option>
                <option value="first_aid">🚨 Первая помощь (CPR)</option>
                <option value="infants">👶 Работа с грудничками</option>
                <option value="lessons">📚 Помощь с уроками</option>
                <option value="montessori">🧩 Монтессори / Развитие</option>
              </select>
            </div>

            <div className={styles.group}>
              <label>Макс. ставка (₸/час)</label>
              <input
                type="number"
                placeholder="Например, 3000"
                value={filters.max_hourly_rate || ''}
                onChange={(e) => onChange('max_hourly_rate', e.target.value)}
              />
            </div>
          </div>

          <div className={styles.row}>
            <div className={styles.group}>
              <label>Мин. рейтинг</label>
              <select
                value={filters.min_rating || ''}
                onChange={(e) => onChange('min_rating', e.target.value)}
              >
                <option value="">Любой рейтинг</option>
                <option value="4.5">★ 4.5+</option>
                <option value="4.0">★ 4.0+</option>
                <option value="3.5">★ 3.5+</option>
              </select>
            </div>
          </div>

          <div className={styles.actions}>
            <button type="button" className={styles.resetBtn} onClick={onReset}>
              Сбросить фильтры
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
