import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      // Any request to /api in dev gets forwarded to your Docker Nginx
      "/api": "http://localhost:8080",
    },
  },
});
