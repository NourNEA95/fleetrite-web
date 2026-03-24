<template>
  <Transition name="fade">
    <div v-if="show" class="confirm-backdrop" @click.self="$emit('cancel')">
      <Transition name="scale">
        <div v-if="show" class="confirm-container glass-effect">
          <div class="confirm-icon" :class="type">
            <svg v-if="type === 'warning'" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
              <line x1="12" y1="9" x2="12" y2="13"></line>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <svg v-else-if="type === 'danger'" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="15" y1="9" x2="9" y2="15"></line>
              <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
             <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
          </div>
          <div class="confirm-content">
            <h3 class="confirm-title">{{ title }}</h3>
            <p class="confirm-message">{{ message }}</p>
          </div>
          <div class="confirm-actions">
            <button class="conf-btn cancel" @click="$emit('cancel')">Cancel</button>
            <button class="conf-btn confirm" :class="type" @click="$emit('confirm')">{{ confirmText }}</button>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<script setup>
defineProps({
  show: Boolean,
  title: { type: String, default: 'Confirmation' },
  message: String,
  confirmText: { type: String, default: 'Confirm' },
  type: { type: String, default: 'warning' } // warning, danger, info
});

defineEmits(['confirm', 'cancel']);
</script>

<style scoped>
.confirm-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 99999;
}

.confirm-container {
  width: 100%;
  max-width: 400px;
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  padding: 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.confirm-icon {
  width: 54px;
  height: 54px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}

.warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
.info { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }

.confirm-title {
  color: #fff;
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 8px;
}

.confirm-message {
  color: #94a3b8;
  font-size: 14px;
  line-height: 1.6;
  margin-bottom: 24px;
}

.confirm-actions {
  display: flex;
  gap: 12px;
  width: 100%;
}

.conf-btn {
  flex: 1;
  padding: 12px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.cancel {
  background: rgba(255, 255, 255, 0.05);
  color: #94a3b8;
}

.cancel:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }

.confirm.warning { background: #f59e0b; color: #000; }
.confirm.danger { background: #ef4444; color: #fff; }
.confirm.info { background: #3b82f6; color: #fff; }

.confirm:hover { transform: translateY(-2px); filter: brightness(1.1); }

/* Transitions */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.scale-enter-active, .scale-leave-active { transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); }
.scale-enter-from, .scale-leave-to { transform: scale(0.9); opacity: 0; }
</style>
