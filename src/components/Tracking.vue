<template>
  <div class="tracking-layout">
    <!-- Left Navigation Sidebar -->
    <SidebarNav 
      @nav-action="handleNavAction" 
      :active-tab="activeMainTab" 
      @go-profile="goProfile" 
      @logout="logout" 
      :username="auth.user?.username || 'Guest'"
    />

    <!-- Objects Sidebar -->
    <aside class="objects-panel" :class="{ 'collapsed': !isPanelOpen }" :style="mobileObjectsPanelStyle">
      
      <!-- Mobile Draggable Handle -->
      <div
        v-if="isMobileView && isPanelOpen"
        class="mobile-drag-handle"
        @mousedown="initObjectsResize"
        @touchstart.passive="initObjectsResize"
      >
        <div class="drag-bar"></div>
      </div>

      <!-- Modern Panel Tab Bar (Fleet / Events / Places / Archive) -->
      <div class="panel-tabs-modern" v-show="isPanelOpen">
        <button 
          class="panel-tab-pill" 
          :class="{ active: activeMainTab === 'Fleet' }" 
          @click="activeMainTab = 'Fleet'"
          title="Fleet"
        >
          <div class="pill-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" class="nav-icon" :style="{ color: activeMainTab === 'Fleet' ? '#ffffff' : '#64748b' }">
               <path d="M21 3L3 10.5L10.5 13.5L13.5 21L21 3Z" fill="currentColor"/>
            </svg>
          </div>
          <span v-if="activeMainTab === 'Fleet'" class="pill-label">Fleet</span>
        </button>

        <button 
          class="panel-tab-pill" 
          :class="{ active: activeMainTab === 'Events' }" 
          @click="activeMainTab = 'Events'"
          title="Events"
        >
          <div class="pill-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" class="nav-icon" :style="{ color: activeMainTab === 'Events' ? '#ffffff' : '#64748b' }">
              <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2.5"/>
              <path d="M7 9h10M7 13h10M7 17h6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
          </div>
          <span v-if="activeMainTab === 'Events'" class="pill-label">Events</span>
        </button>

        <button 
          class="panel-tab-pill" 
          :class="{ active: activeMainTab === 'Places' }" 
          @click="activeMainTab = 'Places'"
          title="Places"
        >
          <div class="pill-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" class="nav-icon" :style="{ color: activeMainTab === 'Places' ? '#ffffff' : '#64748b' }">
              <path d="M12 21s-7-5.5-7-12a7 7 0 1 1 14 0c0 6.5-7 12-7 12z" fill="currentColor"/>
              <circle cx="12" cy="9" r="2.5" fill="#fff" :opacity="activeMainTab === 'Places' ? 1 : 0.4"/>
            </svg>
          </div>
          <span v-if="activeMainTab === 'Places'" class="pill-label">Places</span>
        </button>

        <button 
          class="panel-tab-pill" 
          :class="{ active: activeMainTab === 'Archive' }" 
          @click="activeMainTab = 'Archive'"
          title="Archive"
        >
          <div class="pill-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="nav-icon" :style="{ color: activeMainTab === 'Archive' ? '#ffffff' : '#94a3b8' }">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="currentColor" opacity="0.4"/>
              <path d="M14 2v6h6" stroke="currentColor" stroke-width="2"/>
              <circle cx="11" cy="14" r="3" stroke="currentColor" stroke-width="2" fill="#fff"/>
              <line x1="13" y1="16" x2="16" y2="19" stroke="currentColor" stroke-width="2"/>
            </svg>
          </div>
          <span v-if="activeMainTab === 'Archive'" class="pill-label">Archive</span>
        </button>
      </div>

      <FleetPanel 
        v-if="activeMainTab === 'Fleet'"
        :is-panel-open="isPanelOpen"
        :fleet-data="fleetData"
        v-model:searchQuery="searchQuery"
        v-model:filter="filter"
        :filtered-objects="filteredObjects"
        :selected-imei="selectedImei"
        @toggle-panel="togglePanel"
        @select-vehicle="handleSelectVehicle"
        @open-history="handleOpenHistory"
        @follow-vehicle="openFollowOptionsModal"
        @edit-vehicle="handleEditVehicle"
        @share-position="handleSharePosition"
        @send-command="handleSendCommand"
      />

      <EventsPanel 
        v-if="activeMainTab === 'Events'"
        :is-panel-open="isPanelOpen"
        :events-list="eventsList"
        v-model:searchQuery="eventsSearchQuery"
        v-model:currentPage="eventsCurrentPage"
        v-model:per-page="eventsPerPage"
        v-model:sort-idx="eventsSortIdx"
        v-model:sort-ord="eventsSortOrd"
        :total-pages="eventsTotalPages"
        :loading="eventsLoading"
        @refresh-events="fetchEventsData"
        @delete-all-events="handleDeleteAllEvents"
        @export-events="handleExportEvents"
      />

      <ArchivePanel
        v-if="activeMainTab === 'Archive'"
        :is-panel-open="isPanelOpen"
        :fleet-data="fleetData"
        v-model:history-imei="historyImei"
        v-model:history-period="historyPeriod"
        v-model:history-from="historyFrom"
        v-model:history-to="historyTo"
        v-model:history-index="historyIndex"
        v-model:history-playback-speed="historyPlaybackSpeed"
        :history-loading="historyLoading"
        :history-data="historyData"
        :history-play-status="historyPlayStatus"
        :current-history-time="currentHistoryTime"
        :is-history-playing="isHistoryPlaying"
        @toggle-panel="togglePanel"
        @fetch-history="handleFetchHistory"
        @toggle-play="togglePlayback"
        @stop-playback="stopPlayback"
        @preview-update="updatePlaybackMarker"
        @period-change="handlePeriodChange"
      />
    </aside>

    <!-- Map Area -->
    <main class="map-area">
      <div id="leafletMap" class="map-container"></div>
      
      <!-- Follow Options Modal -->
      <FollowOptionsModal
        v-if="showFollowOptionsModal"
        :is-visible="showFollowOptionsModal"
        :initial-vehicle="initialFollowVehicle"
        :fleet-data="fleetData"
        @close="showFollowOptionsModal = false"
        @action-inline="triggerFollowInline"
        @action-window="triggerFollowWindow"
      />

      <!-- User Profile Badge -->
      <div class="user-badge" @click="goProfile">
        <span class="user-name">Hello, <strong>{{ auth.user?.username || 'Guest' }}</strong></span>
      </div>

      <!-- Map Overlays (Vue Components) -->
      <MapZones 
        v-if="map" 
        :map="map" 
        :visible="showZones" 
      />
      
      <template v-if="activeMainTab !== 'Archive' && map">
        <template v-for="g in markerGroups" :key="g.id">
          <VehicleMarker 
            v-if="g.type === 'single'"
            :map="map"
            :vehicle="g.vehicle"
            :is-selected="selectedImei === g.vehicle.imei"
            :icon-type="iconType"
            :status-colors="statusColors"
            @select="handleSelectVehicle"
          />
          <GroupMarker
            v-else
            :map="map"
            :vehicles="g.vehicles"
            :lat="g.lat"
            :lng="g.lng"
            :is-selected="g.vehicles.some(v => v.imei === selectedImei)"
            :icon-type="iconType"
            :status-colors="statusColors"
            :is-dark-mode="themeStore.isDarkMode"
            @select="handleSelectVehicle"
          />
        </template>
      </template>

      <!-- Map Controls (Custom Premium Layout) -->
      <div class="custom-map-controls">
        <!-- Main Tool Pill -->
        <div class="control-pill tools-pill">
          <!-- Target / Center on Fleet -->
          <button class="tool-btn" @click="centerMap" title="Center on Fleet">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <rect x="3" y="3" width="18" height="18" rx="5" />
              <circle cx="12" cy="12" r="4" />
            </svg>
          </button>
          
          <div class="tool-divider"></div>

          <!-- Zones Toggle -->
          <button class="tool-btn blue-btn" :class="{ active: showZones }" @click="showZones = !showZones" title="Toggle Zones">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <rect x="4" y="4" width="16" height="16" rx="3" stroke-dasharray="3 2" />
              <circle cx="12" cy="12" r="3" />
              <path d="M12 2v2M12 20v2M2 12h2M20 12h2" />
            </svg>
          </button>

          <div class="tool-divider"></div>

          <!-- Icon Type Toggle -->
          <button class="tool-btn blue-btn" @click="iconType = iconType === 'car' ? 'arrow' : 'car'" :title="iconType === 'car' ? 'Switch to Arrows' : 'Switch to Cars'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round">
              <path d="M3 8 L12 19 L21 8" />
            </svg>
          </button>

          <div class="tool-divider"></div>

          <!-- Print Map -->
          <button class="tool-btn" @click="printMap" title="Print Map">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
              <path d="M6 9V4h12v5"></path>
              <path d="M5 9h14v8h-4v3H9v-3H5V9z"></path>
              <line x1="8" y1="12" x2="16" y2="12"></line>
            </svg>
          </button>
        </div>

        <!-- Zoom Pill -->
        <div class="control-pill zoom-pill">
          <button class="tool-btn" @click="mapZoomIn" title="Zoom In">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <line x1="12" y1="6" x2="12" y2="18"></line>
              <line x1="6" y1="12" x2="18" y2="12"></line>
            </svg>
          </button>
          
          <div class="tool-divider"></div>

          <button class="tool-btn" @click="mapZoomOut" title="Zoom Out">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <line x1="6" y1="12" x2="18" y2="12"></line>
            </svg>
          </button>
        </div>
      </div>
      
      <!-- Follow Popup (Draggable) -->
      <FollowPopup 
        v-if="followPopupVehicle" 
        :vehicle="followPopupVehicle" 
        @close="followPopupVehicle = null" 
      />

      <!-- Details Panel (Footer) -->
      <VehicleDetails 
        v-if="selectedVehicle"
        :selected-vehicle="selectedVehicle"
        :panel-height="panelHeight"
        :history-period="historyPeriod"
        :history-from="historyFrom"
        :history-to="historyTo"
        :history-data="historyData"
        :history-index="historyIndex"
        :is-history-playing="isHistoryPlaying"
        @init-resize="initPanelResize"
        @close="closePanel"
        @reload-history="handleFetchHistory"
      />
    </main>

    <!-- Edit Object Modal -->
    <EditObjectModal 
      v-if="showEditModal"
      :vehicle="editingVehicle"
      @close="showEditModal = false"
      @save="saveVehicleEdit"
    />

    <!-- Share Position Modal -->
    <SharePositionModal
      v-if="showSharePositionModal"
      :vehicle="sharingVehicle"
      @close="showSharePositionModal = false"
      @save="saveSharePosition"
    />

    <!-- Object Control Modal -->
    <ObjectControlModal
      v-if="showObjectControlModal"
      :vehicle="objectControlVehicle"
      @close="showObjectControlModal = false"
    />

    <!-- Reports Modal -->
    <ReportsModal
      v-if="reportsStore.showMainModal"
      @close="reportsStore.showMainModal = false"
    />

    <!-- Custom Toast Notification -->
    <transition name="toast-slide">
      <div v-if="toastVisible" class="custom-toast" :class="`toast-${toastType}`">
        <svg
          v-if="toastType === 'error'"
          width="20"
          height="20"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
        >
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span>{{ toastMessage }}</span>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useThemeStore } from '../stores/theme';
import { useReportStore } from '../stores/reports';
import api from '../services/api';

