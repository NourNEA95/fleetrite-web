<template>
  <div class="modal-backdrop" @click.self="$emit('close')">
    <div class="modal-container">
      
      <!-- Top Header -->
      <div class="modal-header">
        <h2 class="modal-title">Object control</h2>
        <button class="close-btn" @click="$emit('close')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="18 6 6 18"></polyline>
            <polyline points="6 6 18 18"></polyline>
          </svg>
        </button>
      </div>

      <!-- Main Body: Sidebar + Content -->
      <div class="modal-body">
        
        <!-- Left Sidebar (Tabs) -->
        <div class="sidebar">
          <ul class="tab-list">
            <li v-for="tab in tabs" :key="tab.id" class="tab-item" :class="{ active: activeTab === tab.id }" @click="activeTab = tab.id">
              {{ tab.name }}
            </li>
          </ul>
        </div>

        <!-- Right Content Area -->
        <div class="content-area custom-scrollbar">
          
          <!-- GPRS Tab Content -->
          <div v-if="activeTab === 'gprs'" class="tab-pane">
            <div class="control-form">
              <div class="form-row">
                <div class="form-group half">
                  <label class="fixed-label">Object</label>
                  <div class="dropdown-wrapper form-control select" @click="showObjectDropdown = !showObjectDropdown">
                    <span class="dropdown-text">{{ selectedObjectsText }}</span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    
                    <div v-if="showObjectDropdown" class="custom-dropdown-list" @click.stop>
                      <div class="dropdown-search">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" v-model="objectSearch" placeholder="" class="obj-search-input" />
                      </div>
                      <label class="dropdown-item">
                        <input type="checkbox" @change="toggleAllObjects" :checked="isAllObjectsSelected" class="modern-checkbox" />
                        [Select all]
                      </label>
                      <label v-for="obj in filteredObjectsList" :key="obj.id" class="dropdown-item">
                        <input type="checkbox" v-model="selectedObjects" :value="obj.id" class="modern-checkbox" />
                        {{ obj.name }}
                      </label>
                    </div>
                  </div>
                </div>
                
                <div class="form-group half">
                  <label class="fixed-label">Template</label>
                  <select class="form-control select flex-1" v-model="gprsTemplate">
                    <option value="Custom">Custom</option>
                  </select>
                </div>
              </div>

              <div class="form-row mt-15">
                <div class="form-group full" style="display:flex; gap:10px; align-items:center;">
                  <label class="fixed-label">Command</label>
                  <select class="form-control select" style="width: 100px;" v-model="gprsCommandType">
                    <option value="ASCII">ASCII</option>
                    <option value="HEX">HEX</option>
                  </select>
                  <input type="text" class="form-control flex-1" v-model="gprsCommand" />
                  <button class="btn btn-default">Send</button>
                </div>
              </div>
            </div>

            <div class="table-container mt-20">
              <table class="data-table">
                <thead>
                  <tr>
                    <th style="width:30px;"><input type="checkbox" class="modern-checkbox" /></th>
                    <th style="width:180px;">Time <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left:5px;"><polyline points="6 9 12 15 18 9"></polyline></svg></th>
                    <th>Object</th>
                    <th>Name</th>
                    <th>Command</th>
                    <th style="width:80px; text-align:center;">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="record in gprsHistory" :key="record.id">
                    <td><input type="checkbox" class="modern-checkbox" /></td>
                    <td style="display:flex; align-items:center; gap:5px; font-variant-numeric: tabular-nums;">
                       <span style="opacity:0.5;">+</span> {{ record.time }}
                    </td>
                    <td>{{ record.object }}</td>
                    <td>{{ record.name }}</td>
                    <td>{{ record.command }}</td>
                    <td style="text-align:center; color: var(--accent);">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" v-if="record.status === 'ok'"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </td>
                  </tr>
                  <tr v-if="gprsHistory.length === 0">
                    <td colspan="6" class="empty-table-msg">No records to view</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="action-bar-footer">
              <div class="left-actions">
                <button class="action-btn" @click="refreshGprs">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><polyline points="23 20 23 14 17 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>
                </button>
                <div class="menu-container">
                  <button class="action-btn" @click="showGprsMenu = !showGprsMenu">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                  </button>
                  <div class="dropdown-menu" v-if="showGprsMenu">
                    <div class="menu-item text-danger">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2-2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Delete
                    </div>
                  </div>
                </div>
              </div>
              <div class="records-info">Total records: {{ gprsHistory.length }}</div>
            </div>
          </div>

          <!-- SMS Tab Content -->
          <div v-if="activeTab === 'sms'" class="tab-pane">
            <div class="control-form">
              <div class="form-row">
                <div class="form-group half">
                  <label class="fixed-label">Object</label>
                  <div class="dropdown-wrapper form-control select" @click="showObjectDropdown = !showObjectDropdown">
                    <span class="dropdown-text">{{ selectedObjectsText }}</span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </div>
                </div>
                <div class="form-group half">
                  <label class="fixed-label">Template</label>
                  <select class="form-control select flex-1" v-model="smsTemplate">
                    <option value="Custom">Custom</option>
                  </select>
                </div>
              </div>

              <div class="form-row mt-15">
                <div class="form-group full" style="display:flex; gap:10px; align-items:center;">
                  <label class="fixed-label">Command</label>
                  <select class="form-control select" style="width: 100px;" v-model="smsCommandType">
                    <option value="ASCII">ASCII</option>
                    <option value="HEX">HEX</option>
                  </select>
                  <input type="text" class="form-control flex-1" v-model="smsCommand" />
                  <button class="btn btn-default">Send</button>
                </div>
              </div>
            </div>

            <div class="table-container mt-20">
              <table class="data-table">
                <thead>
                  <tr>
                    <th style="width:30px;"><input type="checkbox" class="modern-checkbox" /></th>
                    <th style="width:180px;">Time <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left:5px;"><polyline points="6 9 12 15 18 9"></polyline></svg></th>
                    <th>Object</th>
                    <th>Name</th>
                    <th>Command</th>
                    <th style="width:80px; text-align:center;">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="smsHistory.length === 0">
                    <td colspan="6" class="empty-table-msg">No records to view</td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <div class="action-bar-footer">
              <div class="left-actions">
                <button class="action-btn" @click="refreshSms">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><polyline points="23 20 23 14 17 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>
                </button>
                <div class="menu-container">
                  <button class="action-btn" @click="showSmsMenu = !showSmsMenu">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                  </button>
                  <div class="dropdown-menu" v-if="showSmsMenu">
                    <div class="menu-item text-danger">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2-2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Delete
                    </div>
                  </div>
                </div>
              </div>
              <div class="records-info">Total records: {{ smsHistory.length }}</div>
            </div>
          </div>

          <!-- Schedule Tab Content -->
          <div v-if="activeTab === 'schedule'" class="tab-pane">
            <div class="table-header-bar">
              <div class="search-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" v-model="scheduleSearch" placeholder="Search" />
              </div>
            </div>

            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th style="width:40px;"><input type="checkbox" class="modern-checkbox" @change="toggleAllSchedules" :checked="isAllSchedulesSelected" /></th>
                    <th>Name <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left:5px;"><polyline points="18 15 12 9 6 15"></polyline></svg></th>
                    <th>Active</th>
                    <th>Schedule</th>
                    <th>Gateway</th>
                    <th>Type</th>
                    <th>Command</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in filteredSchedules" :key="item.id" @click="editSchedule(item)">
                    <td @click.stop><input type="checkbox" class="modern-checkbox" v-model="selectedSchedules" :value="item.id" /></td>
                    <td>{{ item.name }}</td>
                    <td><input type="checkbox" disabled :checked="item.active" class="modern-checkbox" /></td>
                    <td>{{ item.scheduleText }}</td>
                    <td>{{ item.gateway }}</td>
                    <td>{{ item.type }}</td>
                    <td>{{ item.command }}</td>
                  </tr>
                  <tr v-if="filteredSchedules.length === 0">
                    <td colspan="7" class="empty-table-msg">No records to view</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="action-bar-footer">
              <div class="left-actions">
                <button class="action-btn btn-add" @click="addSchedule">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </button>
                <button class="action-btn" @click="refreshSchedules">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><polyline points="23 20 23 14 17 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>
                </button>
                <div class="menu-container">
                  <button class="action-btn" @click="showScheduleMenu = !showScheduleMenu">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                  </button>
                  <div class="dropdown-menu" v-if="showScheduleMenu">
                    <div class="menu-item text-danger" @click="deleteSelectedSchedules">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2-2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Delete
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
              <div class="records-info">{{ filteredSchedules.length === 0 ? 'No records to view' : `1 - ${filteredSchedules.length} of ${filteredSchedules.length}` }}</div>
            </div>
          </div>

          <!-- Templates Tab Content -->
          <div v-if="activeTab === 'templates'" class="tab-pane">
            <div class="table-header-bar">
              <div class="search-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" v-model="templateSearch" placeholder="Search" />
              </div>
            </div>

            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th style="width:40px;"><input type="checkbox" class="modern-checkbox" @change="toggleAllTemplates" :checked="isAllTemplatesSelected" /></th>
                    <th>Name <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left:5px;"><polyline points="18 15 12 9 6 15"></polyline></svg></th>
                    <th>Protocol</th>
                    <th>Gateway</th>
                    <th>Type</th>
                    <th>Command</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in filteredTemplates" :key="item.id" @click="editTemplate(item)">
                    <td @click.stop><input type="checkbox" class="modern-checkbox" v-model="selectedTemplates" :value="item.id" /></td>
                    <td>{{ item.name }}</td>
                    <td>{{ item.protocol }}</td>
                    <td>{{ item.gateway }}</td>
                    <td>{{ item.type }}</td>
                    <td>{{ item.command }}</td>
                  </tr>
                  <tr v-if="filteredTemplates.length === 0">
                    <td colspan="6" class="empty-table-msg">No records to view</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="action-bar-footer">
              <div class="left-actions">
                <button class="action-btn btn-add" @click="addTemplate">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </button>
                <button class="action-btn" @click="refreshTemplates">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><polyline points="23 20 23 14 17 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>
                </button>
                <div class="menu-container">
                  <button class="action-btn" @click="showTemplateMenu = !showTemplateMenu">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                  </button>
                  <div class="dropdown-menu pop-upwards" v-if="showTemplateMenu">
                    <div class="menu-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> Import</div>
                    <div class="menu-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg> Export</div>
                    <div class="menu-item text-danger" @click="deleteSelectedTemplates">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2-2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Delete
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
              <div class="records-info">{{ filteredTemplates.length === 0 ? 'No records to view' : `1 - ${filteredTemplates.length} of ${filteredTemplates.length}` }}</div>
            </div>
          </div>


        </div>
      </div>
    </div>
    
    <!-- Nested Modals -->
    <SchedulePropertiesModal 
      v-if="showScheduleModal" 
      :schedule="editingSchedule" 
      :vehicle="vehicle"
      @close="showScheduleModal = false" 
      @save="saveSchedule" 
    />
    <TemplatePropertiesModal
      v-if="showTemplateModal"
      :templateData="editingTemplate"
      @close="showTemplateModal = false"
      @save="saveTemplate"
    />
  </div>
