<template>
  <div class="events-panel-modern" v-show="isPanelOpen">
    <!-- Premium Header with Search & Pagination -->
    <div class="panel-header-modern">
      <div class="search-section">
        <div class="search-box-pill">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="text" v-model="searchModel" placeholder="Search events..." />
        </div>
      </div>

      <div class="header-action-row">
        <div class="total-badge">
          <span class="label">Total</span>
          <span class="value">{{ totalItemsCount || 0 }}</span>
        </div>

        <div class="pagination-modern">
          <button class="arrow-btn" :disabled="currentPageModel <= 1" @click="goToPrevPage">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
          </button>
          
          <div class="page-indicator">
            <span class="current">{{ currentPageModel }}</span>
            <span class="sep">.</span>
            <span class="total">{{ totalPages }}</span>
          </div>

          <button class="arrow-btn" :disabled="currentPageModel >= totalPages" @click="goToNextPage">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
          </button>
        </div>

        <div class="menu-action-wrapper">
          <button class="more-options-btn" @click.stop="showMenu = !showMenu">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
              <circle cx="12" cy="5" r="2"></circle>
              <circle cx="12" cy="12" r="2"></circle>
              <circle cx="12" cy="19" r="2"></circle>
            </svg>
          </button>
          <!-- Menu Dropdown -->
          <div class="dropdown-popup" v-if="showMenu">
             <div class="dropdown-option" @click="handleRefresh">Refresh Data</div>
             <div class="dropdown-option" @click="handleExport">Export to Excel</div>
             <div class="dropdown-option destructive" @click="handleDeleteAll">Delete All</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Scrollable Events Grid -->
    <div class="events-scroll-area custom-scrollbar">
      <div v-if="loading" class="events-loading">
        <div class="spinner"></div>
        <span>Updating events...</span>
      </div>

      <div v-else-if="filteredEvents.length === 0" class="empty-events-state">
        <div class="empty-icon">📅</div>
        <p>No events found for today</p>
      </div>

      <div v-else class="events-grid">
        <div 
          v-for="event in filteredEvents" 
          :key="event.id" 
          class="event-card-modern"
        >
          <div class="card-left">
            <div class="status-icon-wrapper" :class="getEventCategory(event.event)">
              <component :is="getEventIcon(event.event)" />
            </div>
            <div class="event-details-main">
              <div class="event-type-name">{{ event.event }}</div>
              <div class="event-time-stamp">{{ formatEventTime(event.time) }}</div>
            </div>
          </div>

          <div class="card-right">
             <div class="vehicle-tag">
               <svg class="v-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                 <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
               </svg>
               <span class="v-id">{{ event.object }}</span>
             </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, h } from 'vue';

const props = defineProps({
  isPanelOpen: { type: Boolean, default: true },
  eventsList:  { type: Array,   default: () => [] },
  searchQuery: { type: String,  default: '' },
  currentPage: { type: Number,  default: 1 },
  totalPages:  { type: Number,  default: 1 },
  perPage:     { type: Number,  default: 25 },
  sortIdx:     { type: String,  default: 'dt_tracker' },
  sortOrd:     { type: String,  default: 'desc' },
  loading:     { type: Boolean, default: false }
});

const emit = defineEmits([
  'update:searchQuery', 'update:currentPage', 'update:perPage',
  'update:sortIdx', 'update:sortOrd', 'refresh-events',
  'delete-all-events', 'export-events'
]);

const showMenu = ref(false);

const searchModel = computed({
  get: () => props.searchQuery,
  set: (val) => emit('update:searchQuery', val)
});

const currentPageModel = computed({
  get: () => props.currentPage,
  set: (val) => {
    const parsed = Number(val);
    if (Number.isNaN(parsed) || parsed < 1) { emit('update:currentPage', 1); return; }
    emit('update:currentPage', parsed);
  }
});

const perPageModel = computed({
  get: () => props.perPage,
  set: (val) => emit('update:perPage', Number(val))
});

const totalItemsCount = computed(() => props.eventsList.length);

const filteredEvents = computed(() => {
  if (!searchModel.value) return props.eventsList;
  const q = searchModel.value.toLowerCase();
  return props.eventsList.filter(e =>
    (e.object || '').toLowerCase().includes(q) ||
    (e.event || '').toLowerCase().includes(q)
  );
});

// Event Icon Helpers
const SpeedIcon = () => h('svg', { width: 16, height: 16, viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", "stroke-width": "2.5" }, [
  h('circle', { cx: 12, cy: 12, r: 10 }),
  h('polyline', { points: "12 6 12 12 16 14" })
]);

const StoppedIcon = () => h('svg', { width: 16, height: 16, viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", "stroke-width": "2.5" }, [
  h('circle', { cx: 12, cy: 12, r: 10 }),
  h('line', { x1: "8", y1: "12", x2: "16", y2: "12" })
]);

const OfflineIcon = () => h('svg', { width: 16, height: 16, viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", "stroke-width": "2.5" }, [
  h('path', { d: "M5 12.55a11 11 0 0 1 14.08 0" }),
  h('path', { d: "M1.42 9a16 16 0 0 1 21.16 0" }),
  h('path', { d: "M8.53 16.11a6 6 0 0 1 6.95 0" }),
  h('line', { x1: "12", y1: "20", x2: "12.01", y2: "20" })
]);

