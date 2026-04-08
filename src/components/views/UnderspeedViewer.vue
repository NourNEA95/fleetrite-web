<template>
  <div class="viewer-page-wrapper glass-effect custom-scrollbar">
    <!-- Header -->
    <div class="viewer-header">
      <div class="header-left">
        <div class="report-icon-bg">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
          </svg>
        </div>
        <div class="header-info">
          <h2 class="report-title">
            <span v-if="language === 'en'">Underspeed Report</span>
            <span v-else>تقرير السرعة المنخفضة</span>
          </h2>
          <div class="report-meta">
             <div class="meta-item">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
              <span>{{ dtf }} - {{ dtt }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="header-right">
         <!-- Language Toggle -->
         <button class="lang-toggle-btn" @click="toggleLanguage">
            <span class="lang-icon">🌐</span>
            <span class="lang-text">{{ language === 'en' ? 'Arabic' : 'English' }}</span>
          </button>

          <!-- Export Actions -->
          <div class="export-group">
            <button class="export-btn excel" @click="exportToExcel">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
              </svg>
              <span>Excel</span>
            </button>
            <button class="export-btn pdf" @click="exportToPDF">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
              </svg>
              <span>PDF</span>
            </button>
             <button class="export-btn html" @click="exportToHTML">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="16 18 22 12 16 6"></polyline>
                <polyline points="8 6 2 12 8 18"></polyline>
              </svg>
              <span>HTML</span>
            </button>
          </div>

          <button class="close-btn" @click="$router.push('/')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
      </div>
    </div>

    <!-- Main View Section -->
    <div class="viewer-body">
      <UnderspeedView 
        :headers="translatedHeaders" 
        :rows="rows" 
      />
    </div>

    <PremiumToast v-if="toast.show" :message="toast.message" :type="toast.type" @close="toast.show = false" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import 'jspdf-autotable';
import UnderspeedView from './reports/UnderspeedView.vue';
import PremiumToast from '../common/PremiumToast.vue';

const router = useRouter();
const language = ref('en');

const dtf = ref('');
const dtt = ref('');
const headers = ref([]);
const rows = ref([]);

const toast = reactive({ show: false, message: '', type: 'success' });
const showToast = (msg, type = 'success') => {
  toast.message = msg;
  toast.type = type;
  toast.show = true;
};

const toggleLanguage = () => {
  language.value = language.value === 'en' ? 'ar' : 'en';
};

const translatedHeaders = computed(() => {
  if (language.value === 'en') return headers.value;
  
  const mapping = {
    '#': 'م',
    'Start': 'بداية',
    'End': 'نهاية',
    'Duration': 'المدة',
    'Top speed': 'أعلى سرعة',
    'Average speed': 'متوسط السرعة',
    'Underspeed position': 'موقع السرعة المنخفضة',
    'Driver': 'السائق'
  };
  
  return headers.value.map(h => mapping[h] || h);
});

onMounted(() => {
  const state = window.history.state;
  if (!state || !state.reportData) {
    router.push('/');
    return;
  }
  
  const data = state.reportData;
  headers.value = data.headers || [];
  rows.value = data.rows || [];
  dtf.value = state.params?.from || '';
  dtt.value = state.params?.to || '';
});

const exportToExcel = () => {
  const ws = XLSX.utils.aoa_to_sheet([headers.value, ...rows.value.map(row => row.map(cell => cell.toString().replace(/<[^>]*>/g, '')))]);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Underspeed Report");
  XLSX.writeFile(wb, `underspeed_report_${new Date().getTime()}.xlsx`);
  showToast('Excel Export Successful');
};

const exportToPDF = () => {
   const doc = new jsPDF('p', 'pt');
   const tableRows = rows.value.map(row => row.map(cell => cell.toString().replace(/<[^>]*>/g, '')));
   
   doc.autoTable({
     head: [headers.value],
     body: tableRows,
     styles: { fontSize: 8 },
     margin: { top: 40 }
   });
   
   doc.save(`underspeed_report_${new Date().getTime()}.pdf`);
   showToast('PDF Export Successful');
};

const exportToHTML = () => {
  let html = `<html><head><title>Underspeed Report</title><style>table { width:100%; border-collapse: collapse; } th, td { border: 1px solid #ccc; padding: 8px; text-align: left; } th { background: #f4f4f4; }</style></head><body>`;
  html += `<h2>Underspeed Report (${dtf.value} to ${dtt.value})</h2>`;
  html += `<table><thead><tr>${headers.value.map(h => `<th>${h}</th>`).join('')}</tr></thead><tbody>`;
  rows.value.forEach(row => {
    html += `<tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>`;
  });
  html += `</tbody></table></body></html>`;
  
  const blob = new Blob([html], { type: 'text/html' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `underspeed_report_${new Date().getTime()}.html`;
  a.click();
  showToast('HTML Export Successful');
};
</script>

<style scoped>
.viewer-page-wrapper {
  position: fixed;
  inset: 20px;
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(24px) saturate(180%);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 24px;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  animation: modalEnter 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalEnter {
  from { opacity: 0; transform: scale(0.95) translateY(20px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.viewer-header {
  padding: 24px 32px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(15, 23, 42, 0.3);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.report-icon-bg {
  width: 52px;
  height: 52px;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(37, 99, 235, 0.4));
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #60a5fa;
  box-shadow: 0 8px 16px rgba(59, 130, 246, 0.2);
}

.report-title {
  font-size: 24px;
  font-weight: 800;
  color: #fff;
  letter-spacing: -0.5px;
  margin: 0;
}

.report-meta {
  display: flex;
  gap: 16px;
  margin-top: 4px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.03);
  padding: 4px 10px;
  border-radius: 6px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.lang-toggle-btn {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 8px 16px;
  border-radius: 10px;
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.lang-toggle-btn:hover { background: rgba(255, 255, 255, 0.1); }

.export-group {
  display: flex;
  gap: 8px;
  background: rgba(0, 0, 0, 0.2);
  padding: 6px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.export-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.export-btn.excel { background: rgba(34, 197, 94, 0.1); color: #4ade80; }
.export-btn.pdf { background: rgba(239, 68, 68, 0.1); color: #f87171; }
.export-btn.html { background: rgba(59, 130, 246, 0.1); color: #60a5fa; }

.export-btn:hover { transform: translateY(-2px); filter: brightness(1.2); }

.close-btn {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.05);
  border: none;
  color: #94a3b8;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.close-btn:hover { background: rgba(239, 68, 68, 0.1); color: #f87171; }

.viewer-body {
  flex: 1;
  padding: 32px;
  overflow-y: auto;
}

.glass-effect {
  background: rgba(15, 23, 42, 0.8);
  backdrop-filter: blur(20px);
}

.custom-scrollbar::-webkit-scrollbar { width: 8px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>
