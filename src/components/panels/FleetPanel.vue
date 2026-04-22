<template>
  <div class="tab-content-wrapper">
    <div class="panel-header-modern" v-show="isPanelOpen">
      <!-- Row 1: Status Pills -->
      <div class="status-pills-row">
        <div 
          class="status-pill-item" 
          :class="['all', { active: filter === 'All' }]"
          @click="$emit('update:filter', 'All')"
        >
          <span class="dot-wrapper"><span class="dot"></span></span>
          <span v-show="filter === 'All'" class="pill-label">All</span>
        </div>

        <div 
          class="status-pill-item" 
          :class="['moving', { active: filter === 'Moving' }]"
          @click="$emit('update:filter', 'Moving')"
        >
          <span class="dot-wrapper"><span class="dot"></span></span>
          <span v-show="filter === 'Moving'" class="pill-label">Moving</span>
        </div>

        <div 
          class="status-pill-item" 
          :class="['idle', { active: filter === 'Idle' }]"
          @click="$emit('update:filter', 'Idle')"
        >
          <span class="dot-wrapper"><span class="dot"></span></span>
          <span v-show="filter === 'Idle'" class="pill-label">Idle</span>
        </div>

        <div 
          class="status-pill-item" 
          :class="['stopped', { active: ['Stopped', 'Parking'].includes(filter) }]"
          @click="$emit('update:filter', 'Stopped')"
        >
          <span class="dot-wrapper"><span class="dot"></span></span>
          <span v-show="['Stopped', 'Parking'].includes(filter)" class="pill-label">Parking</span>
        </div>

        <div 
          class="status-pill-item" 
          :class="['offline', { active: filter === 'Offline' }]"
          @click="$emit('update:filter', 'Offline')"
        >
          <span class="dot-wrapper"><span class="dot"></span></span>
          <span v-show="filter === 'Offline'" class="pill-label">Offline</span>
        </div>
      </div>

      <!-- Row 2: Search Row -->
      <div class="actions-search-row">
        <div class="selection-box-wrapper">
          <div class="selection-box"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
        </div>
        
        <div class="search-input-container">
          <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input type="text" :value="searchQuery" @input="$emit('update:searchQuery', $event.target.value)" placeholder="" />
        </div>
      </div>

      <!-- Row 3: Summary & Tools -->
      <div class="summary-actions-row">
        <div class="summary-row">
          <span class="summary-label">Total Cars</span>
          <span class="summary-value">{{ filteredObjects.length }} Cars</span>
        </div>
        <div class="header-tools">
          <button class="tool-btn"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
          <button class="tool-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></button>
          <button class="tool-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg></button>
        </div>
      </div>
    </div>


    <div class="objects-list" v-show="isPanelOpen">
      <div v-for="(group, groupName) in groupedObjects" :key="groupName" class="object-group">
        <div class="group-header" :class="{ 'is-collapsed': collapsedGroups[groupName] }" @click="toggleGroup(groupName)">
          <div class="group-header-left">
            <svg class="group-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
            <span class="group-name">{{ groupName }}</span>
          </div>
          <span class="group-count">{{ group.length }} Cars</span>
        </div>
        
        <div v-show="!collapsedGroups[groupName]" class="group-content">
          <div 
            v-for="obj in group" 
            :key="obj.imei" 
            class="vehicle-card" 
            :class="['status-' + (obj.status || 'offline').toLowerCase(), {'selected': selectedImei === obj.imei}]"
            @mouseenter="handleMouseEnter(obj, $event)"
            @mouseleave="handleMouseLeave"
            @click="$emit('select-vehicle', obj)"
          >
            <div class="v-card-main">
              <!-- Visual Checkbox -->
              <div class="v-checkbox-visual">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="3" width="18" height="18" rx="4" />
                </svg>
              </div>

              <!-- Vehicle Status Icon -->
              <div class="v-icon" :class="(obj.status || 'offline').toLowerCase()">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13 8 13.67 8 14.5 7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                </svg>
              </div>

              <!-- Vehicle Info -->
              <div class="v-info">
                <div class="v-name">{{ obj.name }}</div>
                <div class="v-datetime">{{ formatDt(obj.dt_tracker) }}</div>
              </div>

              <!-- Top Row Indicators -->
              <div class="v-top-indicators">
                <div class="v-sensors">
                  <span class="sensor-icon" :class="{ 'on': obj.ignition === 'On' }">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                  </span>
                  <span class="sensor-icon signal" :class="getSignalClass(obj.dt_tracker)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/></svg>
                  </span>
                </div>
                <div class="v-speed">{{ formatSpeed(obj.speed) }} <span class="km">km/h</span></div>
              </div>
            </div>

            <!-- Action Toolbar -->
            <div class="v-card-actions">
              <button class="action-square-btn" title="Live Follow" @click.stop="$emit('follow-vehicle', obj)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg>
              </button>
              <button class="action-square-btn" title="History" @click.stop="$emit('open-history', obj)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
              </button>
              <button class="action-square-btn" title="Center" @click.stop="$emit('select-vehicle', obj)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
              </button>
              <button class="action-square-btn" title="Share" @click.stop="$emit('share-position', obj)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
              </button>
              <button class="action-square-btn" title="Settings" @click.stop="$emit('send-command', obj)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>

    <!-- Empty state -->
      <div v-if="Object.keys(groupedObjects).length === 0" class="empty-fleet">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" opacity="0.2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
        <p>No vehicles found</p>
      </div>
  </div>
