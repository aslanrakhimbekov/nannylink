import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { bookingsApi } from '../../api/bookings';
import { reviewsApi } from '../../api/reviews';
import Card from '../../components/common/Card';
import Button from '../../components/common/Button';
import MockPaymentModal from '../../components/payment/MockPaymentModal';
import ChatModal from '../../components/chat/ChatModal';
import ComplimentSelector from '../../components/reviews/ComplimentSelector';
import styles from './Bookings.module.css';

export default function ParentBookings() {
  const { t } = useTranslation();
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  // Modals state
  const [payModal, setPayModal] = useState(null);
  const [chatModal, setChatModal] = useState(null);

  // Cancel modal state
  const [cancelModal, setCancelModal] = useState(null);
  const [cancelComment, setCancelComment] = useState('');
  const [cancelLoading, setCancelLoading] = useState(false);
  const [cancelError, setCancelError] = useState('');

  // Review modal state
  const [reviewModal, setReviewModal] = useState(null);
  const [reviewRating, setReviewRating] = useState(5);
  const [reviewComment, setReviewComment] = useState('');
  const [selectedCompliments, setSelectedCompliments] = useState([]);
  const [reviewLoading, setReviewLoading] = useState(false);
  const [reviewError, setReviewError] = useState('');
  const [reviewSuccess, setReviewSuccess] = useState('');

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

  const handleCancelClick = (booking) => {
    if (booking.status === 'confirmed') {
      setCancelModal(booking);
      setCancelComment('');
      setCancelError('');
    } else {
      if (!window.confirm(t('bookings.cancel_confirm'))) return;
      performCancel(booking.id);
    }
  };

  const performCancel = async (id, comment) => {
    setCancelLoading(true);
    setCancelError('');
    try {
      await bookingsApi.cancelBooking(id, comment);
      setCancelModal(null);
      fetchBookings();
    } catch (err) {
      const errorMsg = err.errors?.cancellation_comment?.[0] || err.message || t('common.error');
      if (cancelModal) setCancelError(errorMsg);
      else alert(errorMsg);
    } finally {
      setCancelLoading(false);
    }
  };

  const handleCancelSubmit = () => {
    if (cancelComment.trim().length < 10) {
      setCancelError('Комментарий должен быть не менее 10 символов.');
      return;
    }
    performCancel(cancelModal.id, cancelComment);
  };

  const handleReviewSubmit = async () => {
    setReviewError('');
    setReviewSuccess('');
    if (reviewComment.trim().length < 5) {
      setReviewError('Комментарий должен быть не менее 5 символов.');
      return;
    }
    setReviewLoading(true);
    try {
      await reviewsApi.createReview({
        booking_id: reviewModal.id,
        rating: reviewRating,
        comment: reviewComment,
        compliments: selectedCompliments,
      });
      setReviewSuccess('Отзыв успешно отправлен!');
      setTimeout(() => {
        setReviewModal(null);
        setReviewSuccess('');
        fetchBookings();
      }, 1500);
    } catch (err) {
      setReviewError(err.errors?.booking_id?.[0] || err.errors?.comment?.[0] || err.message || t('common.error'));
    } finally {
      setReviewLoading(false);
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
            const canCancel = ['pending', 'confirmed'].includes(booking.status);
            const isConfirmed = booking.status === 'confirmed';
            const isCompleted = booking.status === 'completed';
            const nannyName = booking.nanny?.profile 
              ? `${booking.nanny.profile.first_name} ${booking.nanny.profile.last_name}`
              : 'Няня';

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
                    <strong>Няня:</strong> {nannyName}
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
                      <p>{t('search.phone')}: {booking.nanny?.phone || 'Не указан'}</p>
                      {booking.nanny?.telegram_username && (
                        <p>{t('search.telegram')}: <a href={`https://t.me/${booking.nanny.telegram_username}`} target="_blank" rel="noreferrer">@{booking.nanny.telegram_username}</a></p>
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
                    <Button onClick={() => setPayModal(booking)} variant="primary" className={styles.btn}>
                      💳 Оплата (Escrow)
                    </Button>
                  )}

                  {canCancel && (
                    <Button onClick={() => handleCancelClick(booking)} variant="danger" className={styles.btn}>
                      {t('bookings.cancel_btn')}
                    </Button>
                  )}

                  {(isConfirmed || isCompleted) && (
                    <Button
                      onClick={() => {
                        setReviewModal(booking);
                        setReviewRating(5);
                        setReviewComment('');
                        setSelectedCompliments([]);
                        setReviewError('');
                        setReviewSuccess('');
                      }}
                      variant="primary"
                      className={styles.btn}
                    >
                      ⭐ Оставить отзыв
                    </Button>
                  )}
                </div>
              </Card>
            );
          })}
        </div>
      )}

      {/* Pay Escrow Modal */}
      {payModal && (
        <MockPaymentModal
          booking={payModal}
          onClose={() => setPayModal(null)}
          onSuccess={() => {
            setPayModal(null);
            fetchBookings();
          }}
        />
      )}

      {/* Chat Modal */}
      {chatModal && (
        <ChatModal
          booking={chatModal}
          onClose={() => setChatModal(null)}
        />
      )}

      {/* Cancel Modal */}
      {cancelModal && (
        <div className={styles.modalOverlay} onClick={() => setCancelModal(null)}>
          <div className={styles.modal} onClick={e => e.stopPropagation()}>
            <h3>Отмена бронирования</h3>
            <p className={styles.modalSubtitle}>Это бронирование уже подтверждено. Пожалуйста, укажите причину отмены (мин. 10 символов).</p>
            <textarea
              className={styles.textarea}
              placeholder="Опишите причину отмены..."
              value={cancelComment}
              onChange={(e) => setCancelComment(e.target.value)}
              rows={4}
            />
            {cancelError && <div className="error-message">{cancelError}</div>}
            <div className={styles.modalActions}>
              <Button onClick={() => setCancelModal(null)} variant="secondary">Назад</Button>
              <Button onClick={handleCancelSubmit} variant="danger" disabled={cancelLoading}>
                {cancelLoading ? t('common.loading') : 'Отменить бронь'}
              </Button>
            </div>
          </div>
        </div>
      )}

      {/* Review Modal */}
      {reviewModal && (
        <div className={styles.modalOverlay} onClick={() => setReviewModal(null)}>
          <div className={styles.modal} onClick={e => e.stopPropagation()}>
            <h3>⭐ Оставить отзыв</h3>
            <p className={styles.modalSubtitle}>Оцените работу няни</p>
            <div className={styles.starsSelect}>
              {[1, 2, 3, 4, 5].map(star => (
                <button
                  key={star}
                  type="button"
                  className={`${styles.starBtn} ${star <= reviewRating ? styles.starActive : ''}`}
                  onClick={() => setReviewRating(star)}
                >
                  ★
                </button>
              ))}
            </div>

            <ComplimentSelector
              selected={selectedCompliments}
              onChange={setSelectedCompliments}
            />

            <textarea
              className={styles.textarea}
              placeholder="Напишите комментарий (мин. 5 символов)..."
              value={reviewComment}
              onChange={(e) => setReviewComment(e.target.value)}
              rows={3}
            />
            {reviewError && <div className="error-message">{reviewError}</div>}
            {reviewSuccess && <div className="success-message">{reviewSuccess}</div>}
            <div className={styles.modalActions}>
              <Button onClick={() => setReviewModal(null)} variant="secondary">Назад</Button>
              <Button onClick={handleReviewSubmit} variant="primary" disabled={reviewLoading}>
                {reviewLoading ? t('common.loading') : 'Отправить'}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
