import React, { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useAuth } from '../../context/AuthContext';
import { documentsApi } from '../../api/documents';
import { profileApi } from '../../api/profile';
import { showToast } from '../../components/common/Toast';
import { FileText, Upload, Trash2, ShieldCheck, ShieldAlert, AlertCircle } from 'lucide-react';
import Card from '../../components/common/Card';
import Button from '../../components/common/Button';
import Badge from '../../components/common/Badge';
import Skeleton from '../../components/common/Skeleton';
import PageTransition from '../../components/layout/PageTransition';
import styles from './Documents.module.css';

export default function Documents() {
  const { t } = useTranslation();
  const { user, updateUser } = useAuth();

  const [documents, setDocuments] = useState(user?.profile?.documents || []);
  const [loading, setLoading] = useState(false);
  const [uploading, setUploading] = useState(false);
  
  const [docType, setDocType] = useState('criminal_record');
  const [file, setFile] = useState(null);

  const isImageType = docType === 'identity_card';
  const acceptedFormats = isImageType ? '.pdf,.jpg,.jpeg,.png' : '.pdf';

  // Re-fetch user profile to sync documents list & verified status
  const refreshProfile = async () => {
    try {
      const response = await profileApi.updateProfile({}); // Empty update to fetch fresh User model from backend
      updateUser(response.user);
      setDocuments(response.user.profile?.documents || []);
    } catch (err) {
      console.log('Error refreshing profile:', err);
    }
  };

  useEffect(() => {
    refreshProfile();
  }, []);

  const handleFileChange = (e) => {
    if (e.target.files && e.target.files[0]) {
      const selectedFile = e.target.files[0];
      const allowedTypes = isImageType
        ? ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg']
        : ['application/pdf'];
      if (!allowedTypes.includes(selectedFile.type)) {
        showToast('error', isImageType ? 'Допускаются PDF, JPG, PNG' : 'Допускаются только PDF файлы');
        return;
      }
      if (selectedFile.size > 5 * 1024 * 1024) {
        showToast('error', 'Размер файла превышает 5 МБ');
        return;
      }
      setFile(selectedFile);
    }
  };

  const handleUpload = async (e) => {
    e.preventDefault();
    if (!file) {
      showToast('error', 'Выберите PDF файл для загрузки');
      return;
    }

    setUploading(true);
    try {
      await documentsApi.uploadDocument(docType, file);
      showToast('success', 'Документ успешно загружен на проверку');
      setFile(null);
      
      // Reset input element
      const fileInput = document.getElementById('file-upload-input');
      if (fileInput) fileInput.value = '';

      await refreshProfile();
    } catch (err) {
      showToast('error', err.message || 'Ошибка загрузки документа');
    } finally {
      setUploading(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm(t('documents.delete_confirm'))) return;

    setLoading(true);
    try {
      await documentsApi.deleteDocument(id);
      showToast('success', 'Документ удален');
      await refreshProfile();
    } catch (err) {
      showToast('error', err.message || 'Ошибка удаления документа');
    } finally {
      setLoading(false);
    }
  };

  return (
    <PageTransition>
      <div className={styles.container}>
        <div className={styles.header}>
          <h1>{t('documents.title')}</h1>
        </div>

        <div className={`${styles.statusBanner} ${user?.profile?.is_verified ? styles.verified : styles.unverified} glass`}>
          {user?.profile?.is_verified ? (
            <>
              <ShieldCheck size={24} className={styles.verifiedIcon} />
              <div>
                <strong>{t('profile.verified')}</strong>
                <p>Вы можете просматривать заказы и откликаться.</p>
              </div>
            </>
          ) : (
            <>
              <ShieldAlert size={24} className={styles.unverifiedIcon} />
              <div>
                <strong>{t('profile.not_verified')}</strong>
                <p>Загрузите документы для подтверждения вашего аккаунта.</p>
              </div>
            </>
          )}
        </div>

        <Card>
          <h3 className={styles.sectionTitle}>{t('documents.upload')}</h3>
          <form onSubmit={handleUpload} className={styles.uploadForm}>
            <div className={styles.inputGroup}>
              <label>{t('new_order.step_info')}</label>
              <select value={docType} onChange={(e) => { setDocType(e.target.value); setFile(null); }}>
                <option value="criminal_record">{t('documents.type_criminal')}</option>
                <option value="medical_clearance">{t('documents.type_medical')}</option>
                <option value="identity_card">{t('documents.type_identity')}</option>
                <option value="narcology_clearance">{t('documents.type_narcology')}</option>
                <option value="psychiatry_clearance">{t('documents.type_psychiatry')}</option>
              </select>
            </div>

            <div className={styles.dropzone} onClick={() => document.getElementById('file-upload-input').click()}>
              <Upload size={32} className={styles.uploadIcon} />
              <span>{file ? file.name : t('documents.drop_file')}</span>
              <span className={styles.sub}>{isImageType ? 'PDF, JPG, PNG. Макс. 5 МБ' : t('documents.max_size')}</span>
              <input
                id="file-upload-input"
                type="file"
                accept={acceptedFormats}
                onChange={handleFileChange}
                style={{ display: 'none' }}
              />
            </div>

            <Button type="submit" fullWidth loading={uploading} disabled={!file}>
              Загрузить документ
            </Button>
          </form>
        </Card>

        <div className={styles.docsSection}>
          <h3 className={styles.sectionTitle}>Загруженные документы ({documents.length})</h3>

          {documents.length === 0 ? (
            <div className={styles.empty}>{t('documents.empty')}</div>
          ) : (
            <div className={styles.list}>
              {documents.map((doc) => (
                <Card key={doc.id} style={{ padding: '16px' }}>
                  <div className={styles.docHeader}>
                    <div className={styles.docInfo}>
                      <FileText size={20} className={styles.docFileIcon} />
                      <div>
                        <h4>
                          {doc.type === 'criminal_record' 
                            ? t('documents.type_criminal') 
                            : doc.type === 'medical_clearance'
                            ? t('documents.type_medical')
                            : doc.type === 'identity_card'
                            ? t('documents.type_identity')
                            : doc.type === 'narcology_clearance'
                            ? t('documents.type_narcology')
                            : t('documents.type_psychiatry')}
                        </h4>
                        <span className={styles.date}>
                          {new Date(doc.created_at).toLocaleDateString()}
                        </span>
                      </div>
                    </div>

                    <div className={styles.docActions}>
                      <Badge status={doc.status} />
                      <button 
                        className={styles.deleteBtn} 
                        onClick={() => handleDelete(doc.id)}
                        disabled={loading}
                      >
                        <Trash2 size={16} />
                      </button>
                    </div>
                  </div>

                  {doc.status === 'rejected' && doc.rejection_reason && (
                    <div className={styles.rejectionCard}>
                      <AlertCircle size={14} />
                      <span>{t('documents.rejection_reason')}: {doc.rejection_reason}</span>
                    </div>
                  )}
                </Card>
              ))}
            </div>
          )}
        </div>
      </div>
    </PageTransition>
  );
}