// Component Imports
import SidebarNav from './panels/SidebarNav.vue';
import FleetPanel from './panels/FleetPanel.vue';
import EventsPanel from './panels/EventsPanel.vue';
import ArchivePanel from './panels/ArchivePanel.vue';
import VehicleDetails from './panels/VehicleDetails.vue';
import FollowPopup from './panels/FollowPopup.vue';
import FollowOptionsModal from './modals/FollowOptionsModal.vue';
import EditObjectModal from './modals/EditObjectModal.vue';
import SharePositionModal from './modals/SharePositionModal.vue';
import ObjectControlModal from './modals/ObjectControlModal.vue';
import ReportsModal from './modals/ReportsModal.vue';
import VehicleMarker from './map/VehicleMarker.vue';
import GroupMarker from './map/GroupMarker.vue';
import MapZones from './map/MapZones.vue';

import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

// Fix leaflet default icons
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

L.Icon.Default.mergeOptions({
  iconRetinaUrl,
  iconUrl,
  shadowUrl,
});

const router = useRouter();
const auth = useAuthStore();
const themeStore = useThemeStore();
const reportsStore = useReportStore();

const statusColors = computed(() => ({
  Moving: themeStore.isDarkMode ? '#36ffb4' : '#10b981',
  Idle: themeStore.isDarkMode ? '#ffcc00' : '#d97706',
  Offline: themeStore.isDarkMode ? '#ff5a78' : '#dc2626'
}));

const map = ref(null);
const markersGroup = ref(null);

const searchQuery = ref('');
const filter = ref('All');
const showZones = ref(false);
const iconType = ref('car'); // 'car' or 'arrow'
const currentZoom = ref(11);

function handleNavAction(action) {
  if (action === 'reports') {
    reportsStore.showMainModal = true;
  } else if (action === 'tracking') {
    if (activeMainTab.value === 'Fleet') {
      isPanelOpen.value = !isPanelOpen.value;
    } else {
      activeMainTab.value = 'Fleet';
      isPanelOpen.value = true;
    }
  } else if (action === 'events') {
    activeMainTab.value = 'Events';
  } else if (action === 'dashboard') {
    router.push('/dashboard/all');
  } else if (action === 'profile') {
    goProfile();
  } else if (action === 'logout') {
    logout();
  }
}
const selectedImei = ref(null);
const selectedVehicle = ref(null);
const activeMainTab = ref('Fleet');
const isPanelOpen = ref(true);

