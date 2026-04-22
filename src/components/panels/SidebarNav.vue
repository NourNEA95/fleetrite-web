<template>
  <aside class="sidebar-modern">
    <!-- Fixed Logo -->
    <div class="logo-area">
      <div class="logo-wrapper">
        <img src="https://gps.fleetrite.io/img/logo_small.png" alt="Fleetrite" class="brand-logo" />
      </div>
    </div>

    <!-- Scrollable Nav -->
    <div class="nav-scroll-container">
      <nav class="nav-container">
        <!-- Tracking -->
        <button class="nav-btn" :class="{ active: activeTab === 'Tracking' }" @click="$emit('nav-action', 'tracking')" :title="$t('nav.tracking')">
          <div class="icon-box">
             <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
               <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><circle cx="12" cy="11" r="3"/>
             </svg>
          </div>
          <span class="btn-label">{{ $t('nav.tracking') }}</span>
        </button>

        <!-- Dashboard -->
        <button class="nav-btn" @click="$emit('nav-action', 'dashboard')" :title="$t('nav.dashboard')">
          <div class="icon-box">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>
            </svg>
          </div>
          <span class="btn-label">{{ $t('nav.dashboard') }}</span>
        </button>

        <!-- Events -->
        <button class="nav-btn" @click="$emit('nav-action', 'events')" :title="$t('nav.events')">
          <div class="icon-box">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
              <circle cx="18" cy="5" r="3" fill="currentColor" stroke="none" fill-opacity="0.2"/>
            </svg>
          </div>
          <span class="btn-label">{{ $t('nav.events') }}</span>
        </button>

        <!-- Settings -->
        <button class="nav-btn" @click="$emit('nav-action', 'settings')" :title="$t('nav.settings')">
          <div class="icon-box">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </div>
          <span class="btn-label">{{ $t('nav.settings') }}</span>
        </button>

        <!-- Control -->
        <button class="nav-btn" @click="$emit('nav-action', 'control')" :title="$t('nav.control')">
          <div class="icon-box">
             <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="12" rx="2"/><circle cx="12" cy="10" r="2"/><line x1="7" y1="20" x2="17" y2="20"/></svg>
          </div>
          <span class="btn-label">{{ $t('nav.control') }}</span>
        </button>

        <!-- Report -->
        <button class="nav-btn" @click="$emit('nav-action', 'reports')" :title="$t('nav.report')">
          <div class="icon-box">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
          </div>
          <span class="btn-label">{{ $t('nav.report') }}</span>
        </button>

        <!-- General Submenu -->
        <div class="menu-parent" ref="generalRef" @mouseenter="activeMenu = 'general'" @mouseleave="activeMenu = null">
          <button class="nav-btn" :class="{ active: activeMenu === 'general' }" :title="$t('nav.general')">
            <div class="icon-box">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
              </svg>
            </div>
            <span class="btn-label">{{ $t('nav.general') }} <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="margin-left:2px"><path d="M6 9l6 6 6-6"/></svg></span>
          </button>

          <Teleport to="body">
            <transition name="pop">
              <div v-if="activeMenu === 'general'" 
                   class="floating-submenu" 
                   :style="submenuPos" 
                   @mouseenter="activeMenu = 'general'" 
                   @mouseleave="activeMenu = null">
                <div class="submenu-inner">
                  <div class="submenu-item" v-for="item in generalItems" :key="item" @click="handleGeneralAction(item)">
                    <div class="dot"></div>
                    <span class="item-label">{{ $t('general.'+item.toLowerCase()) }}</span>
                    <div class="line"></div>
                  </div>
                </div>
              </div>
            </transition>
          </Teleport>
        </div>

        <!-- Admin -->
        <button class="nav-btn" @click="$emit('nav-action', 'admin')" :title="$t('nav.admin')">
          <div class="icon-box">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>
            </svg>
          </div>
          <span class="btn-label">{{ $t('nav.admin') }}</span>
        </button>

        <!-- Help -->
        <button class="nav-btn" @click="$emit('nav-action', 'help')" :title="$t('nav.help')">
          <div class="icon-box">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          </div>
          <span class="btn-label">{{ $t('nav.help') }}</span>
        </button>
      </nav>
    </div>

    <!-- Fixed Bottom -->
    <div class="bottom-area" ref="accountRef" @mouseenter="activeMenu = 'account'" @mouseleave="activeMenu = null">
      <button class="nav-btn account-btn" :class="{ active: activeMenu === 'account' }" :title="$t('nav.account')">
        <div class="icon-box">
           <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
             <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
           </svg>
        </div>
        <span class="btn-label">{{ $t('nav.account') }} <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M18 15l-6-6-6 6"/></svg></span>
      </button>

      <Teleport to="body">
        <transition name="pop">
          <div v-if="activeMenu === 'account'" 
               class="floating-submenu account-popup" 
               :style="accountPos" 
               @mouseenter="activeMenu = 'account'" 
               @mouseleave="activeMenu = null">
            <div class="submenu-inner">
              <!-- Profile -->
              <div class="submenu-item" @click="handleSubAction('profile')">
                <div class="item-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <span class="item-label">{{ $t('nav.profile') }}</span>
                <div class="line"></div>
              </div>
              <!-- Language -->
              <div class="submenu-item" @click="toggleLocale">
                <div class="item-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </div>
                <span class="item-label">{{ $i18n.locale === 'en' ? 'Arabic' : 'English' }}</span>
                <div class="line"></div>
              </div>
              <!-- Dark Mode -->
              <div class="submenu-item" @click="themeStore.toggleTheme">
                <div class="item-icon">
                  <svg v-if="themeStore.isDarkMode" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                  <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </div>
                <span class="item-label">{{ themeStore.isDarkMode ? $t('nav.light') : $t('nav.dark') }}</span>
                <div class="line"></div>
              </div>
              <!-- Logout -->
              <div class="submenu-item logout-item" @click="handleSubAction('logout')">
                <div class="item-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </div>
                <span class="item-label">{{ $t('nav.logout') }}</span>
              </div>
            </div>
          </div>
        </transition>
      </Teleport>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useThemeStore } from '../../stores/theme';
