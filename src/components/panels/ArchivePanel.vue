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
            <!-- From Field -->
            <div class="date-field">
              <label>From</label>
              <div class="split-picker">
                <input type="date" :value="splitFrom.date" @input="updateDate('from', $event.target.value)" class="date-input" />
                <select :value="splitFrom.hour" @change="updateTime('from', 'hour', $event.target.value)" class="time-select">
                  <option v-for="h in 24" :key="h-1" :value="String(h-1).padStart(2,'0')">{{ String(h-1).padStart(2,'0') }}</option>
                </select>
                <span class="time-sep">:</span>
                <select :value="splitFrom.minute" @change="updateTime('from', 'minute', $event.target.value)" class="time-select">
                  <option v-for="m in 60" :key="m-1" :value="String(m-1).padStart(2,'0')">{{ String(m-1).padStart(2,'0') }}</option>
                </select>
              </div>
            </div>

            <!-- To Field -->
            <div class="date-field">
              <label>To</label>
              <div class="split-picker">
                <input type="date" :value="splitTo.date" @input="updateDate('to', $event.target.value)" class="date-input" />
                <select :value="splitTo.hour" @change="updateTime('to', 'hour', $event.target.value)" class="time-select">
                  <option v-for="h in 24" :key="h-1" :value="String(h-1).padStart(2,'0')">{{ String(h-1).padStart(2,'0') }}</option>
                </select>
                <span class="time-sep">:</span>
                <select :value="splitTo.minute" @change="updateTime('to', 'minute', $event.target.value)" class="time-select">
                  <option v-for="m in 60" :key="m-1" :value="String(m-1).padStart(2,'0')">{{ String(m-1).padStart(2,'0') }}</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </transition>

      <!-- Stops Filter -->
      <div class="control-group">
        <label>Stops</label>
        <div class="custom-select-wrapper" ref="stopsWrapper">
          <div class="custom-select-trigger" :class="{ open: stopsDropdownOpen }" @click="toggleStopsDropdown">
            <span class="trigger-text">{{ stopsOptions.find(o => o.value === selectedStopFilter)?.label || '> 1 min' }}</span>
            <svg class="trigger-arrow" :class="{ rotated: stopsDropdownOpen }" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
          <div v-show="stopsDropdownOpen" class="custom-select-dropdown">
            <div class="dropdown-list">
              <div
                v-for="opt in stopsOptions" :key="opt.value"
                class="dropdown-option" :class="{ selected: selectedStopFilter === opt.value }"
                @click="selectStopFilter(opt.value)"
              >
                <span class="option-name">{{ opt.label }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <button class="show-btn" @click="$emit('fetch-history')" :disabled="historyLoading || !historyImei">
        {{ historyLoading ? 'Loading...' : 'Show History' }}
      </button>
    </div>

    <!-- Trip Summary Bar -->
    <div class="archive-summary" v-if="historyData.length > 0 && isPanelOpen">
      <div class="summary-item">
        <span class="label">Distance</span>
        <span class="value">{{ totalDistance }} km</span>
      </div>
      <div class="summary-item">
        <span class="label">Duration</span>
        <span class="value">{{ totalDuration }}</span>
      </div>
      <div class="summary-item">
        <span class="label">Stops</span>
        <span class="value">{{ totalStops }}</span>
      </div>
    </div>

    <!-- Events List (Professional Vertical Timeline) -->
    <div class="events-timeline scrollbar-custom" v-if="historyData.length > 0 && isPanelOpen">
      <div 
        v-for="(ev, idx) in routeEvents" 
        :key="idx" 
        class="timeline-item" 
        :class="[ev.type, { active: historyIndex >= ev.index && (idx === routeEvents.length - 1 || historyIndex < routeEvents[idx+1].index) }]"
        @click="$emit('update:historyIndex', ev.index); $emit('preview-update', ev.index)"
      >
        <!-- Connector Line -->
        <div class="timeline-connector" v-if="idx < routeEvents.length - 1"></div>
        
        <!-- Status Icon / Point -->
        <div class="timeline-point">
          <div class="point-inner">
             <svg v-if="ev.type === 'start'" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
             <svg v-else-if="ev.type === 'end'" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="4" width="16" height="16" rx="2"></rect></svg>
             <svg v-else-if="ev.type === 'stop'" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15v-4H9V7h4.5c1.93 0 3.5 1.57 3.5 3.5S15.43 14 13.5 14H13v3h-2zm2-6h-2V9h2c.83 0 1.5.67 1.5 1.5S13.83 11 13 11z"/></svg>
             <svg v-else-if="ev.type === 'drive'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M4 12c4-8 12-8 16 0"></path></svg>
          </div>
        </div>

        <div class="timeline-content">
          <div class="timeline-header">
            <span class="type-label">{{ ev.type === 'drive' ? 'Moving' : ev.type.toUpperCase() }}</span>
            <span class="timestamp">{{ ev.time }}</span>
          </div>
          
          <div class="timeline-body" v-if="ev.type === 'stop' || ev.type === 'drive'">
            <div class="stats-row">
              <span class="duration">{{ ev.duration }}</span>
              <template v-if="ev.type === 'drive'">
                <span class="divider">•</span>
                <span class="distance">{{ ev.distance }}</span>
                <span class="divider">•</span>
                <span class="avg-speed" title="Average Speed">Avg: {{ ev.avgSpeed }}</span>
              </template>
            </div>
          </div>
        </div>
      </div>
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

const stopsOptions = [
  { value: 1, label: '> 1 min' },
  { value: 2, label: '> 2 min' },
  { value: 5, label: '> 5 min' },
  { value: 10, label: '> 10 min' },
  { value: 60, label: '> 1 hr' }
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

// ── Starts / Drives Filter ───────────────────────────────────
const selectedStopFilter = ref(1); // Default 1 minute
const stopsDropdownOpen = ref(false);
const stopsWrapper = ref(null);

function toggleStopsDropdown() {
  closeAll('stops');
  stopsDropdownOpen.value = !stopsDropdownOpen.value;
}

function selectStopFilter(val) {
  selectedStopFilter.value = val;
  stopsDropdownOpen.value = false;
}

// ── Close all except one ─────────────────────────────────
function closeAll(except) {
  if (except !== 'object') dropdownOpen.value = false;
  if (except !== 'period') periodDropdownOpen.value = false;
  if (except !== 'speed')  speedDropdownOpen.value = false;
  if (except !== 'stops')  stopsDropdownOpen.value = false;
}

function handleOutsideClick(e) {
  const inObject = selectWrapper.value?.contains(e.target);
  const inPeriod = periodWrapper.value?.contains(e.target);
  const inSpeed  = speedWrapper.value?.contains(e.target);
  const inStops  = stopsWrapper.value?.contains(e.target);
  if (!inObject) dropdownOpen.value = false;
  if (!inPeriod) periodDropdownOpen.value = false;
  if (!inSpeed)  speedDropdownOpen.value = false;
  if (!inStops)  stopsDropdownOpen.value = false;
}

onMounted(() => document.addEventListener('mousedown', handleOutsideClick));
onUnmounted(() => document.removeEventListener('mousedown', handleOutsideClick));

// ── Split Date Picker Logic ──────────────────────────────
const splitFrom = computed(() => parseDateTime(props.historyFrom));
const splitTo   = computed(() => parseDateTime(props.historyTo));

function parseDateTime(val) {
  if (!val) return { date: '', hour: '00', minute: '00' };
  const [date, time] = val.split('T');
  const [hour, minute] = (time || '00:00').split(':');
  return { date: date || '', hour: hour || '00', minute: minute || '00' };
}

function updateDate(target, date) {
  const current = target === 'from' ? splitFrom.value : splitTo.value;
  const combined = `${date || '0000-00-00'}T${current.hour}:${current.minute}`;
  emit(target === 'from' ? 'update:historyFrom' : 'update:historyTo', combined);
}

function updateTime(target, part, val) {
  const current = target === 'from' ? splitFrom.value : splitTo.value;
  const h = part === 'hour' ? val : current.hour;
  const m = part === 'minute' ? val : current.minute;
  const combined = `${current.date || '0000-00-00'}T${h}:${m}`;
  emit(target === 'from' ? 'update:historyFrom' : 'update:historyTo', combined);
}

// ── Slider ───────────────────────────────────────────────
function onSliderInput(event) {
  const newIndex = parseInt(event.target.value, 10);
  emit('update:historyIndex', newIndex);
  emit('preview-update', newIndex);
}

// ── Stops and Drives Engine ──────────────────────────────
function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
  const R = 6371; // Radius of the earth in km
  const dLat = (lat2 - lat1) * (Math.PI / 180);
  const dLon = (lon2 - lon1) * (Math.PI / 180);
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(lat1 * (Math.PI / 180)) * Math.cos(lat2 * (Math.PI / 180)) *
    Math.sin(dLon / 2) * Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c; 
}

function formatDuration(seconds) {
  if (seconds < 60) return `${seconds} s`;
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  if (m < 60) return `${m} min ${s} s`;
  const h = Math.floor(m / 60);
  const m2 = m % 60;
  return `${h} h ${m2} min`;
}

const routeEvents = computed(() => {
  const data = props.historyData;
  if (!data || data.length === 0) return [];
  
  const minStopSec = selectedStopFilter.value * 60;
  const events = [];
  
  let isStopped = false;
  let currentStopStartIdx = -1;
  let currentDriveStartIdx = 0;
  let currentDriveDistance = 0;
  let currentDriveMaxSpeed = 0;
  
  // Start block
  events.push({ type: 'start', time: data[0].dt_tracker, index: 0, info: '' });

  for (let i = 0; i < data.length; i++) {
    const pt = data[i];
    
    // Check moving state
    const speed = pt.speed || 0;
    // We treat speed > 3 km/h as moving, or ignition On and speed > 0.
    const isMovingPoint = speed > 3 || (pt.ignition === 'On' && speed > 0);
    const isStopPoint = !isMovingPoint;

    // Build distance sum
    if (i > 0) {
      currentDriveDistance += getDistanceFromLatLonInKm(data[i-1].lat, data[i-1].lng, pt.lat, pt.lng);
      if (speed > currentDriveMaxSpeed) currentDriveMaxSpeed = speed;
    }

    if (!isStopped && isStopPoint && i < data.length - 1) {
      isStopped = true;
      currentStopStartIdx = i;
    } else if (isStopped && !isStopPoint) {
      const stopStartPt = data[currentStopStartIdx];
      const stopEndPt = data[i];
      const startMs = new Date(stopStartPt.dt_tracker.replace(' ','T')).getTime();
      const endMs = new Date(stopEndPt.dt_tracker.replace(' ','T')).getTime();
      const durationSec = Math.floor((endMs - startMs) / 1000);
      
      if (durationSec >= minStopSec) {
        // Valid STOP, generate previous Drive block first
        if (currentStopStartIdx > currentDriveStartIdx) {
          const driveStartPt = data[currentDriveStartIdx];
          const dStartMs = new Date(driveStartPt.dt_tracker.replace(' ','T')).getTime();
          const dDurationSec = Math.floor((startMs - dStartMs) / 1000);
          
          events.push({
            type: 'drive',
            time: driveStartPt.dt_tracker,
            index: currentDriveStartIdx,
            duration: formatDuration(dDurationSec),
            distance: currentDriveDistance.toFixed(2) + ' km',
            maxSpeed: Math.round(currentDriveMaxSpeed) + ' km/h',
            avgSpeed: dDurationSec > 0 ? Math.round((currentDriveDistance / (dDurationSec / 3600))) + ' km/h' : '0 km/h'
          });
        }
        
        // Push Stop block
        events.push({
          type: 'stop',
          time: stopStartPt.dt_tracker,
          index: currentStopStartIdx,
          duration: formatDuration(durationSec)
        });
        
        // Reset drive context
        currentDriveStartIdx = i;
        currentDriveDistance = 0;
        currentDriveMaxSpeed = 0;
      }
      isStopped = false;
    }
  }

  // Trailing drive logic
  if (currentDriveStartIdx < data.length - 1) {
    const dStartPt = data[currentDriveStartIdx];
    const dEndPt = data[data.length - 1];
    const dStartMs = new Date(dStartPt.dt_tracker.replace(' ','T')).getTime();
    const dEndMs = new Date(dEndPt.dt_tracker.replace(' ','T')).getTime();
    const dDurationSec = Math.floor((dEndMs - dStartMs) / 1000);
    
    if (dDurationSec > 0) {
      events.push({
        type: 'drive',
        time: dStartPt.dt_tracker,
        index: currentDriveStartIdx,
        duration: formatDuration(dDurationSec),
        distance: currentDriveDistance.toFixed(2) + ' km',
        maxSpeed: Math.round(currentDriveMaxSpeed) + ' km/h',
        avgSpeed: dDurationSec > 0 ? Math.round((currentDriveDistance / (dDurationSec / 3600))) + ' km/h' : '0 km/h'
      });
    }
  }

  events.push({ type: 'end', time: data[data.length - 1].dt_tracker, index: data.length - 1, info: '' });
  return events;
});

const totalDistance = computed(() => {
  let dist = 0;
  for (let i = 1; i < props.historyData.length; i++) {
    dist += getDistanceFromLatLonInKm(props.historyData[i - 1].lat, props.historyData[i - 1].lng, props.historyData[i].lat, props.historyData[i].lng);
  }
  return dist.toFixed(2);
});

const totalStops = computed(() => {
  return routeEvents.value.filter(e => e.type === 'stop').length;
});

const totalDuration = computed(() => {
  if (props.historyData.length < 2) return '0 s';
  const startMs = new Date(props.historyData[0].dt_tracker.replace(' ','T')).getTime();
  const endMs = new Date(props.historyData[props.historyData.length - 1].dt_tracker.replace(' ','T')).getTime();
  const sec = Math.floor((endMs - startMs) / 1000);
  return formatDuration(sec);
});

function getEventTooltip(ev) {
  if (ev.type === 'drive') {
    return `Route length: ${ev.distance}\nMove duration: ${ev.duration}\nTop speed: ${ev.maxSpeed}\nAverage speed: ${ev.avgSpeed}`;
  }
  if (ev.type === 'stop') {
    return `Arrived: ${ev.time}\nStop Duration: ${ev.duration}`;
  }
  return '';
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

.split-picker {
  display: flex;
  align-items: center;
  gap: 6px;
}
.time-select {
  background: rgba(0, 0, 0, 0.05); /* Lighter bg to work in both themes */
  border: 1px solid var(--border);
  color: var(--text); /* Use theme color */
  height: 38px; /* Slightly shorter */
  padding: 0 4px; /* More compact */
  border-radius: 8px;
  font-size: 11px; /* Smaller font as requested */
  font-weight: 700;
  outline: none;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 48px;
  text-align: center;
}
.time-select:hover {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.2);
}
.time-select:focus {
  border-color: var(--accent);
  background: rgba(79, 124, 255, 0.1);
  box-shadow: 0 0 0 3px rgba(79, 124, 255, 0.1);
}
.time-select option {
  background: var(--card);
  color: var(--text);
  padding: 10px;
}
.time-sep {
  color: var(--muted);
  font-weight: 700;
  font-size: 14px;
}
.split-picker .date-input {
  flex: 1;
  height: 38px; /* Sync with selects */
  padding: 8px 10px;
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
  color: var(--text); /* Use theme color instead of hardcoded #fff */
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

/* Trip Summary Bar */
.archive-summary {
  display: flex;
  background: var(--bg2);
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
  padding: 12px 16px;
  gap: 16px;
  flex-shrink: 0;
}
.summary-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.summary-item .label {
  font-size: 9px;
  text-transform: uppercase;
  color: var(--muted);
  font-weight: 700;
  letter-spacing: 0.5px;
}
.summary-item .value {
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
}

/* Events Timeline (Vertical Design) */
.events-timeline {
  display: flex;
  flex-direction: column;
  flex: 1;
  background: var(--card);
  padding: 10px 0;
}

.timeline-item {
  position: relative;
  display: flex;
  padding: 12px 20px;
  cursor: pointer;
  transition: all 0.2s;
  min-height: 56px;
}
.timeline-item:hover {
  background: rgba(255, 255, 255, 0.02);
}
.timeline-item.active {
  background: rgba(79, 124, 255, 0.08);
  box-shadow: inset 3px 0 0 var(--accent);
}

.timeline-connector {
  position: absolute;
  left: 26px;
  top: 32px;
  bottom: -4px;
  width: 2px;
  background: var(--border);
  z-index: 1;
}

.timeline-point {
  position: relative;
  z-index: 2;
  flex-shrink: 0;
  width: 14px;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding-top: 4px;
}
.point-inner {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg1);
  border: 2px solid var(--border);
  color: var(--muted);
  transition: all 0.2s;
}

.timeline-content {
  margin-left: 14px;
  flex: 1;
  min-width: 0;
}
.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}
.type-label {
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  color: var(--muted);
}
.timestamp {
  font-size: 11px;
  color: var(--muted);
  font-variant-numeric: tabular-nums;
}

.stats-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
}
.divider { opacity: 0.3; }
.avg-speed { font-size: 11px; color: var(--muted); }

/* Item Specific Colors */
.timeline-item.start .point-inner { border-color: #84cc16; color: #84cc16; }
.timeline-item.end .point-inner   { border-color: #10b981; color: #10b981; }
.timeline-item.stop .point-inner  { border-color: #3b82f6; color: #3b82f6; }
.timeline-item.drive .point-inner { border-color: #ef4444; color: #ef4444; }

.timeline-item.start .type-label { color: #84cc16; }
.timeline-item.end .type-label   { color: #10b981; }
.timeline-item.stop .type-label  { color: #3b82f6; }
.timeline-item.drive .type-label { color: #ef4444; }

.timeline-item.active .point-inner {
  background: currentColor;
  color: var(--bg1);
  transform: scale(1.15);
  box-shadow: 0 0 10px currentColor;
}

/* Adjustments for Drive type connector line */
.timeline-item.drive .timeline-connector {
  border-left: 2px dashed rgba(239, 68, 68, 0.4);
  background: transparent;
}

</style>
