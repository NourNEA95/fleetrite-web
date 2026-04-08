<template>
  <div 
    class="vehicle-details-panel" 
    v-if="selectedVehicle" 
    :class="{ 'light-theme': !themeStore.isDarkMode }"
    :style="{ height: panelHeight + 'px' }"
  >
    <!-- Resize Handle -->
    <div class="resize-handle" @mousedown="$emit('init-resize', $event)" @touchstart.passive="$emit('init-resize', $event)">
      <div class="handle-bar"></div>
    </div>

    <!-- Top Tabs Bar -->
    <div class="panel-tabs">
      <button :class="['tab-btn', { active: activeTab === 'data' }]" @click="activeTab = 'data'">Data</button>
      <button :class="['tab-btn', { active: activeTab === 'graph' }]" @click="activeTab = 'graph'">Graph</button>
      <button :class="['tab-btn', { active: activeTab === 'messages' }]" @click="activeTab = 'messages'">Messages</button>
      <button :class="['tab-btn', { active: activeTab === 'settings' }]" @click="activeTab = 'settings'">Settings</button>
      
      <div class="header-right-meta">
        <span class="v-name">{{ selectedVehicle.name }}</span>
        <span class="v-imei">{{ selectedVehicle.imei }}</span>
      </div>

      <button class="header-close-btn" @click="$emit('close')">
        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/></svg>
      </button>
    </div>

    <!-- Content Area -->
    <div class="panel-content scrollbar-custom">
      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
      </div>

      <div v-if="activeTab === 'data' && gridData" class="main-display-wrapper">
        <!-- Left: Key Data Grid (2 Columns) -->
        <div class="key-data-section">
          <div class="data-cols-wrapper">
            <!-- Data Col 1 -->
            <div class="data-col">
              <div class="detail-item"><span class="icon">👤</span><span class="label">Driver</span><span class="val">{{ gridData.driver.name }}</span></div>
              <div class="detail-item gray"><span class="icon">🪪</span><span class="label">Driver RFID</span><span class="val">{{ gridData.device.rfid }}</span></div>
              <div class="detail-item"><span class="icon">🚗</span><span class="label">Model</span><span class="val">{{ gridData.vehicle.model }}</span></div>
              <div class="detail-item gray"><span class="icon">📊</span><span class="label">Odometer</span><span class="val"><strong>{{ gridData.stats.total_odometer }} km</strong></span></div>
              <div class="detail-item"><span class="icon">🔢</span><span class="label">Plate</span><span class="val">{{ selectedVehicle.name }}</span></div>
              <div class="detail-item gray"><span class="icon">📱</span><span class="label">SIM card number</span><span class="val">{{ gridData.device.sim_number || '---' }}</span></div>
              <div class="detail-item"><span class="icon">📍</span><span class="label">Status</span><span class="val"><strong>{{ selectedVehicle.status }}</strong> ({{ gridData.sensors.status_time }})</span></div>
              <div class="detail-item gray"><span class="icon">🆔</span><span class="label">VIN</span><span class="val">{{ gridData.vehicle.vin }}</span></div>
              <div class="detail-item"><span class="icon">🏠</span><span class="label">Address</span><span class="val truncate" :title="gridData.location.address">{{ gridData.location.address || '---' }}</span></div>
              <div class="detail-item gray"><span class="icon">⛰️</span><span class="label">Altitude</span><span class="val">{{ gridData.location.altitude || '0' }} m</span></div>
              <div class="detail-item"><span class="icon">🧭</span><span class="label">Angle</span><span class="val">{{ gridData.location.angle || '0' }}°</span></div>
              <div class="detail-item gray"><span class="icon">🗺️</span><span class="label">Nearest zone</span><span class="val truncate">{{ gridData.location.near || '---' }}</span></div>
            </div>

            <!-- Data Col 2 -->
            <div class="data-col">
              <div class="detail-item"><span class="icon">📍</span><span class="label">Position</span><a :href="`https://www.google.com/maps?q=${gridData.location.lat},${gridData.location.lng}`" target="_blank" class="val blue-link" style="text-decoration: none;">{{ gridData.location.lat }} &deg;, {{ gridData.location.lng }} &deg;</a></div>
              <div class="detail-item gray"><span class="icon">🛣️</span><span class="label">road limit</span><span class="val">{{ gridData.device.speed_limit || '---' }} km/h</span></div>
              <div class="detail-item"><span class="icon">🚀</span><span class="label">Speed</span><span class="val"><strong>{{ gridData.location.speed || '0' }} km/h</strong></span></div>
              <div class="detail-item gray"><span class="icon">🕒</span><span class="label">Time (position)</span><span class="val">{{ gridData.device.last_date || '---' }}</span></div>
              <div class="detail-item"><span class="icon">🕒</span><span class="label">Time (server)</span><span class="val">{{ new Date().toLocaleString() }}</span></div>
              <div class="detail-item gray"><span class="icon">🔋</span><span class="label">Battery</span><span class="val">{{ gridData.sensors.battery }}</span></div>
              <div class="detail-item"><span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16" style="vertical-align: text-bottom; color: #6b7280;"><path d="M7,4V6H10V8H7L5,10V13H3V10H1V18H3V15H5V18H8L10,20H18V16H20V19H23V9H20V12H18V8L16,6H13V4H7Z" /></svg></span><span class="label">Ignition</span><span class="val"><strong>{{ gridData.sensors.ignition }}</strong></span></div>
              <div class="detail-item gray"><span class="icon">⌛</span><span class="label">Ignition time</span><span class="val">{{ gridData.sensors.ignition_time }}</span></div>
              <div class="detail-item"><span class="icon">🔌</span><span class="label">Main Power</span><span class="val">{{ gridData.sensors.power }}</span></div>
            </div>


          </div>
        </div>

        <!-- Right: Horizontal Scrolling Widgets -->
        <div class="widgets-horizontal-section scrollbar-custom-h">
          <!-- Widget: Driving Behavior -->
          <div class="pro-widget">
            <div class="pro-widget-header" style="background: #e2f1fa; color: #3f6f99;">
              <span class="icon" style="color: #4b1a43; font-weight: 800;">👤</span> Driving behavior
            </div>
            <div class="pro-widget-body scrollbar-custom" style="padding: 15px; margin: 0px; border: 1px solid var(--border-color); background: var(--panel-bg); border-top: none;">
              
              <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                <div style="flex: 1; background: #3f6f99; color: white; border-radius: 6px; padding: 12px; text-align: center;">
                  <div style="font-size: 13px; font-weight: 500;">Avg Speed</div>
                  <div style="font-size: 14px; margin-top: 2px;">{{ gridData.stats.avg_speed || 0 }} km/h</div>
                </div>
                <div style="flex: 1; background: #d32f2f; color: white; border-radius: 6px; padding: 12px; text-align: center;">
                   <div style="font-size: 13px; font-weight: 500;">MAX Speed</div>
                   <div style="font-size: 14px; margin-top: 2px;">{{ gridData.stats.top_speed || 0 }} km/h</div>
                </div>
              </div>

              <!-- Behaviors List -->
              <div style="border: 1px solid var(--border-color); border-radius: 6px; background: var(--panel-bg);">
                <div style="padding: 10px 15px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                  <span style="color: var(--text-dim);">Harsh Braking</span><span style="color: var(--text-main);">No</span>
                </div>
                <div style="padding: 10px 15px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                  <span style="color: var(--text-dim);">Sharp Cornering</span><span style="color: var(--text-main);">No</span>
                </div>
                <div style="padding: 10px 15px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                  <span style="color: var(--text-dim);">Harsh Acceleration</span><span style="color: var(--text-main);">No</span>
                </div>
                <div style="padding: 10px 15px; display: flex; justify-content: space-between;">
                  <span style="color: var(--text-dim);">Over Speeding</span><span style="color: var(--text-main);">No</span>
                </div>
              </div>

            </div>
          </div>

          <!-- Widget: Driver -->
          <div class="pro-widget">
            <div class="pro-widget-header" style="background: #e2f1fa; color: #3f6f99; font-weight: bold;">
              Driver
            </div>
            <div class="pro-widget-body scrollbar-custom" style="padding: 15px; margin: 0px; border: 1px solid var(--border-color); background: var(--panel-bg); border-top: none;">
              <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                  <span class="icon" style="color: #8b5cf6; font-size: 20px;"><svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/></svg></span>
                  <span style="color: var(--text-dim); font-size: 15px;">Driver</span>
                </div>
                <div style="text-align: right; font-weight: bold; font-size: 15px; color: var(--text-main); max-width: 65%;">
                  {{ gridData.driver.name }}
                </div>
              </div>
            </div>
          </div>

          <!-- Widget: Recent tasks -->
          <div class="pro-widget" style="min-width: 280px;">
            <div class="pro-widget-header" style="background: #e2f1fa; color: #3f6f99; font-weight: bold; display: flex; justify-content: space-between;">
              <span>Recent tasks</span>
              <span class="icon" style="color: #9ca3af; font-size: 14px;">🖊️</span>
            </div>
            <div class="pro-widget-body scrollbar-custom empty-widget-body">
              No tasks.
            </div>
          </div>

          <!-- Widget: Object control -->
          <div class="pro-widget" style="min-width: 280px;">
            <div class="pro-widget-header" style="background: #e2f1fa; color: #3f6f99; font-weight: bold; display: flex; justify-content: space-between;">
              <span>Object control</span>
              <span class="icon" style="color: #9ca3af; font-size: 14px;">🖊️</span>
            </div>
            <div class="pro-widget-body scrollbar-custom empty-widget-body">
              No tasks.
            </div>
          </div>

          <!-- Widget: vehicle information -->
          <div class="pro-widget" style="min-width: 320px;">
            <div class="pro-widget-header" style="background: #e2f1fa; color: #3f6f99; font-weight: bold;">
              <span style="color: #6b7280; font-size: 11px; margin-right: 8px;">001</span> vehicle information
            </div>
            <div class="pro-widget-body scrollbar-custom" style="padding: 0px; margin: 0px; border: 1px solid var(--border-color); background: var(--panel-bg); border-top: none; font-size: 13px;">
              <div style="padding: 10px 15px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                <span style="color: var(--text-dim);">Type</span><span style="color: var(--text-main);">{{ gridData.vehicle.type }}</span>
              </div>
              <div style="padding: 10px 15px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                <span style="color: var(--text-dim);">VIN</span><span style="color: var(--text-main);">{{ gridData.vehicle.vin }}</span>
              </div>
              <div style="padding: 10px 15px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                <span style="color: var(--text-dim);">Brand</span><span style="color: var(--text-main);">{{ gridData.vehicle.brand }}</span>
              </div>
              <div style="padding: 10px 15px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                <span style="color: var(--text-dim);">Model</span><span style="color: var(--text-main);">{{ gridData.vehicle.model }}</span>
              </div>
              <div style="padding: 10px 15px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between;">
                <span style="color: var(--text-dim);">Year</span><span style="color: var(--text-main);">{{ gridData.vehicle.year }}</span>
              </div>
              <div style="padding: 10px 15px; display: flex; justify-content: space-between;">
                <span style="color: var(--text-dim);">Color</span><span style="color: var(--text-main);">{{ gridData.vehicle.color }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Graph Tab -->
      <div v-if="activeTab === 'graph'" class="graph-tab-wrapper" style="height: 100%; display: flex; flex-direction: column;">
        <div class="graph-header" style="padding: 10px 15px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; background: var(--panel-bg);">
          <div style="display: flex; gap: 10px; align-items: center;">
            <select v-model="selectedGraphMetric" class="metric-select" style="padding: 5px 10px; border-radius: 4px; border: 1px solid var(--border-color); background: var(--panel-bg); color: var(--text-main);">
              <option value="speed">Speed</option>
              <option value="altitude">Altitude</option>
              <option value="angle">Angle</option>
              <option v-for="param in dynamicGraphParams" :key="param" :value="param">{{ param }}</option>
            </select>
            <div style="font-size: 11px; padding: 2px 6px; background: rgba(56,114,230,0.1); color: #3872e6; border-radius: 4px; border: 1px solid rgba(56,114,230,0.3); font-weight: bold;">
              {{ isHistoryPlaying ? '▶ Playing' : '⏸ Paused' }}
            </div>
          </div>
          <div class="graph-value-display" style="font-size: 13px; color: var(--text-main); font-weight: bold;">
            <span v-if="historyData.length > 0">{{ currentGraphValueLabel }} <span style="font-weight: normal; color: var(--text-dim); margin-left: 6px;">- {{ currentGraphTime }}</span></span>
            <span v-else style="color: var(--text-dim); font-weight: normal;">No history data available</span>
          </div>
        </div>
        <div class="graph-body" style="flex: 1; min-height: 200px; background: var(--panel-bg); padding: 10px 10px 0 10px;" @mouseleave="hoveredIndex = null">
          <v-chart 
            v-if="historyData.length > 0" 
            class="history-chart" 
            :option="chartOption" 
            autoresize 
            style="width: 100%; height: 100%;" 
            @updateAxisPointer="handleAxisPointer"
            @globalout="hoveredIndex = null"
          />
          <div v-else style="display: flex; height: 100%; align-items: center; justify-content: center; color: var(--text-dim); font-style: italic;">
            Please fetch history to view the graph.
          </div>
        </div>
      </div>

      <!-- Messages Tab -->
      <div v-if="activeTab === 'messages'" class="messages-tab-wrapper">
        <div class="messages-table-container scrollbar-custom">
          <table class="messages-table">
            <thead>
              <tr>
                <th style="width: 40px; text-align: center;">
                  <input type="checkbox" :checked="selectedMessageIds.length === messages.length && messages.length > 0" @change="toggleSelectAll" />
                </th>
                <th @click="toggleSort('dt_tracker')" style="cursor: pointer; user-select: none;">Time (position) <span style="font-size: 10px; opacity: 0.6; margin-left: 4px;">{{ sortIcon('dt_tracker') }}</span></th>
                <th @click="toggleSort('dt_server')" style="cursor: pointer; user-select: none;">Time (server) <span style="font-size: 10px; opacity: 0.6; margin-left: 4px;">{{ sortIcon('dt_server') }}</span></th>
                <th @click="toggleSort('lat')" style="cursor: pointer; user-select: none;">Latitude <span style="font-size: 10px; opacity: 0.6; margin-left: 4px;">{{ sortIcon('lat') }}</span></th>
                <th @click="toggleSort('lng')" style="cursor: pointer; user-select: none;">Longitude <span style="font-size: 10px; opacity: 0.6; margin-left: 4px;">{{ sortIcon('lng') }}</span></th>
                <th @click="toggleSort('altitude')" style="cursor: pointer; user-select: none;">Altitude <span style="font-size: 10px; opacity: 0.6; margin-left: 4px;">{{ sortIcon('altitude') }}</span></th>
                <th @click="toggleSort('angle')" style="cursor: pointer; user-select: none;">Angle <span style="font-size: 10px; opacity: 0.6; margin-left: 4px;">{{ sortIcon('angle') }}</span></th>
                <th @click="toggleSort('speed')" style="cursor: pointer; user-select: none;">Speed <span style="font-size: 10px; opacity: 0.6; margin-left: 4px;">{{ sortIcon('speed') }}</span></th>
                <th>Parameters</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="msg in messages" :key="msg.id">
                <td style="text-align: center;"><input type="checkbox" v-model="selectedMessageIds" :value="msg.id" /></td>
                <td>{{ msg.dt_tracker }}</td>
                <td>{{ msg.dt_server }}</td>
                <td>{{ msg.lat }}</td>
                <td>{{ msg.lng }}</td>
                <td>{{ msg.altitude }} m</td>
                <td>{{ msg.angle }}</td>
                <td>{{ msg.speed }} km/h</td>
                <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" :title="formatParams(msg.params)">{{ formatParams(msg.params) }}</td>
              </tr>
              <tr v-if="loadingMessages">
                <td colspan="9" style="text-align: center; padding: 20px;">
                   <div style="display: flex; justify-content: center; align-items: center; gap: 8px;">
                     <div class="spinner" style="width: 20px; height: 20px; border-width: 3px;"></div> Loading messages...
                   </div>
                </td>
              </tr>
              <tr v-else-if="messages.length === 0">
                <td colspan="9" style="text-align: center; padding: 20px; color: var(--text-dim); font-style: italic;">No messages found for this period.</td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="messages-footer">
          <div class="footer-left" style="display: flex; align-items: center; gap: 12px; position: relative;">
            <button class="footer-refresh-btn" title="Refresh" @click="fetchMessages">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M12,18A6,6 0 0,1 6,12C6,11 6.25,10.03 6.7,9.2L5.24,7.74C4.46,8.97 4,10.43 4,12A8,8 0 0,0 12,20V23L16,19L12,15M12,4V1L8,5L12,9V6A6,6 0 0,1 18,12C18,13 17.75,13.97 17.3,14.8L18.76,16.26C19.54,15.03 20,13.57 20,12A8,8 0 0,0 12,4Z"/></svg>
            </button>
            <span style="cursor: pointer; font-size: 16px; font-weight: bold; color: var(--text-dim); line-height: 1;" title="More Options" @click.stop="showMessagesActionMenu = !showMessagesActionMenu">...</span>
            
            <div v-show="showMessagesActionMenu" v-click-outside="() => showMessagesActionMenu = false" class="messages-action-menu">
              <button class="delete-btn" @click="deleteSelectedMessages">
                 <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/></svg>
                 Delete
              </button>
            </div>
          </div>
          <div class="pagination-controls">
            <button class="page-btn" @click="messagesPage = 1" :disabled="messagesPage === 1">|&lt;</button>
            <button class="page-btn" @click="messagesPage--" :disabled="messagesPage === 1">&lt;</button>
            <span class="page-info">Page <input type="number" v-model.lazy="messagesPage" class="page-input" /> of {{ Math.ceil(messagesTotal / messagesPerPage) || 1 }}</span>
            <button class="page-btn" @click="messagesPage++" :disabled="messagesPage >= Math.ceil(messagesTotal / messagesPerPage)">&gt;</button>
            <button class="page-btn" @click="messagesPage = Math.ceil(messagesTotal / messagesPerPage)" :disabled="messagesPage >= Math.ceil(messagesTotal / messagesPerPage)">&gt;|</button>
            <select class="page-size-select" v-model.number="messagesPerPage">
              <option value="25">25</option>
              <option value="50" selected>50</option>
              <option value="100">100</option>
              <option value="200">200</option>
              <option value="300">300</option>
              <option value="400">400</option>
              <option value="500">500</option>
            </select>
          </div>
          <div class="footer-right" style="width: 50px;"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { LineChart } from 'echarts/charts';
import { GridComponent, TooltipComponent, DataZoomComponent, MarkLineComponent } from 'echarts/components';
import VChart from 'vue-echarts';

use([CanvasRenderer, LineChart, GridComponent, TooltipComponent, DataZoomComponent, MarkLineComponent]);

import api from '../../services/api';
import { useThemeStore } from '../../stores/theme';

const themeStore = useThemeStore();

const props = defineProps({
  selectedVehicle: Object,
  panelHeight: Number,
  historyPeriod: String,
  historyFrom: String,
  historyTo: String,
  historyData: { type: Array, default: () => [] },
  historyIndex: { type: Number, default: 0 },
  isHistoryPlaying: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'init-resize', 'reload-history']);

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

const activeTab = ref('data');
const showMessagesActionMenu = ref(false);
const gridData = ref(null);
const loading = ref(false);
let reloadInterval = null;

// Messages logic
const messages = ref([]);
const messagesTotal = ref(0);
const messagesPage = ref(1);
const messagesPerPage = ref(50);
const messagesSidx = ref('dt_tracker');
const messagesSord = ref('desc');
const loadingMessages = ref(false);
const selectedMessageIds = ref([]);

const formatParams = (paramsJsonStr) => {
  if (!paramsJsonStr) return '';
  try {
    const obj = JSON.parse(paramsJsonStr);
    return Object.entries(obj).map(([k, v]) => `${k}=${v}`).join(', ');
  } catch (e) {
    return paramsJsonStr;
  }
};

const toggleSort = (col) => {
  if (messagesSidx.value === col) {
    messagesSord.value = messagesSord.value === 'asc' ? 'desc' : 'asc';
  } else {
    messagesSidx.value = col;
    messagesSord.value = 'desc';
  }
};

const sortIcon = (col) => {
  if (messagesSidx.value !== col) return '';
  return messagesSord.value === 'asc' ? '▲' : '▼';
};

const toggleSelectAll = (e) => {
  if (e.target.checked) {
    selectedMessageIds.value = messages.value.map(m => m.id);
  } else {
    selectedMessageIds.value = [];
  }
};

const fetchMessages = async () => {
  if (!props.selectedVehicle) return;
  loadingMessages.value = true;
  try {
    const res = await api.get(`/api/messages/${props.selectedVehicle.imei}`, {
      params: {
        period: props.historyPeriod || 'today',
        from: props.historyFrom,
        to: props.historyTo,
        page: messagesPage.value,
        per_page: messagesPerPage.value,
        sidx: messagesSidx.value,
        sord: messagesSord.value
      }
    });
    if (res.data && res.data.ok) {
      messages.value = res.data.data;
      messagesTotal.value = res.data.total;
      selectedMessageIds.value = []; // Reset selections across pages
    }
  } catch (err) {
    console.error('Failed to fetch messages', err);
  } finally {
    loadingMessages.value = false;
  }
};

const deleteSelectedMessages = async () => {
  if (selectedMessageIds.value.length === 0) return;
  if (!confirm(`Are you sure you want to delete ${selectedMessageIds.value.length} message(s)?`)) return;

  try {
    const res = await api.post(`/api/messages/${props.selectedVehicle.imei}/delete`, {
      ids: selectedMessageIds.value
    });
    if (res.data && res.data.ok) {
      selectedMessageIds.value = [];
      fetchMessages();
      emit('reload-history');
      showMessagesActionMenu.value = false;
    }
  } catch (err) {
    console.error('Failed to delete messages', err);
  }
};

watch([() => props.historyPeriod, () => props.historyFrom, () => props.historyTo, () => props.selectedVehicle?.imei], () => {
  if (activeTab.value === 'messages') {
    messagesPage.value = 1;
    fetchMessages();
  }
});

watch(activeTab, (val) => {
  if (val === 'messages' && messages.value.length === 0) {
    fetchMessages();
  }
});

watch([messagesPage, messagesPerPage, messagesSidx, messagesSord], () => {
  if (activeTab.value === 'messages') {
    fetchMessages();
  }
});

const fetchGridData = async (silent = false) => {
  if (!props.selectedVehicle) return;
  if (!silent) loading.value = true;
  
  try {
    const res = await api.get(`/api/grid/details/${props.selectedVehicle.imei}`, {
      params: {
        lat: props.selectedVehicle.lat,
        lng: props.selectedVehicle.lng
      }
    });
    if (res.data.ok) {
      gridData.value = res.data;
    }
  } catch (err) {
    console.error('Grid API Error:', err);
  } finally {
    if (!silent) loading.value = false;
  }
};

const startPolling = () => {
  stopPolling();
  reloadInterval = setInterval(() => {
    fetchGridData(true);
  }, 10000);
};

const stopPolling = () => {
  if (reloadInterval) {
    clearInterval(reloadInterval);
    reloadInterval = null;
  }
};

onMounted(() => {
  fetchGridData();
  startPolling();
});

onUnmounted(() => {
  stopPolling();
});

// Graph Logic
const selectedGraphMetric = ref('speed');
const hoveredIndex = ref(null);

const activeDisplayIndex = computed(() => {
  if (hoveredIndex.value !== null && hoveredIndex.value < props.historyData.length) {
    return hoveredIndex.value;
  }
  return props.historyIndex || 0;
});

const handleAxisPointer = (e) => {
  if (e.axesInfo && e.axesInfo.length > 0) {
    hoveredIndex.value = e.axesInfo[0].value;
  }
};

const dynamicGraphParams = computed(() => {
  if (props.historyData.length === 0) return [];
  const sampleParams = props.historyData[0].params;
  if (!sampleParams) return [];
  try {
    const parsed = typeof sampleParams === 'string' ? JSON.parse(sampleParams) : sampleParams;
    return Object.keys(parsed);
  } catch (e) {
    return [];
  }
});

const currentGraphValueLabel = computed(() => {
  if (props.historyData.length === 0) return '0';
  const point = props.historyData[activeDisplayIndex.value] || props.historyData[0];

  if (selectedGraphMetric.value === 'speed') return Math.round(point.speed) + ' km/h';
  if (selectedGraphMetric.value === 'altitude') return Math.round(point.altitude) + ' m';
  if (selectedGraphMetric.value === 'angle') return Math.round(point.heading || point.angle || 0) + '°';

  const rawParams = point.params;
  if (rawParams) {
    try {
      const parsed = typeof rawParams === 'string' ? JSON.parse(rawParams) : rawParams;
      return parsed[selectedGraphMetric.value] !== undefined ? parsed[selectedGraphMetric.value] : 'N/A';
    } catch {
      return 'N/A';
    }
  }
  return 'N/A';
});

const currentGraphTime = computed(() => {
  if (props.historyData.length === 0) return '--:--:--';
  const point = props.historyData[activeDisplayIndex.value] || props.historyData[0];
  return point.dt_tracker;
});

const chartOption = computed(() => {
  const data = [];
  const times = [];

  props.historyData.forEach(p => {
    times.push(p.dt_tracker);
    let val = 0;
    if (selectedGraphMetric.value === 'speed') val = p.speed;
    else if (selectedGraphMetric.value === 'altitude') val = p.altitude;
    else if (selectedGraphMetric.value === 'angle') val = Math.round(p.heading || p.angle || 0);
    else {
      try {
        const parsed = typeof p.params === 'string' ? JSON.parse(p.params) : p.params;
        val = parsed[selectedGraphMetric.value] !== undefined ? Number(parsed[selectedGraphMetric.value]) : 0;
      } catch {
        val = 0;
      }
    }
    data.push(val);
  });

  const markLineData = [];
  if (props.historyData.length > 0 && props.historyIndex < props.historyData.length) {
    markLineData.push({ xAxis: props.historyData[props.historyIndex].dt_tracker });
  }

  const isLight = !themeStore.isDarkMode;
  const textColor = isLight ? '#1a1a1a' : '#f3f4f6';
  const gridLineColor = isLight ? '#e5e7eb' : '#374151';
  const areaColorTop = isLight ? 'rgba(56, 114, 230, 0.5)' : 'rgba(102, 163, 255, 0.5)';
  const areaColorBottom = isLight ? 'rgba(56, 114, 230, 0)' : 'rgba(102, 163, 255, 0)';

  return {
    tooltip: { trigger: 'axis', axisPointer: { type: 'cross' } },
    grid: { left: '2%', right: '2%', bottom: '15%', top: '5%', containLabel: true },
    dataZoom: [
      { type: 'inside', start: 0, end: 100 },
      { start: 0, end: 100, bottom: 0, height: 20 }
    ],
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: times,
      axisLabel: { color: textColor, formatter: (val) => val.split(' ')[1] }
    },
    yAxis: {
      type: 'value',
      axisLabel: { color: textColor },
      splitLine: { lineStyle: { color: gridLineColor } }
    },
    series: [
      {
        name: selectedGraphMetric.value,
        type: 'line',
        smooth: true,
        symbol: 'none',
        sampling: 'lttb',
        itemStyle: { color: 'rgb(56, 114, 230)' },
        areaStyle: {
          color: {
            type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [{ offset: 0, color: areaColorTop }, { offset: 1, color: areaColorBottom }]
          }
        },
        data: data,
        markLine: {
          symbol: ['none', 'none'],
          label: { show: false },
          lineStyle: { color: '#ef4444', width: 2, type: 'solid' },
          data: markLineData,
          animation: false
        }
      }
    ]
  };
});