</div>

  <Teleport to="body">
    <div v-show="activeMenu" class="vehicle-menu-dropdown global" :style="menuStyle" v-click-outside="closeMenu">
      <!-- Show History with Submenu -->
      <div class="menu-item-wrapper has-submenu">
        <button class="menu-item highlight" @click.stop="handleShowHistoryDefault(activeMenuObj)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3"></path><path d="M3.05 11a9 9 0 1 1 .5 4"></path><polyline points="2 12 2 17 7 17"></polyline></svg>
          <span style="flex-grow: 1; text-align: left;">Show history</span>
          <span class="arrow-right-wrapper" @click.stop.prevent="toggleSubmenuMobile">
            <svg class="arrow-right" :class="{ 'rotated': isMobileSubmenuOpen }" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
          </span>
        </button>
        <div class="submenu-dropdown" :class="{ 'force-show': isMobileSubmenuOpen }">
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'last_hour')">Last hour</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'today')">Today</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'yesterday')">Yesterday</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'two_days_ago')">Two days ago</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'three_days_ago')">Three days ago</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'current_week')">Current week</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'last_week')">Last week</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'thisMonth')">Current month</button>
          <button class="menu-item" @click.stop="handleShowHistory(activeMenuObj, 'lastMonth')">Last month</button>
        </div>
      </div>

      <div class="menu-divider"></div>
      
      <button class="menu-item" @click.stop="handleFollow(activeMenuObj)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle></svg>
        Follow
      </button>
      
      <button class="menu-item" @click.stop="handleFollowNewWindow(activeMenuObj)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
        Follow (new window)
      </button>
      
      <button class="menu-item disabled" @click.stop="">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>                      
        Street View (new window)
      </button>
      
      <button class="menu-item" @click.stop="handleSharePosition(activeMenuObj)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
        Share position
      </button>

      <button class="menu-item" @click.stop="handleSendCommand(activeMenuObj)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        Send command
      </button>
      
      <button class="menu-item" @click.stop="handleEdit(activeMenuObj)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
        Edit
      </button>
      
      <button class="menu-item" @click.stop="handleDashboard(activeMenuObj)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
        Unit Dashboard
      </button>
    </div>
    
    <!-- Vehicle Quick Info Side Popup -->
    <div 
      v-if="hoveredObj" 
      class="v-quick-info-popup" 
      :style="quickPopupStyle"
      @mouseenter="clearHideTimeout"
      @mouseleave="handleMouseLeave"
    >
      <!-- Header: Premium Status Pills -->
      <div class="q-header">
        <div class="q-status-group">
          <span class="q-status-item safe"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> GPS: On</span>
          <span class="q-status-item safe"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> GSS: On</span>
          <span class="q-status-item" :class="hoveredObj.status?.toLowerCase()">{{ hoveredObj.status || 'Offline' }}</span>
        </div>
      </div>

      <div class="q-title-row">
        <span class="q-label">Today</span>
      </div>

      <!-- Activity Pie Chart Section -->
      <div class="q-chart-row">
        <div class="q-pie-container">
          <svg viewBox="0 0 36 36" class="q-pie-svg">
            <path class="pie-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
            <path class="pie-seg stopped" stroke-dasharray="45, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
            <path class="pie-seg moving" stroke-dasharray="35, 100" stroke-dashoffset="-45" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
            <path class="pie-seg idle" stroke-dasharray="20, 100" stroke-dashoffset="-80" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
          </svg>
          <div class="q-pie-center">
             <span class="val">20%</span>
             <span class="lab">IDL</span>
          </div>
        </div>
        <div class="q-chart-labels-col">
          <div class="q-chart-l-item stopped">45% <span>Stopped</span></div>
          <div class="q-chart-l-item moving">35% <span>Moving</span></div>
          <div class="q-chart-l-item idle">20% <span>IDL</span></div>
        </div>
      </div>

      <!-- Info List Section -->
      <div class="q-info-list">
        <div class="q-info-item">
          <div class="q-i-left"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Last Update</div>
          <div class="q-i-right">{{ formatDt(hoveredObj.dt_tracker) }}</div>
        </div>
        <div class="q-info-item">
          <div class="q-i-left"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Alerts Today</div>
          <div class="q-i-right">4 alerts</div>
        </div>
        <div class="q-info-item">
          <div class="q-i-left"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> Today Distance</div>
          <div class="q-i-right">{{ (jsonDecodeSafe(hoveredObj.params).total_distance || 0).toLocaleString() }} km</div>
        </div>
        <div class="q-info-item">
          <div class="q-i-left"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> Driver RFID</div>
          <div class="q-i-right">{{ jsonDecodeSafe(hoveredObj.params).driver_rfid || '---' }}</div>
        </div>
        <div class="q-info-item">
          <div class="q-i-left"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg> Speed</div>
          <div class="q-i-right">{{ hoveredObj.speed || 0 }} km/h</div>
        </div>
        <div class="q-info-item loc-full">
          <div class="q-i-left"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> Location</div>
          <div class="q-i-right address">{{ hoveredObj.address || '---' }}</div>
        </div>
      </div>

      <!-- Driver Card Footer -->
      <div class="q-driver-row" v-if="hoveredObj.driver_name">
        <div class="q-d-avatar">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div class="q-d-name">{{ hoveredObj.driver_name }}</div>
        <div class="q-d-actions">
          <button class="q-d-btn call"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></button>
          <button class="q-d-btn wa"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg></button>
        </div>
      </div>
    </div>
  </Teleport>

