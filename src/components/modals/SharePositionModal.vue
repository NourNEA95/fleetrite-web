<template>
  <div class="share-position-backdrop" @click.self="$emit('close')">
    <div class="share-position-container">
      <div class="share-position-header">
        <h2 class="modal-title">Share position</h2>
        <button class="close-btn" @click="$emit('close')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="18 6 6 18"></polyline>
            <polyline points="6 6 18 18"></polyline>
          </svg>
        </button>
      </div>

      <div class="share-position-body">
        
        <div class="toolbar">
          <div class="search-box">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" v-model="searchQuery" placeholder="Search" />
          </div>
        </div>

        <div class="table-container">
          <table class="share-table">
            <thead>
              <tr>
                <th style="width: 40px; text-align: center;">
                  <input type="checkbox" class="modern-checkbox" @change="toggleAll" :checked="isAllSelected" />
                </th>
                <th>Name</th>
                <th>E-mail</th>
                <th>Phone</th>
                <th>Objects</th>
                <th>Active</th>
                <th>Expires on</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredShares" :key="item.id" @click="editShare(item)">
                <td style="text-align: center;" @click.stop>
                  <input type="checkbox" class="modern-checkbox" v-model="selectedShares" :value="item.id" />
                </td>
                <td>{{ item.name }}</td>
                <td>{{ item.email }}</td>
                <td>{{ item.phone }}</td>
                <td>{{ item.objects ? item.objects.join(', ') : '' }}</td>
                <td><input type="checkbox" disabled :checked="item.active" class="modern-checkbox" /></td>
                <td>{{ item.expiresOn }}</td>
              </tr>
              <tr v-if="filteredShares.length === 0">
                <td colspan="7" class="empty-table-msg">No records to view</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="action-bar-footer">
          <div class="left-actions">
            <button class="action-btn btn-add" @click="addShare">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </button>
            <button class="action-btn" @click="refreshShares">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><polyline points="23 20 23 14 17 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>
            </button>
            
            <div class="menu-container">
              <button class="action-btn" @click="showMenu = !showMenu">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
              </button>
              <div class="dropdown-menu" v-if="showMenu">
                <div class="menu-item text-danger" @click="deleteSelected">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                  Delete
                </div>
              </div>
            </div>
          </div>
          
          <div class="pagination">
            <button class="page-btn" disabled>&laquo;</button>
            <button class="page-btn" disabled>&lsaquo;</button>
            <span>Page</span>
            <input type="text" class="page-input" value="1" disabled />
            <span>of 1</span>
            <button class="page-btn" disabled>&rsaquo;</button>
            <button class="page-btn" disabled>&raquo;</button>
            
            <select class="form-control select per-page">
              <option value="50">50</option>
            </select>
          </div>
          
          <div class="records-info">
            {{ filteredShares.length === 0 ? 'No records to view' : `1 - ${filteredShares.length} of ${filteredShares.length}` }}
          </div>
        </div>

      </div>

    </div>

    <!-- Nested Properties Modal -->
    <SharePositionPropertiesModal 
      v-if="showPropertiesModal"
      :share="editingShare"
      :vehicle="vehicle"
      @close="showPropertiesModal = false"
      @save="saveShare"
    />

  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import SharePositionPropertiesModal from './SharePositionPropertiesModal.vue';

