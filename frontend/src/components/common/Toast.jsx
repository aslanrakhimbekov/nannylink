import React, { useState, useEffect } from 'react';
import { CheckCircle, AlertCircle, Info, X } from 'lucide-react';
import styles from './Toast.module.css';

export default function Toast() {
  const [toast, setToast] = useState(null);

  useEffect(() => {
    const handleToast = (e) => {
      const { type, message } = e.detail;
      setToast({ type, message });
    };

    window.addEventListener('nannylink_toast', handleToast);
    return () => window.removeEventListener('nannylink_toast', handleToast);
  }, []);

  useEffect(() => {
    if (toast) {
      const timer = setTimeout(() => {
        setToast(null);
      }, 4000);
      return () => clearTimeout(timer);
    }
  }, [toast]);

  if (!toast) return null;

  const icons = {
    success: <CheckCircle size={18} className={styles.successIcon} />,
    error: <AlertCircle size={18} className={styles.errorIcon} />,
    info: <Info size={18} className={styles.infoIcon} />,
  };

  return (
    <div className={`${styles.toast} glass fade-in`}>
      <div className={styles.content}>
        {icons[toast.type] || <Info size={18} />}
        <span className={styles.message}>{toast.message}</span>
      </div>
      <button className={styles.closeBtn} onClick={() => setToast(null)}>
        <X size={14} />
      </button>
    </div>
  );
}

// Global helper to show toast notifications from anywhere
export function showToast(type, message) {
  window.dispatchEvent(
    new CustomEvent('nannylink_toast', {
      detail: { type, message },
    })
  );
}
