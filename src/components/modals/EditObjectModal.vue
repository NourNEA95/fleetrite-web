<template>
  <div class="edit-modal-backdrop" @click.self="$emit('close')">
    <div class="edit-modal-container">
      <!-- Header -->
      <div class="edit-modal-header">
        <h2 class="modal-title">Edit object</h2>
        <button class="close-btn" @click="$emit('close')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="18 6 6 18"></polyline>
            <polyline points="6 6 18 18"></polyline>
          </svg>
        </button>
      </div>

      <!-- Body: Sidebar + Main Content -->
      <div class="edit-modal-body">
        
        <!-- Sidebar Tabs -->
        <div class="edit-sidebar">
          <button 
            v-for="tab in tabs" 
            :key="tab.id"
            class="tab-btn" 
            :class="{ active: activeTab === tab.id }"
            @click="activeTab = tab.id"
          >
            {{ tab.name }}
          </button>
        </div>

        <!-- Main Content Area -->
        <div class="edit-content">
          <div v-show="activeTab === 'main'" class="tab-pane">
            <h3 class="pane-title">Main</h3>
            
            <div class="form-group row">
              <label>Name</label>
              <input type="text" v-model="formData.name" class="form-control" />
            </div>

            <div class="form-group row">
              <label>IMEI</label>
              <input type="text" v-model="formData.imei" class="form-control" readonly style="opacity: 0.7; cursor: not-allowed;" />
            </div>

            <div class="form-group row">
              <label>Transport model</label>
              <input type="text" v-model="formData.model" class="form-control" />
            </div>

            <div class="form-group row">
              <label>VIN</label>
              <input type="text" v-model="formData.vin" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Plate number</label>
              <input type="text" v-model="formData.plate" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Group</label>
              <select v-model="formData.group" class="form-control select">
                <option value="0">No group assigned</option>
                <option 
                  v-for="group in formData.groupsList" 
                  :key="group.group_id" 
                  :value="group.group_id"
                >
                  {{ group.group_name }}
                </option>
              </select>
            </div>

            <div class="form-group row">
              <label>Driver</label>
              <select v-model="formData.driver" class="form-control select">
                <option value="-1">No driver</option>
                <option value="0">Auto assign</option>
                <option 
                  v-for="driver in formData.driversList" 
                  :key="driver.driver_id" 
                  :value="driver.driver_id"
                >
                  {{ driver.driver_name }}
                </option>
              </select>
            </div>

            <div class="form-group row">
              <label>Trailer</label>
              <select v-model="formData.trailer" class="form-control select">
                <option value="-1">No trailer</option>
                <option value="0">Auto assign</option>
                <option 
                  v-for="trailer in formData.trailersList" 
                  :key="trailer.trailer_id" 
                  :value="trailer.trailer_id"
                >
                  {{ trailer.trailer_name }}
                </option>
              </select>
            </div>

            <div class="form-group row">
              <label>GPS device</label>
              <input type="text" v-model="formData.device" class="form-control" />
            </div>

            <div class="form-group row">
              <label>SIM card number</label>
              <input type="text" v-model="formData.sim" class="form-control" />
            </div>

            <h3 class="pane-title mt-20">Counters</h3>

            <div class="form-group row">
              <label>Odometer (km)</label>
              <div class="counter-input">
                <select v-model="formData.odometerType" class="form-control select small-select">
                  <option value="off">Off</option>
                  <option value="gps">GPS</option>
                  <option value="sen">Sensor</option>
                </select>
                <input type="number" v-model="formData.odometer" class="form-control" />
              </div>
            </div>

            <div class="form-group row">
              <label>Engine hours (h)</label>
              <div class="counter-input">
                <select v-model="formData.engineHoursType" class="form-control select small-select">
                  <option value="off">Off</option>
                  <option value="acc">ACC</option>
                  <option value="sen">Sensor</option>
                </select>
                <input type="number" v-model="formData.engineHours" class="form-control" />
              </div>
            </div>
            
          </div>

          <!-- Icon Tab -->
          <div v-show="activeTab === 'icon'" class="tab-pane">
            <h3 class="pane-title">Icon</h3>

            <div class="form-group row">
              <label>Shown icon on map</label>
              <select v-model="formData.iconType" class="form-control select">
                <option value="arrow">Arrow</option>
                <option value="icon">Icon</option>
              </select>
            </div>

            <div class="form-group row">
              <label>No connection arrow color</label>
              <select v-model="formData.noConnectionColor" class="form-control select">
                <option value="arrow_red">Red</option>
                <option value="arrow_green">Green</option>
                <option value="arrow_blue">Blue</option>
                <option value="arrow_yellow">Yellow</option>
                <option value="arrow_orange">Orange</option>
                <option value="arrow_gray">Gray</option>
              </select>
            </div>

            <div class="form-group row">
              <label>Stopped arrow color</label>
              <select v-model="formData.stoppedColor" class="form-control select">
                <option value="arrow_red">Red</option>
                <option value="arrow_green">Green</option>
                <option value="arrow_blue">Blue</option>
                <option value="arrow_yellow">Yellow</option>
                <option value="arrow_orange">Orange</option>
                <option value="arrow_gray">Gray</option>
              </select>
            </div>

            <div class="form-group row">
              <label>Moving arrow color</label>
              <select v-model="formData.movingColor" class="form-control select">
                <option value="arrow_red">Red</option>
                <option value="arrow_green">Green</option>
                <option value="arrow_blue">Blue</option>
                <option value="arrow_yellow">Yellow</option>
                <option value="arrow_orange">Orange</option>
                <option value="arrow_gray">Gray</option>
              </select>
            </div>

            <div class="form-group row">
              <label>Engine idle arrow color</label>
              <select v-model="formData.engineIdleColor" class="form-control select">
                <option value="off">Off</option>
                <option value="arrow_red">Red</option>
                <option value="arrow_green">Green</option>
                <option value="arrow_blue">Blue</option>
                <option value="arrow_yellow">Yellow</option>
                <option value="arrow_orange">Orange</option>
                <option value="arrow_gray">Gray</option>
              </select>
            </div>

            <div class="form-group row">
              <label>Icon</label>
              <div class="icon-preview" style="font-size:24px; color: #84cc16;">🚗</div>
            </div>

            <h3 class="pane-title mt-20">Tail</h3>

            <div class="form-group row">
              <label>Tail color</label>
              <div style="display: flex; gap: 10px; flex: 1;">
                <input type="color" v-model="formData.tailColor" class="form-control" style="width: 50px; padding: 2px; height: 38px; cursor: pointer;" />
                <input type="text" v-model="formData.tailColorText" class="form-control" />
              </div>
            </div>

            <div class="form-group row">
              <label>Tail points quantity</label>
              <input type="number" v-model="formData.tailPoints" class="form-control" />
            </div>
          </div>

          <!-- Fuel consumption Tab -->
          <div v-show="activeTab === 'fuel'" class="tab-pane">
            <h3 class="pane-title">Calculation</h3>

            <div class="form-group row">
              <label>Source</label>
              <select v-model="formData.fuelSource" class="form-control select">
                <option value="rates">Rates</option>
              </select>
            </div>

            <div class="form-group row">
              <label>Measurement</label>
              <select v-model="formData.fuelMeasurement" class="form-control select">
                <option value="l100km">l/100km</option>
                <option value="mpg">MPG</option>
              </select>
            </div>

            <div class="form-group row">
              <label>Cost per liter</label>
              <input type="number" v-model="formData.costPerLiter" class="form-control" />
            </div>

            <h3 class="pane-title mt-20">Rates</h3>

            <div class="form-group row">
              <label>Summer rate (kilometers per liter)</label>
              <input type="number" v-model="formData.summerRate" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Winter rate (kilometers per liter)</label>
              <input type="number" v-model="formData.winterRate" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Winter from</label>
              <input type="text" placeholder="MM-DD" v-model="formData.winterFrom" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Winter to</label>
              <input type="text" placeholder="MM-DD" v-model="formData.winterTo" class="form-control" />
            </div>
          </div>

          <!-- Accuracy Tab -->
          <div v-show="activeTab === 'accuracy'" class="tab-pane">
            <h3 class="pane-title">Accuracy</h3>

            <div class="form-group row">
              <label>Time zone offset</label>
              <select v-model="formData.timezone" class="form-control select">
                <option value="utc">(UTC 0:00)</option>
              </select>
            </div>

            <div class="form-group row">
              <label>Detect stops using</label>
              <select v-model="formData.detectStops" class="form-control select">
                <option value="gps">GPS</option>
                <option value="acc">ACC</option>
                <option value="gpsacc">GPS + ACC</option>
              </select>
            </div>

            <div class="form-group row">
              <label>Measure route length using</label>
              <select v-model="formData.routeLength" class="form-control select">
                <option value="gps">GPS</option>
                <option value="odo">Odometer sensor</option>
              </select>
            </div>

            <div class="form-group row">
              <label>Min. moving speed in kph</label>
              <input type="number" v-model="formData.minMovingSpeed" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Min. engine idle speed in kph</label>
              <input type="number" v-model="formData.minIdleSpeed" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Min. difference between track points</label>
              <input type="number" step="0.0001" v-model="formData.minDiffTrackPoints" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Min. gpslev value</label>
              <div class="input-with-checkbox">
                <input type="checkbox" v-model="formData.enableGpslev" />
                <input type="number" v-model="formData.minGpslev" class="form-control" :disabled="!formData.enableGpslev" />
              </div>
            </div>

            <div class="form-group row">
              <label>Max. hdop value</label>
              <div class="input-with-checkbox">
                <input type="checkbox" v-model="formData.enableHdop" />
                <input type="number" v-model="formData.maxHdop" class="form-control" :disabled="!formData.enableHdop" />
              </div>
            </div>

            <div class="form-group row checkbox-only">
              <label>Ignore fuel consumption during stops</label>
              <input type="checkbox" v-model="formData.ignoreFuelStops" />
            </div>

            <div class="form-group row">
              <label>Min. fuel difference detection when speed in kph is not above</label>
              <input type="number" v-model="formData.minFuelDiffSpeed" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Min. fuel difference to detect fuel fillings</label>
              <input type="number" v-model="formData.minFuelDiffFillings" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Min. fuel difference to detect fuel thefts</label>
              <input type="number" v-model="formData.minFuelDiffThefts" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Speed limit</label>
              <input type="text" v-model="formData.speedLimit" class="form-control" />
            </div>

            <div class="form-group row">
              <label>speed Limit Duration (seconds)</label>
              <input type="text" v-model="formData.speedLimitDuration" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Ignore Messages After this Speed</label>
              <input type="text" v-model="formData.ignoreMessagesSpeed" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Device Type</label>
              <select v-model="formData.accuracyDeviceType" class="form-control select">
                 <option value="none"></option>
              </select>
            </div>
            
            <h3 class="pane-title mt-20">Other</h3>

            <div class="form-group row checkbox-only">
              <label>Unassign object driver after ignition is off</label>
              <input type="checkbox" v-model="formData.unassignDriverIgnoff" />
            </div>

            <div class="form-group row checkbox-row-wrap">
              <label>Enable virtual ACC parameter depending on voltage (parameter "accvirt")</label>
              <div class="input-with-checkbox static-width">
                <input type="checkbox" v-model="formData.enableAccvirt" />
                <button class="btn btn-secondary btn-sm" style="margin-left: 10px;">Edit</button>
              </div>
            </div>

            <div class="form-group row checkbox-row-wrap">
              <label>Forward the location data of this object to another object (should be used only for Iridium Satellite solutions).</label>
              <div class="input-with-checkbox static-width">
                <input type="checkbox" v-model="formData.enableForward" />
                <select class="form-control select" style="width: 150px; margin-left:10px" :disabled="!formData.enableForward">
                    <option value=""></option>
                </select>
              </div>
            </div>

            <div class="form-group row checkbox-row-wrap">
              <label>Clear detected sensor cache</label>
              <button class="btn btn-secondary btn-sm" style="margin-left: auto;">Clear</button>
            </div>

          </div>

          <!-- Sensors Tab -->
          <div v-show="activeTab === 'sensors'" class="tab-pane">
            <h3 class="pane-title">Sensors</h3>
            
            <div class="sensors-table-container">
              <table class="sensors-table">
                <thead>
                  <tr>
                    <th style="width: 40px; text-align: center;">
                      <input type="checkbox" class="modern-checkbox" @change="toggleAllSensors" :checked="isAllSensorsSelected" />
                    </th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Parameter</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(sensor, index) in formData.sensorsList" :key="sensor.id || index" @click="editSensor(index)">
                    <td style="text-align: center;" @click.stop>
                      <input type="checkbox" class="modern-checkbox" v-model="selectedSensors" :value="sensor.id" />
                    </td>
                    <td>{{ sensor.name }}</td>
                    <td>{{ sensor.type }}</td>
                    <td>{{ sensor.parameter }}</td>
                  </tr>
                  <tr v-if="formData.sensorsList.length === 0">
                    <td colspan="4" class="empty-table-msg">No sensors available. Click + to add one.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Sensors Action Bar -->
            <div class="sensors-action-bar">
              <button class="action-btn btn-add" @click="addSensor">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              </button>
              
              <button class="action-btn" @click="refreshSensors">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><polyline points="23 20 23 14 17 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>
              </button>

              <div class="menu-container">
                <button class="action-btn" @click="showSensorMenu = !showSensorMenu">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                </button>
                <div class="dropdown-menu" v-if="showSensorMenu">
                  <div class="menu-item" @click="importSensors">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Import
                  </div>
                  <div class="menu-item" @click="exportSensors">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    Export
                  </div>
                  <div class="menu-item text-danger" @click="deleteSelectedSensors">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    Delete
                  </div>
                </div>
              </div>
            </div>

          </div>
          
          <!-- Service Tab -->
          <div v-show="activeTab === 'service'" class="tab-pane">
            <h3 class="pane-title">Service</h3>
            
            <div class="sensors-table-container">
              <table class="sensors-table">
                <thead>
                  <tr>
                    <th style="width: 40px; text-align: center;">
                      <input type="checkbox" class="modern-checkbox" @change="toggleAllServices" :checked="isAllServicesSelected" />
                    </th>
                    <th>Name</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(srv, index) in formData.serviceList" :key="srv.id || index" @click="editService(index)">
                    <td style="text-align: center;" @click.stop>
                      <input type="checkbox" class="modern-checkbox" v-model="selectedServices" :value="srv.id" />
                    </td>
                    <td>{{ srv.name }}</td>
                    <td :class="{'text-danger': srv.status.includes('expired')}">{{ srv.status }}</td>
                  </tr>
                  <tr v-if="formData.serviceList.length === 0">
                    <td colspan="3" class="empty-table-msg">No services available. Click + to add one.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Service Action Bar -->
            <div class="sensors-action-bar">
              <button class="action-btn btn-add" @click="addService">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              </button>
              
              <button class="action-btn" @click="refreshServices">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><polyline points="23 20 23 14 17 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>
              </button>

              <div class="menu-container">
                <button class="action-btn" @click="showServiceMenu = !showServiceMenu">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                </button>
                <div class="dropdown-menu" v-if="showServiceMenu">
                  <div class="menu-item" @click="importServices">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Import
                  </div>
                  <div class="menu-item" @click="exportServices">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    Export
                  </div>
                  <div class="menu-item text-danger" @click="deleteSelectedServices">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    Delete
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- Custom Fields Tab -->
          <div v-show="activeTab === 'custom'" class="tab-pane">
            <h3 class="pane-title">Custom fields</h3>
            
            <div class="sensors-table-container">
              <table class="sensors-table">
                <thead>
                  <tr>
                    <th style="width: 40px; text-align: center;">
                      <input type="checkbox" class="modern-checkbox" @change="toggleAllCustomFields" :checked="isAllCustomFieldsSelected" />
                    </th>
                    <th>Name</th>
                    <th>Value</th>
                    <th style="width: 80px; text-align: center;">Data list</th>
                    <th style="width: 80px; text-align: center;">Popup</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(cf, index) in formData.customFieldsList" :key="cf.id || index" @click="editCustomField(index)">
                    <td style="text-align: center;" @click.stop>
                      <input type="checkbox" class="modern-checkbox" v-model="selectedCustomFields" :value="cf.id" />
                    </td>
                    <td>{{ cf.name }}</td>
                    <td>{{ cf.value }}</td>
                    <td style="text-align: center;"><input type="checkbox" class="modern-checkbox" disabled :checked="cf.dataList" /></td>
                    <td style="text-align: center;"><input type="checkbox" class="modern-checkbox" disabled :checked="cf.popup" /></td>
                  </tr>
                  <tr v-if="formData.customFieldsList.length === 0">
                    <td colspan="5" class="empty-table-msg">No custom fields available. Click + to add one.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="sensors-action-bar">
              <button class="action-btn btn-add" @click="addCustomField">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              </button>
              
              <button class="action-btn" @click="refreshCustomFields">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><polyline points="23 20 23 14 17 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>
              </button>

              <div class="menu-container">
                <button class="action-btn" @click="showCustomFieldMenu = !showCustomFieldMenu">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                </button>
                <div class="dropdown-menu" v-if="showCustomFieldMenu">
                  <div class="menu-item" @click="importCustomFields">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Import
                  </div>
                  <div class="menu-item" @click="exportCustomFields">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    Export
                  </div>
                  <div class="menu-item text-danger" @click="deleteSelectedCustomFields">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    Delete
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Info Tab -->
          <div v-show="activeTab === 'info'" class="tab-pane">
            <h3 class="pane-title">Info</h3>
            
            <div class="sensors-table-container">
              <table class="sensors-table info-table">
                <thead>
                  <tr>
                    <th>Data</th>
                    <th>Value</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in formData.infoList" :key="index">
                    <td style="font-weight: 500; width: 40%;">{{ item.data }}</td>
                    <td>{{ item.value }}</td>
                  </tr>
                  <tr v-if="formData.infoList.length === 0">
                    <td colspan="2" class="empty-table-msg">No info available.</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <button class="action-btn" @click="refreshInfo" style="margin-top: 15px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><polyline points="23 20 23 14 17 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>
            </button>
          </div>

          <!-- Profile Tab -->
          <div v-show="activeTab === 'profile'" class="tab-pane">
            <h3 class="pane-title">Profile</h3>

            <div class="form-group row">
              <label>Vin</label>
              <input type="text" v-model="formData.profileData.vin" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Vehicle Type</label>
              <select v-model="formData.profileData.type" class="form-control select">
                <option value="Car">Car</option>
                <option value="Truck">Truck</option>
                <option value="Van">Van</option>
                <option value="Motorcycle">Motorcycle</option>
              </select>
            </div>

            <div class="form-group row checkbox-only">
              <label>Ignore updates during stops</label>
              <span style="display:flex; align-items:center; gap:8px; font-size:13px; color:white;">Yes <input type="checkbox" v-model="formData.profileData.ignoreStops" class="modern-checkbox" /></span>
            </div>

            <div class="form-group row">
              <label>Brand</label>
              <input type="text" v-model="formData.profileData.brand" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Model</label>
              <input type="text" v-model="formData.profileData.model" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Year</label>
              <input type="text" v-model="formData.profileData.year" class="form-control" style="max-width: 150px;" />
            </div>

            <div class="form-group row">
              <label>Colour</label>
              <input type="text" v-model="formData.profileData.color" class="form-control" />
            </div>

            <div class="form-group row">
              <label>Insurance Expiry Date</label>
              <input type="date" v-model="formData.profileData.insuranceExpiry" class="form-control" />
            </div>

            <div class="form-group row" style="align-items: flex-start;">
              <label>Image <span class="text-danger">(png only)</span></label>
              <div class="image-dropzone">
                Drop files here to upload
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Footer Buttons -->
      <div class="edit-modal-footer">
        <button class="btn btn-primary" @click="handleSave">Save</button>
        <button class="btn btn-secondary" @click="$emit('close')">Cancel</button>
      </div>

    </div>

    <!-- Nested Sensor Properties Modal -->
    <SensorPropertiesModal 
      v-if="showSensorFormModal"
      :sensor="editingSensor"
      @close="showSensorFormModal = false"
      @save="saveSensor"
    />

    <!-- Nested Service Properties Modal -->
    <ServicePropertiesModal 
      v-if="showServiceFormModal"
      :service="editingService"
      @close="showServiceFormModal = false"
      @save="saveService"
    />
    <!-- Nested Custom Field Properties Modal -->
    <CustomFieldPropertiesModal 
      v-if="showCustomFieldFormModal"
      :field="editingCustomField"
      @close="showCustomFieldFormModal = false"
      @save="saveCustomField"
    />

  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import SensorPropertiesModal from './SensorPropertiesModal.vue';