const props = defineProps({
  vehicle: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close']);

const searchQuery = ref('');
const shares = ref([]); // Data from API
const selectedShares = ref([]);
const showMenu = ref(false);

const showPropertiesModal = ref(false);
const editingShare = ref(null);

const filteredShares = computed(() => {
  if (!searchQuery.value) return shares.value;
  return shares.value.filter(s => s.name?.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

const isAllSelected = computed(() => {
  return filteredShares.value.length > 0 && selectedShares.value.length === filteredShares.value.length;
});

function toggleAll(e) {
  if (e.target.checked) {
    selectedShares.value = filteredShares.value.map(s => s.id);
  } else {
    selectedShares.value = [];
  }
}

function addShare() {
  editingShare.value = null;
  showPropertiesModal.value = true;
}

function editShare(share) {
  editingShare.value = share;
  showPropertiesModal.value = true;
}

function saveShare(share) {
  const index = shares.value.findIndex(s => s.id === share.id);
  if (index > -1) {
    shares.value[index] = share;
  } else {
    shares.value.push(share);
  }
  showPropertiesModal.value = false;
}

function deleteSelected() {
  if (selectedShares.value.length === 0) return;
  shares.value = shares.value.filter(s => !selectedShares.value.includes(s.id));
  selectedShares.value = [];
  showMenu.value = false;
}

function refreshShares() {
  selectedShares.value = [];
  // Re-fetch logic here
}
</script>

<style scoped>
.share-position-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(2px);
  z-index: 99998; /* Below properties modal */
  display: flex;
  align-items: center;
  justify-content: center;
}

.share-position-container {
  background: #0f1b33;
  width: 90vw;
  max-width: 900px;
  height: 80vh;
  max-height: 700px;
  border-radius: 12px;
  border: 1px solid var(--border);
  box-shadow: 0 25px 50px rgba(0,0,0,0.7);
  display: flex;
  flex-direction: column;
}

.share-position-header {
  height: 60px;
  padding: 0 25px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--border);
}

.modal-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--accent);
}

.close-btn {
  background: transparent;
  border: none;
  color: var(--muted);
  cursor: pointer;
  padding: 5px;
}

.close-btn:hover { color: #ef4444; }

.share-position-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 20px;
  overflow: hidden;
}

.toolbar {
  margin-bottom: 15px;
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
  padding: 10px 10px 10px 35px;
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  color: white;
  outline: none;
  box-sizing: border-box;
}
.search-box input:focus { border-color: var(--accent); }

.table-container {
  flex: 1;
  overflow-y: auto;
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 6px;
  background: rgba(0,0,0,0.15);
}

.table-container::-webkit-scrollbar { width: 6px; }
.table-container::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 4px; }
.table-container::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

.share-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.share-table th {
  background: rgba(255, 255, 255, 0.05);
  padding: 12px 15px;
  text-align: left;
  font-weight: 600;
  color: var(--muted);
  border-bottom: 1px solid rgba(255,255,255,0.05);
  position: sticky;
  top: 0;
  z-index: 10;
}

.share-table td {
  padding: 12px 15px;
  border-bottom: 1px solid rgba(255,255,255,0.02);
  color: var(--text);
}

.share-table tr:hover td {
  background: rgba(255, 255, 255, 0.03);
  cursor: pointer;
}

.empty-table-msg {
  text-align: center !important;
  padding: 30px !important;
  color: var(--muted) !important;
  font-style: italic;
}

.modern-checkbox {
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: var(--accent);
}

.action-bar-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 15px;
  margin-top: 15px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.left-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--text);
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover { background: rgba(255, 255, 255, 0.1); }
.btn-add { background: var(--accent); color: white; border-color: var(--accent); }
.btn-add:hover { background: #3b66df; }

.menu-container { position: relative; }
.dropdown-menu {
  position: absolute;
  bottom: 40px;
  left: 0;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 5px 0;
  min-width: 150px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.5);
  z-index: 100;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 15px;
  color: var(--text);
  font-size: 13px;
  cursor: pointer;
}
.menu-item:hover { background: rgba(255,255,255,0.05); }
.text-danger { color: #ef4444; }

.pagination {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--muted);
}
.page-btn {
  background: transparent;
  border: none;
  color: var(--muted);
  cursor: pointer;
  font-size: 16px;
}
.page-btn:disabled { opacity: 0.3; cursor: not-allowed; }
.page-input {
  width: 30px;
  text-align: center;
  background: rgba(0,0,0,0.2);
  border: 1px solid rgba(255,255,255,0.1);
  color: white;
  border-radius: 4px;
  padding: 2px;
}
.per-page {
  padding: 4px;
  background: rgba(0,0,0,0.2);
  border: 1px solid rgba(255,255,255,0.1);
  color: white;
  border-radius: 4px;
  margin-left: 10px;
}

.records-info {
  font-size: 12px;
  color: var(--muted);
}

@media (max-width: 768px) {
  .share-position-container {
    width: 100%;
    height: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .share-position-body {
    padding: 16px;
  }

  .table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .share-table {
    min-width: 800px;
  }

  .action-bar-footer {
    flex-direction: column;
    align-items: stretch;
    gap: 15px;
    text-align: center;
  }
  
  .left-actions {
    justify-content: center;
  }

  .pagination {
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
  }

  .records-info {
    order: -1;
  }
}
</style>
