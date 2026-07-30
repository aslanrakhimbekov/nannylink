import React from 'react';
import styles from './ComplimentSelector.module.css';

export const COMPLIMENT_OPTIONS = [
  { id: 'punctual', label: '⏰ Пунктуальная' },
  { id: 'finds_common_ground', label: '❤️ Находит подход' },
  { id: 'clean', label: '✨ Чистоплотная' },
  { id: 'active_games', label: '🎨 Развивающие игры' },
  { id: 'polite', label: '🤝 Вежливая' },
  { id: 'cooks', label: '🍳 Готовит еду' },
];

export default function ComplimentSelector({ selected = [], onChange }) {
  const toggleTag = (id) => {
    if (selected.includes(id)) {
      onChange(selected.filter((item) => item !== id));
    } else {
      onChange([...selected, id]);
    }
  };

  return (
    <div className={styles.container}>
      <label className={styles.label}>Что вам больше всего понравилось? (Достоинства)</label>
      <div className={styles.tagsGrid}>
        {COMPLIMENT_OPTIONS.map((tag) => {
          const isSelected = selected.includes(tag.id);
          return (
            <button
              key={tag.id}
              type="button"
              className={`${styles.tagBtn} ${isSelected ? styles.selectedTag : ''}`}
              onClick={() => toggleTag(tag.id)}
            >
              {tag.label}
            </button>
          );
        })}
      </div>
    </div>
  );
}
