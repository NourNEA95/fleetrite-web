<template>
  <div v-if="false"></div>
</template>

<script setup>
import { watch, onUnmounted, onMounted, ref } from 'vue';
import L from 'leaflet';

const props = defineProps({
  map: { type: Object, required: true },
  vehicles: { type: Array, required: true },
  lat: { type: Number, required: true },
  lng: { type: Number, required: true },
  isSelected: { type: Boolean, default: false },
  iconType: { type: String, default: 'car' },
  statusColors: { type: Object, required: true },
  isDarkMode: { type: Boolean, default: false }
});

const emit = defineEmits(['select']);

let marker = null;
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

const createGroupPopup = (vehicles) => {
  const isDark = props.isDarkMode;
  // Generate a key to see if the state changed
  const stateKey = vehicles.map(v => `${v.imei}-${v.status}-${v.speed}`).join('|');
  
  const listHtml = vehicles.map(v => {
    const isOnline = v.status !== 'Offline';
    const isMoving = (v.speed || 0) > 0;
    const activeColor = '#10b981';
    const inactiveColor = isDark ? '#4b5563' : '#9ca3af';
    
    return `
      <div class="group-popup-row" data-imei="${v.imei}" style="cursor: pointer; padding: 6px 12px; border-bottom: 1px solid ${isDark ? 'rgba(255,255,255,0.06)' : '#f1f5f9'}; display: flex; align-items: center; justify-content: space-between; gap: 20px;">
        <div class="g-info-stacked" style="display: flex; flex-direction: column; gap: 1px; min-width: 100px;">
          <span class="g-name-mini" style="font-weight: 700; font-size: 11.5px; color: ${isDark ? '#f8fafc' : '#1e293b'}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${v.name}</span>
          <span class="g-speed-mini" style="font-size: 10.5px; color: ${isDark ? '#94a3b8' : '#64748b'};">${v.speed || 0} km/h</span>
        </div>
        <div class="g-icons-mini" style="display: flex; gap: 8px; align-items: center; flex-shrink: 0;">
          <svg width="18" height="16" viewBox="0 0 20 20" fill="${isMoving ? activeColor : inactiveColor}">
            <path d="M19 11v1h-1v-1h-2v-2h2v1h1v2zm-3-1v6h-1v-6h-3v4l-1 1H7l-1-1v-4H3v6h1v1H1v-1h1v-6h2V8h2V6h8v2h2v2h2v1h-2v-1z M14 8H6v6h8V8z"/>
          </svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="${isOnline ? activeColor : inactiveColor}">
            <path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/>
          </svg>
        </div>
      </div>
    `;
  }).join('');

  return {
    content: `
      <div class="custom-group-popup-compact ${isDark ? 'is-dark-popup' : 'is-light-popup'}" style="background: ${isDark ? '#1a1d21' : '#ffffff'};">
        <div class="group-mini-scroller" style="max-height: 220px; overflow-y: auto;">
          ${listHtml}
        </div>
      </div>
    `,
    stateKey
  };
};

const createGroupIcon = (count, color, heading = 0) => {
  const isSelected = props.isSelected;
  const iconHtml = `
    <div class="marker-container group-mode" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; position: relative;">
      ${isSelected ? `<div class="selection-glow" style="position: absolute; width: 44px; height: 44px; border-radius: 50%; background: rgba(54, 255, 180, 0.4); filter: blur(10px); animation: pulse-glow 2s infinite;"></div>` : ''}
      <svg width="34" height="34" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg" style="transform: rotate(${heading}deg); filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
        <rect x="9" y="3" width="18" height="30" rx="6" fill="${color}" stroke="#1a1a1a" stroke-width="1.5"/>
        <rect x="11" y="11" width="14" height="13" rx="3" fill="rgba(255,255,255,0.15)"/>
        <path d="M 11 9 L 25 9 L 23 12 L 13 12 Z" fill="#1a1a1a"/>
        <path d="M 13 23 L 23 23 L 25 26 L 11 26 Z" fill="#1a1a1a"/>
      </svg>
    </div>
  `;
  return {
    icon: L.divIcon({
      className: 'custom-group-marker',
      html: iconHtml,
      iconSize: [44, 44],
      iconAnchor: [22, 22]
    }),
    html: iconHtml
  };
};

