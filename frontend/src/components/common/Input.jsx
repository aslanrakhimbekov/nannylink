import React from 'react';
import styles from './Input.module.css';

export default function Input({ label, type = 'text', name, value, onChange, placeholder, required = false, error, multiline = false, rows = 4, ...rest }) {
  return (
    <div className={styles.group}>
      {label && (
        <label className={styles.label}>
          {label} {required && <span className={styles.req}>*</span>}
        </label>
      )}

      {multiline ? (
        <textarea
          name={name}
          value={value}
          onChange={onChange}
          placeholder={placeholder}
          rows={rows}
          className={`${styles.input} ${error ? styles.errorInput : ''}`}
          {...rest}
        />
      ) : (
        <input
          type={type}
          name={name}
          value={value}
          onChange={onChange}
          placeholder={placeholder}
          className={`${styles.input} ${error ? styles.errorInput : ''}`}
          {...rest}
        />
      )}

      {error && <span className={styles.errorText}>{error}</span>}
    </div>
  );
}