</template>


<script setup>
import { computed, ref, reactive, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';

// Custom directive to handle clicks outside the menu
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

const props = defineProps({
  isPanelOpen: Boolean,
  fleetData: Array,
  searchQuery: String,
  filter: String,
  filteredObjects: Array,
  selectedImei: String
});

const emit = defineEmits([
  'toggle-panel',
  'update:searchQuery',
  'update:filter',
  'select-vehicle',
  'open-history',
  'follow-vehicle',
  'follow-new-window',
  'edit-vehicle',
  'share-position',
  'send-command',
  'unit-dashboard'
]);

const router = useRouter();

const collapsedGroups = reactive({});
const activeMenu = ref(false);
const activeMenuObj = ref(null);
const menuStyle = ref({ top: '0px', left: '0px' });
const isMobileSubmenuOpen = ref(false);

const hoveredObj = ref(null);
const quickPopupStyle = ref({ top: '0px', opacity: 0, transform: 'translateX(-10px)' });
let hoverTimeout = null;

function handleMouseEnter(obj, event) {
  if (hoverTimeout) {
    clearTimeout(hoverTimeout);
    hoverTimeout = null;
  }
  hoveredObj.value = obj;
  
  const rect = event.currentTarget.getBoundingClientRect();
  const screenHeight = window.innerHeight;
  const popupHeight = 480; 
  
  let top = rect.top;
  if (top + popupHeight > screenHeight) {
    top = screenHeight - popupHeight - 20;
  }

  quickPopupStyle.value = {
    top: top + 'px',
    left: (rect.right + 15) + 'px',
    opacity: 1,
    transform: 'translateX(0)',
    pointerEvents: 'auto',
    zIndex: 999999
  };
}

function clearHideTimeout() {
  if (hoverTimeout) {
    clearTimeout(hoverTimeout);
    hoverTimeout = null;
  }
  // Restore full visibility if we caught it during fade
  quickPopupStyle.value.opacity = 1;
  quickPopupStyle.value.transform = 'translateX(0)';
}

function handleMouseLeave() {
  if (hoverTimeout) clearTimeout(hoverTimeout);
  
  hoverTimeout = setTimeout(() => {
    quickPopupStyle.value.opacity = 0;
    quickPopupStyle.value.transform = 'translateX(-10px)';
    
    // Final cleanup after fade transition
    hoverTimeout = setTimeout(() => {
      if (quickPopupStyle.value.opacity === 0) {
        hoveredObj.value = null;
      }
    }, 200);
  }, 200); // 200ms grace period to cross the gap
}

function toggleSubmenuMobile() {
  isMobileSubmenuOpen.value = !isMobileSubmenuOpen.value;
}

function toggleMenu(obj, event) {
  if (activeMenuObj.value && activeMenuObj.value.imei === obj.imei && activeMenu.value) {
    closeMenu();
  } else {
    activeMenuObj.value = obj;
    activeMenu.value = true;

    // Calculate position
    const rect = event.currentTarget.getBoundingClientRect();
    const popupHeight = 350; // estimate
    const popupWidth = 170;

    let targetTop = rect.bottom + 5;
    let targetLeft = rect.right - popupWidth; // Align to right

    // Prevent overflow below screen
    if (targetTop + popupHeight > window.innerHeight) {
      targetTop = Math.max(10, window.innerHeight - popupHeight - 10);
    }
    
    // Set style safely
    menuStyle.value = {
      position: 'fixed',
      top: targetTop + 'px',
      left: Math.max(10, targetLeft) + 'px'
    };
  }
}

function closeMenu() {
  activeMenu.value = false;
  isMobileSubmenuOpen.value = false;
  setTimeout(() => {
    if (!activeMenu.value) activeMenuObj.value = null;
  }, 200);
}

function handleShowHistory(obj, period = 'today') {
  emit('open-history', { obj, period });
  closeMenu();
}

function handleShowHistoryDefault(obj) {
  emit('open-history', { obj, period: 'today', noFetch: true });
  closeMenu();
}

function handleFollow(obj) {
  emit('follow-vehicle', obj);
  closeMenu();
}

function handleFollowNewWindow(obj) {
  emit('follow-new-window', obj);
  closeMenu();
}

function handleEdit(obj) {
  emit('edit-vehicle', obj);
  closeMenu();
}

function handleSharePosition(obj) {
  emit('share-position', obj);
  closeMenu();
}

function handleSendCommand(obj) {
  emit('send-command', obj);
  closeMenu();
}

const groupedObjects = computed(() => {
  const groups = {};
  props.filteredObjects.forEach(obj => {
    const groupName = obj.group_name || 'Ungrouped';
    if (!groups[groupName]) {
      groups[groupName] = [];
    }
    groups[groupName].push(obj);
  });
  return groups;
});

function toggleGroup(groupName) {
  collapsedGroups[groupName] = !collapsedGroups[groupName];
}

function formatSpeed(speed) {
  return Math.round(speed || 0);
}

function handleDashboard(obj) {
  if (!obj) return;
  const params = jsonDecodeSafe(obj.params) || {};
  const ignition = (params.acc == 1) ? 'ON' : 'OFF';
  router.push({
    path: `/dashboard/${obj.imei}`
  });
  closeMenu();
}

function jsonDecodeSafe(json) {
  if (!json) return {};
  try {
    return typeof json === 'string' ? JSON.parse(json) : json;
  } catch {
    return {};
  }
}

function formatDt(dt) {
  if (!dt) return '---';
  try {
    const d = new Date(dt);
    const date = d.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
    const time = d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    return `${date} ${time}`;
  } catch {
    return dt;
  }
}

function getSignalClass(dt_tracker) {
  if (!dt_tracker) return 'no-signal';
  const diff = (Date.now() - new Date(dt_tracker).getTime()) / 1000;
  if (diff < 60) return 'signal-strong';       // < 1 min
  if (diff < 300) return 'signal-medium';      // < 5 min
  if (diff < 3600) return 'signal-weak';       // < 1 hour
  return 'no-signal';
}
</script>


<style scoped>
.tab-content-wrapper {
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow: hidden;
}

.panel-header-modern {
  padding: 12px 16px;
  background: var(--card);
}

.summary-actions-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 10px;
}

