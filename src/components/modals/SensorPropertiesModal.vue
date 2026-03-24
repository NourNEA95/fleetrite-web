<template>
  <div class="sensor-modal-backdrop" @click.self="$emit('close')">
    <div class="sensor-modal-container">
      <div class="sensor-modal-header">
        <h2 class="modal-title">Sensor properties</h2>
        <button class="close-btn" @click="$emit('close')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="18 6 6 18"></polyline>
            <polyline points="6 6 18 18"></polyline>
          </svg>
        </button>
      </div>

      <div class="sensor-modal-body">
        
        <div class="split-layout">
          <!-- Left Column -->
          <div class="column">
            <h3 class="pane-title">Sensor</h3>
            <div class="form-group row">
              <label>Name</label>
              <input type="text" v-model="sensorData.name" class="form-control" />
            </div>
            <div class="form-group row">
              <label>Type</label>
              <select v-model="sensorData.type" class="form-control select">
                <option value="battery">Battery</option>
                <option value="ignition">Ignition (ACC)</option>
                <option value="driver">Driver assign</option>
                <option value="custom">Custom</option>
                <option value="odometer">Odometer</option>
              </select>
            </div>
            <div class="form-group row">
              <label>Parameter</label>
              <select v-model="sensorData.parameter" class="form-control select">
                <option value="battery">battery</option>
                <option value="acc">acc</option>
                <option value="driverUniqueId">driverUniqueId</option>
                <option value="io66">io66</option>
                <option value="odometer">odometer</option>
              </select>
            </div>
            <div class="form-group row checkbox-only">
              <label>Data list</label>
              <input type="checkbox" v-model="sensorData.dataList" />
            </div>
            <div class="form-group row checkbox-only">
              <label>Popup</label>
              <input type="checkbox" v-model="sensorData.popup" />
            </div>

            <h3 class="pane-title mt-20">Dictionary</h3>
            <div class="list-editor">
              <div class="list-header">
                <div>Value</div>
                <div>Text</div>
              </div>
              <div class="list-body">
                <div v-for="(item, index) in sensorData.dictionary" :key="index" class="list-item">
                  <span class="col-val">{{ item.value }}</span>
                  <span class="col-text">{{ item.text }}</span>
                  <button class="remove-btn" @click="removeDictionaryItem(index)">&times;</button>
                </div>
              </div>
              <div class="list-add-row">
                <input type="text" v-model="newDict.value" class="form-control" placeholder="Value" />
                <span>=</span>
                <input type="text" v-model="newDict.text" class="form-control" placeholder="Text" />
                <button class="btn btn-secondary btn-sm" @click="addDictionaryItem" style="font-size: 16px;">+</button>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="column">
            <h3 class="pane-title">Calibration</h3>
            <div class="list-editor">
              <div class="list-header">
                <div>X</div>
                <div>Y</div>
              </div>
              <div class="list-body">
                <div v-for="(item, index) in sensorData.calibration" :key="index" class="list-item">
                  <span class="col-val">{{ item.x }}</span>
                  <span class="col-text">{{ item.y }}</span>
                  <button class="remove-btn" @click="removeCalibrationItem(index)">&times;</button>
                </div>
              </div>
              <div class="list-add-row">
                <span style="font-size: 12px; font-weight: 500;">X</span>
                <input type="number" v-model="newCal.x" class="form-control" />
                <span style="font-size: 12px; font-weight: 500;">Y</span>
                <input type="number" v-model="newCal.y" class="form-control" />
                <button class="btn btn-secondary btn-sm" @click="addCalibrationItem" style="font-size: 16px;">+</button>
              </div>
            </div>
          </div>
        </div>

        <div class="result-section">
          <h3 class="pane-title mt-20">Result</h3>
          <div class="result-grid">
            <div class="grid-item">
              <label>Type</label>
              <select v-model="sensorData.resultType" class="form-control select">
                <option value="value">Value</option>
                <option value="logic">Logic</option>
              </select>
            </div>
            <div class="grid-item">
              <label>Units</label>
              <input type="text" v-model="sensorData.units" class="form-control" />
            </div>
            <div class="grid-item">
              <label>Sensor "1" text</label>
              <input type="text" v-model="sensorData.sensor1Text" class="form-control" placeholder="On" />
            </div>
            <div class="grid-item">
              <label>Sensor "0" text</label>
              <input type="text" v-model="sensorData.sensor0Text" class="form-control" placeholder="Off" />
            </div>
            <div class="grid-item">
              <label>Formula</label>
              <input type="text" v-model="sensorData.formula" class="form-control" placeholder="(X+1)/2*3" />
            </div>
            <div class="grid-item">
              <label>Lowest value</label>
              <input type="number" v-model="sensorData.lowestValue" class="form-control" />
            </div>
            <div class="grid-item">
              <label>Highest value</label>
              <input type="number" v-model="sensorData.highestValue" class="form-control" />
            </div>
            <div class="grid-item checkbox-grid-item">
              <label>Ignore if ignition is off</label>
              <input type="checkbox" v-model="sensorData.ignoreIgnitionOff" />
            </div>
          </div>

          <h3 class="pane-title mt-20">Sensor result preview</h3>
          <div class="form-group row preview-row">
            <label>Current value</label>
            <input type="text" v-model="previewValue" class="form-control" />
            <button class="btn btn-secondary btn-sm show-res-btn" @click="calcPreview">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 5px;"><polyline points="9 18 15 12 9 6"></polyline></svg>
              Show result
            </button>
          </div>
          <div class="form-group row preview-row">
            <label>Result</label>
            <input type="text" v-model="previewResult" class="form-control" readonly style="opacity:0.7;" />
          </div>
        </div>

      </div>

      <div class="sensor-modal-footer">
        <button class="btn btn-primary" @click="handleSave">Save</button>
        <button class="btn btn-secondary" @click="$emit('close')">Cancel</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';

