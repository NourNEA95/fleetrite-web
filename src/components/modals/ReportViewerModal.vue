<template>
  <div class="modal-backdrop" @click.self="$emit('close')" :dir="currentLang === 'ar' ? 'rtl' : 'ltr'">
    <div class="modal-container ripple-bg">
      <!-- Header -->
      <div class="modal-header">
        <div class="header-content">
          <svg class="header-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
          </svg>
          <h2 class="modal-title">{{ t('report_results') }}: {{ reportTypeDisplayName }}</h2>
        </div>
        <div class="header-actions">
           <!-- Language Toggle -->
           <button class="lang-btn" @click="toggleLang">
             {{ currentLang === 'en' ? 'العربية' : 'English' }}
           </button>

           <div class="export-group">
             <button class="action-btn excel" @click="exportExcel">
               <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                 <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                 <path d="M8 13h8M8 17h8M10 9h6"></path>
               </svg>
               Excel
             </button>
             <button class="action-btn pdf" @click="exportPDF">
               <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                 <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                 <path d="M14 2v6h6"></path>
                 <path d="M16 13H8M16 17H8M10 9H8"></path>
               </svg>
               PDF
             </button>
             <button class="action-btn html" @click="exportHTML">
               <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                 <polyline points="16 18 22 12 16 6"></polyline>
                 <polyline points="8 6 2 12 8 18"></polyline>
               </svg>
               HTML
             </button>
           </div>
           
           <button class="close-btn" @click="$emit('close')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="modal-body custom-scrollbar">
          <!-- Summary Dashboard Overlay (Hidden for route logs) -->
          <div class="dashboard-grid" v-if="safeReportData.length > 0 && reportType !== 'route_data_sensors'">
             <!-- Row 1: Key Performance -->
             <div class="stat-card">
                <div class="stat-icon dist"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg></div>
                <div class="stat-info">
                  <span class="stat-label">{{ t('total_distance') }}</span>
                  <span class="stat-value">{{ cleanValue(globalTotalRow?.route_length) || totalDistance }} <small>km</small></span>
                </div>
             </div>
             
             <div class="stat-card">
                <div class="stat-icon fuel"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 14h18M4 6h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/></svg></div>
                <div class="stat-info">
                  <span class="stat-label">{{ t('total_fuel') }}</span>
                  <span class="stat-value text-info">{{ cleanValue(globalTotalRow?.fuel_consumption) || totalFuel }} <small>L</small></span>
                </div>
             </div>

             <div class="stat-card">
                <div class="stat-icon stops"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/></svg></div>
                <div class="stat-info">
                  <span class="stat-label">{{ t('stop_count') }}</span>
                  <span class="stat-value">{{ globalTotalRow?.stop_count || '0' }}</span>
                </div>
             </div>

             <div class="stat-card">
                <div class="stat-icon overspeed"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
                <div class="stat-info">
                  <span class="stat-label">{{ t('overspeed_count') }}</span>
                  <span class="stat-value text-warning">{{ globalTotalRow?.overspeed_count || '0' }}</span>
                </div>
             </div>

             <!-- Row 2: Engine & Usage -->
             <div class="stat-card">
                <div class="stat-icon engine"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v10m0 0l-4-4m4 4l4-4M5 20h14"/></svg></div>
                <div class="stat-info">
                  <span class="stat-label">{{ t('engine_work') }}</span>
                  <span class="stat-value">{{ globalTotalRow?.engine_work || '-' }}</span>
                </div>
             </div>

             <div class="stat-card">
                <div class="stat-icon idle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h5m10 0h5M7 12a5 5 0 0110 0"/></svg></div>
                <div class="stat-info">
                  <span class="stat-label">{{ t('engine_idle') }}</span>
                  <span class="stat-value text-muted">{{ globalTotalRow?.engine_idle || '-' }}</span>
                </div>
             </div>

             <div class="stat-card">
                <div class="stat-icon odo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></div>
                <div class="stat-info">
                  <span class="stat-label">{{ t('odometer') }}</span>
                  <span class="stat-value">{{ globalTotalRow?.odometer || '-' }}</span>
                </div>
             </div>

             <div class="stat-card">
                <div class="stat-icon time"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div class="stat-info">
                  <span class="stat-label">{{ t('engine_hours') }}</span>
                  <span class="stat-value">{{ globalTotalRow?.engine_hours || '-' }}</span>
                </div>
             </div>
          </div>

          <!-- Professional Data Table -->
          <div class="table-container shadow-2xl">
            <table class="report-table" id="report-table-element">
              <thead>
                <tr v-if="reportType.includes('general')">
                  <th>{{ t('object') }}</th>
                  <th>{{ t('group') }}</th>
                  <th>{{ t('route_start') }}</th>
                  <th>{{ t('route_end') }}</th>
                  <th>{{ t('route_length') }}</th>
                  <th>{{ t('move_duration') }}</th>
                  <th>{{ t('stop_duration') }}</th>
                  <th>{{ t('stop_count') }}</th>
                  <th>{{ t('top_speed') }}</th>
                  <th>{{ t('speed_limit') }}</th>
                  <th>{{ t('overspeed_count') }}</th>
                  <th>{{ t('fuel_consumption') }}</th>
                  <th>{{ t('avg_fuel') }}</th>
                  <th>{{ t('fuel_cost') }}</th>
                  <th>{{ t('engine_work') }}</th>
                  <th>{{ t('engine_idle') }}</th>
                  <th>{{ t('odometer') }}</th>
                  <th>{{ t('engine_hours') }}</th>
                  <th>{{ t('driver') }}</th>
                  <th>{{ t('trailer') }}</th>
                </tr>
                <tr v-else-if="reportType === 'drives_stops'">
                  <th>{{ t('status') }}</th>
                  <th>{{ t('start') }}</th>
                  <th>{{ t('end') }}</th>
                  <th>{{ t('duration') }}</th>
                  <th>{{ t('length') }}</th>
                  <th>{{ t('top_speed') }}</th>
                  <th>{{ t('avg_speed') }}</th>
                  <th>{{ t('fuel_consumption') }}</th>
                  <th>{{ t('engine_idle') }}</th>
                  <th>{{ t('driver') }}</th>
                </tr>
                <!-- Special Header for Dynamic Route Sensors -->
                 <tr v-else-if="reportType === 'route_data_sensors'" align="center" style="background-color:#0b1eaa;">
                   <th v-for="(lab, index) in (safeReportData[0]?.labels || [])" :key="index">
                      {{ lab }}
                   </th>
                 </tr>
              </thead>
              <tbody>
                <!-- Route Data with Dynamic Sensors -->
                <template v-if="reportType === 'route_data_sensors'">
                  <tr v-for="(row, idx) in tableRows" :key="idx" class="table-row">
                     <td v-for="(val, vIdx) in row.values" :key="vIdx" v-html="val">
                     </td>
                  </tr>
                </template>

                <template v-if="reportType.includes('general')">
                  <tr v-for="(row, idx) in tableRows" :key="idx" class="table-row">
                    <td class="sticky-col font-bold">{{ row.object || row.imei }}</td>
                    <td>{{ row.group || '-' }}</td>
                    <td>{{ row.route_start }}</td>
                    <td>{{ row.route_end }}</td>
                    <td class="text-right whitespace-nowrap">{{ row.route_length }} km</td>
                    <td class="text-right">{{ formatDuration(row.route_duration) }}</td>
                    <td class="text-right">{{ formatDuration(row.stop_duration) }}</td>
                    <td class="text-center">{{ row.stop_count }}</td>
                    <td class="text-right">{{ row.top_speed }} km/h</td>
                    <td class="text-right">{{ row.speed_limit || '80' }}</td>
                    <td class="text-center" :class="{ 'danger-text': row.overspeed_count > 0 }">{{ row.overspeed_count }}</td>
                    <td class="text-right">{{ row.fuel_consumption }} L</td>
                    <td class="text-right">{{ row.average_fuel || '0' }} L/100km</td>
                    <td class="text-right">{{ row.fuel_cost || '0' }}</td>
                    <td class="text-right">{{ formatDuration(row.engine_work) }}</td>
                    <td class="text-right">{{ formatDuration(row.engine_idle) }}</td>
                    <td class="text-right">{{ row.odometer || '0' }} km</td>
                    <td class="text-right">{{ formatDuration(row.engine_hours) }}</td>
                    <td>{{ row.driver || 'n/a' }}</td>
                    <td>{{ row.trailer || 'n/a' }}</td>
                  </tr>
                </template>
                
                <template v-else-if="reportType === 'drives_stops'">
                  <tr v-for="(row, idx) in tableRows" :key="idx" class="table-row">
                    <td>
                      <div :class="['status-chip', row.status === 'drive' ? 'drive' : 'stop']">
                        {{ t(row.status) }}
                      </div>
                    </td>
                    <td>{{ row.start }}</td>
                    <td>{{ row.end }}</td>
                    <td class="text-right">{{ formatDuration(row.duration) }}</td>
                    <td :class="row.status === 'stop' ? 'text-left font-medium' : 'text-right'" :style="row.status === 'stop' ? 'min-width: 300px; white-space: normal;' : ''">
                      {{ row.length }}{{ (row.status === 'drive' && row.length) ? ' km' : '' }}
                    </td>
                    <td class="text-right">{{ row.top_speed }}{{ row.top_speed ? ' km/h' : '' }}</td>
                    <td class="text-right">{{ row.avg_speed }}{{ row.avg_speed ? ' km/h' : '' }}</td>
                    <td class="text-right">{{ row.fuel_consumption || '0' }} L</td>
                    <td class="text-right">{{ formatDuration(row.engine_idle) }}</td>
                    <td>{{ row.driver || 'n/a' }}</td>
                  </tr>
                </template>
              </tbody>

              <!-- Dedicated Total Footer Section -->
              <tfoot v-if="globalTotalRow">
                  <!-- General Reports Total -->
                  <tr v-if="reportType.includes('general')" class="global-total-row">
                    <td class="sticky-col font-bold">Total:</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td class="text-right">{{ cleanValue(globalTotalRow.route_length) }} km</td>
                    <td class="text-right">{{ formatDuration(globalTotalRow.route_duration) }}</td>
                    <td class="text-right">{{ formatDuration(globalTotalRow.stop_duration) }}</td>
                    <td class="text-center">{{ globalTotalRow.stop_count }}</td>
                    <td class="text-right">{{ globalTotalRow.top_speed }} km/h</td>
                    <td class="text-right">-</td>
                    <td class="text-center">{{ globalTotalRow.overspeed_count }}</td>
                    <td class="text-right">{{ cleanValue(globalTotalRow.fuel_consumption) }} L</td>
                    <td class="text-right">{{ cleanValue(globalTotalRow.average_fuel) }} L/100km</td>
                    <td class="text-right">{{ cleanValue(globalTotalRow.fuel_cost) }}</td>
                    <td class="text-right">{{ formatDuration(globalTotalRow.engine_work) }}</td>
                    <td class="text-right">{{ formatDuration(globalTotalRow.engine_idle) }}</td>
                    <td class="text-right">{{ cleanValue(globalTotalRow.odometer) }} km</td>
                    <td class="text-right">{{ formatDuration(globalTotalRow.engine_hours) }}</td>
                    <td>-</td>
                    <td>-</td>
                  </tr>

                  <!-- Drives and Stops Total -->
                  <tr v-else-if="reportType === 'drives_stops'" class="global-total-row">
                    <td class="sticky-col font-bold">Total:</td>
                    <td>-</td>
                    <td>-</td>
                    <td class="text-right">{{ formatDuration(globalTotalRow.duration) }}</td>
                    <td class="text-right">
                      <div class="text-xs opacity-75">Move: {{ formatDuration(globalTotalRow.move_duration) }}</div>
                      <div class="text-xs opacity-75">Stop: {{ formatDuration(globalTotalRow.stop_duration) }}</div>
                      {{ globalTotalRow.length }} km
                    </td>
                    <td class="text-right">{{ globalTotalRow.top_speed }} km/h</td>
                    <td class="text-right">{{ globalTotalRow.avg_speed }} km/h</td>
                    <td class="text-right">{{ cleanValue(globalTotalRow.fuel_consumption) }} L</td>
                    <td class="text-right">-</td>
                    <td>-</td>
                  </tr>
              </tfoot>
            </table>
            
            <div v-if="generalPaginated.loading" class="empty-state">
              <div class="loading-spinner"></div>
              <p>Loading report data...</p>
            </div>
            <div v-else-if="safeReportData.length === 0" class="empty-state">
               <div class="empty-icon">
                 <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                   <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                 </svg>
               </div>
               <p>{{ t('no_data_found') }}</p>
            </div>

            <!-- Pagination for general report (key-based, 1000 per page) -->
            <div v-if="isGeneralKeyBased && generalPaginated.last_page > 1" class="report-pagination">
              <span class="pagination-info">
                Page {{ generalPaginated.current_page }} of {{ generalPaginated.last_page }}
                ({{ generalPaginated.total }} total records, {{ generalPaginated.per_page }} per page)
              </span>
              <div class="pagination-btns">
                <button class="pagination-btn" :disabled="generalPaginated.current_page <= 1" @click="loadGeneralInfoPage(generalPaginated.current_page - 1)">
                  ‹ Previous
                </button>
                <button class="pagination-btn" :disabled="generalPaginated.current_page >= generalPaginated.last_page" @click="loadGeneralInfoPage(generalPaginated.current_page + 1)">
                  Next ›
                </button>
              </div>
            </div>
          </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import * as XLSX from 'xlsx';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import api from '../../services/api';