</template>


<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import SchedulePropertiesModal from './SchedulePropertiesModal.vue';
import TemplatePropertiesModal from './TemplatePropertiesModal.vue';

const props = defineProps({
  vehicle: {
    type: Object,
    default: () => null
  }
});

const emit = defineEmits(['close']);

// Tab Management
const tabs = [
  { id: 'gprs', name: 'GPRS' },
  { id: 'sms', name: 'SMS' },
  { id: 'schedule', name: 'Schedule' },
  { id: 'templates', name: 'Templates' }
];
const activeTab = ref('gprs');

// Common objects dropdown logic
const showObjectDropdown = ref(false);
const objectSearch = ref('');
const selectedObjects = ref([]);

const availableObjects = ref([
  { id: '1', name: 'Facility - 648' },
  { id: '2', name: '1/23035' },
  { id: '3', name: '12/62921' },
  { id: '4', name: '14/18182' },
  { id: '5', name: '15/40319' },
  { id: '6', name: '15/87124' },
  { id: '7', name: '21/35720' },
  { id: '8', name: '21/70902' },
  { id: '9', name: '22/69736' }
]);

const filteredObjectsList = computed(() => {
  if (!objectSearch.value) return availableObjects.value;
  return availableObjects.value.filter(o => o.name.toLowerCase().includes(objectSearch.value.toLowerCase()));
});

