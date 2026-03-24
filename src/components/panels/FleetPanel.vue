<template>
  <div class="tab-content-wrapper">
    <div class="panel-header">
      <div class="header-top">
        <h2 v-if="isPanelOpen">Objects List</h2>
        <button class="toggle-btn" @click="$emit('toggle-panel')" :title="isPanelOpen ? 'Collapse' : 'Expand'">
          <svg v-if="isPanelOpen" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
          <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </button>
      </div>
      
      <div v-show="isPanelOpen" class="search-box">
        <input type="text" :value="searchQuery" @input="$emit('update:searchQuery', $event.target.value)" placeholder="Search vehicle..." />
      </div>
      <div v-show="isPanelOpen" class="status-tabs">
        <button class="status-tab" :class="{active: filter==='All'}" @click="$emit('update:filter', 'All')">All</button>
        <button class="status-tab moving" :class="{active: filter==='Moving'}" @click="$emit('update:filter', 'Moving')">Moving</button>
        <button class="status-tab idle" :class="{active: filter==='Idle'}" @click="$emit('update:filter', 'Idle')">Idle</button>
        <button class="status-tab offline" :class="{active: filter==='Offline'}" @click="$emit('update:filter', 'Offline')">Offline</button>
      </div>
    </div>

    <div class="objects-list" v-show="isPanelOpen">
      <div v-for="(group, groupName) in groupedObjects" :key="groupName" class="object-group">
        <div class="group-header" @click="toggleGroup(groupName)">
          <div class="group-title">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :class="{'rotated': collapsedGroups[groupName]}">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
            <span class="group-name">{{ groupName }}</span>
          </div>
          <span class="group-count">({{ group.length }})</span>
        </div>
        
        <div v-show="!collapsedGroups[groupName]" class="group-content">
          <div 
            v-for="obj in group" 
            :key="obj.imei" 
            class="vehicle-card" 
            :class="['status-' + obj.status?.toLowerCase(), {'selected': selectedImei === obj.imei}]"
            @click="$emit('select-vehicle', obj)"
          >
            <!-- Vehicle Status Icon (colored car) -->
            <div class="v-icon" :class="obj.status?.toLowerCase()">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13 8 13.67 8 14.5 7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
              </svg>
            </div>

            <!-- Vehicle Info -->
            <div class="v-info">
              <div class="v-name">{{ obj.name }}</div>
              <div class="v-datetime">{{ formatDt(obj.dt_tracker) }}</div>
            </div>

            <!-- Speed + Sensors Column -->
            <div class="v-stats">
              <div class="v-speed">{{ formatSpeed(obj.speed) }} <span class="km">km/h</span></div>
              <div class="v-sensors">
                <!-- Ignition Icon -->
                <span class="sensor-icon" :class="{ 'on': obj.ignition === 'On' }" :title="'Ignition: ' + (obj.ignition || 'Off')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                  </svg>
                </span>
                <!-- Signal Icon -->
                <span class="sensor-icon signal" :class="getSignalClass(obj.dt_tracker)" :title="'Last seen: ' + (obj.dt_tracker || 'Unknown')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/>
                  </svg>
                </span>
                <!-- Quick Action Menu (Three dots) -->
                <div class="menu-wrapper">
                  <button class="quick-action-btn" title="More Actions" @click.stop="toggleMenu(obj, $event)">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                      <circle cx="12" cy="12" r="2.5"></circle>
                      <circle cx="12" cy="5" r="2.5"></circle>
                      <circle cx="12" cy="19" r="2.5"></circle>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
      <div v-if="Object.keys(groupedObjects).length === 0" class="empty-fleet">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" opacity="0.2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
        <p>No vehicles found</p>
      </div>
  </div>

  <Teleport to="body">
    <div v-show="activeMenu" class="vehicle-menu-dropdown global" :style="menuStyle" v-click-outside="closeMenu">
      <!-- Show History with Submenu -->
      <div class="menu-item-wrapper has-submenu">
        <button class="menu-item highlight" @click.stop="handleShowHistoryDefault(activeMenuObj)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3"></path><path d="M3.05 11a9 9 0 1 1 .5 4"></path><polyline points="2 12 2 17 7 17"></polyline></svg>
          <span style="flex-grow: 1; text-align: left;">Show history</span>
          <span class="arrow-right-wrapper" @click.stop.prevent="toggleSubmenuMobile">
            <svg class="arrow-right" :class="{ 'rotated': isMobileSubmenuOpen }" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
          </span>
        </button>
        <div class="submenu-dropdown" :class="{ 'force-show': isMobileSubmenuOpen }">
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'last_hour')">Last hour</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'today')">Today</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'yesterday')">Yesterday</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'two_days_ago')">Two days ago</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'three_days_ago')">Three days ago</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'current_week')">Current week</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'last_week')">Last week</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'thisMonth')">Current month</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'lastMonth')">Last month</button>
        </div>
      </div>

      <div class="menu-divider"></div>
      
      <button class="menu-item" @click.stop="handleFollow(activeMenuObj)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle></svg>
        Follow
      </button>
      
      <button class="menu-item" @click.stop="handleFollowNewWindow(activeMenuObj)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
        Follow (new window)
      </button>
      
      <button class="menu-item disabled" @click.stop="">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>                      
        Street View (new window)
      </button>
      
      <button class="menu-item" @click.stop="handleSharePosition(activeMenuObj)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
        Share position
      </button>

      <button class="menu-item" @click.stop="handleSendCommand(activeMenuObj)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        Send command
      </button>
      
      <button class="menu-item" @click.stop="handleEdit(activeMenuObj)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
        Edit
      </button>
      
      <button class="menu-item" @click.stop="handleDashboard(activeMenuObj)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
        Unit Dashboard
      </button>
    </div>
  </Teleport>
