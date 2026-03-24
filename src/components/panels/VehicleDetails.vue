<template>
  <transition name="slide-up">
    <div 
      v-if="selectedVehicle" 
      class="details-panel" 
      :style="{ height: panelHeight + 'px' }"
    >
      <!-- Resizer Handle -->
      <div class="panel-resizer" @mousedown="initPanelResize" @touchstart="initPanelResize">
        <div class="resizer-knob"></div>
      </div>

      <div class="panel-header-tabs">
        <div class="tabs-list">
          <button class="tab-btn" :class="{ active: activeTab === 'Data' }" @click="activeTab = 'Data'">Data</button>
          <button class="tab-btn" :class="{ active: activeTab === 'Graph' }" @click="activeTab = 'Graph'">Graph</button>
          <button class="tab-btn" :class="{ active: activeTab === 'Messages' }" @click="activeTab = 'Messages'">Messages</button>
          <button class="tab-btn" :class="{ active: activeTab === 'Settings' }" @click="activeTab = 'Settings'">Settings</button>
        </div>
        <button class="close-panel" @click="$emit('close')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>

      <div class="panel-content">
        <div v-if="activeTab === 'Data'" class="data-tab-wrapper">
          <!-- New Comprehensive Data Grid -->
          <div class="data-grid-comprehensive">
            <!-- Column 1: Basic & Personal Info -->
            <div class="data-column">
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                  <span>Driver RFID</span>
                </div>
                <span class="value">{{ selectedVehicle.driverRfid || 'undefined' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                  <span>Engine hours</span>
                </div>
                <span class="value">{{ selectedVehicle.engineHours || '0 h' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                  <span>Model</span>
                </div>
                <span class="value">{{ selectedVehicle.model || selectedVehicle.name }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19h16"></path><path d="M4 15h16"></path><path d="M4 11h16"></path><path d="M4 7h16"></path></svg>
                  <span>Odometer</span>
                </div>
                <span class="value">{{ selectedVehicle.odometer || '0 km' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                  <span>Plate</span>
                </div>
                <span class="value">{{ selectedVehicle.plate || '---' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                  <span>SIM card nu...</span>
                </div>
                <span class="value">{{ selectedVehicle.simCard || '---' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
                  <span>Status</span>
                </div>
                <span class="value">{{ selectedVehicle.statusTime || selectedVehicle.status }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                  <span>Address</span>
                </div>
                <span class="value address-text">{{ selectedVehicle.address || '---' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l10 10-10 10L2 12z"></path></svg>
                  <span>Altitude</span>
                </div>
                <span class="value">{{ selectedVehicle.altitude || '0 m' }}</span>
              </div>
            </div>

            <!-- Column 2: Location & Time -->
            <div class="data-column">
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>
                  <span>Angle</span>
                </div>
                <span class="value">{{ selectedVehicle.angle || '0°' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                  <span>Nearest zone</span>
                </div>
                <span class="value">{{ selectedVehicle.nearestZone || '---' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                  <span>Position</span>
                </div>
                <span class="value position-link">{{ selectedVehicle.position || '---' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 12a11 11 0 1 1-22 0 11 11 0 0 1 22 0z"></path><path d="M12 12L16 10"></path></svg>
                  <span>road limit</span>
                </div>
                <span class="value">{{ selectedVehicle.roadLimit || '100 km/h' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                  <span>Speed</span>
                </div>
                <span class="value">{{ selectedVehicle.speed || 0 }} km/h</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                  <span>Time (position)</span>
                </div>
                <span class="value">{{ selectedVehicle.timePosition || selectedVehicle.lastUpdate }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                  <span>Time (server)</span>
                </div>
                <span class="value">{{ selectedVehicle.timeServer || '---' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="16" height="10" rx="2" ry="2"></rect><line x1="22" y1="11" x2="22" y2="13"></line><line x1="6" y1="11" x2="6" y2="13"></line></svg>
                  <span>Battery</span>
                </div>
                <span class="value">{{ selectedVehicle.battery || '0' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
                  <span>Ignition</span>
                </div>
                <span class="value">{{ selectedVehicle.ignition || 'Off' }}</span>
              </div>
            </div>

            <!-- Column 3: Maintenance & Status -->
            <div class="data-column">
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                  <span>ignition time</span>
                </div>
                <span class="value">{{ selectedVehicle.ignitionTime || '---' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
                  <span>Main Power</span>
                </div>
                <span class="value">{{ selectedVehicle.mainPower || '---' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                  <span>SUV Mainten...</span>
                </div>
                <span class="value">{{ selectedVehicle.suvMaintenance || '---' }}</span>
              </div>
              <div class="info-row">
                <div class="label-with-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                  <span>test</span>
                </div>
                <span class="value">{{ selectedVehicle.testField || '---' }}</span>
              </div>
            </div>
          </div>

          <!-- Unified Data Cards Grid -->
          <div class="data-grid-legacy">
            <!-- Daily Statistics Card -->
            <div class="data-card">
              <div class="card-title">Daily statistics</div>
              <div class="card-body">
                <div class="info-row"><span class="label">Route length</span><span class="value">73.71 km</span></div>
                <div class="info-row"><span class="label">Move duration</span><span class="value">1 h 10 min 20 s</span></div>
                <div class="info-row"><span class="label">Stop duration</span><span class="value">5 h 14 min 30 s</span></div>
                <div class="info-row"><span class="label">Top speed</span><span class="value">116 km/h</span></div>
              </div>
            </div>

            <!-- Driving Behavior Card -->
            <div class="data-card">
              <div class="card-title">Driving behavior</div>
              <div class="card-body">
                <div class="behavior-stats-legacy">
                  <div class="stat-box-legacy blue"><span class="stat-val">62 km/h</span><span class="stat-lbl">Avg Speed</span></div>
                  <div class="stat-box-legacy red"><span class="stat-val">116 km/h</span><span class="stat-lbl">MAX Speed</span></div>
                </div>
                <div class="info-row"><span class="label">Harsh Events</span><span class="value">No</span></div>
              </div>
            </div>

            <!-- GPS Device Information Card -->
            <div class="data-card">
              <div class="card-title">GPS Device information</div>
              <div class="card-body">
                <div class="info-row"><span class="label">Model</span><span class="value">teltonika</span></div>
                <div class="info-row"><span class="label">IMEI</span><span class="value">{{ selectedVehicle.imei }}</span></div>
                <div class="info-row"><span class="label">Status</span><span class="value">Online</span></div>
              </div>
            </div>

            <!-- Vehicle Information Card -->
            <div class="data-card">
              <div class="card-title">Vehicle information</div>
              <div class="card-body">
                <div class="info-row"><span class="label">Type</span><span class="value">Car</span></div>
                <div class="info-row"><span class="label">Plate</span><span class="value">{{ selectedVehicle.plate || '---' }}</span></div>
                <div class="info-row"><span class="label">Status</span><span class="value status-pill" :class="selectedVehicle.status">{{ selectedVehicle.status }}</span></div>
              </div>
            </div>

            <!-- Extended Info Card -->
            <div class="data-card">
              <div class="card-title">Extended information</div>
              <div class="card-body">
                <div class="info-row"><span class="label">VIN</span><span class="value">{{ selectedVehicle.vin || '---' }}</span></div>
                <div class="info-row"><span class="label">Brand</span><span class="value">{{ selectedVehicle.brand || '---' }}</span></div>
                <div class="info-row"><span class="label">Year</span><span class="value">{{ selectedVehicle.year || '---' }}</span></div>
                <div class="info-row"><span class="label">Color</span><span class="value">{{ selectedVehicle.color || '---' }}</span></div>
              </div>
            </div>

            <!-- Recent Events Card -->
            <div class="data-card">
              <div class="card-title">Recent events</div>
              <div class="card-body center-content">
                <span class="muted-text">No recent events</span>
              </div>
            </div>

            <!-- Driver Card -->
            <div class="data-card">
              <div class="card-title">Driver</div>
              <div class="card-body center-content">
                <span class="muted-text">No driver assigned</span>
              </div>
            </div>

            <!-- Recent Tasks Card -->
            <div class="data-card">
              <div class="card-title">
                Recent tasks
                <button class="edit-icon-btn"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>
              </div>
              <div class="card-body center-content">
                <span class="no-data-msg-small">No tasks.</span>
              </div>
            </div>

            <!-- Object Control Card -->
            <div class="data-card">
              <div class="card-title">
                Object control
                <button class="edit-icon-btn"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>
              </div>
              <div class="card-body center-content">
                <span class="no-data-msg-small">No tasks.</span>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="empty-tab">
          <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-opacity="0.2">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <p>No data available for {{ activeTab }} yet.</p>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  selectedVehicle: Object,
  map: Object // We might need this to trigger invalidateSize
});

const emit = defineEmits(['close', 'resize']);

const activeTab = ref('Data');
const panelHeight = ref(320);
const isResizing = ref(false);

const initPanelResize = (e) => {
  isResizing.value = true;
  document.addEventListener('mousemove', handlePanelResize);
  document.addEventListener('mouseup', endPanelResize);
  document.addEventListener('touchmove', handlePanelResize);
  document.addEventListener('touchend', endPanelResize);
  e.preventDefault();
};

const handlePanelResize = (e) => {
  if (!isResizing.value) return;
  const clientY = e.type === 'touchmove' ? e.touches[0].clientY : e.clientY;
  const newHeight = window.innerHeight - clientY;
  // Constraints: Min 100px, Max 70% of window height
  if (newHeight > 100 && newHeight < window.innerHeight * 0.70) {
    panelHeight.value = newHeight;
  }
};

const endPanelResize = () => {
  isResizing.value = false;
  document.removeEventListener('mousemove', handlePanelResize);
  document.removeEventListener('mouseup', endPanelResize);
  document.removeEventListener('touchmove', handlePanelResize);
  document.removeEventListener('touchend', endPanelResize);
  
  emit('resize');
};

watch(panelHeight, () => {
  emit('resize');
});
</script>

<style scoped>
.details-panel {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--bg1);
  border-top: 1px solid var(--border);
  z-index: 1001;
  display: flex;
  flex-direction: column;
  box-shadow: 0 -10px 30px rgba(0,0,0,0.5);
}

.panel-resizer {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 6px;
  cursor: ns-resize;
  z-index: 1002;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: all 0.2s;
}

.panel-resizer:hover {
  background: rgba(79, 124, 255, 0.2);
}

.resizer-knob {
  width: 40px;
  height: 4px;
  background: var(--border);
  border-radius: 2px;
  opacity: 0.5;
}

.panel-resizer:hover .resizer-knob {
  background: var(--accent);
  opacity: 1;
}

.panel-header-tabs {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--card);
  padding: 0 20px;
  height: 50px;
  border-bottom: 1px solid var(--border);
}

.tabs-list {
  display: flex;
  gap: 2px;
  height: 100%;
}

.tab-btn {
  background: transparent;
  border: none;
  color: var(--muted);
  padding: 0 20px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  position: relative;
  transition: all 0.2s;
}

.tab-btn:hover {
  color: var(--text);
}

.tab-btn.active {
  color: var(--accent);
}

.tab-btn.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--accent);
}

.close-panel {
  background: transparent;
  border: none;
  color: var(--muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  padding: 5px;
  transition: color 0.2s;
}

.close-panel:hover {
  color: var(--text);
}

.panel-content {
  flex: 1;
  overflow-y: auto;
  padding: 15px 20px;
}
.data-tab-wrapper {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.data-grid-comprehensive {
  display: grid;
  grid-template-columns: 1.2fr 1.2fr 1fr;
  gap: 30px;
  padding: 10px 5px;
}

.data-column {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
  padding-bottom: 8px;
  border-bottom: 1px solid rgba(255,255,255,0.03);
}

.label-with-icon {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  font-weight: 500;
}
.label-with-icon svg {
  opacity: 0.6;
}

.info-row .value {
  color: var(--text);
  font-weight: 600;
}
.value.address-text {
  max-width: 150px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.value.position-link {
  color: var(--accent);
  cursor: pointer;
}
.value.position-link:hover {
  text-decoration: underline;
}

.data-grid-legacy {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-top: 10px;
  padding-bottom: 20px;
}

.data-card {
  background: var(--card);
  border-radius: 4px;
  border-left: 4px solid var(--accent);
  padding: 15px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.card-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--accent);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.edit-icon-btn {
  background: transparent;
  border: none;
  color: var(--muted);
  cursor: pointer;
  float: right;
  opacity: 0.5;
  transition: opacity 0.2s;
}

.edit-icon-btn:hover {
  opacity: 1;
}

.card-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.behavior-stats-legacy {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}

.stat-box-legacy {
  flex: 1;
  background: rgba(0,0,0,0.2);
  border-radius: 4px;
  padding: 12px 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  border-bottom: 2px solid;
}
.stat-box-legacy.blue { border-color: #3b82f6; }
.stat-box-legacy.red { border-color: #ef4444; }

.stat-val {
  font-size: 16px;
  font-weight: 700;
  color: #fff;
}
.stat-lbl {
  font-size: 10px;
  color: var(--muted);
  text-transform: uppercase;
  margin-top: 4px;
}

.status-pill {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}
.status-pill.Moving { background: rgba(54, 255, 180, 0.1); color: #36ffb4; border: 1px solid rgba(54,255,180,0.2); }
.status-pill.Idle { background: rgba(255, 204, 0, 0.1); color: #ffcc00; border: 1px solid rgba(255,204,0,0.2); }
.status-pill.Offline { background: rgba(255, 90, 120, 0.1); color: #ff5a78; border: 1px solid rgba(255,90,120,0.2); }

.center-content {
  align-items: center;
  justify-content: center;
  min-height: 80px;
}

.muted-text {
  color: var(--muted);
  font-size: 13px;
  font-style: italic;
}
.no-data-msg-small {
  color: rgba(255,255,255,0.3);
  font-size: 12px;
}

.empty-tab {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-state {
  text-align: center;
  color: var(--muted);
}

.empty-state svg {
  margin-bottom: 15px;
}

.empty-state p {
  font-size: 14px;
  margin: 0;
}

/* Animations */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}

@media (max-width: 768px) {
  .panel-header-tabs {
    padding: 0 10px;
  }
  .tabs-list {
    overflow-x: auto;
    overflow-y: hidden;
    flex-wrap: nowrap;
  }
  .tabs-list::-webkit-scrollbar {
    display: none;
  }
  .tab-btn {
    padding: 0 12px;
    font-size: 13px;
    white-space: nowrap;
  }
}
</style>
