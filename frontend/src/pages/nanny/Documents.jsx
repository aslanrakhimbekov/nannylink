import React, { useState, useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';
import { documentsApi } from '../../api/documents';
import { profileApi } from '../../api/profile';
import { showToast } from '../../components/common/Toast';
import { ShieldCheck, ShieldAlert, AlertTriangle, Upload, CheckCircle2, Clock, XCircle, FileText } from 'lucide-react';
import Card from '../../components/common/Card';
import Button from '../../components/common/Button';
import PageTransition from '../../components/layout/PageTransition';
import styles from './Documents.module.css';

const REQUIRED_DOCUMENTS = [
  {
    type: 'identity_card',
    icon: '🪪',
    titleKey: 'documents.type_identity',
    description: 'Удостоверение личности гражданина РК (лицевая сторона)'
  },
  {
    type: 'criminal_record',
    icon: '📋',
    titleKey: 'documents.type_criminal',
    description: 'Официальная справка об отсутствии судимости с портала eGov.kz'
  },
  {
    type: 'medical_clearance',
    icon: '🏥',
    titleKey: 'documents.type_medical',
    description: 'Личная медицинская книжка (санкнижка) или форма 075/у'
  },
  {
    type: 'narcology_clearance',
    icon: '🧪',
    titleKey: 'documents.type_narcology',
    description: 'Справка о несостоянии на учёте в наркологическом диспансере'
  },
  {
    type: 'psychiatry_clearance',
    icon: '🧠',
    titleKey: 'documents.type_psychiatry',
    description: 'Справка о несостоянии на учёте в психоневрологическом диспансере'
  },
];

export default function Documents() {
  const { t } = useTranslation();
  const { user, updateUser } = useAuth();

  const [documents, setDocuments] = useState(user?.profile?.documents || []);
  const [uploadingType, setUploadingType] = useState(null);
  const [selectedFiles, setSelectedFiles] = useState({});

  const fileInputRefs = useRef({});

  const refreshProfile = async () => {
    try {
      const response = await profileApi.updateProfile({});
      updateUser(response.user);
      setDocuments(response.user.profile?.documents || []);
    } catch (err) {
      console.log('Error refreshing profile:', err);
    }
  };

  useEffect(() => {
    refreshProfile();
  }, []);

  const handleFileSelect = (type, file) => {
    if (!file) return;

    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    if (!allowedTypes.includes(file.type)) {
      showToast('error', 'Допускаются файлы PDF, JPG, PNG');
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      showToast('error', 'Размер файла не должен превышать 5 МБ');
      return;
    }

    setSelectedFiles((prev) => ({ ...prev, [type]: file }));
  };

  const handleUpload = async (type) => {
    const file = selectedFiles[type];
    if (!file) {
      showToast('error', 'Пожалуйста, выберите файл для загрузки');
      return;
    }

    setUploadingType(type);
    try {
      await documentsApi.uploadDocument(type, file);
      showToast('success', 'Документ успешно загружен на проверку!');
      setSelectedFiles((prev) => {
        const copy = { ...prev };
        delete copy[type];
        return copy;
      });
      await refreshProfile();
    } catch (err) {
      showToast('error', err.message || 'Ошибка при загрузке документа');
    } finally {
      setUploadingType(null);
    }
  };

  const approvedCount = documents.filter((d) => d.status === 'approved').length;
  const isFullyVerified = user?.profile?.is_verified && approvedCount === 5;

  return (
    <PageTransition>
      <div className={styles.container}>
        <div className={styles.header}>
          <h1>{t('documents.title')}</h1>
          <p className={styles.subtitle}>
            Для работы на платформе необходимо загрузить все 5 документов. Проверка администрацией занимает до 24 часов.
          </p>
        </div>

        {/* Status Banner */}
        <div className={`${styles.statusBanner} ${isFullyVerified ? styles.verified : styles.unverified} glass`}>
          {isFullyVerified ? (
            <>
              <ShieldCheck size={28} className={styles.verifiedIcon} />
              <div>
                <strong>{t('profile.verified')} (5/5)</strong>
                <p>Все ваши документы проверены и одобрены. Профиль активен для бронирований!</p>
              </div>
            </>
          ) : (
            <>
              <ShieldAlert size={28} className={styles.unverifiedIcon} />
              <div>
                <strong>{t('profile.not_verified')} ({approvedCount}/5 проверено)</strong>
                <p>
                  Загрузите все 5 документов для активации профиля и приема заказов. 
                  При обновлении любого документа профиль повторно отправляется на проверку.
                </p>
              </div>
            </>
          )}
        </div>

        {/* 5 Standard Document Cards */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '16px', marginTop: '20px' }}>
          {REQUIRED_DOCUMENTS.map((docDef) => {
            const existingDoc = documents.find((d) => d.type === docDef.type);
            const status = existingDoc ? existingDoc.status : 'missing';
            const selectedFile = selectedFiles[docDef.type];

            return (
              <Card key={docDef.type} style={{ padding: '20px' }}>
                <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', flexWrap: 'wrap', gap: '12px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <span style={{ fontSize: '2rem' }}>{docDef.icon}</span>
                    <div>
                      <h3 style={{ margin: 0, fontSize: '1.05rem', color: 'var(--color-text-primary)' }}>
                        {t(docDef.titleKey)}
                      </h3>
                      <p style={{ margin: '4px 0 0', fontSize: '0.82rem', color: 'var(--color-text-muted)' }}>
                        {docDef.description}
                      </p>
                    </div>
                  </div>

                  {/* Status Badge */}
                  <div>
                    {status === 'approved' && (
                      <span style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', padding: '6px 12px', borderRadius: '20px', background: 'rgba(16, 185, 129, 0.12)', color: '#10B981', fontWeight: 700, fontSize: '0.82rem' }}>
                        <CheckCircle2 size={16} /> Одобрен
                      </span>
                    )}
                    {status === 'pending' && (
                      <span style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', padding: '6px 12px', borderRadius: '20px', background: 'rgba(245, 158, 11, 0.12)', color: '#f59e0b', fontWeight: 700, fontSize: '0.82rem' }}>
                        <Clock size={16} /> На проверке
                      </span>
                    )}
                    {status === 'rejected' && (
                      <span style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', padding: '6px 12px', borderRadius: '20px', background: 'rgba(239, 68, 68, 0.12)', color: '#ef4444', fontWeight: 700, fontSize: '0.82rem' }}>
                        <XCircle size={16} /> Отклонён
                      </span>
                    )}
                    {status === 'missing' && (
                      <span style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', padding: '6px 12px', borderRadius: '20px', background: 'var(--color-border)', color: 'var(--color-text-muted)', fontWeight: 600, fontSize: '0.82rem' }}>
                        <AlertTriangle size={16} /> Не загружен
                      </span>
                    )}
                  </div>
                </div>

                {/* Rejection Alert Box */}
                {status === 'rejected' && existingDoc?.rejection_reason && (
                  <div style={{ marginTop: '14px', padding: '12px 14px', borderRadius: '10px', background: 'rgba(239, 68, 68, 0.08)', border: '1px solid rgba(239, 68, 68, 0.3)', color: '#dc2626', fontSize: '0.88rem', display: 'flex', alignItems: 'flex-start', gap: '8px' }}>
                    <AlertTriangle size={18} style={{ flexShrink: 0, marginTop: '2px' }} />
                    <div>
                      <strong>Причина отклонения администратором:</strong>
                      <p style={{ margin: '2px 0 0', color: '#991b1b', fontWeight: 500 }}>
                        {existingDoc.rejection_reason}
                      </p>
                    </div>
                  </div>
                )}

                {/* File Upload Control */}
                <div style={{ marginTop: '16px', paddingTop: '14px', borderTop: '1px solid var(--color-border)', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '12px' }}>
                  <input
                    type="file"
                    ref={(el) => (fileInputRefs.current[docDef.type] = el)}
                    accept=".pdf,.jpg,.jpeg,.png"
                    onChange={(e) => handleFileSelect(docDef.type, e.target.files?.[0])}
                    style={{ display: 'none' }}
                  />

                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <Button
                      type="button"
                      variant="secondary"
                      onClick={() => fileInputRefs.current[docDef.type]?.click()}
                      style={{ fontSize: '0.85rem', padding: '8px 14px' }}
                    >
                      <Upload size={14} style={{ marginRight: '6px' }} />
                      {existingDoc ? 'Заменить файл' : 'Выбрать файл'}
                    </Button>

                    {selectedFile && (
                      <span style={{ fontSize: '0.82rem', color: 'var(--color-primary)', fontWeight: 600, display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                        <FileText size={14} /> {selectedFile.name}
                      </span>
                    )}
                  </div>

                  {selectedFile && (
                    <Button
                      type="button"
                      variant="primary"
                      loading={uploadingType === docDef.type}
                      onClick={() => handleUpload(docDef.type)}
                      style={{ fontSize: '0.85rem', padding: '8px 18px' }}
                    >
                      Отправить на проверку
                    </Button>
                  )}
                </div>
              </Card>
            );
          })}
        </div>
      </div>
    </PageTransition>
  );
}
