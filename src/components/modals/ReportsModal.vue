<template>
  <div class="modal-backdrop" @click.self="$emit('close')">
    <div class="modal-container ripple-bg">
      <!-- Header -->
      <div class="modal-header">
        <div class="header-content">
          <svg class="header-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
          </svg>
          <h2 class="modal-title">Reports</h2>
        </div>
        <button class="close-btn" @click="$emit('close')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <!-- Sidebar -->
        <div class="modal-sidebar">
          <button 
            class="sidebar-tab" 
            :class="{ active: activeTab === 'reports' }" 
            @click="activeTab = 'reports'"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="3" y1="9" x2="21" y2="9"></line>
              <line x1="9" y1="21" x2="9" y2="9"></line>
            </svg>
            Reports
          </button>
          <button 
            class="sidebar-tab" 
            :class="{ active: activeTab === 'generated' }" 
            @click="activeTab = 'generated'"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            </svg>
            Generated
          </button>
        </div>

        <!-- Main Content -->
        <div class="modal-content">
          <!-- Search and Controls -->
          <div class="content-controls">
            <div class="search-wrapper">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input type="text" :placeholder="activeTab === 'reports' ? 'Search reports...' : 'Search generated...'" v-model="searchQuery" class="search-input" />
            </div>
            <div class="action-group" v-if="activeTab === 'reports'">
               <button class="action-btn add-btn" @click="showAddReport = true">
                 <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                   <line x1="12" y1="5" x2="12" y2="19"></line>
                   <line x1="5" y1="12" x2="19" y2="12"></line>
                 </svg>
                 Add Report
               </button>
            </div>
          </div>

          <!-- Table Wrapper -->
          <div class="table-container custom-scrollbar">
            <table class="premium-table">
              <thead>
                <tr v-if="activeTab === 'reports'">
                  <th width="40"><input type="checkbox" /></th>
                  <th class="sortable" @click="handleSort('name')">
                    Name <span class="sort-icon" v-if="sorting.reports.by === 'name'">{{ sorting.reports.order === 'asc' ? '↑' : '↓' }}</span>
                  </th>
                  <th class="sortable" @click="handleSort('type')">
                    Type <span class="sort-icon" v-if="sorting.reports.by === 'type'">{{ sorting.reports.order === 'asc' ? '↑' : '↓' }}</span>
                  </th>
                  <th class="sortable" @click="handleSort('format')">
                    Format <span class="sort-icon" v-if="sorting.reports.by === 'format'">{{ sorting.reports.order === 'asc' ? '↑' : '↓' }}</span>
                  </th>
                  <th>Objects</th>
                  <th width="80">Actions</th>
                </tr>
                <tr v-else>
                  <th width="40"><input type="checkbox" /></th>
                  <th class="sortable" @click="handleSort('dt_report')">
                    Date <span class="sort-icon" v-if="sorting.generated.by === 'dt_report'">{{ sorting.generated.order === 'asc' ? '↑' : '↓' }}</span>
                  </th>
                  <th class="sortable" @click="handleSort('name')">
                    Name <span class="sort-icon" v-if="sorting.generated.by === 'name'">{{ sorting.generated.order === 'asc' ? '↑' : '↓' }}</span>
                  </th>
                  <th class="sortable" @click="handleSort('type')">
                    Type <span class="sort-icon" v-if="sorting.generated.by === 'type'">{{ sorting.generated.order === 'asc' ? '↑' : '↓' }}</span>
                  </th>
                  <th class="sortable" @click="handleSort('format')">
                    Format <span class="sort-icon" v-if="sorting.generated.by === 'format'">{{ sorting.generated.order === 'asc' ? '↑' : '↓' }}</span>
                  </th>
                  <th width="80">Actions</th>
                </tr>
              </thead>
              <tbody>
                <template v-if="activeTab === 'reports'">
                  <tr v-for="report in filteredReports" :key="report.report_id" class="table-row">
                    <td><input type="checkbox" /></td>
                    <td>
                      <div class="report-name">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                          <polyline points="13 2 13 9 20 9"></polyline>
                        </svg>
                        {{ report.name }}
                      </div>
                    </td>
                    <td><span class="type-badge">{{ report.type }}</span></td>
                    <td>{{ (report.format || '').toUpperCase() }}</td>
                    <td>{{ (report.imei ? report.imei.split(',').length : 0) }}</td>
                    <td>
                      <div class="row-actions">
                        <button class="icon-btn edit-btn" title="Edit" @click="editReport(report)">
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                          </svg>
                        </button>
                        <button class="icon-btn delete-btn" title="Delete" @click="deleteReport(report.report_id)">
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>
                <template v-else>
                  <tr v-for="gen in filteredGenerated" :key="gen.report_id" class="table-row">
                    <td><input type="checkbox" /></td>
                    <td>{{ formatDate(gen.dt_report) }}</td>
                    <td>
                      <div class="report-name">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        </svg>
                        {{ gen.name }}
                      </div>
                    </td>
                    <td><span class="type-badge">{{ gen.type }}</span></td>
                    <td>{{ (gen.format || '').toUpperCase() }}</td>
                    <td>
                      <div class="row-actions">
                        <button class="icon-btn edit-btn" title="Download" v-if="gen.report_file" @click="downloadFile(gen.report_file)">
                           <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                          </svg>
                        </button>
                        <button class="icon-btn delete-btn" title="Delete" @click="deleteGenerated(gen.report_id)">
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>

                <tr v-if="isLoading" class="table-row">
                  <td colspan="6" class="no-data">Loading reports...</td>
                </tr>
                <tr v-else-if="activeTab === 'reports' ? !filteredReports.length : !filteredGenerated.length">
                  <td colspan="6" class="no-data">No reports found</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination / Footer -->
          <div class="modal-footer">
            <div class="footer-stats">
              <span class="total-text">Total records: {{ activeTab === 'reports' ? pagination.reports.total : pagination.generated.total }}</span>
              <div class="per-page-wrapper">
                <span class="view-label">View</span>
                <select 
                  class="per-page-select" 
                  v-model="currentPerPage"
                  @change="handlePerPageChange"
                >
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                </select>
              </div>
            </div>
            <div class="pagination">
               <button class="p-btn" @click="handlePageChange(1)" :disabled="activeTab === 'reports' ? pagination.reports.current === 1 : pagination.generated.current === 1">&laquo;</button>
               <button class="p-btn" @click="handlePageChange((activeTab === 'reports' ? pagination.reports.current : pagination.generated.current) - 1)" :disabled="activeTab === 'reports' ? pagination.reports.current === 1 : pagination.generated.current === 1">&lsaquo;</button>
               <span class="p-info">Page {{ activeTab === 'reports' ? pagination.reports.current : pagination.generated.current }} of {{ activeTab === 'reports' ? pagination.reports.last : pagination.generated.last }}</span>
               <button class="p-btn" @click="handlePageChange((activeTab === 'reports' ? pagination.reports.current : pagination.generated.current) + 1)" :disabled="activeTab === 'reports' ? pagination.reports.current === pagination.reports.last : pagination.generated.current === pagination.generated.last">&rsaquo;</button>
               <button class="p-btn" @click="handlePageChange(activeTab === 'reports' ? pagination.reports.last : pagination.generated.last)" :disabled="activeTab === 'reports' ? pagination.reports.current === pagination.reports.last : pagination.generated.current === pagination.generated.last">&raquo;</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Notifications -->
    <PremiumToast 
      v-if="toast.show" 
      :show="toast.show" 
      :message="toast.message" 
      :type="toast.type" 
      @close="toast.show = false" 
    />

    <ReportPropertiesModal 
      v-if="showAddReport" 
      :edit-data="editingReport"
      @close="closeReportModal" 
      @saved="onReportSaved"
      @generated="onReportGenerated"
    />



    <!-- Confirmation Modal -->
    <PremiumConfirm
      :show="confirmModal.show"
      :title="confirmModal.title"
      :message="confirmModal.message"
      :type="confirmModal.type"
      @confirm="confirmModal.onConfirm"
      @cancel="confirmModal.show = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import ReportPropertiesModal from './ReportPropertiesModal.vue';