import { useI18n } from 'vue-i18n';

const themeStore = useThemeStore();
const { locale, t } = useI18n();

defineProps(['activeTab']);
const emit = defineEmits(['nav-action']);

const activeMenu = ref(null);
const generalRef = ref(null);
const accountRef = ref(null);

const generalItems = ['RFID', 'DTC', 'Maintenance', 'Expenses'];

const toggleLocale = () => {
  locale.value = locale.value === 'en' ? 'ar' : 'en';
  localStorage.setItem('locale', locale.value);
  activeMenu.value = null;
};

const handleGeneralAction = (item) => {
  console.log('General Action:', item);
  activeMenu.value = null;
};

const handleSubAction = (act) => {
  emit('nav-action', act);
  activeMenu.value = null;
};

// Submenu positions
const submenuPos = computed(() => {
  if (!generalRef.value) return {};
  const rect = generalRef.value.getBoundingClientRect();
  return {
    top: rect.top + 'px',
    left: (rect.right + 12) + 'px'
  };
});

const accountPos = computed(() => {
  if (!accountRef.value) return {};
  const rect = accountRef.value.getBoundingClientRect();
  return {
    top: (rect.top - 120) + 'px', // offset up
    left: (rect.right + 12) + 'px'
  };
});
</script>

<style scoped>
.sidebar-modern {
  width: 70px;
  background: var(--bg2); /* Default is Dark */
  height: 100vh;
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  z-index: 2000;
  overflow: visible;
  transition: background 0.3s;
}

body.light-mode .sidebar-modern {
  background: white;
  border-right-color: #e2e8f0;
}

.logo-area {
  flex-shrink: 0;
  display: flex;
  justify-content: center;
  padding: 10px 0 20px;
}

.nav-scroll-container {
  flex: 1;
  overflow-y: auto;
  overflow-x: visible;
  scrollbar-width: none;
}
.nav-scroll-container::-webkit-scrollbar { display: none; }

