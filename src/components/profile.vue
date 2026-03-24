<template>
  <div class="page">
    <header class="topbar">
      <div class="topbar-left">
        <img class="top-logo" src="https://gps.fleetrite.io/img/logo.png" alt="Fleetrite" />
        <div>
          <div class="title">My Profile</div>
          <div class="subtitle">Account overview</div>
        </div>
      </div>

      <div class="topbar-actions">
        <button class="btn-outline" @click="$router.push('/tracking')">
          <svg style="margin-right:6px; vertical-align:middle;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"></path><circle cx="12" cy="9" r="2.5"></circle></svg>
          Tracking
        </button>
        <button class="btn-outline" @click="refresh" :disabled="loading">
          <span v-if="loading">Refreshing...</span>
          <span v-else>Refresh</span>
        </button>

        <button class="btn-outline theme-toggle-btn" :title="themeStore.isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'" @click="themeStore.toggleTheme">
          <svg v-if="themeStore.isDarkMode" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <circle cx="12" cy="12" r="5"></circle>
             <line x1="12" y1="1" x2="12" y2="3"></line>
             <line x1="12" y1="21" x2="12" y2="23"></line>
             <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
             <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
             <line x1="1" y1="12" x2="3" y2="12"></line>
             <line x1="21" y1="12" x2="23" y2="12"></line>
             <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
             <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
          </svg>
          <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
          </svg>
        </button>

        <button class="btn-danger" @click="onLogout">Logout</button>
      </div>
    </header>

    <main class="content">
      <div class="grid2">
        <!-- Left: Summary Card -->
        <section class="card">
          <div class="card-head">
            <div class="avatar">{{ initials }}</div>
            <div>
              <div class="name">{{ user?.username || "-" }}</div>
              <div class="muted">{{ user?.email || "-" }}</div>
              <div class="pill" :class="user?.active === 'true' ? 'pill-ok' : 'pill-bad'">
                {{ user?.active === 'true' ? 'Active' : 'Inactive' }}
              </div>
            </div>
          </div>

          <div class="divider"></div>

          <div class="kv">
            <div class="k">User ID</div><div class="v">{{ user?.id ?? "-" }}</div>
            <div class="k">Type</div><div class="v">{{ privilegesObj?.type || "-" }}</div>
            <div class="k">Currency</div><div class="v">{{ user?.currency || "-" }}</div>
            <div class="k">Language</div><div class="v">{{ user?.language || "-" }}</div>
            <div class="k">Units</div><div class="v">{{ user?.units || "-" }}</div>
            <div class="k">Timezone</div><div class="v">{{ user?.timezone || "-" }}</div>
            <div class="k">Registered</div><div class="v">{{ user?.dt_reg || "-" }}</div>
            <div class="k">Last Login</div><div class="v">{{ user?.dt_login || "-" }}</div>
            <div class="k">IP</div><div class="v">{{ user?.ip || "-" }}</div>
          </div>

          <p v-if="err" class="error" style="margin-top:12px">{{ err }}</p>
        </section>

        <!-- Right: Tabs -->
        <section class="card">
          <div class="tabs">
            <button class="tab" :class="{ active: tab==='account' }" @click="tab='account'">Account</button>
            <button class="tab" :class="{ active: tab==='settings' }" @click="tab='settings'">Settings</button>
            <button class="tab" :class="{ active: tab==='privileges' }" @click="tab='privileges'">Privileges</button>
            <button class="tab" :class="{ active: tab==='raw' }" @click="tab='raw'">Raw</button>
          </div>

          <div class="tab-body" v-if="tab==='account'">
            <div class="section-title">Account</div>
            <div class="grid3">
              <InfoCard label="API Access" :value="user?.api" />
              <InfoCard label="Account Expire" :value="user?.account_expire" />
              <InfoCard label="Expire Date" :value="user?.account_expire_dt" />
              <InfoCard label="Obj Add" :value="user?.obj_add" />
              <InfoCard label="Obj Limit" :value="user?.obj_limit" />
              <InfoCard label="Obj Limit Num" :value="user?.obj_limit_num" />
            </div>

            <div class="divider"></div>

            <div class="section-title">White Label</div>
            <div class="grid3">
              <InfoCard label="White Label ID" :value="user?.white_label_id" />
              <InfoCard label="Enabled" :value="whiteLabelObj?.status" />
              <InfoCard label="Custom URL" :value="whiteLabelObj?.url_custom" />
              <InfoCard label="Title" :value="whiteLabelObj?.title" />
            </div>
          </div>

          <div class="tab-body" v-else-if="tab==='settings'">
            <div class="section-title">Profile Info</div>
            <div class="grid3">
              <InfoCard label="Name" :value="infoObj?.name" />
              <InfoCard label="Company" :value="infoObj?.company" />
              <InfoCard label="City" :value="infoObj?.city" />
              <InfoCard label="Country" :value="infoObj?.country" />
              <InfoCard label="Phone 1" :value="infoObj?.phone1" />
              <InfoCard label="Phone 2" :value="infoObj?.phone2" />
            </div>

            <div class="divider"></div>

            <div class="section-title">Notifications</div>
            <div class="grid3">
              <InfoCard label="Notify Account Expire" :value="user?.notify_account_expire" />
              <InfoCard label="Notify Object Expire" :value="user?.notify_object_expire" />
              <InfoCard label="Push Desktop" :value="user?.push_notify_desktop" />
              <InfoCard label="Push Mobile" :value="user?.push_notify_mobile" />
            </div>
          </div>

          <div class="tab-body" v-else-if="tab==='privileges'">
            <div class="section-title">Privileges</div>

            <div class="chips">
              <span class="chip" v-for="(val,key) in privilegesFlags" :key="key" :class="val ? 'chip-on':'chip-off'">
                {{ key }}
              </span>
            </div>

            <div class="hint">
              * دي مبنية على `privileges` JSON اللي جاي من السيرفر.
            </div>
          </div>

          <div class="tab-body" v-else>
            <div class="section-title">Raw Response</div>
            <pre class="code">{{ pretty }}</pre>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { useThemeStore } from "../stores/theme";

