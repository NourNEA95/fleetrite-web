<template>
  <div class="follow-page-layout">
    <div class="header follow-header-modern">
      <div class="logo">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="#60a5fa"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
        <span class="logo-text">Live Follow</span>
      </div>
      <div class="vehicle-info-pill" v-if="vehicle">
        <span class="v-name">{{ vehicle.name }}</span>
        <span class="v-status" :class="vehicle.status?.toLowerCase()">{{ vehicle.status }}</span>
        <span class="v-speed">{{ Math.round(vehicle.speed || 0) }} km/h</span>
      </div>
    </div>
    
    <div class="map-wrapper">
      <div id="leafletFollowPageMap"></div>

      <!-- Loading Overlay -->
      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
        <p>Locating Vehicle...</p>
      </div>

       <!-- Info Bar -->
       <div v-if="vehicle" class="info-bar">
         <div class="info-item">
           <span class="label">IMEI</span>
           <span class="value">{{ vehicle.imei }}</span>
         </div>
         <div class="info-item">
           <span class="label">Date</span>
           <span class="value">{{ vehicle.dt_tracker }}</span>
         </div>
         <div class="info-item">
           <span class="label">Ignition</span>
           <span class="value" :class="vehicle.ignition === 'On' ? 'text-green' : 'text-danger'">{{ vehicle.ignition || 'Off' }}</span>
         </div>
         <div class="info-item">
           <span class="label">Odometer</span>
           <span class="value">{{ vehicle.odometer || '0' }} km</span>
         </div>
       </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useRoute } from 'vue-router';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import api from '../services/api';

const route = useRoute();
const imei = route.params.imei;

const vehicle = ref(null);
const loading = ref(true);

let map = null;
let marker = null;
let pollInterval = null;
let trailRoute = null;
let trailCoords = [];

// Fix leaflet default icons
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

onMounted(() => {
  document.title = `Following ${imei}`;
  
  map = L.map('leafletFollowPageMap', {
    zoomControl: true,
  }).setView([0, 0], 2);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

  trailRoute = L.polyline([], { color: '#4f7cff', weight: 4 }).addTo(map);

  fetchVehicleData();
  pollInterval = setInterval(fetchVehicleData, 5000);
});

onBeforeUnmount(() => {
  if (pollInterval) clearInterval(pollInterval);
  if (map) {
    map.remove();
    map = null;
  }
  trailRoute = null;
  marker = null;
});

async function fetchVehicleData() {
  try {
    const res = await api.get('/api/tracking/objects');
    if (res.data && res.data.ok) {
      const data = res.data.data;
      // Find our specific vehicle
      const dataArr = Array.isArray(data) ? data : Object.values(data);
      const targetVehicle = dataArr.find(v => v.imei == imei);
      
      if (targetVehicle) {
        vehicle.value = targetVehicle;
        document.title = `Tracking ${targetVehicle.name}`;
        updateMap(targetVehicle);
      }
    }
  } catch(e) {
    console.error('Error fetching live data', e);
  } finally {
    loading.value = false;
  }
}

function updateMap(v) {
  if (!v || !v.lat || !v.lng) return;
  const latlng = [v.lat, v.lng];

  if (trailRoute) {
    if (trailCoords.length === 0 || trailCoords[trailCoords.length - 1][0] !== latlng[0] || trailCoords[trailCoords.length - 1][1] !== latlng[1]) {
      trailCoords.push(latlng);
      if (map && map._animatingZoom) {
        map.once('zoomend', () => { if (trailRoute) trailRoute.setLatLngs(trailCoords); });
      } else {
        trailRoute.setLatLngs(trailCoords);
      }
    }
  }

  if (!marker) {
    const icon = L.divIcon({
      className: 'custom-div-icon',
      html: `<div class="v-icon-map ${v.status?.toLowerCase() || 'offline'}"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13 8 13.67 8 14.5 7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg></div>`,
      iconSize: [30, 30],
      iconAnchor: [15, 15]
    });
    marker = L.marker(latlng, { icon }).addTo(map);
    map.setView(latlng, 16);
  } else {
    marker.setLatLng(latlng);
    map.panTo(latlng, { animate: true, duration: 1.0 });
  }
}
</script>

<style scoped>
.follow-page-layout {
  height: 100vh;
  width: 100vw;
  display: flex;
  flex-direction: column;
  background: var(--bg, #0B1120);
  color: var(--text, #fff);
  font-family: 'Inter', sans-serif;
  overflow: hidden;
}

.follow-header-modern {
  height: 60px;
  background: #0c335a !important;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  border-bottom: 2px solid #1e4b7a;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
  z-index: 1000;
}

.logo {
  display: flex;
  align-items: center;
  gap: 10px;
}

.logo-text {
  font-size: 19px;
  font-weight: 800;
  color: #ffffff !important;
  letter-spacing: 0.5px;
}

.vehicle-info-pill {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #cbd5e1;
  padding: 6px 16px;
  border-radius: 10px;
  color: #0c335a;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.v-name {
  font-weight: 800;
  font-size: 16px;
}

.v-status {
  font-size: 10px;
  text-transform: uppercase;
  padding: 2px 8px;
  border-radius: 6px;
  font-weight: 800;
  letter-spacing: 0.3px;
}

.v-status.moving { background: #36ffb4; color: #064e3b; }
.v-status.idle { background: #ffcc00; color: #78350f; }
.v-status.offline { background: #ff5a78; color: #ffffff; }

.v-speed {
  font-size: 16px;
  font-weight: 800;
  color: #2563eb;
}

.map-wrapper {
  flex: 1;
  position: relative;
}

#leafletFollowPageMap {
  width: 100%;
  height: 100%;
}

.loading-overlay {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(11, 17, 32, 0.8);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}
.spinner {
  width: 40px; height: 40px;
  border: 3px solid rgba(255,255,255,0.1);
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin 1s infinite linear;
  margin-bottom: 15px;
}
@keyframes spin { 100% { transform: rotate(360deg); } }

.info-bar {
  position: absolute;
  bottom: 25px;
  left: 50%;
  transform: translateX(-50%);
  background: #ffffff !important;
  display: flex;
  gap: 40px;
  padding: 15px 40px;
  border-radius: 15px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.25);
  border: 1.5px solid #e2e8f0;
  z-index: 1000;
}
.info-item {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.info-item .label {
  font-size: 12px;
  color: #64748b !important;
  font-weight: 700;
  text-transform: uppercase;
  margin-bottom: 6px;
  letter-spacing: 0.5px;
}
.info-item .value {
  font-size: 16px;
  font-weight: 800;
  color: #0c335a !important;
}
.text-green { color: #36ffb4 !important; }
.text-danger { color: #ef4444 !important; }

::v-deep .v-icon-map {
  width: 30px;
  height: 30px;
  background: var(--card);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid;
}
::v-deep .v-icon-map.moving { color: #36ffb4; border-color: #36ffb4; }
::v-deep .v-icon-map.idle { color: #ffcc00; border-color: #ffcc00; }
::v-deep .v-icon-map.offline { color: #ff5a78; border-color: #ff5a78; }
</style>
