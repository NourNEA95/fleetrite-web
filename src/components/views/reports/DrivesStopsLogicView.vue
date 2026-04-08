<template>
  <div class="drives-stops-logic-container">
    <!-- Header Section -->
    <div class="report-top-header">
      <div class="info-group">
        <div class="info-item"><strong>Period:</strong> {{ reportData.period || '-' }}</div>
      </div>
      
      <div class="report-center-titles">
        <h1 class="main-title">Drives and stops with logic sensors</h1>
        <div class="sub-title">DRIVES AND STOPS WITH LOGIC SENSORS</div>
        <div class="export-text">Exporting data from {{ reportData.period || '-' }}</div>
      </div>
    </div>

    <!-- Table Section -->
    <div class="table-container" v-for="(vehicle, vIdx) in reportData.vehicles" :key="'v-' + vIdx">
      <div class="vehicle-section-header">
        <div class="vehicle-badge">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
          <span class="vehicle-name">{{ vehicle.name }} ({{ vehicle.imei }})</span>
        </div>
      </div>

      <div class="table-wrapper">
        <table class="report-table">
          <thead>
            <tr class="header-row-1">
              <th rowspan="2" class="sticky-num">#</th>
              <th rowspan="2">Status</th>
              <th rowspan="2">Start</th>
              <th rowspan="2">End</th>
              <th rowspan="2">Duration</th>
              <th colspan="3" class="merged-header">Stop position</th>
              <th rowspan="2" v-for="(header, hIdx) in vehicle.headers.slice(7)" :key="'h-' + hIdx">
                {{ header }}
              </th>
            </tr>
            <tr class="header-row-2">
              <th>Distance Travelled KM</th>
              <th>Top speed</th>
              <th>Average speed</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in vehicle.rows" :key="idx" 
                class="data-row" :class="row[0]?.toLowerCase()">
              <td class="sticky-num">{{ idx + 1 }}</td>
              <td>
                <div :class="['status-chip', getStatusClass(row[0])]">
                  {{ row[0] }}
                </div>
              </td>
              <td class="datetime-cell">{{ row[1] }}</td>
              <td class="datetime-cell">{{ row[2] }}</td>
              <td class="duration-cell">{{ row[3] }}</td>
              
              <!-- Dynamic Cells for Stopped vs Moving -->
              <template v-if="row[0]?.toLowerCase() === 'stopped' || row[0] === 'متوقف'">
                <td colspan="3" class="address-cell" v-html="row[4]"></td>
                <td class="metric-cell" v-for="cellIdx in Array.from({length: row.slice(5).length}, (_, i) => i + 5)" :key="'c-' + cellIdx" v-html="row[cellIdx] || '-'"></td>
              </template>
              <template v-else>
                <td class="metric-cell">{{ row[4] }}</td>
                <td class="metric-cell">{{ row[5] }}</td>
                <td class="metric-cell">{{ row[6] }}</td>
                <td class="metric-cell" v-for="cellIdx in Array.from({length: row.slice(7).length}, (_, i) => i + 7)" :key="'c-' + cellIdx" v-html="row[cellIdx] || '-'"></td>
              </template>
            </tr>
            <!-- TOTAL ROW -->
            <tr class="total-summary-row" v-if="vehicle.summaryValues?.length">
              <td colspan="5" class="total-label">Subtotal / Unit Summary</td>
              <td class="total-metric">{{ getSummaryValue(vehicle, 'Route length') }}</td>
              <td class="total-metric">{{ getSummaryValue(vehicle, 'Top speed') }}</td>
              <td class="total-metric">{{ getSummaryValue(vehicle, 'Average speed') }}</td>
              <td class="total-metric" v-for="mIdx in Array.from({length: vehicle.headers.length - 7}, (_, i) => i + 7)" :key="'m-' + mIdx">
                {{ getSummaryValue(vehicle, vehicle.headers[mIdx]) }}
              </td>
            </tr>
            <tr v-if="(!vehicle.rows || !vehicle.rows.length)">
              <td colspan="15" class="no-data-msg">No data recorded for this vehicle in selected period.</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  reportData: {
    type: Object,
    required: true
  }
});