watch(() => props.selectedVehicle?.imei, () => {
  gridData.value = null; // Reset for new vehicle
  fetchGridData();
  startPolling();
});
</script>

<style scoped>
/* Theme Variables */
.vehicle-details-panel {
  --panel-bg: #111827;
  --header-bg: #1f2937;
  --tab-active-bg: #ffffff;
  --tab-active-text: #111827;
  --tab-inactive-text: #9ca3af;
  --text-main: #f9fafb;
  --text-dim: #9ca3af;
  --border-color: #374151;
  --row-gray-bg: #1f2937;
  --widget-header-bg: #1e293b;
  --widget-header-text: #60a5fa;
  --widget-border: #334155;
  --scrollbar-thumb: #4b5563;
}

.vehicle-details-panel.light-theme {
  --panel-bg: #ffffff;
  --header-bg: #f3f4f6;
  --tab-active-bg: #ffffff;
  --tab-active-text: #3f6f99;
  --tab-inactive-text: #6b7280;
  --text-main: #1f292e;
  --text-dim: #4b5563;
  --border-color: #e5e7eb;
  --row-gray-bg: #f9fafb;
  --widget-header-bg: #dcf1ff;
  --widget-header-text: #3f6f99;
  --widget-border: #e2e8f0;
  --scrollbar-thumb: #cbd5e1;
}

