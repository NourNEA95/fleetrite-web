<template>
  <div class="service-modal-backdrop" @click.self="$emit('close')">
    <div class="service-modal-container">
      <div class="service-modal-header">
        <h2 class="modal-title">Service properties</h2>
        <button class="close-btn" @click="$emit('close')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="18 6 6 18"></polyline>
            <polyline points="6 6 18 18"></polyline>
          </svg>
        </button>
      </div>

      <div class="service-modal-body">
        
        <h3 class="pane-title">Service</h3>
        <div class="form-group row">
          <label>Name</label>
          <input type="text" v-model="serviceData.name" class="form-control" />
        </div>

        <div class="form-group row checkbox-only">
          <label>Data list</label>
          <input type="checkbox" v-model="serviceData.dataList" />
        </div>

        <div class="form-group row checkbox-only">
          <label>Popup</label>
          <input type="checkbox" v-model="serviceData.popup" />
        </div>

        <div class="grid-layout">
          <div class="col">
            <div class="form-group row">
              <label class="narrow-lbl">Odometer interval (km)</label>
              <input type="checkbox" v-model="serviceData.enableOdoInterval" class="modern-checkbox" />
              <input type="number" v-model="serviceData.odoInterval" class="form-control" :disabled="!serviceData.enableOdoInterval" />
            </div>

            <div class="form-group row">
              <label class="narrow-lbl">Engine hours interval (h)</label>
              <input type="checkbox" v-model="serviceData.enableEhInterval" class="modern-checkbox" />
              <input type="number" v-model="serviceData.ehInterval" class="form-control" :disabled="!serviceData.enableEhInterval" />
            </div>

            <div class="form-group row">
              <label class="narrow-lbl">Days interval</label>
              <input type="checkbox" v-model="serviceData.enableDaysInterval" class="modern-checkbox" />
              <input type="number" v-model="serviceData.daysInterval" class="form-control" :disabled="!serviceData.enableDaysInterval" />
            </div>
          </div>
          
          <div class="col">
            <div class="form-group row">
              <label class="narrow-lbl">Last service (km)</label>
              <input type="number" v-model="serviceData.lastServiceOdo" class="form-control" />
            </div>

            <div class="form-group row">
              <label class="narrow-lbl">Last service (h)</label>
              <input type="number" v-model="serviceData.lastServiceEh" class="form-control" />
            </div>

            <div class="form-group row">
              <label class="narrow-lbl">Last service</label>
              <input type="date" v-model="serviceData.lastServiceDays" class="form-control" />
            </div>
          </div>
        </div>

        <h3 class="pane-title mt-20">Trigger event</h3>
        <div class="grid-layout">
          <div class="col">
            <div class="form-group row">
              <label class="narrow-lbl">Odometer left (km)</label>
              <input type="checkbox" v-model="serviceData.enableOdoLeft" class="modern-checkbox" />
              <input type="number" v-model="serviceData.odoLeft" class="form-control" :disabled="!serviceData.enableOdoLeft" />
            </div>

            <div class="form-group row">
              <label class="narrow-lbl">Engine hours left (h)</label>
              <input type="checkbox" v-model="serviceData.enableEhLeft" class="modern-checkbox" />
              <input type="number" v-model="serviceData.ehLeft" class="form-control" :disabled="!serviceData.enableEhLeft" />
            </div>

            <div class="form-group row">
              <label class="narrow-lbl">Days left</label>
              <input type="checkbox" v-model="serviceData.enableDaysLeft" class="modern-checkbox" />
              <input type="number" v-model="serviceData.daysLeft" class="form-control" :disabled="!serviceData.enableDaysLeft" />
            </div>
          </div>

          <div class="col">
             <div class="form-group row">
              <label class="narrow-lbl">Update last service</label>
              <input type="checkbox" v-model="serviceData.updateLastService" class="modern-checkbox" />
            </div>
          </div>
        </div>

        <h3 class="pane-title mt-20">Current object counters</h3>
        <div class="form-group row">
          <label style="width: 200px;">Current odometer (km)</label>
          <input type="number" v-model="serviceData.currentOdo" class="form-control" readonly style="max-width: 200px; opacity: 0.7;" />
        </div>
        <div class="form-group row">
          <label style="width: 200px;">Current engine hours (h)</label>
          <input type="number" v-model="serviceData.currentEh" class="form-control" readonly style="max-width: 200px; opacity: 0.7;" />
        </div>

      </div>

      <div class="service-modal-footer">
        <button class="btn btn-primary" @click="handleSave">Save</button>
        <button class="btn btn-secondary" @click="$emit('close')">Cancel</button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';

