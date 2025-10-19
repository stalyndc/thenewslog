import { defineConfig } from "vite";

export default defineConfig({
  root: "resources",
  build: {
    outDir: "../public/assets",
    emptyOutDir: false,
    rollupOptions: {
      input: {
        app: "resources/ts/app.ts"
      }
    }
  }
});
