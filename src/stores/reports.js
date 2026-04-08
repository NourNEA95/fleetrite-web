import { defineStore } from 'pinia';

export const useReportStore = defineStore('reports', {
  state: () => ({
    showMainModal: false,
    showPropertiesModal: false,
    editingReport: null,
    form: {
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
    }
  }),
  actions: {
    openMainModal() {
      this.showMainModal = true;
    },
    closeMainModal() {
      this.showMainModal = false;
    },
    openProperties(editData = null) {
      this.editingReport = editData;
      this.showPropertiesModal = true;
    },
    closeProperties() {
      this.showPropertiesModal = false;
      this.editingReport = null;
    },
    updateForm(newFields) {
      this.form = { ...this.form, ...newFields };
    },
    resetForm() {
      this.form = {
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
      };
    }
  }
});