</template>


<script setup>
import { computed, ref, reactive, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';

// Custom directive to handle clicks outside the menu
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = function(event) {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event);
      }
    };
    document.body.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el) {
    document.body.removeEventListener('click', el.clickOutsideEvent);
  }
};

const props = defineProps({
  isPanelOpen: Boolean,
  searchQuery: String,
  filter: String,
  filteredObjects: Array,
  selectedImei: String
});

const emit = defineEmits([
  'toggle-panel',
  'update:searchQuery',
  'update:filter',
  'select-vehicle',
  'open-history',
  'follow-vehicle',
  'follow-new-window',
  'edit-vehicle',
  'share-position',
  'send-command',
  'unit-dashboard'
]);

const router = useRouter();

const collapsedGroups = reactive({});
const activeMenu = ref(false);
const activeMenuObj = ref(null);
const menuStyle = ref({ top: '0px', left: '0px' });
const isMobileSubmenuOpen = ref(false);

function toggleSubmenuMobile() {
  isMobileSubmenuOpen.value = !isMobileSubmenuOpen.value;
}

function toggleMenu(obj, event) {
  if (activeMenuObj.value && activeMenuObj.value.imei === obj.imei && activeMenu.value) {
    closeMenu();
  } else {
    activeMenuObj.value = obj;
    activeMenu.value = true;

    // Calculate position
    const rect = event.currentTarget.getBoundingClientRect();
    const popupHeight = 350; // estimate
    const popupWidth = 170;

    let targetTop = rect.bottom + 5;
    let targetLeft = rect.right - popupWidth; // Align to right

    // Prevent overflow below screen
    if (targetTop + popupHeight > window.innerHeight) {
      targetTop = Math.max(10, window.innerHeight - popupHeight - 10);
    }
    
    // Set style safely
    menuStyle.value = {
      position: 'fixed',
      top: targetTop + 'px',
      left: Math.max(10, targetLeft) + 'px'
    };
  }
}

function closeMenu() {
  activeMenu.value = false;
  isMobileSubmenuOpen.value = false;
  setTimeout(() => {
    if (!activeMenu.value) activeMenuObj.value = null;
  }, 200);
}

function handleShowHistory(obj, period = 'today') {
  emit('open-history', { obj, period });
  closeMenu();
}

function handleShowHistoryDefault(obj) {
  emit('open-history', { obj, period: 'today', noFetch: true });
  closeMenu();
}

function handleFollow(obj) {
  emit('follow-vehicle', obj);
  closeMenu();
}

function handleFollowNewWindow(obj) {
  emit('follow-new-window', obj);
  closeMenu();
}

function handleEdit(obj) {
  emit('edit-vehicle', obj);
  closeMenu();
}

function handleSharePosition(obj) {
  emit('share-position', obj);
  closeMenu();
}

function handleSendCommand(obj) {
  emit('send-command', obj);
  closeMenu();
}

const groupedObjects = computed(() => {
  const groups = {};
  props.filteredObjects.forEach(obj => {
    const groupName = obj.group_name || 'Ungrouped';
    if (!groups[groupName]) {
      groups[groupName] = [];
    }
    groups[groupName].push(obj);
  });
  return groups;
});

function toggleGroup(groupName) {
  collapsedGroups[groupName] = !collapsedGroups[groupName];
}

function formatSpeed(speed) {
  return Math.round(speed || 0);
}

