<template>
  <div class="drives-stops-sensors-container">
    <!-- Header Section -->
    <div class="report-top-header">
      <div class="info-group">
        <div class="info-item"><strong>Object:</strong> {{ reportData.object || '1/23035' }}</div>
        <div class="info-item"><strong>Group:</strong> {{ reportData.group || 'Facility - 648' }}</div>
        <div class="info-item"><strong>Period:</strong> {{ reportData.period || '-' }}</div>
      </div>
      
      <div class="report-center-titles">
        <h1 class="main-title">Drives and stops with sensors</h1>
        <div class="sub-title">DRIVES AND STOPS WITH SENSORS</div>
        <div class="export-text">Exporting data from {{ reportData.period || '-' }}</div>
      </div>
    </div>

    <!-- Table Section -->
    <div class="table-container" v-for="(vehicle, vIdx) in reportData.vehicles" :key="'v-' + vIdx">
      <div class="vehicle-section-header">
        <div class="vehicle-badge">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
          <span class="vehicle-name">{{ vehicle.name }}</span>
        </div>
      </div>

      <div class="table-wrapper">
        <table class="report-table">
          <thead>
            <tr class="header-row-1">
              <th rowspan="2" class="sticky-num">#</th>
              <th rowspan="2">{{ t('status') }}</th>
              <th rowspan="2">{{ t('start') }}</th>
              <th rowspan="2">{{ t('end') }}</th>
              <th rowspan="2">{{ t('duration') }}</th>
              <th colspan="3" class="merged-header">{{ t('stop_position') }}</th>
              <th rowspan="2">{{ t('fuel_consumption') }}</th>
              <th rowspan="2">{{ t('avg_fuel') }}</th>
              <th rowspan="2">{{ t('fuel_cost') }}</th>
              <th rowspan="2">{{ t('engine_idle') }}</th>
              <th rowspan="2">{{ t('driver') }}</th>
              <th rowspan="2">{{ t('trailer') }}</th>
            </tr>
            <tr class="header-row-2">
              <th>{{ t('distance') }}</th>
              <th>{{ t('top_speed') }}</th>
              <th>{{ t('avg_speed') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in vehicle.rows" :key="idx" 
                class="data-row" :class="row[0]?.toLowerCase()">
              <td class="sticky-num">{{ idx + 1 }}</td>
              <td :class="row[0]?.toLowerCase() === 'stopped' ? 'text-danger' : 'text-success'">
                <strong>{{ row[0] }}</strong>
              </td>
              <td>{{ row[1] }}</td>
              <td>{{ row[2] }}</td>
              <td>{{ row[3] }}</td>
              
              <!-- Dynamic Cells for Stopped vs Moving -->
              <template v-if="row[0]?.toLowerCase() === 'stopped'">
                <td colspan="3" class="address-cell" v-html="row[4]"></td>
                <td v-html="row[5]"></td>
                <td v-html="row[6]"></td>
                <td v-html="row[7]"></td>
                <td v-html="row[8]"></td>
                <td v-html="row[9]"></td>
                <td v-html="row[10]"></td>
              </template>
              <template v-else>
                <td v-for="cellIdx in [4,5,6,7,8,9,10,11,12]" :key="cellIdx" v-html="row[cellIdx]"></td>
              </template>
            </tr>
            <tr v-if="!vehicle.rows?.length">
              <td colspan="14" class="no-data-msg">No data recorded for this vehicle in selected period.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Summary Section PER Vehicle -->
      <div class="summary-footer-premium" v-if="vehicle.summaryHeaders?.length">
        <div class="summary-card" v-for="(header, sIdx) in vehicle.summaryHeaders" :key="sIdx">
          <div class="card-icon">
            <svg v-if="header.toLowerCase().includes('move')" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            <svg v-else-if="header.toLowerCase().includes('stop')" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="6" width="12" height="12"/></svg>
            <svg v-else-if="header.toLowerCase().includes('speed')" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
          </div>
          <div class="card-content">
            <span class="label">{{ header }}</span>
            <span class="value" v-html="vehicle.summaryValues[sIdx]"></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  reportData: {
    type: Object,
    required: true
  },
  t: {
    type: Function,
    required: true
  }
});
</script>