import ServicePropertiesModal from './ServicePropertiesModal.vue';
import CustomFieldPropertiesModal from './CustomFieldPropertiesModal.vue';
import api from '@/services/api';

const props = defineProps({
  vehicle: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'save']);

const activeTab = ref('main');
const isLoading = ref(false);

const tabs = [
  { id: 'main', name: 'Main' },
  { id: 'icon', name: 'Icon' },
  { id: 'fuel', name: 'Fuel consumption' },
  { id: 'accuracy', name: 'Accuracy' },
  { id: 'sensors', name: 'Sensors' },
  { id: 'service', name: 'Service' },
  { id: 'custom', name: 'Custom fields' },
  { id: 'info', name: 'Info' },
  { id: 'profile', name: 'Profile' }
];

const formData = reactive({
  name: '',
  imei: '',
  model: '',
  vin: '',
  plate: '',
  group: 0,
  driver: 0,
  trailer: 0,
  groupsList: [],
  driversList: [],
  trailersList: [],
  device: '',
  sim: '',
  odometerType: 'gps',
  odometer: 0,
  engineHoursType: 'off',
  engineHours: 0,
  
  // Icon tab
  iconType: 'arrow',
  noConnectionColor: 'red',
  stoppedColor: 'red',
  movingColor: 'green',
  engineIdleColor: 'off',
  tailColor: '#00FF44',
  tailColorText: '00FF44',
  tailPoints: 7,

  // Fuel consumption tab
  fuelSource: 'rates',
  fuelMeasurement: 'l/100km',
  costPerLiter: 0,
  summerRate: 0,
  winterRate: 0,
  winterFrom: '12-01',
  winterTo: '03-01',

  // Accuracy tab
  timezone: 'utc',
  detectStops: 'gps',
  routeLength: 'gps',
  minMovingSpeed: 6,
  minIdleSpeed: 3,
  minDiffTrackPoints: 0.0005,
  enableGpslev: false,
  minGpslev: 5,
  enableHdop: false,
  maxHdop: 3,
  ignoreFuelStops: false,
  minFuelDiffSpeed: 10,
  minFuelDiffFillings: 10,
  minFuelDiffThefts: 10,
  speedLimit: '',
  speedLimitDuration: '',
  ignoreMessagesSpeed: '',
  accuracyDeviceType: 'none',
  unassignDriverIgnoff: false,
  enableAccvirt: false,
  enableForward: false,

  // Sensors Data Array
  sensorsList: [
    { id: 's1', name: 'Battery', type: 'Battery', parameter: 'battery' },
    { id: 's2', name: 'Driver Id', type: 'Driver assign', parameter: 'driverUniqueId' },
    { id: 's3', name: 'Ignition', type: 'Ignition (ACC)', parameter: 'acc' },
    { id: 's4', name: 'Main Power', type: 'Custom', parameter: 'io66' },
    { id: 's5', name: 'odometer', type: 'Odometer', parameter: 'odometer' }
  ],

  // Services Data Array
  serviceList: [
    { id: 'srv1', name: 'change oil', status: 'odometer expired (12023 km)' },
    { id: 'srv2', name: 'iihih', status: 'days expired (228)' }
  ],

  // Custom Fields Data Array
  customFieldsList: [],

  // Info Data Array
  infoList: [
    { data: 'Altitude', value: '24 m' },
    { data: 'Angle', value: '270 °' },
    { data: 'Latitude', value: '29.295623 °' },
    { data: 'Longitude', value: '47.823623 °' },
    { data: 'Parameters', value: 'priority=0, sat=19, event=0, rssi=5, in1=1, out1=, in2=, in3=, out2=, io113=95, pdop=1' },
    { data: 'Protocol', value: 'teltonika' },
    { data: 'Speed', value: '84 km/h' },
    { data: 'Time (position)', value: '2026-03-11 06:59:28' },
    { data: 'Time (server)', value: '2026-03-11 06:59:32' }
  ],

  // Profile Data object
  profileData: {
    vin: '',
    type: 'Car',
    ignoreStops: false,
    brand: '',
    model: '',
    year: '',
    color: '',
    insuranceExpiry: ''
  }
});

