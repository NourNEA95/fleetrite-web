<template>
  <div class="current-position-container">
    <!-- Header Section -->
    <div class="report-top-header">
      <div class="info-group">
        <div class="info-item"><strong>Generated:</strong> {{ new Date().toLocaleString() }}</div>
      </div>
      
      <div class="report-center-titles flex-center">
        <h1 class="main-title">Current Position</h1>
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
                  <div class="v-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                  </div>
                  <span class="v-name">{{ cell }}</span>
                </div>

                <!-- Position Column (Map Links) -->
                <div v-else-if="reportData.headers[cIdx]?.toLowerCase() === 'position'" v-html="cell" class="position-link"></div>

                <!-- Status Column -->
                <div v-else-if="reportData.headers[cIdx]?.toLowerCase() === 'status'" :class="['status-chip', getStatusClass(cell)]">
                  <span class="dot"></span>
                  {{ cell }}
                </div>

                <!-- Time Column -->
                <span v-else-if="reportData.headers[cIdx]?.toLowerCase() === 'time'" class="time-stamp">{{ cell }}</span>

                <!-- Default -->
                <span v-else class="metric-value">{{ cell }}</span>
              </td>
            </template>
          </tr>
          <tr v-if="!reportData.rows || !reportData.rows.length">
            <td :colspan="reportData.headers.length" class="no-data-msg">
              <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <p>No active units found in the selected parameters.</p>
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
  if (s.includes('moving') || s.includes('تحرك') || s.includes('drive')) return 'status-moving';
  if (s.includes('stopped') || s.includes('متوقف')) return 'status-stopped';
  if (s.includes('idle')) return 'status-idle';
  return 'status-default';
};
</script>

<style scoped>
.current-position-container {
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
  text-align: center;
  flex: 1;
}

.flex-center {
  display: flex;
  justify-content: center;
}

.main-title {
  font-size: 26px;
  font-weight: 800;
  color: #2563eb;
  margin: 0 0 6px 0;
  letter-spacing: -1px;
}

.sub-title {
  font-size: 13px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-bottom: 16px;
}

.export-badge {
  font-size: 12px;
  font-weight: 600;
  color: #3b82f6;
  background: #eff6ff;
  padding: 6px 14px;
  border-radius: 100px;
  display: inline-block;
}

.table-wrapper {
  background: #fff;
  border-radius: 20px;
  overflow-x: auto;
  border: 1px solid #e2e8f0;
}

.box-shadow-premium {
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05), 0 4px 10px -5px rgba(0, 0, 0, 0.02);
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
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #f1f5f9;
}

.report-table td {
  padding: 16px 24px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.data-row:hover {
  background: #fcfdfe;
}

.row-index {
  font-family: 'JetBrains Mono', monospace;
  color: #94a3b8;
  font-weight: 600;
}

.object-cell {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12px;
  white-space: nowrap;
}

.v-icon {
  width: 28px;
  height: 28px;
  background: #eff6ff;
  color: #2563eb;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.v-name {
  font-weight: 700;
  color: #1e293b;
}

.position-link :deep(a) {
  color: #3b82f6;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.2s;
}

.position-link :deep(a):hover {
  color: #2563eb;
  text-decoration: underline;
}

.status-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  border-radius: 100px;
  font-size: 12px;
  font-weight: 700;
  border: 1px solid transparent;
}

.dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.status-moving { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
.status-moving .dot { background: #22c55e; box-shadow: 0 0 8px #22c55e; }

.status-stopped { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
.status-stopped .dot { background: #ef4444; }

.status-idle { background: #fef9c3; color: #854d0e; border-color: #fef08a; }
.status-idle .dot { background: #eab308; }

.status-default { background: #f1f5f9; color: #475569; }

.time-stamp {
  font-family: 'JetBrains Mono', monospace;
  font-size: 13px;
  color: #64748b;
}

.metric-value {
  color: #4b5563;
  font-weight: 500;
}

.no-data-msg {
  padding: 80px 0 !important;
  text-align: center;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  color: #94a3b8;
}

.empty-state p {
  font-size: 16px;
  font-style: italic;
}

/* Sticky Headers/Columns for horizontal scroll */
.sticky-col {
  position: sticky;
  left: 0;
  background: #fff; /* Solid background to prevent overlap transparency */
  z-index: 2;
  box-shadow: 4px 0 10px rgba(0,0,0,0.05); /* Slightly stronger shadow for better depth */
}

th.sticky-col {
  z-index: 3;
}
</style>
