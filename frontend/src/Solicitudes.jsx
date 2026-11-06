/* eslint-disable react-hooks/set-state-in-effect */
import { useCallback, useEffect, useState } from "react";
import { CheckCircle2, ClipboardList, ShieldCheck, XCircle } from "lucide-react";
import api from "./api/axios";
import toast from "react-hot-toast";

const initialForm = { tipo: "servicio", asunto: "", descripcion: "", prioridad: "media" };
const labels = {
  servicio: "Servicio",
  informacion: "Información",
  acceso: "Acceso",
  baja: "Baja",
  media: "Media",
  alta: "Alta",
  critica: "Crítica",
  pendiente: "Pendiente",
  en_revision: "En revisión",
  resuelta: "Resuelta",
  rechazada: "Rechazada",
  aprobada: "Aprobada",
};

function dateLabel(value) {
  if (!value) return "Sin fecha";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString("es-PE", { dateStyle: "short", timeStyle: "short" });
}

export default function Solicitudes({ user }) {
  const [requests, setRequests] = useState([]);
  const [form, setForm] = useState(initialForm);
  const [resolution, setResolution] = useState({});
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const canReview = ["admin", "gerente"].includes(user?.role_name);

  const refresh = useCallback(async () => {
    try {
      const response = await api.get("/solicitudes");
      setRequests(Array.isArray(response.data) ? response.data : []);
    } catch (error) {
      toast.error(error.response?.data?.message || "No se pudieron cargar las solicitudes");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { refresh(); }, [refresh]);

  const create = async (event) => {
    event.preventDefault();
    setBusy(true);
    try {
      await api.post("/solicitudes", form);
      setForm(initialForm);
      toast.success("Solicitud registrada");
      await refresh();
    } catch (error) {
      const first = Object.values(error.response?.data?.errors || {}).flat()[0];
      toast.error(first || error.response?.data?.message || "No se pudo registrar la solicitud");
    } finally {
      setBusy(false);
    }
  };

  const assign = async (id) => {
    if (!user?.id) return;
    setBusy(true);
    try {
      await api.patch(`/solicitudes/${id}/asignar`, { responsable_id: user.id, estado: "en_revision" });
      toast.success("Solicitud asumida para revisión");
      await refresh();
    } catch (error) {
      toast.error(error.response?.data?.message || "No se pudo asignar la solicitud");
    } finally {
      setBusy(false);
    }
  };

  const decide = async (id, decision) => {
    const text = resolution[id]?.trim();
    if (!text) {
      toast.error("Escribe la resolución antes de registrar la decisión");
      return;
    }
    setBusy(true);
    try {
      await api.patch(`/solicitudes/${id}/resolver`, { decision, resolucion: text });
      toast.success("Decisión registrada");
      setResolution((current) => ({ ...current, [id]: "" }));
      await refresh();
    } catch (error) {
      toast.error(error.response?.data?.message || "No se pudo registrar la decisión");
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="space-y-5">
      <section className="app-card p-5">
        <div className="flex items-start gap-3">
          <ClipboardList className="mt-1 shrink-0" size={22} aria-hidden="true" />
          <div>
            <h2 className="text-xl font-semibold">Solicitudes</h2>
            <p className="mt-1 text-sm text-zinc-600">Registra necesidades de servicio, información o acceso. Cada decisión queda visible en el historial.</p>
          </div>
        </div>
        <form onSubmit={create} className="mt-5 grid gap-4 md:grid-cols-2">
          <label className="block text-sm font-medium">Tipo
            <select className="app-field mt-1" value={form.tipo} onChange={(event) => setForm({ ...form, tipo: event.target.value })}>
              <option value="servicio">Servicio</option><option value="informacion">Información</option><option value="acceso">Acceso</option>
            </select>
          </label>
          <label className="block text-sm font-medium">Prioridad
            <select className="app-field mt-1" value={form.prioridad} onChange={(event) => setForm({ ...form, prioridad: event.target.value })}>
              <option value="baja">Baja</option><option value="media">Media</option><option value="alta">Alta</option><option value="critica">Crítica</option>
            </select>
          </label>
          <label className="block text-sm font-medium md:col-span-2">Asunto
            <input className="app-field mt-1" required maxLength={160} value={form.asunto} onChange={(event) => setForm({ ...form, asunto: event.target.value })} placeholder="Resumen de la necesidad" />
          </label>
          <label className="block text-sm font-medium md:col-span-2">Descripción
            <textarea className="app-field mt-1 min-h-24" required maxLength={3000} value={form.descripcion} onChange={(event) => setForm({ ...form, descripcion: event.target.value })} placeholder="Explica qué necesitas y para qué" />
          </label>
          {form.tipo === "acceso" && <p className="text-sm text-zinc-600 md:col-span-2"><ShieldCheck size={16} className="inline" aria-hidden="true" /> La aprobación de una solicitud de acceso registra la decisión; los permisos se gestionan por separado.</p>}
          <button type="submit" data-voice="Registrar solicitud" disabled={busy} className="app-primary md:justify-self-start">{busy ? "Guardando…" : "Registrar solicitud"}</button>
        </form>
      </section>

      <section className="space-y-3" aria-label="Historial de solicitudes">
        <h3 className="text-lg font-semibold">{canReview ? "Todas las solicitudes" : "Mis solicitudes"}</h3>
        {loading && <p role="status">Cargando solicitudes…</p>}
        {!loading && requests.length === 0 && <p className="app-card p-5 text-sm text-zinc-600">Todavía no hay solicitudes.</p>}
        {requests.map((item) => {
          const pending = item.estado === "pendiente" || item.estado === "en_revision";
          return (
            <article key={item.id} className="app-card p-5">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <p className="text-xs font-bold uppercase tracking-widest text-zinc-600">#{item.id} · {labels[item.tipo] || item.tipo} · Prioridad {labels[item.prioridad] || item.prioridad}</p>
                  <h4 className="mt-1 text-lg font-semibold">{item.asunto}</h4>
                </div>
                <span className={`request-status request-${item.estado}`}><span aria-hidden="true">{item.estado === "resuelta" ? "✓" : item.estado === "rechazada" ? "✕" : "◷"}</span> {labels[item.estado] || item.estado}</span>
              </div>
              <p className="mt-3 whitespace-pre-wrap text-sm text-zinc-700">{item.descripcion}</p>
              <dl className="mt-4 grid gap-2 border-t border-zinc-200 pt-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div><dt className="text-zinc-600">Solicitante</dt><dd className="font-medium">{item.solicitante_nombre || `Usuario #${item.solicitante_id}`}</dd></div>
                <div><dt className="text-zinc-600">Fecha</dt><dd className="font-medium">{dateLabel(item.fecha_solicitud)}</dd></div>
                <div><dt className="text-zinc-600">Responsable</dt><dd className="font-medium">{item.responsable_nombre || "Sin asignar"}</dd></div>
                <div><dt className="text-zinc-600">Decisión</dt><dd className="font-medium">{item.decision ? labels[item.decision] || item.decision : "Pendiente"}</dd></div>
              </dl>
              {item.resolucion && <p className="mt-3 rounded-lg bg-zinc-100 p-3 text-sm"><strong>Resolución:</strong> {item.resolucion}</p>}
              {canReview && pending && (
                <div className="mt-4 border-t border-zinc-200 pt-4">
                  {!item.responsable_id && <button type="button" data-voice="Asumir solicitud" disabled={busy} onClick={() => assign(item.id)} className="app-secondary">Asumir revisión</button>}
                  {item.responsable_id && <div className="space-y-3">
                    <label className="block text-sm font-medium">Resolución y motivo de la decisión
                      <textarea className="app-field mt-1 min-h-20" maxLength={3000} value={resolution[item.id] || ""} onChange={(event) => setResolution((current) => ({ ...current, [item.id]: event.target.value }))} placeholder="Deja constancia de la decisión" />
                    </label>
                    <div className="flex flex-wrap gap-2">
                      <button type="button" data-voice="Aprobar solicitud" disabled={busy} onClick={() => decide(item.id, "aprobada")} className="app-success"><CheckCircle2 size={17} /> Aprobar</button>
                      <button type="button" data-voice="Rechazar solicitud" disabled={busy} onClick={() => decide(item.id, "rechazada")} className="app-danger"><XCircle size={17} /> Rechazar</button>
                    </div>
                  </div>}
                </div>
              )}
            </article>
          );
        })}
      </section>
    </div>
  );
}
