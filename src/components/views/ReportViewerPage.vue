<template>
  <div class="page-backdrop" :dir="currentLang === 'ar' ? 'rtl' : 'ltr'">
    <div class="page-container ripple-bg">
      <!-- Header -->
      <div class="modal-header">
        <div class="header-content">
          <svg class="header-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
          </svg>
          <h2 class="modal-title">{{ t('report_results') }}: {{ reportTypeDisplayName }}</h2>
        </div>
        <div class="header-actions">
           <!-- Language Toggle -->
           <button class="lang-btn" @click="toggleLang">
             {{ currentLang === 'en' ? 'العربية' : 'English' }}
           </button>

           <div class="export-group">
             <button class="action-btn excel" @click="exportExcel">
               <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                 <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                 <path d="M8 13h8M8 17h8M10 9h6"></path>
               </svg>
               Excel
             </button>
             <button class="action-btn pdf" @click="exportPDF">
               <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                 <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                 <path d="M14 2v6h6"></path>
                 <path d="M16 13H8M16 17H8M10 9H8"></path>
               </svg>
               PDF
             </button>
             <button class="action-btn html" @click="exportHTML">
               <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                 <polyline points="16 18 22 12 16 6"></polyline>
                 <polyline points="8 6 2 12 8 18"></polyline>
               </svg>
               HTML
             </button>
           </div>
           
           <button class="close-btn" @click="closePage">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
      </div>

      <!-- Body -->
        <div class="modal-body custom-scrollbar">
          <!-- Highly Specialized/Dedicated View for Drives Stops Sensors -->
          <template v-if="reportType === 'drives_stops_sensors' && reportDataFromState">
            <DrivesStopsSensorsView :reportData="reportDataFromState" :t="t" />
          </template>

          <!-- Original Generic/Modular View for all other reports -->
          <template v-else>
            <div class="report-header-title">
              <h3>{{ reportTypeDisplayName }}</h3>
            </div>

          <!-- Professional Data Table -->
          <div class="table-container shadow-sm">
            <table class="report-table" id="report-table-element">
              <thead>
                <tr v-if="reportType.includes('general')">
                  <th class="sticky-col">{{ t('object') }}</th>
                  <th>{{ t('group') }}</th>
                  <th>{{ t('route_start') }}</th>
                  <th>{{ t('route_end') }}</th>
                  <th>{{ t('route_length') }}</th>
                  <th>{{ t('move_duration') }}</th>
                  <th>{{ t('stop_duration') }}</th>
                  <th>{{ t('stop_count') }}</th>
                  <th>{{ t('top_speed') }}</th>
                  <th>{{ ['general_accuracy', 'general_merged'].includes(reportType) ? t('speed_limit') : t('avg_speed') }}</th>
                  <th>{{ t('overspeed_count') }}</th>
                  <th>{{ t('fuel_consumption') }}</th>
                  <th>{{ t('avg_fuel') }}</th>
                  <th>{{ t('fuel_cost') }}</th>
                  <th>{{ t('engine_work') }}</th>
                  <th>{{ t('engine_idle') }}</th>
                  <th>{{ t('odometer') }}</th>
                  <th>{{ t('engine_hours') }}</th>
                  <th>{{ t('driver') }}</th>
                  <th>{{ t('trailer') }}</th>
                </tr>
                <tr v-else-if="reportType === 'drives_stops'">
                   <th class="sticky-col">#</th>
                   <th>{{ t('status') }}</th>
                   <th>{{ t('start') }}</th>
                   <th>{{ t('end') }}</th>
                   <th>{{ t('duration') }}</th>
                   <th>{{ t('distance_travelled_km') }}</th>
                   <th>{{ t('top_speed') }}</th>
                   <th>{{ t('avg_speed') }}</th>
                   <th>{{ t('fuel_consumption') }}</th>
                   <th>{{ t('avg_fuel_cons_mpg') }}</th>
                   <th>{{ t('fuel_cost') }}</th>
                   <th>{{ t('engine_idle') }}</th>
                   <th>{{ t('driver') }}</th>
                   <th>{{ t('trailer') }}</th>
                </tr>
                <tr v-else-if="reportType === 'driver_travel_sheet'">
                  <th class="sticky-col">#</th>
                  <th>{{ t('driver') }}</th>
                  <th>{{ t('group') }}</th>
                  <th>{{ t('object') }}</th>
                  <th>{{ t('start_time') }}</th>
                  <th>{{ t('departed_from') }}</th>
                  <th>{{ t('start_odometer') }}</th>
                  <th>{{ t('end_time') }}</th>
                  <th>{{ t('arrived_at') }}</th>
                  <th>{{ t('end_odometer') }}</th>
                  <th>{{ t('max_speed') }}</th>
                  <th>{{ t('duration') }}</th>
                  <th>{{ t('stop_duration') }}</th>
                  <th>{{ t('distance_travelled_km') }}</th>
                  <th>{{ t('fuel_consumption') }}</th>
                  <th>{{ t('avg_fuel_cons_100_km') }}</th>
                  <th>{{ t('fuel_cost') }}</th>
                  <th>{{ t('trailer') }}</th>
                </tr>
                <tr v-else-if="['travel_sheet', 'travel_sheet_dn'].includes(reportType)">
                  <th class="sticky-col">#</th>
                  <th>{{ t('object') }}</th>
                  <th>{{ t('group') }}</th>
                  <th>{{ t('time_a') }}</th>
                  <th>{{ t('position_a') }}</th>
                  <th>{{ t('odometer_a') }}</th>
                  <th>{{ t('time_b') }}</th>
                  <th>{{ t('position_b') }}</th>
                  <th>{{ t('odometer_b') }}</th>
                  <th>{{ t('duration') }}</th>
                  <th>{{ t('stop_duration') }}</th>
                  <th>{{ t('route_length') }}</th>
                  <th>{{ t('fuel_consumption') }}</th>
                  <th>{{ t('avg_fuel') }}</th>
                  <th>{{ t('fuel_cost') }}</th>
                  <th>{{ t('driver') }}</th>
                  <th>{{ t('trailer') }}</th>
                </tr>
                 <!-- Special Header for KOC Directorate -->
                  <tr v-else-if="reportType === 'overspeednew'">
                    <th class="sticky-col">#</th>
                    <th>{{ t('group') }}</th>
                    <th>{{ t('start') }}</th>
                    <th>{{ t('imei') }}</th>
                    <th style="min-width: 300px">{{ t('location') }}</th>
                    <th>{{ t('heading') }}</th>
                    <th>{{ t('recorded_speed') }}</th>
                    <th>{{ t('speed_limit') }}</th>
                    <th>{{ t('start_time') }}</th>
                    <th>{{ t('end_time') }}</th>
                    <th>{{ t('speed') }}</th>
                    <th>{{ t('os_duration') }}</th>
                    <th>{{ t('os_distance') }}</th>
                    <th>{{ t('seat_belt') }}</th>
                    <th>{{ t('tamper') }}</th>
                    <th>{{ t('violations') }}</th>
                    <th>{{ t('remarks') }}</th>
                 </tr>

                 <!-- Special Header for KOC Directorate Summary -->
                 <tr v-else-if="reportType === 'overspeednew2'">
                    <th class="sticky-col">#</th>
                    <th>{{ t('plate_number') }}</th>
                    <th style="min-width: 250px">{{ t('location') }}</th>
                    <th>{{ t('vehicle_heading') }}</th>
                    <th>{{ t('recorded_speed') }}</th>
                    <th>{{ t('allowed_speed_limit') }}</th>
                    <th>{{ t('start_time') }}</th>
                    <th>{{ t('end_time') }}</th>
                    <th>{{ t('os_duration') }}</th>
                    <th>{{ t('os_distance') }}</th>
                    <th>{{ t('seat_belt') }}</th>
                    <th>{{ t('tamper') }}</th>
                    <th>{{ t('harsh_accel') }}</th>
                    <th>{{ t('harsh_brak') }}</th>
                    <th>{{ t('harsh_corn') }}</th>
                    <th>{{ t('os_count') }}</th>
                    <th>{{ t('total_violation') }}</th>
                 </tr>

                 <!-- Dynamic Header for Object Information -->
                 <tr v-else-if="reportType === 'object_info'">
                    <th class="sticky-col">#</th>
                    <th v-for="(header, hIdx) in (reportDataFromState?.headers || [])" :key="hIdx">
                      {{ header }}
                    </th>
                  </tr>

                 <!-- Special Header for Dynamic Route Sensors -->
                 <tr v-else-if="reportType === 'route_data_sensors'" align="center" style="background-color:#0b1eaa;">
                   <th v-for="(lab, index) in sensorLabels" :key="index">
                      {{ lab }}
                   </th>
                 </tr>
              </thead>
              <tbody>
                <!-- Route Data with Dynamic Sensors -->
                <template v-if="reportType === 'route_data_sensors'">
                  <tr v-for="(row, idx) in tableRows" :key="idx" class="table-row">
                     <td v-for="(val, vIdx) in row.values" :key="vIdx" v-html="val">
                     </td>
                  </tr>
                </template>

                <template v-if="reportType.includes('general')">
                  <tr v-for="(row, idx) in tableRows" :key="idx" class="table-row">
                    <td class="sticky-col font-bold">{{ row.object || row.imei }}</td>
                    <td>{{ row.group || '-' }}</td>
                    <td>{{ row.route_start }}</td>
                    <td>{{ row.route_end }}</td>
                    <td class="text-right whitespace-nowrap">{{ row.route_length }} km</td>
                    <td class="text-right">{{ formatDuration(row.route_duration) }}</td>
                    <td class="text-right">{{ formatDuration(row.stop_duration) }}</td>
                    <td class="text-center">{{ row.stop_count }}</td>
                    <td class="text-right">{{ formatSpeed(row.top_speed) }} km/h</td>
                    <td class="text-right">{{ ['general_accuracy', 'general_merged'].includes(reportType) ? formatSpeed(row.average_speed || row.avg_speed || '80') : formatSpeed(row.avg_speed || '0') }}</td>
                    <td class="text-center" :class="{ 'danger-text': row.overspeed_count > 0 }">{{ row.overspeed_count }}</td>
                    <td class="text-right">{{ row.fuel_consumption }} L</td>
                    <td class="text-right">{{ row.average_fuel || '0' }} L/100km</td>
                    <td class="text-right">{{ row.fuel_cost || '0' }}</td>
                    <td class="text-right">{{ formatDuration(row.engine_work) }}</td>
                    <td class="text-right">{{ formatDuration(row.engine_idle) }}</td>
                    <td class="text-right">{{ row.odometer || '0' }} km</td>
                    <td class="text-right">{{ formatDuration(row.engine_hours) }}</td>
                    <td>{{ row.driver || 'n/a' }}</td>
                    <td>{{ row.trailer || 'n/a' }}</td>
                  </tr>
                </template>
                
                <template v-else-if="['travel_sheet', 'travel_sheet_dn'].includes(reportType)">
                  <template v-for="(group, gIdx) in tableRowsGrouped" :key="gIdx">
                    <tr class="imei-header-row">
                      <td colspan="17" class="text-center font-bold bg-light-blue py-2">
                        {{ group.object || group.imei }} ({{ group.imei }})
                      </td>
                    </tr>
                    <tr v-for="(row, idx) in group.rows" :key="idx" class="table-row" :class="{ 'global-total-row': row.is_total }">
                      <td class="sticky-col font-bold">{{ row.is_total ? '' : idx + 1 }}</td>
                      <td>{{ row.object || row.imei }}</td>
                      <td>{{ row.group || 'No Group' }}</td>
                      <td>{{ row.start_time }}</td>
                      <td :class="{ 'font-bold': row.is_total }">{{ row.departed_from }}</td>
                      <td>{{ row.start_odometer }}</td>
                      <td>{{ row.end_time }}</td>
                      <td>{{ row.arrived_at }}</td>
                      <td>{{ row.end_odometer }}</td>
                      <td class="text-right">{{ formatDuration(row.duration) }}</td>
                      <td class="text-right">{{ formatDuration(row.stop_duration) }}</td>
                      <td class="text-right">{{ row.distance_travelled }} km</td>
                      <td class="text-right">{{ row.fuel_consumption }} L</td>
                      <td class="text-right">{{ row.avg_fuel_consumption }} L/100km</td>
                      <td class="text-right">{{ row.fuel_cost }}</td>
                      <td>{{ row.driver || 'n/a' }}</td>
                      <td>{{ row.trailer || 'n/a' }}</td>
                    </tr>
                  </template>
                </template>
                <template v-else-if="reportType === 'driver_travel_sheet'">
                  <tr v-for="(row, idx) in tableRows" :key="idx" class="table-row">
                    <td class="sticky-col font-bold">{{ idx + 1 }}</td>
                    <td>{{ row.driver }}</td>
                    <td>{{ row.group || 'No Group' }}</td>
                    <td>{{ row.object || row.imei }}</td>
                    <td>{{ row.start_time }}</td>
                    <td>{{ row.departed_from }}</td>
                    <td>{{ row.start_odometer }}</td>
                    <td>{{ row.end_time }}</td>
                    <td>{{ row.arrived_at }}</td>
                    <td>{{ row.end_odometer }}</td>
                    <td>{{ formatSpeed(row.max_speed || row.top_speed || '0') }}</td>
                    <td class="text-right">{{ formatDuration(row.duration) }}</td>
                    <td class="text-right">{{ formatDuration(row.stop_duration) }}</td>
                    <td class="text-right">{{ row.distance_travelled }}</td>
                    <td class="text-right">{{ row.fuel_consumption }}</td>
                    <td class="text-right">{{ row.avg_fuel_consumption }}</td>
                    <td class="text-right">{{ row.fuel_cost }}</td>
                    <td>{{ row.trailer || 'n/a' }}</td>
                  </tr>
                </template>

                <template v-else-if="reportType === 'overspeednew2'">
                  <tr v-for="(row, idx) in tableRows" :key="idx" class="table-row">
                    <td class="sticky-col font-bold">{{ idx + 1 }}</td>
                    <td>{{ row.plate_number }}</td>
                    <td class="text-left" style="font-size: 11px">{{ row.location }}</td>
                    <td>{{ row.heading }}</td>
                    <td>{{ row.recorded_speed }} km/h</td>
                    <td>{{ row.speed_limit }} km/h</td>
                    <td>{{ row.start_time }}</td>
                    <td>{{ row.end_time }}</td>
                    <td>{{ row.os_duration }}</td>
                    <td>{{ row.os_distance }}</td>
                    <td>{{ row.seat_belt }}</td>
                    <td>{{ row.tamper }}</td>
                    <td>{{ row.harsh_accel }}</td>
                    <td>{{ row.harsh_brak }}</td>
                    <td>{{ row.harsh_corn }}</td>
                    <td>{{ row.os_count }}</td>
                    <td>{{ row.total_violation }}</td>
                  </tr>
                </template>

                <template v-else-if="reportType === 'object_info'">
                  <tr v-for="(row, idx) in (reportDataFromState?.rows || [])" :key="idx" class="table-row">
                    <td class="sticky-col font-bold">{{ idx + 1 }}</td>
                    <td v-for="(val, vIdx) in row" :key="vIdx" v-html="val">
                    </td>
                  </tr>
                </template>

                <template v-else-if="reportType === 'overspeednew'">
                  <template v-for="(group, gIdx) in tableRowsGrouped" :key="gIdx">
                    <tr class="imei-header-row">
                      <td colspan="17" class="text-center font-bold bg-light-blue py-2">
                        {{ group.rows[0]?.imei || group.imei }}
                      </td>
                    </tr>
                    <tr v-for="(row, idx) in group.rows" :key="idx" class="table-row">
                      <td class="sticky-col font-bold">{{ idx + 1 }}</td>
                      <td>{{ row.group }}</td>
                      <td>{{ row.start }}</td>
                      <td>{{ row.imei }}</td>
                      <td class="text-left" style="font-size: 11px">{{ row.location }}</td>
                      <td>{{ row.heading }}</td>
                      <td>{{ row.recorded_speed }} km/h</td>
                      <td>{{ row.speed_limit }} km/h</td>
                      <td>{{ row.start_time }}</td>
                      <td>{{ row.end_time }}</td>
                      <td>{{ row.speed }} km/h</td>
                      <td>{{ row.os_duration }} s</td>
                      <td>{{ row.os_distance }} km</td>
                      <td>{{ row.seat_belt }}</td>
                      <td>{{ row.tamper }}</td>
                      <td>{{ row.violations }}</td>
                      <td>{{ row.remarks }}</td>
                    </tr>
                  </template>
                </template>
                <template v-else-if="reportType === 'drives_stops'">
                  <template v-for="(group, gIdx) in tableRowsGrouped" :key="gIdx">
                    <tr class="imei-header-row">
                      <td colspan="14" class="text-center font-bold bg-light-blue py-2">
                        {{ group.object || group.imei }} ({{ group.imei }})
                      </td>
                    </tr>
                    <tr v-for="(row, idx) in group.rows" :key="idx" class="table-row" :class="{ 'global-total-row': row.is_total }">
                      <td class="sticky-col font-bold">{{ row.is_total ? '' : idx + 1 }}</td>
                      <td>
                        <div v-if="row.is_total" class="font-bold">TOTAL</div>
                        <div v-else :class="['status-chip', (row.status === 'drive' || row.status === 'Moving' || row.status === 'تحرك') ? 'drive' : 'stop']">
                          {{ row.status }}
                        </div>
                      </td>
                      <td>{{ row.start }}</td>
                      <td>{{ row.end }}</td>
                      <td class="text-right">{{ formatDuration(row.duration) }}</td>
                      
                      <!-- Distance / Stop Position -->
                      <template v-if="row.status === 'Stopped' || row.status === 'متوقف'">
                        <td class="text-left font-medium" style="min-width: 300px; white-space: normal; color: #595bd4;">
                          {{ row.stop_position }}
                        </td>
                        <td class="text-right">-</td>
                        <td class="text-right">-</td>
                      </template>
                      <template v-else>
                        <td class="text-right">{{ (row.distance && row.distance != '-') ? row.distance + ' km' : '-' }}</td>
                        <td class="text-right">{{ (row.top_speed && row.top_speed != '-') ? formatSpeed(row.top_speed) + ' km/h' : '-' }}</td>
                        <td class="text-right">{{ (row.avg_speed && row.avg_speed != '-') ? formatSpeed(row.avg_speed) + ' km/h' : '-' }}</td>
                      </template>

                      <td class="text-right">{{ (row.fuel_consumption && row.fuel_consumption != '0') ? row.fuel_consumption + ' L' : '-' }}</td>
                      <td class="text-right">{{ (row.avg_fuel_consumption && row.avg_fuel_consumption != '-') ? row.avg_fuel_consumption : '-' }}</td>
                      <td class="text-right">{{ row.fuel_cost || '0' }}</td>
                      <td class="text-right">{{ formatDuration(row.engine_idle) }}</td>
                      <td>{{ row.driver || 'n/a' }}</td>
                      <td>{{ row.trailer || 'n/a' }}</td>
                    </tr>
                  </template>
                </template>
              </tbody>

              <!-- Dedicated Total Footer Section -->
              <tfoot v-if="globalTotalRow">
                  <!-- General Reports Total -->
                  <tr v-if="reportType.includes('general')" class="global-total-row">
                    <td class="sticky-col font-bold">Total:</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td class="text-right">{{ cleanValue(globalTotalRow.route_length) }} km</td>
                    <td class="text-right">{{ formatDuration(globalTotalRow.route_duration) }}</td>
                    <td class="text-right">{{ formatDuration(globalTotalRow.stop_duration) }}</td>
                    <td class="text-center">{{ globalTotalRow.stop_count }}</td>
                    <td class="text-right">{{ formatSpeed(globalTotalRow.top_speed) }} km/h</td>
                    <td class="text-right">{{ ['general_accuracy', 'general_merged'].includes(reportType) ? (globalTotalRow.speed_limit || '-') : '-' }}</td>
                    <td class="text-center">{{ globalTotalRow.overspeed_count }}</td>
                    <td class="text-right">{{ cleanValue(globalTotalRow.fuel_consumption) }} L</td>
                    <td class="text-right">{{ cleanValue(globalTotalRow.average_fuel) }} L/100km</td>
                    <td class="text-right">{{ cleanValue(globalTotalRow.fuel_cost) }}</td>
                    <td class="text-right">{{ formatDuration(globalTotalRow.engine_work) }}</td>
                    <td class="text-right">{{ formatDuration(globalTotalRow.engine_idle) }}</td>
                    <td class="text-right">{{ cleanValue(globalTotalRow.odometer) }} km</td>
                    <td class="text-right">{{ formatDuration(globalTotalRow.engine_hours) }}</td>
                    <td>-</td>
                    <td>-</td>
                  </tr>

                  <!-- Drives and Stops Total -->
                  <tr v-else-if="reportType === 'drives_stops'" class="global-total-row">
                    <td class="sticky-col font-bold">Total:</td>
                    <td>-</td>
                    <td>-</td>
                    <td class="text-right">{{ formatDuration(globalTotalRow.duration) }}</td>
                    <td class="text-right">
                      <div class="text-xs opacity-75">Move: {{ formatDuration(globalTotalRow.move_duration) }}</div>
                      <div class="text-xs opacity-75">Stop: {{ formatDuration(globalTotalRow.stop_duration) }}</div>
                      {{ globalTotalRow.length }} km
                    </td>
                    <td class="text-right">{{ formatSpeed(globalTotalRow.top_speed) }} km/h</td>
                    <td class="text-right">{{ formatSpeed(globalTotalRow.avg_speed) }} km/h</td>
                    <td class="text-right">{{ cleanValue(globalTotalRow.fuel_consumption) }} L</td>
                    <td class="text-right">-</td>
                    <td>-</td>
                  </tr>
              </tfoot>
            </table>
          </div>
            
            <div v-if="generalPaginated.loading" class="empty-state">
              <div class="loading-spinner"></div>
              <p>Loading report data...</p>
            </div>
            <div v-else-if="safeReportData.length === 0" class="empty-state">
               <div class="empty-icon">
                 <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                   <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                 </svg>
               </div>
               <p>{{ t('no_data_found') }}</p>
            </div>

            <!-- Pagination for general report (key-based, 1000 per page) -->
            <div v-if="isGeneralKeyBased && (generalPaginated.last_page > 1 || generalPaginated.loading)" class="report-pagination">
              <div class="pagination-left">
                <span class="pagination-info" v-if="!generalPaginated.loading">
                  Showing <b>{{ (generalPaginated.current_page - 1) * generalPaginated.per_page + 1 }}</b> to 
                  <b>{{ Math.min(generalPaginated.current_page * generalPaginated.per_page, generalPaginated.total) }}</b> of 
                  <b>{{ generalPaginated.total }}</b> records
                </span>
                <span v-else class="pagination-info">Loading...</span>
              </div>
              
              <div class="pagination-right">
                <div class="rows-selector" v-if="!generalPaginated.loading">
                  <span>Rows per page:</span>
                  <select v-model="generalPaginated.per_page" @change="loadGeneralInfoPage(1)">
                    <option :value="1000">1000</option>
                    <option :value="2500">2500</option>
                    <option :value="5000">5000</option>
                    <option :value="10000">10000</option>
                  </select>
                </div>
                
                <div class="pagination-btns">
                  <button class="pagination-btn" :disabled="generalPaginated.current_page <= 1 || generalPaginated.loading" @click="loadGeneralInfoPage(generalPaginated.current_page - 1)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    Previous
                  </button>
                  <div class="page-indicator">Page {{ generalPaginated.current_page }} of {{ generalPaginated.last_page }}</div>
                  <button class="pagination-btn" :disabled="generalPaginated.current_page >= generalPaginated.last_page || generalPaginated.loading" @click="loadGeneralInfoPage(generalPaginated.current_page + 1)">
                    Next
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                  </button>
                </div>
              </div>
            </div>
          </template>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import DrivesStopsSensorsView from './reports/DrivesStopsSensorsView.vue';
