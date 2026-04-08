<template>
  <div class="viewer-layout" :dir="currentLang === 'ar' ? 'rtl' : 'ltr'">
    <!-- Top Bar -->
    <div class="top-bar glass-effect">
      <div class="header-left">
        <div class="report-meta">
          <h1 class="report-title-premium">Current Position Report</h1>
        </div>
      </div>

      <div class="top-right">
        <!-- Language -->
        <button class="nav-btn lang-btn" @click="toggleLang">
          {{ currentLang === 'en' ? 'العربية' : 'English' }}
        </button>

        <!-- Export Actions -->
        <div class="btn-group">
          <button class="btn-secondary" @click="exportExcel">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Excel
          </button>
          <button class="btn-secondary" @click="exportPDF">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M16 13a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"/><path d="M12 17v1"/></svg>
            PDF
          </button>
          <button class="btn-secondary" @click="exportHTML">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            HTML
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
        <CurrentPositionView :reportData="reportData" />
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
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import CurrentPositionView from './reports/CurrentPositionView.vue';
import * as XLSX from 'xlsx';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';

const router = useRouter();
const reportData = ref(null);
const currentLang = ref('en');

onMounted(() => {
  if (history.state.reportData) {
    reportData.value = history.state.reportData;
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
  
  const wsData = [
    reportData.value.headers.slice(1),
    ...reportData.value.rows.map(row => row.slice(1).map(cell => stripHtml(cell)))
  ];

  const ws = XLSX.utils.aoa_to_sheet(wsData);
  XLSX.utils.book_append_sheet(wb, ws, "Current Position");
  XLSX.writeFile(wb, `Report_CurrentPosition_${new Date().getTime()}.xlsx`);
};

const exportPDF = () => {
  if (!reportData.value) return;
  const doc = new jsPDF('l', 'mm', 'a4');
  
  doc.setFontSize(18);
  doc.setTextColor(37, 99, 235);
  doc.text("Current Position Report", 14, 15);
  
  const stripHtml = (html) => {
    if (typeof html !== 'string') return html;
    return html.replace(/<[^>]*>?/gm, '');
  };

  const bodyData = reportData.value.rows.map(row => row.map(cell => stripHtml(cell)));

  autoTable(doc, {
    head: [reportData.value.headers],
    body: bodyData,
    startY: 20,
    styles: { fontSize: 8, cellPadding: 2 },
    headStyles: { fillColor: [37, 99, 235] }
  });

  doc.save(`Report_CurrentPosition_${new Date().getTime()}.pdf`);
};

const exportHTML = () => {
    let html = `<html><head><meta charset="utf-8">
        <style>
            table { width: 100%; border-collapse: collapse; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 10pt; }
            th { border: 1px solid #CCC; padding: 10pt 8pt; background: #64748b; color: #FFF; text-align: left; text-transform: uppercase; }
            td { border: 1px solid #EEE; padding: 10pt 8pt; color: #333; }
            tr:nth-child(even) { background: #f8fafc; }
            h2 { color: #1e293b; font-family: sans-serif; }
        </style>
    </head><body>`;
    html += `<h2>Current Position Report</h2>`;
    html += `<p>Generated: ${new Date().toLocaleString()}</p>`;
    html += `<table><thead><tr>`;
    reportData.value.headers.slice(1).forEach(h => html += `<th>${h}</th>`);
    html += `</tr></thead><tbody>`;
    reportData.value.rows.forEach(row => {
        html += `<tr>`;
        row.slice(1).forEach(cell => {
          html += `<td>${stripHtml(cell)}</td>`;
        });
        html += `</tr>`;
    });
    html += `</tbody></table></body></html>`;

    const blob = new Blob([html], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Report_CurrentPosition_${new Date().getTime()}.html`;
    a.click();
    URL.revokeObjectURL(url);
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

.custom-scrollbar::-webkit-scrollbar { width: 8px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f8fafc; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; border: 2px solid #f8fafc; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