const props = defineProps({
  sensor: {
    type: Object,
    default: () => null
  }
});

const emit = defineEmits(['close', 'save']);

const sensorData = reactive({
  id: null,
  name: '',
  type: 'battery',
  parameter: 'battery',
  dataList: true,
  popup: false,
  dictionary: [],
  calibration: [],
  resultType: 'value',
  units: '',
  sensor1Text: '',
  sensor0Text: '',
  formula: '',
  lowestValue: null,
  highestValue: null,
  ignoreIgnitionOff: false
});

const newDict = reactive({ value: '', text: '' });
const newCal = reactive({ x: null, y: null });

const previewValue = ref('');
const previewResult = ref('');

onMounted(() => {
  if (props.sensor && props.sensor.id) {
    Object.assign(sensorData, props.sensor);
  }
});

function addDictionaryItem() {
  if (newDict.value && newDict.text) {
    sensorData.dictionary.push({ value: newDict.value, text: newDict.text });
    newDict.value = '';
    newDict.text = '';
  }
}

function removeDictionaryItem(index) {
  sensorData.dictionary.splice(index, 1);
}

function addCalibrationItem() {
  if (newCal.x !== null && newCal.y !== null && newCal.x !== '' && newCal.y !== '') {
    sensorData.calibration.push({ x: Number(newCal.x), y: Number(newCal.y) });
    newCal.x = null;
    newCal.y = null;
  }
}

function removeCalibrationItem(index) {
  sensorData.calibration.splice(index, 1);
}

function calcPreview() {
  // Simple mock
  previewResult.value = previewValue.value ? previewValue.value + (sensorData.units || '') : '';
}

function handleSave() {
  emit('save', { ...sensorData, id: sensorData.id || Date.now() });
}
</script>

<style scoped>
.sensor-modal-backdrop {
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

.sensor-modal-container {
  background: #0f1b33;
  width: 90vw;
  max-width: 950px;
  max-height: 90vh;
  border-radius: 12px;
  border: 1px solid var(--border);
  box-shadow: 0 25px 50px rgba(0,0,0,0.7);
  display: flex;
  flex-direction: column;
}

.sensor-modal-header {
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

.sensor-modal-body {
  flex: 1;
  padding: 25px;
  overflow-y: auto;
}

/* Custom Scrollbar for modal body */
.sensor-modal-body::-webkit-scrollbar { width: 6px; }
.sensor-modal-body::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 4px; }
.sensor-modal-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }
.sensor-modal-body::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.25); }

