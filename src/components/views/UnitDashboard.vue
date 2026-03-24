<template>
  <div class="premium-dashboard custom-scrollbar" @mousemove="handleMouseMove">
    <!-- Navbar / Header -->
    <header class="dash-nav">
      <div class="nav-left">
        <button class="back-btn" @click="$router.push('/tracking')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
        <div class="title-group">
          <h1>{{ unitData?.name || 'Unit Dashboard' }}</h1>
          <span class="imei-badge">{{ imei }}</span>
        </div>
      </div>
      <div class="nav-right">
        <div class="status-indicator" :class="unitData?.status.toLowerCase()">
          <span class="pulse"></span>
          {{ unitData?.status || 'Loading...' }}
        </div>
        <button class="refresh-btn" @click="fetchData" :class="{ rotating: loading }">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"></path></svg>
        </button>
      </div>
    </header>

    <div class="main-layout" v-if="unitData">
      <!-- SIDEBAR: STATIC INFO -->
      <aside class="sidebar-panel">
        <div class="glass-card profile-card">
          <div class="car-avatar-premium">
            <div class="glow-bg-animated"></div>
            <img :src="'/car_premium.png?v=' + Date.now()" alt="Vehicle" class="car-img" />
          </div>
          
          <h2 class="vehicle-card-name">{{ unitData.name }}</h2>
          

          <div class="quick-stats-grid">
            <div class="qs-item">
              <label>Model</label>
              <span>{{ unitData.profile.brand }} {{ unitData.profile.model }}</span>
            </div>
            <div class="qs-item">
              <label>Color</label>
              <span>{{ unitData.profile.color }}</span>
            </div>
            <div class="qs-item">
              <label>Odometer</label>
              <span class="highlight">{{ unitData.odometer.toLocaleString() }} <small>km</small></span>
            </div>
            <div class="qs-item">
              <label>Engine Hours</label>
              <span>{{ unitData.engine_hours }} <small>h</small></span>
            </div>
          </div>

          <div class="ignition-status-card" :class="unitData.ignition.toLowerCase()">
            <div class="ign-header">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12,2A10,10,0,1,0,22,12,10,10,0,0,0,12,2Zm1,15H11V11h2Zm0-8H11V7h2Z"/></svg>
              Ignition {{ unitData.ignition }}
            </div>
            <p>{{ unitData.ignition_message }}</p>
          </div>
          
          <div class="driver-info">
             <div class="avatar-mini">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
             </div>
             <div class="d-text">
                <label>Current Driver</label>
                <span>{{ unitData.profile.driver }}</span>
             </div>
          </div>
        </div>

        <div class="glass-card battery-card">
           <div class="card-header">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="16" height="10" rx="2" ry="2"></rect><line x1="22" y1="11" x2="22" y2="13"></line></svg>
              Battery Levels
           </div>
           <div class="battery-stats">
              <div class="b-item">
                 <label>Today</label>
                 <span class="v-blue">{{ unitData.battery.today }}V</span>
              </div>
               <div class="b-item">
                 <label>Week</label>
                 <span class="v-orange">{{ unitData.battery.week }}V</span>
              </div>
               <div class="b-item">
                 <label>Month</label>
                 <span class="v-red">{{ unitData.battery.month }}V</span>
              </div>
           </div>
        </div>
      </aside>

      <!-- CONTENT AREA -->
      <main class="content-panel">
        
        <!-- ROW 1: CORE METRICS (REORDERABLE) -->
        <transition-group name="flip-list" tag="div" class="metrics-grid">
           <div v-for="(metric, idx) in metrics" :key="metric.id" 
                class="glass-card metric-box animate-in draggable-item" 
                :style="`--delay: ${(idx + 1) * 0.1}s`"
                draggable="true"
                @dragstart="handleDragStart(idx)"
                @dragover.prevent
                @drop="handleDrop(idx)">
              <div :class="['icon-wrap', metric.colorClass]" v-html="metric.svg"></div>
              <div class="m-data">
                 <label>{{ metric.label }}</label>
                 <div class="val" v-html="metric.value"></div>
              </div>
           </div>
        </transition-group>

        <!-- ROW 2: MAP & CHARTS -->
        <div class="grid-2">
           <!-- MAP CARD -->
           <div class="glass-card map-container">
              <div class="card-header">
                 <span>Current Location</span>
                 <a :href="`https://www.google.com/maps?q=${unitData.lat},${unitData.lng}`" target="_blank" class="external-link">View in Google Maps</a>
              </div>
              <div id="dashboardMap" class="small-map"></div>
              <div class="map-footer">
                 <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                 Last Position: {{ unitData.dt_tracker }}
              </div>
           </div>

           <!-- ALERTS CHART SECTION -->
           <div class="glass-card chart-card">
              <div class="card-header">Efficiency & Usage</div>
              <div class="chart-content">
                 <div class="usage-stats">
                    <div class="u-box">
                       <label>Weekly Moves</label>
                       <div class="u-val">{{ unitData.events.week }} <small>Events</small></div>
                    </div>
                    <div class="u-box">
                       <label>Daily Avg Move</label>
                       <div class="u-val">{{ unitData.events.avg }} <small>h</small></div>
                    </div>
                 </div>
                 
                 <!-- Custom SVG Chart for Distance -->
                 <div class="svg-chart-wrap">
                    <label>Daily Distance (km)</label>
                    <div class="bar-chart">
                       <div v-for="day in unitData.daily_stats" :key="day.date" class="chart-col">
                          <div class="bar-bg">
                             <div class="bar-fill" :style="{ height: ((parseFloat(day.distance) / (maxDist || 1)) * 85) + '%' }">
                                <span class="static-label">{{ Math.round(day.distance) }}</span>
                                <span class="tip">{{ day.distance }} km</span>
                             </div>
                          </div>
                          <span class="day-lbl">{{ day.day }}</span>
                       </div>
                    </div>
                 </div>
              </div>
           </div>
        </div>

        <!-- ROW 3: DETAILED TABLES / LISTS -->
        <div class="grid-detailed">
           <div class="glass-card overflow-hidden">
              <div class="card-header">Detailed Vehicle Statistics</div>
              <div class="details-table-wrap">
                 <table class="details-table">
                    <thead>
                       <tr>
                          <th>Metric</th>
                          <th>Value</th>
                       </tr>
                    </thead>
                    <tbody>
                       <tr>
                          <td>Vehicle Odometer</td>
                          <td class="bold">{{ unitData.odometer.toLocaleString() }} km</td>
                       </tr>
                       <tr>
                          <td>Total Engine Hours</td>
                          <td>{{ unitData.engine_hours }} hours</td>
                       </tr>
                       <tr>
                          <td>Top Speed (7d)</td>
                          <td>{{ unitData.stats.top_speed_7 }} km/h</td>
                       </tr>
                       <tr>
                          <td>Average Speed (7d)</td>
                          <td>{{ unitData.stats.avg_speed_7 }} km/h</td>
                       </tr>
                       <tr>
                          <td>Plate Number</td>
                          <td>{{ displayPlateFallback }}</td>
                       </tr>
                        <tr>
                          <td>VIN</td>
                          <td>{{ unitData.profile.vin }}</td>
                       </tr>
                    </tbody>
                 </table>
              </div>
           </div>

           <div class="glass-card overflow-hidden">
              <div class="card-header">Security & Ovespeeding</div>
              <div class="speed-grid">
                 <div class="speed-box">
                    <label>Daily Limit</label>
                    <span class="limit">80 km/h</span>
                 </div>
                 <div class="speed-box highlight">
                    <label>7-Day Violations</label>
                    <span class="violation">{{ unitData.events.week }}</span>
                 </div>
              </div>
              
              <!-- OverSpeeding Chart -->
              <div class="svg-chart-wrap compact">
                 <label>Violations Count</label>
                 <div class="bar-chart">
                    <div v-for="day in unitData.daily_stats" :key="day.date" class="chart-col">
                       <div class="bar-bg">
                          <div class="bar-fill red-fill" :style="{ height: ((parseInt(day.overspeed_count) / (maxOS || 1)) * 85) + '%' }">
                             <span class="static-label">{{ day.overspeed_count }}</span>
                             <span class="tip">{{ day.overspeed_count }} events</span>
                          </div>
                       </div>
                       <span class="day-lbl">{{ day.day }}</span>
                    </div>
                 </div>
              </div>
           </div>
        </div>

      </main>
    </div>

    <!-- LOADING STATE -->
    <div v-else class="loading-overlay">
       <div class="loader"></div>
       <p>Calculating Analytics...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../services/api';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const route = useRoute();
