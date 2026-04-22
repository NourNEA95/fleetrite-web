<template>
  <Teleport to="body">
    <div class="modal-overlay" v-if="isVisible" @click="closeModal">
      <div class="modal-content" @click.stop>
        
        <!-- Header -->
        <div class="modal-header">
          <h2 class="modal-title">Follow track window</h2>
          
          <!-- Custom Select Dropdown -->
          <div class="vehicle-select" v-click-outside="() => showDropdown = false">
            <div class="select-trigger" @click="showDropdown = !showDropdown">
              <div class="selected-val" v-if="localSelectedVehicle">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="color: #64748b; margin-right: 6px;">
                  <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                </svg>
                <span class="v-name">{{ localSelectedVehicle.name }}</span>
              </div>
              <span v-else>Select a vehicle</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" class="dropdown-icon" :class="{'open': showDropdown}">
                <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </div>

            <div class="select-dropdown" v-if="showDropdown">
              <div class="search-wrap">
                 <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21L16.65 16.65"/></svg>
                 <input type="text" v-model="searchQuery" placeholder="Search vehicle..." autofocus />
              </div>
              <div class="options-list scrollbar-custom">
                <div class="option-item" 
                     v-for="v in filteredVehicles" 
                     :key="v.imei" 
                     :class="{'active': localSelectedVehicle && localSelectedVehicle.imei === v.imei}"
                     @click="selectVehicle(v)">
                  <div class="option-car-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                    </svg>
                  </div>
                  <span class="v-name">{{ v.name }}</span>
                </div>
                <div class="no-results" v-if="filteredVehicles.length === 0">No vehicles found.</div>
              </div>
            </div>
          </div>
          
          <button class="close-btn" @click="closeModal" title="Close"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/></svg></button>
        </div>

        <!-- Action Cards -->
        <div class="modal-body">
          <div class="action-cards">
            
            <!-- Inline Card -->
            <div class="action-card">
              <div class="card-title">Inside Screen</div>
              <div class="card-ill inline-ill">
                <div class="mock-browser">
                  <div class="mock-dots"></div>
                  <div class="mock-content"></div>
                </div>
              </div>
              <button class="card-btn" @click="handleAction('inline')">View Inline</button>
            </div>

            <!-- New Window Card -->
            <div class="action-card">
              <div class="card-title">Full View</div>
              <div class="card-ill full-ill">
                <div class="mock-browser full">
                  <div class="mock-dots"></div>
                </div>
              </div>
              <button class="card-btn" @click="handleAction('window')">New Window</button>
            </div>

          </div>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  isVisible: Boolean,
  initialVehicle: Object,
  fleetData: Array
});

const emit = defineEmits(['close', 'action-inline', 'action-window']);

const showDropdown = ref(false);
const searchQuery = ref('');
const localSelectedVehicle = ref(null);

watch(() => props.isVisible, (val) => {
  if (val) {
    localSelectedVehicle.value = props.initialVehicle;
    searchQuery.value = '';
    showDropdown.value = false;
  }
}, { immediate: true });

const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = function(event) {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event);
      }
    };
    document.body.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el) {
    document.body.removeEventListener('click', el.clickOutsideEvent);
  }
};

const filteredVehicles = computed(() => {
  if (!props.fleetData) return [];
  const query = searchQuery.value.toLowerCase().trim();
  if (!query) return props.fleetData;
  return props.fleetData.filter(v => v.name.toLowerCase().includes(query) || (v.imei && v.imei.includes(query)));
});

const selectVehicle = (v) => {
  localSelectedVehicle.value = v;
  showDropdown.value = false;
  searchQuery.value = '';
};

const closeModal = () => {
  emit('close');
};

const handleAction = (type) => {
  if (!localSelectedVehicle.value) {
    alert('Please select a vehicle first.');
    return;
  }
  
  if (type === 'inline') {
    emit('action-inline', localSelectedVehicle.value);
  } else if (type === 'window') {
    emit('action-window', localSelectedVehicle.value);
  }
  closeModal();
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  font-family: 'Outfit', 'Inter', sans-serif;
}

.modal-content {
  background: #0f172a; /* Default (Dark) */
  width: 550px;
  max-width: 90vw;
  border-radius: 12px;
  border: 1px solid #1e293b;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
  overflow: visible;
  display: flex;
  flex-direction: column;
}

body.light-mode .modal-content {
  background: white;
  border: none;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid #1e293b; /* Default (Dark) */
  position: relative;
}

body.light-mode .modal-header {
  border-color: #e2e8f0;
}

.modal-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #f8fafc; /* Default (Dark) */
  margin: 0;
}

body.light-mode .modal-title {
  color: #0c335a;
}

/* Custom Dropdown */
.vehicle-select {
  position: relative;
  min-width: 220px;
  font-size: 0.95rem;
}

