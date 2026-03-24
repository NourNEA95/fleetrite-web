<template>
  <div class="inner-modal-backdrop" @click.self="$emit('close')">
    <div class="inner-modal-container glass-effect">
      <!-- Header -->
      <div class="inner-header">
        <h3 class="inner-title">Report properties</h3>
        <button class="inner-close" @click="$emit('close')" :disabled="isGenerating">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Catchy Loading Overlay -->
      <Transition name="fade-loading">
        <div v-if="isGenerating" class="generation-overlay">
          <div class="loader-container">
            <div class="radar-scanner">
              <div class="scanner-circle"></div>
              <div class="scanner-hand"></div>
              <div class="scanner-dots">
                <div class="dot d1"></div>
                <div class="dot d2"></div>
                <div class="dot d3"></div>
              </div>
            </div>
            
            <div class="loading-content">
              <h2 class="loading-title">
                <span class="en">Processing Report Data...</span>
                <span class="ar">جاري معالجة بيانات التقرير...</span>
              </h2>
              <p class="loading-subtitle">
                <span class="en">Synchronizing with GPS satellites and calculating metrics</span>
                <span class="ar">مزامنة البيانات والحسابات الدقيقة جارية الآن</span>
              </p>
              
              <div class="progress-bar-container">
                <div class="premium-progress-bar" :style="{ width: generationProgress + '%' }"></div>
              </div>
              
              <div v-if="totalChunks > 1" class="progress-info">
                <span class="progress-text">Processing Batch {{ currentChunk }} of {{ totalChunks }}</span>
              </div>
              
              <div class="loading-steps">
                <div class="step pulse">⚡ Collecting Vehicle Data</div>
                <div class="step pulse delay-1">📊 Calculating Distance & Fuel</div>
                <div class="step pulse delay-2">✨ Finalizing Results</div>
              </div>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Scrollable Content -->
      <div class="inner-body custom-scrollbar">
        <!-- Section: Report -->
        <div class="form-section">
          <div class="section-badge-title">Report</div>
          <div class="report-grid">
            <!-- Left Side: Basic Fields -->
            <!-- Left Side: Basic Fields -->
            <div class="report-col">
              <div class="form-field-row">
                <label>Contract No</label>
                <input type="text" v-model="form.name2" class="premium-field" placeholder="Contract Number" />
              </div>
              <div class="form-field-row">
                <label>Contract Title</label>
                <input type="text" v-model="form.name3" class="premium-field" placeholder="Contract Title" />
              </div>
              <div class="form-field-row">
                <label>Name</label>
                <input type="text" v-model="form.name" class="premium-field" placeholder="Untitled Report" />
              </div>
              <div class="form-field-row">
                <label>Type</label>
                <div class="premium-field multi-select-wrapper" :class="{ 'on-top': activeDropdown === 'type' }" v-click-outside="() => activeDropdown === 'type' && (activeDropdown = null)">
                  <div class="custom-multi-select" @click.stop="toggleDropdown('type')">
                    <span class="truncate">{{ reportTypes.find(t => t.value === form.type)?.label || 'Select Type' }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </div>
                  <div v-if="activeDropdown === 'type'" class="dropdown-panel glass-panel">
                    <div class="panel-list custom-scrollbar">
                      <div v-for="group in groupedReportTypes" :key="group.label">
                        <div class="dropdown-group-title">{{ group.label }}</div>
                        <div v-for="type in group.items" :key="type.value" 
                             class="dropdown-item-single" 
                             :class="{ active: form.type === type.value }"
                             @click.stop="form.type = type.value; handleTypeChange(); activeDropdown = null">
                          {{ type.label }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Objects Select -->
              <div v-if="isFieldEnabled('imei')" class="form-field-row" :class="{ disabled: !isFieldEnabled('imei') }">
                <label>Objects</label>
                <div class="premium-field multi-select-wrapper" :class="{ 'on-top': activeDropdown === 'objects' }" v-click-outside="() => activeDropdown === 'objects' && (activeDropdown = null)">
                  <div class="custom-multi-select" @click.stop="toggleDropdown('objects')">
                    <span class="truncate">{{ form.imei.length ? `${form.imei.length} selected` : 'Select Objects...' }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </div>
                  <div v-if="activeDropdown === 'objects'" class="dropdown-panel glass-panel">
                    <div class="panel-header">
                      <input type="text" v-model="searchTerms.objects" placeholder="Search objects..." class="dropdown-search" @click.stop />
                      <button class="select-all-btn" @click.stop="toggleSelectAll('objects')">
                        {{ form.imei.length === (metadata.objects?.length || 0) ? 'Deselect All' : 'Select All' }}
                      </button>
                    </div>
                    <div class="panel-list custom-scrollbar">
                      <label v-for="obj in filteredMetadata.objects" :key="obj.imei" class="dropdown-item" @click.stop>
                        <input type="checkbox" :value="obj.imei" v-model="form.imei" />
                        <span class="item-label">{{ obj.name }}</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Markers Select -->
              <!-- Drivers Select (Conditional) -->
              <div v-if="isFieldEnabled('driver_ids')" class="form-field-row">
                <label>Drivers</label>
                <div class="premium-field multi-select-wrapper" :class="{ 'on-top': activeDropdown === 'drivers' }" v-click-outside="() => activeDropdown === 'drivers' && (activeDropdown = null)">
                  <div class="custom-multi-select" @click.stop="toggleDropdown('drivers')">
                    <span class="truncate">{{ (form.driver_ids?.length || 0) ? `${form.driver_ids.length} selected` : 'Select Drivers...' }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </div>
                  <div v-if="activeDropdown === 'drivers'" class="dropdown-panel glass-panel">
                    <div class="panel-header">
                      <input type="text" v-model="searchTerms.drivers" placeholder="Search drivers..." class="dropdown-search" @click.stop />
                      <button class="select-all-btn" @click.stop="toggleSelectAll('drivers')">
                        {{ (form.driver_ids?.length || 0) === (metadata.drivers?.length || 0) ? 'Deselect All' : 'Select All' }}
                      </button>
                    </div>
                    <div class="panel-list custom-scrollbar">
                      <label v-for="d in filteredMetadata.drivers" :key="d.driver_id" class="dropdown-item" @click.stop>
                        <input type="checkbox" :value="d.driver_id" v-model="form.driver_ids" />
                        <span class="item-label">{{ d.driver_name }}</span>
                      </label>
                      <div v-if="!filteredMetadata.drivers.length" class="no-data-hint">No drivers found</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Markers Select -->
              <div class="form-field-row" :class="{ disabled: !isFieldEnabled('marker_ids') }">
                <label>Markers</label>
                <div class="premium-field multi-select-wrapper" :class="{ 'on-top': activeDropdown === 'markers' }" v-click-outside="() => activeDropdown === 'markers' && (activeDropdown = null)">
                  <div class="custom-multi-select" @click.stop="isFieldEnabled('marker_ids') && toggleDropdown('markers')">
                    <span class="truncate">{{ (form.marker_ids?.length || 0) ? `${form.marker_ids.length} selected` : 'Select Markers...' }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </div>
                  <div v-if="activeDropdown === 'markers'" class="dropdown-panel glass-panel">
                   <div class="panel-header">
                      <input type="text" v-model="searchTerms.markers" placeholder="Search markers..." class="dropdown-search" @click.stop />
                      <button class="select-all-btn" @click.stop="toggleSelectAll('markers')">
                         {{ (form.marker_ids?.length || 0) === (metadata.markers?.length || 0) ? 'Deselect All' : 'Select All' }}
                      </button>
                    </div>
                    <div class="panel-list custom-scrollbar">
                      <label v-for="m in filteredMetadata.markers" :key="m.marker_id" class="dropdown-item" @click.stop>
                        <input type="checkbox" :value="m.marker_id" v-model="form.marker_ids" />
                        <span class="item-label">{{ m.marker_name }}</span>
                      </label>
                      <div v-if="!filteredMetadata.markers.length" class="no-data-hint">No markers found</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Zones Select -->
              <div class="form-field-row" :class="{ disabled: !isFieldEnabled('zone_ids') }">
                <label>Zones</label>
                <div class="premium-field multi-select-wrapper" :class="{ 'on-top': activeDropdown === 'zones' }" v-click-outside="() => activeDropdown === 'zones' && (activeDropdown = null)">
                  <div class="custom-multi-select" @click.stop="isFieldEnabled('zone_ids') && toggleDropdown('zones')">
                    <span class="truncate">{{ form.zone_ids.length ? `${form.zone_ids.length} selected` : 'Select Zones...' }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </div>
                  <div v-if="activeDropdown === 'zones'" class="dropdown-panel glass-panel">
                    <div class="panel-header">
                      <input type="text" v-model="searchTerms.zones" placeholder="Search zones..." class="dropdown-search" @click.stop />
                      <button class="select-all-btn" @click.stop="toggleSelectAll('zones')">
                         {{ (form.zone_ids?.length || 0) === (metadata.zones?.length || 0) ? 'Deselect All' : 'Select All' }}
                      </button>
                    </div>
                    <div class="panel-list custom-scrollbar">
                      <label v-for="z in filteredMetadata.zones" :key="z.zone_id" class="dropdown-item" @click.stop>
                        <input type="checkbox" :value="z.zone_id" v-model="form.zone_ids" />
                        <span class="item-label">{{ z.zone_name }}</span>
                      </label>
                      <div v-if="!filteredMetadata.zones.length" class="no-data-hint">No zones found</div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Sensors Select -->
              <div class="form-field-row" :class="{ disabled: !isFieldEnabled('sensor_names') }">
                <label>Sensors</label>
                <div class="premium-field multi-select-wrapper" :class="{ 'on-top': activeDropdown === 'sensors' }" v-click-outside="() => activeDropdown === 'sensors' && (activeDropdown = null)">
                   <div class="custom-multi-select" @click.stop="isFieldEnabled('sensor_names') && toggleDropdown('sensors')">
                    <span class="truncate">{{ (form.sensor_names?.length || 0) ? `${form.sensor_names.length} selected` : 'Select Sensors...' }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </div>
                  <div v-if="activeDropdown === 'sensors'" class="dropdown-panel glass-panel">
                    <div class="panel-list custom-scrollbar">
                      <div v-if="!(metadata.sensors?.length)" class="no-data-hint">
                        {{ (form.imei?.length || 0) ? 'No sensors found' : 'Select objects first' }}
                      </div>
                      <label v-for="s in metadata.sensors" :key="s" class="dropdown-item" @click.stop>
                        <input type="checkbox" :value="s" v-model="form.sensor_names" />
                        <span class="item-label">{{ s }}</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>


              <!-- Data Items Select -->
              <div class="form-field-row">
                <label>Data items</label>
                 <div class="premium-field multi-select-wrapper" :class="{ 'on-top': activeDropdown === 'data_items' }" v-click-outside="() => activeDropdown === 'data_items' && (activeDropdown = null)">
                   <div class="custom-multi-select" @click.stop="toggleDropdown('data_items')">
                    <span class="truncate">{{ (form.data_items?.length || 0) ? `${form.data_items.length} items selected` : 'All items selected' }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </div>
                  <div v-if="activeDropdown === 'data_items'" class="dropdown-panel glass-panel">
                    <div class="panel-header no-search">
                      <button class="select-all-btn" @click.stop="toggleSelectAll('data_items')">
                        {{ (form.data_items?.length || 0) === (dataItemsList.value?.length || 0) ? 'Deselect All' : 'Select All' }}
                      </button>
                    </div>
                    <div class="panel-list custom-scrollbar">
                      <label v-for="item in dataItemsList" :key="item.value" class="dropdown-item" @click.stop>
                        <input type="checkbox" :value="item.value" v-model="form.data_items" />
                        <span class="item-label">{{ item.label }}</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              <div class="form-field-row">
                <label>Format</label>
                <div class="premium-field multi-select-wrapper" :class="{ 'on-top': activeDropdown === 'format' }" v-click-outside="() => activeDropdown === 'format' && (activeDropdown = null)">
                  <div class="custom-multi-select" @click.stop="toggleDropdown('format')">
                    <span class="truncate">{{ form.format.toUpperCase() }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </div>
                  <div v-if="activeDropdown === 'format'" class="dropdown-panel glass-panel">
                    <div class="panel-list custom-scrollbar">
                      <div v-for="fmt in availableFormats" :key="fmt" 
                           class="dropdown-item-single"
                           :class="{ active: form.format.toUpperCase() === fmt }"
                           @click.stop="form.format = fmt.toLowerCase(); activeDropdown = null">
                        {{ fmt }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Side: Checkboxes & Special Controls -->
            <div class="report-col">
              <div class="checkbox-stack">
                <label class="premium-cb" :class="{ disabled: !isFieldEnabled('ignore_empty_reports') }">
                  <input type="checkbox" v-model="form.ignore_empty_reports" :disabled="!isFieldEnabled('ignore_empty_reports')" /> Ignore empty reports
                </label>
                <label class="premium-cb" :class="{ disabled: !isFieldEnabled('show_coordinates') }">
                  <input type="checkbox" v-model="form.show_coordinates" :disabled="!isFieldEnabled('show_coordinates')" /> Show coordinates
                </label>
                <label class="premium-cb" :class="{ disabled: !isFieldEnabled('show_addresses') }">
                  <input type="checkbox" v-model="form.show_addresses" :disabled="!isFieldEnabled('show_addresses')" /> Show addresses
                </label>
                <label class="premium-cb" :class="{ disabled: !isFieldEnabled('markers_addresses') }">
                  <input type="checkbox" v-model="form.markers_addresses" :disabled="!isFieldEnabled('markers_addresses')" /> Markers instead of addresses
                </label>
                <label class="premium-cb" :class="{ disabled: !isFieldEnabled('zones_addresses') }">
                  <input type="checkbox" v-model="form.zones_addresses" :disabled="!isFieldEnabled('zones_addresses')" /> Zones instead of addresses
                </label>
              </div>

              <div class="form-field-row mt-32" :class="{ disabled: !isFieldEnabled('stop_duration') }">
                <label>Stops</label>
                <div class="premium-field multi-select-wrapper" :class="{ 'on-top': activeDropdown === 'stops' }" v-click-outside="() => activeDropdown === 'stops' && (activeDropdown = null)">
                  <div class="custom-multi-select" @click.stop="isFieldEnabled('stop_duration') && toggleDropdown('stops')">
                    <span class="truncate">> {{ form.stop_duration }} min</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </div>
                  <div v-if="activeDropdown === 'stops'" class="dropdown-panel glass-panel">
                    <div class="panel-list custom-scrollbar">
                      <div v-for="stop in [5,10,20,30,60,120,300]" :key="stop" 
                           class="dropdown-item-single"
                           :class="{ active: form.stop_duration === stop }"
                           @click.stop="form.stop_duration = stop; activeDropdown = null">
                        > {{ stop < 60 ? stop + ' min' : (stop/60) + ' h' }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="form-field-row" :class="{ disabled: !isFieldEnabled('speed_limit') }">
                <label>Speed limit</label>
                <div class="input-with-unit">
                  <input type="number" v-model="form.speed_limit" class="premium-field" placeholder="0" :disabled="!isFieldEnabled('speed_limit')" />
                  <span class="unit">km/h</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Section: Day/Night (Conditional) -->
        <div v-if="isFieldEnabled('other_dn')" class="form-section">
          <div class="section-badge-title">Day/Night Settings</div>
          <div class="report-grid">
            <div class="report-col">
              <div class="form-field-row">
                <label>Night starts</label>
                <div class="time-selects">
                  <select v-model="form.other_dn_starts_hour" class="premium-field time-unit"><option v-for="h in 24" :key="h-1" :value="String(h-1).padStart(2, '0')">{{ String(h-1).padStart(2, '0') }}</option></select>
                  <select v-model="form.other_dn_starts_minute" class="premium-field time-unit"><option v-for="m in 60" :key="m-1" :value="String(m-1).padStart(2, '0')">{{ String(m-1).padStart(2, '0') }}</option></select>
                </div>
              </div>
            </div>
            <div class="report-col">
              <div class="form-field-row">
                <label>Night ends</label>
                <div class="time-selects">
                  <select v-model="form.other_dn_ends_hour" class="premium-field time-unit"><option v-for="h in 24" :key="h-1" :value="String(h-1).padStart(2, '0')">{{ String(h-1).padStart(2, '0') }}</option></select>
                  <select v-model="form.other_dn_ends_minute" class="premium-field time-unit"><option v-for="m in 60" :key="m-1" :value="String(m-1).padStart(2, '0')">{{ String(m-1).padStart(2, '0') }}</option></select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Section: RAG Score (Conditional) -->
        <div v-if="isFieldEnabled('other_rag')" class="form-section">
          <div class="section-badge-title">RAG Score Settings</div>
          <div class="report-grid">
            <div class="report-col">
              <div class="form-field-row">
                <label>Lowest score</label>
                <input type="number" v-model="form.other_rag_low_score" class="premium-field" />
              </div>
            </div>
            <div class="report-col">
              <div class="form-field-row">
                <label>Highest score</label>
                <input type="number" v-model="form.other_rag_high_score" class="premium-field" />
              </div>
            </div>
          </div>
        </div>

        <div class="footer-sections-grid">
          <!-- Section: Schedule -->
          <div class="form-section">
            <div class="section-badge-title">Schedule</div>
            <div class="schedule-inputs">
              <div class="checkbox-stack horizontal">
                <label class="premium-cb"><input type="checkbox" v-model="form.schedule_daily" /> Daily</label>
                <label class="premium-cb"><input type="checkbox" v-model="form.schedule_weekly" /> Weekly</label>
                <label class="premium-cb"><input type="checkbox" v-model="form.schedule_monthly" /> Monthly</label>
              </div>
              <div class="form-field-row mt-16">
                <label>Email</label>
                <input type="email" v-model="form.schedule_email_address" class="premium-field" placeholder="Enter emails separated by comma" />
              </div>
            </div>
          </div>

          <!-- Section: Time Period -->
          <div class="form-section">
            <div class="section-badge-title">Time period</div>
            <div class="time-period-inputs">
              <div class="form-field-row">
                <label>Filter</label>
                <div class="premium-field multi-select-wrapper" :class="{ 'on-top': activeDropdown === 'filter' }" v-click-outside="() => activeDropdown === 'filter' && (activeDropdown = null)">
                  <div class="custom-multi-select" @click.stop="toggleDropdown('filter')">
                    <span class="truncate">{{ (timeFilters.find(f => f.value === form.time_period_filter) || {label: 'Custom'}).label }}</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </div>
                  <div v-if="activeDropdown === 'filter'" class="dropdown-panel glass-panel">
                    <div class="panel-list custom-scrollbar">
                      <div v-for="filter in timeFilters" :key="filter.value" 
                           class="dropdown-item-single"
                           :class="{ active: form.time_period_filter === filter.value }"
                           @click.stop="form.time_period_filter = filter.value; activeDropdown = null">
                        {{ filter.label }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="form-field-row">
                <label>Time from</label>
                <div class="datetime-picker">
                  <input type="date" v-model="form.date_from" class="premium-field date-input" />
                  <div class="time-selects">
                    <select v-model="form.hour_from" class="premium-field time-unit"><option v-for="h in 24" :key="h-1" :value="String(h-1).padStart(2, '0')">{{ String(h-1).padStart(2, '0') }}</option></select>
                    <select v-model="form.minute_from" class="premium-field time-unit"><option v-for="m in 60" :key="m-1" :value="String(m-1).padStart(2, '0')">{{ String(m-1).padStart(2, '0') }}</option></select>
                  </div>
                </div>
              </div>
              <div class="form-field-row">
                <label>Time to</label>
                <div class="datetime-picker">
                  <input type="date" v-model="form.date_to" class="premium-field date-input" />
                  <div class="time-selects">
                    <select v-model="form.hour_to" class="premium-field time-unit"><option v-for="h in 24" :key="h-1" :value="String(h-1).padStart(2, '0')">{{ String(h-1).padStart(2, '0') }}</option></select>
                    <select v-model="form.minute_to" class="premium-field time-unit"><option v-for="m in 60" :key="m-1" :value="String(m-1).padStart(2, '0')">{{ String(m-1).padStart(2, '0') }}</option></select>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Action Buttons -->
      <div class="inner-footer" :class="{ 'disabled-footer': isGenerating }">
        <button class="action-btn generate-btn" @click="handleGenerate" :disabled="isGenerating">
          {{ isGenerating ? 'Generating...' : 'Generate' }}
        </button>
        <button class="action-btn save-btn" @click="handleSave" :disabled="isSaving || isGenerating">
          {{ isSaving ? 'Saving...' : 'Save' }}
        </button>
        <button class="action-btn cancel-btn" @click="$emit('close')" :disabled="isGenerating">Cancel</button>
      </div>
    </div>

    <!-- Toast Notifications -->
    <PremiumToast 
      v-if="toast.show" 
      :show="toast.show" 
      :message="toast.message" 
      :type="toast.type" 
      @close="toast.show = false" 
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import api from '../../services/api';
import PremiumToast from '../common/PremiumToast.vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();

const props = defineProps({
  editData: {
    type: Object,
    default: null
  }
});
const emit = defineEmits(['close', 'saved', 'generated']);

const toast = reactive({
  show: false,
  message: '',
  type: 'success'
});

const isSaving = ref(false);
const isGenerating = ref(false);
const activeDropdown = ref(null);
const generationProgress = ref(0);
const totalChunks = ref(1);
const currentChunk = ref(0);

const showToast = (message, type = 'success') => {
  toast.message = message;
  toast.type = type;
  toast.show = true;
};
const groupedReportTypes = [
  {
    label: 'Text reports',
    items: [
      { value: 'general', label: 'General information' },
      { value: 'general_merged', label: 'General information (merged)' },
      { value: 'general_accuracy', label: 'General Accuracy' },
      { value: 'tasks', label: 'Tasks' },
      { value: 'object_info', label: 'Object information' },
      { value: 'location_device', label: 'Device position' },
      { value: 'current_position', label: 'Current position' },
      { value: 'current_position_off', label: 'Current position (offline)' },
      { value: 'route_data_sensors', label: 'Route data with sensors' },
      { value: 'drives_stops', label: 'Drives and stops' },
      { value: 'drives_stops_sensors', label: 'Drives and stops with sensors' },
      { value: 'drives_stops_logic', label: 'Drives and stops with logic sensors' },
      { value: 'travel_sheet', label: 'Travel sheet' },
      { value: 'travel_sheet_dn', label: 'Travel sheet (Day/Night)' },
      { value: 'travel_without_driver', label: 'Travel trips without driver' },
      { value: 'mileage_daily', label: 'Mileage daily' },
      { value: 'mileage_daily_new', label: 'Mileage daily (new)' },
      { value: 'overspeed', label: 'Overspeeds' },
      { value: 'overspeed_accuracy', label: 'Overspeeds Accuracy' },
      { value: 'overspeed_count', label: 'Overspeed count merged' },
      { value: 'overspeed_zone_in', label: 'Overspeed zone' },
      { value: 'underspeed', label: 'Underspeeds' },
      { value: 'underspeed_count', label: 'Underspeed count merged' },
      { value: 'marker_in_out', label: 'Marker in/out' },
      { value: 'marker_in_out_gen', label: 'Marker in/out with gen information' },
      { value: 'zone_in_out', label: 'Zone in/out' },
      { value: 'zone_in_out_general', label: 'Zone in/out with gen information' },
      { value: 'events', label: 'Events' },
      { value: 'service', label: 'Service' },
      { value: 'service2', label: 'Service with information' },
      { value: 'fuelfillings', label: 'Fuel fillings' },
      { value: 'fuelthefts', label: 'Fuel thefts' },
      { value: 'logic_sensors', label: 'Logic sensors' },
      { value: 'rilogbook', label: 'RFID and iButton logbook' },
      { value: 'dtc', label: 'Diagnostic Trouble Codes' },
      { value: 'expenses', label: 'Expenses' }
    ]
  },
  {
    label: 'Graphical reports',
    items: [
      { value: 'speed_graph', label: 'Speed' },
      { value: 'altitude_graph', label: 'Altitude' },
      { value: 'acc_graph', label: 'Ignition' },
      { value: 'fuellevel_graph', label: 'Fuel level' },
      { value: 'temperature_graph', label: 'Temperature' },
      { value: 'sensor_graph', label: 'Sensor' }
    ]
  },
  {
    label: 'Map reports',
    items: [
      { value: 'routes', label: 'Routes' },
      { value: 'routes_stops', label: 'Routes with stops' }
    ]
  },
  {
    label: 'Media reports',
    items: [
      { value: 'image_gallery', label: 'Image Gallery' }
    ]
  },
  {
    label: 'Driver reports',
    items: [
      { value: 'driver_overspeed', label: 'Driver overspeed' },
      { value: 'driver_travel_sheet', label: 'Driver travels' },
      { value: 'rag_driver', label: 'Driver behavior RAG by driver' },
      { value: 'rag', label: 'Driver behavior RAG by object' },
      { value: 'driver_mileage', label: 'Driver mileage' },
      { value: 'driver_score_new', label: 'Driver score' }
    ]
  },
  {
    label: 'KOC reports',
    items: [
      { value: 'overspeednew', label: 'KOC Directorate' },
      { value: 'overspeednew2', label: 'KOC Directorate Summary' },
      { value: 'IVMS', label: 'IVMS' },
      { value: 'overspeednew_zone', label: 'KOC in zone' },
      { value: 'overspeednew2_zone', label: 'KOC Directorate Summary Zones' },
      { value: 'IVMS_zone', label: 'IVMS Zones' }
    ]
  }
];

const reportTypes = computed(() => {
  return groupedReportTypes.flatMap(g => g.items);
});

// Reactive Form State
const form = reactive({
  id: null,
  name: '',
  type: 'general',
  imei: [],
  marker_ids: [],
  zone_ids: [],
  driver_ids: [],
  sensor_names: [],
  data_items: [],
  format: 'html',
  ignore_empty_reports: false,
  show_coordinates: false,
  show_addresses: false,
  markers_addresses: false,
  zones_addresses: false,
  stop_duration: 5,
  speed_limit: '',
  name2: '',
  name3: '',
  schedule_daily: false,
  schedule_weekly: false,
  schedule_monthly: false,
  schedule_email_address: '',
  time_period_filter: 'today',
  date_from: new Date().toISOString().split('T')[0],
  hour_from: '00',
  minute_from: '00',
  date_to: new Date().toISOString().split('T')[0],
  hour_to: '23',
  minute_to: '59',
  other_dn_starts_hour: '22',
  other_dn_starts_minute: '00',
  other_dn_ends_hour: '06',
  other_dn_ends_minute: '00',
  other_rag_low_score: '0',
  other_rag_high_score: '5'
});

onMounted(async () => {
  await fetchMetadata();
  
  if (props.editData) {
    const d = props.editData;
    form.id = d.report_id;
    form.name = d.name || '';
    form.name2 = d.name2 || '';
    form.name3 = d.name3 || '';
    form.type = d.type || 'general';
    form.format = (d.format || 'html').toLowerCase();
    
    // Booleans stored as strings in legacy
    form.ignore_empty_reports = d.ignore_empty_reports === 'true';
    form.show_coordinates = d.show_coordinates === 'true';
    form.show_addresses = d.show_addresses === 'true';
    form.markers_addresses = d.markers_addresses === 'true';
    form.zones_addresses = d.zones_addresses === 'true';
    
    form.stop_duration = parseInt(d.stop_duration) || 5;
    form.speed_limit = d.speed_limit || '';
    
    // Comma separated strings to arrays
    form.imei = d.imei ? d.imei.split(',') : [];
    form.marker_ids = d.marker_ids ? d.marker_ids.split(',') : [];
    form.zone_ids = d.zone_ids ? d.zone_ids.split(',') : [];
    form.driver_ids = d.driver_ids ? d.driver_ids.split(',') : [];
    form.sensor_names = d.sensor_names ? d.sensor_names.split(',') : [];
    form.data_items = d.data_items ? d.data_items.split(',') : [];
    
    // Schedule period
    const sp = d.schedule_period || '';
    form.schedule_daily = sp.includes('d');
    form.schedule_weekly = sp.includes('w');
    form.schedule_monthly = sp.includes('m');
    form.schedule_email_address = d.schedule_email_address || '';

    // Handle "other" field (JSON string)
    if (d.other) {
      try {
        const other = JSON.parse(d.other);
        if (form.type === 'travel_sheet_dn') {
          form.other_dn_starts_hour = other.dn_starts_hour || '22';
          form.other_dn_starts_minute = other.dn_starts_minute || '00';
          form.other_dn_ends_hour = other.dn_ends_hour || '06';
          form.other_dn_ends_minute = other.dn_ends_minute || '00';
        } else if (['rag', 'rag_driver'].includes(form.type)) {
          form.other_rag_low_score = other.low_score || '0';
          form.other_rag_high_score = other.high_score || '5';
        }
      } catch (e) {
        console.error('Failed to parse other field:', e);
      }
    }
  } else {
    handleTypeChange();
  }
});
const metadata = reactive({
  objects: [],
  zones: [],
  markers: [],
  drivers: [],
  sensors: []
});

const searchTerms = reactive({
  objects: '',
  markers: '',
  zones: '',
  drivers: ''
});


const timeFilters = [
  { value: 'last_hour', label: 'Last hour' },
  { value: 'today', label: 'Today' },
  { value: 'yesterday', label: 'Yesterday' },
  { value: 'two_days', label: 'Two days ago' },
  { value: 'three_days', label: 'Three days ago' },
  { value: 'this_week', label: 'Current week' },
  { value: 'last_week', label: 'Last week' },
  { value: 'this_month', label: 'Current month' },
  { value: 'last_month', label: 'Last month' }
];

const DATA_ITEMS_MAPPING = {
  general: ["route_start", "route_end", "route_length", "move_duration", "stop_duration", "stop_count", "top_speed", "avg_speed", "overspeed_count", "fuel_consumption", "avg_fuel_consumption", "fuel_cost", "engine_work", "engine_idle", "odometer", "engine_hours", "driver", "trailer", "custom_fields"],
  general_accuracy: ["route_start", "route_end", "route_length", "move_duration", "stop_duration", "stop_count", "top_speed", "avg_speed", "overspeed_count", "fuel_consumption", "avg_fuel_consumption", "fuel_cost", "engine_work", "engine_idle", "odometer", "engine_hours", "driver", "trailer", "custom_fields"],
  general_merged: ["route_start", "route_end", "route_length", "move_duration", "stop_duration", "stop_count", "top_speed", "avg_speed", "overspeed_count", "fuel_consumption", "avg_fuel_consumption", "fuel_cost", "engine_work", "engine_idle", "odometer", "engine_hours", "driver", "trailer", "total"],
  object_info: ["imei", "group", "transport_model", "vin", "plate_number", "odometer", "engine_hours", "driver", "trailer", "gps_device", "sim_card_number"],
  current_position: ["time", "position", "speed", "altitude", "angle", "status", "odometer", "engine_hours", "driver", "trailer"],
  current_position_off: ["time", "position", "speed", "altitude", "angle", "status", "odometer", "engine_hours", "driver", "trailer"],
  route_data_sensors: ["time", "position", "speed", "altitude", "angle"],
  drives_stops: ["status", "start", "end", "duration", "move_duration", "stop_duration", "route_length", "top_speed", "avg_speed", "fuel_consumption", "avg_fuel_consumption", "fuel_cost", "engine_work", "engine_idle", "driver", "trailer"],
  drives_stops_sensors: ["status", "start", "end", "duration", "move_duration", "stop_duration", "route_length", "top_speed", "avg_speed", "fuel_consumption", "avg_fuel_consumption", "fuel_cost", "engine_work", "engine_idle", "driver", "trailer"],
  drives_stops_logic: ["status", "start", "end", "duration", "move_duration", "stop_duration", "route_length", "top_speed", "avg_speed", "fuel_consumption", "avg_fuel_consumption", "fuel_cost", "engine_work", "engine_idle", "driver", "trailer"],
  travel_sheet: ["time_a", "position_a", "odometer_a", "time_b", "position_b", "odometer_b", "duration", "route_length", "fuel_consumption", "avg_fuel_consumption", "fuel_cost", "driver", "trailer", "total"],
  driver_mileage: ["time_a", "position_a", "odometer_a", "time_b", "position_b", "odometer_b", "duration", "route_length", "fuel_consumption", "avg_fuel_consumption", "fuel_cost", "driver", "trailer", "total"],
  driver_travel_sheet: ["time_a", "position_a", "odometer_a", "time_b", "position_b", "odometer_b", "duration", "route_length", "fuel_consumption", "avg_fuel_consumption", "fuel_cost", "driver", "trailer", "total"],
  travel_sheet_dn: ["time_a", "position_a", "odometer_a", "time_b", "position_b", "odometer_b", "duration", "route_length", "fuel_consumption", "avg_fuel_consumption", "fuel_cost", "driver", "trailer", "total"],
  mileage_daily: ["time", "start", "end", "move_duration", "route_length", "fuel_consumption", "avg_fuel_consumption", "fuel_cost", "engine_hours", "driver", "trailer", "total"],
  overspeed: ["start", "end", "duration", "top_speed", "avg_speed", "overspeed_position", "driver", "trailer"],
  overspeed_accuracy: ["start", "end", "duration", "top_speed", "avg_speed", "overspeed_position", "driver", "trailer"],
  overspeed_count: ["route_start", "route_end", "route_length", "move_duration", "top_speed", "avg_speed", "overspeed_count"],
  underspeed: ["start", "end", "duration", "top_speed", "avg_speed", "underspeed_position", "driver", "trailer"],
  underspeed_count: ["route_start", "route_end", "route_length", "move_duration", "top_speed", "avg_speed", "underspeed_count"],
  marker_in_out: ["marker_in", "marker_out", "duration", "route_length", "engine_hours", "marker_name", "marker_position", "total"],
  marker_in_out_gen: ["marker_in", "marker_out", "duration", "route_length", "engine_hours", "marker_name", "marker_position", "total"],
  zone_in_out: ["zone_in", "zone_out", "duration", "route_length", "engine_hours", "zone_name", "zone_position", "total"],
  zone_in_out_general: ["zone_in", "zone_out", "duration", "route_length", "engine_hours", "zone_name", "zone_position", "total"],
  events: ["time", "event", "event_position", "driver", "trailer", "total"],
  service: ["service", "last_service", "status"],
  rag: ["overspeed_score", "harsh_acceleration_score", "harsh_braking_score", "harsh_cornering_score"],
  rag_driver: ["overspeed_score", "harsh_acceleration_score", "harsh_braking_score", "harsh_cornering_score"],
  tasks: ["name", "description", "from", "start_time", "to", "end_time", "priority", "status"],
  rilogbook: ["group", "name", "position"],
  dtc: ["code", "position"],
  expenses: ["date", "name", "object", "quantity", "cost", "supplier", "buyer", "odometer", "engine_hours", "description", "total"],
  logic_sensors: ["sensor", "activation_time", "deactivation_time", "duration", "activation_position", "deactivation_position"],
  fuelfillings: ["time", "position", "before", "after", "filled", "sensor", "driver", "trailer", "total"],
  fuelthefts: ["time", "position", "before", "after", "stolen", "sensor", "driver", "trailer", "total"],
  routes: ["route_start", "route_end", "route_length", "move_duration", "stop_duration", "stop_count", "top_speed", "avg_speed", "overspeed_count", "fuel_consumption", "avg_fuel_consumption", "fuel_cost", "engine_work", "engine_idle", "odometer", "engine_hours", "driver", "trailer"],
  routes_stops: ["route_start", "route_end", "route_length", "move_duration", "stop_duration", "stop_count", "top_speed", "avg_speed", "overspeed_count", "fuel_consumption", "avg_fuel_consumption", "fuel_cost", "engine_work", "engine_idle", "odometer", "engine_hours", "driver", "trailer"],
  image_gallery: ["time", "position"],
  overspeednew: ["route_start", "route_end", "route_length", "move_duration", "stop_duration", "stop_count", "top_speed", "avg_speed", "overspeed_count", "fuel_consumption", "avg_fuel_consumption", "fuel_cost", "engine_work", "engine_idle", "odometer", "engine_hours", "driver", "trailer", "custom_fields"],
};

// Search logic
const filteredMetadata = computed(() => {
  return {
    objects: (metadata.objects || []).filter(o => o.name?.toLowerCase().includes(searchTerms.objects?.toLowerCase() || '')),
    markers: (metadata.markers || []).filter(m => m.marker_name?.toLowerCase().includes(searchTerms.markers?.toLowerCase() || '')),
    zones: (metadata.zones || []).filter(z => z.zone_name?.toLowerCase().includes(searchTerms.zones?.toLowerCase() || '')),
    drivers: (metadata.drivers || []).filter(d => d.driver_name?.toLowerCase().includes(searchTerms.drivers?.toLowerCase() || ''))
  };
});

const dataItemsList = computed(() => {
  const items = DATA_ITEMS_MAPPING[form.type] || DATA_ITEMS_MAPPING['general'];
  return items.map(item => ({
    value: item,
    label: item.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
  }));
});


// Select All logic
const toggleSelectAll = (type) => {
  if (type === 'objects') {
    if ((form.imei?.length || 0) === (metadata.objects?.length || 0)) form.imei = [];
    else form.imei = (metadata.objects || []).map(o => o.imei);
  } else if (type === 'markers') {
    if ((form.marker_ids?.length || 0) === (metadata.markers?.length || 0)) form.marker_ids = [];
    else form.marker_ids = (metadata.markers || []).map(m => m.marker_id);
  } else if (type === 'zones') {
    if ((form.zone_ids?.length || 0) === (metadata.zones?.length || 0)) form.zone_ids = [];
    else form.zone_ids = (metadata.zones || []).map(z => z.zone_id);
  } else if (type === 'drivers') {
    if ((form.driver_ids?.length || 0) === (metadata.drivers?.length || 0)) form.driver_ids = [];
    else form.driver_ids = (metadata.drivers || []).map(d => d.driver_id);
  } else if (type === 'data_items') {
    if ((form.data_items?.length || 0) === (dataItemsList.value?.length || 0)) form.data_items = [];
    else form.data_items = dataItemsList.value.map(i => i.value);
  }
};

// Custom directive for clicking outside to close
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value();
      }
    };
    document.addEventListener("click", el.clickOutsideEvent);
  },
  unmounted(el) {
    document.removeEventListener("click", el.clickOutsideEvent);
  },
};

const toggleDropdown = (name) => {
  if (activeDropdown.value === name) activeDropdown.value = null;
  else activeDropdown.value = name;
};

const fetchMetadata = async () => {
  try {
    const response = await api.get('/api/reports/metadata');
    metadata.objects = response.data.objects;
    metadata.zones = response.data.zones;
    metadata.markers = response.data.markers;
    metadata.drivers = response.data.drivers;
  } catch (error) {
    console.error('Failed to fetch report metadata:', error);
  }
};

const fetchSensors = async () => {
  if (!form.imei.length) {
    metadata.sensors = [];
    return;
  }
  try {
    const response = await api.post('/api/reports/sensors', { imeis: form.imei });
    metadata.sensors = response.data;
  } catch (error) {
    console.error('Failed to fetch sensors:', error);
  }
};

watch(() => form.imei, () => {
  if (isFieldEnabled('sensor_names')) {
    fetchSensors();
  }
}, { deep: true });

watch(() => form.type, () => {
  handleTypeChange();
});

watch(() => form.time_period_filter, (val) => {
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  
  let from = new Date(today);
  let to = new Date(today);
  to.setHours(23, 59, 59);

  switch(val) {
    case 'last_hour':
      from = new Date(now.getTime() - (1000 * 60 * 60));
      to = new Date(now);
      break;
    case 'today':
      from = new Date(today);
      to.setHours(23, 59, 59);
      break;
    case 'yesterday':
      from = new Date(today.getTime() - (1000 * 60 * 60 * 24));
      to = new Date(from);
      to.setHours(23, 59, 59);
      break;
    case 'two_days':
      from = new Date(today.getTime() - (1000 * 60 * 60 * 24 * 2));
      to = new Date(from);
      to.setHours(23, 59, 59);
      break;
    case 'three_days':
      from = new Date(today.getTime() - (1000 * 60 * 60 * 24 * 3));
      to = new Date(from);
      to.setHours(23, 59, 59);
      break;
    case 'this_week':
      const day = today.getDay(); // 0 is Sunday
      from = new Date(today.getTime() - (1000 * 60 * 60 * 24 * day));
      to = new Date(now);
      break;
    case 'last_week':
      const lastWeekDay = today.getDay();
      const lastWeekEnd = new Date(today.getTime() - (1000 * 60 * 60 * 24 * (lastWeekDay + 1)));
      from = new Date(lastWeekEnd.getTime() - (1000 * 60 * 60 * 24 * 6));
      to = new Date(lastWeekEnd);
      to.setHours(23, 59, 59);
      break;
    case 'this_month':
      from = new Date(now.getFullYear(), now.getMonth(), 1);
      to = new Date(now);
      break;
    case 'last_month':
      from = new Date(now.getFullYear(), now.getMonth() - 1, 1);
      to = new Date(now.getFullYear(), now.getMonth(), 0);
      to.setHours(23, 59, 59);
      break;
  }

  form.date_from = from.toISOString().split('T')[0];
  form.hour_from = String(from.getHours()).padStart(2, '0');
  form.minute_from = String(from.getMinutes()).padStart(2, '0');
  
  form.date_to = to.toISOString().split('T')[0];
  form.hour_to = String(to.getHours()).padStart(2, '0');
  form.minute_to = String(to.getMinutes()).padStart(2, '0');
});

// Logic helpers for conditional fields (Sync with gsv2.main.js reportsSwitchType)
const isFieldEnabled = (fieldName) => {
  const e = form.type;
  
  switch(fieldName) {
    case 'marker_ids':
      return ["marker_in_out", "marker_in_out_gen"].includes(e);
    case 'zone_ids':
      return ["overspeednew_zone", "overspeednew2_zone", "IVMS_zone", "overspeed_zone_in", "zone_in_out", "zone_in_out_general"].includes(e);
    case 'sensor_names':
      return ["route_data_sensors", "drives_stops_sensors", "drives_stops_logic", "logic_sensors", "sensor_graph"].includes(e);
    case 'speed_limit':
      return ["general", "general_merged", "general_accuracy", "mileage_daily", "drives_stops", "travel_sheet", "driver_score_new", "travel_sheet_dn", "driver_travel_sheet", "driver_mileage", "driver_mileage_daily", "driver_overspeed", "overspeed", "underspeed", "overspeed_count", "underspeed_count", "rag", "rag_driver", "routes", "marker_in_out_gen", "zone_in_out_general", "routes_stops"].includes(e);
    case 'stop_duration':
      return ["general", "general_merged", "general_accuracy", "mileage_daily", "drives_stops", "travel_sheet", "travel_sheet_dn", "driver_travel_sheet", "driver_mileage", "driver_mileage_daily", "driver_overspeed", "drives_stops_sensors", "drives_stops_logic", "zone_in_out_general", "routes_stops"].includes(e);
    case 'show_coordinates':
    case 'show_addresses':
    case 'markers_addresses':
    case 'zones_addresses':
      // List of types where metadata selection is disabled (Migrated from legacy)
      const disabledTypes = [
        "general", "general_accuracy", "general_merged", "mileage_daily", 
        "object_info", "tasks", "expenses", "driver_travel_sheet", 
        "driver_mileage", "driver_mileage_daily", "drives_stops", 
        "travel_sheet", "driver_score_new", "travel_sheet_dn", 
        "driver_overspeed", "overspeed_count", "underspeed_count", 
        "rag", "rag_driver", "routes", "service", "speed_graph", 
        "altitude_graph", "acc_graph", "fuellevel_graph", 
        "temperature_graph", "routes_stops"
      ];
      
      if (disabledTypes.includes(e)) return false;
      // Specific fine-tuning
      if (fieldName === 'markers_addresses' && ["marker_in_out", "marker_in_out_gen"].includes(e)) return false;
      if (fieldName === 'zones_addresses' && ["overspeednew_zone", "overspeednew2_zone", "IVMS_zone", "overspeed_zone_in", "zone_in_out", "zone_in_out_general", "marker_in_out", "marker_in_out_gen"].includes(e)) return false;
      
      return true;
    case 'driver_ids':
      return ['driver_travel_sheet', 'IVMS2_new', 'driver_overspeed', 'driver_score_new', 'driver_mileage'].includes(e);
    case 'imei':
      return !['driver_travel_sheet', 'IVMS2_new', 'driver_overspeed', 'driver_score_new', 'driver_mileage'].includes(e);
    case 'other_dn':
      return e === 'travel_sheet_dn';
    case 'other_rag':
      return ['rag', 'rag_driver'].includes(e);
    default:
      return true;
  }
};

const availableFormats = computed(() => {
  const graphical = ['sensor_graph', 'speed_graph', 'fuellevel_graph', 'image_gallery'];
  if (graphical.includes(form.type)) {
    return ['HTML'];
  }
  return ['HTML', 'PDF', 'XLS'];
});

const handleTypeChange = () => {
  // Always checked as per request
  form.show_coordinates = true;

  // Reset fields that become disabled (Migrated logic from gsv2.main.js)
  if (!isFieldEnabled('marker_ids')) form.marker_ids = [];
  if (!isFieldEnabled('zone_ids')) form.zone_ids = [];
  if (!isFieldEnabled('sensor_names')) form.sensor_names = [];
  if (!isFieldEnabled('speed_limit')) form.speed_limit = '';
  if (!isFieldEnabled('stop_duration')) form.stop_duration = 5;
  if (!isFieldEnabled('show_addresses')) form.show_addresses = false;
  if (!isFieldEnabled('markers_addresses')) form.markers_addresses = false;
  if (!isFieldEnabled('zones_addresses')) form.zones_addresses = false;
  
  if (form.type === 'travel_sheet_dn') {
    form.other_dn_starts_hour = '22';
    form.other_dn_starts_minute = '00';
    form.other_dn_ends_hour = '06';
    form.other_dn_ends_minute = '00';
  } else if (['rag', 'rag_driver'].includes(form.type)) {
    form.other_rag_low_score = '0';
    form.other_rag_high_score = '5';
  }

  // If object select is hidden, automatically select all objects
  if (!isFieldEnabled('imei')) {
    form.imei = (metadata.objects || []).map(o => o.imei);
  }

  if (!isFieldEnabled('driver_ids')) form.driver_ids = [];

  // Set default format if currently selected is not available
  if (!availableFormats.value.includes(form.format.toUpperCase())) {
    form.format = 'html';
  }

  // Update data items when type changes
  form.data_items = dataItemsList.value.map(i => i.value);
};

const handleGenerate = async () => {
  if (!form.imei.length && isFieldEnabled('imei')) {
    showToast('Please select at least one object', 'error');
    return;
  }
  
  if (!form.driver_ids.length && isFieldEnabled('driver_ids')) {
    showToast('Please select at least one driver', 'error');
    return;
  }

  if (['overspeed', 'overspeed_count', 'underspeed', 'underspeed_count'].includes(form.type) && !form.speed_limit) {
    showToast('Speed limit is required for this report type', 'error');
    return;
  }

  if (['marker_in_out', 'marker_in_out_gen'].includes(form.type) && !form.marker_ids.length) {
    showToast('Please select at least one marker', 'error');
    return;
  }

  if (['zone_in_out', 'zone_in_out_general'].includes(form.type) && !form.zone_ids.length) {
    showToast('Please select at least one zone', 'error');
    return;
  }

  if (['drives_stops_sensors', 'drives_stops_logic', 'logic_sensors', 'sensor_graph'].includes(form.type) && !form.sensor_names.length) {
    showToast('Please select at least one sensor', 'error');
    return;
  }

  const payload = { ...form };
  
  // Format schedule_period
  let sp = '';
  if (form.schedule_daily) sp += 'd';
  if (form.schedule_weekly) sp += 'w';
  if (form.schedule_monthly) sp += 'm';
  payload.schedule_period = sp;

  // Format "other" field
  if (form.type === 'travel_sheet_dn') {
    payload.other = JSON.stringify({
      dn_starts_hour: form.other_dn_starts_hour,
      dn_starts_minute: form.other_dn_starts_minute,
      dn_ends_hour: form.other_dn_ends_hour,
      dn_ends_minute: form.other_dn_ends_minute
    });
  } else if (['rag', 'rag_driver'].includes(form.type)) {
    payload.other = JSON.stringify({
      low_score: form.other_rag_low_score,
      high_score: form.other_rag_high_score
    });
  } else {
    payload.other = '';
  }
  
  // Validation for date range (Migrated from legacy PHP rules)
  if (form.time_period_filter === 'custom') {
    const start = new Date(form.date_from);
    const end = new Date(form.date_to);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays > 31 && form.imei.length > 1) {
      showToast('Generating detailed reports for multiple objects is limited to 31 days. Please reduce selection.', 'error');
      return;
    }
  }

  isGenerating.value = true;
  generationProgress.value = 0;
  totalChunks.value = 1;
  currentChunk.value = 0;

  try {
    const fromStr = `${form.date_from} ${form.hour_from}:${form.minute_from}:00`;
    const toStr = `${form.date_to} ${form.hour_to}:${form.minute_to}:59`;

    const basePayload = {
      ...form,
      from: fromStr,
      to: toStr
    };
    
    let endpoint = '/api/reports/generate/';
    let allKeys = [];
    let allData = [];

    if (['general', 'general_accuracy'].includes(form.type)) {
      endpoint = form.type === 'general' ? '/api/reports/modular/general-information/generate' : '/api/reports/modular/general-accuracy/generate';
      
      // Speed optimization: Parallelize by 100 vehicles per batch
      const batchSize = 100;
      const chunks = [];
      for (let i = 0; i < form.imei.length; i += batchSize) {
        chunks.push(form.imei.slice(i, i + batchSize));
      }
      
      totalChunks.value = chunks.length;
      
      // Execute in parallel
      const promises = chunks.map(async (chunk, index) => {
        const payload = { ...basePayload, imei: chunk };
        try {
          const response = await api.post(endpoint, payload);
          currentChunk.value++;
          generationProgress.value = Math.round((currentChunk.value / totalChunks.value) * 100);
          
          if (response?.data?.status === 'success') {
             if (response.data.keys) allKeys.push(...response.data.keys);
             else if (response.data.key) allKeys.push(response.data.key);
             
             if (response.data.data) allData.push(...response.data.data);
          }
          return response;
        } catch (e) {
          console.error(`Batch ${index + 1} failed:`, e);
          throw e;
        }
      });
      
      await Promise.all(promises);
      
      if (allKeys.length === 0 && allData.length === 0) {
        throw new Error('All batches failed or returned no data');
      }

      showToast('Report generated successfully!');
      emit('generated', { 
         type: form.type, 
         data: allData,
         keys: allKeys
      });
      emit('close');

    } else {
      // Standard single-request generation for other types
      const response = await api.post(endpoint, basePayload);
      if (response?.data?.status === 'success') {
        showToast('Report generated successfully!');
        emit('generated', { 
           type: form.type, 
           data: response.data.data || [],
           key: response.data.key || '',
           keys: response.data.keys || null
        });
        emit('close');
      } else {
         showToast(response?.data?.message || 'Failed to generate report', 'error');
      }
    }
  } catch (error) {
    console.error('Generation failed:', error);
    showToast(error.response?.data?.message || error.message || 'Failed to generate report', 'error');
  } finally {
    isGenerating.value = false;
    generationProgress.value = 0;
  }
};

const handleSave = async () => {
  if (!form.name) {
    showToast('Please enter a report name', 'error');
    return;
  }

  // Email required if any schedule is selected
  if ((form.schedule_daily || form.schedule_weekly || form.schedule_monthly) && !form.schedule_email_address) {
    showToast('Email address is required for scheduled reports', 'error');
    return;
  }
  
  // Re-run the same payload logic as generate
  let sp = '';
  if (form.schedule_daily) sp += 'd';
  if (form.schedule_weekly) sp += 'w';
  if (form.schedule_monthly) sp += 'm';
  
  const payload = { ...form, schedule_period: sp };
  
  if (form.type === 'travel_sheet_dn') {
    payload.other = JSON.stringify({
      dn_starts_hour: form.other_dn_starts_hour,
      dn_starts_minute: form.other_dn_starts_minute,
      dn_ends_hour: form.other_dn_ends_hour,
      dn_ends_minute: form.other_dn_ends_minute
    });
  } else if (['rag', 'rag_driver'].includes(form.type)) {
    payload.other = JSON.stringify({
      low_score: form.other_rag_low_score,
      high_score: form.other_rag_high_score
    });
  } else {
    payload.other = '';
  }

  isSaving.value = true;
  try {
    const response = await api.post('/api/reports', payload);
    if (response.status === 200 || response.status === 201) {
      emit('saved', 'Report template saved successfully');
      emit('close');
    }
  } catch (error) {
    console.error('Save failed:', error.response?.data || error.message);
    showToast(error.response?.data?.message || 'Failed to save report template', 'error');
  } finally {
    isSaving.value = false;
  }
};
</script>

<style scoped>
.inner-modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.4);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
}

.inner-modal-container {
  width: 900px;
  max-width: 95%;
  background: #111827;
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.8);
}

.inner-header {
  padding: 16px 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.inner-title {
  font-size: 15px;
  font-weight: 700;
  color: #3b82f6;
  text-transform: capitalize;
}

.inner-close {
  background: transparent;
  border: none;
  color: #4b5563;
  cursor: pointer;
  padding: 4px;
}

.inner-close:hover { color: #fff; }

.inner-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

/* Section Header */
.section-badge-title {
  color: #3b82f6;
  font-size: 14px;
  font-weight: 700;
  margin-bottom: 20px;
  padding-bottom: 8px;
  border-bottom: 1px solid rgba(59, 130, 246, 0.2);
}

/* Grid Layouts */
.report-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  margin-bottom: 32px;
}

.footer-sections-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
}

.form-field-row {
  display: grid;
  grid-template-columns: 120px 1fr;
  align-items: center;
  gap: 16px;
  margin-bottom: 12px;
  transition: opacity 0.3s;
}

.form-field-row.disabled {
  opacity: 0.3;
  pointer-events: none;
}

.form-field-row label {
  font-size: 13px;
  font-weight: 500;
  color: #94a3b8;
}

/* Premium Inputs */
.premium-field {
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  padding: 10px 12px;
  color: #fff;
  font-size: 14px;
  transition: all 0.2s;
  outline: none;
  width: 100%;
}

.premium-field:focus {
  border-color: #3b82f6;
  background: rgba(59, 130, 246, 0.05);
}

.multi-select-box {
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  color: #64748b;
  min-height: 40px;
}

.input-with-unit {
  position: relative;
  display: flex;
  align-items: center;
}

.input-with-unit .unit {
  position: absolute;
  right: 12px;
  font-size: 11px;
  color: #64748b;
}

/* Checkboxes */
.checkbox-stack {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.checkbox-stack.horizontal {
  flex-direction: row;
  gap: 24px;
}

.premium-cb {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: #cbd5e1;
  cursor: pointer;
  user-select: none;
  transition: opacity 0.3s;
}

.premium-cb.disabled {
  opacity: 0.3;
  pointer-events: none;
}

.premium-cb input {
  accent-color: #3b82f6;
  width: 16px;
  height: 16px;
}

/* Datetime Picker */
.datetime-picker {
  display: flex;
  gap: 8px;
  align-items: center;
}

.time-selects {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
}

.date-input {
  flex: 1;
}

.time-unit {
  width: 60px !important;
  text-align: center;
  padding: 10px 4px !important;
}

/* Footer Actions */
.inner-footer {
  padding: 20px 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  justify-content: center;
  gap: 16px;
}

.action-btn {
  padding: 10px 24px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  flex: 1;
  max-width: 180px;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.generate-btn {
  background: #3b82f6;
  color: #fff;
}

.save-btn {
  background: transparent;
  border: 1px solid #3b82f6;
  color: #3b82f6;
}

.cancel-btn {
  background: transparent;
  color: #64748b;
}

.generate-btn:hover { background: #2563eb; transform: translateY(-1px); }
.save-btn:hover:not(:disabled) { background: rgba(59, 130, 246, 0.1); }
.cancel-btn:hover { color: #fff; }

.mt-16 { margin-top: 16px; }
.mt-32 { margin-top: 32px; }

.progress-info {
  margin-top: 12px;
  text-align: center;
}

.progress-text {
  font-size: 13px;
  color: #3b82f6;
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* Scrollbar */
/* Multi-select Styles */
.multi-select-wrapper {
  position: relative;
  width: 100%;
  z-index: 10;
}

.multi-select-wrapper.on-top {
  z-index: 1001;
}

.dropdown-group-title {
  padding: 8px 12px;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #3b82f6;
  font-weight: 700;
  opacity: 0.7;
}

.dropdown-item-single {
  padding: 10px 12px;
  color: #cbd5e1;
  font-size: 13px;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s;
}

.dropdown-item-single:hover {
  background: rgba(59, 130, 246, 0.1);
  color: #fff;
}

.dropdown-item-single.active {
  background: #3b82f6;
  color: #fff;
}

.custom-multi-select {
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  min-height: 42px;
  padding: 0 14px;
}

.truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 85%;
}

.dropdown-panel {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: 100%;
  background: #1e293b;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  backdrop-filter: blur(12px);
  z-index: 1000;
  box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.7);
  overflow: hidden;
  animation: slideDropdown 0.2s ease-out;
}

@keyframes slideDropdown {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.panel-header {
  padding: 10px 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: rgba(15, 23, 42, 0.8);
}

.panel-header.no-search {
  padding: 8px 12px;
  flex-direction: row;
  justify-content: flex-start;
}

.dropdown-search {
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 8px 12px;
  color: #fff;
  font-size: 13px;
  outline: none;
}

.select-all-btn {
  background: rgba(59, 130, 246, 0.1);
  color: #60a5fa;
  border: 1px solid rgba(59, 130, 246, 0.2);
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  align-self: flex-start;
  transition: all 0.2s;
}

.select-all-btn:hover {
  background: #3b82f6;
  color: #fff;
}

.panel-list {
  max-height: 240px;
  overflow-y: auto;
  padding: 6px;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  color: #cbd5e1;
  font-size: 13px;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.15s;
}

.dropdown-item:hover {
  background: rgba(255, 255, 255, 0.03);
  color: #fff;
}

.dropdown-item input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: #3b82f6;
  cursor: pointer;
}

.item-label {
  flex: 1;
}

.no-data-hint {
  padding: 24px;
  text-align: center;
  color: #64748b;
  font-size: 13px;
  font-style: italic;
}

/* Base Select Style Improvement */
select.premium-field {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  background-size: 14px;
  padding-right: 40px;
}

.glass-panel {
  background: rgba(30, 41, 59, 0.85);
  backdrop-filter: blur(16px) saturate(180%);
}

.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }

@media (max-width: 992px) {
  .inner-modal-container {
    width: 95%;
  }
}

@media (max-width: 768px) {
  .inner-modal-container {
    height: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .report-grid, .footer-sections-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }

  .form-field-row {
    grid-template-columns: 1fr;
    gap: 8px;
  }

  .datetime-picker {
    flex-direction: column;
    align-items: stretch;
  }

  .time-selects {
    width: 100%;
  }

  .time-unit {
    flex: 1;
  }

  .checkbox-stack.horizontal {
    flex-direction: column;
    gap: 12px;
  }

  .inner-footer {
    flex-direction: column;
    padding: 16px;
  }

  .action-btn {
    max-width: none;
    width: 100%;
  }

  .inner-body {
    padding: 16px;
  }
}

/* HIGH-END CATCHY LOADER STYLES */
.generation-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(15, 23, 42, 0.95);
  backdrop-filter: blur(20px);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 20px;
}

.loader-container {
  text-align: center;
  max-width: 450px;
  width: 90%;
}

.radar-scanner {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  border: 2px solid rgba(59, 130, 246, 0.2);
  margin: 0 auto 30px;
  position: relative;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 70%);
}

.scanner-circle {
  position: absolute;
  inset: 10px;
  border: 1px solid rgba(59, 130, 246, 0.1);
  border-radius: 50%;
}

.scanner-hand {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 50%;
  height: 2px;
  background: linear-gradient(to right, transparent, #3b82f6);
  transform-origin: left center;
  animation: scan 2s linear infinite;
  box-shadow: 0 0 20px #3b82f6;
}

.scanner-dots .dot {
  position: absolute;
  width: 6px;
  height: 6px;
  background: #3b82f6;
  border-radius: 50%;
  filter: blur(1px);
  box-shadow: 0 0 10px #3b82f6;
  opacity: 0;
}

.d1 { top: 30%; left: 40%; animation: blink 1.5s infinite; }
.d2 { top: 60%; left: 70%; animation: blink 1.5s infinite 0.5s; }
.d3 { top: 20%; left: 80%; animation: blink 1.5s infinite 1.2s; }

@keyframes scan {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes blink {
  0%, 100% { opacity: 0; transform: scale(0.5); }
  50% { opacity: 1; transform: scale(1.2); }
}

.loading-content {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.loading-title {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.loading-title .en { color: #fff; font-size: 20px; font-weight: 800; letter-spacing: -0.5px; }
.loading-title .ar { color: #3b82f6; font-size: 18px; font-weight: 700; }

.loading-subtitle {
  display: flex;
  flex-direction: column;
  font-size: 13px;
  color: #94a3b8;
}

.progress-bar-container {
  width: 100%;
  height: 4px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  overflow: hidden;
  margin: 10px 0;
}

.premium-progress-bar {
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6, #3b82f6);
  background-size: 200% 100%;
  animation: progressMove 2s infinite linear;
}

@keyframes progressMove {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.loading-steps {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: center;
}

.step {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  color: #475569;
  letter-spacing: 1px;
}

.pulse { animation: stepPulse 2s infinite ease-in-out; }
.delay-1 { animation-delay: 0.5s; }
.delay-2 { animation-delay: 1s; }

@keyframes stepPulse {
  0%, 100% { color: #475569; transform: scale(1); }
  50% { color: #3b82f6; transform: scale(1.05); }
}

.fade-loading-enter-active, .fade-loading-leave-active { transition: opacity 0.5s ease; }
.fade-loading-enter-from, .fade-loading-leave-to { opacity: 0; }

.disabled-footer { opacity: 0.5; pointer-events: none; }
</style>
