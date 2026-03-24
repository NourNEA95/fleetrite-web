<template>
  <div class="schedule-props-backdrop" @click.self="$emit('close')">
    <div class="schedule-props-container">
      <div class="schedule-props-header">
        <h2 class="modal-title">Schedule properties</h2>
        <button class="close-btn" @click="$emit('close')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="18 6 6 18"></polyline>
            <polyline points="6 6 18 18"></polyline>
          </svg>
        </button>
      </div>

      <div class="schedule-props-body">
        
        <div class="grid-layout">
          <!-- Left Column (Schedule Details) -->
          <div class="col">
            <h3 class="pane-title">Schedule</h3>
            
            <div class="form-group row checkbox-only">
              <label>Active</label>
              <input type="checkbox" v-model="formData.active" class="modern-checkbox" />
            </div>

            <div class="form-group row">
              <label>Name</label>
              <input type="text" v-model="formData.name" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Protocol</label>
              <div class="dropdown-wrapper form-control select" @click="showProtocolDropdown = !showProtocolDropdown">
                <span>{{ formData.protocol }}</span>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="dropdown-arrow"><polyline points="6 9 12 15 18 9"></polyline></svg>
                <div v-if="showProtocolDropdown" class="custom-dropdown-list" @click.stop>
                  <label class="dropdown-item" @click="selectProtocol('All protocols')">All protocols</label>
                  <label class="dropdown-item" @click="selectProtocol('Teltonika')">Teltonika</label>
                  <label class="dropdown-item" @click="selectProtocol('Coban')">Coban</label>
                </div>
              </div>
            </div>

            <div class="form-group row">
              <label>Objects</label>
              <div class="dropdown-wrapper form-control select" @click="showObjectDropdown = !showObjectDropdown">
                <span>{{ selectedObjectsText }}</span>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="dropdown-arrow"><polyline points="6 9 12 15 18 9"></polyline></svg>
                
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
                    <input type="checkbox" v-model="formData.objects" :value="obj.id" class="modern-checkbox" />
                    {{ obj.name }}
                  </label>
                </div>
              </div>
            </div>

            <div class="form-group row">
              <label>Template</label>
              <div class="dropdown-wrapper form-control select" @click="showTemplateDropdown = !showTemplateDropdown">
                <span>{{ formData.template }}</span>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="dropdown-arrow"><polyline points="6 9 12 15 18 9"></polyline></svg>
                <div v-if="showTemplateDropdown" class="custom-dropdown-list" @click.stop>
                  <label class="dropdown-item" @click="selectTemplate('Custom')">Custom</label>
                  <label class="dropdown-item" @click="selectTemplate('Engine Block')">Engine Block</label>
                  <label class="dropdown-item" @click="selectTemplate('Engine Unblock')">Engine Unblock</label>
                </div>
              </div>
            </div>

            <div class="form-group row">
              <label>Gateway</label>
              <div class="dropdown-wrapper form-control select" @click="showGatewayDropdown = !showGatewayDropdown">
                <span>{{ formData.gateway }}</span>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="dropdown-arrow"><polyline points="6 9 12 15 18 9"></polyline></svg>
                <div v-if="showGatewayDropdown" class="custom-dropdown-list" @click.stop>
                  <label class="dropdown-item" @click="selectGateway('GPRS')">GPRS</label>
                  <label class="dropdown-item" @click="selectGateway('SMS')">SMS</label>
                </div>
              </div>
            </div>

            <div class="form-group row">
              <label>Type</label>
              <div class="dropdown-wrapper form-control select" @click="showTypeDropdown = !showTypeDropdown">
                <span>{{ formData.type }}</span>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="dropdown-arrow"><polyline points="6 9 12 15 18 9"></polyline></svg>
                <div v-if="showTypeDropdown" class="custom-dropdown-list" @click.stop>
                  <label class="dropdown-item" @click="selectType('ASCII')">ASCII</label>
                  <label class="dropdown-item" @click="selectType('HEX')">HEX</label>
                </div>
              </div>
            </div>

            <div class="form-group row">
              <label>Command</label>
              <input type="text" v-model="formData.command" class="form-control" />
            </div>
            
          </div>
          
          <!-- Right Column (Time Selection) -->
          <div class="col right-border-col">
            <h3 class="pane-title">Time</h3>
            
            <div class="form-group row checkbox-only align-center mb-15">
              <label class="w-120">Exact time</label>
              <input type="checkbox" v-model="formData.time.exact.active" class="modern-checkbox mr-10" />
              <input type="date" v-model="formData.time.exact.date" :disabled="!formData.time.exact.active" class="form-control mr-10 date-input flex-1" />
              <select v-model="formData.time.exact.time" :disabled="!formData.time.exact.active" class="form-control select time-select">
                <option value="00:00">00:00</option><option value="12:00">12:00</option>
              </select>
            </div>

            <div class="form-group row checkbox-only align-center mb-15" v-for="day in days" :key="day">
              <label class="w-120">{{ day }}</label>
              <input type="checkbox" v-model="formData.time.days[day].active" class="modern-checkbox mr-10" />
              <select v-model="formData.time.days[day].time" :disabled="!formData.time.days[day].active" class="form-control select time-select flex-1" style="max-width: 90px;">
                <option value="00:00">00:00</option><option value="08:00">08:00</option><option value="12:00">12:00</option>
              </select>
            </div>
          </div>

        </div>

      </div>

      <div class="schedule-props-footer">
        <button class="btn btn-primary" @click="handleSave">Save</button>
        <button class="btn btn-secondary" @click="$emit('close')">Cancel</button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  schedule: { type: Object, default: () => null },
  vehicle: { type: Object, default: () => null }
});