.summary-row {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.summary-label {
  font-size: 16px;
  font-weight: 800;
  color: var(--accent);
}

body.light-mode .summary-label {
  color: #1e3a8a; /* Deep Navy */
}

.summary-value {
  font-size: 14px;
  font-weight: 600;
  color: var(--muted);
}

.actions-search-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 4px;
  margin-bottom: 12px;
}

.selection-box-wrapper {
  flex-shrink: 0;
}

.selection-box {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  color: #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.search-input-container {
  flex: 1;
  position: relative;
}

.search-input-container .search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

.search-input-container input {
  width: 100%;
  padding: 10px 12px 10px 38px;
  border-radius: 12px;
  border: 1px solid var(--border);
  background: var(--input-bg);
  font-size: 14px;
  color: var(--text);
  outline: none;
  transition: all 0.2s;
}

.search-input-container input:focus {
  background: rgba(255, 255, 255, 0.05);
  border-color: var(--accent);
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

body.light-mode .search-input-container input {
  background: #f1f5f9;
  color: #1e293b;
}

body.light-mode .search-input-container input:focus {
  background: white;
}

.header-tools {
  display: flex;
  gap: 8px;
}

.tool-btn {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: var(--input-bg);
  color: var(--muted);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.tool-btn:hover {
  background: var(--accent);
  color: white;
  border-color: var(--accent);
}

body.light-mode .tool-btn {
  background: #f1f5f9;
  color: #64748b;
}

body.light-mode .tool-btn:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.status-pills-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 4px;
  margin-bottom: 4px;
}

.status-pill-item {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: var(--input-bg);
  cursor: pointer;
  transition: all 0.25s;
  white-space: nowrap;
  justify-content: center;
}

.status-pill-item.active {
  border-color: var(--accent);
  background: rgba(59, 130, 246, 0.1);
  padding: 8px 14px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

body.light-mode .status-pill-item {
  border-color: #f1f5f9;
  background: #f8fafc;
}

body.light-mode .status-pill-item.active {
  background: #ffffff;
  border-color: #3b82f6;
}

.dot-wrapper {
  width: 14px;
  height: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.pill-label {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
}

/* Active & Type specific styles */
.status-pill-item.all .dot { background: #0055ff; }
.status-pill-item.all.active { border-color: #0055ff; }
.status-pill-item.all.active .pill-label { color: #0055ff; }

.status-pill-item.moving .dot { background: #10b981; }
.status-pill-item.moving.active { border-color: #10b981; }
.status-pill-item.moving.active .pill-label { color: #10b981; }

.status-pill-item.idle .dot { background: #fbbf24; }
.status-pill-item.idle.active { border-color: #fbbf24; }
.status-pill-item.idle.active .pill-label { color: #fbbf24; }

.status-pill-item.stopped .dot { background: #ef4444; }
.status-pill-item.stopped.active { border-color: #ef4444; }
.status-pill-item.stopped.active .pill-label { color: #ef4444; }

.status-pill-item.offline .dot { background: #64748b; }
.status-pill-item.offline.active { border-color: #64748b; }
.status-pill-item.offline.active .pill-label { color: #64748b; }

.panel-header h2 {
  margin: 0;
  font-size: 17px;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.search-box input {
  width: 100%;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--input-bg);
  color: var(--text);
  padding: 9px 14px;
  font-size: 13px;
  outline: none;
  transition: border-color 0.3s;
  box-sizing: border-box;
}
.search-box input:focus { border-color: var(--accent); }

/* Status Tabs */
.status-tabs {
  display: flex;
  margin-top: 12px;
  background: var(--input-bg);
  border-radius: 8px;
  border: 1px solid var(--border);
  padding: 3px;
  gap: 2px;
}
.status-tab {
  flex: 1;
  background: transparent;
  border: none;
  color: var(--muted);
  font-size: 11px;
  font-weight: 600;
  padding: 5px 0;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}
.status-tab.active {
  background: var(--card);
  color: var(--text);
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.status-tab.moving.active { color: var(--status-moving); }
.status-tab.idle.active   { color: var(--status-idle); }
.status-tab.offline.active{ color: var(--status-offline); }

/* Objects List */
.objects-list {
  flex: 1;
  overflow-y: auto;
  padding: 8px;
}
.objects-list::-webkit-scrollbar { width: 4px; }
.objects-list::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.08);
  border-radius: 2px;
}

/* Grouping */
.object-group {
  margin-bottom: 12px;
}

.group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  cursor: pointer;
  border-radius: 12px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none;
  margin-bottom: 4px;
}

.group-header:hover {
  background: var(--input-bg);
}

.group-header-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.group-chevron {
  color: var(--muted);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  transform: rotate(90deg); /* Default open */
}

.group-header.is-collapsed .group-chevron {
  transform: rotate(0deg);
}

.group-name {
  font-size: 15px;
  font-weight: 700;
  color: var(--accent);
}

body.light-mode .group-name {
  color: #475569; /* Slate Gray - much softer */
}

.group-count {
  font-size: 14px;
  color: var(--muted);
  font-weight: 600;
}
.group-content { padding-left: 4px; }

/* Vehicle Card — Modern Expanded Layout */
.vehicle-card {
  display: flex;
  flex-direction: column;
  padding: 12px;
  border-radius: 12px;
  margin-bottom: 8px;
  cursor: pointer;
  background: var(--card);
  border: 1px solid var(--border);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  gap: 0;
}

.v-card-main {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
}

.v-card-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-top: 0;
  max-height: 0;
  opacity: 0;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.vehicle-card:hover, .vehicle-card.selected {
  box-shadow: 0 10px 25px rgba(0,0,0,0.05);
}

.vehicle-card:hover .v-card-actions, 
.vehicle-card.selected .v-card-actions {
  margin-top: 14px;
  max-height: 50px;
  opacity: 1;
}

/* Status-based Tints (Hover/Selected Only) */
.vehicle-card.status-moving:hover, .vehicle-card.status-moving.selected {
  background: var(--status-moving-bg);
  border-color: var(--status-moving);
  box-shadow: 0 0 20px rgba(16, 185, 129, 0.1);
}
.vehicle-card.status-idle:hover, .vehicle-card.status-idle.selected {
  background: var(--status-idle-bg);
  border-color: var(--status-idle);
  box-shadow: 0 0 20px rgba(251, 191, 36, 0.1);
}
.vehicle-card.status-stopped:hover, .vehicle-card.status-stopped.selected,
.vehicle-card.status-parking:hover, .vehicle-card.status-parking.selected,
.vehicle-card.status-offline:hover, .vehicle-card.status-offline.selected {
  background: var(--status-offline-bg);
  border-color: var(--status-offline);
  box-shadow: 0 0 20px rgba(244, 63, 94, 0.1);
}

.v-checkbox-visual {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--muted);
  flex-shrink: 0;
  opacity: 0.5;
}

.vehicle-card:hover .v-checkbox-visual,
.vehicle-card.selected .v-checkbox-visual {
  color: var(--text);
  opacity: 1;
}


/* Car Icon */
.v-icon {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: color 0.3s, background 0.3s;
}
.v-icon.moving  { color: var(--status-moving); background: var(--status-moving-bg); opacity: 0.9; }
.v-icon.idle    { color: var(--status-idle); background: var(--status-idle-bg); opacity: 0.9; }
.v-icon.offline { color: var(--status-offline); background: var(--status-offline-bg); opacity: 0.9; }

/* Info block */
.v-info {
  flex: 1;
  min-width: 0;
}
.v-name {
  font-size: 12.5px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--text);
}
.v-datetime {
  font-size: 10.5px;
  color: var(--muted);
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.v-top-indicators {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
  margin-left: auto;
}

.v-speed {
  font-size: 14px;
  font-weight: 800;
  color: var(--text);
  white-space: nowrap;
}
.v-speed .km {
  font-size: 10px;
  font-weight: 400;
  color: var(--muted);
}
.v-sensors {
  display: flex;
  align-items: center;
  gap: 5px;
}

/* Sensor Icons */
.sensor-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--muted);
  opacity: 0.4;
  transition: color 0.2s;
}
.sensor-icon.on { color: #ffcc00; }  /* ignition on = yellow */

/* Signal strength colors */
.sensor-icon.signal.signal-strong { color: var(--status-moving); }
.sensor-icon.signal.signal-medium { color: var(--accent); }
.sensor-icon.signal.signal-weak   { color: var(--status-idle); }
.sensor-icon.signal.no-signal     { color: var(--muted); opacity: 0.2; }

/* History quick action button & Menu */
.menu-wrapper {
  position: relative;
}

.action-square-btn {
  flex: 1;
  height: 36px;
  background: var(--input-bg);
  border: 1px solid var(--border);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--muted);
  transition: all 0.2s;
}

.action-square-btn:hover {
  background: var(--accent);
  color: #ffffff;
  border-color: var(--accent);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(79, 124, 255, 0.2);
}

.vehicle-menu-dropdown.global {
  position: fixed;
  background: var(--card);
  backdrop-filter: blur(12px);
  border: 1px solid var(--border);
  border-radius: 10px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.25);
  z-index: 10000; /* Ensure high priority over sidebar */
  min-width: 170px;
  padding: 6px;
  display: flex;
  flex-direction: column;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: transparent;
  border: none;
  width: 100%;
  color: var(--text, white);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  border-radius: 6px;
  transition: all 0.2s;
  text-align: left;
}

.menu-item svg {
  color: var(--muted, #94a3b8);
  transition: color 0.2s;
}

.menu-item:hover {
  background: var(--border);
}

.menu-item:hover svg {
  color: var(--text, white);
}

.menu-item.highlight {
  color: var(--accent, #4f7cff);
  font-weight: 600;
}
.menu-item.highlight svg:not(.arrow-right) {
  color: var(--accent, #4f7cff);
}

.arrow-right-wrapper {
  margin-left: auto;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 5px;
  margin-right: -5px;
}

.arrow-right-wrapper .arrow-right {
  opacity: 0.6;
  margin-left: 0;
  transition: transform 0.2s;
}

.arrow-right-wrapper .arrow-right.rotated {
  transform: rotate(90deg);
}

.menu-item.disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.menu-divider {
  height: 1px;
  background: var(--border);
  margin: 4px 0;
}

/* Submenu specifics */
.menu-item-wrapper.has-submenu {
  position: relative;
}

.submenu-dropdown {
  position: absolute;
  top: 0;
  left: 100%;
  margin-left: 5px;
  background: var(--card);
  backdrop-filter: blur(12px);
  border: 1px solid var(--border);
  border-radius: 10px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.25);
  z-index: 10000;
  min-width: 150px;
  padding: 6px;
  display: flex;
  flex-direction: column;
  opacity: 0;
  visibility: hidden;
  transform: translateX(-10px);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.menu-item-wrapper.has-submenu:hover .submenu-dropdown,
.submenu-dropdown.force-show {
  opacity: 1;
  visibility: visible;
  transform: translateX(0);
}

.submenu-dropdown .menu-item {
  font-weight: 400;
}
.submenu-dropdown .menu-item:hover {
  font-weight: 600;
  color: var(--accent, #4f7cff);
}

/* Vehicle Quick Info Popup Modernized */
.v-quick-info-popup {
  position: fixed;
  width: 320px;
  background: var(--glass);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  border-radius: 24px;
  padding: 20px;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
  z-index: 1000000;
  display: flex;
  flex-direction: column;
  gap: 16px;
  border: 1px solid var(--glass-border);
  font-family: 'Inter', sans-serif;
}

body.light-mode .v-quick-info-popup {
  background: rgba(255,255,255,0.95);
  border-color: rgba(0,0,0,0.05);
  box-shadow: 0 15px 45px rgba(0, 0, 0, 0.1);
}

.q-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.q-status-group {
  display: flex;
  align-items: center;
  gap: 10px;
}

.q-status-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--muted);
}

.q-status-item.safe { color: var(--status-moving); }
.q-status-item.moving { color: var(--status-moving); }
.q-status-item.idle { color: var(--status-idle); }
.q-status-item.stopped { color: var(--status-offline); }

.q-title-row { margin-top: -4px; }
.q-label { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }

/* Pie Chart Layout */
.q-chart-row {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 4px 0;
}

.q-pie-container {
  width: 90px;
  height: 90px;
  position: relative;
}

.q-pie-svg { width: 100%; height: 100%; transform: rotate(-90deg); }
.pie-bg { fill: none; stroke: var(--border); stroke-width: 3.5; }
.pie-seg { fill: none; stroke-width: 3.5; stroke-linecap: round; }
.pie-seg.stopped { stroke: var(--status-offline); }
.pie-seg.moving { stroke: var(--status-moving); }
.pie-seg.idle { stroke: var(--status-idle); }

.q-pie-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  line-height: 1;
}
.q-pie-center .val { font-size: 14px; font-weight: 800; color: var(--text); }
.q-pie-center .lab { font-size: 9px; font-weight: 700; color: var(--muted); margin-top: 2px; }

.q-chart-labels-col {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.q-chart-l-item {
  display: flex;
  align-items: baseline;
  gap: 6px;
  font-size: 14px;
  font-weight: 800;
}
.q-chart-l-item span { font-size: 11px; font-weight: 600; color: var(--muted); }
.q-chart-l-item.moving { color: var(--status-moving); }
.q-chart-l-item.stopped { color: var(--status-offline); }
.q-chart-l-item.idle { color: var(--status-idle); }

/* Info List */
.q-info-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  border-top: 1px solid var(--border);
  padding-top: 16px;
}

.q-info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.q-i-left {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: var(--muted);
  font-weight: 500;
}

.q-i-right {
  font-size: 12px;
  font-weight: 700;
  color: var(--text);
}

.q-info-item.loc-full {
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
  margin-top: 4px;
}

.q-i-right.address { color: var(--muted); font-weight: 500; }

/* Driver Card */
.q-driver-row {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--input-bg);
  padding: 10px 14px;
  border-radius: 16px;
  margin-top: 4px;
}

.q-d-avatar {
  width: 32px;
  height: 32px;
  background: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.q-d-name {
  flex: 1;
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
}

.q-d-actions {
  display: flex;
  gap: 6px;
}

.q-d-btn {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  cursor: pointer;
  transition: transform 0.2s;
}

.q-d-btn:hover { transform: scale(1.1); }
.q-d-btn.call { background: #fbbf24; }
.q-d-btn.wa { background: #10b981; }

@media (max-width: 1100px) {

  .v-quick-info-popup { display: none !important; }
}

.panel-header-modern {
  padding: 16px;
  background: var(--card);
  border-bottom: 1px solid var(--border);
}

.header-top-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.fleet-title-meta {
  display: flex;
  align-items: flex-end;
  gap: 8px;
}

.fleet-title-meta .label {
  font-size: 18px;
  font-weight: 800;
  color: var(--text);
  line-height: 1;
}

.fleet-title-meta .count-badge {
  font-size: 12px;
  font-weight: 600;
  color: var(--muted);
  background: var(--border);
  padding: 2px 8px;
  border-radius: 8px;
}

.collapse-pill-btn {
  background: var(--border);
  border: none;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--muted);
  transition: all 0.2s;
}

.collapse-pill-btn:hover {
  background: var(--accent);
  color: white;
}

.search-pill-wrapper {
  position: relative;
  width: 100%;
}

.search-pill-wrapper svg {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

.search-pill-wrapper input {
  width: 100%;
  padding: 10px 16px 10px 40px;
  background: var(--input-bg);
  border: 1.5px solid transparent;
  border-radius: 12px;
  color: var(--text);
  font-size: 14px;
  outline: none;
  transition: all 0.2s;
}

.search-pill-wrapper input:focus {
  background: white;
  border-color: var(--accent);
  box-shadow: 0 0 0 4px rgba(79, 124, 255, 0.1);
}


</style>