const isAllObjectsSelected = computed(() => {
  return filteredObjectsList.value.length > 0 && selectedObjects.value.length === filteredObjectsList.value.length;
});

const selectedObjectsText = computed(() => {
  if (selectedObjects.value.length === 0) return 'Nothing selected';
  if (selectedObjects.value.length === 1) {
    const obj = availableObjects.value.find(o => o.id === selectedObjects.value[0]);
    return obj ? obj.name : '1 selected';
  }
  return `${selectedObjects.value.length} selected`;
});

function toggleAllObjects(e) {
  if (e.target.checked) {
    selectedObjects.value = filteredObjectsList.value.map(o => o.id);
  } else {
    selectedObjects.value = [];
  }
}

const handleClickOutside = (e) => {
  const wrapper = document.querySelector('.dropdown-wrapper');
  if (wrapper && !wrapper.contains(e.target)) {
    showObjectDropdown.value = false;
  }
};

onMounted(() => {
  if (props.vehicle && props.vehicle.id) {
    selectedObjects.value = [props.vehicle.id]; // Select the opened vehicle by default
  }
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

// GPRS State
const gprsTemplate = ref('Custom');
const gprsCommandType = ref('ASCII');
const gprsCommand = ref('');
const showGprsMenu = ref(false);
const gprsHistory = ref([
  { id: 1, time: '2026-01-22 11:54:32', object: 'Custom', name: 'Custom', command: 'getparam 50390', status: 'ok' },
  { id: 2, time: '2026-01-22 11:52:04', object: 'Custom', name: 'Custom', command: 'setparam 11703:3', status: 'ok' },
  { id: 3, time: '2026-01-22 11:47:19', object: 'Custom', name: 'Custom', command: 'setparam 11703:1', status: 'ok' }
]);
function refreshGprs() {}

// SMS State
const smsTemplate = ref('Custom');
const smsCommandType = ref('ASCII');
const smsCommand = ref('');
const showSmsMenu = ref(false);
const smsHistory = ref([]);
function refreshSms() {}

// Schedule State
const schedules = ref([]);
const scheduleSearch = ref('');
const selectedSchedules = ref([]);
const showScheduleMenu = ref(false);
const showScheduleModal = ref(false);
const editingSchedule = ref(null);

const filteredSchedules = computed(() => {
  if (!scheduleSearch.value) return schedules.value;
  return schedules.value.filter(s => s.name?.toLowerCase().includes(scheduleSearch.value.toLowerCase()));
});
const isAllSchedulesSelected = computed(() => filteredSchedules.value.length > 0 && selectedSchedules.value.length === filteredSchedules.value.length);

function toggleAllSchedules(e) {
  if (e.target.checked) selectedSchedules.value = filteredSchedules.value.map(s => s.id);
  else selectedSchedules.value = [];
}
function addSchedule() {
  editingSchedule.value = null;
  showScheduleModal.value = true;
}
function editSchedule(item) {
  editingSchedule.value = item;
  showScheduleModal.value = true;
}
function saveSchedule(item) {
  const idx = schedules.value.findIndex(s => s.id === item.id);
  if (idx > -1) schedules.value[idx] = item;
  else schedules.value.push(item);
  showScheduleModal.value = false;
}
function deleteSelectedSchedules() {
  schedules.value = schedules.value.filter(s => !selectedSchedules.value.includes(s.id));
  selectedSchedules.value = [];
  showScheduleMenu.value = false;
}
function refreshSchedules() {}

// Templates State
const templates = ref([]);
const templateSearch = ref('');
const selectedTemplates = ref([]);
const showTemplateMenu = ref(false);
const showTemplateModal = ref(false);
const editingTemplate = ref(null);

const filteredTemplates = computed(() => {
  if (!templateSearch.value) return templates.value;
  return templates.value.filter(t => t.name?.toLowerCase().includes(templateSearch.value.toLowerCase()));
});
const isAllTemplatesSelected = computed(() => filteredTemplates.value.length > 0 && selectedTemplates.value.length === filteredTemplates.value.length);
function toggleAllTemplates(e) {
  if (e.target.checked) selectedTemplates.value = filteredTemplates.value.map(t => t.id);
  else selectedTemplates.value = [];
}

function addTemplate() {
  editingTemplate.value = null;
  showTemplateModal.value = true;
}

function editTemplate(item) {
  editingTemplate.value = item;
  showTemplateModal.value = true;
}

function saveTemplate(item) {
  const idx = templates.value.findIndex(t => t.id === item.id);
  if (idx > -1) templates.value[idx] = item;
  else templates.value.push(item);
  showTemplateModal.value = false;
}
function deleteSelectedTemplates() {
  templates.value = templates.value.filter(t => !selectedTemplates.value.includes(t.id));
  selectedTemplates.value = [];
  showTemplateMenu.value = false;
}
function refreshTemplates() {}

</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(2px);
  z-index: 99990;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-container {
  background: var(--card);
  width: 90vw;
  max-width: 950px;
  height: 85vh;
  max-height: 800px;
  border-radius: 12px;
  border: 1px solid var(--border);
  box-shadow: 0 25px 50px rgba(0,0,0,0.7);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.modal-header {
  height: 60px;
  padding: 0 25px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--border);
  background: rgba(0,0,0,0.1);
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

.modal-body {
  flex: 1;
  display: flex;
  overflow: hidden;
  position: relative;
}

/* Sidebar styling borrowed from EditObjectModal */
.sidebar {
  width: 200px;
  background: rgba(0, 0, 0, 0.2);
  border-right: 1px solid var(--border);
  height: 100%;
  overflow-y: auto;
  flex-shrink: 0;
}

.tab-list {
  list-style: none;
  margin: 0;
  padding: 15px 0;
  display: flex;
  flex-direction: column;
}

.tab-item {
  padding: 12px 25px;
  color: var(--muted);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  border-left: 3px solid transparent;
  transition: all 0.2s;
  white-space: nowrap;
}

.tab-item:hover {
  background: rgba(255, 255, 255, 0.03);
  color: var(--text);
}

.tab-item.active {
  background: rgba(79, 124, 255, 0.1);
  color: var(--accent);
  border-left-color: var(--accent);
}

.content-area {
  flex: 1;
  padding: 25px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

.tab-pane {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.control-form {
  padding-bottom: 20px;
  border-bottom: 1px solid var(--border);
}

.form-row {
  display: flex;
  gap: 20px;
}

.form-group.half { flex: 1; display:flex; align-items:center; gap: 10px; }
.form-group.full { width: 100%; }

.fixed-label {
  font-size: 13px;
  font-weight: 500;
  color: var(--text);
  min-width: 65px;
}

.form-control {
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: white;
  padding: 9px 12px;
  border-radius: 6px;
  font-size: 13px;
  outline: none;
  transition: border-color 0.2s;
  box-sizing: border-box;
}

.form-control:focus { border-color: var(--accent); }
.flex-1 { flex: 1; }
.mt-15 { margin-top: 15px; }
.mt-20 { margin-top: 20px; }

.btn {
  padding: 9px 25px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}
.btn-default { border: 1px solid rgba(255,255,255,0.2); background: transparent; color: white; }
.btn-default:hover { background: rgba(255,255,255,0.05); }

/* Custom Dropdown Overrides inside Modal */
.dropdown-wrapper { position: relative; cursor: pointer; display: flex; justify-content: space-between; align-items: center; width:100%;}
.custom-dropdown-list {
  position: absolute; top: calc(100% + 5px); left: 0; right: 0; background: #0f1b33;
  border: 1px solid var(--border); border-radius: 6px; max-height: 250px; overflow-y: auto;
  z-index: 100; box-shadow: 0 10px 30px rgba(0,0,0,0.8);
}
.custom-dropdown-list::-webkit-scrollbar { width: 6px; }
.custom-dropdown-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }
.dropdown-search { padding: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 8px; color: var(--muted); position: sticky; top: 0; background: #0f1b33; z-index: 101; }
.obj-search-input { background: transparent; border: none; color: white; outline: none; width: 100%; font-size: 13px; }
.dropdown-item { display: flex; align-items: center; gap: 10px; padding: 8px 12px; font-size: 13px; cursor: pointer; width: 100%; box-sizing: border-box; }
.dropdown-item:hover { background: rgba(255,255,255,0.05); }
.modern-checkbox { width: 16px; height: 16px; cursor: pointer; accent-color: var(--accent); }


.table-container {
  flex: 1;
  overflow-y: auto;
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 6px;
  background: rgba(0,0,0,0.15);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.data-table th {
  background: rgba(255, 255, 255, 0.05);
  padding: 10px 15px;
  text-align: left;
  font-weight: 600;
  color: var(--muted);
  border-bottom: 1px solid rgba(255,255,255,0.05);
  position: sticky;
  top: 0;
  z-index: 10;
  white-space: nowrap;
}

.data-table td {
  padding: 10px 15px;
  border-bottom: 1px solid rgba(255,255,255,0.02);
  color: var(--text);
}
.data-table tr:hover td { background: rgba(255, 255, 255, 0.03); cursor: pointer; }
.empty-table-msg { text-align: center !important; padding: 30px !important; color: var(--muted) !important; font-style: italic; }

.table-header-bar {
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


.action-bar-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 15px;
  margin-top: 15px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.left-actions { display: flex; gap: 8px; }

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
.dropdown-menu.pop-upwards { bottom: 40px; top: auto; }

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
.page-btn { background: transparent; border: none; color: var(--muted); cursor: pointer; font-size: 16px; }
.page-btn:disabled { opacity: 0.3; cursor: not-allowed; }
.page-input { width: 30px; text-align: center; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 4px; padding: 2px; }
.per-page { padding: 4px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 4px; margin-left: 10px; }

.records-info { font-size: 12px; color: var(--muted); }


/* Responsive Tweaks */
@media (max-width: 768px) {
  .modal-body { flex-direction: column; }
  .sidebar { width: 100%; height: auto; border-right: none; border-bottom: 1px solid var(--border); overflow-x: auto; overflow-y: hidden; }
  .tab-list { flex-direction: row; padding: 0; }
  .tab-item { border-left: none; border-bottom: 3px solid transparent; padding: 15px 20px; text-align: center; }
  .tab-item.active { border-left-color: transparent; border-bottom-color: var(--accent); }
  
  .form-row { flex-direction: column; gap: 15px; }
  .form-group.full { flex-wrap: wrap; }
  
  .action-bar-footer { flex-direction: column; gap: 15px; }
  .records-info, .pagination { justify-content: center; }
}
/* Responsive */
@media (max-width: 992px) {
  .modal-container {
    width: 95%;
  }
}

@media (max-width: 768px) {
  .modal-container {
    width: 100%;
    height: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .modal-body {
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
    height: auto;
    border-right: none;
    border-bottom: 1px solid var(--border);
    overflow-x: auto;
    overflow-y: hidden;
  }

  .tab-list {
    flex-direction: row;
    padding: 0;
  }

  .tab-item {
    padding: 15px 20px;
    border-left: none;
    border-bottom: 3px solid transparent;
    flex-shrink: 0;
  }

  .tab-item.active {
    border-bottom-color: var(--accent);
  }

  .content-area {
    padding: 16px;
  }

  .form-row {
    flex-direction: column;
    gap: 15px;
  }

  .form-group.half, .form-group.full {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }

  .fixed-label {
    min-width: 0;
  }

  .table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .data-table {
    min-width: 600px;
  }

  .action-bar-footer {
    flex-direction: column;
    gap: 20px;
    align-items: stretch;
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
