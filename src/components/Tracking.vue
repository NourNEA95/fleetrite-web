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

      <!-- Panel Tab Bar (Fleet / Archive) -->
      <div class="panel-tabs" v-show="isPanelOpen">
        <button 
          class="panel-tab" 
          :class="{ active: activeMainTab === 'Fleet' }" 
          @click="activeMainTab = 'Fleet'"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="1" y="3" width="15" height="13"></rect>
            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
            <circle cx="5.5" cy="18.5" r="2.5"></circle>
            <circle cx="18.5" cy="18.5" r="2.5"></circle>
          </svg>
          Fleet
        </button>

        <button 
          class="panel-tab" 
          :class="{ active: activeMainTab === 'Events' }" 
          @click="activeMainTab = 'Events'"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
          </svg>
          Events
        </button>

        <button 
          class="panel-tab" 
          :class="{ active: activeMainTab === 'Places' }" 
          @click="activeMainTab = 'Places'"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
          Places
        </button>

        <button 
          class="panel-tab" 
          :class="{ active: activeMainTab === 'Archive' }" 
          @click="activeMainTab = 'Archive'"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          Archive
        </button>
      </div>

      <FleetPanel 
        v-if="activeMainTab === 'Fleet'"
        :is-panel-open="isPanelOpen"
        v-model:search-query="searchQuery"
        v-model:filter="filter"
        :filtered-objects="filteredObjects"
        :selected-imei="selectedImei"
        @toggle-panel="togglePanel"
        @select-vehicle="selectVehicle"
        @open-history="openHistory"
        @follow-vehicle="handleFollowVehicle"
        @follow-new-window="handleFollowNewWindow"
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

      <!-- User Profile Badge -->
      <div class="user-badge" @click="goProfile">
        <span class="user-name">Hello, <strong>{{ auth.user?.username || 'Guest' }}</strong></span>
        <div class="user-avatar-mini">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
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
        @init-resize="initPanelResize"
        @close="closePanel"
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
      v-if="showReportsModal"
      @close="showReportsModal = false"
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
import api from '../services/api';

// Component Imports
import SidebarNav from './panels/SidebarNav.vue';
import FleetPanel from './panels/FleetPanel.vue';
import EventsPanel from './panels/EventsPanel.vue';
import ArchivePanel from './panels/ArchivePanel.vue';
import VehicleDetails from './panels/VehicleDetails.vue';
import FollowPopup from './panels/FollowPopup.vue';
import EditObjectModal from './modals/EditObjectModal.vue';
import SharePositionModal from './modals/SharePositionModal.vue';
import ObjectControlModal from './modals/ObjectControlModal.vue';
import ReportsModal from './modals/ReportsModal.vue';

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

const statusColors = computed(() => ({
  Moving: themeStore.isDarkMode ? '#36ffb4' : '#10b981',
  Idle: themeStore.isDarkMode ? '#ffcc00' : '#d97706',
  Offline: themeStore.isDarkMode ? '#ff5a78' : '#dc2626'
}));

const map = ref(null);
const markersGroup = ref(null);

const searchQuery = ref('');
const filter = ref('All');
const showReportsModal = ref(false);

