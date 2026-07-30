import React from 'react';
import { useTranslation } from 'react-i18next';

const STEPS = [0.5, 1, 2, 3, 5, 8, 12];

export default function RadiusSlider({ value, onChange }) {
  const { t } = useTranslation();

  const currentIndex = STEPS.indexOf(value) !== -1 ? STEPS.indexOf(value) : 4; // default to 5km if not found

  const formatDistanceLabel = (val) => {
    if (val === 0.5) return '500 м';
    return `${val} км`;
  };

  const getStepDescription = (val) => {
    switch (val) {
      case 0.5: return 'Рядом (пешком)';
      case 1: return 'Соседние кварталы';
      case 2: return 'В пределах района';
      case 3: return '10 минут на машине';
      case 5: return 'Средняя дистанция';
      case 8: return 'Широкий поиск';
      case 12: return 'Весь город';
      default: return '';
    }
  };

  return (
    <div style={{ margin: '15px 0', padding: '15px', background: 'var(--color-surface)', borderRadius: '12px', border: '1px solid var(--color-border)' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px', fontSize: '0.9rem', fontWeight: 650 }}>
        <span>{t('search.radius')}</span>
        <span style={{ color: 'var(--color-primary)' }}>
          {formatDistanceLabel(STEPS[currentIndex])}
        </span>
      </div>
      <div style={{ fontSize: '0.75rem', color: 'var(--color-text-muted)', marginBottom: '10px', fontStyle: 'italic' }}>
        {getStepDescription(STEPS[currentIndex])}
      </div>
      <input
        type="range"
        min="0"
        max={STEPS.length - 1}
        step="1"
        value={currentIndex}
        onChange={(e) => onChange(STEPS[parseInt(e.target.value)])}
        style={{
          accentColor: 'var(--color-primary)',
          cursor: 'pointer',
          height: '6px',
          width: '100%'
        }}
      />
      <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.7rem', color: 'var(--color-text-muted)', marginTop: '6px', fontWeight: 500 }}>
        <span>500 м</span>
        <span>2 км</span>
        <span>5 км</span>
        <span>12 км</span>
      </div>
    </div>
  );
}
