import axios from "axios";
import { apiBaseUrl, apiUnavailable, deploymentNotice } from "../config/deployment";

const api = axios.create({
  baseURL: apiBaseUrl,
});

api.interceptors.request.use((config) => {
  if (apiUnavailable) {
    throw new axios.AxiosError(deploymentNotice, "ERR_API_NOT_CONFIGURED", config);
  }

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