const followedImei = ref(null);
const followPopupVehicle = ref(null);

const showEditModal = ref(false);
const editingVehicle = ref(null);

const showSharePositionModal = ref(false);
const sharingVehicle = ref(null);

const showObjectControlModal = ref(false);
const objectControlVehicle = ref(null);

// History State
const historyImei = ref('');
const historyPeriod = ref('today');
const historyFrom = ref('');
const historyTo = ref('');
const historyLoading = ref(false);
const historyData = ref([]);
const historyPlayStatus = ref('Stopped');
const historyIndex = ref(0);
const isHistoryPlaying = ref(false);
const historyPlaybackSpeed = ref(1000);
const historySettings = ref(null); // Cache for timezone/dst settings

// Events State
const eventsList = ref([]);
const eventsSearchQuery = ref('');
const eventsCurrentPage = ref(1);
const eventsTotalPages = ref(1);
const eventsPerPage = ref(25);
const eventsSortIdx = ref('dt_tracker');
const eventsSortOrd = ref('desc');
const eventsLoading = ref(false);
let eventsSearchDebounce = null;

// Custom Toast Logic
const toastVisible = ref(false);
const toastMessage = ref('');
const toastType = ref('error');

function showToast(msg, type = 'error') {
  toastMessage.value = msg;
  toastType.value = type;
  toastVisible.value = true;

  setTimeout(() => {
    toastVisible.value = false;
  }, 4000);
}

const currentHistoryTime = computed(() => {
  if (historyData.value.length === 0 || historyIndex.value >= historyData.value.length) return '--:--:--';
  return historyData.value[historyIndex.value].dt_tracker;
});

let historyPolyline = null;
let historyMarker = null;
let playbackInterval = null;

// Resizable Panel Logic (Vehicle Details)
const panelHeight = ref(320);
const isResizing = ref(false);

const initPanelResize = (e) => {
  isResizing.value = true;
  document.addEventListener('mousemove', handlePanelResize);
  document.addEventListener('mouseup', endPanelResize);
  document.addEventListener('touchmove', handlePanelResize, { passive: false });
  document.addEventListener('touchend', endPanelResize);
  e.preventDefault();
};

const handlePanelResize = (e) => {
  if (!isResizing.value) return;

  const clientY = e.type === 'touchmove' ? e.touches[0].clientY : e.clientY;
  const newHeight = window.innerHeight - clientY;

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

  if (map.value) map.value.invalidateSize();
};

// Mobile View & Objects Panel Resizing
const isMobileView = ref(false);
const objectsPanelHeightVh = ref(40);
const isObjectsResizing = ref(false);

const mobileObjectsPanelStyle = computed(() => {
  if (isMobileView.value && isPanelOpen.value) {
    return { height: `${objectsPanelHeightVh.value}vh` };
  }
  return {};
});

const initObjectsResize = () => {
  isObjectsResizing.value = true;
  document.addEventListener('mousemove', handleObjectsResize);
  document.addEventListener('mouseup', endObjectsResize);
  document.addEventListener('touchmove', handleObjectsResize, { passive: false });
  document.addEventListener('touchend', endObjectsResize);
};

const handleObjectsResize = (e) => {
  if (!isObjectsResizing.value) return;

  e.preventDefault();
  const clientY = e.type === 'touchmove' ? e.touches[0].clientY : e.clientY;
  const winHeight = window.innerHeight;
  const newHeightVh = ((winHeight - clientY) / winHeight) * 100;

  if (newHeightVh >= 20 && newHeightVh <= 70) {
    objectsPanelHeightVh.value = newHeightVh;
  }
};

const endObjectsResize = () => {
  isObjectsResizing.value = false;
  document.removeEventListener('mousemove', handleObjectsResize);
  document.removeEventListener('mouseup', endObjectsResize);
  document.removeEventListener('touchmove', handleObjectsResize);
  document.removeEventListener('touchend', endObjectsResize);

  if (map.value) map.value.invalidateSize();
};

watch(panelHeight, () => {
  if (map.value) {
    map.value.invalidateSize();
  }
});

watch(activeMainTab, async (newTab) => {
  if (newTab === 'Archive') {
    followedImei.value = null;
    followPopupVehicle.value = null;

    if (map.value && markersGroup.value) {
      map.value.removeLayer(markersGroup.value);
    }
    stopPlayback();
    clearHistoryMap();
  }

  // Handle switching back to Fleet or Events
  if (newTab === 'Fleet') {
    // Markers are handled declaratively by VehicleMarker components
  }

  if (newTab === 'Events') {
    await fetchEventsData();
  }
});

watch(eventsSearchQuery, () => {
  eventsCurrentPage.value = 1;

  if (activeMainTab.value !== 'Events') return;

  if (eventsSearchDebounce) clearTimeout(eventsSearchDebounce);
  eventsSearchDebounce = setTimeout(() => {
    fetchEventsData();
  }, 350);
});

watch(eventsPerPage, () => {
  eventsCurrentPage.value = 1;

  if (activeMainTab.value === 'Events') {
    fetchEventsData();
  }
});

watch(eventsCurrentPage, () => {
  if (activeMainTab.value === 'Events') {
    fetchEventsData();
  }
});

watch([eventsSortIdx, eventsSortOrd], () => {
  if (activeMainTab.value === 'Events') {
    fetchEventsData();
  }
});

function togglePanel() {
  isPanelOpen.value = !isPanelOpen.value;

  setTimeout(() => {
    handleResize();
  }, 350);
}

// Fleet Data State
const fleetData = ref([]);
let fetchInterval = null;

const filteredObjects = computed(() => {
  return fleetData.value.filter(obj => {
    const searchTarget = `${obj.name || ''}${obj.imei || ''}`;
    const matchSearch = searchTarget.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchStatus = filter.value === 'All' || obj.status === filter.value;
    return matchSearch && matchStatus;
  });
});

// Implementation of Marker Clustering/Grouping (Currently Disabled for Stability)
const markerGroups = computed(() => {
  if (activeMainTab.value === 'Archive' || !map.value) return [];
  
  // Return all vehicles as standalone markers for now to ensure console stability
  return filteredObjects.value.map(v => ({
    type: 'single',
    id: v.imei,
    vehicle: v
  }));
});

// Custom Map Tool Methods
function mapZoomIn() {
  if (map.value) map.value.zoomIn();
}

function mapZoomOut() {
  if (map.value) map.value.zoomOut();
}

function centerMap() {
  if (!map.value || fleetData.value.length === 0) return;
  const bounds = L.latLngBounds(fleetData.value.map(v => [v.lat, v.lng]));
  if (bounds.isValid()) {
    map.value.fitBounds(bounds, { padding: [50, 50] });
  }
}

function printMap() {
  window.print();
}

