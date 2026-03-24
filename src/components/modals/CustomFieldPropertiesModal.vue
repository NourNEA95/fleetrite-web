<template>
  <div class="custom-field-modal-backdrop" @click.self="$emit('close')">
    <div class="custom-field-modal-container">
      <div class="custom-field-modal-header">
        <h2 class="modal-title">Custom field properties</h2>
        <button class="close-btn" @click="$emit('close')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="18 6 6 18"></polyline>
            <polyline points="6 6 18 18"></polyline>
          </svg>
        </button>
      </div>

      <div class="custom-field-modal-body">
        
        <div class="form-group row">
          <label>Name</label>
          <input type="text" v-model="fieldData.name" class="form-control" />
        </div>

        <div class="form-group row">
          <label>Value</label>
          <input type="text" v-model="fieldData.value" class="form-control" />
        </div>

        <div class="form-group row checkbox-only">
          <label>Data list</label>
          <input type="checkbox" v-model="fieldData.dataList" class="modern-checkbox" />
        </div>

        <div class="form-group row checkbox-only">
          <label>Popup</label>
          <input type="checkbox" v-model="fieldData.popup" class="modern-checkbox" />
        </div>

      </div>

      <div class="custom-field-modal-footer">
        <button class="btn btn-primary" @click="handleSave">Save</button>
        <button class="btn btn-secondary" @click="$emit('close')">Cancel</button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';

const props = defineProps({
  field: {
    type: Object,
    default: () => null
  }
});

const emit = defineEmits(['close', 'save']);

const fieldData = reactive({
  id: null,
  name: '',
  value: '',
  dataList: false,
  popup: false
});

onMounted(() => {
  if (props.field && props.field.id) {
    Object.assign(fieldData, props.field);
  }
});

function handleSave() {
  emit('save', { ...fieldData, id: fieldData.id || Date.now() });
}
</script>

<style scoped>
.custom-field-modal-backdrop {
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

.custom-field-modal-container {
  background: #0f1b33;
  width: 90vw;
  max-width: 500px;
  border-radius: 12px;
  border: 1px solid var(--border);
  box-shadow: 0 25px 50px rgba(0,0,0,0.7);
  display: flex;
  flex-direction: column;
}

.custom-field-modal-header {
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

.custom-field-modal-body {
  padding: 25px;
}

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

.form-control:focus { border-color: var(--accent); }

.form-group.checkbox-only { flex-direction: row; align-items: center; }
.form-group.checkbox-only label { flex: none; width: 100px; }
.modern-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: var(--accent);
}

.custom-field-modal-footer {
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
  .custom-field-modal-container {
    width: 100%;
    margin: 16px;
    border-radius: 12px;
  }

  .custom-field-modal-body {
    padding: 16px;
  }

  .form-group.row { flex-direction: column; align-items: flex-start; gap: 8px; }
  .form-group.row label { width: 100%; margin-bottom: 0px; }
  
  .form-group.checkbox-only { flex-direction: column; align-items: flex-start; }
  .form-group.checkbox-only label { width: 100%; }

  .custom-field-modal-footer {
    flex-direction: column;
    padding: 16px;
  }

  .custom-field-modal-footer .btn {
    width: 100%;
  }
}
</style>