.vehicle-details-panel {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--panel-bg);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  box-shadow: 0 -10px 30px rgba(0,0,0,0.3);
  overflow: hidden;
  font-family: 'Inter', sans-serif;
}

.resize-handle {
  height: 6px;
  cursor: ns-resize;
  background: var(--border-color);
  display: flex;
  justify-content: center;
  align-items: center;
}
.handle-bar { width: 40px; height: 3px; background: var(--text-dim); border-radius: 2px; }

/* Tabs & Header */
.panel-tabs { 
  display: flex; 
  align-items: center; 
  background: var(--header-bg); 
  border-bottom: 1px solid var(--border-color);
  padding: 0 10px;
}
.tab-btn {
  padding: 12px 20px;
  border: none;
  background: transparent;
  color: var(--tab-inactive-text);
  font-weight: 600;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.2s;
}
.tab-btn.active {
  background: var(--tab-active-bg);
  color: var(--tab-active-text);
  border-bottom: 2px solid #3f6f99;
}

.header-right-meta { flex: 1; text-align: right; padding: 0 15px; }
.header-right-meta .v-name { font-weight: 700; color: var(--text-main); margin-right: 15px; }
.header-right-meta .v-imei { color: var(--text-dim); font-size: 0.85rem; }

.header-close-btn { background: none; border: none; color: var(--text-dim); cursor: pointer; padding: 5px; }
.header-close-btn:hover { color: #ef4444; }

/* Content Layout */
.panel-content { flex: 1; overflow: hidden; position: relative; }

.main-display-wrapper {
  display: flex;
  height: 100%;
}

/* Left: Key Data Section */
.key-data-section {
  width: 550px;
  min-width: 550px;
  border-right: 1px solid var(--border-color);
  overflow-y: auto;
  padding: 10px;
}
.data-cols-wrapper {
  display: flex;
  gap: 10px;
}
.data-col { flex: 1; }

.detail-item {
  display: flex;
  align-items: center;
  padding: 6px 10px;
  font-size: 0.85rem;
  border-radius: 4px;
}
.detail-item.gray { background: var(--row-gray-bg); }
.detail-item .icon { width: 20px; margin-right: 10px; opacity: 0.7; }
.detail-item .label { color: var(--text-dim); flex: 1; }
.detail-item .val { color: var(--text-main); font-weight: 600; max-width: 150px; text-align: right; }
.val.blue-link { color: #3b82f6; }
.val.truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Right: Horizontal Widgets */
.widgets-horizontal-section {
  flex: 1;
  display: flex;
  overflow-x: auto;
  padding: 15px;
  gap: 15px;
  background: rgba(0,0,0,0.02);
}

.pro-widget {
  min-width: 320px;
  max-width: 350px;
  height: 250px; /* Fixed identical height */
  background: var(--panel-bg);
  border: 1px solid var(--widget-border);
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.pro-widget-header {
  background: var(--widget-header-bg);
  color: var(--widget-header-text);
  padding: 10px 15px;
  font-weight: 700;
  font-size: 0.9rem;
  border-bottom: 1px solid var(--widget-border);
  min-height: 40px; /* Secure header space */
}

.pro-widget-body { 
  padding: 15px; 
  flex: 1; 
  overflow-y: auto; /* Enable scroll if content exceeds */
}

.empty-widget-body {
  padding: 15px; 
  margin: 0px; 
  border: 1px solid var(--border-color); 
  background: var(--panel-bg); 
  border-top: none; 
  display: flex; 
  justify-content: center; 
  align-items: center; 
  color: #9ca3af; 
  font-size: 15px;
  flex: 1;
}

.behavior-badges-row { display: flex; gap: 10px; margin-bottom: 15px; }
.val-badge {
  flex: 1;
  padding: 10px;
  border-radius: 6px;
  color: #fff;
  text-align: center;
}
.val-badge.blue { background: #3f6f99; }
.val-badge.red { background: #dc2626; }
.val-badge .b-label { font-size: 0.75rem; opacity: 0.9; margin-bottom: 4px; }
.val-badge .b-val { font-size: 0.95rem; font-weight: 700; }

.data-row-pro {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid var(--border-color);
  font-size: 0.85rem;
  color: var(--text-main);
}
.data-row-pro:last-child { border-bottom: none; }
.data-row-pro span { color: var(--text-dim); }

.no-events { text-align: center; color: var(--text-dim); padding: 20px; font-style: italic; }

/* Scrollbars */
.scrollbar-custom::-webkit-scrollbar { width: 6px; }
.scrollbar-custom::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb); border-radius: 3px; }

.scrollbar-custom-h::-webkit-scrollbar { height: 8px; }
.scrollbar-custom-h::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb); border-radius: 4px; }

.loading-overlay {
  position: absolute; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.1); display: flex; justify-content: center; align-items: center;
}
.spinner {
  width: 30px; height: 30px; border: 4px solid #f3f3f3; border-top: 4px solid #3f6f99;
  border-radius: 50%; animation: spin 1s linear infinite;
}
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* Messages Tab Styles */
.messages-tab-wrapper {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--panel-bg);
}

