<template>
  <div class="login-wrapper" :class="{ rtl: currentLang === 'ar' }">
    <!-- Left Side: Form (33% Width) -->
    <div class="login-left">
      <div class="brand-logo-final">
        <!-- Item 3: Larger, white logo -->
        <img src="../assets/login-logo-final.png" alt="FleetRite Logo" />
      </div>

      <div class="login-card">
        <!-- Item 4: Balanced form block -->
        <form @submit.prevent="onSubmit">
          <div class="form-group">
            <label>{{ t('username') }}</label>
            <input 
              v-model.trim="username" 
              type="text" 
              autocomplete="username" 
              placeholder="User Name"
              required
            />
          </div>

          <div class="form-group">
            <label>{{ t('password') }}</label>
            <input 
              v-model="password" 
              type="password" 
              autocomplete="current-password" 
              placeholder="******" 
              required
            />
          </div>

          <!-- Item 7: Checkbox Row -->
          <label class="checkbox-row">
            <input type="checkbox" v-model="rememberMe" />
            <span>{{ t('rememberMe') }}</span>
          </label>

          <!-- Item 8: Primary Sign In Button -->
          <button class="btn-signin" type="submit" :disabled="auth.loading">
            <span v-if="auth.loading">{{ t('signingIn') }}</span>
            <span v-else>{{ t('signIn') }}</span>
          </button>

          <p v-if="error" style="color: #ff6b6b; font-size: 13px; margin-top: 15px; text-align: center;">{{ error }}</p>

          <!-- Item 9: Or Divider -->
          <div class="login-divider">{{ t('or') }}</div>

          <!-- Item 10: Secondary Buttons -->
          <div class="extra-buttons">
            <button type="button" class="btn-extra">{{ t('signUp') }}</button>
            <button type="button" class="btn-extra">{{ t('recovery') }}</button>
          </div>
        </form>
      </div>

      <div class="sidebar-footer">
        <!-- Item 11: App Store Buttons (Left) -->
        <div class="store-buttons">
           <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" class="store-btn" alt="Google Play" />
           <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" class="store-btn" alt="App Store" />
        </div>

        <!-- Item 12: Language Switcher (Right) -->
        <div class="lang-toggle" @click="toggleLang">
          <span>{{ currentLang === 'en' ? 'اللغة العربية' : 'English' }}</span>
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24">
            <path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
          </svg>
        </div>
      </div>
    </div>

    <!-- Right Side: Hero Section -->
    <div class="login-right">
      <!-- Item 18: Floating Glass Square -->
      <div class="hero-glass-square"></div>

      <div class="slider-container" v-if="slides.length > 0">
        <!-- Item 15: Repositioned Navigation Arrows (Relative to container) -->
        <div class="slider-nav slider-prev" @click="prevSlide">
           <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><path d="M15 19l-7-7 7-7"/></svg>
        </div>

        <div class="slide-content" :key="currentSlideIndex">
          <!-- Item 14: Hero Branding & Compact Text -->
          <div class="hero-branding">KOURIER</div>
          <p class="slide-description">{{ slides[currentSlideIndex].description }}</p>
          <!-- Item 16: Read More Button -->
          <button class="btn-readmore">{{ t('readMore') }}</button>
        </div>

        <div class="slider-nav slider-next" @click="nextSlide">
           <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><path d="M9 5l7 7-7 7"/></svg>
        </div>
      </div>

      <!-- Item 17: Bottom-Right FleetRite Branding -->
      <div class="bottom-brand">
        <div class="brand-row">
            <svg viewBox="0 0 84 84" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M25 59L59 25M59 25V50M59 25H34" stroke="white" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>FleetRite</span>
        </div>
        <div class="brand-slogan">See Every Move. Control Every Mile</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import api from "../services/api";
import "../assets/login.css";

const router = useRouter();
const auth = useAuthStore();

const username = ref("");
const password = ref("");
const rememberMe = ref(false);
const error = ref("");

// Localization
const currentLang = ref(localStorage.getItem("lang") || "en");
const translations = {
  en: {
    username: "User Name",
    password: "Password",
    rememberMe: "Remember Me",
    signIn: "Sign In",
    signingIn: "Signing In...",
    or: "Or",
    signUp: "Sign Up",
    recovery: "Recovery",
    readMore: "Read More"
  },
  ar: {
    username: "اسم المستخدم",
    password: "كلمة المرور",
    rememberMe: "تذكرني",
    signIn: "دخول",
    signingIn: "جاري الدخول...",
    or: "أو",
    signUp: "تسجيل جديد",
    recovery: "استعادة الحساب",
    readMore: "اقرأ المزيد"
  }
};

function t(key) {
  return translations[currentLang.value][key] || key;
}

function toggleLang() {
  currentLang.value = currentLang.value === "en" ? "ar" : "en";
  localStorage.setItem("lang", currentLang.value);
}

// Slider Logic
const slides = ref([]);
const currentSlideIndex = ref(0);
let slideInterval = null;

async function fetchSlides() {
  try {
    const res = await api.get("/api/login/slider");
    if (res.data?.ok) {
      slides.value = res.data.slides;
    }
  } catch (err) {
    console.error("Failed to fetch slides", err);
    slides.value = [{
      title: "KOURIER",
      description: "Automate Mail & Parcels delivery operations via our innovative post management solution which provides real-time tracking, automated dispatching"
    }];
  }
}

function nextSlide() {
  if (slides.value.length === 0) return;
  currentSlideIndex.value = (currentSlideIndex.value + 1) % slides.value.length;
}

function prevSlide() {
  if (slides.value.length === 0) return;
  currentSlideIndex.value = (currentSlideIndex.value - 1 + slides.value.length) % slides.value.length;
}

function startAutoSlide() {
  stopAutoSlide();
  slideInterval = setInterval(nextSlide, 6000);
}

function stopAutoSlide() {
  if (slideInterval) clearInterval(slideInterval);
}

onMounted(() => {
  fetchSlides();
  startAutoSlide();
});

onUnmounted(() => {
  stopAutoSlide();
});

async function onSubmit() {
  error.value = "";
  try {
    const success = await auth.login(username.value, password.value);
    if (success) {
      router.push("/profile");
    }
  } catch (e) {
    error.value = e?.response?.data?.msg || t('loginFailed');
  }
}
</script>