function initMap() {
  map.value = L.map('leafletMap', { zoomControl: false }).setView([29.3759, 47.9774], 11);

  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
  }).addTo(map.value);

  // Register animation locks
  map.value.on('zoomstart', onMapMoveStart);
  map.value.on('zoomend', onMapMoveEnd);
  map.value.on('movestart', onMapMoveStart);
  map.value.on('moveend', onMapMoveEnd);

  // Register zoom tracker
  map.value.on('zoomend', () => {
    currentZoom.value = map.value.getZoom();
  });
}

const markersMap = {};

function createCarIcon(color, heading = 0) {
  const icon = L.divIcon({
    className: 'custom-vehicle-marker',
    html: `
      <div style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
        <svg width="36" height="36" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg" style="transform: rotate(${heading}deg); transition: transform 0.5s ease-in-out; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));">
          <g transform="translate(18, 18) rotate(0) translate(-18, -18)">
            <rect x="9" y="3" width="18" height="30" rx="6" fill="${color}" stroke="#1a1a1a" stroke-width="1.5"/>
            <rect x="11" y="11" width="14" height="13" rx="3" fill="rgba(255,255,255,0.15)"/>
            <rect x="12" y="12" width="12" height="11" rx="2" fill="rgba(0,0,0,0.1)"/>
            <path d="M 11 9 L 25 9 L 23 12 L 13 12 Z" fill="#1a1a1a"/>
            <path d="M 13 23 L 23 23 L 25 26 L 11 26 Z" fill="#1a1a1a"/>
            <rect x="10" y="3" width="4" height="2" rx="1" fill="#fff" opacity="0.9"/>
            <rect x="22" y="3" width="4" height="2" rx="1" fill="#fff" opacity="0.9"/>
            <rect x="10" y="31" width="5" height="2" rx="1" fill="#ff3b30"/>
            <rect x="21" y="31" width="5" height="2" rx="1" fill="#ff3b30"/>
            <rect x="7" y="10" width="2" height="4" rx="1" fill="${color}" stroke="#1a1a1a" stroke-width="1"/>
            <rect x="27" y="10" width="2" height="4" rx="1" fill="${color}" stroke="#1a1a1a" stroke-width="1"/>
          </g>
        </svg>
      </div>
    `,
    iconSize: [36, 36],
    iconAnchor: [18, 18]
  });

  icon.options._carColor = color;
  icon.options._carHeading = heading;
  return icon;
}

// Watcher for real-time following
watch(fleetData, (newFleet) => {
  if (selectedImei.value && map.value) {
    // Basic safety: if the map is currently animating (flyTo/zoom) or being moved, skip
    if (map.value._animating || isMapAnimating.value) return;

    const selected = newFleet.find(v => v.imei === selectedImei.value);
    if (selected) {
      const latlng = [selected.lat, selected.lng];
      const bounds = map.value.getBounds();
      
      // USER REQUEST: Only center if vehicle goes outside the current visible screen
      if (!bounds.contains(latlng)) {
        map.value.panTo(latlng);
      }
    }
  }
}, { deep: true });

function handleSelectVehicle(obj) {
  selectedImei.value = obj.imei;
  selectedVehicle.value = obj;
  map.value.flyTo([obj.lat, obj.lng], 15, { animate: true, duration: 1.5 });

  if (markersMap[obj.imei]) {
    markersMap[obj.imei].openPopup();
  }
}

const showFollowOptionsModal = ref(false);
const initialFollowVehicle = ref(null);

function openFollowOptionsModal(obj) {
  initialFollowVehicle.value = obj;
  showFollowOptionsModal.value = true;
}

function triggerFollowInline(obj) {
  followPopupVehicle.value = obj;
  showToast(`Follow started for ${obj.name}`, 'info');
}

function triggerFollowWindow(obj) {
  const routeParams = router.resolve({ path: '/follow/' + obj.imei });
  window.open(routeParams.href, '_blank', 'width=1000,height=700,menubar=no,toolbar=no,location=no,status=no');
}

function closePanel() {
  selectedVehicle.value = null;
  // We DO NOT set selectedImei to null here to keep the tail visible on the map
}

function handleEditVehicle(obj) {
  editingVehicle.value = obj;
  showEditModal.value = true;
}

function saveVehicleEdit(updatedVehicle) {
  if (!updatedVehicle || !updatedVehicle.imei) {
    showEditModal.value = false;
    return;
  }

  const index = fleetData.value.findIndex(v => v.imei === updatedVehicle.imei);
  if (index !== -1) {
    fleetData.value[index] = { ...fleetData.value[index], ...updatedVehicle };
  }

  if (selectedVehicle.value?.imei === updatedVehicle.imei) {
    selectedVehicle.value = { ...selectedVehicle.value, ...updatedVehicle };
  }

  showEditModal.value = false;
  showToast('Vehicle updated successfully', 'success');
}

function handleSharePosition(obj) {
  sharingVehicle.value = obj;
  showSharePositionModal.value = true;
}

function saveSharePosition() {
  showToast('Share position saved locally', 'success');
  showSharePositionModal.value = false;
}

function handleSendCommand(obj) {
  objectControlVehicle.value = obj;
  showObjectControlModal.value = true;
}

function goProfile() {
  router.push('/profile');
}

function logout() {
  auth.logout();
  router.push('/login');
}

function handleOpenHistory(data) {
  const obj = data.obj || data;
  const period = data.period || 'today';

  activeMainTab.value = 'Archive';
  historyImei.value = obj.imei;
  historyPeriod.value = period;

  if (data.noFetch) {
    clearHistoryMap();
    historyData.value = [];
  } else {
    handleFetchHistory();
  }
}

function handlePeriodChange() {
  if (historyPeriod.value !== 'custom') {
    historyFrom.value = '';
    historyTo.value = '';
    } else {
      // Pre-fill datetime-local with today's date and default 00:00 for both
      // as per user request to have clean start/end points.
      const now = new Date();
      const dateStr = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0');
      
      if (!historyFrom.value) {
        historyFrom.value = dateStr + 'T00:00';
      }
      if (!historyTo.value) {
        // Defaulting to 00:00 for both From and To as requested by user.
        historyTo.value = dateStr + 'T00:00';
      }
    }
}