const imei = route.params.imei;
const unitData = ref(null);
const loading = ref(false);
let map = null;
let marker = null;

const metrics = ref([]);
let draggedIdx = null;

function handleDragStart(idx) {
  draggedIdx = idx;
}

function handleDrop(idx) {
  const item = metrics.value.splice(draggedIdx, 1)[0];
  metrics.value.splice(idx, 0, item);
  draggedIdx = null;
}

function initMetrics() {
  if (!unitData.value) return;
  metrics.value = [
    { id: 'dist', label: '7-Day Distance', value: `${unitData.value.stats.distance_7} <small>km</small>`, colorClass: 'bg-blue', svg: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"></path><circle cx="12" cy="10" r="3"></circle></svg>' },
    { id: 'speed', label: '7-Day Avg Speed', value: `${unitData.value.stats.avg_speed_7} <small>km/h</small>`, colorClass: 'bg-orange', svg: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>' },
    { id: 'alerts', label: 'Alerts Today', value: unitData.value.events.today, colorClass: 'bg-red', svg: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>' },
    { id: 'status', label: 'Device Status', value: `<span class="status-text">${unitData.value.status}</span>`, colorClass: 'bg-green', svg: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>' }
  ];
}

const maxDist = computed(() => {
   if (!unitData.value?.daily_stats?.length) return 100;
   const distances = unitData.value.daily_stats.map(d => parseFloat(d.distance) || 0);
   return Math.max(...distances) || 1;
});

const maxOS = computed(() => {
   if (!unitData.value?.daily_stats?.length) return 10;
   const counts = unitData.value.daily_stats.map(d => parseInt(d.overspeed_count) || 0);
   return Math.max(...counts) || 1;
});

function handleMouseMove(e) {
  const cards = document.querySelectorAll('.glass-card');
  cards.forEach(card => {
    const rect = card.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    card.style.setProperty('--mouse-x', `${x}px`);
    card.style.setProperty('--mouse-y', `${y}px`);
  });
}

const displayPlateFallback = computed(() => {
  if (!unitData.value) return '---';
  return (unitData.value.profile.registration && unitData.value.profile.registration !== 'N/A') 
         ? unitData.value.profile.registration 
         : unitData.value.name;
});

async function fetchData() {
  loading.value = true;
  try {
    const res = await api.get(`/api/dashboard/unit-stats/${imei}`);
    if (res.data.ok) {
      unitData.value = res.data.data;
      initMetrics();
      setTimeout(() => {
        initMap();
        updateMap();
      }, 500);
    }
  } catch (err) {
    console.error('Error fetching dashboard stats:', err);
  } finally {
    loading.value = false;
  }
}

function initMap() {
  const mapEl = document.getElementById('dashboardMap');
  if (!mapEl || map) return;
  
  map = L.map('dashboardMap', {
    zoomControl: false,
    attributionControl: false
  }).setView([0,0], 2);

  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_labels_under/{z}/{x}/{y}{r}.png').addTo(map);
  
  setTimeout(() => { if(map) map.invalidateSize(); }, 500);
}

function updateMap() {
  if (!unitData.value || !map) return;
  const lat = parseFloat(unitData.value.lat);
  const lng = parseFloat(unitData.value.lng);
  if (isNaN(lat) || isNaN(lng)) return;
  
  const pos = [lat, lng];
  map.setView(pos, 15);
  
  if (marker) map.removeLayer(marker);
  
  // Primary Marker
  marker = L.circleMarker(pos, {
    radius: 8,
    fillColor: "#3b82f6",
    color: "#fff",
    weight: 3,
    opacity: 1,
    fillOpacity: 1,
    className: 'map-marker-pulse'
  }).addTo(map);
  
  setTimeout(() => { if(map) map.invalidateSize(); }, 200);
}

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
.premium-dashboard {
  min-height: 100vh;
  width: 100vw;
  background: radial-gradient(circle at top left, #1a2235, #0a0e17);
  color: #e2e8f0;
  display: flex;
  flex-direction: column;
  font-family: 'Inter', sans-serif;
}

/* NAVBAR */
.dash-nav {
  height: 70px;
  background: rgba(15, 23, 42, 0.8);
  backdrop-filter: blur(15px);
  border-bottom: 1px solid rgba(255,255,255,0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 24px;
  z-index: 100;
  flex-shrink: 0;
}

.nav-left { display: flex; align-items: center; gap: 20px; }
.back-btn {
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  width: 40px;
  height: 40px;
  border-radius: 12px;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
}
.back-btn:hover { background: #3b82f6; border-color: #3b82f6; transform: translateX(-3px); }

.title-group h1 { 
  font-size: 22px; 
  font-weight: 900; 
  margin: 0; 
  background: linear-gradient(135deg, #ffffff 30%, #3b82f6 100%); 
  -webkit-background-clip: text; 
  background-clip: text;
  -webkit-text-fill-color: transparent; 
  letter-spacing: -0.5px;
  filter: drop-shadow(0 4px 10px rgba(59, 130, 246, 0.2));
}
.vehicle-card-name {
  font-size: 24px;
  font-weight: 900;
  margin: -10px 0 20px 0;
  background: linear-gradient(135deg, #ffffff 30%, #3b82f6 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  text-align: center;
  filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
}
.imei-badge { font-size: 11px; font-weight: 600; color: #475569; background: #0f172a; padding: 2px 8px; border-radius: 4px; border: 1px solid #1e293b; }

.nav-right { display: flex; align-items: center; gap: 15px; }
.status-indicator {
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255,255,255,0.05);
}
.status-indicator.moving { color: #10b981; }
.status-indicator.stopped { color: #f59e0b; }
.status-indicator.offline { color: #ef4444; }

.pulse { width: 8px; height: 8px; border-radius: 50%; background: currentColor; box-shadow: 0 0 10px currentColor; animation: pulse 1.5s infinite; }
@keyframes pulse { 0% { opacity: 0.4; } 50% { opacity: 1; } 100% { opacity: 0.4; } }

.refresh-btn {
  background: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  transition: color 0.3s;
}
.refresh-btn:hover { color: #fff; }
.rotating { animation: rotate 1s linear infinite; }
@keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* LAYOUT */
.main-layout {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 24px;
  padding: 24px;
  flex: 1;
}

/* SIDEBAR PANEL */
.sidebar-panel { display: flex; flex-direction: column; gap: 20px; overflow-y: auto; padding-right: 4px; }

.glass-card {
  background: rgba(30, 41, 59, 0.4);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
  position: relative;
  overflow: visible;
}
.glass-card:hover {
  background: rgba(30, 41, 59, 0.6);
  border-color: rgba(59, 130, 246, 0.5);
  transform: translateY(-8px) scale(1.02);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 15px rgba(59, 130, 246, 0.3);
  z-index: 10;
}

.profile-card { text-align: center; position: relative; }
.car-avatar-premium { position: relative; margin-bottom: 25px; height: 180px; display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 12px; background: rgba(0,0,0,0.2); }
.car-img { width: 240px; position: relative; z-index: 2; mix-blend-mode: multiply; filter: brightness(1.6) contrast(1.2) saturate(1.2); transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.car-img:hover { transform: scale(1.1) rotate(-2deg); }

.glow-bg-animated { 
  position: absolute; width: 130px; height: 130px; 
  background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%); 
  filter: blur(40px); animation: pulse-glow 3s infinite alternate; 
}
@keyframes pulse-glow { from { opacity: 0.3; transform: scale(0.8); } to { opacity: 0.8; transform: scale(1.3); } }

.plate-box { margin-bottom: 24px; }
.plate-inner {
  display: inline-flex;
  align-items: center;
  background: #fff;
  color: #000;
  border-radius: 6px;
  overflow: hidden;
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
  border: 2px solid #000;
}
.plate-inner .country { background: #3b82f6; color: #fff; padding: 10px 8px; font-size: 10px; font-weight: 900; }
.plate-inner .number { padding: 8px 15px; font-size: 20px; font-weight: 900; letter-spacing: 2px; }

.quick-stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; text-align: left; margin-bottom: 20px; }
.qs-item label { display: block; font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; }
.qs-item span { font-size: 12px; font-weight: 600; color: #e2e8f0; }
.qs-item .highlight { color: #3b82f6; font-size: 14px; font-weight: 800; }

.ignition-status-card {
  padding: 12px;
  border-radius: 12px;
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.2);
  margin-bottom: 20px;
  text-align: left;
}
.ignition-status-card.off { background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2); }
.ign-header { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 800; color: #10b981; margin-bottom: 4px; }
.off .ign-header { color: #ef4444; }
.ignition-status-card p { font-size: 11px; color: #94a3b8; margin: 0; }

.driver-info { display: flex; align-items: center; gap: 12px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.05); text-align: left; }
.avatar-mini { width: 32px; height: 32px; border-radius: 50%; background: #3b82f6; display: flex; align-items: center; justify-content: center; color: #fff; }
.d-text label { display: block; font-size: 10px; color: #64748b; }
.d-text span { font-size: 13px; font-weight: 700; color: #f8fafc; }

.battery-stats { display: flex; justify-content: space-between; margin-top: 15px; }
.b-item { text-align: center; }
.b-item label { display: block; font-size: 10px; color: #64748b; margin-bottom: 5px; }
.b-item span { font-size: 16px; font-weight: 800; }
.v-blue { color: #3b82f6; } .v-orange { color: #f59e0b; } .v-red { color: #ef4444; }

/* CONTENT PANEL */
.content-panel { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 24px; padding-right: 10px; }

.metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.metric-box { display: flex; align-items: center; gap: 20px; }
.icon-wrap { width: 50px; height: 50px; border-radius: 15px; display: flex; align-items: center; justify-content: center; color: #fff; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
.bg-blue { background: linear-gradient(135deg, #3b82f6, #1e40af); }
.bg-orange { background: linear-gradient(135deg, #f59e0b, #9a3412); }
.bg-red { background: linear-gradient(135deg, #ef4444, #7f1d1d); }
.bg-green { background: linear-gradient(135deg, #10b981, #064e3b); }

.m-data label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; }
.m-data .val { font-size: 22px; font-weight: 900; color: #fff; }
.m-data .status-text { font-size: 18px; color: #10b981; }

.grid-2 { display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px; }

.map-container { display: flex; flex-direction: column; padding: 0 !important; }
.card-header { padding: 15px 20px; font-size: 14px; font-weight: 800; color: #fff; display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); }
.external-link { font-size: 11px; color: #3b82f6; text-decoration: none; font-weight: 600; }
.small-map { flex: 1; min-height: 280px; }
.map-footer { padding: 8px 15px; font-size: 10px; color: #64748b; display: flex; align-items: center; gap: 6px; }

.chart-card { display: flex; flex-direction: column; }
.usage-stats { display: flex; gap: 20px; margin-bottom: 30px; }
.u-box { flex: 1; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 15px; text-align: center; }
.u-box label { font-size: 11px; color: #64748b; }
.u-val { font-size: 20px; font-weight: 800; color: #3b82f6; }

.svg-chart-wrap { margin-top: auto; }
.svg-chart-wrap label { font-size: 12px; font-weight: 700; color: #94a3b8; display: block; margin-bottom: 15px; }
.bar-chart { height: 140px; display: flex; align-items: flex-end; gap: 10px; padding-top: 25px; }
.chart-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px; height: 100%; }
.bar-bg { width: 100%; height: 100%; background: rgba(255,255,255,0.03); border-radius: 6px; position: relative; }
.bar-fill { position: absolute; bottom: 0; left: 0; width: 100%; background: linear-gradient(to top, #3b82f6, #60a5fa); border-radius: 6px; transition: height 1s cubic-bezier(0.175, 0.885, 0.32, 1.275); display: flex; justify-content: center; }
.red-fill { background: linear-gradient(to top, #ef4444, #f87171); }

.static-label {
  position: absolute;
  top: -24px;
  font-size: 11px;
  font-weight: 800;
  color: #fff;
  background: rgba(15, 23, 42, 0.6);
  padding: 2px 6px;
  border-radius: 4px;
  pointer-events: none;
  border: 1px solid rgba(255,255,255,0.05);
  transition: opacity 0.3s;
}
.bar-fill:hover .static-label { opacity: 0; }
.red-fill .static-label { color: #fca5a5; }

.tip { 
  position: absolute; 
  top: -24px; 
  left: 50%; 
  transform: translateX(-50%); 
  font-size: 11px; 
  font-weight: 900;
  opacity: 0; 
  transition: all 0.3s; 
  color: #fff; 
  background: #3b82f6;
  padding: 2px 8px;
  border-radius: 6px;
  white-space: nowrap;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}
.red-fill .tip { background: #ef4444; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4); }
.bar-fill:hover .tip { opacity: 1; transform: translateX(-50%) translateY(-5px); }
.bar-fill:hover { transform: scaleX(1.1); filter: brightness(1.2); }
.day-lbl { font-size: 10px; color: #475569; font-weight: 800; text-transform: uppercase; }

.grid-detailed { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
.details-table-wrap { padding: 10px; }
.details-table { width: 100%; border-collapse: collapse; }
.details-table th { text-align: left; padding: 12px; font-size: 12px; color: #64748b; border-bottom: 1px solid rgba(255,255,255,0.05); }
.details-table td { padding: 12px; font-size: 13px; border-bottom: 1px solid rgba(255,255,255,0.02); }
.bold { font-weight: 800; color: #3b82f6; }

.speed-grid { display: flex; gap: 20px; padding: 20px; }
.speed-box { flex: 1; text-align: center; padding: 15px; border-radius: 12px; background: rgba(0,0,0,0.2); }
.speed-box label { font-size: 11px; color: #64748b; }
.speed-box span { display: block; font-size: 24px; font-weight: 900; }
.speed-box.highlight span { color: #ef4444; }

.loading-overlay { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px; }
.loader { width: 40px; height: 40px; border: 4px solid rgba(255,255,255,0.1); border-top-color: #3b82f6; border-radius: 50%; animation: rotate 1s infinite linear; }

.animate-in { opacity: 0; transform: translateY(20px); animation: fadeInUp 0.5s forwards; animation-delay: var(--delay); }
@keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

/* Sleek Custom Scrollbar */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.5); }
::-webkit-scrollbar-thumb { 
  background: linear-gradient(to bottom, #3b82f6, #1e40af); 
  border-radius: 10px; 
  border: 1px solid rgba(255,255,255,0.05);
}
::-webkit-scrollbar-thumb:hover { background: #60a5fa; }

.custom-scrollbar { scroll-behavior: smooth; }

.custom-pulsing-marker {
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: visible !important;
}
.p-marker {
  width: 14px;
  height: 14px;
  background: #3b82f6;
  border: 4px solid #fff;
  border-radius: 50%;
  box-shadow: 0 0 20px #3b82f6;
  animation: pulse-marker 1.5s infinite;
}
.map-marker-pulse {
  filter: drop-shadow(0 0 10px #3b82f6);
  animation: map-pulse 1.5s infinite;
}
/* flip-list transition */
.flip-list-move {
  transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.draggable-item {
  cursor: grab;
}
.draggable-item:active {
  cursor: grabbing;
}

@keyframes map-pulse {
  0% { stroke-width: 3; stroke-opacity: 1; }
  50% { stroke-width: 15; stroke-opacity: 0; }
  100% { stroke-width: 3; stroke-opacity: 0; }
}

.metric-box label { font-size: 13px; color: #94a3b8; font-weight: 600; display: block; margin-bottom: 5px; }
.metric-box .val { font-size: 26px; font-weight: 900; color: #fff; letter-spacing: -0.5px; }

/* MOBILE RESPONSIVENESS */
@media (max-width: 1024px) {
  .main-layout {
    grid-template-columns: 1fr;
  }
  
  .sidebar-panel {
    overflow-y: visible;
  }
  
  .metrics-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .dash-nav {
    padding: 0 12px;
    height: 60px;
  }
  
  .title-group h1 {
    font-size: 16px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
  }
  
  .imei-badge {
    font-size: 9px;
    padding: 1px 4px;
  }
  
  .nav-left {
    gap: 8px;
  }
  
  .main-layout {
    padding: 12px;
    gap: 12px;
  }
  
  .grid-2, .grid-detailed {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .metrics-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .metric-box {
    padding: 12px;
  }
  
  .m-data .val {
    font-size: 18px;
  }
  
  .small-map {
    min-height: 220px;
  }

  .usage-stats {
    flex-direction: column;
    gap: 10px;
  }

  .car-img {
    width: 200px;
  }

  .car-avatar-premium {
    height: 140px;
  }
  
  .status-indicator {
    padding: 4px 10px;
    font-size: 10px;
  }
}
</style>