import PremiumToast from '../common/PremiumToast.vue';
import PremiumConfirm from '../common/PremiumConfirm.vue';
import api from '../../services/api';

const activeTab = ref('reports');
const searchQuery = ref('');
const showAddAddReport = ref(false); // Fix potential typo or use existing
const showAddReport = ref(false);
const editingReport = ref(null);
const isLoading = ref(false);
const emit = defineEmits(['close']);
const router = useRouter();

// Pagination and Sorting State
const pagination = reactive({
  reports: { current: 1, total: 0, last: 1, perPage: 50 },
  generated: { current: 1, total: 0, last: 1, perPage: 50 }
});

const sorting = reactive({
  reports: { by: 'name', order: 'asc' },
  generated: { by: 'dt_report', order: 'desc' }
});

const reports = ref([]);
const generatedReports = ref([]);

const toast = reactive({
  show: false,
  message: '',
  type: 'success'
});

const confirmModal = reactive({
  show: false,
  title: '',
  message: '',
  type: 'warning',
  onConfirm: () => {}
});

const showToast = (message, type = 'success') => {
  toast.message = message;
  toast.type = type;
  toast.show = true;
};

const fetchReports = async () => {
  isLoading.value = true;
  try {
    const params = {
      page: pagination.reports.current,
      per_page: pagination.reports.perPage,
      sort_by: sorting.reports.by,
      sort_order: sorting.reports.order,
      search: searchQuery.value
    };
    const response = await api.get('/api/reports', { params });
    reports.value = response.data.data;
    pagination.reports.total = response.data.total;
    pagination.reports.last = response.data.last_page;
  } catch (error) {
    console.error('Failed to fetch reports:', error);
    showToast('Failed to load reports', 'error');
  } finally {
    isLoading.value = false;
  }
};