const emit = defineEmits(['close', 'save']);

const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

const formData = reactive({
  id: null,
  active: true,
  name: '',
  protocol: 'All protocols',
  objects: [],
  template: 'Custom',
  gateway: 'GPRS',
  type: 'ASCII',
  command: '',
  time: {
    exact: { active: false, date: '', time: '00:00' },
    days: {
      Monday: { active: false, time: '00:00' },
      Tuesday: { active: false, time: '00:00' },
      Wednesday: { active: false, time: '00:00' },
      Thursday: { active: false, time: '00:00' },
      Friday: { active: false, time: '00:00' },
      Saturday: { active: false, time: '00:00' },
      Sunday: { active: false, time: '00:00' }
    }
  }
});

// Dropdown Toggles
const showProtocolDropdown = ref(false);
const showObjectDropdown = ref(false);
const showTemplateDropdown = ref(false);
const showGatewayDropdown = ref(false);
const showTypeDropdown = ref(false);

const objectSearch = ref('');
const availableObjects = ref([
  { id: '1', name: 'Facility - 648' }, { id: '2', name: '1/23035' },
  { id: '3', name: '12/62921' }, { id: '4', name: '14/18182' },
  { id: '5', name: '15/40319' }, { id: '6', name: '15/87124' }
]);

const filteredObjectsList = computed(() => {
  if (!objectSearch.value) return availableObjects.value;
  return availableObjects.value.filter(o => o.name.toLowerCase().includes(objectSearch.value.toLowerCase()));
});

const isAllObjectsSelected = computed(() => filteredObjectsList.value.length > 0 && formData.objects.length === filteredObjectsList.value.length);
const selectedObjectsText = computed(() => {
  if (formData.objects.length === 0) return 'Nothing selected';
  if (formData.objects.length === 1) {
    const obj = availableObjects.value.find(o => o.id === formData.objects[0]);
    return obj ? obj.name : '1 selected';
  }
  return `${formData.objects.length} selected`;
});

function toggleAllObjects(e) {
  if (e.target.checked) formData.objects = filteredObjectsList.value.map(o => o.id);
  else formData.objects = [];
}

const selectProtocol = (val) => { formData.protocol = val; showProtocolDropdown.value = false; };
const selectTemplate = (val) => { formData.template = val; showTemplateDropdown.value = false; };
const selectGateway = (val) => { formData.gateway = val; showGatewayDropdown.value = false; };
const selectType = (val) => { formData.type = val; showTypeDropdown.value = false; };

