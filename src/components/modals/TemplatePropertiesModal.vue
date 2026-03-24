<template>
  <div class="template-props-backdrop" @click.self="$emit('close')">
    <div class="template-props-container">
      <div class="template-props-header">
        <h2 class="modal-title">Command properties</h2>
        <button class="close-btn" @click="$emit('close')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="18 6 6 18"></polyline>
            <polyline points="6 6 18 18"></polyline>
          </svg>
        </button>
      </div>

      <div class="template-props-body">
        
        <div class="pane-section">
          <h3 class="pane-title">Template</h3>
          
          <div class="form-group row">
            <label>Name</label>
            <input type="text" v-model="formData.name" class="form-control" />
          </div>

          <div class="form-group row checkbox-only align-center">
            <label>Hide unused protocols</label>
            <input type="checkbox" v-model="formData.hideUnusedProtocols" class="modern-checkbox" />
          </div>

          <div class="form-group row">
            <label>Protocol</label>
            <div class="dropdown-wrapper form-control select" @click="showProtocolDropdown = !showProtocolDropdown">
              <span>{{ formData.protocol }}</span>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="dropdown-arrow"><polyline points="6 9 12 15 18 9"></polyline></svg>
              <div v-if="showProtocolDropdown" class="custom-dropdown-list" @click.stop>
                <div class="dropdown-search">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                  <input type="text" v-model="protocolSearch" placeholder="" class="obj-search-input" />
                </div>
                <label v-for="prot in filteredProtocols" :key="prot" class="dropdown-item" @click="selectProtocol(prot)">
                  {{ prot }}
                </label>
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

        <div class="pane-section mt-30">
          <h3 class="pane-title">Variables</h3>
          <p class="static-text">%IMEI% - Object IMEI</p>
        </div>

      </div>

      <div class="template-props-footer">
        <button class="btn btn-primary" @click="handleSave">Save</button>
        <button class="btn btn-secondary" @click="$emit('close')">Cancel</button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  templateData: { type: Object, default: () => null }
});

const emit = defineEmits(['close', 'save']);

const formData = reactive({
  id: null,
  name: '',
  hideUnusedProtocols: false,
  protocol: 'All protocols',
  gateway: 'GPRS',
  type: 'ASCII',
  command: ''
});

// Dropdown Toggles
const showProtocolDropdown = ref(false);
const showGatewayDropdown = ref(false);
const showTypeDropdown = ref(false);

const protocolSearch = ref('');
const allProtocolsList = ['All protocols', 'Teltonika', 'Coban', 'Concox', 'SinoTrack'];

const filteredProtocols = computed(() => {
  if (!protocolSearch.value) return allProtocolsList;
  return allProtocolsList.filter(p => p.toLowerCase().includes(protocolSearch.value.toLowerCase()));
});

const selectProtocol = (val) => { formData.protocol = val; showProtocolDropdown.value = false; };
const selectGateway = (val) => { formData.gateway = val; showGatewayDropdown.value = false; };
const selectType = (val) => { formData.type = val; showTypeDropdown.value = false; };

const handleClickOutside = (e) => {
  if (!e.target.closest('.dropdown-wrapper')) {
    showProtocolDropdown.value = false;
    showGatewayDropdown.value = false;
    showTypeDropdown.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  if (props.templateData) {
    Object.assign(formData, JSON.parse(JSON.stringify(props.templateData)));
  }
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

function handleSave() {
  emit('save', {
    ...formData,
    id: formData.id || Date.now()
  });
}
</script>

<style scoped>
.template-props-backdrop {
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

.template-props-container {
  background: #0f1b33;
  width: 90vw;
  max-width: 500px;
  max-height: 90vh;
  border-radius: 12px;
  border: 1px solid var(--border);
  box-shadow: 0 35px 60px rgba(0,0,0,0.8);
  display: flex;
  flex-direction: column;
}

.template-props-header {
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

.template-props-body {
  flex: 1;
  padding: 25px;
  overflow-y: auto;
}
.template-props-body::-webkit-scrollbar { width: 6px; }
.template-props-body::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 4px; }
.template-props-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

.pane-section { margin-bottom: 20px; }
.pane-title { margin: 0 0 20px 0; color: var(--accent); font-size: 15px; font-weight: 600; }
.mt-30 { margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px; }

.form-group.row { display: flex; align-items: center; margin-bottom: 15px; position:relative;}
.form-group.row label { width: 140px; font-size: 13px; color: var(--text); font-weight: 500; flex-shrink:0;}
.align-center { align-items: center; }

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

.form-control:focus { border-color: var(--accent); }

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

.static-text {
  font-size: 13px;
  color: var(--text);
  margin: 0;
  opacity: 0.9;
}

.template-props-footer {
  padding: 20px 25px;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: center;
  gap: 15px;
}

.btn { padding: 10px 40px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; }
.btn-primary { background: var(--accent); color: white; }
.btn-primary:hover { background: #3b66df; }
.btn-secondary { background: transparent; color: white; border: 1px solid rgba(255,255,255,0.2); }
.btn-secondary:hover { background: rgba(255,255,255,0.05); }

@media (max-width: 768px) {
  .template-props-container {
    width: 100%;
    margin: 16px;
    border-radius: 12px;
  }

  .template-props-body {
    padding: 16px;
  }

  .form-group.row { flex-direction: column; align-items: flex-start; gap: 8px; }
  .form-group.row label { width: 100% !important; margin-bottom: 0px; }

  .template-props-footer {
    flex-direction: column;
    padding: 16px;
  }

  .template-props-footer .btn {
    width: 100%;
  }
}
</style>
