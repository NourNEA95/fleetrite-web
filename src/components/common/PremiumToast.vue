<template>
  <Transition name="toast">
    <div v-if="show" class="premium-toast" :class="type">
      <div class="toast-icon">
        <svg v-if="type === 'success'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <svg v-else-if="type === 'error'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="15" y1="9" x2="9" y2="15"></line>
          <line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>
        <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
      </div>
      <div class="toast-content">
        <div class="toast-message">{{ message }}</div>
      </div>
      <button class="toast-close" @click="$emit('close')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
  </Transition>
</template>

<script setup>
import { onMounted } from 'vue';

const props = defineProps({
  show: Boolean,
  message: String,
  type: {
    type: String,
    default: 'success' // success, error, info
  },
  duration: {
    type: Number,
    default: 4000
  }
});

const emit = defineEmits(['close']);

onMounted(() => {
  if (props.show) {
    setTimeout(() => {
      emit('close');
    }, props.duration);
  }
});
</script>

<style scoped>
.premium-toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  min-width: 300px;
  max-width: 450px;
  background: rgba(15, 23, 42, 0.9);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
  z-index: 9999;
}

.toast-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.success .toast-icon { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
.error .toast-icon { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.info .toast-icon { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }

.success { border-left: 4px solid #22c55e; }
.error { border-left: 4px solid #ef4444; }
.info { border-left: 4px solid #3b82f6; }

.toast-content {
  flex: 1;
}

.toast-message {
  color: #fff;
  font-size: 14px;
  font-weight: 600;
}

.toast-close {
  background: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all 0.2s;
}

.toast-close:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
}

/* Animations */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100px);
}

.toast-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
</style>
