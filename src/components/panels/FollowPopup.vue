<template>
  <div class="follow-popup-window" :style="popupStyle" @mousedown="bringToFront">
    <div class="follow-popup-header" @mousedown="startDrag">
      <span class="popup-title">Follow ({{ vehicle?.name }})</span>
      <button class="close-btn" @click="$emit('close')">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>
    <div class="follow-popup-toolbar">
      <label><input type="checkbox" v-model="showInfo" /> Info</label>
      <label><input type="checkbox" v-model="autoFollow" /> Follow</label>
    </div>
    <div class="follow-popup-body">
      <div ref="mapRef" class="mini-map-container"></div>
      
      <!-- Info Overlay -->
      <div v-if="showInfo && vehicle" class="info-overlay">
        <div><strong>Speed:</strong> {{ Math.round(vehicle?.speed || 0) }} km/h</div>
        <div><strong>Date:</strong> {{ vehicle?.dt_tracker }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
  vehicle: Object
});

const emit = defineEmits(['close']);

const showInfo = ref(false);
const autoFollow = ref(true);

const popupStyle = ref({
  top: '80px',
  left: '400px',
  zIndex: 9999
});

let isDragging = false;
let startX = 0;
let startY = 0;
let initialTop = 0;
let initialLeft = 0;

const mapRef = ref(null);
let miniMap = null;
let miniMarker = null;
let trailRoute = null;
let trailCoords = [];
let resizeObserver = null;

onMounted(() => {
  // Init leaflet mini map
  miniMap = L.map(mapRef.value, {
    zoomControl: true,
    attributionControl: false
  }).setView([props.vehicle?.lat || 0, props.vehicle?.lng || 0], 14);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(miniMap);
  
  trailRoute = L.polyline([], { color: '#4f7cff', weight: 4 }).addTo(miniMap);
  if (props.vehicle?.lat && props.vehicle?.lng) {
    trailCoords.push([props.vehicle.lat, props.vehicle.lng]);
    trailRoute.setLatLngs(trailCoords);
  }

  miniMarker = L.marker([props.vehicle?.lat || 0, props.vehicle?.lng || 0], { icon: getIcon(props.vehicle) }).addTo(miniMap);
  setTimeout(() => miniMap.invalidateSize(), 100);

  // Map resize observer to react to manual window resizes
  resizeObserver = new ResizeObserver(() => {
    if (miniMap) {
      miniMap.invalidateSize();
    }
  });
  if (mapRef.value) resizeObserver.observe(mapRef.value);
});

function getIcon(v) {
  const statusClass = v?.status?.toLowerCase() || 'offline';
  return L.divIcon({
    className: 'custom-div-icon',
    html: `<div class="v-icon-map ${statusClass}"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13 8 13.67 8 14.5 7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg></div>`,
    iconSize: [30, 30],
    iconAnchor: [15, 15]
  });
}

onBeforeUnmount(() => {
  if (resizeObserver) {
    resizeObserver.disconnect();
    resizeObserver = null;
  }
  if (miniMap) {
    miniMap.remove();
    miniMap = null;
  }
  trailRoute = null;
  miniMarker = null;
});

watch(() => props.vehicle, (newV) => {
  if (newV && miniMap && miniMarker && trailRoute) {
    const coords = [newV.lat, newV.lng];
    
    // Add to trail if coords changed
    if (trailCoords.length === 0 || trailCoords[trailCoords.length - 1][0] !== coords[0] || trailCoords[trailCoords.length - 1][1] !== coords[1]) {
      trailCoords.push(coords);
      // Add to trail if coords changed safely
      if (miniMap && miniMap._animatingZoom) {
        miniMap.once('zoomend', () => {
          if (trailRoute && miniMap) trailRoute.setLatLngs(trailCoords);
        });
      } else if (trailRoute) {
        trailRoute.setLatLngs(trailCoords);
      }
    }

    miniMarker.setLatLng(coords);
    miniMarker.setIcon(getIcon(newV));

    if (autoFollow.value && miniMap && !miniMap._animatingZoom) {
      // Don't pan too aggressively if already animating
      miniMap.panTo(coords, { animate: true, duration: 1 });
    }
  }
}, { deep: true });

function bringToFront() {
  popupStyle.value.zIndex = 10000;
}

function startDrag(e) {
  isDragging = true;
  startX = e.clientX;
  startY = e.clientY;
  initialTop = parseInt(popupStyle.value.top, 10);
  initialLeft = parseInt(popupStyle.value.left, 10);
  document.addEventListener('mousemove', onDrag);
  document.addEventListener('mouseup', endDrag);
}

function onDrag(e) {
  if (!isDragging) return;
  const dx = e.clientX - startX;
  const dy = e.clientY - startY;
  popupStyle.value.top = `${initialTop + dy}px`;
  popupStyle.value.left = `${initialLeft + dx}px`;
}

function endDrag() {
  isDragging = false;
  document.removeEventListener('mousemove', onDrag);
  document.removeEventListener('mouseup', endDrag);
}
</script>

<style scoped>
.follow-popup-window {
  position: fixed;
  top: 100px;
  right: 20px;
  width: 400px;
  height: 350px;
  max-width: 95vw;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 12px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.5);
  z-index: 99999;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  resize: both;
}

@media (max-width: 768px) {
  .follow-popup-window {
    top: 80px !important;
    left: 2.5vw !important;
    right: auto !important;
    width: 95vw !important;
    height: 350px !important;
    resize: none;
  }
}

.follow-popup-header {
  height: 35px;
  background: var(--input-bg);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 10px;
  cursor: grab;
  user-select: none;
  border-bottom: 1px solid var(--border);
}

.follow-popup-header:active {
  cursor: grabbing;
}

.popup-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
}

.close-btn {
  background: transparent;
  border: none;
  color: var(--muted, #94a3b8);
  cursor: pointer;
  padding: 4px;
}
.close-btn:hover {
  color: #ef4444;
}

.follow-popup-toolbar {
  display: flex;
  gap: 15px;
  padding: 8px 10px;
  background: var(--input-bg);
  opacity: 0.9;
  font-size: 12px;
  color: var(--text);
  border-bottom: 1px solid var(--border);
}
.follow-popup-toolbar label {
  display: flex;
  align-items: center;
  gap: 5px;
  cursor: pointer;
}

.follow-popup-body {
  flex: 1;
  position: relative;
}

.mini-map-container {
  flex: 1;
  width: 100%;
  min-height: 200px;
  position: relative;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
}

.info-overlay {
  position: absolute;
  top: 10px;
  right: 10px;
  background: rgba(0,0,0,0.7);
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 11px;
  color: #fff;
  z-index: 400; /* Leaflet UI is around 400 */
}

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
