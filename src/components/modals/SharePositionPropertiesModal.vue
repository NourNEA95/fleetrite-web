<template>
  <div class="share-props-backdrop" @click.self="$emit('close')">
    <div class="share-props-container">
      <div class="share-props-header">
        <h2 class="modal-title">Share position properties</h2>
        <button class="close-btn" @click="$emit('close')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="18 6 6 18"></polyline>
            <polyline points="6 6 18 18"></polyline>
          </svg>
        </button>
      </div>

      <div class="share-props-body">
        
        <h3 class="pane-title">Share position</h3>
        
        <div class="grid-layout">
          <div class="col">
            <div class="form-group row checkbox-only">
              <label>Active</label>
              <input type="checkbox" v-model="shareData.active" class="modern-checkbox" />
            </div>

            <div class="form-group row">
              <label>Name</label>
              <input type="text" v-model="shareData.name" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Object</label>
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
                    <input type="checkbox" v-model="shareData.objects" :value="obj.id" class="modern-checkbox" />
                    {{ obj.name }}
                  </label>
                </div>
              </div>
            </div>

            <div class="form-group row">
              <label>E-mail</label>
              <input type="text" v-model="shareData.email" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Phone</label>
              <input type="text" v-model="shareData.phone" class="form-control" />
            </div>
          </div>
          
          <div class="col">
            <div class="form-group row checkbox-only" style="align-items: center;">
              <label>Expire on</label>
              <input type="checkbox" v-model="shareData.enableExpire" class="modern-checkbox" style="margin-right:15px;"/>
              <input type="date" v-model="shareData.expiresOn" class="form-control" :disabled="!shareData.enableExpire" />
            </div>

            <div class="form-group row checkbox-only mt-10">
              <label>Delete after expiration</label>
              <input type="checkbox" v-model="shareData.deleteAfterExpire" class="modern-checkbox" />
            </div>
          </div>
        </div>

        <h3 class="pane-title mt-20">Access via URL</h3>
        
        <div class="form-group row checkbox-only">
          <label class="wide-label">Send via e-mail</label>
          <input type="checkbox" v-model="shareData.sendEmail" class="modern-checkbox" />
        </div>

        <div class="form-group row checkbox-only">
          <label class="wide-label">Send via SMS</label>
          <input type="checkbox" v-model="shareData.sendSms" class="modern-checkbox" />
        </div>

        <div class="form-group row">
          <label class="wide-label">URL desktop</label>
          <input type="text" v-model="shareData.urlDesktop" class="form-control" readonly />
        </div>

        <div class="form-group row">
          <label class="wide-label">URL mobile</label>
          <input type="text" v-model="shareData.urlMobile" class="form-control" readonly />
        </div>

      </div>

      <div class="share-props-footer">
        <button class="btn btn-primary" @click="handleSave">Save</button>
        <button class="btn btn-secondary" @click="$emit('close')">Cancel</button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  share: { type: Object, default: () => null },
  vehicle: { type: Object, default: () => null }
});

const emit = defineEmits(['close', 'save']);

const shareData = reactive({
  id: null,
  active: true,
  name: '',
  objects: [],
  email: '',
  phone: '',
  enableExpire: false,
  expiresOn: '',
  deleteAfterExpire: false,
  sendEmail: true,
  sendSms: true,
  urlDesktop: 'http://gps.fleetrite.io/index.php?su=70922FED3DB1E254F0FE4AF8E4F7A205',
  urlMobile: 'http://gps.fleetrite.io/index.php?su=70922FED3DB1E254F0FE4AF8E4F7A205&m=true'
});

const availableObjects = ref([
  { id: '1', name: 'Facility - 648' },
  { id: '2', name: '1/23035' },
  { id: '3', name: '12/62921' },
  { id: '4', name: '14/18182' },
  { id: '5', name: '15/40319' },
  { id: '6', name: '15/87124' },
  { id: '7', name: '21/35720' },
  { id: '8', name: '21/70902' },
  { id: '9', name: '22/69736' },
  { id: '10', name: '4/96086' }
]);

const showObjectDropdown = ref(false);
const objectSearch = ref('');

const filteredObjectsList = computed(() => {
  if (!objectSearch.value) return availableObjects.value;
  return availableObjects.value.filter(o => o.name.toLowerCase().includes(objectSearch.value.toLowerCase()));
});

