<template>
  <div class="tab-content-wrapper archive-wrapper">
    <div class="archive-header" v-show="isPanelOpen">
       <h2>Route History</h2>
       <button class="toggle-btn layout-fixed-btn" @click="$emit('toggle-panel')" title="Collapse">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
       </button>
    </div>
    <!-- Collapsed toggle (always visible when closed) -->
    <div class="archive-collapsed-btn" v-show="!isPanelOpen">
      <button class="toggle-btn" @click="$emit('toggle-panel')" title="Expand">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
      </button>
    </div>
    <div class="archive-controls" v-show="isPanelOpen">
      <!-- Custom Searchable Object Selector -->
      <div class="control-group">
        <label>Object</label>
        <div class="custom-select-wrapper" ref="selectWrapper">
          <!-- Trigger Button -->
          <div 
            class="custom-select-trigger" 
            :class="{ open: dropdownOpen, 'has-value': selectedObj }"
            @click="toggleDropdown"
          >
            <div class="trigger-content">
              <span v-if="selectedObj" class="trigger-vehicle">
                <span class="trigger-dot" :class="selectedObj.status?.toLowerCase()"></span>
                {{ selectedObj.name }}
              </span>
              <span v-else class="trigger-placeholder">Select object...</span>
            </div>
            <svg class="trigger-arrow" :class="{ rotated: dropdownOpen }" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>

          <!-- Dropdown -->
          <div v-show="dropdownOpen" class="custom-select-dropdown">
            <!-- Search Input -->
            <div class="dropdown-search">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" opacity="0.5">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input 
                ref="searchInput"
                v-model="searchQuery"
                type="text" 
                placeholder="Search vehicle..."
                @click.stop
                @keydown.escape="dropdownOpen = false"
              />
              <button v-if="searchQuery" class="clear-search" @click.stop="searchQuery = ''" >×</button>
            </div>

            <!-- Options List -->
            <div class="dropdown-list">
              <div
                v-for="obj in filteredFleet"
                :key="obj.imei"
                class="dropdown-option"
                :class="{ selected: obj.imei === historyImei }"
                @click="selectObject(obj)"
              >
                <span class="option-dot" :class="obj.status?.toLowerCase()"></span>
                <span class="option-name">{{ obj.name }}</span>
                <span class="option-imei">{{ obj.imei }}</span>
              </div>
              <div v-if="filteredFleet.length === 0" class="dropdown-empty">
                No vehicles found
              </div>
            </div>
          </div>
        </div>
      </div>


      <!-- Custom Period Selector -->
      <div class="control-group">
        <label>Period</label>
        <div class="custom-select-wrapper" ref="periodWrapper">
          <div 
            class="custom-select-trigger"
            :class="{ open: periodDropdownOpen }"
            @click="togglePeriodDropdown"
          >
            <span class="trigger-content">
              <span class="trigger-text">{{ periodOptions.find(p => p.value === historyPeriod)?.label || 'Select period...' }}</span>
            </span>
            <svg class="trigger-arrow" :class="{ rotated: periodDropdownOpen }" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
          <div v-show="periodDropdownOpen" class="custom-select-dropdown period-dropdown">
            <div class="dropdown-list">
              <div
                v-for="opt in periodOptions"
                :key="opt.value"
                class="dropdown-option"
                :class="{ selected: historyPeriod === opt.value }"
                @click="selectPeriod(opt.value)"
              >
                <span class="period-icon">{{ opt.icon }}</span>
                <span class="option-name">{{ opt.label }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Custom Date range: floating popup triggered when period===custom -->
      <transition name="date-popup">
        <div v-if="historyPeriod === 'custom'" class="custom-date-popup" ref="datePopupRef">
          <div class="date-popup-header">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Custom Range
          </div>
          <div class="date-popup-body">
            <div class="date-field">
              <label>From</label>
              <input type="datetime-local" :value="historyFrom" @input="$emit('update:historyFrom', $event.target.value)" class="date-input" />
            </div>
            <div class="date-field">
              <label>To</label>
              <input type="datetime-local" :value="historyTo" @input="$emit('update:historyTo', $event.target.value)" class="date-input" />
            </div>
          </div>
        </div>
      </transition>

      <button class="show-btn" @click="$emit('fetch-history')" :disabled="historyLoading || !historyImei">
        {{ historyLoading ? 'Loading...' : 'Show History' }}
      </button>
    </div>

    <div v-if="historyData.length > 0 && isPanelOpen" class="playback-controls">
      <div class="playback-header">
        <h3>Playback</h3>
        <span class="p-status">{{ historyPlayStatus }}</span>
      </div>
      
      <div class="p-slider">
        <input type="range" min="0" :max="historyData.length - 1" :value="historyIndex" @input="onSliderInput" />
        <div class="p-time">{{ currentHistoryTime }}</div>
      </div>

      <div class="p-actions">
        <button class="p-btn" @click="$emit('toggle-play')">
           <svg v-if="!isHistoryPlaying" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
           <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
        </button>
        <button class="p-btn" @click="$emit('stop-playback')">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="4" width="16" height="16"></rect></svg>
        </button>
        
        <!-- Custom Speed Select -->
        <div class="custom-select-wrapper speed-wrapper" ref="speedWrapper">
          <div class="custom-select-trigger speed-trigger" :class="{ open: speedDropdownOpen }" @click="toggleSpeedDropdown">
            <span class="trigger-text">{{ speedOptions.find(s => s.value === historyPlaybackSpeed)?.label || '1x' }}</span>
            <svg class="trigger-arrow" :class="{ rotated: speedDropdownOpen }" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
          <div v-show="speedDropdownOpen" class="custom-select-dropdown speed-dropdown">
            <div class="dropdown-list">
              <div
                v-for="opt in speedOptions"
                :key="opt.value"
                class="dropdown-option speed-option"
                :class="{ selected: historyPlaybackSpeed === opt.value }"
                @click="selectSpeed(opt.value)"
              >{{ opt.label }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  isPanelOpen: Boolean,
  fleetData: Array,
  historyImei: String,
  historyPeriod: String,
  historyFrom: String,
  historyTo: String,
  historyLoading: Boolean,
  historyData: Array,
  historyPlayStatus: String,
  historyIndex: Number,
  currentHistoryTime: String,
  isHistoryPlaying: Boolean,
  historyPlaybackSpeed: Number
});