// Sensors State
const selectedSensors = ref([]);
const showSensorMenu = ref(false);
const showSensorFormModal = ref(false);
const editingSensor = ref(null);
const editingSensorIndex = ref(-1);

const isAllSensorsSelected = computed(() => {
  return formData.sensorsList.length > 0 && selectedSensors.value.length === formData.sensorsList.length;
});

function toggleAllSensors(e) {
  if (e.target.checked) {
    selectedSensors.value = formData.sensorsList.map(s => s.id);
  } else {
    selectedSensors.value = [];
  }
}

function addSensor() {
  editingSensor.value = null;
  editingSensorIndex.value = -1;
  showSensorFormModal.value = true;
}

function editSensor(index) {
  editingSensor.value = { ...formData.sensorsList[index] };
  editingSensorIndex.value = index;
  showSensorFormModal.value = true;
}

function saveSensor(sensor) {
  if (editingSensorIndex.value > -1) {
    formData.sensorsList[editingSensorIndex.value] = sensor;
  } else {
    formData.sensorsList.push(sensor);
  }
  showSensorFormModal.value = false;
}

function deleteSelectedSensors() {
  if (selectedSensors.value.length === 0) return;
  formData.sensorsList = formData.sensorsList.filter(s => !selectedSensors.value.includes(s.id));
  selectedSensors.value = [];
  showSensorMenu.value = false;
}

