import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
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
  const { t } = useTranslation();
  const [expanded, setExpanded] = useState(false);

  return (
    <div className={styles.filterCard}>
      <div className={styles.header} onClick={() => setExpanded(!expanded)}>
        <span className={styles.title}>
          {t('filters.title')} ({filters.city || t('filters.all_cities')})
        </span>
        <span className={styles.toggle}>
          {expanded ? t('filters.collapse') : t('filters.expand')}
        </span>
      </div>

      {expanded && (
        <div className={styles.body}>
          <div className={styles.row}>
            <div className={styles.group}>
              <label>{t('filters.city')}</label>
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
              <label>{t('filters.language')}</label>
              <select
                value={filters.language || ''}
                onChange={(e) => onChange('language', e.target.value)}
              >
                <option value="">{t('filters.all_languages')}</option>
                <option value="kk">{t('languages_list.kk')}</option>
                <option value="ru">{t('languages_list.ru')}</option>
                <option value="en">{t('languages_list.en')}</option>
              </select>
            </div>
          </div>

          <div className={styles.row}>
            <div className={styles.group}>
              <label>{t('filters.skill')}</label>
              <select
                value={filters.skill || ''}
                onChange={(e) => onChange('skill', e.target.value)}
              >
                <option value="">{t('filters.all_skills')}</option>
                <option value="first_aid">{t('skills.first_aid')}</option>
                <option value="infants">{t('skills.infants')}</option>
                <option value="lessons">{t('skills.lessons')}</option>
                <option value="montessori">{t('skills.montessori')}</option>
              </select>
            </div>

            <div className={styles.group}>
              <label>{t('filters.max_rate')}</label>
              <input
                type="number"
                placeholder={t('filters.rate_placeholder')}
                value={filters.max_hourly_rate || ''}
                onChange={(e) => onChange('max_hourly_rate', e.target.value)}
              />
            </div>
          </div>

          <div className={styles.row}>
            <div className={styles.group}>
              <label>{t('filters.min_rating')}</label>
              <select
                value={filters.min_rating || ''}
                onChange={(e) => onChange('min_rating', e.target.value)}
              >
                <option value="">{t('filters.any_rating')}</option>
                <option value="4.5">★ 4.5+</option>
                <option value="4.0">★ 4.0+</option>
                <option value="3.5">★ 3.5+</option>
              </select>
            </div>
          </div>

          <div className={styles.actions}>
            <button type="button" className={styles.resetBtn} onClick={onReset}>
              {t('filters.reset')}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