async function handleFetchHistory() {
  if (!historyImei.value) return;

  if (historyPeriod.value === 'custom') {
    if (!historyFrom.value && !historyTo.value) {
      showToast('Please select both "From" and "To" dates for custom range.');
      return;
    }

    if (!historyFrom.value) {
      showToast('Please select a "From" date.');
      return;
    }

    if (!historyTo.value) {
      const now = new Date();
      historyTo.value = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0') + 'T' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
    }
  }

  stopPlayback();
  clearHistoryMap();

  historyLoading.value = true;
  historyData.value = [];

  try {
    const now = new Date();
    let f, t;
    
    // ── Timezone Helper ──────────────────────────────────────────
    const getProfileOffset = (settings) => {
      let tzOffsetHours = 0;
      if (settings?.timezone) {
        const match = settings.timezone.match(/([+-])\s*(\d+)/);
        if (match) {
          const sign = match[1] === '+' ? 1 : -1;
          tzOffsetHours = sign * parseInt(match[2], 10);
        }
      }
      if (settings?.dst === 'true') tzOffsetHours += 1;
      return tzOffsetHours;
    };

    const parseLocalToUTC = (val, settings) => {
      if (!settings) return new Date(val); // Fallback to browser locale
      const tzOffsetHours = getProfileOffset(settings);
      const [dPart, tPart] = val.split('T');
      const [year, month, day] = dPart.split('-').map(Number);
      const [hour, min] = tPart.split(':').map(Number);
      const dUTC = new Date(Date.UTC(year, month - 1, day, hour, min));
      return new Date(dUTC.getTime() - (tzOffsetHours * 60 * 60 * 1000));
    };
    // ─────────────────────────────────────────────────────────────

    if (historyPeriod.value === 'today') {
      f = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0);
      t = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
    } else if (historyPeriod.value === 'yesterday') {
      f = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1, 0, 0, 0);
      t = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1, 23, 59, 59);
    } else if (historyPeriod.value === 'last_hour') {
      f = new Date(now.getTime() - 60 * 60 * 1000);
      t = new Date(now);
    } else if (historyPeriod.value === 'current_week') {
      const startOfWeek = now.getDate() - now.getDay(); 
      f = new Date(now.getFullYear(), now.getMonth(), startOfWeek, 0, 0, 0);
      t = new Date(now);
    } else if (historyPeriod.value === 'last_week') {
      const startOfPrevWeek = now.getDate() - now.getDay() - 7;
      f = new Date(now.getFullYear(), now.getMonth(), startOfPrevWeek, 0, 0, 0);
      t = new Date(now.getFullYear(), now.getMonth(), startOfPrevWeek + 6, 23, 59, 59);
    } else {
      // Custom Range: Use cached profile settings if available to maintain perfect sync
      f = parseLocalToUTC(historyFrom.value, historySettings.value);
      t = parseLocalToUTC(historyTo.value, historySettings.value);
    }

    const fromUTC = f.toISOString().slice(0, 19).replace('T', ' ');
    const toUTC = t.toISOString().slice(0, 19).replace('T', ' ');

    const params = {
      period: 'custom',
      from: fromUTC,
      to: toUTC
    };

    const res = await api.get(`/api/history/${historyImei.value}`, { params });

    if (res.data && res.data.ok) {
      const data = res.data.data;
      const settings = res.data.settings || {};
      historySettings.value = settings; // Cache for next search
      
      const tzOffsetHours = getProfileOffset(settings);

      // Convert DB UTC to Profile Local for display
      for (let i = 0; i < data.length; i++) {
        if (data[i].dt_tracker) {
          const utcPart = data[i].dt_tracker.replace(' ', 'T');
          const d = new Date(utcPart + 'Z'); 
          const profileDate = new Date(d.getTime() + (tzOffsetHours * 60 * 60 * 1000));
          
          data[i].dt_tracker = 
            profileDate.getUTCFullYear() + '-' + 
            String(profileDate.getUTCMonth()+1).padStart(2,'0') + '-' + 
            String(profileDate.getUTCDate()).padStart(2,'0') + ' ' + 
            String(profileDate.getUTCHours()).padStart(2,'0') + ':' + 
            String(profileDate.getUTCMinutes()).padStart(2,'0') + ':' + 
            String(profileDate.getUTCSeconds()).padStart(2,'0');
        }
      }
      
      historyData.value = data;

      if (historyData.value.length > 0) {
        drawHistoryPath();
      } else {
        showToast('No history found for this period.', 'error');
      }
    }
  } catch (err) {
    console.error('Failed fetching history', err);
  } finally {
    historyLoading.value = false;
  }
}

function clearHistoryMap() {
  if (historyPolyline) {
    map.value.removeLayer(historyPolyline);
    historyPolyline = null;
  }

  if (historyMarker) {
    map.value.removeLayer(historyMarker);
    historyMarker = null;
  }
}

function drawHistoryPath() {
  if (!map.value || historyData.value.length === 0) return;

  const latlngs = historyData.value.map(p => [p.lat, p.lng]);

  const historyColor = themeStore.isDarkMode ? '#36ffb4' : '#10b981';

  historyPolyline = L.polyline(latlngs, {
    color: historyColor,
    weight: 4,
    opacity: 0.8,
    dashArray: '5, 10'
  }).addTo(map.value);

  const firstPoint = historyData.value[0];
  const icon = createCarIcon(historyColor, firstPoint.angle || 0);
  historyMarker = L.marker([firstPoint.lat, firstPoint.lng], { icon }).addTo(map.value);

  map.value.fitBounds(historyPolyline.getBounds(), { padding: [50, 50] });
  historyIndex.value = 0;
}

function togglePlayback() {
  if (isHistoryPlaying.value) {
    pausePlayback();
  } else {
    startPlayback();
  }
}

function startPlayback() {
  if (historyData.value.length === 0) return;

  isHistoryPlaying.value = true;
  historyPlayStatus.value = 'Playing';

  playbackInterval = setInterval(() => {
    if (historyIndex.value < historyData.value.length - 1) {
      historyIndex.value++;
      updatePlaybackMarker();
    } else {
      stopPlayback();
    }
  }, historyPlaybackSpeed.value);
}

function pausePlayback() {
  isHistoryPlaying.value = false;
  historyPlayStatus.value = 'Paused';
  if (playbackInterval) clearInterval(playbackInterval);
}

function stopPlayback() {
  pausePlayback();
  historyPlayStatus.value = 'Stopped';
  historyIndex.value = 0;
  updatePlaybackMarker();
}

const isMapAnimating = ref(false);

const onMapMoveStart = () => { isMapAnimating.value = true; };
const onMapMoveEnd = () => { 
  isMapAnimating.value = false; 
  if (activeMainTab.value === 'Archive' && isHistoryPlaying.value) {
    updatePlaybackMarker();
  }
};

function updatePlaybackMarker() {
  if (!historyMarker || historyData.value.length === 0 || !map.value) return;

  // Safety guard against zoom/pan crashes
  if (isMapAnimating.value || map.value._animating) return;

  const point = historyData.value[historyIndex.value];
  if (!point) return;

  const historyColor = themeStore.isDarkMode ? '#36ffb4' : '#10b981';

  const latlng = [point.lat, point.lng];
  
  try {
    historyMarker.setLatLng(latlng);
    historyMarker.setIcon(createCarIcon(historyColor, point.angle || 0));
  } catch (e) {
    console.warn('Deferred history marker update', e);
  }
}

const fetchFleetData = async () => {
  try {
    const res = await api.get('/api/tracking/objects');
    if (res.data && res.data.ok) {
      fleetData.value = res.data.data;
    }
  } catch (err) {
    if (err.response && err.response.status === 401) {
      logout();
    }
    console.error('Failed fetching live fleet data', err);
  }
};

