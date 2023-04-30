import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import proxyStubs from './proxyStubs';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      vue: 'vue/dist/vue.esm-browser.prod.js'
    }
  },
  build: {
    outDir: './../app/',
    rollupOptions: {
      input: 'src/main.js',
      output: {//disable hashing
        entryFileNames: `[name].js`,
        chunkFileNames: `[name].js`,
        assetFileNames: `[name].[ext]`
      }
    }
  },
  server: {
    port: 8080,
    open: true,
    proxy: {
      '/testimages': {
        target: 'https://croptest.totalmedial.de',
        secure: false,
        changeOrigin: true,
        logLevel: "info"
      },
      '/wp-admin': {
        target: 'https://www.totalmedial.de',
        secure: false,
        changeOrigin: true,
        logLevel: "info"
      },
      '/fake-ajax-url': {
        target: 'https://www.totalmedial.de',//any real URL should work
        secure: false,
        changeOrigin: true,
        logLevel: "info",
        configure: (proxy, options) => {
          proxy.on("proxyRes", proxyStubs);
        }
      },
    }
  }
})