const router = useRouter();
const auth = useAuthStore();
const themeStore = useThemeStore();

const loading = ref(false);
const err = ref("");
const tab = ref("account");

const user = computed(() => auth.user);

// safe parse JSON strings
function safeJsonParse(value) {
  try {
    if (!value) return null;
    if (typeof value === "object") return value;
    return JSON.parse(value);
  } catch {
    return null;
  }
}

const privilegesObj = computed(() => safeJsonParse(user.value?.privileges) || {});
const infoObj = computed(() => safeJsonParse(user.value?.info) || {});
const whiteLabelObj = computed(() => safeJsonParse(user.value?.white_label_config) || {});

const privilegesFlags = computed(() => {
  const obj = privilegesObj.value || {};
  const out = {};
  for (const [k, v] of Object.entries(obj)) {
    if (k === "type") continue;
    out[k] = v === true || v === "true" || v === 1 || v === "1";
  }
  return out;
});

const initials = computed(() => {
  const u = user.value?.username || "U";
  return u.slice(0, 2).toUpperCase();
});

const pretty = computed(() => JSON.stringify({ ok: true, user: user.value }, null, 2));

async function refresh() {
  err.value = "";
  loading.value = true;
  try {
    await auth.fetchMe();
  } catch (e) {
    err.value = e?.response?.data?.message || "Failed to load profile data.";
  } finally {
    loading.value = false;
  }
}

function onLogout() {
  auth.logout();
  router.push("/login");
}

onMounted(async () => {
  if (!auth.user) await refresh();
});

// small local component
const InfoCard = {
  props: { label: String, value: [String, Number, Boolean, Object, null] },
  template: `
    <div class="info">
      <div class="info-label">{{label}}</div>
      <div class="info-value">{{ format(value) }}</div>
    </div>
  `,
  methods: {
    format(v) {
      if (v === null || v === undefined || v === "") return "-";
      if (typeof v === "boolean") return v ? "true" : "false";
      return String(v);
    },
  },
};
</script>