const props = defineProps({
  reportType: { type: String, default: 'general' },
  reportData: { type: Array, default: () => [] },
  reportKey: { type: [String, Array], default: '' },
});

const emit = defineEmits(['close']);

// When type is general and we have a key but no data, data is loaded via paginated API
const isGeneralKeyBased = computed(() =>
  props.reportType &&
  props.reportType.includes('general') &&
  (
    (Array.isArray(props.reportKey) && props.reportKey.length > 0) ||
    (typeof props.reportKey === 'string' && props.reportKey.trim().length > 0)
  ) &&
  (!props.reportData || props.reportData.length === 0)
);

const generalPaginated = ref({
  data: [],
  totals_row: null,
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 1000,
  loading: false
});

// Avoid reading .length on undefined when parent passes :report-data="viewerData.data" and it's undefined
const safeReportData = computed(() => {
  if (isGeneralKeyBased.value && generalPaginated.value.data.length) {
    return generalPaginated.value.data;
  }
  return Array.isArray(props.reportData) ? props.reportData : [];
});

// Translation System
const currentLang = ref('en');
const translations = {
  en: {
    report_results: 'Report Results',
    object: 'OBJECT',
    group: 'GROUP',
    route_start: 'ROUTE START',
    route_end: 'ROUTE END',
    route_length: 'ROUTE LENGTH',
    move_duration: 'MOVE DURATION',
    stop_duration: 'STOP DURATION',
    stop_count: 'STOP COUNT',
    top_speed: 'TOP SPEED',
    speed_limit: 'SPEED LIMIT',
    overspeed_count: 'OVERSPEED COUNT',
    fuel_consumption: 'FUEL CONSUMPTION',
    avg_fuel: 'AVG. FUEL',
    fuel_cost: 'FUEL COST',
    engine_work: 'ENGINE WORK',
    engine_idle: 'ENGINE IDLE',
    odometer: 'ODOMETER',
    engine_hours: 'ENGINE HOURS',
    driver: 'DRIVER',
    trailer: 'TRAILER',
    no_data_found: 'No data found for this period.',
    drive: 'Drive',
    stop: 'Stop',
    status: 'STATUS',
    start: 'START',
    end: 'END',
    duration: 'DURATION',
    length: 'LENGTH',
    avg_speed: 'AVG. SPEED'
  },
  ar: {
    report_results: 'نتائج التقرير',
    object: 'الجسم/المركبة',
    group: 'المجموعة',
    route_start: 'بداية المسار',
    route_end: 'نهاية المسار',
    route_length: 'طول المسار',
    move_duration: 'مدة الحركة',
    stop_duration: 'مدة التوقف',
    stop_count: 'عدد التوقفات',
    top_speed: 'أقصى سرعة',
    speed_limit: 'حد السرعة',
    overspeed_count: 'مرات تجاوز السرعة',
    fuel_consumption: 'استهلاك الوقود',
    avg_fuel: 'متوسط الاستهلاك',
    fuel_cost: 'تكلفة الوقود',
    engine_work: 'عمل المحرك',
    engine_idle: 'خمول المحرك',
    odometer: 'عداد المسافة',
    engine_hours: 'ساعات العمل',
    driver: 'السائق',
    trailer: 'المقطورة',
    no_data_found: 'لا توجد بيانات لهذه الفترة.',
    drive: 'قيادة',
    stop: 'توقف',
    status: 'الحالة',
    start: 'البداية',
    end: 'النهاية',
    duration: 'المدة',
    length: 'المسافة',
    avg_speed: 'متوسط السرعة'
  }
};