function exportSensors() {
  const data = JSON.stringify(formData.sensorsList, null, 2);
  const blob = new Blob([data], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `sensors_${formData.imei || Date.now()}.json`;
  a.click();
  URL.revokeObjectURL(url);
  showSensorMenu.value = false;
}

function importSensors() {
  alert('Import logic will be implemented via backend later.');
  showSensorMenu.value = false;
}

function refreshSensors() {
  selectedSensors.value = [];
}

// Services State
const selectedServices = ref([]);
const showServiceMenu = ref(false);
const showServiceFormModal = ref(false);
const editingService = ref(null);
const editingServiceIndex = ref(-1);

const isAllServicesSelected = computed(() => {
  return formData.serviceList.length > 0 && selectedServices.value.length === formData.serviceList.length;
});

function toggleAllServices(e) {
  if (e.target.checked) {
    selectedServices.value = formData.serviceList.map(s => s.id);
  } else {
    selectedServices.value = [];
  }
}

function addService() {
  editingService.value = null;
  editingServiceIndex.value = -1;
  showServiceFormModal.value = true;
}

function editService(index) {
  editingService.value = { ...formData.serviceList[index] };
  editingServiceIndex.value = index;
  showServiceFormModal.value = true;
}

function saveService(service) {
  if (editingServiceIndex.value > -1) {
    formData.serviceList[editingServiceIndex.value] = service;
  } else {
    formData.serviceList.push(service);
  }
  showServiceFormModal.value = false;
}

function deleteSelectedServices() {
  if (selectedServices.value.length === 0) return;
  formData.serviceList = formData.serviceList.filter(s => !selectedServices.value.includes(s.id));
  selectedServices.value = [];
  showServiceMenu.value = false;
}

function exportServices() {
  const data = JSON.stringify(formData.serviceList, null, 2);
  const blob = new Blob([data], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `services_${formData.imei || Date.now()}.json`;
  a.click();
  URL.revokeObjectURL(url);
  showServiceMenu.value = false;
}

function importServices() {
  alert('Import logic will be implemented via backend later.');
  showServiceMenu.value = false;
}

function refreshServices() {
  selectedServices.value = [];
}

// Custom Fields State
const selectedCustomFields = ref([]);
const showCustomFieldMenu = ref(false);
const showCustomFieldFormModal = ref(false);
const editingCustomField = ref(null);
const editingCustomFieldIndex = ref(-1);

const isAllCustomFieldsSelected = computed(() => {
  return formData.customFieldsList.length > 0 && selectedCustomFields.value.length === formData.customFieldsList.length;
});

function toggleAllCustomFields(e) {
  if (e.target.checked) {
    selectedCustomFields.value = formData.customFieldsList.map(cf => cf.id);
  } else {
    selectedCustomFields.value = [];
  }
}

function addCustomField() {
  editingCustomField.value = null;
  editingCustomFieldIndex.value = -1;
  showCustomFieldFormModal.value = true;
}

function editCustomField(index) {
  editingCustomField.value = { ...formData.customFieldsList[index] };
  editingCustomFieldIndex.value = index;
  showCustomFieldFormModal.value = true;
}

function saveCustomField(cf) {
  if (editingCustomFieldIndex.value > -1) {
    formData.customFieldsList[editingCustomFieldIndex.value] = cf;
  } else {
    formData.customFieldsList.push(cf);
  }
  showCustomFieldFormModal.value = false;
}

function deleteSelectedCustomFields() {
  if (selectedCustomFields.value.length === 0) return;
  formData.customFieldsList = formData.customFieldsList.filter(cf => !selectedCustomFields.value.includes(cf.id));
  selectedCustomFields.value = [];
  showCustomFieldMenu.value = false;
}

function exportCustomFields() {
  const data = JSON.stringify(formData.customFieldsList, null, 2);
  const blob = new Blob([data], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `custom_fields_${formData.imei || Date.now()}.json`;
  a.click();
  URL.revokeObjectURL(url);
  showCustomFieldMenu.value = false;
}

function importCustomFields() {
  alert('Import logic will be implemented via backend later.');
  showCustomFieldMenu.value = false;
}

function refreshCustomFields() {
  selectedCustomFields.value = [];
}

function refreshInfo() {
  // Placeholder for refreshing info
}

async function fetchObjectSettings() {
  if (!props.vehicle?.imei) return;
  
  isLoading.value = true;
  try {
    const response = await api.get(`/api/objects/${props.vehicle.imei}/settings`);
    if (response.data.success) {
      const data = response.data.data;
      
      // Map Main Data
      formData.name = data.name;
      formData.imei = data.imei;
      formData.model = data.model;
      formData.vin = data.vin;
      formData.plate = data.plate_number;
      formData.device = data.device;
      formData.sim = data.sim_number;
      formData.odometer = data.config.odometer;
      formData.odometerType = data.config.odometer_type;
      formData.engineHours = data.config.engine_hours;
      formData.engineHoursType = data.config.engine_hours_type;
      formData.group = data.user_link.group_id || 0;
      formData.driver = data.user_link.driver_id || 0;
      formData.trailer = data.user_link.trailer_id || 0;
      
      // Map Selection Lists
      formData.groupsList = data.lists.groups || [];
      formData.driversList = data.lists.drivers || [];
      formData.trailersList = data.lists.trailers || [];
      
      // Map Icon Tab
      if (data.config.map_arrows) {
        formData.noConnectionColor = data.config.map_arrows.arrow_no_connection || 'arrow_red';
        formData.stoppedColor = data.config.map_arrows.arrow_stopped || 'arrow_red';
        formData.movingColor = data.config.map_arrows.arrow_moving || 'arrow_green';
        formData.engineIdleColor = data.config.map_arrows.arrow_engine_idle || 'off';
      }
      formData.iconType = data.config.map_icon || 'arrow';
      formData.tailColor = data.config.tail_color || '#00FF44';
      formData.tailColorText = (data.config.tail_color || '00FF44').replace('#', '');
      formData.tailPoints = data.config.tail_points || 7;

      // Map Fuel Tab
      if (data.config.fcr) {
        formData.fuelSource = data.config.fcr.source || 'rates';
        formData.fuelMeasurement = data.config.fcr.measurement || 'l100km';
        formData.costPerLiter = data.config.fcr.cost || 0;
        formData.summerRate = data.config.fcr.summer || 0;
        formData.winterRate = data.config.fcr.winter || 0;
        formData.winterFrom = data.config.fcr.winter_start || '12-01';
        formData.winterTo = data.config.fcr.winter_end || '03-01';
      }

      // Map Accuracy Tab
      formData.timezone = data.config.time_adj || 'utc';
      if (data.config.accuracy) {
        formData.detectStops = data.config.accuracy.stops || 'gps';
        formData.routeLength = data.config.accuracy.route_length || 'gps';
        formData.minMovingSpeed = data.config.accuracy.min_moving_speed || 6;
        formData.minIdleSpeed = data.config.accuracy.min_idle_speed || 3;
        formData.minDiffTrackPoints = data.config.accuracy.min_diff_points || 0.0005;
        formData.enableGpslev = data.config.accuracy.use_gpslev || false;
        formData.minGpslev = data.config.accuracy.min_gpslev || 5;
        formData.enableHdop = data.config.accuracy.use_hdop || false;
        formData.maxHdop = data.config.accuracy.max_hdop || 3;
        formData.ignoreFuelStops = data.config.accuracy.ign_fuel_cons_stops || false;
        formData.minFuelDiffSpeed = data.config.accuracy.min_fuel_speed || 10;
        formData.minFuelDiffFillings = data.config.accuracy.min_ff || 10;
        formData.minFuelDiffThefts = data.config.accuracy.min_ft || 10;
      }
      formData.speedLimit = data.performance.speed_limit || '';
      formData.speedLimitDuration = data.performance.speed_limit_duration || '';
      formData.ignoreMessagesSpeed = data.performance.ignore_after_speed || '';
      formData.accuracyDeviceType = data.performance.accuracy_car_type || 'none';

      // Map Dynamic Lists
      formData.sensorsList = data.sensors || [];
      formData.serviceList = data.services || [];
      formData.customFieldsList = data.custom_fields || [];
      
      // Map Info Tab
      formData.infoList = [
        { data: 'Altitude', value: data.info.altitude || '0 m' },
        { data: 'Angle', value: data.info.angle || '0 °' },
        { data: 'Latitude', value: data.info.lat || '0.000000 °' },
        { data: 'Longitude', value: data.info.lng || '0.000000 °' },
        { data: 'Parameters', value: data.info.params || '' },
        { data: 'Protocol', value: data.info.protocol || '' },
        { data: 'Speed', value: data.info.speed || '0 km/h' },
        { data: 'Time (position)', value: data.info.dt_tracker || '' },
        { data: 'Time (server)', value: data.info.dt_server || '' }
      ];

      // Map Profile Tab
      formData.profileData.vin = data.profile.vin || '';
      formData.profileData.type = data.profile.vehicle_type || 'Car';
      formData.profileData.ignoreStops = data.profile.ignore_updates_stop === 'yes';
      formData.profileData.brand = data.profile.brand || '';
      formData.profileData.model = data.profile.model || '';
      formData.profileData.year = data.profile.year || '';
      formData.profileData.color = data.profile.color || '';
      formData.profileData.insuranceExpiry = data.profile.insurance_ex_day || '';
    }
  } catch (error) {
    console.error("Error fetching object settings:", error);
  } finally {
    isLoading.value = false;
  }
}

onMounted(() => {
  fetchObjectSettings();
});

function handleSave() {
  // Pass the modified data back up
  emit('save', { ...formData });
}
</script>

<style scoped>
.edit-modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(2px);
  z-index: 99999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.edit-modal-container {
  background: #0f1b33;
  width: 90vw;
  max-width: 900px;
  max-height: 90vh;
  border-radius: 12px;
  border: 1px solid var(--border);
  box-shadow: 0 20px 40px rgba(0,0,0,0.6);
  display: flex;
  flex-direction: column;
}

/* Header */
.edit-modal-header {
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
  transition: color 0.2s;
}

.close-btn:hover {
  color: #ef4444;
}

/* Body */
.edit-modal-body {
  display: flex;
  flex: 1;
  overflow: hidden; /* prevents layout breakage */
}

/* Sidebar Tabs */
.edit-sidebar {
  width: 220px;
  background: rgba(255, 255, 255, 0.02);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}

/* Custom Scrollbar */
.edit-sidebar::-webkit-scrollbar,
.edit-content::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
.edit-sidebar::-webkit-scrollbar-track,
.edit-content::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.1); 
  border-radius: 4px;
}
.edit-sidebar::-webkit-scrollbar-thumb,
.edit-content::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.15); 
  border-radius: 4px;
}
.edit-sidebar::-webkit-scrollbar-thumb:hover,
.edit-content::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.25); 
}

