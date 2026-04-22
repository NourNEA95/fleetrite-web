<template>
  <!-- Managed by Tracking.vue -->
  <div v-if="false"></div>
</template>

<script setup>
import { onMounted, onUnmounted, watch } from 'vue';
import L from 'leaflet';
import api from '../../services/api';

const props = defineProps({
  map: { type: Object, required: true },
  visible: { type: Boolean, default: false }
});

const zoneLayers = [];
let zonesData = [];

const parseVertices = (verticesStr) => {
  if (!verticesStr) return [];
  const coords = verticesStr.split(',').map(Number);
  const latLngs = [];
  for (let i = 0; i < coords.length; i += 2) {
    if (coords[i] !== undefined && coords[i+1] !== undefined) {
      latLngs.push([coords[i], coords[i+1]]);
    }
  }
  return latLngs;
};

const clearZones = () => {
  zoneLayers.forEach(layer => props.map.removeLayer(layer));
  zoneLayers.length = 0;
};

const renderZones = () => {
  clearZones();
  if (!props.visible) return;

  zonesData.forEach(zone => {
    if (zone.zone_visible === 'false') return;

    const latLngs = parseVertices(zone.zone_vertices);
    if (latLngs.length === 0) return;

    const polygon = L.polygon(latLngs, {
      color: zone.zone_color || '#3388ff',
      weight: 2,
      opacity: 0.8,
      fillOpacity: 0.2
    }).addTo(props.map);

    // Add Label (Tooltip)
    polygon.bindTooltip(zone.zone_name, {
      permanent: true,
      direction: 'center',
      className: 'zone-label'
    });

    zoneLayers.push(polygon);
  });
};

const fetchZones = async () => {
  try {
    const response = await api.get('/api/tracking/zones');
    if (response.data.ok) {
      zonesData = response.data.data;
      renderZones();
    }
  } catch (error) {
    console.error('Failed to fetch zones:', error);
  }
};

onMounted(() => {
  fetchZones();
});

watch(() => props.visible, () => {
  renderZones();
});

onUnmounted(() => {
  clearZones();
});
</script>

<style>
.zone-label {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  color: white !important;
  font-weight: 600 !important;
  text-shadow: 0 1px 3px rgba(0,0,0,0.8) !important;
  pointer-events: none !important;
}
</style>
