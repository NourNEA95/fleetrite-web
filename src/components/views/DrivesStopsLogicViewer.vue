<template>
  <div class="viewer-layout" :dir="currentLang === 'ar' ? 'rtl' : 'ltr'">
    <!-- Top Bar -->
    <div class="top-bar glass-effect">
      <div class="top-left">
        <div class="icon-box">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
          </svg>
        </div>
        <div class="title-group">
          <h2>Drives and stops with logic sensors</h2>
          <span class="report-badge">Modular Report V2</span>
        </div>
      </div>

      <div class="top-right">
        <!-- Language -->
        <button class="nav-btn lang-btn" @click="toggleLang">
          {{ currentLang === 'en' ? 'العربية' : 'English' }}
        </button>

        <!-- Export Actions -->
        <div class="btn-group">
          <button class="export-btn excel" @click="exportExcel">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h8"/></svg>
            <span>Excel</span>
          </button>
          <button class="export-btn pdf" @click="exportPDF">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/></svg>
            <span>PDF</span>
          </button>
        </div>

        <button class="close-btn" @click="goBack">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
    </div>

    <!-- Scrollable Content Container -->
    <div class="viewer-body custom-scrollbar">
      <div v-if="reportData" class="content-wrapper pulse-entry">
        <DrivesStopsLogicView :reportData="reportData" />
      </div>
      
      <!-- Empty/Loading State -->
      <div v-else class="empty-placeholder">
        <div class="loader-anim"></div>
        <p>Awaiting report data synchronization...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import DrivesStopsLogicView from './reports/DrivesStopsLogicView.vue';
import * as XLSX from 'xlsx';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';

const router = useRouter();
const route = useRoute();

const reportData = ref(null);
const currentLang = ref('en');

onMounted(() => {
  if (history.state.reportData) {
    reportData.ref = history.state.reportData;
    // Actually assigned correctly below
    reportData.value = history.state.reportData;
  } else {
    // If no state, go back
    console.warn('No report data found in history state');
    // router.replace('/tracking');
  }
});

const toggleLang = () => {
  currentLang.value = currentLang.value === 'en' ? 'ar' : 'en';
};

const goBack = () => {
  router.back();
};

const exportExcel = () => {
  if (!reportData.value) return;
  const wb = XLSX.utils.book_new();
  const allData = [];

  // Helper to strip HTML tags from strings
  const stripHtml = (html) => {
    if (typeof html !== 'string') return html;
    return html.replace(/<[^>]*>?/gm, '');
  };

  // Helper to get subtotal values from summary
  const getSummaryValue = (vehicle, labelPart) => {
    if (!vehicle.summaryHeaders || !vehicle.summaryValues) return '-';
    const idx = vehicle.summaryHeaders.findIndex(h => h.toLowerCase().includes(labelPart.toLowerCase()));
    return idx !== -1 ? stripHtml(vehicle.summaryValues[idx]) : '-';
  };

  reportData.value.vehicles.forEach((vehicle, vIdx) => {
    // Add separator and name if multiple vehicles
    if (reportData.value.vehicles.length > 1) {
      if (vIdx > 0) allData.push([]); // Space before next vehicle
      allData.push([`VEHICLE: ${vehicle.name} (${vehicle.imei})`]);
    }

    // Header
    allData.push(vehicle.headers);

    // Rows
    vehicle.rows.forEach(row => {
      let excelRow = row.map(cell => stripHtml(cell));
      
      // Alignment fix for Stopped rows
      if (excelRow[0]?.toLowerCase() === 'stopped' || excelRow[0] === 'متوقف') {
        // Metric data (Fuel etc) begins at index 5 in Stopped but index 7 in Moving.
        if (excelRow.length < vehicle.headers.length) {
          excelRow.splice(5, 0, "", "");
        }
      }
      allData.push(excelRow);
    });

    // Add UI Subtotal Row
    const subtotal = [
      "SUBTOTAL / UNIT SUMMARY", 
      "", "", "", 
      getSummaryValue(vehicle, 'Route length'), 
      getSummaryValue(vehicle, 'Top speed'), 
      getSummaryValue(vehicle, 'Average speed'), 
    ];
    // Dynamic sensors for subtotal
    for (let mIdx = 7; mIdx < vehicle.headers.length; mIdx++) {
       subtotal.push(getSummaryValue(vehicle, vehicle.headers[mIdx]));
    }
    allData.push(subtotal);

    // Add Summary card metrics at end of vehicle section
    allData.push([]);
    allData.push(["UNIT SUMMARY METRICS"]);
    vehicle.summaryHeaders.forEach((h, i) => {
      allData.push([h, stripHtml(vehicle.summaryValues[i])]);
    });
    allData.push([]); // Gap before next vehicle
  });

  const ws = XLSX.utils.aoa_to_sheet(allData);
  XLSX.utils.book_append_sheet(wb, ws, "Drives and Stops Report");
  XLSX.writeFile(wb, `Report_DrivesAndStopsLogic_${new Date().getTime()}.xlsx`);
};