const emit = defineEmits([
  'toggle-panel',
  'update:historyImei',
  'update:historyPeriod',
  'update:historyFrom',
  'update:historyTo',
  'update:historyPlaybackSpeed',
  'update:historyIndex',
  'period-change',
  'fetch-history',
  'toggle-play',
  'stop-playback',
  'preview-update'
]);

// ── Options ──────────────────────────────────────────────
const periodOptions = [
  { value: 'today',        label: 'Today',        icon: '📅' },
  { value: 'yesterday',    label: 'Yesterday',    icon: '📆' },
  { value: 'last_hour',    label: 'Last hour',    icon: '⏱️' },
  { value: 'current_week', label: 'Current week', icon: '📊' },
  { value: 'last_week',    label: 'Last week',    icon: '📊' },
  { value: 'custom',       label: 'Custom range', icon: '✏️' },
];

const speedOptions = [
  { value: 1000, label: '1x' },
  { value: 500,  label: '2x' },
  { value: 250,  label: '4x' },
  { value: 100,  label: '10x' },
];

// ── Object Dropdown ──────────────────────────────────────
const dropdownOpen = ref(false);
const searchQuery = ref('');
const searchInput = ref(null);
const selectWrapper = ref(null);

const selectedObj = computed(() => {
  if (!props.historyImei || !props.fleetData) return null;
  return props.fleetData.find(o => o.imei === props.historyImei) || null;
});

const filteredFleet = computed(() => {
  if (!props.fleetData) return [];
  const q = searchQuery.value.toLowerCase().trim();
  if (!q) return props.fleetData;
  return props.fleetData.filter(o =>
    (o.name || '').toLowerCase().includes(q) ||
    (o.imei || '').toLowerCase().includes(q)
  );
});

function toggleDropdown() {
  closeAll('object');
  dropdownOpen.value = !dropdownOpen.value;
  if (dropdownOpen.value) nextTick(() => searchInput.value?.focus());
}
function selectObject(obj) {
  emit('update:historyImei', obj.imei);
  dropdownOpen.value = false;
  searchQuery.value = '';
}

