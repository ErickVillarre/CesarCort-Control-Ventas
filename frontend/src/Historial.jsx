/* eslint-disable react-hooks/set-state-in-effect, react-hooks/exhaustive-deps */
import { useEffect, useState } from "react";
import api from "./api/axios";
import { ChevronDown, ChevronUp } from "lucide-react";
import toast from "react-hot-toast";

const today = new Date().toISOString().split("T")[0];

export default function Historial() {
  const [filters, setFilters] = useState({ desde: today, hasta: today, cliente_id: "", vendedor_id: "", comprobante_tipo: "", metodo_pago: "" });
  const [summary, setSummary] = useState(null);
  const [ventas, setVentas] = useState([]);
  const [abiertos, setAbiertos] = useState({});

  const cargar = async () => {
    try {
      const res = await api.get("/ventas/historial", { params: filters });
      setSummary(res.data);
      setVentas(res.data.ventas || []);
    } catch (error) {
      toast.error(error.response?.data?.message || "No se pudo cargar el historial");
    }
  };

  useEffect(() => {
    cargar();
  }, []);

  const setFilter = (field, value) => setFilters((current) => ({ ...current, [field]: value }));
  const toggle = (id) => setAbiertos((prev) => ({ ...prev, [id]: !prev[id] }));

  return (
    <div className="space-y-5">
      <section className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
        <h2 className="text-xl font-semibold">Historial de ventas</h2>
        <div className="mt-4 grid gap-3 md:grid-cols-6">
          <input type="date" max={today} value={filters.desde} onChange={(e) => setFilter("desde", e.target.value)} className="rounded border border-zinc-300 px-3 py-2" />
          <input type="date" max={today} value={filters.hasta} onChange={(e) => setFilter("hasta", e.target.value)} className="rounded border border-zinc-300 px-3 py-2" />
          <input value={filters.cliente_id} onChange={(e) => setFilter("cliente_id", e.target.value)} className="rounded border border-zinc-300 px-3 py-2" placeholder="ID cliente" />
          <input value={filters.vendedor_id} onChange={(e) => setFilter("vendedor_id", e.target.value)} className="rounded border border-zinc-300 px-3 py-2" placeholder="ID vendedor" />
          <select value={filters.comprobante_tipo} onChange={(e) => setFilter("comprobante_tipo", e.target.value)} className="rounded border border-zinc-300 px-3 py-2">
            <option value="">Comprobante</option><option value="boleta">Boleta</option><option value="factura">Factura</option><option value="interno">Interno</option>
          </select>
          <button onClick={cargar} className="rounded bg-zinc-900 px-4 py-2 text-white">Consultar</button>
        </div>
      </section>

      <section className="grid gap-3 md:grid-cols-4 xl:grid-cols-6">
        {[
          ["Ventas", summary?.cantidad_ventas],
          ["Subtotal", summary?.subtotal],
          ["Descuentos", summary?.descuentos],
          ["Total", summary?.total_dia],
          ["Pendientes", summary?.operaciones_pendientes],
          ["Anuladas", summary?.ventas_anuladas],
        ].map(([label, value]) => (
          <div key={label} className="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
            <p className="text-xs uppercase text-zinc-500">{label}</p>
            <p className="mt-2 text-2xl font-semibold">{Number(value || 0).toFixed(["Subtotal", "Descuentos", "Total"].includes(label) ? 2 : 0)}</p>
          </div>
        ))}
      </section>

      <section className="grid gap-3 md:grid-cols-6">
        {["efectivo", "yape", "transferencia", "tarjeta", "credito", "dinero_cuenta"].map((key) => (
          <div key={key} className="rounded border border-zinc-200 bg-zinc-50 p-3 text-sm">
            <p className="text-zinc-500">{key.replace("_", " ")}</p>
            <p className="font-semibold">S/ {Number(summary?.[key] || 0).toFixed(2)}</p>
          </div>
        ))}
      </section>

      <section className="space-y-3">
        {ventas.map((v) => {
          const abierto = !!abiertos[v.id];

          return (
            <div key={v.id} className="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
              <button onClick={() => toggle(v.id)} className="flex w-full items-center justify-between px-5 py-4 text-left hover:bg-zinc-50">
                <div>
                  <h3 className="font-semibold">{v.cliente?.nombre || "Cliente anonimo"}</h3>
                  <p className="text-sm text-zinc-500">{new Date(v.created_at).toLocaleString("es-PE")} - {v.vendedor?.name || "Sin vendedor"}</p>
                </div>
                <div className="flex items-center gap-4">
                  <span className="font-bold">S/ {Number(v.total).toFixed(2)}</span>
                  {abierto ? <ChevronUp size={18} /> : <ChevronDown size={18} />}
                </div>
              </button>

              {abierto && (
                <div className="px-5 pb-5">
                  <div className="overflow-x-auto rounded border border-zinc-200">
                    <table className="w-full text-sm">
                      <tbody>
                        {v.detalles?.map((d) => (
                          <tr key={d.id} className="border-b border-zinc-100 last:border-0">
                            <td className="p-2">{d.producto?.nombre}</td>
                            <td className="p-2">{d.cantidad}</td>
                            <td className="p-2">S/ {Number(d.precio).toFixed(2)}</td>
                            <td className="p-2">S/ {Number(d.subtotal).toFixed(2)}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </div>
          );
        })}
      </section>
    </div>
  );
}