const requestUpdate = () => {
  if (updateRequested || !props.map) return;
  updateRequested = true;
  thisRaf = requestAnimationFrame(updateMarker);
};

const updateMarker = () => {
  updateRequested = false;
  if (!props.map || props.vehicles.length === 0 || !props.map._loaded) return;
  
  // CRITICAL: Block all updates during map animations to prevent Leaflet projection crashes
  if (props.map._animating || (props.map._zoomAnim && props.map._zoomAnim._animating)) {
    requestUpdate(); // Queue for next frame if still animating
    return;
  }

  try {
    const firstV = props.vehicles[0];
    const color = props.statusColors[firstV.status] || props.statusColors.Offline;
    const { icon, html: iconHtml } = createGroupIcon(props.vehicles.length, color, firstV.heading);
    const { content: popupHtml, stateKey: popupKey } = createGroupPopup(props.vehicles);

    if (!marker) {
      marker = L.marker([props.lat, props.lng], { icon })
        .addTo(props.map)
        .on('mouseover', () => {
           if (marker && marker._map) marker.openPopup();
        })
        .on('click', () => {
          if (!props.map) return;
          const currentZoom = props.map.getZoom();
          const nextZoom = Math.min(currentZoom + 3, 19);
          props.map.flyTo([props.lat, props.lng], nextZoom);
        })
        .on('popupopen', (e) => {
          const popupEl = e.popup._contentNode;
          const items = popupEl.querySelectorAll('.group-popup-row');
          items.forEach(item => {
            item.addEventListener('click', () => {
              const imei = item.getAttribute('data-imei');
              const vehicle = props.vehicles.find(v => v.imei === imei);
              if (vehicle) emit('select', vehicle);
            });
          });
        });

      marker.bindPopup(popupHtml, {
        className: 'group-compact-popup',
        maxWidth: 250,
        minWidth: 180,
        closeButton: true
      });
      lastIconContent = iconHtml;
      lastPopupContent = popupKey;
    } else {
      // Defensive check: Leaflet markers can lose their map reference during rapid unmounts
      if (!marker._map || !props.map.hasLayer(marker)) {
        marker.addTo(props.map);
      }
      
      marker.setLatLng([props.lat, props.lng]);
      
      if (lastIconContent !== iconHtml) {
        marker.setIcon(icon);
        lastIconContent = iconHtml;
      }
      
      if (lastPopupContent !== popupKey) {
        const popup = marker.getPopup();
        if (popup) {
          popup.setContent(popupHtml);
        }
        lastPopupContent = popupKey;
      }
    }
  } catch (e) {
    // Silently handle projection errors during map state transitions
    console.debug('Marker update deferred due to map state', e);
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

watch(() => [props.lat, props.lng, props.vehicles, props.isSelected, props.isDarkMode], () => {
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
      marker.off(); // Remove all listeners
      props.map.removeLayer(marker);
    } catch(e) {}
  }
  marker = null;
});
</script>

<style>
.group-compact-popup .leaflet-popup-content-wrapper {
  padding: 0;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important;
}
.group-compact-popup .leaflet-popup-content {
  margin: 0;
}
.group-compact-popup .leaflet-popup-tip {
  display: none; /* Cleaner look without tip for compact popup */
}
.group-popup-row:hover {
  background: rgba(0,0,0,0.03);
}
.is-dark-popup .group-popup-row:hover {
  background: rgba(255,255,255,0.04);
}
.group-mini-scroller::-webkit-scrollbar {
  width: 4px;
}
.group-mini-scroller::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
@keyframes pulse-glow {
  0% { transform: scale(1); opacity: 0.3; }
  50% { transform: scale(1.1); opacity: 0.5; }
  100% { transform: scale(1); opacity: 0.3; }
}
</style>
