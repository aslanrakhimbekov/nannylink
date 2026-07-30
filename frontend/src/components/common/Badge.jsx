import React from 'react';
import { useTranslation } from 'react-i18next';

export default function Badge({ status }) {
  const { t } = useTranslation();

  const config = {
    open: { bg: 'rgba(0, 206, 201, 0.15)', color: 'var(--color-accent)', text: t('orders.status_open') },
    matched: { bg: 'rgba(108, 92, 231, 0.15)', color: 'var(--color-primary-light)', text: t('orders.status_matched') },
    completed: { bg: 'rgba(0, 184, 148, 0.15)', color: 'var(--color-success)', text: t('orders.status_completed') },
    cancelled: { bg: 'rgba(255, 118, 117, 0.15)', color: 'var(--color-danger)', text: t('orders.status_cancelled') },
    pending: { bg: 'rgba(253, 203, 110, 0.15)', color: 'var(--color-warning)', text: t('documents.status_pending') },
    approved: { bg: 'rgba(0, 184, 148, 0.15)', color: 'var(--color-success)', text: t('documents.status_approved') },
    rejected: { bg: 'rgba(255, 118, 117, 0.15)', color: 'var(--color-danger)', text: t('documents.status_rejected') },
  };

  const current = config[status] || { bg: 'var(--color-surface)', color: 'var(--color-text)', text: status };

  return (
    <span 
      style={{
        display: 'inline-flex',
        alignItems: 'center',
        padding: '4px 10px',
        borderRadius: '99px',
        fontSize: '0.75rem',
        fontWeight: '700',
        letterSpacing: '0.2px',
        backgroundColor: current.bg,
        color: current.color,
        border: `1px solid ${current.color}30`
      }}
    >
      {current.text}
    </span>
  );
}