.split-layout {
  display: flex;
  gap: 30px;
}

.column {
  flex: 1;
}

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
  width: 120px;
  font-size: 13px;
  color: var(--text);
  font-weight: 500;
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

.form-control:focus { border-color: var(--accent); }

.select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 35px;
}

.select option {
  background: #0f1b33;
  color: white;
}

.form-group.checkbox-only { flex-direction: row; }
.form-group.checkbox-only label { flex: none; width: 120px; }
.form-group.checkbox-only input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: var(--accent);
}

.list-editor {
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 6px;
  overflow: hidden;
}

.list-header {
  display: flex;
  background: rgba(0,0,0,0.2);
  padding: 10px;
  font-size: 12px;
  font-weight: 600;
  color: var(--muted);
}
.list-header div:first-child { flex: 1; border-right: 1px solid rgba(255,255,255,0.05); }
.list-header div:last-child { flex: 1; text-align: center; }
.list-header div { text-align: center; }

.list-body {
  min-height: 100px;
  max-height: 150px;
  overflow-y: auto;
}
.list-body::-webkit-scrollbar { width: 4px; }
.list-body::-webkit-scrollbar-track { background: transparent; }
.list-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }

.list-item {
  display: flex;
  align-items: center;
  padding: 8px 10px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  font-size: 13px;
}
.col-val { flex: 1; text-align: center; border-right: 1px solid rgba(255,255,255,0.05); }
.col-text { flex: 1; text-align: center; }
.remove-btn { background: transparent; border: none; color: #ef4444; cursor: pointer; opacity: 0.6; }
.remove-btn:hover { opacity: 1; }

.list-add-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  background: rgba(0,0,0,0.1);
}

.result-section {
  margin-top: 10px;
}

.result-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.grid-item label {
  display: block;
  font-size: 12px;
  color: var(--text);
  margin-bottom: 5px;
  font-weight: 500;
}

.checkbox-grid-item {
  display: flex;
  flex-direction: column;
}
.checkbox-grid-item input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: var(--accent);
  margin-top: 5px;
  cursor: pointer;
}

.preview-row label { width: 120px; }
.preview-row .form-control { max-width: 250px; }
.show-res-btn {
  margin-left: 15px;
  display: flex;
  align-items: center;
}

.sensor-modal-footer {
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
.btn-sm { padding: 6px 12px; font-size: 13px; }

.btn-primary { background: var(--accent); color: white; }
.btn-primary:hover { background: #3b66df; }

.btn-secondary { background: transparent; color: white; border: 1px solid rgba(255,255,255,0.2); }
.btn-secondary:hover { background: rgba(255,255,255,0.05); }

@media (max-width: 768px) {
  .sensor-modal-container {
    width: 100%;
    height: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .sensor-modal-body {
    padding: 16px;
  }

  .split-layout { flex-direction: column; gap: 32px; }
  
  .form-group.row { flex-direction: column; align-items: flex-start; gap: 8px; }
  .form-group.row label { width: 100%; }
  
  .form-group.checkbox-only { flex-direction: column; align-items: flex-start; }
  .form-group.checkbox-only label { width: 100%; }
  
  .form-control, .select { width: 100% !important; box-sizing: border-box; }
  
  .result-grid { grid-template-columns: 1fr; }
  
  .preview-row .form-control { max-width: 100% !important; margin-bottom: 8px; }
  .show-res-btn { margin-left: 0; width: 100%; justify-content: center; }

  .list-add-row { 
    flex-direction: column; 
    align-items: stretch;
    gap: 12px;
  }

  .sensor-modal-footer {
    flex-direction: column;
    padding: 16px;
  }

  .sensor-modal-footer .btn {
    width: 100%;
  }
}
</style>
