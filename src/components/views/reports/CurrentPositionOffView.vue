<template>
  <div class="current-position-off-container">
    <!-- Header Section -->
    <div class="report-top-header">
      <div class="info-group">
        <div class="info-item"><strong>Generated:</strong> {{ new Date().toLocaleString() }}</div>
      </div>
      
      <div class="report-center-titles flex-center">
        <h1 class="main-title">Current Position Offline</h1>
      </div>
    </div>

    <!-- Main Table -->
    <div class="table-wrapper box-shadow-premium">
      <table class="report-table">
        <thead>
          <tr>
            <template v-for="(header, hIdx) in reportData.headers" :key="hIdx">
              <th v-if="hIdx > 0" :class="{ 'sticky-col': hIdx === 1 }">
                {{ header }}
              </th>
            </template>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, rIdx) in reportData.rows" :key="rIdx" class="data-row">
            <template v-for="(cell, cIdx) in row" :key="cIdx">
              <td v-if="cIdx > 0" :class="{ 'sticky-col': cIdx === 1 }">
                <!-- Object/Vehicle Column -->
                <div v-if="reportData.headers[cIdx]?.toLowerCase() === 'object'" class="object-cell">
                  <div class="v-icon-off">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                  </div>
                  <span class="v-name">{{ cell }}</span>
                </div>

                <!-- Position Column (Map Links) -->
                <div v-else-if="reportData.headers[cIdx]?.toLowerCase() === 'position'" v-html="cell" class="position-link"></div>

                <!-- Status Column - Specialized for Offline -->
                <div v-else-if="reportData.headers[cIdx]?.toLowerCase() === 'status'" :class="['status-chip-off', getStatusClass(cell)]">
                  <span class="pulse-red"></span>
                  {{ cell }}
                </div>

                <!-- Time Column -->
                <span v-else-if="reportData.headers[cIdx]?.toLowerCase() === 'time'" class="time-stamp-off">{{ cell }}</span>

                <!-- Default -->
                <span v-else class="metric-value-off">{{ cell }}</span>
              </td>
            </template>
          </tr>
          <tr v-if="!reportData.rows || !reportData.rows.length">
            <td :colspan="reportData.headers.length" class="no-data-msg">
              <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M16 16s1.5-2 4-2 4 2 4 2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                <p>All selected units are currently online and synchronized.</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
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

const getStatusClass = (status) => {
  if (!status) return 'status-default';
  const s = status.toLowerCase();
  if (s.includes('offline') || s.includes('unreachable') || s.includes('متوقف')) return 'status-offline';
  return 'status-default';
};
</script>

<style scoped>
.current-position-off-container {
  padding: 32px;
  background: #fcfcfc;
  min-height: 100%;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: #334155;
}

.report-top-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 40px;
  padding-bottom: 24px;
  border-bottom: 1px solid #f1f5f9;
}

.report-center-titles {
  text-align: center;
  flex: 1;
}

.flex-center {
  display: flex;
  justify-content: center;
}

.main-title {
  font-size: 26px;
  font-weight: 900;
  color: #1e293b;
  margin: 0 0 6px 0;
  letter-spacing: -0.5px;
}

.sub-title {
  font-size: 13px;
  font-weight: 700;
  color: #dc2626;
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-bottom: 16px;
}

.export-badge-off {
  font-size: 12px;
  font-weight: 600;
  color: #7f1d1d;
  background: #fee2e2;
  padding: 6px 14px;
  border-radius: 100px;
  display: inline-block;
  border: 1px solid #fecaca;
}

.table-wrapper {
  background: #fff;
  border-radius: 20px;
  overflow-x: auto;
  border: 1px solid #f1f5f9;
}

.box-shadow-premium {
  box-shadow: 0 10px 40px -15px rgba(0, 0, 0, 0.08);
}

.report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
  white-space: nowrap;
}

.report-table th {
  background: #f8fafc;
  padding: 18px 24px;
  text-align: left;
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 1px;
  border-bottom: 2px solid #f1f5f9;
}

.report-table td {
  padding: 18px 24px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.data-row:hover {
  background: #fafafa;
}

.object-cell {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12px;
  white-space: nowrap;
}

.v-icon-off {
  width: 28px;
  height: 28px;
  background: #f1f5f9;
  color: #64748b;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-chip-off {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 8px 16px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 700;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #475569;
}

.status-offline {
  background: #fff1f2;
  color: #be123c;
  border-color: #fecdd3;
}

.pulse-red {
  width: 8px;
  height: 8px;
  background: #ef4444;
  border-radius: 50%;
  box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
  animation: pulse-red-anim 1.5s infinite;
}

@keyframes pulse-red-anim {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

.position-link :deep(a) {
  color: #64748b;
  text-decoration: none;
  border-bottom: 1px dashed #cbd5e1;
}

.time-stamp-off {
  font-family: 'JetBrains Mono', monospace;
  color: #94a3b8;
  font-size: 13px;
}

.metric-value-off {
  color: #64748b;
  font-weight: 600;
}

/* Sticky */
.sticky-col {
  position: sticky;
  left: 0;
  background: #fff; /* Solid background to prevent overlap transparency */
  z-index: 2;
  box-shadow: 4px 0 10px rgba(0,0,0,0.05); /* Slightly stronger shadow for better depth */
}

th.sticky-col { z-index: 3; }
</style>
