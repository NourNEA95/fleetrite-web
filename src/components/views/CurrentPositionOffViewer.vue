<template>
  <div class="reports-viewer-premium">
    <!-- Header -->
    <header class="viewer-header glass-morphism">
      <div class="header-left">
        <div class="report-meta">
          <h1 class="report-title-premium">Offline Status Report</h1>
        </div>
      </div>

      <div class="header-right">
        <div class="action-buttons">
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
        <button class="close-viewer" @click="$router.push('/reports')">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <main class="viewer-body-premium scroll-container custom-scrollbar">
      <CurrentPositionOffView :reportData="reportData" />
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import CurrentPositionOffView from './reports/CurrentPositionOffView.vue';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import 'jspdf-autotable';

const route = useRoute();
const reportData = ref({
  headers: [],
  rows: [],
  period: ''
});

onMounted(() => {
  if (history.state.reportData) {
    reportData.value = history.state.reportData;
  }
});

const stripHtml = (html) => {
  const tmp = document.createElement("DIV");
  tmp.innerHTML = html;
  return tmp.textContent || tmp.innerText || "";
};

const exportExcel = () => {
  const wsData = [
    reportData.value.headers.slice(1),
    ...reportData.value.rows.map(row => row.slice(1).map(cell => {
      if (typeof cell === 'string' && cell.includes('<a')) return stripHtml(cell);
      return cell;
    }))
  ];
  const ws = XLSX.utils.aoa_to_sheet(wsData);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Offline Report");
  XLSX.writeFile(wb, `Offline_Report_${new Date().getTime()}.xlsx`);
};

const exportPDF = () => {
  const doc = new jsPDF('l', 'mm', 'a4');
  doc.setFontSize(18);
  doc.text('Offline Fleet Status Report', 14, 22);
  doc.setFontSize(11);
  doc.setTextColor(100);
  doc.text(`Generated: ${new Date().toLocaleString()}`, 14, 30);

  doc.autoTable({
    head: [reportData.value.headers.slice(1)],
    body: reportData.value.rows.map(row => row.slice(1).map(cell => {
      if (typeof cell === 'string' && cell.includes('<a')) return stripHtml(cell);
      return cell;
    })),
    startY: 40,
    theme: 'grid',
    styles: { fontSize: 8, cellPadding: 2 },
    headStyles: { fillColor: [71, 85, 105], textColor: 255, fontStyle: 'bold' },
    alternateRowStyles: { fillColor: [248, 250, 252] }
  });

  doc.save(`Offline_Report_${new Date().getTime()}.pdf`);
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
    html += `<h2>Offline Status Report</h2>`;
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
    a.download = `Offline_Report_${new Date().getTime()}.html`;
    a.click();
    URL.revokeObjectURL(url);
};
</script>

<style scoped>
.reports-viewer-premium {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: #f8fafc;
  z-index: 2000;
  display: flex;
  flex-direction: column;
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(1.02); }
  to { opacity: 1; transform: scale(1); }
}

.glass-morphism {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}

.viewer-header {
  height: 80px;
  min-height: 80px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 40px;
  box-shadow: 0 4px 20px -5px rgba(0, 0, 0, 0.05);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 24px;
}

.back-btn-premium {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f1f5f9;
  border: none;
  padding: 10px 20px;
  border-radius: 12px;
  color: #475569;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.back-btn-premium:hover {
  background: #e2e8f0;
  transform: translateX(-4px);
}

.v-divider {
  width: 1px;
  height: 32px;
  background: #e2e8f0;
}

.report-title-premium {
  font-size: 18px;
  font-weight: 900;
  color: #1e293b;
  margin: 0;
  letter-spacing: -0.5px;
}

.report-tag-premium {
  font-size: 11px;
  font-weight: 800;
  color: #dc2626;
  text-transform: uppercase;
  letter-spacing: 1.5px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 24px;
}

.action-buttons {
  display: flex;
  gap: 12px;
}

.btn-secondary {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #fff;
  border: 1px solid #e2e8f0;
  padding: 10px 20px;
  border-radius: 12px;
  color: #475569;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.close-viewer {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  transition: all 0.2s;
}

.close-viewer:hover {
  background: #fee2e2;
  color: #ef4444;
  transform: rotate(90deg);
}

.viewer-body-premium {
  flex: 1;
  overflow-y: auto;
  position: relative;
}

.custom-scrollbar::-webkit-scrollbar { width: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f8fafc; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; border: 3px solid #f8fafc; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>
