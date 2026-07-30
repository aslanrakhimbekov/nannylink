import React from 'react';
import styles from './Button.module.css';

export default function Button({ children, onClick, type = 'button', variant = 'primary', loading = false, disabled = false, fullWidth = false }) {
  const btnClass = `${styles.btn} ${styles[variant]} ${fullWidth ? styles.full : ''} ${loading ? styles.loadingState : ''}`;

  return (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled || loading}
      className={btnClass}
    >
      {loading ? (
        <span className={styles.spinner} aria-hidden="true"></span>
      ) : children}
    </button>
  );
}
