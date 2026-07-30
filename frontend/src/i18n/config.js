import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import ru from './ru.json';
import kk from './kk.json';

const savedLang = localStorage.getItem('nannylink_lang') || 'ru';

i18n
  .use(initReactI18next)
  .init({
    resources: {
      ru: { translation: ru },
      kk: { translation: kk },
    },
    lng: savedLang,
    fallbackLng: 'ru',
    interpolation: {
      escapeValue: false,
    },
  });

export default i18n;
