import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';
import { bookingsApi } from '../../api/bookings';
import { escrowApi } from '../../api/escrow';
import ChatModal from '../../components/chat/ChatModal';
import Card from '../../components/common/Card';
import Button from '../../components/common/Button';
import styles from './Bookings.module.css';

export default function NannyBookings() {
  const { t } = useTranslation();
  const { user } = useAuth();
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [chatModal, setChatModal] = useState(null);

  const isNewNanny = user?.profile?.is_new_nanny;

  const fetchBookings = async () => {
    try {
      const response = await bookingsApi.getBookings();
      setBookings(response || []);
    } catch (err) {
      setError(t('common.error'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchBookings();
  }, []);

  const handleConfirm = async (id) => {
    const msg = isNewNanny
      ? t('promo.nanny_free_confirm')
      : t('search.respond_cost') + '. Продолжить?';

    if (!window.confirm(msg)) return;
    try {
      await bookingsApi.confirmBooking(id);
      fetchBookings();
    } catch (err) {
      alert(err.errors?.balance_coins?.[0] || err.message || t('common.error'));
    }
  };

  const handleReject = async (id) => {
    if (!window.confirm(t('common.confirm') + '?')) return;
    try {
      await bookingsApi.rejectBooking(id);
      fetchBookings();
    } catch (err) {
      alert(t('common.error'));
    }
  };

  const handleComplete = async (id) => {
    if (!window.confirm('Завершить заказ и получить выплату?')) return;
    try {
      await escrowApi.completeBooking(id);
      fetchBookings();
    } catch (err) {
      alert(err.message || t('common.error'));
    }
  };

  const formatDateTime = (isoString) => {
    const d = new Date(isoString);
    return d.toLocaleString('ru-RU', {
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  if (loading) return <div className="loading">{t('common.loading')}</div>;

  return (
    <div className="container">
      <h2 style={{ marginBottom: '1.5rem' }}>{t('bookings.title')}</h2>
      
      {error && <div className="error-message">{error}</div>}

      {bookings.length === 0 ? (
        <div className="text-center text-muted" style={{ padding: '3rem' }}>
          {t('bookings.empty')}
        </div>
      ) : (
        <div className={styles.list}>
          {bookings.map((booking) => {
            const isPending = booking.status === 'pending';
            const isConfirmed = booking.status === 'confirmed';
            const isCompleted = booking.status === 'completed';
            const parentName = booking.parent?.profile 
              ? `${booking.parent.profile.first_name} ${booking.parent.profile.last_name}`
              : 'Родитель';

            return (
              <Card key={booking.id} className={styles.bookingCard}>
                <div className={styles.header}>
                  <span className={`${styles.statusBadge} ${styles[booking.status]}`}>
                    {t(`bookings.status_${booking.status}`)}
                  </span>
                  <span className={styles.price}>{booking.total_price} ₸</span>
                </div>

                <div className={styles.details}>
                  <div className={styles.item}>
                    <strong>Семья:</strong> {parentName}
                  </div>
                  <div className={styles.item}>
                    <strong>Время:</strong> {formatDateTime(booking.start_time)} — {formatDateTime(booking.end_time)}
                  </div>
                  <div className={styles.item}>
                    <strong>Адрес:</strong> {booking.address_string}
                  </div>

                  {isConfirmed && (
                    <div className={styles.contacts}>
                      <h4>{t('search.contact_info')}:</h4>
                      <p>{t('search.phone')}: {booking.parent?.phone || 'Не указан'}</p>
                      {booking.parent?.telegram_username && (
                        <p>{t('search.telegram')}: <a href={`https://t.me/${booking.parent.telegram_username}`} target="_blank" rel="noreferrer">@{booking.parent.telegram_username}</a></p>
                      )}
                    </div>
                  )}

                  {booking.cancellation_comment && (
                    <div className={styles.cancelComment}>
                      <strong>Причина отмены:</strong> {booking.cancellation_comment}
                    </div>
                  )}
                </div>

                <div className={styles.actions}>
                  {(isConfirmed || isCompleted) && (
                    <Button onClick={() => setChatModal(booking)} variant="secondary" className={styles.btn}>
                      💬 Чат
                    </Button>
                  )}

                  {isConfirmed && (
                    <Button onClick={() => handleComplete(booking.id)} variant="primary" className={styles.btn}>
                      ✅ Завершить работу
                    </Button>
                  )}

                  {isPending && (
                    <>
                      <Button onClick={() => handleConfirm(booking.id)} variant="primary" className={styles.btn}>
                        {t('bookings.confirm_btn')}
                      </Button>
                      <Button onClick={() => handleReject(booking.id)} variant="danger" className={styles.btn}>
                        {t('bookings.reject_btn')}
                      </Button>
                    </>
                  )}
                </div>
              </Card>
            );
          })}
        </div>
      )}

      {chatModal && (
        <ChatModal
          booking={chatModal}
          onClose={() => setChatModal(null)}
        />
      )}
    </div>
  );
}
