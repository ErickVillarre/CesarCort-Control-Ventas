/* eslint-disable react-hooks/set-state-in-effect, react-hooks/exhaustive-deps */
import { useEffect, useState } from "react";
import { Download, FileSpreadsheet } from "lucide-react";
import api from "./api/axios";
import toast from "react-hot-toast";

export default function Reportes() {
  const today = new Date().toISOString().split("T")[0];
  const [filters, setFilters] = useState({ desde: today.slice(0, 8) + "01", hasta: today });
  const [data, setData] = useState(null);

  const cargar = async () => {
    const res = await api.get("/reportes", { params: filters });
    setData(res.data);
  };

  useEffect(() => {
    cargar().catch(() => toast.error("No se pudieron cargar reportes"));
  }, []);

  const exportar = async () => {
    const res = await api.get("/reportes/exportar-csv", { params: filters, responseType: "blob" });
    const url = URL.createObjectURL(new Blob([res.data], { type: "text/csv" }));
    const link = document.createElement("a");
    link.href = url;
    link.download = "reporte-ventas.csv";
    link.click();
    URL.revokeObjectURL(url);
  };

  return (
    <div className="space-y-5">
      <section className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
        <div className="flex flex-col justify-between gap-3 md:flex-row md:items-center">
          <div className="flex items-center gap-3">
            <div className="rounded bg-zinc-900 p-2 text-white"><FileSpreadsheet size={22} /></div>
            <div>
              <h2 className="text-xl font-semibold">Centro de Reportes</h2>
              <p className="text-sm text-zinc-500">Ventas, caja, vendedores, clientes, creditos, inventario, empleados y mantenimiento.</p>
            </div>
          </div>
          <button onClick={exportar} className="flex items-center gap-2 rounded bg-zinc-900 px-4 py-2 text-white"><Download size={17} /> CSV</button>
        </div>
        <div className="mt-4 grid gap-3 md:grid-cols-3">
          <input type="date" max={today} value={filters.desde} onChange={(e) => setFilters({ ...filters, desde: e.target.value })} className="rounded border border-zinc-300 px-3 py-2" />
          <input type="date" max={today} value={filters.hasta} onChange={(e) => setFilters({ ...filters, hasta: e.target.value })} className="rounded border border-zinc-300 px-3 py-2" />
          <button onClick={cargar} className="rounded border border-zinc-300 px-4 py-2">Aplicar filtros</button>
        </div>
      </section>

      <section className="grid gap-4 xl:grid-cols-2">
        <ReportTable title="Reporte de ventas" rows={data?.ventas || []} />
        <ReportTable title="Reporte de caja" rows={data?.caja || []} />
        <ReportTable title="Reporte por cliente" rows={data?.clientes || []} />
        <ReportTable title="Reporte de creditos" rows={data?.creditos || []} />
        <ReportTable title="Inventario" rows={data?.inventario ? [data.inventario] : []} />
        <ReportTable title="Mantenimiento" rows={data?.mantenimiento || []} />
        <ReportTable title="Empleados" rows={data?.empleados || []} />
      </section>
    </div>
  );
}

function ReportTable({ title, rows }) {
  const columns = Object.keys(rows[0] || {});

  return (
    <div className="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
      <div className="border-b border-zinc-200 px-4 py-3 font-semibold">{title}</div>
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <tbody>
            {rows.map((row, index) => (
              <tr key={index} className="border-b border-zinc-100 last:border-0">
                {columns.map((column) => <td key={column} className="px-4 py-3">{String(row[column] ?? "-")}</td>)}
              </tr>
            ))}
            {rows.length === 0 && <tr><td className="px-4 py-4 text-zinc-500">Sin registros para el rango seleccionado.</td></tr>}
          </tbody>
        </table>
      </div>
    </div>
  );
}
