import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { Toaster } from 'react-hot-toast'
import './index.css'
import App from './App.jsx'

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <>
      <App />
      <Toaster position="top-right" toastOptions={{
        style: { background: "var(--ui-surface)", color: "var(--ui-ink)", border: "1px solid var(--ui-border)" },
        success: { iconTheme: { primary: "var(--ui-success)", secondary: "#fff" } },
        error: { iconTheme: { primary: "var(--ui-danger)", secondary: "#fff" } },
      }} />
    </>
  </StrictMode>,
)