const DefaultIcon = () => h('svg', { width: 16, height: 16, viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", "stroke-width": "2.5" }, [
  h('circle', { cx: 12, cy: 12, r: 10 }),
  h('line', { x1: "12", y1: "8", x2: "12", y2: "12" }),
  h('line', { x1: "12", y1: "16", x2: "12.01", y2: "16" })
]);

function getEventIcon(eventName) {
  const name = (eventName || '').toLowerCase();
  if (name.includes('speed')) return SpeedIcon;
  if (name.includes('stop')) return StoppedIcon;
  if (name.includes('off')) return OfflineIcon;
  return DefaultIcon;
}

function getEventCategory(eventName) {
  const name = (eventName || '').toLowerCase();
  if (name.includes('speed')) return 'category-speed';
  if (name.includes('stop')) return 'category-stop';
  if (name.includes('off')) return 'category-offline';
  return 'category-default';
}

function formatEventTime(timeStr) {
  if (!timeStr) return '';
  // Assuming format is already pretty clean, e.g. "2024-04-16 11:48:17"
  // If we wanted to split into two lines as in design:
  return timeStr; 
}

const handleClickOutside = (e) => {
  if (!e.target.closest('.menu-action-wrapper')) showMenu.value = false;
};

function goToPrevPage() { if (currentPageModel.value > 1) currentPageModel.value--; }
function goToNextPage() { if (currentPageModel.value < props.totalPages) currentPageModel.value++; }
function handleRefresh() { emit('refresh-events'); showMenu.value = false; }
function handleDeleteAll() { emit('delete-all-events'); showMenu.value = false; }
function handleExport() { emit('export-events'); showMenu.value = false; }

onMounted(() => { document.addEventListener('click', handleClickOutside); });
onUnmounted(() => { document.removeEventListener('click', handleClickOutside); });
</script>

<style scoped>
.events-panel-modern {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--card);
}

.panel-header-modern {
  padding: 16px;
  border-bottom: 1px solid var(--border);
  background: var(--card);
}

.search-box-pill {
  position: relative;
  width: 100%;
  margin-bottom: 16px;
}

.search-box-pill svg {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

.search-box-pill input {
  width: 100%;
  padding: 11px 16px 11px 40px;
  background: var(--input-bg);
  border: 1px solid var(--border);
  border-radius: 12px;
  color: var(--text);
  font-size: 14px;
  outline: none;
  transition: all 0.2s;
}

.search-box-pill input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 4px rgba(79, 124, 255, 0.1);
}

.header-action-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.total-badge {
  display: flex;
  align-items: baseline;
  gap: 6px;
}

.total-badge .label {
  font-size: 14px;
  font-weight: 700;
  color: var(--accent);
}

.total-badge .value {
  font-size: 13px;
  color: var(--muted);
  font-weight: 600;
}

.pagination-modern {
  display: flex;
  align-items: center;
  gap: 10px;
}

.arrow-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.arrow-btn:hover:not(:disabled) {
  background: #e2e8f0;
  color: #0f172a;
}

.arrow-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.page-indicator {
  display: flex;
  align-items: center;
  gap: 6px;
}

.page-indicator span {
  font-size: 14px;
  font-weight: 700;
}

.page-indicator .current {
  color: #fff;
  background: #0055ff;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 12px;
}

.page-indicator .total {
  color: #94a3b8;
}

.more-options-btn {
  background: #f1f5f9;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.menu-action-wrapper {
  position: relative;
}

.dropdown-popup {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 12px;
  min-width: 160px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.25);
  z-index: 100;
  overflow: hidden;
  padding: 4px;
}

.dropdown-option {
  padding: 10px 16px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  color: var(--text);
  border-radius: 8px;
}

.dropdown-option:hover {
  background: var(--border);
}

.dropdown-option.destructive {
  color: #ef4444;
}

/* Events Area */
.events-scroll-area {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

.events-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.event-card-modern {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 14px 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.event-card-modern:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.08);
  border-color: var(--accent);
}

.card-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.status-icon-wrapper {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.category-speed { background: #eefdf6; color: #10b981; }
.category-stop  { background: #fef2f2; color: #ef4444; }
.category-offline { background: #f8fafc; color: #64748b; }
.category-default { background: #f1f5f9; color: #94a3b8; }

.event-type-name {
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
}

.event-time-stamp {
  font-size: 12px;
  color: var(--muted);
  font-weight: 500;
  margin-top: 2px;
}

.card-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.vehicle-tag {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #f8fafc;
  padding: 4px 10px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.v-icon { color: #64748b; }

.v-id {
  font-size: 12px;
  font-weight: 700;
  color: #334155;
}

.events-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 200px;
  color: var(--muted);
  gap: 12px;
}

.spinner {
  width: 24px;
  height: 24px;
  border: 3px solid rgba(0, 85, 255, 0.1);
  border-top-color: #0055ff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.empty-events-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: var(--muted);
  text-align: center;
}

.empty-icon { font-size: 40px; margin-bottom: 12px; opacity: 0.5; }

.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.05); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.1); }
</style>