.tab-btn {
  background: transparent;
  border: none;
  text-align: left;
  padding: 15px 20px;
  color: var(--muted);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border-left: 3px solid transparent;
}

.tab-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  color: white;
}

.tab-btn.active {
  background: rgba(79, 124, 255, 0.1);
  color: var(--accent);
  border-left-color: var(--accent);
}

/* Content Area */
.edit-content {
  flex: 1;
  padding: 25px 30px;
  overflow-y: auto;
}

.pane-title {
  margin: 0 0 20px 0;
  color: var(--accent);
  font-size: 15px;
  font-weight: 600;
}

.mt-20 {
  margin-top: 30px;
}

/* Form Styles */
.form-group.row {
  display: flex;
  align-items: center;
  margin-bottom: 15px;
}

.form-group.row label {
  width: 250px;
  font-size: 13px;
  color: var(--text);
  font-weight: 500;
  line-height: 1.4;
  padding-right: 15px;
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

.form-control:focus {
  border-color: var(--accent);
}

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

.counter-input {
  flex: 1;
  display: flex;
  gap: 15px;
}

.small-select {
  width: 120px;
  flex: none;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--muted);
  gap: 15px;
  text-align: center;
}

.empty-state p {
  font-size: 14px;
}

/* Additions for checkboxes and specific layouts */
.input-with-checkbox {
  display: flex;
  align-items: center;
  gap: 15px;
  flex: 1;
}