const fetchGeneratedReports = async () => {
  isLoading.value = true;
  try {
    const params = {
      page: pagination.generated.current,
      per_page: pagination.generated.perPage,
      sort_by: sorting.generated.by,
      sort_order: sorting.generated.order,
      search: searchQuery.value
    };
    const response = await api.get('/api/reports/generated', { params });
    generatedReports.value = response.data.data;
    pagination.generated.total = response.data.total;
    pagination.generated.last = response.data.last_page;
  } catch (error) {
    console.error('Failed to fetch generated reports:', error);
    showToast('Failed to load generated reports', 'error');
  } finally {
    isLoading.value = false;
  }
};

const currentPerPage = computed({
  get: () => activeTab.value === 'reports' ? pagination.reports.perPage : pagination.generated.perPage,
  set: (val) => {
    if (activeTab.value === 'reports') pagination.reports.perPage = val;
    else pagination.generated.perPage = val;
  }
});

const handlePerPageChange = () => {
  const currentPag = activeTab.value === 'reports' ? pagination.reports : pagination.generated;
  currentPag.current = 1; // Reset to page 1
  refreshActiveTab();
};

const handleSort = (field) => {
  const currentSorting = activeTab.value === 'reports' ? sorting.reports : sorting.generated;
  if (currentSorting.by === field) {
    currentSorting.order = currentSorting.order === 'asc' ? 'desc' : 'asc';
  } else {
    currentSorting.by = field;
    currentSorting.order = field === 'dt_report' ? 'desc' : 'asc';
  }
  refreshActiveTab();
};

const handlePageChange = (newPage) => {
  const currentPag = activeTab.value === 'reports' ? pagination.reports : pagination.generated;
  if (newPage < 1 || newPage > currentPag.last) return;
  currentPag.current = newPage;
  refreshActiveTab();
};

const refreshActiveTab = () => {
  if (activeTab.value === 'reports') {
    fetchReports();
  } else {
    fetchGeneratedReports();
  }
};

