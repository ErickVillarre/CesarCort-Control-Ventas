import axios from "axios";
import { announceResult } from "../accessibilityEvents";

const configuredApi = import.meta.env.VITE_API_URL?.replace(/\/+$/, "");
const apiBase = configuredApi || (import.meta.env.DEV ? "http://127.0.0.1:8000/api" : null);
const apiConfigError = import.meta.env.PROD && (!apiBase || !apiBase.startsWith("https://"))
  ? "API no configurada. Define VITE_API_URL con la URL HTTPS de Laravel y vuelve a desplegar."
  : null;
const api = axios.create({
  baseURL: apiBase || undefined,
});

api.interceptors.request.use((config) => {
  if (apiConfigError) return Promise.reject(new Error(apiConfigError));
  const token = localStorage.getItem("token");

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

api.interceptors.response.use(
  (response) => {
    if (["post", "put", "patch", "delete"].includes(response.config.method?.toLowerCase())) announceResult("success");
    return response;
  },
  (error) => {
    if (["post", "put", "patch", "delete"].includes(error.config?.method?.toLowerCase()) || [401, 403].includes(error.response?.status)) announceResult("error");
    return Promise.reject(error);
  }
);

export const apiOrigin = apiBase?.startsWith("http") ? new URL(apiBase).origin : window.location.origin;
export { apiConfigError };

export default api;
