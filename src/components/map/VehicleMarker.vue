<template>
  <div v-if="false"></div>
</template>

<script setup>
import { watch, onUnmounted, onMounted, ref } from 'vue';
import L from 'leaflet';

const props = defineProps({
  map: { type: Object, required: true },
  vehicle: { type: Object, required: true },
  isSelected: { type: Boolean, default: false },
  iconType: { type: String, default: 'car' },
  statusColors: { type: Object, required: true }
});

const emit = defineEmits(['select']);

let marker = null;
let tailLine = null;
const tailPoints = [];
const isZooming = ref(false);
let lastIconContent = null;
let lastPopupContent = null;
let updateRequested = false;
let thisRaf = null;

const onZoomStart = () => { isZooming.value = true; };
const onZoomEnd = () => {
  isZooming.value = false;
  requestUpdate();
};

const requestUpdate = () => {
  if (updateRequested || !props.map) return;
  updateRequested = true;
  thisRaf = requestAnimationFrame(updateMarker);
};

const createDetailedPopup = (v) => {
  return `
    <div class="premium-vehicle-popup">
      <div class="popup-header">
        <div class="header-main">
          <span class="vehicle-name">${v.name || 'Unknown'}</span>
          <div class="header-pills">
            <div class="pill driver-pill">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
              <span>${v.driver_name || 'No Driver'}</span>
            </div>
            <div class="close-popup-btn" onclick="document.querySelector('.leaflet-popup-close-button').click()">✕</div>
          </div>
        </div>
        <div class="details-pill">Vehicle Details</div>
      </div>

      <div class="popup-body">
        <div class="info-row">
          <div class="info-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg></div>
          <div class="info-content">
            <div class="info-label">Location</div>
            <div class="info-value address">${v.address || 'Locating...'}</div>
          </div>
        </div>

        <div class="info-row">
          <div class="info-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="7 13 12 18 17 13"></polyline><polyline points="7 6 12 11 17 6"></polyline></svg></div>
          <div class="info-content">
            <div class="info-label">KuwaitPosition</div>
            <div class="info-value">${v.lat.toFixed(6)}° , ${v.lng.toFixed(6)}°</div>
          </div>
        </div>

        <div class="info-row">
          <div class="info-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
          <div class="info-content">
            <div class="info-label">Speed</div>
            <div class="info-value">${v.speed || 0} km/h</div>
          </div>
        </div>

        <div class="info-row">
          <div class="info-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div>
          <div class="info-content">
            <div class="info-label">Time</div>
            <div class="info-value">${v.dt_tracker || '---'}</div>
          </div>
        </div>

        <div class="info-row">
          <div class="info-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div>
          <div class="info-content">
            <div class="info-label">Odometer</div>
            <div class="info-value">${v.odometer || 0} km</div>
          </div>
        </div>
      </div>
    </div>
  `;
};

const createCarIcon = (color, heading) => {
  const safeHeading = heading || 0;
  const isSelected = props.isSelected;
  const iconHtml = `
    <div class="marker-container" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; position: relative;">
      ${isSelected ? `
        <div class="selected-label-floating">
          ${props.vehicle.name} (${props.vehicle.speed || 0} km/h)
        </div>
      ` : ''}
      <svg width="36" height="36" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0; transform: rotate(${safeHeading}deg); transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
        <rect x="9" y="3" width="18" height="30" rx="6" fill="${color}" stroke="#1a1a1a" stroke-width="1.5"/>
        <rect x="11" y="11" width="14" height="13" rx="3" fill="rgba(255,255,255,0.15)"/>
        <path d="M 11 9 L 25 9 L 23 12 L 13 12 Z" fill="#1a1a1a"/>
        <path d="M 13 23 L 23 23 L 25 26 L 11 26 Z" fill="#1a1a1a"/>
        <rect x="10" y="3" width="4" height="2" rx="1" fill="#fff" opacity="0.8"/>
        <rect x="22" y="3" width="4" height="2" rx="1" fill="#fff" opacity="0.8"/>
        <rect x="10" y="31" width="5" height="2" rx="1" fill="#ff3b30" opacity="0.8"/>
        <rect x="21" y="31" width="5" height="2" rx="1" fill="#ff3b30" opacity="0.8"/>
      </svg>
    </div>
  `;
  return L.divIcon({
    className: 'custom-vehicle-marker',
    html: iconHtml,
    iconSize: [36, 36],
    iconAnchor: [18, 18]
  });
};

const createArrowIcon = (color, heading) => {
  const safeHeading = heading || 0;
  const isSelected = props.isSelected;
  const iconHtml = `
    <div class="marker-container" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; position: relative;">
      ${isSelected ? `
        <div class="selected-label-floating">
          ${props.vehicle.name} (${props.vehicle.speed || 0} km/h)
        </div>
      ` : ''}
      <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0; transform: rotate(${safeHeading}deg); transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4));">
        <path d="M16 2 L28 28 L16 22 L4 28 Z" fill="${color}" stroke="#fff" stroke-width="1.5" stroke-linejoin="round"/>
      </svg>
    </div>
  `;
  return L.divIcon({
    className: 'custom-vehicle-marker-arrow',
    html: iconHtml,
    iconSize: [32, 32],
    iconAnchor: [16, 16]
  });
};

