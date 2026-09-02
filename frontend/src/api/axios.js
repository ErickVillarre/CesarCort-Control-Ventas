import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api",
});

api.interceptors.request.use((config) => {
  const sessionToken = localStorage.getItem("token");

  if (sessionToken) {
    config.headers.Authorization = `Bearer ${sessionToken}`;
  }

  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 403) {
      window.dispatchEvent(new CustomEvent("access-denied"));
    }

    return Promise.reject(error);
  }
);

export default api;
