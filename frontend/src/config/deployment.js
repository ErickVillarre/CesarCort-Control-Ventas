const configuredApiUrl = (import.meta.env.VITE_API_URL || "").trim().replace(/\/+$/, "");

export const apiBaseUrl = configuredApiUrl || (import.meta.env.DEV ? "http://127.0.0.1:8000/api" : "");
export const apiUnavailable = import.meta.env.PROD && !configuredApiUrl;
export const deploymentNotice = "Publicacion parcial. El acceso estara disponible cuando se conecte el servicio de datos.";

export function storageUrl(path) {
  if (!path || !apiBaseUrl) return "";
  return `${apiBaseUrl.replace(/\/api$/, "")}/storage/${path.replace(/^\/+/, "")}`;
}