.logo-wrapper {
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.4);
  transition: all 0.3s;
}

body.light-mode .logo-wrapper {
  background: white;
  border: none;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.brand-logo {
  width: 32px;
  height: 32px;
  filter: drop-shadow(0 2px 8px rgba(0,0,0,0.5)) brightness(1.1);
}

body.light-mode .brand-logo {
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1)) brightness(1);
}

.nav-container {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.nav-btn {
  width: 100%;
  border: none;
  background: transparent;
  padding: 10px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  color: #94a3b8; /* Default (Dark) */
  transition: all 0.2s;
  position: relative;
}

body.light-mode .nav-btn {
  color: #64748b;
}

.nav-btn .icon-box {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  transition: all 0.2s;
}

.nav-btn:hover .icon-box {
  background: rgba(255, 255, 255, 0.04);
  color: var(--accent);
  transform: translateY(-1px);
}

body.light-mode .nav-btn:hover .icon-box {
  background: #f1f5f9;
  color: #3b82f6;
}

.nav-btn.active .icon-box {
  background: rgba(59, 130, 246, 0.15);
  color: var(--accent);
  box-shadow: 0 0 20px rgba(59, 130, 246, 0.1);
}

body.light-mode .nav-btn.active .icon-box {
  background: #eff6ff;
  color: #3b82f6;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

/* Vertical Indicator */
.nav-btn.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 15%;
  bottom: 15%;
  width: 3px;
  background: var(--accent);
  border-radius: 0 4px 4px 0;
  box-shadow: 2px 0 10px var(--accent);
}

.nav-btn.active .btn-label {
  color: var(--text);
  font-weight: 700;
}

body.light-mode .nav-btn.active .btn-label {
  color: #2563eb; /* Slightly darker blue for readability */
  font-weight: 700;
}

.btn-label {
  font-size: 10px;
  font-weight: 600;
  text-transform: capitalize;
  margin-top: 5px;
  color: var(--muted);
}

.bottom-area {
  margin-top: auto;
  padding-top: 10px;
  border-top: 1px solid var(--border);
}

.account-btn {
  padding-bottom: 20px;
}

/* Submenu Styles */
.floating-submenu {
  position: fixed;
  background: var(--glass);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid var(--glass-border);
  border-radius: 12px;
  box-shadow: 0 15px 45px rgba(0,0,0,0.5);
  z-index: 10000;
  width: 190px;
  padding: 8px;
}

body.light-mode .floating-submenu {
  background: rgba(255,255,255,0.9);
  border-color: #e2e8f0;
  box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

.submenu-inner {
  display: flex;
  flex-direction: column;
}

.submenu-item {
  display: flex;
  align-items: center;
  padding: 12px 14px;
  cursor: pointer;
  position: relative;
  transition: background 0.2s;
}

.submenu-item:hover {
  background: #2d3748;
}

body.light-mode .submenu-item:hover {
  background: #f8fafc;
}

.submenu-item .item-icon {
  width: 18px;
  height: 18px;
  margin-right: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--accent);
  opacity: 0.9;
}

body.light-mode .submenu-item .item-icon {
  color: #3b82f6;
}

.logout-item .item-icon {
  color: #f43f5e !important;
  opacity: 1;
}

.item-label {
  font-size: 14px;
  font-weight: 700;
  color: #f8fafc; /* Default (Dark) */
}

body.light-mode .item-label {
  color: #0c335a;
}

.submenu-item .line {
  position: absolute;
  bottom: 0;
  left: 32px;
  right: 14px;
  height: 1px;
  background: #334155;
}

body.light-mode .submenu-item .line {
  background: #f1f5f9;
}

.logout-item .item-label { color: #ef4444; }
.logout-item .dot { background: #ef4444; }

/* Transitions */
.pop-enter-active, .pop-leave-active {
  transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.pop-enter-from, .pop-leave-to {
  opacity: 0;
  transform: translateX(-10px) scale(0.95);
}
</style>
