<template>
  <aside class="side-nav">
    <div class="side-nav-inner">

      <!-- Logo -->
      <div class="brand-icon">
        <img src="https://gps.fleetrite.io/img/logo_small.png" alt="Logo" class="mini-logo" />
      </div>

      <!-- Main Nav -->
      <nav class="nav-links">
        <button class="nav-item" :class="{ active: activeTab === 'Tracking' }" title="Tracking" @click="$emit('nav-action', 'tracking')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle><line x1="12" y1="2" x2="12" y2="5"></line><line x1="12" y1="19" x2="12" y2="22"></line><line x1="2" y1="12" x2="5" y2="12"></line><line x1="19" y1="12" x2="22" y2="12"></line></svg>
          <span class="label">Tracking</span>
        </button>

        <button class="nav-item" title="Help" @click="$emit('nav-action', 'help')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
          <span class="label">Help</span>
        </button>

        <button class="nav-item" title="Settings" @click="$emit('nav-action', 'settings')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
          <span class="label">Settings</span>
        </button>

        <button class="nav-item" title="Reports" @click="$emit('nav-action', 'reports')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
          <span class="label">Reports</span>
        </button>

        <button class="nav-item" title="Events" @click="$emit('nav-action', 'events')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
          <span class="label">Events</span>
        </button>

        <button class="nav-item" title="Dashboard" @click="$emit('nav-action', 'dashboard')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="M18 9l-5 5-2-2-4 4"></path></svg>
          <span class="label">Dashboard</span>
        </button>

        <!-- More ··· Button -->
        <div class="more-wrapper" ref="moreWrapperRef">
          <button 
            class="nav-item more-btn" 
            :class="{ 'more-active': moreOpen }" 
            ref="moreBtnRef"
            title="More" 
            @click="toggleMore"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
              <circle cx="12" cy="5"  r="1.8"></circle>
              <circle cx="12" cy="12" r="1.8"></circle>
              <circle cx="12" cy="19" r="1.8"></circle>
            </svg>
          </button>

          <!-- More Popup — rendered at body level to escape overflow clipping -->
          <Teleport to="body">
            <div v-if="moreOpen" class="more-popup-backdrop" @click="moreOpen = false"></div>
            <transition :name="isMobile ? 'popup-mobile' : 'popup-fade'">
              <div 
                v-if="moreOpen" 
                class="more-popup-fixed"
                :class="{ 'mobile-popup-mode': isMobile }"
                :style="popupStyle"
              >
                <div class="popup-title">More Tools</div>
                <div class="popup-grid">
                  <button class="popup-item" v-for="item in moreItems" :key="item.label">
                    <div class="popup-icon" v-html="item.svg"></div>
                    <span class="popup-label">{{ item.label }}</span>
                  </button>
                </div>
              </div>
            </transition>
          </Teleport>
        </div>
      </nav>

      <!-- Bottom Nav -->
      <div class="nav-bottom">
        <div class="nav-divider"></div>

        <div class="nav-item admin-item" title="Admin">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          <span class="label">Admin</span>
        </div>

        <div class="nav-divider"></div>

        <button class="nav-item" title="Language">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
          <span class="label">English</span>
        </button>

        <button class="nav-item" title="Delegates">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          <span class="label">Delegates</span>
        </button>

        <button class="nav-item theme-toggle" :title="themeStore.isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'" @click="themeStore.toggleTheme">
          <svg v-if="themeStore.isDarkMode" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <circle cx="12" cy="12" r="5"></circle>
             <line x1="12" y1="1" x2="12" y2="3"></line>
             <line x1="12" y1="21" x2="12" y2="23"></line>
             <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
             <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
             <line x1="1" y1="12" x2="3" y2="12"></line>
             <line x1="21" y1="12" x2="23" y2="12"></line>
             <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
             <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
          </svg>
          <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
          </svg>
          <span class="label">{{ themeStore.isDarkMode ? 'Light' : 'Dark' }}</span>
        </button>

        <button class="nav-item logout-item" title="Logout" @click="$emit('nav-action', 'logout')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          <span class="label">Logout</span>
        </button>
      </div>

    </div>
  </aside>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useThemeStore } from '../../stores/theme';

const themeStore = useThemeStore();

defineProps(['activeTab']);
defineEmits(['nav-action']);

const moreOpen = ref(false);
const moreWrapperRef = ref(null);
const moreBtnRef = ref(null);