const exportPDF = () => {
  const doc = new jsPDF('l', 'mm', 'a4');
  let y = 20;

  reportData.value.vehicles.forEach((vehicle, idx) => {
    if (idx > 0) doc.addPage();
    doc.setFontSize(16);
    doc.setTextColor(37, 99, 235);
    doc.text(`${vehicle.name} Report`, 14, 15);
    
    autoTable(doc, {
      head: [vehicle.headers],
      body: vehicle.rows,
      startY: 20,
      styles: { fontSize: 8, cellPadding: 2 },
      headStyles: { fillColor: [37, 99, 235] }
    });
  });

  doc.save(`Report_DrivesAndStopsLogic_${new Date().getTime()}.pdf`);
};
</script>

<style scoped>
.viewer-layout {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: #f8fafc;
  z-index: 9999;
  display: flex;
  flex-direction: column;
}

.top-bar {
  height: 80px;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px) saturate(180%);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  padding: 0 40px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  z-index: 10;
  box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}

.top-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.icon-box {
  width: 48px;
  height: 48px;
  background: #eff6ff;
  color: #3b82f6;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: inset 0 2px 4px rgba(59, 130, 246, 0.1);
}

.title-group h2 {
  font-size: 18px;
  font-weight: 800;
  color: #1e293b;
  margin: 0;
  letter-spacing: -0.2px;
}

.report-badge {
  font-size: 11px;
  font-weight: 700;
  color: #3b82f6;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.top-right {
  display: flex;
  align-items: center;
  gap: 24px;
}

.btn-group {
  display: flex;
  gap: 12px;
  background: #f1f5f9;
  padding: 6px;
  border-radius: 12px;
}

.nav-btn, .export-btn {
  padding: 8px 18px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.lang-btn {
  background: transparent;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

.lang-btn:hover { background: #fff; color: #1e293b; border-color: #3b82f6; }

.export-btn {
  background: #fff;
  color: #1e293b;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.export-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.export-btn.excel:hover { color: #166534; }
.export-btn.pdf:hover { color: #991b1b; }

.close-btn {
  background: #f1f5f9;
  color: #94a3b8;
  border: none;
  width: 44px;
  height: 44px;
  border-radius: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.close-btn:hover { background: #fee2e2; color: #ef4444; transform: rotate(90deg); }

.viewer-body {
  flex: 1;
  overflow-y: auto;
  position: relative;
}

.content-wrapper {
  max-width: 1440px;
  margin: 0 auto;
}

.pulse-entry {
  animation: pulse-in 0.6s cubic-bezier(0.2, 0, 0.2, 1);
}

@keyframes pulse-in {
  from { opacity: 0; transform: scale(0.98); }
  to { opacity: 1; transform: scale(1); }
}

.empty-placeholder {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
}

.loader-anim {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 8px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f8fafc; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; border: 2px solid #f8fafc; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