.select-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 14px;
  border: 1.5px solid #3b82f6;
  border-radius: 12px;
  cursor: pointer;
  background: #1e293b; /* Default (Dark) */
  color: #f8fafc;
  font-weight: 700;
  transition: all 0.2s;
  min-width: 180px;
}

body.light-mode .select-trigger {
  background: #fff;
  color: #0c335a;
}

.selected-val {
  display: flex;
  align-items: center;
  gap: 8px;
}

.selected-val .v-name {
  font-size: 1.1rem;
}

.dropdown-icon {
  margin-left: 10px;
  transition: transform 0.2s;
}

.dropdown-icon.open {
  transform: rotate(180deg);
}

.select-dropdown {
  position: absolute;
  top: 110%;
  left: 0;
  right: 0;
  background: #1e293b; /* Default (Dark) */
  border: 1px solid #334155;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.6);
  z-index: 100;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 300px;
}

body.light-mode .select-dropdown {
  background: #ffffff;
  border-color: #e2e8f0;
  box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.search-wrap {
  display: flex;
  align-items: center;
  padding: 10px 14px;
  border-bottom: 1px solid #334155; /* Default (Dark) */
  background: #1a2233;
}

body.light-mode .search-wrap {
  border-bottom-color: #e2e8f0;
  background: #f8fafc;
}

.search-wrap input {
  border: none;
  background: transparent;
  width: 100%;
  padding-left: 10px;
  outline: none;
  font-size: 1rem;
  font-weight: 500;
}

.options-list {
  flex: 1;
  overflow-y: auto;
}

.option-item {
  display: flex;
  align-items: center;
  padding: 12px 14px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
}

.option-item:hover {
  background: #334155; /* Default (Dark) */
}

body.light-mode .option-item:hover {
  background: #eff6ff;
}

.option-item.active {
  background: #3b82f6;
  color: white;
}

.option-car-icon {
  width: 32px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 10px;
  color: #94a3b8;
}

.active .option-car-icon {
  color: white;
}

/* Close Button */
.close-btn {
  background: #0c335a;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  color: white;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 10px rgba(12, 51, 90, 0.3);
}

.close-btn:hover {
  background: #ef4444;
  transform: rotate(90deg);
  box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
}

/* Body Content */
.modal-body {
  padding: 40px 20px;
}

.action-cards {
  display: flex;
  gap: 20px;
  justify-content: center;
}

.action-card {
  flex: 1;
  max-width: 200px;
  background: #1e293b; /* Default (Dark) */
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.3);
  padding: 20px 15px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  border: 1px solid rgba(255,255,255,0.05);
  transition: transform 0.2s, box-shadow 0.2s;
}

body.light-mode .action-card {
  background: #ffffff;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  border: 1px solid transparent;
}

.action-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 25px rgba(0,0,0,0.1);
}

.card-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #f8fafc; /* Default (Dark) */
  margin-bottom: 15px;
}

body.light-mode .card-title {
  color: #1e293b;
}

/* Illustrations */
.card-ill {
  width: 80px;
  height: 60px;
  background: #334155; /* Default (Dark) */
  border-radius: 6px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
}

body.light-mode .card-ill {
  background: #e2e8f0;
}

.mock-browser {
  width: 100%;
  height: 100%;
  background: white;
  border-radius: 4px;
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mock-browser.full {
  background: #4A81D3;
}

.mock-dots {
  height: 6px;
  display: flex;
  padding: 4px;
  gap: 2px;
}

.mock-dots::before, .mock-dots::after, .mock-dots {
  content: '';
}

.mock-dots {
  position: relative;
}

.mock-browser .mock-dots {
  background: rgba(0,0,0,0.05);
}

.mock-browser.full .mock-dots {
  background: rgba(255,255,255,0.2);
}

.mock-browser .mock-dots::before, .mock-browser .mock-dots::after {
  width: 3px;
  height: 3px;
  border-radius: 50%;
  background: rgba(0,0,0,0.2);
  position: absolute;
  top: 4px;
}

.mock-browser.full .mock-dots::before, .mock-browser.full .mock-dots::after {
  background: rgba(255,255,255,0.5);
}

.mock-browser .mock-dots::before { left: 4px; }
.mock-browser .mock-dots::after { left: 9px; }

.mock-content {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.mock-content::before {
  content: '';
  width: 30px;
  height: 20px;
  background: #4A81D3;
  border-radius: 4px;
}

.card-btn {
  background: #0047AB;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 6px;
  font-weight: 600;
  width: 100%;
  cursor: pointer;
  transition: background 0.2s;
  font-size: 0.9rem;
}

.card-btn:hover {
  background: #003380;
}

/* Scrollbar */
.scrollbar-custom::-webkit-scrollbar {
  width: 6px;
}
.scrollbar-custom::-webkit-scrollbar-track {
  background: rgba(0,0,0,0.05);
}
.scrollbar-custom::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 3px;
}
</style>
