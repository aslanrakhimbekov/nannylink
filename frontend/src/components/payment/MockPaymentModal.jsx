import React, { useState } from 'react';
import { escrowApi } from '../../api/escrow';
import Button from '../common/Button';
import styles from './MockPaymentModal.module.css';

export default function MockPaymentModal({ booking, onClose, onSuccess }) {
  const [method, setMethod] = useState('kaspi_qr'); // 'kaspi_qr' or 'card'
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [cardName, setCardName] = useState('ASLAN SAMATOV');
  const [cardNumber, setCardNumber] = useState('4400 1234 5678 9012');
  const [expiry, setExpiry] = useState('12/28');
  const [cvv, setCvv] = useState('777');

  const handlePay = async () => {
    setError('');
    setLoading(true);
    try {
      await escrowApi.payBooking(booking.id, method === 'kaspi_qr' ? 'kaspi_qr_mock' : 'card_mock');
      onSuccess();
    } catch (err) {
      setError(err.message || 'Ошибка оплаты');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className={styles.overlay} onClick={onClose}>
      <div className={styles.modal} onClick={(e) => e.stopPropagation()}>
        <div className={styles.header}>
          <h3>💰 Оплата бронирования (Escrow)</h3>
          <button className={styles.closeBtn} onClick={onClose}>✕</button>
        </div>

        <p className={styles.subtitle}>
          Сумма: <strong>{booking.total_price} ₸</strong>. Деньги будут заморожены в Escrow до завершения работы няни.
        </p>

        <div className={styles.tabs}>
          <button
            className={`${styles.tab} ${method === 'kaspi_qr' ? styles.activeTab : ''}`}
            onClick={() => setMethod('kaspi_qr')}
          >
            🟡 Kaspi QR (Тест)
          </button>
          <button
            className={`${styles.tab} ${method === 'card' ? styles.activeTab : ''}`}
            onClick={() => setMethod('card')}
          >
            💳 Банковская карта
          </button>
        </div>

        {method === 'kaspi_qr' ? (
          <div className={styles.qrContainer}>
            <div className={styles.qrBox}>
              <div className={styles.qrMockCode}>
                <span className={styles.qrLogo}>Kaspi.kz</span>
                <div className={styles.qrGrid}></div>
                <span className={styles.qrText}>Отсканируйте в приложении Kaspi</span>
              </div>
            </div>
            <p className={styles.hint}>
              Для теста нажмите кнопку ниже, чтобы сымитировать успешное сканирование и оплату.
            </p>
          </div>
        ) : (
          <div className={styles.cardForm}>
            <div className={styles.inputGroup}>
              <label>Номер карты</label>
              <input
                type="text"
                value={cardNumber}
                onChange={(e) => setCardNumber(e.target.value)}
                maxLength={19}
              />
            </div>
            <div className={styles.cardRow}>
              <div className={styles.inputGroup}>
                <label>Срок действия</label>
                <input
                  type="text"
                  value={expiry}
                  onChange={(e) => setExpiry(e.target.value)}
                  maxLength={5}
                />
              </div>
              <div className={styles.inputGroup}>
                <label>CVV/CVC</label>
                <input
                  type="password"
                  value={cvv}
                  onChange={(e) => setCvv(e.target.value)}
                  maxLength={3}
                />
              </div>
            </div>
            <div className={styles.inputGroup}>
              <label>Имя на карте</label>
              <input
                type="text"
                value={cardName}
                onChange={(e) => setCardName(e.target.value)}
              />
            </div>
          </div>
        )}

        {error && <div className="error-message">{error}</div>}

        <div className={styles.actions}>
          <Button variant="secondary" onClick={onClose}>Отмена</Button>
          <Button variant="primary" loading={loading} onClick={handlePay}>
            Оплатить {booking.total_price} ₸
          </Button>
        </div>
      </div>
    </div>
  );
}