const editReport = (report) => {
  editingReport.value = { ...report };
  showAddReport.value = true;
};

const closeReportModal = () => {
  showAddReport.value = false;
  editingReport.value = null;
};

const onReportSaved = (message) => {
  showToast(message || 'Report saved successfully');
  fetchReports();
};

const showReportViewer = ref(false);
const viewerData = reactive({ type: '', data: [], key: '' });

const onReportGenerated = (payload) => {
  const rKey = (payload.keys && payload.keys.length) ? payload.keys : (payload.key || '');
  const keyStr = Array.isArray(rKey) ? rKey.join(',') : rKey;
  router.push({
    path: '/reports/viewer',
    query: { type: payload.type, key: keyStr },
    state: { reportData: payload.data || [] }
  });
  emit('close');
};

const deleteReport = (id) => {
  if (!id) return;
  
  confirmModal.title = 'Delete Template';
  confirmModal.message = 'Are you sure you want to delete this report template? This action cannot be undone.';
  confirmModal.type = 'danger';
  confirmModal.onConfirm = async () => {
    confirmModal.show = false;
    try {
      await api.delete(`/api/reports/${id}`);
      showToast('Report deleted successfully');
      fetchReports();
    } catch (error) {
      console.error('Failed to delete report:', error);
      showToast('Error deleting report', 'error');
    }
  };
  confirmModal.show = true;
};

const deleteGenerated = (id) => {
  if (!id) return;
  
  confirmModal.title = 'Delete Generated Report';
  confirmModal.message = 'Are you sure you want to delete this generated report?';
  confirmModal.type = 'danger';
  confirmModal.onConfirm = async () => {
    confirmModal.show = false;
    try {
      await api.delete(`/api/reports/generated/${id}`);
      showToast('Generated report deleted successfully');
      fetchGeneratedReports();
    } catch (error) {
      console.error('Failed to delete generated report:', error);
      showToast('Error deleting generated report', 'error');
    }
  };
  confirmModal.show = true;
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleString();
};

const downloadFile = (filename) => {
  const url = `${api.defaults.baseURL}/storage/reports/${filename}`;
  window.open(url, '_blank');
};

let searchTimeout = null;
watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    const currentPag = activeTab.value === 'reports' ? pagination.reports : pagination.generated;
    currentPag.current = 1;
    refreshActiveTab();
  }, 300);
});

watch(activeTab, (newTab) => {
  searchQuery.value = '';
  refreshActiveTab();
});

onMounted(() => {
  fetchReports();
});