function handleDashboard(obj) {
  if (!obj) return;
  const params = jsonDecodeSafe(obj.params) || {};
  const ignition = (params.acc == 1) ? 'ON' : 'OFF';
  router.push({
    path: `/dashboard/${obj.imei}`
  });
  closeMenu();
}

function jsonDecodeSafe(json) {
  if (!json) return {};
  try {
    return typeof json === 'string' ? JSON.parse(json) : json;
  } catch {
    return {};
  }
}

function formatDt(dt) {
  if (!dt) return '---';
  try {
    const d = new Date(dt);
    const date = d.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
    const time = d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    return `${date} ${time}`;
  } catch {
    return dt;
  }
}

function getSignalClass(dt_tracker) {
  if (!dt_tracker) return 'no-signal';
  const diff = (Date.now() - new Date(dt_tracker).getTime()) / 1000;
  if (diff < 60) return 'signal-strong';       // < 1 min
  if (diff < 300) return 'signal-medium';      // < 5 min
  if (diff < 3600) return 'signal-weak';       // < 1 hour
  return 'no-signal';
}
</script>


<style scoped>
.tab-content-wrapper {
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow: hidden;
}

.panel-header {
  padding: 16px 16px 12px;
  border-bottom: 1px solid var(--border);
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.toggle-btn {
  background: var(--border);
  border: 1px solid var(--border);
  color: var(--text);
  border-radius: 8px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.toggle-btn:hover {
  background: rgba(79, 124, 255, 0.2);
  color: var(--accent);
}

.panel-header h2 {
  margin: 0;
  font-size: 17px;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.search-box input {
  width: 100%;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--input-bg);
  color: var(--text);
  padding: 9px 14px;
  font-size: 13px;
  outline: none;
  transition: border-color 0.3s;
  box-sizing: border-box;
}
.search-box input:focus { border-color: var(--accent); }

/* Status Tabs */
.status-tabs {
  display: flex;
  margin-top: 12px;
  background: var(--input-bg);
  border-radius: 8px;
  border: 1px solid var(--border);
  padding: 3px;
  gap: 2px;
}
.status-tab {
  flex: 1;
  background: transparent;
  border: none;
  color: var(--muted);
  font-size: 11px;
  font-weight: 600;
  padding: 5px 0;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}
.status-tab.active {
  background: var(--card);
  color: var(--text);
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.status-tab.moving.active { color: var(--status-moving); }
.status-tab.idle.active   { color: var(--status-idle); }
.status-tab.offline.active{ color: var(--status-offline); }

/* Objects List */
.objects-list {
  flex: 1;
  overflow-y: auto;
  padding: 8px;
}
.objects-list::-webkit-scrollbar { width: 4px; }
.objects-list::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.08);
  border-radius: 2px;
}

/* Grouping */
.object-group { margin-bottom: 4px; }
.group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 7px 10px;
  background: var(--border);
  border-radius: 7px;
  border: 1px solid var(--border);
  margin-bottom: 3px;
  cursor: pointer;
  transition: background 0.2s;
}
.group-header:hover { background: var(--border); opacity: 0.8; }
.group-title { display: flex; align-items: center; gap: 7px; }
.group-title svg { transition: transform 0.2s; color: var(--muted); }
.group-title svg.rotated { transform: rotate(-90deg); }
.group-name { font-size: 12px; font-weight: 700; color: var(--text); letter-spacing: 0.3px; }
.group-count { font-size: 11px; color: var(--muted); font-weight: 600; }
.group-content { padding-left: 4px; }

/* Vehicle Card — Compact Legacy Style */
.vehicle-card {
  display: flex;
  align-items: center;
  padding: 8px 10px;
  border-radius: 8px;
  margin-bottom: 3px;
  cursor: pointer;
  border-left: 3px solid transparent;
  background: var(--card);
  border: 1px solid var(--border);
  transition: all 0.18s ease;
  gap: 8px;
}
.vehicle-card:hover { background: var(--border); }
.vehicle-card.selected {
  background: rgba(79, 124, 255, 0.1);
  border-left-color: var(--accent);
}

/* Status-based left border + background tint */
.vehicle-card.status-moving {
  border-left-color: var(--status-moving);
  background: var(--status-moving-bg);
}
.vehicle-card.status-idle {
  border-left-color: var(--status-idle);
  background: var(--status-idle-bg);
}
.vehicle-card.status-offline {
  border-left-color: var(--status-offline);
  background: var(--status-offline-bg);
}

/* Car Icon */
.v-icon {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: color 0.3s, background 0.3s;
}
.v-icon.moving  { color: var(--status-moving); background: var(--status-moving-bg); opacity: 0.9; }
.v-icon.idle    { color: var(--status-idle); background: var(--status-idle-bg); opacity: 0.9; }
.v-icon.offline { color: var(--status-offline); background: var(--status-offline-bg); opacity: 0.9; }

