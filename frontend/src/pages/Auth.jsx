import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../context/AuthContext';
import { authApi } from '../api/auth';
import { profileApi } from '../api/profile';
import { showToast } from '../components/common/Toast';
import Input from '../components/common/Input';
import Button from '../components/common/Button';
import PageTransition from '../components/layout/PageTransition';
import styles from './Auth.module.css';

export default function Auth() {
  const { t } = useTranslation();
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  // Target role passed from Landing page CTAs
  const targetRole = location.state?.targetRole || null;

  const [step, setStep] = useState(1); // 1 = Phone Input, 2 = OTP Input
  const [phone, setPhone] = useState('+7');
  const [code, setCode] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [countdown, setCountdown] = useState(0);

  // Sync OTP timer
  useEffect(() => {
    if (countdown > 0) {
      const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [countdown]);

  const formatPhoneWithSpaces = (val) => {
    if (!val) return '+7';
    let digits = val.replace(/\D/g, '');
    
    let body = '';
    if (digits.length === 11 && digits.startsWith('8')) {
      body = digits.slice(1);
    } else if (digits.length > 0 && digits.startsWith('7')) {
      body = digits.slice(1);
    } else {
      body = digits;
    }

    body = body.slice(0, 10);

    let formatted = '+7';
    if (body.length > 0) {
      formatted += ' ' + body.slice(0, 3);
    }
    if (body.length >= 4) {
      formatted += ' ' + body.slice(3, 6);
    }
    if (body.length >= 7) {
      formatted += ' ' + body.slice(6, 8);
    }
    if (body.length >= 9) {
      formatted += ' ' + body.slice(8, 10);
    }
    return formatted;
  };

  const handlePhoneChange = (val) => {
    setPhone(formatPhoneWithSpaces(val));
  };

  const handlePhoneFocus = () => {
    if (!phone || phone === '+' || phone === '+7') {
      setPhone('+7 ');
    }
  };

  const [sentVia, setSentVia] = useState('sms_mock');
  const [botUsername, setBotUsername] = useState('nannylink_auth_bot');

  const handleSendOtp = async (e) => {
    if (e) e.preventDefault();
    setError('');
    
    const cleanPhone = phone.replace(/\s+/g, '');
    const phoneRegex = /^\+7[0-9]{10}$/;
    if (!phoneRegex.test(cleanPhone)) {
      setError('Формат номера должен быть: +7 7XX XXX XX XX');
      return;
    }

    setLoading(true);
    try {
      const response = await authApi.requestOtp(cleanPhone);
      setStep(2);
      setCountdown(60);
      if (response.sent_via) setSentVia(response.sent_via);
      if (response.bot_username) setBotUsername(response.bot_username);
      
      if (response.sent_via === 'telegram') {
        showToast('success', '🔐 Код отправлен в ваш Telegram!');
      } else {
        showToast('success', 'OTP код отправлен!');
      }
    } catch (err) {
      showToast('error', err.message || 'Ошибка отправки OTP');
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyOtp = async (e) => {
    if (e) e.preventDefault();
    setError('');

    if (code.length !== 4) {
      setError('Введите 4-значный код');
      return;
    }

    const cleanPhone = phone.replace(/\s+/g, '');

    setLoading(true);
    try {
      const response = await authApi.verifyOtp(cleanPhone, code);
      login(response.token, response.user);
      
      // Apply target role automatically if registration
      if (targetRole && response.user && response.user.role === 'parent') {
        const updated = await profileApi.updateProfile({ role: targetRole });
        login(response.token, updated.user);
      }

      showToast('success', 'Вход выполнен!');
      
      if (response.user.profile?.first_name) {
        navigate(response.user.role === 'parent' ? '/parent/search' : '/nanny/schedule');
      } else {
        navigate('/onboarding');
      }
    } catch (err) {
      if (err.status === 403 || err.message?.includes('заблокирован')) {
        setError('🚫 ' + (err.message || 'Ваш аккаунт заблокирован. Обратитесь в поддержку.'));
        showToast('error', 'Аккаунт заблокирован');
      } else {
        setError(t('errors.validation'));
        showToast('error', err.message || 'Неверный OTP-код');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <PageTransition>
      <div className={styles.wrapper}>
        <div className={`${styles.authCard} glass`}>
          <div className={styles.cardHeader}>
            <span className={styles.backLink} onClick={() => navigate('/')}>
              ← На главную
            </span>
          </div>

          <h2 className={styles.title}>{t('auth.title')}</h2>

          {step === 1 ? (
            <form onSubmit={handleSendOtp} className={styles.form}>
              <Input
                label={t('auth.phone_label')}
                placeholder="+7 7XX XXX XX XX"
                value={phone}
                onChange={(e) => handlePhoneChange(e.target.value)}
                onFocus={handlePhoneFocus}
                error={error}
                required
              />

              <Button type="submit" fullWidth loading={loading}>
                {t('auth.send_otp')}
              </Button>

              <div style={{ marginTop: '14px', textAlign: 'center', fontSize: '0.82rem', color: 'var(--color-text-muted)' }}>
                💬 Код входа придёт в бота: <a href={`https://t.me/${botUsername}?start=auth`} target="_blank" rel="noreferrer" style={{ color: '#2AABEE', fontWeight: 600, textDecoration: 'underline' }}>@{botUsername}</a>
              </div>

            </form>
          ) : (
            <form onSubmit={handleVerifyOtp} className={styles.form}>
              <p className={styles.subtitle}>
                {t('auth.otp_subtitle')} <span style={{ fontWeight: 'bold' }}>{phone}</span>
              </p>

              <div style={{ padding: '14px 16px', borderRadius: '14px', background: 'rgba(84, 169, 235, 0.12)', border: '1px solid rgba(84, 169, 235, 0.35)', marginBottom: '18px', fontSize: '0.88rem' }}>
                <div style={{ fontWeight: 600, color: '#2a82c4', display: 'flex', alignItems: 'center', gap: '6px', marginBottom: '8px' }}>
                  🤖 Как получить проверочный код:
                </div>
                <ol style={{ margin: '0 0 12px 18px', padding: 0, color: 'var(--color-text)', lineHeight: 1.5, fontSize: '0.84rem' }}>
                  <li>Нажмите на кнопку ниже и кликните <strong>«Запустить / Start»</strong> в Телеграме.</li>
                  <li>Бот пришлет вам 4-значный код. Введите его в поле ниже.</li>
                </ol>
                <a 
                  href={`https://t.me/${botUsername}?start=auth`} 
                  target="_blank" 
                  rel="noreferrer" 
                  style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px', padding: '10px 14px', borderRadius: '10px', background: '#2AABEE', color: '#ffffff', fontWeight: 600, textDecoration: 'none', textAlign: 'center', fontSize: '0.86rem', boxShadow: '0 2px 8px rgba(42, 171, 238, 0.3)' }}
                >
                  <svg viewBox="0 0 24 24" width="18" height="18">
                    <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-1-.65-.35-1 .22-1.62.15-.15 2.72-2.5 2.77-2.7.01-.03.01-.15-.06-.21-.07-.06-.17-.04-.25-.02-.11.02-1.91 1.21-5.4 3.57-.51.35-.97.52-1.38.51-.45-.01-1.32-.26-1.97-.47-.79-.26-1.42-.4-1.36-.85.03-.23.35-.47.96-.71 3.76-1.63 6.27-2.71 7.53-3.23 3.58-1.48 4.32-1.74 4.81-1.75.11 0 .35.03.5.16.13.1.17.24.18.34-.01.06-.01.12-.02.16z" />
                  </svg>
                  Открыть бота @{botUsername}
                </a>
                <div style={{ marginTop: '10px', fontSize: '0.82rem', color: '#059669', fontWeight: 600, textAlign: 'center' }}>
                  💡 Для быстрого входа без Телеграма код: <span style={{ fontSize: '0.95rem', letterSpacing: '1px' }}>1111</span>
                </div>
              </div>

              <Input
                label={t('auth.otp_title')}
                placeholder="0000"
                value={code}
                onChange={(e) => setCode(e.target.value)}
                maxLength={4}
                error={error}
                required
              />

              <Button type="submit" fullWidth loading={loading}>
                {t('auth.verify')}
              </Button>

              <div className={styles.countdownContainer}>
                {countdown > 0 ? (
                  <span className={styles.timer}>
                    {t('auth.resend_in', { count: countdown })}
                  </span>
                ) : (
                  <button 
                    type="button" 
                    className={styles.resendBtn} 
                    onClick={handleSendOtp}
                  >
                    {t('auth.resend')}
                  </button>
                )}
              </div>
            </form>
          )}
        </div>
      </div>
    </PageTransition>
  );
}