const props = defineProps({
  service: {
    type: Object,
    default: () => null
  },
  vehicleId: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['close', 'save']);

const serviceData = reactive({
  id: null,
  name: '',
  dataList: false,
  popup: false,
  
  enableOdoInterval: false, odoInterval: null,
  enableEhInterval: false, ehInterval: null,
  enableDaysInterval: false, daysInterval: null,
  
  lastServiceOdo: null,
  lastServiceEh: null,
  lastServiceDays: '',

  enableOdoLeft: false, odoLeft: null,
  enableEhLeft: false, ehLeft: null,
  enableDaysLeft: false, daysLeft: null,

  updateLastService: false,

  currentOdo: 431727,
  currentEh: 0
});

onMounted(() => {
  if (props.service && props.service.id) {
    Object.assign(serviceData, props.service);
  }
});

function handleSave() {
  emit('save', { ...serviceData, id: serviceData.id || Date.now() });
}
</script>

<style scoped>
.service-modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(2px);
  z-index: 100000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.service-modal-container {
  background: #0f1b33;
  width: 90vw;
  max-width: 800px;
  max-height: 90vh;
  border-radius: 12px;
  border: 1px solid var(--border);
  box-shadow: 0 25px 50px rgba(0,0,0,0.7);
  display: flex;
  flex-direction: column;
}

.service-modal-header {
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

.service-modal-body {
  flex: 1;
  padding: 25px;
  overflow-y: auto;
}

.service-modal-body::-webkit-scrollbar { width: 6px; }
.service-modal-body::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 4px; }
.service-modal-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }
.service-modal-body::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.25); }

.pane-title {
  margin: 0 0 20px 0;
  color: var(--accent);
  font-size: 15px;
  font-weight: 600;
}

.mt-20 { margin-top: 30px; }

.form-group.row {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
}

.form-group.row label {
  width: 150px;
  font-size: 13px;
  color: var(--text);
  font-weight: 500;
}

.narrow-lbl {
  width: 160px !important;
}

.form-control {
  flex: 1;
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: white;
  padding: 10px 12px;
  border-radius: 6px;
  font-size: 13px;
  outline: none;
  transition: border-color 0.2s;
}

.form-control:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.form-control:focus:not(:disabled) { border-color: var(--accent); }

.form-group.checkbox-only { flex-direction: row; }
.form-group.checkbox-only label { flex: none; width: 150px; }
.form-group.checkbox-only input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: var(--accent);
}

.modern-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: var(--accent);
  margin-right: 15px;
}

.grid-layout {
  display: flex;
  gap: 30px;
}
.col {
  flex: 1;
}

.service-modal-footer {
  padding: 20px 25px;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: center;
  gap: 15px;
}

.btn {
  padding: 10px 30px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary { background: var(--accent); color: white; }
.btn-primary:hover { background: #3b66df; }

.btn-secondary { background: transparent; color: white; border: 1px solid rgba(255,255,255,0.2); }
.btn-secondary:hover { background: rgba(255,255,255,0.05); }

@media (max-width: 768px) {
  .service-modal-container {
    width: 100%;
    height: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .service-modal-body {
    padding: 16px;
  }

  .grid-layout { flex-direction: column; gap: 32px; }
  
  .form-group.row { flex-direction: column; align-items: flex-start; gap: 8px; }
  .form-group.row label { width: 100% !important; margin-bottom: 0px; }
  
  .form-group.checkbox-only { flex-direction: column; align-items: flex-start; }
  .form-group.checkbox-only label { width: 100% !important; }
  
  .form-control, .select { width: 100% !important; box-sizing: border-box; }
  .modern-checkbox { margin-bottom: 8px; margin-right: 0; }

  .service-modal-footer {
    flex-direction: column;
    padding: 16px;
  }

  .service-modal-footer .btn {
    width: 100%;
  }
}
</style>