import * as XLSX from 'xlsx';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import api from '../../services/api';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const reportType = computed(() => route.query.type || 'general');
console.log('ReportViewer initialized with type:', reportType.value, 'VERSION: 1.0.5');
const reportKey = computed(() => route.query.key || '');
const reportId = computed(() => route.query.id || null);
const hashId = computed(() => route.query.hash_id || null);
const reportDataFromState = computed(() => history.state.reportData || null);

const closePage = () => { router.back(); };

// When type is general and we have a key but no data, data is loaded via paginated API
const isGeneralKeyBased = computed(() => {
  if (['object_info', 'drives_stops_sensors'].includes(reportType.value)) return false; // Handled directly
  const isTypeMatch = ['general', 'general_information', 'general_accuracy', 'general_merged', 'travel_sheet', 'travel_sheet_dn', 'driver_travel_sheet', 'route_data_sensors', 'drives_stops', 'overspeednew', 'overspeednew2'].includes(reportType.value);
  const hasIdentifier = (String(reportKey.value || '').trim().length > 0 || reportId.value || hashId.value);
  const isDataEmpty = (!reportDataFromState.value || (Array.isArray(reportDataFromState.value) && reportDataFromState.value.length === 0));

  return isTypeMatch && hasIdentifier && isDataEmpty;
});

