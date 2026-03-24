import { defineStore } from "pinia";
import api from "../services/api";

export const useAuthStore = defineStore("auth", {
    state: () => ({
        token: localStorage.getItem("token") || "",
        user: JSON.parse(localStorage.getItem("user") || "null"),
        loading: false,
    }),

    getters: {
        isAuthed: (s) => !!s.token,
    },

    actions: {
        async login(username, password) {
            this.loading = true;
            try {
                const res = await api.post("/api/login", { username, password });
                // متوقع response زي اللي عندك: ok, token, token_type, user
                const token = res.data?.token;
                const user = res.data?.user;

                if (!token) throw new Error("Token not found in response");

                this.token = token;
                this.user = user;

                localStorage.setItem("token", token);
                localStorage.setItem("user", JSON.stringify(user || null));

                // هات بيانات /me للتأكيد وتحديث اليوزر
                await this.fetchMe();

                return true;
            } finally {
                this.loading = false;
            }
        },

        async fetchMe() {
            const res = await api.get("/api/me");
            this.user = res.data?.user ?? res.data; // حسب عندك بيرجع ايه
            localStorage.setItem("user", JSON.stringify(this.user || null));
            return this.user;
        },

        logout() {
            localStorage.removeItem("token");
            localStorage.removeItem("user");
            this.token = "";
            this.user = null;
        },
    },
});