.input-with-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: var(--accent);
}

.static-width {
  flex: none;
}

.form-group.checkbox-only {
  flex-direction: row;
}

.form-group.checkbox-only label {
  flex: 1;
}

.form-group.checkbox-only input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: var(--accent);
}

.checkbox-row-wrap {
  justify-content: space-between;
}

.checkbox-row-wrap label {
  flex: 1;
}

.btn-sm {
  padding: 6px 15px;
  font-size: 12px;
}

/* Sensors Tab Styles */
.sensors-table-container {
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 6px;
  overflow: hidden;
  margin-bottom: 20px;
}

.sensors-table {
  width: 100%;
  border-collapse: collapse;
}

.sensors-table th, .sensors-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  font-size: 13px;
  color: var(--text);
}

.sensors-table th {
  background: rgba(0,0,0,0.2);
  color: var(--muted);
  font-weight: 600;
}

.sensors-table tbody tr {
  cursor: pointer;
  transition: background 0.1s;
}

.sensors-table tbody tr:hover {
  background: rgba(255,255,255,0.03);
}

.empty-table-msg {
  text-align: center !important;
  color: var(--muted);
  padding: 30px !important;
}

.modern-checkbox {
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: var(--accent);
}

.sensors-action-bar {
  display: flex;
  align-items: center;
  gap: 15px;
}