.messages-table-container {
  flex: 1;
  overflow: auto;
  border-bottom: 1px solid var(--border-color);
}

.messages-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
  color: var(--text-main);
}

.messages-table th {
  position: sticky;
  top: 0;
  background: #fdfdfd;
  padding: 12px 15px;
  text-align: left;
  font-weight: 500;
  color: #4b5563;
  border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
  font-size: 12px;
  z-index: 10;
}

.messages-table td {
  padding: 10px 15px;
  border-bottom: 1px solid var(--border-color);
}

.messages-action-menu {
  position: absolute;
  bottom: 30px;
  left: 0;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  padding: 4px;
  z-index: 50;
  min-width: 120px;
}

.messages-actions {
  display: none; /* Hide the old static action bar as it's now a popup */
}

.footer-refresh-btn {
  background: #3b82f6;
  border: none;
  width: 24px;
  height: 24px;
  border-radius: 3px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: white;
  transition: background 0.2s;
  padding: 0;
}
.footer-refresh-btn:hover { background: #2563eb; }

.delete-btn {
  display: flex;
  width: 100%;
  align-items: center;
  gap: 8px;
  background: white;
  border: 1px solid #d1d5db;
  padding: 6px 14px;
  border-radius: 4px;
  font-size: 13px;
  color: #4b5563;
  cursor: pointer;
  transition: all 0.2s;
}
.delete-btn:hover { background: #f3f4f6; color: #111827; }

.messages-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 15px;
  background: white;
  font-size: 13px;
  color: var(--text-dim);
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 5px;
}

.page-btn {
  background: transparent;
  border: none;
  color: var(--text-dim);
  cursor: pointer;
  padding: 4px 6px;
  font-size: 13px;
  transition: color 0.15s;
}
.page-btn:hover { color: #3b82f6; }

.page-info { margin: 0 10px; color: #6b7280; font-size: 12px; }
.page-input {
  width: 40px;
  padding: 2px 5px;
  text-align: center;
  border: 1px solid #d1d5db;
  border-radius: 3px;
  margin: 0 5px;
  font-size: 12px;
  outline: none;
}
.page-input:focus { border-color: #3b82f6; }

.page-size-select {
  padding: 2px 5px;
  border: 1px solid #d1d5db;
  border-radius: 3px;
  margin-left: 10px;
  background: white;
  font-size: 12px;
  color: #4b5563;
  outline: none;
}
</style>