// ── Period Dropdown ──────────────────────────────────────
const periodDropdownOpen = ref(false);
const periodWrapper = ref(null);

function togglePeriodDropdown() {
  closeAll('period');
  periodDropdownOpen.value = !periodDropdownOpen.value;
}
function selectPeriod(value) {
  emit('update:historyPeriod', value);
  emit('period-change');
  periodDropdownOpen.value = false;
}

// ── Speed Dropdown ───────────────────────────────────────
const speedDropdownOpen = ref(false);
const speedWrapper = ref(null);

function toggleSpeedDropdown() {
  closeAll('speed');
  speedDropdownOpen.value = !speedDropdownOpen.value;
}
function selectSpeed(value) {
  emit('update:historyPlaybackSpeed', value);
  speedDropdownOpen.value = false;
}

// ── Close all except one ─────────────────────────────────
function closeAll(except) {
  if (except !== 'object') dropdownOpen.value = false;
  if (except !== 'period') periodDropdownOpen.value = false;
  if (except !== 'speed')  speedDropdownOpen.value = false;
}

function handleOutsideClick(e) {
  const inObject = selectWrapper.value?.contains(e.target);
  const inPeriod = periodWrapper.value?.contains(e.target);
  const inSpeed  = speedWrapper.value?.contains(e.target);
  if (!inObject) dropdownOpen.value = false;
  if (!inPeriod) periodDropdownOpen.value = false;
  if (!inSpeed)  speedDropdownOpen.value = false;
}

onMounted(() => document.addEventListener('mousedown', handleOutsideClick));
onUnmounted(() => document.removeEventListener('mousedown', handleOutsideClick));

// ── Slider ───────────────────────────────────────────────
function onSliderInput(event) {
  const newIndex = parseInt(event.target.value, 10);
  emit('update:historyIndex', newIndex);
  emit('preview-update', newIndex);
}
</script>


<style scoped>
.archive-wrapper {
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow-y: auto;
  padding-bottom: 80px; /* Give space for absolute dropdowns at the bottom */
}
.archive-header {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.archive-header h2 { margin: 0; font-size: 15px; font-weight: 700; }

.toggle-btn {
  background: rgba(255,255,255,0.05);
  border: 1px solid var(--border);
  color: var(--text);
  border-radius: 8px;
  width: 32px; height: 32px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.2s;
}
.toggle-btn:hover { background: var(--border); color: var(--accent); }
.layout-fixed-btn { width: 28px; height: 28px; }

.archive-controls {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}

/* Collapsed state toggle button */
.archive-collapsed-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px 0;
}

/* Custom Date Range Popup */
.custom-date-popup {
  background: var(--bg1);
  border: 1px solid var(--border);
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
.date-popup-header {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 9px 12px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--accent);
  background: rgba(79,124,255,0.08);
  border-bottom: 1px solid rgba(79,124,255,0.15);
}
.date-popup-body {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.date-field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.date-field label {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--muted);
}

/* Transition */
.date-popup-enter-active, .date-popup-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.date-popup-enter-from, .date-popup-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}


.control-group { display: flex; flex-direction: column; gap: 6px; }
.control-group label {
  font-size: 10px;
  color: var(--muted);
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.6px;
}

/* ===== Custom Searchable Select ===== */
.custom-select-wrapper {
  position: relative;
}

.custom-select-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--input-bg);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 10px 12px;
  cursor: pointer;
  transition: all 0.2s;
  min-height: 42px;
}
.custom-select-trigger:hover { border-color: var(--accent); }
.custom-select-trigger.open {
  border-color: var(--accent);
  box-shadow: 0 0 0 4px rgba(79,124,255,0.1);
}