const fetchEventsData = async () => {
  try {
    eventsLoading.value = true;

    const res = await api.get('/api/events', {
      params: {
        page: eventsCurrentPage.value,
        per_page: eventsPerPage.value,
        search: eventsSearchQuery.value,
        sidx: eventsSortIdx.value,
        sord: eventsSortOrd.value
      }
    });

    if (res.data && res.data.ok) {
      eventsList.value = (res.data.data || []).map(item => ({
        id: item.event_id,
        time: item.dt_tracker,
        object: item.object_name || item.imei || 'Unknown',
        event: item.event_desc
      }));
      eventsTotalPages.value = res.data.last_page || 1;
    }
  } catch (err) {
    if (err.response && err.response.status === 401) {
      logout();
    }
    console.error('Failed fetching events', err);
  } finally {
    eventsLoading.value = false;
  }
};

async function handleDeleteAllEvents() {
  if (!confirm('Are you sure you want to delete all events?')) return;

  try {
    const res = await api.delete('/api/events');

    if (res.data && res.data.ok) {
      eventsList.value = [];
      eventsCurrentPage.value = 1;
      eventsTotalPages.value = 1;
      showToast('All events deleted successfully', 'success');
    }
  } catch (err) {
    if (err.response && err.response.status === 401) {
      logout();
    }
    console.error('Failed deleting events', err);
    showToast('Failed deleting events', 'error');
  }
}

function handleExportEvents() {
  const url = `${import.meta.env.VITE_API_BASE_URL}/api/events/export?token=${localStorage.getItem('token')}`;
  window.open(url, '_blank');
}

onMounted(async () => {
  if (!auth.isAuthed) {
    router.push('/login');
    return;
  }

  await auth.fetchMe();
  initMap();

  await Promise.all([
    fetchFleetData(),
    fetchEventsData()
  ]);

  fetchInterval = setInterval(fetchFleetData, 3000);

  window.addEventListener('resize', handleResize);
  handleResize();
});

function handleResize() {
  isMobileView.value = window.innerWidth <= 768;
  if (map.value) {
    setTimeout(() => {
      map.value.invalidateSize();
    }, 100);
  }
}

onUnmounted(() => {
  if (fetchInterval) clearInterval(fetchInterval);
  if (eventsSearchDebounce) clearTimeout(eventsSearchDebounce);

  window.removeEventListener('resize', handleResize);

  if (map.value) {
    map.value.off('zoomstart', onMapMoveStart);
    map.value.off('zoomend', onMapMoveEnd);
    map.value.off('movestart', onMapMoveStart);
    map.value.off('moveend', onMapMoveEnd);
  }

  for (const key in markersMap) {
    if (Object.prototype.hasOwnProperty.call(markersMap, key)) {
      delete markersMap[key];
    }
  }
});
</script>

<style scoped>
.tracking-layout {
  display: flex;
  height: 100vh;
  width: 100vw;
  overflow: hidden;
  background-color: var(--bg1);
}

/* Side Navigation */
.side-nav {
  width: 68px;
  background: var(--card);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  z-index: 1000;
  box-shadow: 4px 0 15px rgba(0,0,0,0.3);
  backdrop-filter: blur(10px);
  position: relative;
  height: 100vh;
}

.side-nav-inner {
  height: 100%;
  overflow-y: auto;
  overflow-x: hidden;
  display: flex;
  flex-direction: column;
  padding: 8px 0;
}

.side-nav-inner::-webkit-scrollbar {
  width: 3px;
}
.side-nav-inner::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.05);
  border-radius: 10px;
}

.brand-icon {
  padding: 8px 0;
  display: flex;
  justify-content: center;
  margin-bottom: 5px;
  flex-shrink: 0;
}
.mini-logo {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  filter: drop-shadow(0 4px 10px rgba(0,0,0,0.2));
}

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
  padding: 10px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  border-left: 3px solid transparent;
  position: relative;
  flex-shrink: 0;
}

.nav-item .label {
  font-size: 8.5px;
  font-weight: 600;
  text-transform: capitalize;
  letter-spacing: 0.2px;
  text-align: center;
  padding: 0 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  width: 100%;
}

.nav-item:hover {
  background: rgba(255,255,255,0.03);
  color: var(--text);
}

.nav-item.active {
  background: rgba(79, 124, 255, 0.08);
  color: var(--accent);
  border-left: 3px solid var(--accent);
}

.more-dots {
  padding: 8px 0;
  opacity: 0.2;
}

.nav-bottom {
  margin-top: auto;
  width: 100%;
  display: flex;
  flex-direction: column;
}

.admin-item {
  background: var(--accent);
  color: #08101f !important;
  margin-top: 5px;
  padding: 12px 0;
  font-weight: 700;
}
.admin-item:hover {
  filter: brightness(1.1);
}
.admin-item .label {
  color: #08101f;
}

.divider-dots {
  display: flex;
  justify-content: center;
  padding: 6px 0;
  opacity: 0.2;
}

.objects-panel {
  width: 400px;
  background: var(--card);
  backdrop-filter: blur(12px);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  z-index: 999;
  box-shadow: 5px 0 20px rgba(0,0,0,0.2);
  transition: background 0.3s, color 0.3s, border-color 0.3s;
  flex-shrink: 0;
}

.panel-tabs-modern {
  display: flex;
  padding: 12px 14px;
  gap: 10px;
  background: var(--card);
  border-bottom: 1px solid var(--border);
  align-items: center;
  flex-shrink: 0;
}

.panel-tab-pill {
  flex: 1; /* Make tabs fill the width */
  height: 52px;
  border-radius: 12px;
  border: none;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  color: #64748b;
}

.panel-tab-pill.active {
  background: #0055ff;
  color: #ffffff;
  box-shadow: 0 4px 15px rgba(0, 85, 255, 0.25);
  flex: 1.5; /* Give more space to the active tab to prevent text cutoff */
}

.pill-label {
  font-size: 15px;
  font-weight: 700;
  white-space: nowrap;
}

.nav-icon {
  flex-shrink: 0;
  transition: color 0.2s;
}

.objects-panel.collapsed {
  width: 60px;
}
.objects-panel.collapsed .panel-header {
  padding: 20px 10px;
}
.objects-panel.collapsed .objects-list {
  display: none;
}

.panel-header {
  padding: 20px;
  border-bottom: 1px solid var(--border);
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}
.objects-panel.collapsed .header-top {
  justify-content: center;
  margin-bottom: 0;
}

