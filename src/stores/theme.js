import { defineStore } from 'pinia';
import { ref, watch } from 'vue';

export const useThemeStore = defineStore('theme', () => {
  const isDarkMode = ref(localStorage.getItem('theme') !== 'light');

  const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value;
    updateTheme();
  };

  const updateTheme = () => {
    const theme = isDarkMode.value ? 'dark' : 'light';
    localStorage.setItem('theme', theme);
    if (isDarkMode.value) {
      document.body.classList.remove('light-mode');
    } else {
      document.body.classList.add('light-mode');
    }
  };

  // Initialize theme
  const init = () => {
     updateTheme();
  };

  return { isDarkMode, toggleTheme, init };
});