const moreItems = [
  { label: 'RFID',        svg: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M9 9h6M9 12h6M9 15h4"/></svg>` },
  { label: 'DTC',         svg: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>` },
  { label: 'Maintenance', svg: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>` },
  { label: 'Expenses',    svg: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>` },
  { label: 'Control',     svg: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 12h4"/><path d="M14 12h4"/><circle cx="12" cy="12" r="1.5"/></svg>` },
  { label: 'Analytics',   svg: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3v18h18"/><path d="M18 9l-5 5-2-2-4 4"/></svg>` },
];

// Compute popup position dynamically based on screen mode
const isMobile = ref(false);

function checkMobile() {
  isMobile.value = window.innerWidth <= 768;
}

const popupStyle = computed(() => {
  if (!moreBtnRef.value) return {};
  const rect = moreBtnRef.value.getBoundingClientRect();
  
  if (isMobile.value) {
    return {
      position: 'fixed',
      left: '50%',
      top: 'auto',
      bottom: '80px',
      transform: 'translateX(-50%)',
      zIndex: 99999,
      width: '90vw',
      maxWidth: '320px',
    };
  }

  return {
    position: 'fixed',
    left: rect.right + 12 + 'px',
    top:  rect.top + rect.height / 2 + 'px',
    transform: 'translateY(-50%)',
    zIndex: 99999,
  };
});

function toggleMore() {
  moreOpen.value = !moreOpen.value;
}

function handleOutside(e) {
  if (moreWrapperRef.value && !moreWrapperRef.value.contains(e.target)) {
    // also allow clicking within teleported popup
    const popup = document.querySelector('.more-popup-fixed');
    if (popup && popup.contains(e.target)) return;
    moreOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener('mousedown', handleOutside);
  window.addEventListener('resize', checkMobile);
  checkMobile();
});
onUnmounted(() => {
  document.removeEventListener('mousedown', handleOutside);
  window.removeEventListener('resize', checkMobile);
});
</script>

<style scoped>
.side-nav {
  width: 66px;
  min-width: 66px;
  background: var(--card);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  z-index: 1000;
  box-shadow: 2px 0 10px rgba(0,0,0,0.25);
  height: 100vh;
  flex-shrink: 0;
}

.side-nav-inner {
  height: 100%;
  overflow-y: auto;
  overflow-x: visible;
  display: flex;
  flex-direction: column;
  padding: 4px 0;
  scrollbar-width: none;
}
.side-nav-inner::-webkit-scrollbar { display: none; }

/* Logo */
.brand-icon {
  padding: 8px 0 6px;
  display: flex;
  justify-content: center;
  flex-shrink: 0;
}
.mini-logo {
  width: 28px;
  height: 28px;
  border-radius: 7px;
  filter: drop-shadow(0 2px 6px rgba(0,0,0,0.3));
}

/* Nav Items – icon only */
.nav-links {
  display: flex;
  flex-direction: column;
  width: 100%;
}

.nav-item {
  background: transparent;
  border: none;
  color: var(--muted);
  width: 100%;
  padding: 8px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  cursor: pointer;
  transition: all 0.2s;
  border-left: 2px solid transparent;
  position: relative;
  flex-shrink: 0;
}
.nav-item .label {
  font-size: 8px;
  font-weight: 600;
  letter-spacing: 0.2px;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  width: 100%;
  text-transform: capitalize;
}
.nav-item:hover {
  color: var(--text);
  background: var(--border);
}
.nav-item.active {
  background: rgba(79, 124, 255, 0.12);
  color: var(--accent);
  border-left-color: var(--accent);
}

/* More button */
.more-wrapper { position: relative; }
.more-btn { opacity: 0.4; }
.more-btn:hover, .more-btn.more-active {
  opacity: 1;
  color: var(--accent);
  background: rgba(79,124,255,0.1);
}
/* No tooltip for more button since it has a custom popup */
.more-btn::after { display: none; }

/* ── More Popup ─────────────────────────────── */
.more-popup {
  position: absolute;
  left: calc(100% + 12px);
  top: 50%;
  transform: translateY(-50%);
  background: var(--glass);
  backdrop-filter: blur(12px);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 14px;
  z-index: 9999;
  box-shadow: 0 16px 48px rgba(0,0,0,0.65);
  width: 196px;
}

.popup-arrow {
  position: absolute;
  left: -6px;
  top: 50%;
  width: 11px;
  height: 11px;
  background: var(--glass);
  border-left: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
  transform: translateY(-50%) rotate(45deg);
}

.popup-title {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--muted);
  margin-bottom: 10px;
  padding: 0 2px;
}

.popup-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 6px;
}