.action-btn {
  width: 36px;
  height: 36px;
  border-radius: 6px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  color: var(--text);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover {
  background: rgba(255,255,255,0.1);
}

.action-btn.btn-add {
  background: var(--accent);
  color: white;
  border-color: var(--accent);
}

.action-btn.btn-add:hover {
  background: #3b66df;
}

.menu-container {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  bottom: 45px;
  left: 0;
  background: #172440;
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 6px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.5);
  width: 150px;
  overflow: hidden;
  z-index: 100;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 15px;
  font-size: 13px;
  color: white;
  cursor: pointer;
  transition: background 0.2s;
}

.menu-item:hover {
  background: rgba(255,255,255,0.05);
}

.text-danger {
  color: #ef4444;
}

.info-table th, .info-table td {
  padding: 10px 15px;
}
.image-dropzone {
  flex: 1;
  border: 1px dashed rgba(255,255,255,0.2);
  border-radius: 6px;
  padding: 40px;
  text-align: center;
  color: var(--muted);
  background: rgba(0,0,0,0.1);
  cursor: pointer;
  transition: all 0.2s;
}
.image-dropzone:hover {
  border-color: var(--accent);
  background: rgba(0,0,0,0.2);
}

/* Footer */
.edit-modal-footer {
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

.btn-primary {
  background: var(--accent);
  color: white;
}
.btn-primary:hover {
  background: #3b66df;
}

.btn-secondary {
  background: transparent;
  color: white;
  border: 1px solid rgba(255,255,255,0.2);
}
.btn-secondary:hover {
  background: rgba(255,255,255,0.05);
}

/* Responsive */
@media (max-width: 992px) {
  .edit-modal-container {
    width: 95%;
  }
}

@media (max-width: 768px) {
  .edit-modal-container {
    width: 100%;
    height: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .edit-modal-body {
    flex-direction: column;
  }
  .edit-sidebar {
    width: 100%;
    flex-direction: row;
    height: auto;
    flex-shrink: 0;
    border-right: none;
    border-bottom: 1px solid var(--border);
    overflow-x: auto;
    overflow-y: hidden;
  }
  .tab-btn {
    border-left: none;
    border-bottom: 3px solid transparent;
    padding: 15px 20px;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .tab-btn.active {
    border-bottom-color: var(--accent);
  }
  .form-group.row {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  .form-group.row label {
    width: 100%;
  }
  .form-control {
    width: 100%;
  }
  .counter-input {
    flex-direction: column;
    width: 100%;
  }
  .small-select {
    width: 100%;
  }
  .edit-modal-footer {
    flex-direction: column;
    padding: 16px;
  }
  .edit-modal-footer .btn {
    width: 100%;
    max-width: none;
  }
}
</style>