<style scoped>
.drives-stops-sensors-container {
  padding: 30px;
  background: #ffffff;
  min-height: 100%;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: #0f172a;
}

.report-top-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 40px;
  padding-bottom: 20px;
  border-bottom: 1px solid #f1f5f9;
}

.table-container {
  margin-bottom: 60px;
}

.vehicle-section-header {
  margin-bottom: 16px;
  display: flex;
  align-items: center;
}

.vehicle-badge {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f1f5f9;
  padding: 8px 16px;
  border-radius: 100px;
  color: #1e293b;
  border: 1px solid #e2e8f0;
}

.vehicle-badge svg {
  color: #3b82f6;
}

.vehicle-name {
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.info-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.info-item {
  font-size: 13px;
  color: #64748b;
  display: flex;
  gap: 8px;
}

.info-item strong {
  color: #1e293b;
  min-width: 60px;
}

.report-center-titles {
  text-align: right;
  flex: 1;
}

.main-title {
  font-size: 18px;
  font-weight: 700;
  color: #2563eb;
  margin: 0 0 4px 0;
  letter-spacing: -0.02em;
}

.sub-title {
  font-size: 12px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 12px;
}

.export-text {
  font-size: 12px;
  color: #64748b;
  background: #f8fafc;
  padding: 6px 12px;
  border-radius: 6px;
  display: inline-block;
}

.table-wrapper {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
  white-space: nowrap;
}

.report-table th, .report-table td {
  padding: 12px 16px;
  border: 1px solid #f1f5f9;
  text-align: center;
}

/* Header Styles */
.header-row-1 th, .header-row-2 th {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.merged-header {
  background: #f1f5f9 !important;
  color: #1e293b !important;
}

.sticky-num {
  font-weight: 600;
  color: #94a3b8;
  width: 50px;
}

.no-data-msg {
  padding: 40px !important;
  color: #94a3b8;
  font-style: italic;
  font-size: 14px;
}

/* Row Styling */
.data-row {
  transition: background 0.15s ease;
}

.data-row:hover {
  background: #f8fafc;
}

.text-danger { 
  color: #e11d48; 
  font-weight: 600;
}
.text-success { 
  color: #10b981; 
  font-weight: 600;
}

.stopped { 
  background: #fffafa; 
}

.address-cell {
  background: #fdfdfd;
  color: #2563eb;
  font-size: 12px;
  max-width: 300px;
  white-space: normal;
  line-height: 1.4;
}

.address-cell :deep(a) {
  color: #2563eb;
  text-decoration: none;
  font-weight: 500;
}

.address-cell :deep(a):hover {
  text-decoration: underline;
}

/* Premium Summary Cards */
.summary-footer-premium {
  margin-top: 40px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  padding: 24px;
  background: #f8fafc;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
}

.summary-card {
  background: white;
  padding: 16px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.summary-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.card-icon {
  width: 36px;
  height: 36px;
  background: #eff6ff;
  color: #3b82f6;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card-content {
  display: flex;
  flex-direction: column;
}

.card-content .label {
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  margin-bottom: 2px;
}

.card-content .value {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
}

/* Responsive & RTL */
@media (max-width: 1024px) {
  .report-top-header {
    flex-direction: column;
    gap: 20px;
  }
  .report-center-titles {
    text-align: left;
  }
}

[dir="rtl"] .report-center-titles {
  text-align: left;
}

[dir="rtl"] .report-table th, 
[dir="rtl"] .report-table td { 
  text-align: center; 
}

[dir="rtl"] .info-item strong {
  min-width: 80px;
}
</style>
