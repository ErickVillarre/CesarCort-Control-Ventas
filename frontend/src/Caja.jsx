/* eslint-disable react-hooks/set-state-in-effect */
import { useEffect, useState } from "react";
import { Banknote, CheckCircle, DoorOpen, Save, XCircle } from "lucide-react";
import api from "./api/axios";
import toast from "react-hot-toast";

export default function Caja() {
  const [data, setData] = useState(null);
  const [apertura, setApertura] = useState({ caja: "Caja principal", fondo_inicial: "", observacion: "" });
  const [selectedSale, setSelectedSale] = useState(null);
  const [pagos, setPagos] = useState([{ metodo: "efectivo", monto: "", numero_operacion: "" }]);
  const [cierre, setCierre] = useState({ monto_contado: "", justificacion_diferencia: "" });

  const cargar = async () => {
    const res = await api.get("/caja/dashboard");
    setData(res.data);
  };

  useEffect(() => {
    cargar().catch(() => toast.error("No se pudo cargar caja"));
  }, []);

  const abrir = async () => {
    try {
      await api.post("/caja/abrir", apertura);
      toast.success("Caja abierta");
      cargar();
    } catch (error) {
      toast.error(error.response?.data?.message || "No se pudo abrir caja");
    }
  };

  const cobrar = async () => {
    try {
      await api.post(`/caja/ventas/${selectedSale.id}/cobrar`, { pagos });
      toast.success("Cobro registrado");
      setSelectedSale(null);
      cargar();
    } catch (error) {
      toast.error(error.response?.data?.message || "No se pudo cobrar");
    }
  };

  const cerrar = async () => {
    try {
      await api.post(`/caja/${data.caja_actual.id}/cerrar`, cierre);
      toast.success("Caja cerrada");
      cargar();
    } catch (error) {
      toast.error(error.response?.data?.message || "No se pudo cerrar");
    }
  };

  const validarPago = async (id, estado) => {
    try {
      await api.post(`/caja/pagos/${id}/validar`, { estado });
      toast.success(estado === "validado" ? "Pago validado" : "Pago rechazado");
      cargar();
    } catch (error) {
      toast.error(error.response?.data?.message || "No se pudo validar el pago");
    }
  };

  const totals = data?.totales || {};

  return (
    <div className="space-y-5">
      <section className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
        <div className="flex items-center gap-3">
          <div className="rounded bg-zinc-900 p-2 text-white"><Banknote size={22} /></div>
          <div>
            <h2 className="text-xl font-semibold">Caja</h2>
            <p className="text-sm text-zinc-500">Apertura, cobros, validacion y cuadre diario.</p>
          </div>
        </div>
      </section>

      <section className="grid gap-3 md:grid-cols-4">
        {Object.entries({
          preventas: data?.preventas_pendientes?.length || 0,
          cobradas_hoy: data?.ventas_cobradas_hoy || 0,
          efectivo: totals.efectivo || 0,
          pagos_pendientes: data?.pagos_pendientes_validacion?.length || 0,
        }).map(([label, value]) => (
          <div key={label} className="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
            <p className="text-xs uppercase text-zinc-500">{label.replaceAll("_", " ")}</p>
            <p className="mt-2 text-2xl font-semibold">{Number(value).toFixed(label === "efectivo" ? 2 : 0)}</p>
          </div>
        ))}
      </section>

      {!data?.caja_actual ? (
        <section className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
          <h3 className="mb-4 flex items-center gap-2 font-semibold"><DoorOpen size={18} /> Apertura de caja</h3>
          <div className="grid gap-3 md:grid-cols-4">
            <input value={apertura.caja} onChange={(e) => setApertura({ ...apertura, caja: e.target.value })} className="rounded border border-zinc-300 px-3 py-2" />
            <input type="number" min="0" step="0.01" value={apertura.fondo_inicial} onChange={(e) => setApertura({ ...apertura, fondo_inicial: e.target.value })} className="rounded border border-zinc-300 px-3 py-2" placeholder="Fondo inicial" />
            <input value={apertura.observacion} onChange={(e) => setApertura({ ...apertura, observacion: e.target.value })} className="rounded border border-zinc-300 px-3 py-2" placeholder="Observacion" />
            <button onClick={abrir} className="rounded bg-zinc-900 px-4 py-2 text-white">Abrir caja</button>
          </div>
        </section>
      ) : (
        <section className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
          <h3 className="font-semibold">Caja abierta: {data.caja_actual.codigo}</h3>
          <div className="mt-4 grid gap-3 md:grid-cols-3">
            <input type="number" min="0" step="0.01" value={cierre.monto_contado} onChange={(e) => setCierre({ ...cierre, monto_contado: e.target.value })} className="rounded border border-zinc-300 px-3 py-2" placeholder="Monto contado" />
            <input value={cierre.justificacion_diferencia} onChange={(e) => setCierre({ ...cierre, justificacion_diferencia: e.target.value })} className="rounded border border-zinc-300 px-3 py-2" placeholder="Justificacion si hay diferencia" />
            <button onClick={cerrar} className="rounded bg-zinc-900 px-4 py-2 text-white">Cerrar y cuadrar</button>
          </div>
        </section>
      )}

      <section className="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div className="border-b border-zinc-200 px-4 py-3 font-semibold">Preventas pendientes de cobro</div>
        <div className="divide-y divide-zinc-100">
          {(data?.preventas_pendientes || []).map((venta) => (
            <button key={venta.id} onClick={() => setSelectedSale(venta)} className="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-zinc-50">
              <span>{venta.cliente?.nombre || "Cliente"} - {venta.vendedor?.name || "Vendedor"}</span>
              <strong>S/ {Number(venta.total).toFixed(2)}</strong>
            </button>
          ))}
        </div>
      </section>

      <section className="rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div className="border-b border-zinc-200 px-4 py-3 font-semibold">Pagos pendientes de validacion</div>
        <div className="divide-y divide-zinc-100">
          {(data?.pagos_pendientes_validacion || []).map((pago) => (
            <div key={pago.id} className="flex flex-col justify-between gap-3 px-4 py-3 md:flex-row md:items-center">
              <div>
                <p className="font-semibold">Venta #{pago.venta_id} - {pago.metodo}</p>
                <p className="text-sm text-zinc-500">
                  S/ {Number(pago.monto).toFixed(2)} {pago.numero_operacion ? `- Op. ${pago.numero_operacion}` : ""}
                </p>
              </div>
              <div className="flex gap-2">
                <button onClick={() => validarPago(pago.id, "validado")} className="flex items-center gap-2 rounded bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                  <CheckCircle size={16} /> Validar
                </button>
                <button onClick={() => validarPago(pago.id, "rechazado")} className="flex items-center gap-2 rounded bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700">
                  <XCircle size={16} /> Rechazar
                </button>
              </div>
            </div>
          ))}
          {!data?.pagos_pendientes_validacion?.length && <p className="px-4 py-3 text-sm text-zinc-500">No hay pagos por validar.</p>}
        </div>
      </section>

      {selectedSale && (
        <section className="rounded-lg border border-zinc-200 bg-zinc-50 p-5 shadow-sm">
          <h3 className="mb-3 font-semibold">Cobrar venta #{selectedSale.id}</h3>
          <div className="space-y-3">
            {pagos.map((pago, index) => (
              <div key={index} className="grid gap-3 md:grid-cols-4">
                <select value={pago.metodo} onChange={(e) => setPagos(pagos.map((p, i) => i === index ? { ...p, metodo: e.target.value } : p))} className="rounded border border-zinc-300 px-3 py-2">
                  {["efectivo", "yape", "transferencia", "tarjeta", "credito", "dinero_cuenta"].map((m) => <option key={m} value={m}>{m}</option>)}
                </select>
                <input type="number" step="0.01" value={pago.monto} onChange={(e) => setPagos(pagos.map((p, i) => i === index ? { ...p, monto: e.target.value } : p))} className="rounded border border-zinc-300 px-3 py-2" placeholder="Monto" />
                <input value={pago.numero_operacion} onChange={(e) => setPagos(pagos.map((p, i) => i === index ? { ...p, numero_operacion: e.target.value } : p))} className="rounded border border-zinc-300 px-3 py-2" placeholder="Operacion" />
                <button onClick={() => setPagos([...pagos, { metodo: "efectivo", monto: "", numero_operacion: "" }])} className="rounded border border-zinc-300 px-3 py-2">Agregar medio</button>
              </div>
            ))}
            <button onClick={cobrar} className="flex items-center gap-2 rounded bg-zinc-900 px-4 py-2 text-white"><CheckCircle size={17} /> Confirmar cobro</button>
          </div>
        </section>
      )}
    </div>
  );
}