function handleNavAction(action) {
  if (action === 'reports') {
    showReportsModal.value = true;
  } else if (action === 'tracking') {
    activeMainTab.value = 'Fleet';
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
    if (map.value && markersGroup.value) {
      if (!map.value.hasLayer(markersGroup.value)) {
        markersGroup.value.addTo(map.value);
      }
    }
    drawMarkers();
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

function initMap() {
  map.value = L.map('leafletMap').setView([29.3759, 47.9774], 11);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
  }).addTo(map.value);

  markersGroup.value = L.featureGroup().addTo(map.value);
  drawMarkers();
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

function drawMarkers() {
  if (!markersGroup.value || !map.value || activeMainTab.value === 'Archive') return;

  if (map.value._animatingZoom) {
    setTimeout(drawMarkers, 500);
    return;
  }

  fleetData.value.forEach(obj => {
    if (markersMap[obj.imei]) {
      const marker = markersMap[obj.imei];
      marker.setLatLng([obj.lat || 0, obj.lng || 0]);

      const newColor = statusColors.value[obj.status] || statusColors.value.Offline;

      const newHeading = obj.heading != null ? obj.heading : 0;
      const oldColor = marker.options.icon.options._carColor;
      const oldHeading = marker.options.icon.options._carHeading;

      if (oldColor !== newColor || oldHeading !== newHeading) {
        marker.setIcon(createCarIcon(newColor, newHeading));
      }

      marker.setPopupContent(`<b>${obj.name}</b><br>IMEI: ${obj.imei}<br>Speed: ${obj.speed} km/h`);

      if (selectedImei.value === obj.imei) {
        selectedVehicle.value = obj;
        if (!map.value._animatingZoom && selectedVehicle.value) {
          map.value.panTo([obj.lat, obj.lng], { animate: true, duration: 1.0 });
        }
      }

      if (followPopupVehicle.value && followPopupVehicle.value.imei === obj.imei) {
        followPopupVehicle.value = obj;
      }
    } else {
      const markerColor = statusColors.value[obj.status] || statusColors.value.Offline;

      const icon = createCarIcon(markerColor, obj.heading != null ? obj.heading : 0);

      const marker = L.marker([obj.lat || 0, obj.lng || 0], { icon })
        .bindPopup(`<b>${obj.name}</b><br>IMEI: ${obj.imei}<br>Speed: ${obj.speed} km/h`)
        .addTo(markersGroup.value);

      marker.on('click', () => {
        selectVehicle(obj);
      });

      markersMap[obj.imei] = marker;

      if (followedImei.value === obj.imei && !map.value._animatingZoom) {
        map.value.panTo([obj.lat, obj.lng], { animate: true, duration: 1.0 });
      }

      if (followPopupVehicle.value && followPopupVehicle.value.imei === obj.imei) {
        followPopupVehicle.value = obj;
      }

      if (selectedImei.value === obj.imei) {
        selectedVehicle.value = obj;
        if (!map.value._animatingZoom && selectedVehicle.value && !followedImei.value) {
          map.value.panTo([obj.lat, obj.lng], { animate: true, duration: 1.0 });
        }
      }
    }
  });
}

function selectVehicle(obj) {
  selectedImei.value = obj.imei;
  selectedVehicle.value = obj;
  map.value.flyTo([obj.lat, obj.lng], 16, { animate: true, duration: 1.5 });

  if (markersMap[obj.imei]) {
    markersMap[obj.imei].openPopup();
  }
}

function handleFollowVehicle(obj) {
  followPopupVehicle.value = obj;
  showToast(`Follow started for ${obj.name}`, 'info');
}

function handleFollowNewWindow(obj) {
  const routeParams = router.resolve({ path: '/follow/' + obj.imei });
  window.open(routeParams.href, '_blank', 'width=1000,height=700,menubar=no,toolbar=no,location=no,status=no');
}

function closePanel() {
  selectedVehicle.value = null;
  selectedImei.value = null;
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

function openHistory(data) {
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
      const offsetMs = now.getTimezoneOffset() * 60 * 1000;
      historyTo.value = new Date(now.getTime() - offsetMs).toISOString().slice(0, 16);
    }
  }

  stopPlayback();
  clearHistoryMap();

  historyLoading.value = true;
  historyData.value = [];

  try {
    const params = {
      period: historyPeriod.value,
      from: historyFrom.value,
      to: historyTo.value
    };

    const res = await api.get(`/api/history/${historyImei.value}`, { params });

    if (res.data && res.data.ok) {
      historyData.value = res.data.data;

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

  historyPolyline = L.polyline(latlngs, {
    color: '#4f7cff',
    weight: 4,
    opacity: 0.8,
    dashArray: '5, 10'
  }).addTo(map.value);

  const firstPoint = historyData.value[0];
  const icon = createCarIcon('#4f7cff', firstPoint.angle || 0);
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

function updatePlaybackMarker() {
  if (!historyMarker || historyData.value.length === 0) return;

  const point = historyData.value[historyIndex.value];
  if (!point) return;

  const latlng = [point.lat, point.lng];
  historyMarker.setLatLng(latlng);
  historyMarker.setIcon(createCarIcon('#4f7cff', point.angle || 0));
}

const fetchFleetData = async () => {
  try {
    const res = await api.get('/api/tracking/objects');
    if (res.data && res.data.ok) {
      fleetData.value = res.data.data;
      drawMarkers();
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
  width: 320px;
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

.panel-tabs {
  display: flex;
  background: var(--input-bg);
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}

.panel-tab {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 11px 0;
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  color: var(--muted);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  letter-spacing: 0.3px;
}

.panel-tab:hover {
  color: var(--text);
  background: var(--border);
}

.panel-tab.active {
  color: var(--accent);
  border-bottom-color: var(--accent);
  background: var(--card);
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