const isAllObjectsSelected = computed(() => {
  return filteredObjectsList.value.length > 0 && shareData.objects.length === filteredObjectsList.value.length;
});

const selectedObjectsText = computed(() => {
  if (shareData.objects.length === 0) return 'Nothing selected';
  if (shareData.objects.length === 1) {
    const obj = availableObjects.value.find(o => o.id === shareData.objects[0]);
    return obj ? obj.name : '1 selected';
  }
  return `${shareData.objects.length} selected`;
});

function toggleAllObjects(e) {
  if (e.target.checked) {
    shareData.objects = filteredObjectsList.value.map(o => o.id);
  } else {
    shareData.objects = [];
  }
}

const handleClickOutside = (e) => {
  const wrapper = document.querySelector('.dropdown-wrapper');
  if (wrapper && !wrapper.contains(e.target)) {
    showObjectDropdown.value = false;
  }
};

onMounted(() => {
  if (props.share) {
    Object.assign(shareData, props.share);
  } else if (props.vehicle && props.vehicle.id) {
    // Pre-select current vehicle by default if starting new share from a vehicle
    shareData.objects = [props.vehicle.id];
  }
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

function handleSave() {
  emit('save', { ...shareData, id: shareData.id || Date.now() });
}
</script>

<style scoped>
.share-props-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.4);
  /* No heavy blur, it sits on top of another modal */
  z-index: 100000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.share-props-container {
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

.share-props-header {
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

.share-props-body {
  flex: 1;
  padding: 25px;
  overflow-y: auto;
}

.share-props-body::-webkit-scrollbar { width: 6px; }
.share-props-body::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 4px; }
.share-props-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

.pane-title {
  margin: 0 0 20px 0;
  color: var(--accent);
  font-size: 15px;
  font-weight: 600;
}

.mt-20 { margin-top: 30px; }
.mt-10 { margin-top: 10px; }

.grid-layout {
  display: flex;
  gap: 30px;
}
.col { flex: 1; }

.form-group.row {
  display: flex;
  align-items: center;
  margin-bottom: 15px;
}

.form-group.row label {
  width: 100px;
  font-size: 13px;
  color: var(--text);
  font-weight: 500;
}

.wide-label {
  width: 140px !important;
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
  width: 100%;
  box-sizing: border-box;
}

.form-control:disabled { opacity: 0.5; cursor: not-allowed; }
.form-control:focus:not(:disabled) { border-color: var(--accent); }

.form-group.checkbox-only { flex-direction: row; align-items: center;}
.form-group.checkbox-only label { flex: none; width: 100px; }
.modern-checkbox {
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: var(--accent);
}

/* Custom Dropdown for Objects */
.dropdown-wrapper {
  position: relative;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dropdown-arrow {
  color: var(--muted);
}

.custom-dropdown-list {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 6px;
  margin-top: 5px;
  max-height: 250px;
  overflow-y: auto;
  z-index: 100;
  box-shadow: 0 10px 20px rgba(0,0,0,0.5);
}

.custom-dropdown-list::-webkit-scrollbar { width: 6px; }
.custom-dropdown-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

.dropdown-search {
  padding: 8px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  position: sticky;
  top: 0;
  background: var(--card);
  z-index: 101;
}

.obj-search-input {
  background: transparent;
  border: none;
  color: white;
  outline: none;
  width: 100%;
  font-size: 13px;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  font-size: 13px;
  cursor: pointer;
  width: 100%;
  box-sizing: border-box;
}
.dropdown-item:hover { background: rgba(255,255,255,0.05); }

.share-props-footer {
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
  .share-props-container {
    width: 100%;
    margin: 16px;
    border-radius: 12px;
  }

  .share-props-body {
    padding: 16px;
  }

  .grid-layout { flex-direction: column; gap: 32px; }
  
  .form-group.row { flex-direction: column; align-items: flex-start; gap: 8px; }
  .form-group.row label { width: 100% !important; margin-bottom: 0px; }
  
  .form-group.checkbox-only { flex-direction: column; align-items: flex-start; }
  .form-group.checkbox-only label { width: 100% !important; margin-bottom: 0px; }

  .share-props-footer {
    flex-direction: column;
    padding: 16px;
  }

  .share-props-footer .btn {
    width: 100%;
  }
}
</style>
