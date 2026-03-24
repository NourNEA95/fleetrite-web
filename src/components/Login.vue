<template>
  <div class="auth-page">
    <div class="auth-card">
      <div class="brand">
        <img class="logo" src="https://gps.fleetrite.io/img/logo.png" alt="Fleetrite" />
        <h1>Welcome Back</h1>
        <p>Sign in to continue</p>
      </div>

      <form class="form" @submit.prevent="onSubmit">
        <label>
          Username
          <input v-model.trim="username" type="text" autocomplete="username" placeholder="Enter username" />
        </label>

        <label>
          Password
          <input v-model="password" type="password" autocomplete="current-password" placeholder="Enter password" />
        </label>

        <button class="btn" type="submit" :disabled="auth.loading">
          <span v-if="auth.loading">Logging in...</span>
          <span v-else>Login</span>
        </button>

        <p v-if="error" class="error">{{ error }}</p>
      </form>

      <div class="footnote">
        <span>Fleetrite Web</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";

const router = useRouter();
const auth = useAuthStore();

const username = ref("");
const password = ref("");
const error = ref("");

async function onSubmit() {
  error.value = "";
  try {
    await auth.login(username.value, password.value);
    router.push("/profile");
  } catch (e) {
    error.value = e?.response?.data?.message || "Login failed. Check credentials or API.";
  }
}
</script>