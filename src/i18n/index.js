import { createI18n } from 'vue-i18n';

const messages = {
  en: {
    nav: {
      tracking: 'Tracking',
      dashboard: 'Dashboard',
      events: 'Events',
      settings: 'Settings',
      control: 'Control',
      report: 'Report',
      general: 'General',
      admin: 'Admin',
      help: 'Help',
      account: 'Account',
      profile: 'My Profile',
      arabic: 'Arabic',
      english: 'English',
      dark: 'Dark Mode',
      light: 'Light Mode',
      logout: 'Logout'
    },
    general: {
      rfid: 'RFID',
      dtc: 'DTC',
      maintenance: 'Maintenance',
      expenses: 'Expenses'
    }
  },
  ar: {
    nav: {
      tracking: 'المتابعة',
      dashboard: 'لوحة القيادة',
      events: 'التنبيهات',
      settings: 'الإعدادات',
      control: 'التحكم',
      report: 'التقارير',
      general: 'عام',
      admin: 'الإدارة',
      help: 'المساعدة',
      account: 'الحساب',
      profile: 'ملفي الشخصي',
      arabic: 'العربية',
      english: 'الإنجليزية',
      dark: 'الوضع الداكن',
      light: 'الوضع المضيء',
      logout: 'تسجيل الخروج'
    },
    general: {
      rfid: 'RFID',
      dtc: 'DTC',
      maintenance: 'الصيانة',
      expenses: 'المصاريف'
    }
  }
};

const i18n = createI18n({
  legacy: false,
  locale: localStorage.getItem('locale') || 'en',
  fallbackLocale: 'en',
  messages,
});

export default i18n;
