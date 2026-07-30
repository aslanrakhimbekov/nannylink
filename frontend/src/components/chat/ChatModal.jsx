import React, { useState, useEffect, useRef } from 'react';
import { messagesApi } from '../../api/messages';
import { useAuth } from '../../context/AuthContext';
import Button from '../common/Button';
import styles from './ChatModal.module.css';

export default function ChatModal({ booking, onClose }) {
  const { user } = useAuth();
  const [messages, setMessages] = useState([]);
  const [text, setText] = useState('');
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const messagesEndRef = useRef(null);

  const fetchMessages = async () => {
    try {
      const data = await messagesApi.getMessages(booking.id);
      setMessages(data || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMessages();
    const interval = setInterval(fetchMessages, 3000); // Auto-poll every 3s
    return () => clearInterval(interval);
  }, [booking.id]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const handleSend = async (e) => {
    e.preventDefault();
    if (!text.trim()) return;
    setSending(true);
    try {
      const newMsg = await messagesApi.sendMessage(booking.id, text);
      setMessages((prev) => [...prev, newMsg]);
      setText('');
    } catch (err) {
      alert(err.message || 'Ошибка отправки');
    } finally {
      setSending(false);
    }
  };

  const otherUser = user.id === booking.parent_id ? booking.nanny : booking.parent;
  const otherName = otherUser?.profile 
    ? `${otherUser.profile.first_name} ${otherUser.profile.last_name}` 
    : 'Собеседник';

  return (
    <div className={styles.overlay} onClick={onClose}>
      <div className={styles.modal} onClick={(e) => e.stopPropagation()}>
        <div className={styles.header}>
          <div>
            <h3>💬 Чат с {otherName}</h3>
            <span className={styles.bookingRef}>Заказ №{booking.id}</span>
          </div>
          <button className={styles.closeBtn} onClick={onClose}>✕</button>
        </div>

        <div className={styles.messagesContainer}>
          {loading ? (
            <div className={styles.loading}>Загрузка сообщений...</div>
          ) : messages.length === 0 ? (
            <div className={styles.empty}>
              Сообщений пока нет. Напишите первый приветственный текст!
            </div>
          ) : (
            messages.map((msg) => {
              const isMine = msg.sender_id === user.id;
              const senderName = msg.sender?.profile?.first_name || (isMine ? 'Вы' : 'Собеседник');
              const timeStr = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

              return (
                <div
                  key={msg.id}
                  className={`${styles.msgBubble} ${isMine ? styles.mine : styles.theirs}`}
                >
                  <div className={styles.msgHeader}>
                    <span className={styles.sender}>{senderName}</span>
                    <span className={styles.time}>{timeStr}</span>
                  </div>
                  <p className={styles.content}>{msg.content}</p>
                </div>
              );
            })
          )}
          <div ref={messagesEndRef} />
        </div>

        <form onSubmit={handleSend} className={styles.form}>
          <input
            type="text"
            placeholder="Введите сообщение..."
            value={text}
            onChange={(e) => setText(e.target.value)}
            disabled={sending}
          />
          <Button type="submit" disabled={sending || !text.trim()}>
            Отправить
          </Button>
        </form>
      </div>
    </div>
  );
}