const t = (key) => translations[currentLang.value][key] || key;
const toggleLang = () => currentLang.value = currentLang.value === 'en' ? 'ar' : 'en';

const reportTypeDisplayName = computed(() => {
    return t(props.reportType) || props.reportType;
});

// Formatting Logic
const formatDuration = (val) => {
  if (!val) return '0 s';
  // If it's already a formatted string like "1 h 22 min", return it
  if (typeof val === 'string' && (val.includes('h') || val.includes('m'))) return val;
  
  const seconds = parseInt(val);
  if (isNaN(seconds)) return val;
  
  const d = Math.floor(seconds / (3600 * 24));
  const h = Math.floor((seconds % (3600 * 24)) / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = seconds % 60;
  
  let res = '';
  if (d > 0) res += `${d} d `;
  if (h > 0) res += `${h} h `;
  if (m > 0) res += `${m} min `;
  if (s > 0 || res === '') res += `${s} s`;
  return res.trim();
};

// Extract Backend Total Row (from props or from paginated API totals_row)
const globalTotalRow = computed(() => {
    if (isGeneralKeyBased.value && generalPaginated.value.totals_row) {
        return generalPaginated.value.totals_row;
    }
    if (!Array.isArray(props.reportData)) return null;
    return props.reportData.find(r => 
        r.is_total === true || 
        r.is_total === 1 || 
        r.is_total === 'true' ||
        (r.object && r.object.toString().startsWith('Total:'))
    );
});

// Filter out Total row from main table body (since we use tfoot)
const tableRows = computed(() => {
    if (!Array.isArray(safeReportData.value)) return [];
    return safeReportData.value.filter(r => 
        !r.is_total && 
        r.is_total !== 1 && 
        r.is_total !== 'true' && 
        !(r.object && r.object.toString().startsWith('Total:'))
    );
});

// Utility to strip units for calculations/display
const cleanValue = (val) => {
    if (val === undefined || val === null) return '';
    return val.toString().replace(/[a-zA-Z\s]/g, '').trim();
};

const totalDistance = computed(() => {
  const sum = tableRows.value.reduce((acc, row) => acc + (parseFloat(cleanValue(row.route_length || row.length)) || 0), 0);
  return sum.toFixed(2);
});

const totalMoveDuration = computed(() => {
    return formatDuration(tableRows.value.reduce((acc, row) => acc + (parseInt(row.route_duration || row.duration) || 0), 0));
});

const topSpeed = computed(() => {
  if (tableRows.value.length === 0) return 0;
  return Math.max(...tableRows.value.map(row => parseFloat(row.top_speed) || 0), 0);
});

const totalFuel = computed(() => {
  const sum = tableRows.value.reduce((acc, row) => acc + (parseFloat(cleanValue(row.fuel_consumption)) || 0), 0);
  return sum.toFixed(2);
});

const exportExcel = () => {
  const table = document.getElementById('report-table-element');
  const wb = XLSX.utils.table_to_book(table);
  XLSX.writeFile(wb, `${props.reportType}_report.xlsx`);
};

const exportPDF = () => {
  const doc = new jsPDF('l', 'mm', 'a4');
  autoTable(doc, { 
    html: '#report-table-element',
    styles: { font: 'helvetica', fontSize: 8 },
    headStyles: { fillColor: [11, 30, 170] }
  });
  doc.save(`${props.reportType}_report.pdf`);
};

const exportHTML = () => {
    const table = document.getElementById('report-table-element').outerHTML;
    const blob = new Blob([`<html><head><style>table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px}th{background:#0b1eaa;color:white}tr:nth-child(even){background:#f2f2f2}</style></head><body>${table}</body></html>`], { type: 'text/html' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `${props.reportType}_report.html`;
    link.click();
};

// Load paginated general info data when we have key but no data
const loadGeneralInfoPage = async (page = 1) => {
  if (!props.reportType?.includes('general')) return;
  const keysParam = Array.isArray(props.reportKey) ? props.reportKey.join(',') : props.reportKey;
  if (!keysParam || !String(keysParam).trim()) return;

  generalPaginated.value.loading = true;
  try {
    const res = await api.post('/api/reports/general-info/data', {
      keys: keysParam,
      page,
      per_page: 1000
    });
    generalPaginated.value.data = res.data.data || [];
    generalPaginated.value.totals_row = res.data.totals_row || null;
    generalPaginated.value.current_page = res.data.current_page ?? 1;
    generalPaginated.value.last_page = res.data.last_page ?? 1;
    generalPaginated.value.total = res.data.total ?? 0;
    generalPaginated.value.per_page = res.data.per_page ?? 1000;
  } catch (e) {
    console.error('Failed to load report data:', e);
    generalPaginated.value.data = [];
    generalPaginated.value.totals_row = null;
  } finally {
    generalPaginated.value.loading = false;
  }
};

onMounted(() => {
  if (isGeneralKeyBased.value) loadGeneralInfoPage(1);
});

watch(
  () => [props.reportKey, props.reportType],
  () => {
    if (isGeneralKeyBased.value) {
      generalPaginated.value.data = [];
      generalPaginated.value.totals_row = null;
      loadGeneralInfoPage(1);
    }
  }
);
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-container {
  width: 95vw;
  height: 90vh;
  background: #0f172a;
  border-radius: 24px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  overflow: hidden;
  color: #fff;
}

.modal-header {
  padding: 24px 40px;
  background: rgba(255, 255, 255, 0.03);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-icon { color: #3b82f6; }

.modal-title {
  font-size: 20px;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 20px;
}

.lang-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #cbd5e1;
    padding: 8px 16px;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.lang-btn:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }

.export-group {
    display: flex;
    background: rgba(255, 255, 255, 0.03);
    padding: 4px;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 18px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.action-btn.excel { color: #22c55e; background: transparent; }
.action-btn.pdf { color: #ef4444; background: transparent; }
.action-btn.html { color: #3b82f6; background: transparent; }

.action-btn:hover { background: rgba(255, 255, 255, 0.08); }

.modal-body {
  flex: 1;
  padding: 40px;
  overflow-y: auto;
}

/* Dashboard Grid */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
  margin-bottom: 40px;
}

.stat-card {
  background: rgba(255, 255, 255, 0.03);
  padding: 24px;
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  align-items: center;
  gap: 20px;
  transition: transform 0.3s;
}

.stat-card:hover { transform: translateY(-4px); background: rgba(255, 255, 255, 0.05); }

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

/* Icon Colors */
.stat-icon.dist { color: #3b82f6; background: rgba(59, 130, 246, 0.1); }
.stat-icon.fuel { color: #10b981; background: rgba(16, 185, 129, 0.1); }
.stat-icon.stops { color: #f59e0b; background: rgba(245, 158, 11, 0.1); }
.stat-icon.overspeed { color: #ef4444; background: rgba(239, 68, 68, 0.1); }

.stat-info { display: flex; flex-direction: column; }
.stat-label { font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; }
.stat-value { font-size: 20px; font-weight: 800; }

/* Table Section */
.table-container {
  background: rgba(15, 23, 42, 0.6);
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  overflow: hidden;
}

.report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.report-table th {
  background: rgba(255, 255, 255, 0.02);
  padding: 18px 16px;
  text-align: left;
  font-weight: 700;
  color: #94a3b8;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.report-table td {
  padding: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.03);
  color: #cbd5e1;
}

.table-row:hover { background: rgba(255, 255, 255, 0.02); }

.sticky-col { position: sticky; left: 0; background: #0f172a; z-index: 10; padding-right: 24px; }

.danger-text { color: #ef4444; font-weight: 700; }

.status-chip {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}
.status-chip.drive { background: rgba(16, 185, 129, 0.2); color: #10b981; }
.status-chip.stop { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.global-total-row {
  background: rgba(59, 130, 246, 0.1) !important;
  font-weight: 800;
  color: #fff;
}

.global-total-row td {
  border-top: 2px solid rgba(59, 130, 246, 0.3);
  color: #fff !important;
}

.close-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 8px;
  border-radius: 10px;
  transition: all 0.2s;
}

.close-btn:hover { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }

.empty-state {
  padding: 60px;
  text-align: center;
  color: #64748b;
}

.empty-icon {
  margin-bottom: 16px;
  opacity: 0.3;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 16px;
  border: 3px solid rgba(59, 130, 246, 0.2);
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.report-pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  margin-top: 16px;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.05);
}
.pagination-info { font-size: 13px; color: #94a3b8; font-weight: 600; }
.pagination-btns { display: flex; gap: 12px; }
.pagination-btn {
  padding: 8px 16px;
  border-radius: 8px;
  background: rgba(59, 130, 246, 0.15);
  border: 1px solid rgba(59, 130, 246, 0.3);
  color: #60a5fa;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.pagination-btn:hover:not(:disabled) { background: rgba(59, 130, 246, 0.25); color: #fff; }
.pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* RTL Support */
[dir="rtl"] .report-table th, 
[dir="rtl"] .report-table td { text-align: right; }
[dir="rtl"] .sticky-col { left: auto; right: 0; }
</style>