const updateMarker = () => {
  updateRequested = false;
  if (!props.map || !props.vehicle || !props.map._loaded) return;
  
  // CRITICAL: Block all updates during map animations to prevent Leaflet projection crashes
  if (props.map._animating || (props.map._zoomAnim && props.map._zoomAnim._animating)) {
    requestUpdate(); // Queue for next frame if still animating
    return;
  }

  try {
    const color = props.statusColors[props.vehicle.status] || props.statusColors.Offline;
    const icon = props.iconType === 'arrow' 
      ? createArrowIcon(color, props.vehicle.heading) 
      : createCarIcon(color, props.vehicle.heading);
    const iconHtml = icon.options.html;
    const popupHtml = createDetailedPopup(props.vehicle);

    if (!marker) {
      marker = L.marker([props.vehicle.lat, props.vehicle.lng], { icon })
        .addTo(props.map)
        .on('click', () => emit('select', props.vehicle));
      
      marker.bindPopup(popupHtml, {
        className: 'detailed-vehicle-popup',
        maxWidth: 250
      });
      lastIconContent = iconHtml;
      lastPopupContent = popupHtml;
    } else {
      // Basic safety: ensure the marker still thinks it's on the map
      if (!marker._map || !props.map.hasLayer(marker)) {
         marker.addTo(props.map);
      }

      marker.setLatLng([props.vehicle.lat, props.vehicle.lng]);
      
      if (lastIconContent !== iconHtml) {
        marker.setIcon(icon);
        lastIconContent = iconHtml;
      }
      
      if (lastPopupContent !== popupHtml) {
        const popup = marker.getPopup();
        if (popup) popup.setContent(popupHtml);
        lastPopupContent = popupHtml;
      }
    }

    // Handle Tail
    if (props.isSelected) {
      const currentPoint = [props.vehicle.lat, props.vehicle.lng];
      if (tailPoints.length === 0 || 
          Math.abs(tailPoints[tailPoints.length - 1][0] - currentPoint[0]) > 0.00001 || 
          Math.abs(tailPoints[tailPoints.length - 1][1] - currentPoint[1]) > 0.00001) {
        tailPoints.push(currentPoint);
        if (tailPoints.length > 50) tailPoints.shift();
      }

      if (!tailLine) {
        tailLine = L.polyline(tailPoints, {
          color: '#36ffb4',
          weight: 3,
          opacity: 0.8,
          smoothFactor: 1,
          lineJoin: 'round'
        }).addTo(props.map);
      } else {
        if (!tailLine._map) tailLine.addTo(props.map);
        try {
          tailLine.setLatLngs(tailPoints);
        } catch(e) {}
      }
    } else if (tailLine) {
      if (props.map.hasLayer(tailLine)) {
        try {
          props.map.removeLayer(tailLine);
        } catch(e) {}
      }
      tailLine = null;
      tailPoints.length = 0;
    }
  } catch (e) {
    // Silently handle projection errors during map state transitions
    console.debug('Vehicle marker update deferred', e);
  }
};

onMounted(() => {
  if (props.map) {
    props.map.on('zoomstart', onZoomStart);
    props.map.on('zoomend', onZoomEnd);
    props.map.on('movestart', onZoomEnd);
    props.map.on('moveend', onZoomEnd);
  }
  requestUpdate();
});

watch(() => [props.vehicle, props.isSelected, props.iconType, props.statusColors], () => {
  requestUpdate();
}, { deep: true });

onUnmounted(() => {
  if (thisRaf) cancelAnimationFrame(thisRaf);
  if (props.map) {
    props.map.off('zoomstart', onZoomStart);
    props.map.off('zoomend', onZoomEnd);
    props.map.off('movestart', onZoomEnd);
    props.map.off('moveend', onZoomEnd);
  }
  if (marker && props.map) {
    try {
      marker.off();
      props.map.removeLayer(marker);
    } catch(e) {}
  }
  if (tailLine && props.map) {
    try {
      props.map.removeLayer(tailLine);
    } catch(e) {}
  }
  marker = null;
  tailLine = null;
});
</script>

<style>
.premium-vehicle-popup {
  background: #040712;
  color: #ffffff;
  min-width: 280px;
  border-radius: 12px;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
}

.popup-header {
  padding: 16px;
  background: rgba(255, 255, 255, 0.03);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.header-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.vehicle-name {
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -0.5px;
}

.header-pills {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pill {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
}

.driver-pill {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #fff;
  white-space: nowrap;
}

.details-pill {
  display: inline-block;
  padding: 6px 14px;
  background: #ffffff;
  color: #040712;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: opacity 0.2s;
}

.details-pill:hover {
  opacity: 0.9;
}

.close-popup-btn {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  cursor: pointer;
  font-size: 10px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.popup-body {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.info-icon {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  flex-shrink: 0;
}

.info-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.info-label {
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: capitalize;
}

.info-value {
  font-size: 13px;
  font-weight: 500;
  line-height: 1.4;
}

.info-value.address {
  font-size: 12px;
  color: #e2e8f0;
}

/* Leaflet Overrides */
.detailed-vehicle-popup .leaflet-popup-content-wrapper {
  background: transparent !important;
  box-shadow: 0 20px 50px rgba(0,0,0,0.5) !important;
  padding: 0;
  border: none !important;
}

.detailed-vehicle-popup .leaflet-popup-content {
  margin: 0;
  width: auto !important;
}

.detailed-vehicle-popup .leaflet-popup-tip {
  background: #040712 !important;
}

.detailed-vehicle-popup .leaflet-popup-close-button {
  display: none;
}

/* Floating Label for Selected Vehicle Marker */
.selected-label-floating {
  position: absolute;
  top: -28px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(255, 255, 255, 0.95);
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  border: 1px solid rgba(0,0,0,0.05);
  pointer-events: none;
  z-index: 1000;
}

body.dark-mode .selected-label-floating {
  background: rgba(30, 41, 59, 0.95);
  color: #f8fafc;
  border-color: rgba(255,255,255,0.1);
  box-shadow: 0 4px 12px rgba(0,0,0,0.4);
}
</style>
