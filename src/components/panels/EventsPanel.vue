<template>
  <div class="events-panel" v-show="isPanelOpen">
    <!-- Top Search Bar -->
    <div class="panel-header-search">
      <div class="search-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" v-model="searchModel" placeholder="Search" />
      </div>
    </div>

    <!-- Data Table -->
    <div class="table-container custom-scrollbar">
      <table class="data-table">
        <thead>
          <tr>
            <th class="w-time sortable" @click="toggleSort('dt_tracker')">
              Time
              <svg 
                width="10" 
                height="10" 
                viewBox="0 0 24 24" 
                fill="none" 
                stroke="currentColor" 
                stroke-width="2" 
                class="sort-icon"
                :class="{ 'rotate-180': props.sortIdx === 'dt_tracker' && props.sortOrd === 'asc' }"
              >
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </th>
            <th>Object</th>
            <th>Event</th>
          </tr>
        </thead>

        <tbody>
          <tr v-if="loading">
            <td colspan="3" class="empty-table-msg">Loading events...</td>
          </tr>

          <tr v-else v-for="event in filteredEvents" :key="event.id" class="event-row">
            <td class="tabular-nums">{{ event.time }}</td>
            <td>{{ event.object }}</td>
            <td>{{ event.event }}</td>
          </tr>

          <tr v-if="!loading && filteredEvents.length === 0">
            <td colspan="3" class="empty-table-msg">No records to view</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer Action Bar -->
    <div class="panel-footer-actions">
      <div class="pagination">
        <button class="page-btn" :disabled="currentPageModel <= 1" @click="goToFirstPage">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="11 17 6 12 11 7"></polyline>
            <polyline points="18 17 13 12 18 7"></polyline>
          </svg>
        </button>

        <button class="page-btn" :disabled="currentPageModel <= 1" @click="goToPrevPage">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>

        <span class="page-text">Page</span>
        <input type="text" class="page-input" v-model="currentPageModel" />
        <span class="page-text">of {{ totalPages }}</span>

        <button class="page-btn" :disabled="currentPageModel >= totalPages" @click="goToNextPage">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>

        <button class="page-btn" :disabled="currentPageModel >= totalPages" @click="goToLastPage">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="13 17 18 12 13 7"></polyline>
            <polyline points="6 17 11 12 6 7"></polyline>
          </svg>
        </button>
      </div>

      <div class="right-actions">
        <select class="form-control select per-page" v-model="perPageModel">
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>

        <div class="menu-container dropdown-wrapper">
          <button class="action-btn" @click.stop="showMenu = !showMenu">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="5" cy="12" r="1"></circle>
              <circle cx="12" cy="12" r="1"></circle>
              <circle cx="19" cy="12" r="1"></circle>
            </svg>
          </button>

          <div class="custom-dropdown-list pop-upwards" v-if="showMenu" @click.stop>
            <div class="dropdown-item" @click="handleExport">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
              </svg>
              Export
            </div>

            <div class="dropdown-item text-danger" @click="handleDeleteAll">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2-2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
              Delete all events
            </div>
          </div>
        </div>

        <button class="action-btn" @click="handleRefresh">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="1 4 1 10 7 10"></polyline>
            <polyline points="23 20 23 14 17 14"></polyline>
            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  isPanelOpen: {
    type: Boolean,
    default: true
  },
  eventsList: {
    type: Array,
    default: () => []
  },
  searchQuery: {
    type: String,
    default: ''
  },
  currentPage: {
    type: Number,
    default: 1
  },
  totalPages: {
    type: Number,
    default: 1
  },
  perPage: {
    type: Number,
    default: 25
  },
  sortIdx: {
    type: String,
    default: 'dt_tracker'
  },
  sortOrd: {
    type: String,
    default: 'desc'
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits([
  'update:searchQuery',
  'update:currentPage',
  'update:perPage',
  'update:sortIdx',
  'update:sortOrd',
  'refresh-events',
  'delete-all-events',
  'export-events'
]);

const showMenu = ref(false);

const searchModel = computed({
  get: () => props.searchQuery,
  set: (val) => emit('update:searchQuery', val)
});

const currentPageModel = computed({
  get: () => props.currentPage,
  set: (val) => {
    const parsed = Number(val);
    if (Number.isNaN(parsed) || parsed < 1) {
      emit('update:currentPage', 1);
      return;
    }
    emit('update:currentPage', parsed);
  }
});

const perPageModel = computed({
  get: () => props.perPage,
  set: (val) => emit('update:perPage', Number(val))
});

const filteredEvents = computed(() => {
  if (!searchModel.value) return props.eventsList;

  return props.eventsList.filter(e =>
    (e.object || '').toLowerCase().includes(searchModel.value.toLowerCase()) ||
    (e.event || '').toLowerCase().includes(searchModel.value.toLowerCase())
  );
});

const handleClickOutside = (e) => {
  if (!e.target.closest('.dropdown-wrapper')) {
    showMenu.value = false;
  }
};

function goToFirstPage() {
  if (currentPageModel.value > 1) {
    currentPageModel.value = 1;
  }
}

function goToPrevPage() {
  if (currentPageModel.value > 1) {
    currentPageModel.value = currentPageModel.value - 1;
  }
}

function goToNextPage() {
  if (currentPageModel.value < props.totalPages) {
    currentPageModel.value = currentPageModel.value + 1;
  }
}

function goToLastPage() {
  if (currentPageModel.value < props.totalPages) {
    currentPageModel.value = props.totalPages;
  }
}

function toggleSort(col) {
  if (props.sortIdx === col) {
    emit('update:sortOrd', props.sortOrd === 'desc' ? 'asc' : 'desc');
  } else {
    emit('update:sortIdx', col);
    emit('update:sortOrd', 'desc');
  }
}

function handleRefresh() {
  emit('refresh-events');
}

function handleDeleteAll() {
  emit('delete-all-events');
  showMenu.value = false;
}

function handleExport() {
  emit('export-events');
  showMenu.value = false;
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.events-panel {
  display: flex;
  flex-direction: column;
  height: calc(100% - 45px);
  background: var(--card);
}

.panel-header-search {
  padding: 12px 15px;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}

.search-box {
  position: relative;
  width: 100%;
}

.search-box svg {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
}

.search-box input {
  width: 100%;
  padding: 9px 12px 9px 36px;
  background: var(--input-bg);
  border: 1px solid var(--border);
  border-radius: 6px;
  color: var(--text);
  font-size: 13px;
  outline: none;
  transition: all 0.2s;
  box-sizing: border-box;
}

.search-box input:focus {
  border-color: var(--accent);
  background: rgba(0, 0, 0, 0.3);
}

.table-container {
  flex: 1;
  overflow-y: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.data-table th {
  position: sticky;
  top: 0;
  background: var(--card);
  padding: 10px 15px;
  text-align: left;
  font-weight: 500;
  color: var(--muted);
  border-bottom: 1px solid var(--border);
  z-index: 10;
  white-space: nowrap;
}

.data-table td {
  padding: 10px 15px;
  border-bottom: 1px solid var(--border);
  color: var(--text);
}

.w-time {
  width: 80px;
}

.tabular-nums {
  font-variant-numeric: tabular-nums;
  color: var(--text);
}

.sort-icon {
  margin-left: 4px;
}

.event-row:hover td {
  background: var(--border);
  cursor: default;
}

.sortable {
  cursor: pointer;
}

.sortable:hover {
  background: var(--border) !important;
  color: var(--text) !important;
}

.rotate-180 {
  transform: rotate(180deg);
}

.empty-table-msg {
  text-align: center !important;
  padding: 30px !important;
  color: var(--muted) !important;
  font-style: italic;
}

.panel-footer-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 12px 10px;
  border-top: 1px solid var(--border);
  background: var(--card);
  flex-shrink: 0;
}

.pagination {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.page-btn {
  background: transparent;
  border: none;
  color: var(--muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
  border-radius: 4px;
}

.page-btn:hover:not(:disabled) {
  background: var(--border);
  color: var(--text);
}

.page-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.page-text {
  font-size: 13px;
  color: var(--muted);
}

.page-input {
  width: 32px;
  text-align: center;
  background: var(--input-bg);
  border: 1px solid var(--border);
  color: var(--text);
  border-radius: 4px;
  padding: 4px 0;
  font-size: 13px;
  outline: none;
}

.page-input:focus {
  border-color: var(--accent);
}

.form-control.select {
  appearance: none;
  -webkit-appearance: none;
  background-color: var(--input-bg);
  border: 1px solid var(--border);
  color: var(--text);
  border-radius: 4px;
  padding: 4px 22px 4px 8px;
  font-size: 13px;
  outline: none;
  background-image: url('data:image/svg+xml;utf8,<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="gray" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>');
  background-repeat: no-repeat;
  background-position: right 6px center;
  cursor: pointer;
  margin-left: 4px;
}

.right-actions {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-shrink: 0;
}

.action-btn {
  background: transparent;
  border: none;
  color: var(--muted);
  width: 28px;
  height: 28px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover {
  background: var(--border);
  color: var(--text);
}

.dropdown-wrapper {
  position: relative;
}

.custom-dropdown-list {
  position: absolute;
  right: 0;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 6px;
  min-width: 180px;
  z-index: 100;
  box-shadow: 0 10px 30px rgba(0,0,0,0.25);
  display: flex;
  flex-direction: column;
  padding: 4px 0;
}

.custom-dropdown-list.pop-upwards {
  bottom: 35px;
  top: auto;
}

.dropdown-item {
  display: flex;
  align-items: center;
  padding: 8px 15px;
  font-size: 13px;
  cursor: pointer;
  color: var(--text);
}

.dropdown-item:hover {
  background: var(--border);
}

.text-danger {
  color: #ef4444;
}

.mr-2 {
  margin-right: 8px;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.1);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255,255,255,0.2);
}
</style>