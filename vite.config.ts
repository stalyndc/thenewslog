import { defineConfig } from "vite";

export default defineConfig({
  root: "resources",
  build: {
    outDir: "../assets",
    emptyOutDir: false,
    cssCodeSplit: false,
    rollupOptions: {
      input: {
        app: "resources/ts/app.ts"
      },
      output: {
        entryFileNames: "app.js",
        assetFileNames: (assetInfo) => {
          const name = assetInfo.name ?? "asset";
          if (name.endsWith('.css')) {
            return 'app.css';
          }
          return "[name][extname]";
        }
      }
    }
  }
});
