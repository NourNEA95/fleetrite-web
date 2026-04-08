<template>
  <div class="report-view-container custom-scrollbar">
    <table class="premium-table">
      <thead>
        <tr>
          <th v-for="header in headers" :key="header">{{ header }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, rowIndex) in rows" :key="rowIndex" :class="{ 'vehicle-header-row': row[0] === 'ROW_IS_HEADER' }">
          <template v-if="row[0] === 'ROW_IS_HEADER'">
            <td :colspan="headers.length" class="header-td">
              <div class="header-content">
                <span class="text">{{ row[1] }}</span>
              </div>
            </td>
          </template>
          <template v-else>
            <td v-for="(cell, cellIndex) in row" :key="cellIndex">
              <div v-if="isLocation(cell)" v-html="cell" class="location-link"></div>
              <div v-else>{{ cell }}</div>
            </td>
          </template>
        </tr>
        <tr v-if="!rows.length" class="no-data-row">
          <td :colspan="headers.length" class="text-center">No underspeed events found for the selected period</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
const props = defineProps({
  headers: { type: Array, default: () => [] },
  rows: { type: Array, default: () => [] }
});

const isLocation = (val) => {
  if (typeof val !== 'string') return false;
  return val.includes('<a href=') && val.includes('maps.google.com');
};
</script>

<style scoped>
.report-view-container {
  width: 100%;
  overflow-x: auto;
  border-radius: 12px;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.premium-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 13px;
}

.premium-table th {
  background: rgba(30, 41, 59, 0.8);
  padding: 14px 16px;
  text-align: left;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid rgba(59, 130, 246, 0.2);
  white-space: nowrap;
  position: sticky;
  top: 0;
  z-index: 10;
}

.premium-table td {
  padding: 12px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.03);
  color: #cbd5e1;
  transition: all 0.2s;
}

.vehicle-header-row .header-td {
  background: rgba(59, 130, 246, 0.15);
  border-bottom: 2px solid rgba(59, 130, 246, 0.3);
  padding: 10px 16px;
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.header-content .text {
  font-weight: 800;
  color: #60a5fa;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-size: 14px;
}

.premium-table tr:hover td:not(.header-td) {
  background: rgba(59, 130, 246, 0.05);
  color: #fff;
}

.location-link :deep(a) {
  color: #60a5fa;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.2s;
}

.location-link :deep(a:hover) {
  color: #93c5fd;
  text-decoration: underline;
}

.no-data-row td {
  padding: 40px;
  color: #64748b;
  font-style: italic;
  text-align: center;
}

.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>