/* Modern Premium Map Controls Exact Figma Match */
.custom-map-controls {
  position: absolute;
  top: 90px;
  left: 20px; /* Moved to left */
  display: flex;
  flex-direction: column;
  gap: 15px;
  z-index: 1000;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.control-pill {
  background: #ffffff;
  border: 1px solid rgba(0,0,0,0.05); /* Softer border */
  border-radius: 14px; /* Slightly more rounded */
  display: flex;
  flex-direction: column;
  align-items: center;
  box-shadow: 0 8px 30px rgba(0,0,0,0.08); /* Premium soft shadow */
  overflow: hidden;
  padding: 4px 0;
}

.tool-btn {
  background: transparent;
  border: none;
  width: 48px;
  height: 52px; /* Taller buttons */
  display: flex;
  align-items: center;
  justify-content: center;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s ease;
}

.tool-btn svg {
  width: 25px;
  height: 25px;
  stroke-width: 2.2; /* Consistent stroke */
}

.tool-btn.blue-btn {
  color: #2563eb;
}

.tool-btn:hover {
  background: #f8fafc;
  color: #1d4ed8;
}

.tool-btn.active {
  color: #1d4ed8;
  background: #eff6ff;
}

.tool-btn.blue-btn:hover {
  color: #1e40af;
}

.tool-divider {
  width: 30px; /* Centered narrow divider */
  height: 1px;
  background: #f1f5f9;
}

/* Ensure controls adapt to dark mode but keep Figma vibe */
body.dark-mode .control-pill {
  background: #1e293b;
  border-color: rgba(255,255,255,0.06);
  box-shadow: 0 10px 40px rgba(0,0,0,0.4);
}

body.dark-mode .tool-btn {
  color: #94a3b8;
}

body.dark-mode .tool-btn.blue-btn {
  color: #38bdf8;
}

body.dark-mode .tool-btn:hover, body.dark-mode .tool-btn.active {
  background: #334155;
  color: #38bdf8;
}

body.dark-mode .tool-divider {
  background: #334155;
}

@media (max-width: 768px) {
  .custom-map-controls {
    left: auto;
    right: 20px; /* Back to right for mobile */
    top: 20px;
  }
}

.toggle-btn {
  background: rgba(255,255,255,0.05);
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
  font-size: 18px;
  font-weight: 700;
  letter-spacing: 0.5px;
  color: var(--text);
}

.search-box input {
  width: 100%;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: rgba(0,0,0,0.2);
  color: var(--text);
  padding: 10px 14px;
  font-size: 13px;
  outline: none;
  transition: all 0.2s;
}

.search-box input:focus {
  border-color: var(--accent);
}

.status-tabs {
  display: flex;
  justify-content: space-between;
  margin-top: 15px;
  background: var(--input-bg);
  border-radius: 8px;
  padding: 4px;
  border: 1px solid var(--border);
}

.status-tab {
  flex: 1;
  background: transparent;
  border: none;
  color: var(--muted);
  font-size: 12px;
  padding: 6px 0;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.status-tab.active {
  background: var(--card);
  color: var(--text);
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.objects-list {
  flex: 1;
  overflow-y: auto;
  padding: 10px;
}

.objects-list::-webkit-scrollbar {
  width: 6px;
}
.objects-list::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.1);
  border-radius: 3px;
}

.main-tabs-sys {
  display: flex;
  background: var(--input-bg);
  border-bottom: 1px solid var(--border);
}

.m-tab {
  flex: 1;
  background: transparent;
  border: none;
  color: var(--muted);
  padding: 12px 0;
  font-size: 13px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.m-tab:hover {
  color: var(--text);
  background: var(--border);
}

.m-tab.active {
  background: var(--accent);
  color: #fff;
}

.status-tabs-wrapper {
  margin: 15px 0 0 0;
}



.v-row-item {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  border-radius: 0;
  border-bottom: 1px solid var(--border);
  cursor: pointer;
  background: transparent;
  transition: all 0.2s ease;
  gap: 10px;
}

.v-row-item:hover {
  background: var(--border);
}

.v-row-item.selected {
  background: rgba(79, 124, 255, 0.1);
}

.v-row-item.Moving {
  background: linear-gradient(90deg, rgba(54, 255, 180, 0.1) 0%, transparent 100%);
  border-left: 3px solid #36ffb4;
}
.v-row-item.Moving:hover {
  background: linear-gradient(90deg, rgba(54, 255, 180, 0.15) 0%, transparent 100%);
}

.v-row-item.Offline {
  background: linear-gradient(90deg, rgba(255, 90, 120, 0.1) 0%, transparent 100%);
  border-left: 3px solid #ff5a78;
}
.v-row-item.Offline:hover {
  background: linear-gradient(90deg, rgba(255, 90, 120, 0.15) 0%, transparent 100%);
}

.v-row-item.Idle {
  background: linear-gradient(90deg, rgba(255, 204, 0, 0.1) 0%, transparent 100%);
  border-left: 3px solid #ffcc00;
}

.v-row-item.Parking {
  border-left: 3px solid #0d6efd;
}

.row-checkboxes {
  display: flex;
  gap: 6px;
  align-items: center;
}

.row-checkboxes input[type="checkbox"] {
  accent-color: #0d6efd;
  width: 14px;
  height: 14px;
  cursor: pointer;
}

.row-icon {
  display: flex;
  align-items: center;
  justify-content: center;
}
.row-icon.Moving { color: #36ffb4; }
.row-icon.Idle { color: #ffcc00; }
.row-icon.Offline { color: #ff5a78; }
.row-icon.Parking { color: #0d6efd; }

.row-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.row-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.row-date {
  font-size: 11px;
  color: var(--muted);
  margin-top: 2px;
}

.row-speed {
  font-size: 13px;
  font-weight: 600;
  color: var(--muted);
  white-space: nowrap;
}
.row-speed.is-moving { color: var(--text); }
.row-speed small { font-size: 10px; font-weight: 400; color: var(--muted); }

.row-sensors {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: 5px;
}

.map-area {
  flex: 1;
  position: relative;
  display: flex;
  flex-direction: column;
}



.map-container {
  flex: 1;
  width: 100%;
}

.user-badge {
  position: absolute;
  top: 15px;
  right: 15px;
  background: var(--glass);
  backdrop-filter: blur(10px);
  padding: 6px 6px 6px 15px;
  border-radius: 30px;
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid var(--border);
  cursor: pointer;
  z-index: 1000;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.user-badge:hover {
  background: var(--card);
  border-color: var(--accent);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.4);
}

.user-name {
  color: var(--text);
  font-size: 13px;
  opacity: 0.9;
}

.user-name strong {
  color: var(--accent);
  font-weight: 700;
}

.user-avatar-mini {
  width: 32px;
  height: 32px;
  background: var(--accent);
  color: #08101f;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(79, 124, 255, 0.4);
}

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
  padding: 10px;
  border-radius: 4px;
  text-align: center;
}

.stat-box-legacy.blue {
  background: rgba(79, 124, 255, 0.1);
  border: 1px solid rgba(79, 124, 255, 0.2);
}

.stat-box-legacy.red {
  background: rgba(255, 90, 120, 0.1);
  border: 1px solid rgba(255, 90, 120, 0.2);
}

.stat-val {
  display: block;
  font-size: 16px;
  font-weight: 800;
}

.stat-lbl {
  display: block;
  font-size: 10px;
  text-transform: uppercase;
  opacity: 0.6;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
  padding-bottom: 8px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}

.label-with-icon {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--muted);
  font-weight: 500;
}

.label-with-icon svg {
  color: var(--accent);
  opacity: 0.8;
}

.info-row .value {
  color: var(--text);
  font-weight: 600;
  text-align: right;
}

.address-text {
  max-width: 150px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.position-link {
  color: var(--accent) !important;
  text-decoration: underline;
  cursor: pointer;
}

@media (max-width: 1024px) {
  .data-grid-comprehensive {
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }
}

@media (max-width: 768px) {
  .details-panel {
    height: 60vh;
  }
  .data-grid-comprehensive {
    grid-template-columns: 1fr;
    gap: 15px;
  }
  .address-text {
    max-width: 200px;
  }
  .panel-header-tabs {
    padding: 0 10px;
  }
  .tabs-list {
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    scrollbar-width: none;
  }
  .tabs-list::-webkit-scrollbar {
    display: none;
  }
  .tab-btn {
    padding: 0 15px;
    font-size: 13px;
    white-space: nowrap;
  }
}

.behavior-stats {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}

.stat-box {
  flex: 1;
  padding: 10px;
  border-radius: 4px;
  text-align: center;
}

.stat-box.blue {
  background: rgba(79, 124, 255, 0.1);
  border: 1px solid rgba(79, 124, 255, 0.2);
}

.stat-box.red {
  background: rgba(255, 90, 120, 0.1);
  border: 1px solid rgba(255, 90, 120, 0.2);
}

.stat-val {
  font-size: 16px;
  font-weight: 700;
  display: block;
}

.stat-box.blue .stat-val { color: var(--accent); }

.center-content {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 80px;
}

.muted-text {
  color: var(--muted);
  font-size: 13px;
  font-style: italic;
  opacity: 0.6;
}

.no-data-msg-small {
  color: var(--muted);
  font-size: 14px;
  font-weight: 300;
}

.edit-icon-btn {
  background: transparent;
  border: none;
  color: var(--muted);
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  transition: color 0.2s;
}

.edit-icon-btn:hover {
  color: var(--accent);
}

.empty-state-compact {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60px;
}

.no-data-msg-compact {
  color: var(--muted);
  font-size: 12px;
  opacity: 0.5;
}

.status-pill {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 700;
}

.status-pill.Moving {
  background: rgba(54, 255, 180, 0.1);
  color: #36ffb4;
  border: 1px solid rgba(54, 255, 180, 0.3);
}
.status-pill.Idle {
  background: rgba(255, 204, 0, 0.1);
  color: #ffcc00;
  border: 1px solid rgba(255, 204, 0, 0.3);
}
.status-pill.Offline {
  background: rgba(160, 160, 160, 0.1);
  color: #a0a0a0;
  border: 1px solid rgba(160, 160, 160, 0.3);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 200px;
  opacity: 0.5;
}

.slide-up-enter-active, .slide-up-leave-active {
  transition: transform 0.3s ease;
}
.slide-up-enter-from, .slide-up-leave-to {
  transform: translateY(100%);
}

:deep(.leaflet-popup-content-wrapper) {
  background: var(--card);
  color: var(--text);
  border: 1px solid var(--border);
  border-radius: 12px;
}
:deep(.leaflet-popup-tip) {
  background: var(--card);
}
:deep(.custom-vehicle-marker) {
  transition: all 1s linear;
}

@media (max-width: 1024px) {
  .details-panel {
    height: 360px;
  }
}

@media (max-width: 768px) {
  .tracking-layout {
    flex-direction: column;
    height: 100vh;
    height: 100dvh;
  }
  
  .side-nav {
    width: 100%;
    height: 60px;
    min-height: 60px;
    border-right: none;
    border-bottom: 1px solid var(--border);
    padding: 0;
  }

  .side-nav-inner {
    flex-direction: row;
    width: 100%;
    padding: 0 10px;
    align-items: center;
    overflow-x: auto;
    overflow-y: hidden;
    justify-content: flex-start;
    gap: 5px;
    flex-wrap: nowrap;
  }

  .side-nav-inner::-webkit-scrollbar {
    height: 0;
    display: none;
  }
  
  .brand-icon {
    margin-bottom: 0;
    margin-right: 15px;
    padding: 0;
    flex-shrink: 0;
  }
  
  .mini-logo {
    width: 32px;
    height: 32px;
  }
  
  .nav-links {
    flex-direction: row;
    width: auto;
    gap: 5px;
    flex-shrink: 0;
  }
  
  .nav-item {
    width: 50px;
    height: 50px;
    padding: 0;
    border-left: none;
    border-bottom: 3px solid transparent;
    justify-content: center;
    border-radius: 8px;
  }
  
  .nav-item .label {
    display: none;
  }
  
  .nav-item.active {
    border-left: none;
    border-bottom: 3px solid var(--accent);
    background: rgba(79, 124, 255, 0.1);
  }
  
  .nav-bottom {
    margin-top: 0;
    display: flex;
    flex-direction: row;
    width: auto;
    flex-shrink: 0;
    gap: 5px;
  }

  .admin-item {
    margin-top: 0;
    width: 50px !important;
    height: 50px;
    padding: 0;
    border-radius: 8px;
  }

  .divider-dots,
  .more-dots {
    display: none;
  }
  
  .objects-panel {
    width: 100%;
    flex: none;
    border-right: none;
    border-top: 1px solid var(--border);
    order: 3;
    overflow-y: hidden;
    display: flex;
    flex-direction: column;
    background: var(--card);
    z-index: 1000;
  }
  
  .mobile-drag-handle {
    width: 100%;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    cursor: ns-resize;
    flex-shrink: 0;
  }

  .mobile-drag-handle .drag-bar {
    width: 36px;
    height: 4px;
    background: var(--muted);
    opacity: 0.3;
    border-radius: 2px;
  }
  
  .objects-panel.collapsed {
    width: 100%;
    height: 60px;
  }
  
  .map-area {
    width: 100%;
    height: auto !important;
    flex: 1;
    min-height: 0;
    order: 2;
  }
  
  .details-panel {
    height: 50vh;
    position: fixed;
    z-index: 2000;
  }
  
  .data-grid {
    grid-template-columns: 1fr;
  }

  .custom-toast {
    top: 10px;
    right: 10px;
    left: 10px;
    justify-content: center;
  }
}

.custom-toast {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 99999;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 20px;
  background: var(--card);
  border-radius: 8px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
  color: var(--text);
  font-weight: 600;
  font-size: 14px;
}
.toast-error {
  border-left: 4px solid #ef4444;
}
.toast-success {
  border-left: 4px solid #36ffb4;
}
.toast-info {
  border-left: 4px solid #4f7cff;
}

.toast-slide-enter-active, .toast-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.toast-slide-enter-from, .toast-slide-leave-to {
  opacity: 0;
  transform: translateX(50px);
}
</style>