.trigger-content { flex: 1; min-width: 0; }
.trigger-placeholder { color: var(--muted); font-size: 13px; }
.trigger-vehicle {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.trigger-arrow {
  flex-shrink: 0;
  color: var(--muted);
  transition: transform 0.2s;
  margin-left: 6px;
}
.trigger-arrow.rotated { transform: rotate(180deg); }

/* Status dot */
.trigger-dot, .option-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
  background: rgba(255,255,255,0.2);
}
.trigger-dot.moving, .option-dot.moving { background: #36ffb4; }
.trigger-dot.idle, .option-dot.idle     { background: #ffcc00; }
.trigger-dot.offline, .option-dot.offline { background: #ff5a78; }

/* Dropdown */
.custom-select-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 10px;
  z-index: 2000;
  box-shadow: 0 12px 32px rgba(0,0,0,0.25);
  overflow: hidden;
}

.dropdown-search {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border-bottom: 1px solid var(--border);
  background: var(--input-bg);
}
.dropdown-search input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: var(--text);
  font-size: 13px;
}
.dropdown-search input::placeholder { color: var(--muted); }
.clear-search {
  background: none; border: none; color: var(--muted);
  cursor: pointer; font-size: 16px; line-height: 1;
  padding: 0 2px;
  transition: color 0.2s;
}
.clear-search:hover { color: var(--text); }

.dropdown-list {
  max-height: 220px;
  overflow-y: auto;
}
.dropdown-list::-webkit-scrollbar { width: 4px; }
.dropdown-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }

.dropdown-option {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 14px;
  cursor: pointer;
  transition: background 0.15s;
  border-bottom: 1px solid rgba(255,255,255,0.03);
}
.dropdown-option:hover { background: var(--border); }
.dropdown-option.selected { background: var(--accent); color: #fff; }
.dropdown-option.selected .option-name, .dropdown-option.selected .option-imei { color: #fff; }

.option-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.option-imei {
  font-size: 10px;
  color: var(--muted);
  flex-shrink: 0;
}

.dropdown-empty {
  padding: 20px;
  text-align: center;
  color: var(--muted);
  font-size: 13px;
}

/* Native selects (Period + Speed) */
.fleet-select, .date-input {
  background: rgba(0,0,0,0.2);
  border: 1px solid var(--border);
  color: #fff;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
  transition: border-color 0.2s;
}
.fleet-select:focus, .date-input:focus { border-color: var(--accent); }

.custom-dates {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 12px;
  background: var(--input-bg);
  border-radius: 8px;
  border: 1px solid var(--border);
}

.show-btn {
  background: var(--accent);
  color: #fff;
  border: none;
  padding: 12px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: filter 0.2s, transform 0.1s;
  letter-spacing: 0.3px;
}
.show-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.show-btn:hover:not(:disabled) { filter: brightness(1.1); }
.show-btn:active:not(:disabled) { transform: scale(0.99); }

/* Playback */
.playback-controls {
  padding: 16px 20px;
  background: var(--card);
  border-top: 1px solid var(--border);
  flex-shrink: 0;
}
.playback-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}
.playback-header h3 { font-size: 13px; margin: 0; font-weight: 700; }
.p-status { font-size: 11px; color: var(--accent); font-weight: 700; text-transform: uppercase; }
.p-slider { margin-bottom: 20px; }
.p-slider input { width: 100%; cursor: pointer; accent-color: var(--accent); }
.p-time { font-size: 12px; color: var(--muted); margin-top: 8px; text-align: center; font-variant-numeric: tabular-nums; }

.p-actions { display: flex; gap: 10px; align-items: center; justify-content: center; }
.p-btn {
  background: var(--card);
  border: 1px solid var(--border);
  color: #fff;
  width: 44px; height: 44px;
  border-radius: 50%;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.p-btn:hover { background: rgba(79, 124, 255, 0.2); border-color: var(--accent); color: var(--accent); }
/* Speed Wrapper (compact) */
.speed-wrapper { width: 72px; }
.speed-trigger {
  padding: 8px 10px !important;
  min-height: 38px !important;
  justify-content: space-between;
  gap: 4px;
}
.speed-dropdown {
  width: 90px;
  right: 0;
  left: auto;
  bottom: calc(100% + 4px);
  top: auto;
}
.speed-option {
  justify-content: center;
  font-weight: 700;
  font-size: 13px;
  padding: 9px 12px !important;
}

/* Period icon */
.period-icon { font-size: 14px; flex-shrink: 0; }

/* trigger-text */
.trigger-text {
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
}

</style>
