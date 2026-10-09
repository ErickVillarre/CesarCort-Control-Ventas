import { defineConfig, loadEnv } from 'vite'
import process from 'node:process'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig(({ command, mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const frontendOnly = command === 'build' && env.DEPLOYMENT_MODE === 'frontend-only'

  if (command === 'build' && !frontendOnly) {
    let api
    try {
      api = new URL(env.VITE_API_URL?.trim() || '')
    } catch {
      throw new Error('Configura VITE_API_URL con la URL HTTPS real de la API o usa DEPLOYMENT_MODE=frontend-only para una publicacion parcial.')
    }

    const hostname = api.hostname.toLowerCase().replace(/\.$/, '')
    const localHost = !hostname.includes('.') && !hostname.includes(':')
      || /(^|\.)localhost$|(^|\.)local$|^127\.|^0\.|^10\.|^192\.168\.|^169\.254\.|^172\.(1[6-9]|2\d|3[01])\.|^\[(::|::1|f[cd][0-9a-f:]*|fe[89ab][0-9a-f:]*)\]$/.test(hostname)

    if (api.protocol !== 'https:' || localHost || api.username || api.password || api.search || api.hash || !api.pathname.replace(/\/+$/, '').endsWith('/api')) {
      throw new Error('VITE_API_URL debe ser una URL HTTPS publica terminada en /api, sin credenciales, parametros ni fragmentos.')
    }
  }

  if (frontendOnly) {
    console.warn('Publicacion parcial: no se incluira una API y el inicio de sesion estara deshabilitado.')
  }

  return {
    plugins: [react()],
    // Explicit partial builds must not inherit a local API from .env.
    ...(frontendOnly ? { define: { 'import.meta.env.VITE_API_URL': JSON.stringify('') } } : {}),
  }
})