const getDefaultHeader = (idx) => {
  const defaults = {
    7: 'Fuel consumption',
    8: 'Avg. fuel cons.',
    9: 'Fuel cost',
    10: 'Engine idle',
    11: 'Driver/Trailer'
  };
  return defaults[idx] || '';
};

const getStatusClass = (status) => {
  if (!status) return '';
  const s = status.toLowerCase();
  if (s.includes('moving') || s.includes('تحرك') || s.includes('drive')) return 'status-moving';
  return 'status-stopped';
};

const getSummaryValue = (vehicle, labelPart) => {
  if (!vehicle.summaryHeaders || !vehicle.summaryValues) return '-';
  const idx = vehicle.summaryHeaders.findIndex(h => h.toLowerCase().includes(labelPart.toLowerCase()));
  return idx !== -1 ? vehicle.summaryValues[idx] : '-';
};
</script>

<style scoped>
.drives-stops-logic-container {
  padding: 32px;
  background: #fdfdfd;
  min-height: 100%;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: #1e293b;
}

.report-top-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 40px;
  padding-bottom: 24px;
  border-bottom: 1px solid #e2e8f0;
}

.report-center-titles {
  text-align: right;
  flex: 1;
}

.main-title {
  font-size: 24px;
  font-weight: 800;
  color: #2563eb;
  margin: 0 0 6px 0;
  letter-spacing: -0.5px;
}

.sub-title {
  font-size: 13px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-bottom: 16px;
}

.export-text {
  font-size: 13px;
  color: #64748b;
  background: #f1f5f9;
  padding: 8px 16px;
  border-radius: 8px;
  display: inline-block;
}

.table-container {
  margin-bottom: 64px;
}

.vehicle-section-header {
  margin-bottom: 20px;
  display: flex;
  align-items: center;
}

.vehicle-badge {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #eff6ff;
  padding: 10px 20px;
  border-radius: 100px;
  color: #1e40af;
  border: 1px solid #dbeafe;
  box-shadow: 0 2px 4px rgba(37, 99, 235, 0.05);
}

.vehicle-name {
  font-size: 15px;
  font-weight: 700;
}

.table-wrapper {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03), 0 0 1px rgba(0, 0, 0, 0.1);
  overflow-x: auto;
  border: 1px solid #e2e8f0;
}

.report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13.5px;
  white-space: nowrap;
}

.report-table th, .report-table td {
  padding: 14px 18px;
  border: 1px solid #f1f5f9;
  text-align: center;
}

.header-row-1 th, .header-row-2 th {
  background: #f8fafc;
  color: #475569;
  font-weight: 700;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.merged-header {
  background: #f1f5f9 !important;
  color: #1e3a8a !important;
}

.sticky-num {
  font-weight: 600;
  color: #94a3b8;
  width: 60px;
}

.status-chip {
  padding: 4px 10px;
  border-radius: 6px;
  font-weight: 700;
  font-size: 12px;
  display: inline-block;
  min-width: 80px;
}

.status-moving {
  background: #dcfce7;
  color: #166534;
}

.status-stopped {
  background: #fee2e2;
  color: #991b1b;
}

.datetime-cell {
  font-family: 'JetBrains Mono', 'Courier New', monospace;
  font-size: 12.5px;
  color: #4b5563;
}

.duration-cell {
  font-weight: 600;
  color: #2563eb;
}

.address-cell {
  background: #fdfdfd;
  color: #3b82f6;
  font-size: 12px;
  max-width: 350px;
  white-space: normal;
  text-align: left !important;
  line-height: 1.5;
}

.address-cell :deep(a) {
  color: #2563eb;
  text-decoration: none;
  font-weight: 500;
}

.total-summary-row {
  background: #f8fafc;
  font-weight: 800;
  color: #1e293b;
}

.total-label {
  text-align: right !important;
  padding-right: 24px !important;
  text-transform: uppercase;
  color: #64748b;
  font-size: 12px;
}

.total-metric {
  color: #2563eb;
  background: rgba(37, 99, 235, 0.03);
}

.no-data-msg {
  padding: 60px !important;
  color: #94a3b8;
  font-style: italic;
  font-size: 16px;
}
</style>