const sensorLabels = ref([]);

const generalPaginated = ref({
  data: [],
  totals_row: null,
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 10000,
  loading: false
});

// Avoid reading .length on undefined when parent passes :report-data="viewerData.data" and it's undefined
const safeReportData = computed(() => {
  if (isGeneralKeyBased.value && generalPaginated.value.data.length) {
    return generalPaginated.value.data;
  }
  if (['object_info', 'drives_stops_sensors'].includes(reportType.value)) {
    return reportDataFromState.value?.rows || [];
  }
  return Array.isArray(reportDataFromState.value) ? reportDataFromState.value : [];
});

// Translation System
const currentLang = ref('en');
const translations = {
  en: {
    report_results: 'Report Results',
    object: 'OBJECT',
    group: 'GROUP',
    route_start: 'ROUTE START',
    route_end: 'ROUTE END',
    route_length: 'ROUTE LENGTH',
    move_duration: 'MOVE DURATION',
    stop_duration: 'STOP DURATION',
    stop_count: 'STOP COUNT',
    top_speed: 'TOP SPEED',
    speed_limit: 'SPEED LIMIT',
    overspeed_count: 'OVERSPEED COUNT',
    fuel_consumption: 'FUEL CONSUMPTION',
    avg_fuel: 'AVG. FUEL',
    fuel_cost: 'FUEL COST',
    engine_work: 'ENGINE WORK',
    engine_idle: 'ENGINE IDLE',
    odometer: 'ODOMETER',
    engine_hours: 'ENGINE HOURS',
    driver: 'DRIVER',
    trailer: 'TRAILER',
    no_data_found: 'No data found for this period.',
    drive: 'Drive',
    stop: 'Stop',
    status: 'STATUS',
    start: 'START',
    end: 'END',
    duration: 'DURATION',
    length: 'LENGTH',
    avg_speed: 'AVG. SPEED',
    general: 'General Information',
    general_merged: 'General Information (Merged)',
    general_accuracy: 'General Accuracy',
    travel_sheet: 'Travel Sheet',
    travel_sheet_dn: 'Travel Sheet (Day/Night)',
    driver_travel_sheet: 'Driver Travels',
    time_a: 'TIME A',
    position_a: 'POSITION A',
    odometer_a: 'ODOMETER A',
    time_b: 'TIME B',
    position_b: 'POSITION B',
    odometer_b: 'ODOMETER B',
    max_speed: 'MAX SPEED',
    start_time: 'START TIME',
    departed_from: 'DEPARTED FROM',
    start_odometer: 'START ODOMETER',
    end_time: 'END TIME',
    arrived_at: 'ARRIVED AT',
    end_odometer: 'END ODOMETER',
    distance_travelled_km: 'DISTANCE TRAVELLED KM',
    avg_fuel_cons_100_km: 'AVG. FUEL CONS. (100 KM)',
    overspeednew: 'KOC Directorate Report',
    recorded_speed: 'RECORDED SPEED',
    os_duration: 'OVERSPEED DURATION',
    os_distance: 'OVERSPEED DISTANCE',
    seat_belt: 'SEAT BELT',
    tamper: 'TAMPER',
    violations: 'VIOLATIONS',
    remarks: 'REMARKS',
    heading: 'HEADING',
    plate_number: 'PLATE NUMBER',
    imei: 'IMEI',
    vehicle_heading: 'VEHICLE HEADING DIRECTION',
    allowed_speed_limit: 'ALLOWED SPEED LIMIT',
    harsh_accel: 'HARSH ACCELERATION',
    harsh_brak: 'HARSH BRAKING',
    harsh_corn: 'HARSH CORNERING',
    os_count: 'OVERSPEED COUNT',
    total_violation: 'TOTAL NO. OF VIOLATION',
    object_info: 'Object Information',
    report_summary: 'Report Summary',
    drives_stops_sensors: 'Drives and stops with sensors',
    stop_position: 'Stop position',
  },
  ar: {
    report_results: 'نتائج التقرير',
    object: 'الجسم/المركبة',
    group: 'المجموعة',
    route_start: 'بداية المسار',
    route_end: 'نهاية المسار',
    route_length: 'طول المسار',
    move_duration: 'مدة الحركة',
    stop_duration: 'مدة التوقف',
    stop_count: 'عدد التوقفات',
    top_speed: 'أقصى سرعة',
    speed_limit: 'حد السرعة',
    overspeed_count: 'مرات تجاوز السرعة',
    fuel_consumption: 'استهلاك الوقود',
    avg_fuel: 'متوسط الاستهلاك',
    fuel_cost: 'تكلفة الوقود',
    engine_work: 'عمل المحرك',
    engine_idle: 'خمول المحرك',
    odometer: 'عداد المسافة',
    engine_hours: 'ساعات العمل',
    driver: 'السائق',
    trailer: 'المقطورة',
    no_data_found: 'لا توجد بيانات لهذه الفترة.',
    drive: 'قيادة',
    stop: 'توقف',
    status: 'الحالة',
    start: 'البداية',
    end: 'النهاية',
    duration: 'المدة',
    length: 'المسافة',
    avg_speed: 'متوسط السرعة',
    general: 'معلومات عامة',
    general_merged: 'معلومات عامة (مدمج)',
    general_accuracy: 'دقة المعلومات العامة',
    travel_sheet: 'تقرير الرحلات المجمع',
    travel_sheet_dn: 'تقرير الرحلات (يومي/ليلي)',
    driver_travel_sheet: 'رحلات السائقين',
    time_a: 'وقت البداية',
    position_a: 'مكان التحرك',
    odometer_a: 'عداد البداية',
    time_b: 'وقت النهاية',
    position_b: 'مكان الوصول',
    odometer_b: 'عداد النهاية',
    max_speed: 'أقصى سرعة',
    start_time: 'وقت البداية',
    departed_from: 'مكان التحرك',
    start_odometer: 'عداد البداية',
    end_time: 'وقت النهاية',
    arrived_at: 'مكان الوصول',
    end_odometer: 'عداد النهاية',
    distance_travelled_km: 'المسافة المقطوعة كم',
    avg_fuel_cons_100_km: 'متوسط استهلاك الوقود (100 كم)',
    overspeednew: 'تقرير مديرية KOC',
    plate_number: 'رقم اللوحة',
    recorded_speed: 'السرعة المسجلة',
    os_duration: 'مدة تجاوز السرعة',
    os_distance: 'مسافة تجاوز السرعة',
    seat_belt: 'حزام الأمان',
    tamper: 'إشارة التلاعب',
    violations: 'إجمالي المخالفات',
    remarks: 'ملاحظات القيادة العنيفة',
    heading: 'الاتجاه',
    imei: 'رقم الجهاز (IMEI)',
    vehicle_heading: 'اتجاه حركة المركبة',
    allowed_speed_limit: 'حد السرعة المسموح به',
    harsh_accel: 'تسارع عنيف',
    harsh_brak: 'فرملة عنيفة',
    harsh_corn: 'انعطاف عنيف',
    os_count: 'عدد مرات تجاوز السرعة',
    total_violation: 'إجمالي رقم المخالفات',
  }
};