// Since we are doing server side filtering/sorting, filteredReports just returns reports.value
const filteredReports = computed(() => reports.value);
const filteredGenerated = computed(() => generatedReports.value);
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-container {
  width: 90%;
  max-width: 1000px;
  height: 80vh;
  background: var(--bg1);
  border: 1px solid var(--border);
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  animation: slideUp 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

@keyframes slideUp {
  from { transform: translateY(30px) scale(0.95); opacity: 0; }
  to { transform: translateY(0) scale(1); opacity: 1; }
}

/* Footer */
.modal-footer {
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.footer-stats {
  color: #64748b;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 20px;
}

.per-page-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
}

.per-page-select {
  background: var(--input-bg);
  border: 1px solid var(--border);
  border-radius: 6px;
  color: #cbd5e1;
  padding: 2px 8px;
  font-size: 12px;
  cursor: pointer;
  outline: none;
}

.per-page-select:focus {
  border-color: #3b82f6;
}

/* Header */
.modal-header {
  padding: 16px 24px;
  background: var(--card);
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-icon {
  color: #3b82f6;
}

.modal-title {
  font-size: 18px;
  font-weight: 800;
  color: var(--text);
  margin: 0;
  letter-spacing: -0.5px;
}

.close-btn {
  background: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 5px;
  border-radius: 8px;
  transition: all 0.2s;
}

.close-btn:hover {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

/* Body */
.modal-body {
  flex: 1;
  display: flex;
  overflow: hidden;
}

/* Sidebar */
.modal-sidebar {
  width: 200px;
  background: var(--card);
  border-right: 1px solid var(--border);
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.sidebar-tab {
  width: 100%;
  background: transparent;
  border: none;
  padding: 12px 16px;
  border-radius: 12px;
  color: #94a3b8;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

.sidebar-tab:hover {
  background: var(--border);
  color: var(--text);
}

.sidebar-tab.active {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

/* Content */
.modal-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 24px;
  overflow: hidden;
}

.content-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  gap: 16px;
}

.search-wrapper {
  position: relative;
  flex: 1;
  max-width: 400px;
}

.search-wrapper svg {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #475569;
}

.search-input {
  width: 100%;
  background: var(--input-bg);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 10px 16px 10px 42px;
  color: var(--text);
  font-size: 14px;
  outline: none;
  transition: all 0.2s;
}

.search-input:focus {
  border-color: #3b82f6;
  background: rgba(15, 23, 42, 0.6);
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.add-btn {
  background: #3b82f6;
  color: #fff;
  box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
}

.add-btn:hover {
  background: #2563eb;
  transform: translateY(-2px);
  box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.4);
}

/* Table */
.table-container {
  flex: 1;
  overflow-y: auto;
  border-radius: 16px;
  background: var(--input-bg);
  border: 1px solid var(--border);
}

.premium-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.premium-table th {
  background: var(--card);
  padding: 16px;
  text-align: left;
  font-weight: 700;
  color: var(--muted);
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 1px;
  position: sticky;
  top: 0;
  z-index: 5;
}

.premium-table th.sortable {
  cursor: pointer;
  transition: all 0.2s;
}

.premium-table th.sortable:hover {
  background: rgba(59, 130, 246, 0.1);
  color: #fff;
}

.sort-icon {
  display: inline-block;
  margin-left: 4px;
  color: #3b82f6;
  font-weight: 900;
}

.premium-table td {
  padding: 16px;
  color: var(--text);
  border-bottom: 1px solid var(--border);
}

.table-row:hover {
  background: var(--border);
}

.report-name {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  color: var(--text);
}

.type-badge {
  background: rgba(59, 130, 246, 0.1);
  color: #60a5fa;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
}

.row-actions {
  display: flex;
  gap: 8px;
}

.icon-btn {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.2s;
}

.edit-btn:hover { color: #3b82f6; background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.2); }
.delete-btn:hover { color: #ef4444; background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2); }

.no-data {
  text-align: center;
  padding: 40px !important;
  color: #64748b;
  font-style: italic;
}

/* Footer */
.modal-footer {
  margin-top: 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #64748b;
  font-size: 13px;
}

.pagination {
  display: flex;
  align-items: center;
  gap: 12px;
}

.p-btn {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  padding: 6px 12px;
  border-radius: 8px;
  color: #94a3b8;
  cursor: pointer;
  font-weight: 700;
}

.p-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
}

.p-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.p-info {
  font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
  .modal-container {
    width: 100%;
    height: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .modal-body {
    flex-direction: column;
    overflow-y: auto;
  }

  .modal-sidebar {
    width: 100%;
    border-right: none;
    border-bottom: 1px solid var(--border);
    flex-direction: row;
    padding: 8px;
    gap: 8px;
    overflow-x: auto;
    flex-shrink: 0;
  }

  .sidebar-tab {
    white-space: nowrap;
    padding: 8px 12px;
    font-size: 13px;
    flex: 1;
    justify-content: center;
  }

  .modal-content {
    padding: 16px;
  }

  .content-controls {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .search-wrapper {
    max-width: none;
  }

  .table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .premium-table {
    min-width: 700px;
  }

  .modal-footer {
    flex-direction: column;
    gap: 16px;
    align-items: center;
    text-align: center;
    padding-bottom: 24px;
  }

  .footer-stats {
    flex-direction: column;
    gap: 10px;
  }

  .pagination {
    gap: 8px;
  }

  .p-info {
    font-size: 12px;
  }

  .p-btn {
    padding: 8px 12px;
  }
}
</style>