/* Info block */
.v-info {
  flex: 1;
  min-width: 0;
}
.v-name {
  font-size: 12.5px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--text);
}
.v-datetime {
  font-size: 10.5px;
  color: var(--muted);
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Stats column */
.v-stats {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
  flex-shrink: 0;
}
.v-speed {
  font-size: 12px;
  font-weight: 700;
  color: var(--text);
  white-space: nowrap;
}
.v-speed .km {
  font-size: 10px;
  font-weight: 400;
  color: var(--muted);
}
.v-sensors {
  display: flex;
  align-items: center;
  gap: 5px;
}

/* Sensor Icons */
.sensor-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--muted);
  opacity: 0.4;
  transition: color 0.2s;
}
.sensor-icon.on { color: #ffcc00; }  /* ignition on = yellow */

/* Signal strength colors */
.sensor-icon.signal.signal-strong { color: var(--status-moving); }
.sensor-icon.signal.signal-medium { color: var(--accent); }
.sensor-icon.signal.signal-weak   { color: var(--status-idle); }
.sensor-icon.signal.no-signal     { color: var(--muted); opacity: 0.2; }

/* History quick action button & Menu */
.menu-wrapper {
  position: relative;
}

.quick-action-btn {
  background: var(--input-bg);
  border: 1px solid var(--border);
  color: var(--muted);
  width: 24px;
  height: 24px;
  border-radius: 6px; /* slightly more rounded */
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  padding: 0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* little bit of depth */
}
.quick-action-btn:hover {
  background: rgba(79, 124, 255, 0.25);
  border-color: rgba(79, 124, 255, 0.4);
  color: var(--accent);
  transform: translateY(-1px);
}

.vehicle-menu-dropdown.global {
  position: fixed;
  background: var(--card);
  backdrop-filter: blur(12px);
  border: 1px solid var(--border);
  border-radius: 10px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.25);
  z-index: 10000; /* Ensure high priority over sidebar */
  min-width: 170px;
  padding: 6px;
  display: flex;
  flex-direction: column;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: transparent;
  border: none;
  width: 100%;
  color: var(--text, white);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  border-radius: 6px;
  transition: all 0.2s;
  text-align: left;
}

.menu-item svg {
  color: var(--muted, #94a3b8);
  transition: color 0.2s;
}

.menu-item:hover {
  background: var(--border);
}

.menu-item:hover svg {
  color: var(--text, white);
}

.menu-item.highlight {
  color: var(--accent, #4f7cff);
  font-weight: 600;
}
.menu-item.highlight svg:not(.arrow-right) {
  color: var(--accent, #4f7cff);
}

.arrow-right-wrapper {
  margin-left: auto;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 5px;
  margin-right: -5px;
}

.arrow-right-wrapper .arrow-right {
  opacity: 0.6;
  margin-left: 0;
  transition: transform 0.2s;
}

.arrow-right-wrapper .arrow-right.rotated {
  transform: rotate(90deg);
}

.menu-item.disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.menu-divider {
  height: 1px;
  background: var(--border);
  margin: 4px 0;
}

/* Submenu specifics */
.menu-item-wrapper.has-submenu {
  position: relative;
}

.submenu-dropdown {
  position: absolute;
  top: 0;
  left: 100%;
  margin-left: 5px;
  background: var(--card);
  backdrop-filter: blur(12px);
  border: 1px solid var(--border);
  border-radius: 10px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.25);
  z-index: 10000;
  min-width: 150px;
  padding: 6px;
  display: flex;
  flex-direction: column;
  opacity: 0;
  visibility: hidden;
  transform: translateX(-10px);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.menu-item-wrapper.has-submenu:hover .submenu-dropdown,
.submenu-dropdown.force-show {
  opacity: 1;
  visibility: visible;
  transform: translateX(0);
}

.submenu-dropdown .menu-item {
  font-weight: 400;
}
.submenu-dropdown .menu-item:hover {
  font-weight: 600;
  color: var(--accent, #4f7cff);
}

@media (max-width: 768px) {
  .submenu-dropdown.force-show {
    position: static;
    margin-top: 5px;
    margin-left: 0;
    box-shadow: none;
    border: none;
    background: var(--border);
    border-radius: 6px;
    padding-left: 15px;
    max-height: 250px;
    overflow-y: auto;
  }
}

/* Empty state */
.empty-fleet {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  color: var(--muted);
  gap: 12px;
}
.empty-fleet p {
  font-size: 13px;
  margin: 0;
}
</style>