const handleClickOutside = (e) => {
  if (!e.target.closest('.dropdown-wrapper')) {
    showProtocolDropdown.value = false;
    showObjectDropdown.value = false;
    showTemplateDropdown.value = false;
    showGatewayDropdown.value = false;
    showTypeDropdown.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  if (props.schedule) {
    // Populate form with existing data securely
    Object.assign(formData, JSON.parse(JSON.stringify(props.schedule)));
  } else if (props.vehicle && props.vehicle.id) {
    formData.objects = [props.vehicle.id]; // default to selected object
  }
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

function handleSave() {
  const scheduleText = [];
  if (formData.time.exact.active) scheduleText.push(formData.time.exact.date);
  else {
    days.forEach(d => { if (formData.time.days[d].active) scheduleText.push(d.substr(0,3)) });
  }

  emit('save', {
    ...formData,
    id: formData.id || Date.now(),
    scheduleText: scheduleText.join(', ') || 'None'
  });
}
</script>

<style scoped>
.schedule-props-backdrop {
  position: fixed;
  top: 0; left: 0;
  width: 100vw; height: 100vh;
  background: rgba(0, 0, 0, 0.4);
  /* Sits precisely on top of ObjectControlModal */
  z-index: 100000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.schedule-props-container {
  background: #0f1b33;
  width: 90vw;
  max-width: 800px;
  max-height: 90vh;
  border-radius: 12px;
  border: 1px solid var(--border);
  box-shadow: 0 35px 60px rgba(0,0,0,0.8);
  display: flex;
  flex-direction: column;
}

.schedule-props-header {
  height: 60px;
  padding: 0 25px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--border);
}

.modal-title { margin: 0; font-size: 16px; font-weight: 600; color: var(--accent); }
.close-btn { background: transparent; border: none; color: var(--muted); cursor: pointer; padding: 5px; }
.close-btn:hover { color: #ef4444; }

.schedule-props-body {
  flex: 1;
  padding: 25px;
  overflow-y: auto;
}
.schedule-props-body::-webkit-scrollbar { width: 6px; }
.schedule-props-body::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 4px; }
.schedule-props-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

.pane-title { margin: 0 0 20px 0; color: var(--accent); font-size: 15px; font-weight: 600; }

.grid-layout { display: flex; gap: 40px; }
.col { flex: 1; display: flex; flex-direction: column; }
.right-border-col { padding-left: 10px; } /* Removed border based on screenshot */

.form-group.row { display: flex; align-items: center; margin-bottom: 15px; position:relative;}
.form-group.row label { width: 100px; font-size: 13px; color: var(--text); font-weight: 500; flex-shrink:0;}
.w-120 { width: 120px !important; }
.align-center { align-items: center; }
.mb-15 { margin-bottom: 15px; }
.mr-10 { margin-right: 10px; }
.flex-1 { flex: 1; }

.form-control {
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: white;
  padding: 9px 12px;
  border-radius: 6px;
  font-size: 13px;
  outline: none;
  transition: border-color 0.2s;
  width: 100%;
  box-sizing: border-box;
}

.form-control:disabled { opacity: 0.5; cursor: not-allowed; }
.form-control:focus:not(:disabled) { border-color: var(--accent); }
.date-input { width: 100%; }
select.form-control { appearance: none; -webkit-appearance: none; background-image: url('data:image/svg+xml;utf8,<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="gray" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>'); background-repeat: no-repeat; background-position: right 10px center; padding-right: 25px; }

.form-group.checkbox-only { flex-direction: row; }
.modern-checkbox { width: 16px; height: 16px; cursor: pointer; accent-color: var(--accent); }

/* Dropdown styling */
.dropdown-wrapper { cursor: pointer; display: flex; justify-content: space-between; align-items: center; position:relative;}
.dropdown-arrow { color: var(--muted); }
.custom-dropdown-list {
  position: absolute; top: calc(100% + 5px); left: 0; right: 0; background: #0f1b33;
  border: 1px solid var(--border); border-radius: 6px; max-height: 250px; overflow-y: auto;
  z-index: 100; box-shadow: 0 10px 30px rgba(0,0,0,0.8); display: flex; flex-direction: column;
}
.custom-dropdown-list::-webkit-scrollbar { width: 6px; }
.custom-dropdown-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }
.dropdown-search { padding: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 8px; color: var(--muted); position: sticky; top: 0; background: #0f1b33; z-index: 101; }
.obj-search-input { background: transparent; border: none; color: white; outline: none; width: 100%; font-size: 13px; }
.dropdown-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; font-size: 13px; cursor: pointer; width: 100%; box-sizing: border-box; }
.dropdown-item:hover { background: rgba(255,255,255,0.05); color: var(--text); }


.schedule-props-footer {
  padding: 20px 25px;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: center;
  gap: 15px;
}

.btn { padding: 10px 30px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; }
.btn-primary { background: var(--accent); color: white; }
.btn-primary:hover { background: #3b66df; }
.btn-secondary { background: transparent; color: white; border: 1px solid rgba(255,255,255,0.2); }
.btn-secondary:hover { background: rgba(255,255,255,0.05); }

@media (max-width: 768px) {
  .schedule-props-container {
    width: 100%;
    height: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .grid-layout { 
    flex-direction: column; 
    gap: 32px; 
  }

  .form-group.row {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .form-group.row label {
    width: 100% !important;
  }

  .schedule-props-footer {
    flex-direction: column;
    padding: 16px;
  }

  .schedule-props-footer .btn {
    width: 100%;
  }

  .right-border-col {
    padding-left: 0;
  }
}
</style>