.popup-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 10px 4px 8px;
  background: var(--border);
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--muted);
  cursor: pointer;
  transition: all 0.18s;
  min-width: 0;
}
.popup-item:hover {
  background: rgba(79,124,255,0.15);
  color: var(--accent);
  border-color: rgba(79,124,255,0.3);
  transform: translateY(-1px);
}
.popup-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 0;
}
.popup-label {
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.2px;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  width: 100%;
}

/* Transitions */
.popup-fade-enter-active, .popup-fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.popup-fade-enter-from, .popup-fade-leave-to {
  opacity: 0;
  transform: translateY(-50%) translateX(-8px) !important;
}
.popup-fade-enter-to, .popup-fade-leave-from {
  opacity: 1;
  transform: translateY(-50%) translateX(0) !important;
}

.popup-mobile-enter-active, .popup-mobile-leave-active {
  transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
}
.popup-mobile-enter-from, .popup-mobile-leave-to {
  opacity: 0;
  transform: translate(-50%, 20px) !important;
}
.popup-mobile-enter-to, .popup-mobile-leave-from {
  opacity: 1;
  transform: translate(-50%, 0) !important;
}

/* Bottom section */
.nav-bottom {
  margin-top: auto;
  width: 100%;
  display: flex;
  flex-direction: column;
}

.nav-divider {
  height: 1px;
  background: rgba(255,255,255,0.06);
  margin: 3px 6px;
}

.admin-item {
  border-left-color: transparent !important;
  cursor: pointer !important;
}
.admin-item:hover { 
  background: var(--border) !important;
  color: var(--text) !important;
}

.logout-item:hover { color: #ff5a78; }

/* ── Mobile View (Horizontal Scroll) ── */
@media (max-width: 768px) {
  .side-nav {
    width: 100%;
    min-width: 100%;
    height: 60px;
    border-right: none;
    border-bottom: 1px solid var(--border);
    flex-direction: row;
  }

  .side-nav-inner {
    flex-direction: row;
    width: 100%;
    height: 100%;
    padding: 0 10px;
    align-items: center;
    overflow-x: auto;
    overflow-y: hidden;
    gap: 5px;
  }

  .side-nav-inner::-webkit-scrollbar {
    display: none;
  }

  .brand-icon {
    margin-bottom: 0;
    margin-right: 15px;
    padding: 0;
  }

  .nav-links {
    flex-direction: row;
    width: auto;
    gap: 5px;
    align-items: center;
  }

  .nav-item {
    width: 50px;
    height: 50px;
    padding: 0;
    border-left: none;
    border-bottom: 3px solid transparent;
    border-radius: 8px;
    justify-content: center;
  }
  .nav-item .label {
    display: none; /* Hide labels on mobile */
  }

  .nav-item.active {
    border-left-color: transparent;
    border-bottom-color: var(--accent);
  }

  .nav-bottom {
    margin-top: 0;
    margin-left: auto;
    flex-direction: row;
    width: auto;
    gap: 5px;
    align-items: center;
  }

  .nav-divider { display: none; }

  .admin-item {
    width: 50px !important;
    height: 50px;
    margin-top: 0;
  }
}
</style>

<!-- Global styles for teleported popup (not scoped) -->
<style>
.more-popup-fixed {
  background: var(--glass);
  backdrop-filter: blur(16px);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 14px;
  box-shadow: 0 16px 48px rgba(0,0,0,0.4);
  width: 200px;
}

.more-popup-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0,0,0,0.4);
  backdrop-filter: blur(2px);
  z-index: 99998;
}

.more-popup-fixed.mobile-popup-mode {
  padding: 20px;
  border-radius: 20px;
  border-color: rgba(255,255,255,0.15);
}

.more-popup-fixed .popup-title {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--muted);
  margin-bottom: 10px;
}

.more-popup-fixed.mobile-popup-mode .popup-title {
  font-size: 12px;
  text-align: center;
  margin-bottom: 16px;
}

.more-popup-fixed .popup-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 6px;
}

.more-popup-fixed.mobile-popup-mode .popup-grid {
  gap: 12px;
}

.more-popup-fixed .popup-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 10px 4px 8px;
  background: var(--border);
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--muted);
  cursor: pointer;
  transition: all 0.18s;
  min-width: 0;
}

.more-popup-fixed.mobile-popup-mode .popup-item {
  padding: 14px 8px;
  background: var(--border);
}

.more-popup-fixed .popup-item:hover {
  background: rgba(79,124,255,0.18);
  color: #4f7cff;
  border-color: rgba(79,124,255,0.35);
  transform: translateY(-1px);
}
.more-popup-fixed .popup-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 0;
}
.more-popup-fixed .popup-label {
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.2px;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  width: 100%;
}
.more-popup-fixed.mobile-popup-mode .popup-label {
  font-size: 10px;
}
</style>
