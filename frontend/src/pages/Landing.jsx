import React from 'react';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { Heart, Search, ShieldCheck, Zap } from 'lucide-react';
import Button from '../components/common/Button';
import Card from '../components/common/Card';
import PageTransition from '../components/layout/PageTransition';
import styles from './Landing.module.css';

export default function Landing() {
  const { t } = useTranslation();
  const navigate = useNavigate();

  const handleStart = (role) => {
    // Navigate to auth screen with target role metadata
    navigate('/auth', { state: { targetRole: role } });
  };

  return (
    <PageTransition>
      <div className={styles.container}>
        <section className={styles.hero}>
          <div className={styles.heartContainer}>
            <Heart className={styles.floatingHeart} size={48} />
          </div>
          <h1 className={`${styles.title} slide-up`}>
            {t('landing.hero_title')}
          </h1>
          <p className={`${styles.subtitle} fade-in`}>
            {t('landing.hero_subtitle')}
          </p>

          <div className={styles.cta}>
            <Button fullWidth onClick={() => handleStart('parent')}>
              {t('landing.cta_parent')}
            </Button>
            <Button fullWidth variant="secondary" onClick={() => handleStart('nanny')}>
              {t('landing.cta_nanny')}
            </Button>
            <div className={styles.loginPrompt}>
              Уже зарегистрированы?{' '}
              <span className={styles.loginLink} onClick={() => navigate('/auth')}>
                Войти в аккаунт
              </span>
            </div>
          </div>
        </section>

        <section className={styles.features}>
          <h2 className={styles.sectionTitle}>{t('landing.how_it_works')}</h2>
          
          <Card>
            <div className={styles.stepHeader}>
              <Zap size={24} className={styles.stepIcon} />
              <h3>{t('landing.step1_title')}</h3>
            </div>
            <p>{t('landing.step1_desc')}</p>
          </Card>

          <Card>
            <div className={styles.stepHeader}>
              <ShieldCheck size={24} className={styles.stepIcon} />
              <h3>{t('landing.step2_title')}</h3>
            </div>
            <p>{t('landing.step2_desc')}</p>
          </Card>

          <Card>
            <div className={styles.stepHeader}>
              <Search size={24} className={styles.stepIcon} />
              <h3>{t('landing.step3_title')}</h3>
            </div>
            <p>{t('landing.step3_desc')}</p>
          </Card>
        </section>

        <section className={styles.benefits}>
          <h2 className={styles.sectionTitle}>{t('landing.benefits_title')}</h2>
          <ul className={styles.benefitList}>
            <li>{t('landing.benefit1')}</li>
            <li>{t('landing.benefit2')}</li>
            <li>{t('landing.benefit3')}</li>
            <li>{t('landing.benefit4')}</li>
          </ul>
        </section>
      </div>
    </PageTransition>
  );
}