const t = (key) => translations[currentLang.value][key] || key;
const toggleLang = () => currentLang.value = currentLang.value === 'en' ? 'ar' : 'en';

const reportTypeDisplayName = computed(() => {
    return t(reportType.value) || reportType.value;
});

// Formatting Logic
const formatDuration = (val) => {
  if (!val) return '0 s';
  // If it's already a formatted string like "1 h 22 min", return it
  if (typeof val === 'string' && (val.includes('h') || val.includes('m'))) return val;
  
  const seconds = parseInt(val);
  if (isNaN(seconds)) return val;
  
  const d = Math.floor(seconds / (3600 * 24));
  const h = Math.floor((seconds % (3600 * 24)) / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = seconds % 60;
  
  let res = '';
  if (d > 0) res += `${d} d `;
  if (h > 0) res += `${h} h `;
  if (m > 0) res += `${m} min `;
  if (s > 0 || res === '') res += `${s} s`;
  return res.trim();
};

// Extract Backend Total Row (from props or from paginated API totals_row)
const globalTotalRow = computed(() => {
    if (isGeneralKeyBased.value && generalPaginated.value.totals_row) {
        return generalPaginated.value.totals_row;
    }
    if (!Array.isArray(reportDataFromState.value)) return null;
    return reportDataFromState.value.find(r => 
        r.is_total === true || 
        r.is_total === 1 || 
        r.is_total === 'true' ||
        (r.object && r.object.toString().startsWith('Total:'))
    );
});

// Filter out Total row from main table body (since we use tfoot)
const tableRows = computed(() => {
    if (!Array.isArray(safeReportData.value)) return [];
    return safeReportData.value.filter(r => {
        // Don't filter out totals for travel sheets as they are per-vehicle
        if (['travel_sheet', 'travel_sheet_dn', 'drives_stops'].includes(reportType.value)) {
            return !r.is_header; // Skip header rows as grouped generator adds them
        }
        
        return !r.is_total && 
        r.is_total !== 1 && 
        r.is_total !== 'true' && 
        !(r.object && r.object.toString().startsWith('Total:'));
    });
});

const tableRowsGrouped = computed(() => {
  if (!['travel_sheet', 'travel_sheet_dn', 'driver_travel_sheet', 'drives_stops', 'overspeednew'].includes(reportType.value)) return [];
  const groups = [];
  let currentGroup = null;
  tableRows.value.forEach(row => {
    if (!currentGroup || currentGroup.imei !== row.imei) {
      currentGroup = { imei: row.imei, object: row.object, rows: [] };
      groups.push(currentGroup);
    }
    currentGroup.rows.push(row);
  });
  return groups;
});

// Utility to strip units for calculations/display
const cleanValue = (val) => {
    if (val === undefined || val === null) return '';
    return val.toString().replace(/[a-zA-Z\s]/g, '').trim();
};

const formatSpeed = (val) => {
  if (val === undefined || val === null || val === '') return '0';
  const num = parseFloat(cleanValue(val));
  return isNaN(num) ? '0' : Math.round(num).toString();
};

const totalDistance = computed(() => {
  const sum = tableRows.value.reduce((acc, row) => acc + (parseFloat(cleanValue(row.route_length || row.length)) || 0), 0);
  return sum.toFixed(2);
});

const totalMoveDuration = computed(() => {
    return formatDuration(tableRows.value.reduce((acc, row) => acc + (parseInt(row.route_duration || row.duration) || 0), 0));
});

const topSpeed = computed(() => {
  if (tableRows.value.length === 0) return 0;
  return Math.max(...tableRows.value.map(row => parseFloat(row.top_speed) || 0), 0);
});

const totalFuel = computed(() => {
  const sum = tableRows.value.reduce((acc, row) => acc + (parseFloat(cleanValue(row.fuel_consumption)) || 0), 0);
  return sum.toFixed(2);
});

const exportExcel = () => {
  const table = document.getElementById('report-table-element');
  const wb = XLSX.utils.table_to_book(table);
  XLSX.writeFile(wb, `${reportType.value}_report.xlsx`);
};

const exportPDF = () => {
  const doc = new jsPDF('l', 'mm', 'a4');
  autoTable(doc, { 
    html: '#report-table-element',
    styles: { font: 'helvetica', fontSize: 8 },
    headStyles: { fillColor: [11, 30, 170] }
  });
  doc.save(`${reportType.value}_report.pdf`);
};

const exportHTML = () => {
    const table = document.getElementById('report-table-element').outerHTML;
    const blob = new Blob([`<html><head><style>table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px}th{background:#0b1eaa;color:white}tr:nth-child(even){background:#f2f2f2}</style></head><body>${table}</body></html>`], { type: 'text/html' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `${reportType.value}_report.html`;
    link.click();
};

// Load paginated general info data when we have key but no data
const loadGeneralInfoPage = async (page = 1) => {
  if (['object_info', 'drives_stops_sensors'].includes(reportType.value)) return;
  if (!['general', 'general_information', 'general_accuracy', 'general_merged', 'travel_sheet', 'travel_sheet_dn', 'driver_travel_sheet', 'route_data_sensors', 'drives_stops', 'overspeednew', 'overspeednew2'].includes(reportType.value)) return;
  const keysParam = Array.isArray(reportKey.value) ? reportKey.value.join(',') : reportKey.value;
  const idParam = reportId.value;
  const hashIdParam = hashId.value;

  if (!String(keysParam || '').trim() && !idParam && !hashIdParam) {
    console.log('ReportViewer fetch aborted: No keys, id or hash_id');
    return;
  }

  generalPaginated.value.loading = true;
  console.log('Fetching data for type:', reportType.value, 'id:', idParam, 'hash:', hashIdParam, 'VERSION: 1.1.0_FORCE_RELOAD');
  try {
    const modularEndpoints = {
      'general': '/api/reports/modular/general-information/fetch',
      'general_information': '/api/reports/modular/general-information/fetch',
      'general_accuracy': '/api/reports/modular/general_accuracy/fetch',
      'general_merged': '/api/reports/modular/general-merged/fetch',
      'travel_sheet': '/api/reports/modular/travel-sheet/fetch',
      'travel_sheet_dn': '/api/reports/travel-sheet-dn/fetch',
      'driver_travel_sheet': '/api/reports/modular/driver-travels/fetch',
      'drives_stops': '/api/reports/modular/drives-stops/fetch',
      'route_data_sensors': '/api/reports/modular/route-data-sensors/fetch',
      'overspeednew': '/api/reports/modular/koc-directorate/fetch',
      'overspeednew2': '/api/reports/modular/koc-summary/fetch'
    };
    
    const endpoint = modularEndpoints[reportType.value] || '/api/reports/paginated';
    console.log('Final endpoint determined:', endpoint);

    const res = await api.post(endpoint, {
      keys: keysParam,
      id: idParam,
      hash_id: hashId.value,
      page,
      per_page: generalPaginated.value.per_page,
      data_items: route.query.data_items || '',
      drivers: route.query.driver_ids || ''
    });
    
    // The modular API returns { data: [...], totals: {...} }
    if (['general', 'general_accuracy', 'general_merged', 'travel_sheet', 'travel_sheet_dn', 'driver_travel_sheet', 'route_data_sensors', 'drives_stops', 'overspeednew', 'overspeednew2'].includes(reportType.value)) {
      generalPaginated.value.data = res.data.data || [];
      generalPaginated.value.totals_row = res.data.totals || null;
      generalPaginated.value.current_page = res.data.current_page || page;
      generalPaginated.value.last_page = res.data.last_page || 1;
      generalPaginated.value.total = res.data.total || generalPaginated.value.data.length;
      if (res.data.sensorLabels) {
        sensorLabels.value = res.data.sensorLabels;
      }
    } else {
      generalPaginated.value.data = res.data.data || [];
      generalPaginated.value.totals_row = res.data.totals_row || null;
      generalPaginated.value.current_page = res.data.current_page ?? 1;
      generalPaginated.value.last_page = res.data.last_page ?? 1;
      generalPaginated.value.total = res.data.total ?? 0;
    }
    
    if (res.data.labels) {
      sensorLabels.value = res.data.labels;
    }
  } catch (e) {
    console.error('Failed to load report data:', e);
    generalPaginated.value.data = [];
    generalPaginated.value.totals_row = null;
  } finally {
    generalPaginated.value.loading = false;
  }
};

onMounted(() => {
  if (isGeneralKeyBased.value) loadGeneralInfoPage(1);
});

watch(
  () => [reportKey.value, reportType.value, hashId.value],
  () => {
    if (isGeneralKeyBased.value) {
      generalPaginated.value.data = [];
      generalPaginated.value.totals_row = null;
      loadGeneralInfoPage(1);
    }
  }
);
</script>

<style scoped>
.page-backdrop {
  position: fixed;
  inset: 0;
  background: var(--bg1);
  overflow-y: auto;
  padding: 24px;
  z-index: 100;
}

.page-container {
  width: 100%;
  min-height: 90vh;
  margin: 0 auto;
  background: var(--card);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
  border: 1px solid var(--border);
  overflow: hidden;
  color: var(--text);
}

.modal-header {
  padding: 16px 40px;
  background: var(--card);
  border-bottom: 2px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-icon { color: #0b1eaa; }

.modal-title {
  font-size: 20px;
  font-weight: 700;
  color: #0b1eaa;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 20px;
}

.lang-btn {
    background: var(--input-bg);
    border: 1px solid var(--border);
    color: var(--muted);
    padding: 6px 14px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.lang-btn:hover { background: var(--border); color: var(--text); }

.export-group {
    display: flex;
    background: var(--input-bg);
    padding: 3px;
    border-radius: 10px;
    border: 1px solid var(--border);
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.action-btn.excel { color: #166534; background: transparent; }
.action-btn.pdf { color: #991b1b; background: transparent; }
.action-btn.html { color: #0b1eaa; background: transparent; }

.action-btn:hover { background: var(--border); }

.modal-body {
  flex: 1;
  padding: 30px 40px;
  overflow-y: auto;
}

.report-header-title {
  text-align: center;
  margin-bottom: 24px;
}

.report-header-title h3 {
  font-size: 18px;
  font-weight: 700;
  color: var(--text);
  text-transform: capitalize;
}

/* Dashboard Grid */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: var(--card);
  padding: 20px;
  border-radius: 12px;
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-icon.dist { color: #2563eb; background: rgba(37, 99, 235, 0.1); }
.stat-icon.fuel { color: #059669; background: rgba(5, 150, 105, 0.1); }
.stat-icon.stops { color: #d97706; background: rgba(217, 119, 6, 0.1); }
.stat-icon.overspeed { color: #dc2626; background: rgba(220, 38, 38, 0.1); }
.stat-icon.engine { color: #7c3aed; background: rgba(124, 58, 237, 0.1); }
.stat-icon.idle { color: #4b5563; background: rgba(75, 85, 99, 0.1); }
.stat-icon.odo { color: #0369a1; background: rgba(3, 105, 161, 0.1); }
.stat-icon.time { color: #be185d; background: rgba(190, 24, 93, 0.1); }

.stat-info { display: flex; flex-direction: column; }
.stat-label { font-size: 11px; color: var(--muted); font-weight: 700; text-transform: uppercase; }
.stat-value { font-size: 18px; font-weight: 700; color: var(--text); }

/* Table Section */
.table-container {
  background: var(--card);
  border-radius: 8px;
  border: 1px solid var(--border);
  overflow: auto;
}

.report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

.report-table th {
  background: #0b1eaa;
  padding: 12px 10px;
  text-align: center;
  font-weight: 700;
  color: #ffffff;
  border: 1px solid var(--border);
  text-transform: uppercase;
  white-space: nowrap;
}

.report-table td {
  padding: 10px;
  border: 1px solid var(--border);
  color: var(--text);
}

.table-row:nth-child(even) { background: rgba(0, 0, 0, 0.05); }
.table-row:hover { background: var(--border); }

.sticky-col { 
  position: sticky; 
  left: 0; 
  z-index: 20; 
  font-weight: 700;
  box-shadow: 2px 0 5px -2px rgba(0,0,0,0.3);
}

/* Ensure sticky th header has correct color */
th.sticky-col {
  background-color: #0b1eaa !important;
  z-index: 30;
}

/* Ensure sticky td rows have solid background to avoid transparency */
td.sticky-col {
  background-color: var(--card) !important;
}

.table-row:nth-child(even) td.sticky-col {
  background-color: #1a1b1e !important; /* A slightly darker shade for the even row sticky column */
}

.danger-text { color: #dc2626; font-weight: 700; }

.status-chip {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  display: inline-block;
}
.status-chip.drive { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.status-chip.stop { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

.global-total-row {
  background: var(--input-bg) !important;
  font-weight: 700;
}

.global-total-row td {
  border-top: 2px solid var(--border);
  color: var(--text) !important;
}

.close-btn {
  background: transparent;
  border: none;
  color: var(--muted);
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  transition: all 0.2s;
}

.close-btn:hover { background: rgba(239, 68, 68, 0.1); color: #dc2626; }

.custom-scrollbar::-webkit-scrollbar { width: 8px; height: 8px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--muted); }

.empty-state {
  padding: 60px;
  text-align: center;
  color: var(--muted);
}

.empty-icon {
  margin-bottom: 16px;
  opacity: 0.5;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 16px;
  border: 3px solid var(--border);
  border-top-color: #0b1eaa;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.report-pagination {
  margin-top: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: var(--input-bg);
  border: 1px solid var(--border);
  border-radius: 12px;
}

.pagination-left {
  color: var(--muted);
  font-size: 13px;
}

.pagination-right {
  display: flex;
  align-items: center;
  gap: 24px;
}

.rows-selector {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: var(--muted);
}

.rows-selector select {
  padding: 4px 8px;
  border-radius: 6px;
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--text);
  outline: none;
}

.pagination-btns {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pagination-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: var(--border);
  border-color: #0b1eaa;
  color: #0b1eaa;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-indicator {
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
}

/* Summary Section Styles */
.report-summary-container {
  margin-top: 20px;
  padding: 15px 0;
  border-top: 1px solid #e2e8f0;
}

.summary-title {
  display: none; /* Hidden to match screenshot */
}

.summary-grid {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 25px;
}

.summary-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  border: none !important;
  background: transparent !important;
  padding: 0 !important;
  box-shadow: none !important;
}

.summary-label {
  font-size: 12px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
}

.summary-value {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}

.report-header-info {
  margin-bottom: 25px;
}

.report-header-info .info-row {
  font-size: 13px;
  margin-bottom: 4px;
  color: #1e293b;
}

.report-main-title {
  font-size: 16px;
  font-weight: 800;
  text-align: center;
  margin-bottom: 5px;
  text-transform: uppercase;
  color: #0f172a;
}

.summary-value a {
  color: #0b1eaa;
  text-decoration: underline;
}

/* Professional Report Theming */
.pro-header-row-1 th, .pro-header-row-2 th {
  background: #f8fafc !important;
  color: #1e293b !important;
  border: 1px solid #e2e8f0 !important;
  font-weight: 700 !important;
  text-transform: uppercase;
  font-size: 11px !important;
  letter-spacing: 0.5px;
}

.pro-report-row td {
  border: 1px solid #e2e8f0 !important;
  padding: 10px 12px !important;
  font-size: 12px !important;
  color: #334155;
}

.pro-report-row:hover td {
  background: #f1f5f9 !important;
}

.status-stopped { color: #dc2626; font-weight: 700; }
.status-moving { color: #16a34a; font-weight: 700; }

.address-cell {
  color: #0b1eaa;
  font-size: 11px;
  max-width: 400px;
}

.address-cell a {
  color: #0b1eaa;
  text-decoration: none;
}

.address-cell a:hover {
  text-decoration: underline;
}

.pro-report-row.stopped {
  background: #fffafa;
}

/* RTL Support */
[dir="rtl"] .report-table th, 
[dir="rtl"] .report-table td { text-align: right; }
[dir="rtl"] .sticky-col { left: auto; right: 0; }

.imei-header-row td {
    background-color: #326099 !important;
    color: white !important;
    text-align: center !important;
    font-weight: bold !important;
    border-bottom: 2px solid #1f3e64 !important;
}

.bg-light-blue {
    background-color: #e3f2fd !important;
    color: #1f3e64 !important;
}
</style>
