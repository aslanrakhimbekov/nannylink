import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';
import { profileApi } from '../../api/profile';
import { bookingsApi } from '../../api/bookings';
import { Coins, ArrowDownLeft, ArrowUpRight, RotateCcw } from 'lucide-react';
import Card from '../../components/common/Card';
import Button from '../../components/common/Button';
import PageTransition from '../../components/layout/PageTransition';
import styles from './Balance.module.css';

export default function Balance() {
  const { t } = useTranslation();
  const { user, updateUser } = useAuth();

  const balance = user?.profile?.balance_coins || 0;
  const rawTransactions = user?.coin_transactions || [];
  
  const transactions = [...rawTransactions];
  if (transactions.length === 0 && balance > 0) {
    transactions.push({
      id: 'initial_deposit',
      type: 'deposit',
      amount: balance,
      created_at: user?.created_at || new Date().toISOString(),
      isInitial: true,
    });
  }

  const [depositAmount, setDepositAmount] = useState(1000);
  const [depositing, setDepositing] = useState(false);
  const [depositSuccess, setDepositSuccess] = useState('');

  const refreshBalance = async () => {
    try {
      const response = await profileApi.updateProfile({});
      updateUser(response.user);
    } catch (err) {
      console.log('Error refreshing balance:', err);
    }
  };

  const handleDeposit = async (e) => {
    e.preventDefault();
    setDepositing(true);
    setDepositSuccess('');
    try {
      const response = await bookingsApi.depositCoins(depositAmount);
      updateUser(response.user);
      setDepositSuccess('Баланс успешно пополнен!');
      setTimeout(() => setDepositSuccess(''), 3000);
    } catch (err) {
      console.error(err);
      alert('Ошибка при пополнении баланса.');
    } finally {
      setDepositing(false);
    }
  };

  useEffect(() => {
    refreshBalance();
  }, []);

  const numResponses = Math.floor(balance / 500);

  const getTxTypeLabel = (tx) => {
    if (tx.isInitial) return 'Стартовый баланс';
    const labels = {
      spend: t('balance.type_spend'),
      refund: t('balance.type_refund'),
      deposit: t('balance.type_deposit'),
    };
    return labels[tx.type] || tx.type;
  };

  return (
    <PageTransition>
      <div className={styles.container}>
        <div className={styles.header}>
          <h1>{t('balance.title')}</h1>
        </div>

        <div className={`${styles.balanceCard} gradient-bg`}>
          <Coins size={36} className={styles.coinsIcon} />
          <div className={styles.balanceInfo}>
            <span>{t('balance.current')}</span>
            <h2>{balance} {t('balance.coins')}</h2>
            {numResponses > 0 ? (
              <p>{t('balance.enough_for', { count: numResponses })}</p>
            ) : (
              <p>Пополните баланс для продолжения подтверждения бронирований</p>
            )}
          </div>
        </div>

        <Card className={styles.depositCard} style={{ padding: '20px', marginTop: '15px' }}>
          <h3 style={{ fontSize: '1.1rem', fontWeight: 700, marginBottom: '10px' }}>Пополнение баланса (Тест)</h3>
          <p style={{ fontSize: '0.85rem', color: 'var(--color-text-muted)', marginBottom: '15px' }}>
            Каждое подтверждение бронирования списывает 500 монет. Выберите пакет для пополнения:
          </p>
          <form onSubmit={handleDeposit} style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '10px' }}>
              {[500, 1000, 3000].map((amt) => (
                <button
                  key={amt}
                  type="button"
                  onClick={() => setDepositAmount(amt)}
                  style={{
                    padding: '10px',
                    borderRadius: '8px',
                    border: '2px solid ' + (depositAmount === amt ? 'var(--color-primary)' : 'var(--color-border)'),
                    background: depositAmount === amt ? 'rgba(255, 122, 89, 0.1)' : 'var(--color-surface)',
                    color: 'var(--color-text)',
                    fontWeight: 'bold',
                    fontSize: '0.9rem',
                    transition: 'all 0.2s ease',
                    cursor: 'pointer'
                  }}
                >
                  +{amt}
                </button>
              ))}
            </div>
            {depositSuccess && <div className="success-message" style={{ margin: '5px 0' }}>{depositSuccess}</div>}
            <Button type="submit" disabled={depositing} full>
              {depositing ? t('common.loading') : `Пополнить на ${depositAmount} монет`}
            </Button>
          </form>
        </Card>

        <div className={styles.historySection}>
          <h3 className={styles.sectionTitle}>{t('balance.history')}</h3>

          {transactions.length === 0 ? (
            <div className={styles.empty}>{t('balance.empty')}</div>
          ) : (
            <div className={styles.list}>
              {transactions.map((tx) => (
                <Card key={tx.id} style={{ padding: '14px' }}>
                  <div className={styles.txRow}>
                    <div className={styles.txInfo}>
                      <div className={`${styles.txIcon} ${styles[tx.type]}`}>
                        {tx.type === 'deposit' && <ArrowDownLeft size={16} />}
                        {tx.type === 'spend' && <ArrowUpRight size={16} />}
                        {tx.type === 'refund' && <RotateCcw size={16} />}
                      </div>
                      <div>
                        <h4>{getTxTypeLabel(tx)}</h4>
                        <span className={styles.date}>
                          {new Date(tx.created_at).toLocaleDateString()}
                        </span>
                      </div>
                    </div>

                    <span className={`${styles.amount} ${styles[tx.type + 'Text']}`}>
                      {tx.type === 'spend' ? '-' : '+'}{tx.amount}
                    </span>
                  </div>
                </Card>
              ))}
            </div>
          )}
        </div>
      </div>
    </PageTransition>
  );
}
