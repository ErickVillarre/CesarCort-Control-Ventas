import { useEffect, useState } from "react";
import { Megaphone } from "lucide-react";
import api from "./api/axios";
import toast from "react-hot-toast";

export default function Marketing() {
  const [data, setData] = useState(null);

  useEffect(() => {
    api.get("/marketing/dashboard").then((res) => setData(res.data)).catch(() => toast.error("No se pudo cargar marketing"));
  }, []);

  return (
    <div className="space-y-5">
      <section className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
        <div className="flex items-center gap-3">
          <div className="rounded bg-zinc-900 p-2 text-white"><Megaphone size={22} /></div>
          <div>
            <h2 className="text-xl font-semibold">Marketing</h2>
            <p className="text-sm text-zinc-500">Tendencias reales, redes configurables y calendario de contenidos.</p>
          </div>
        </div>
      </section>

      <section className="grid gap-4 xl:grid-cols-2">
        <Table title="Productos mas vendidos" rows={data?.productos_mas_vendidos || []} columns={["nombre", "unidades", "total"]} />
        <Table title="Baja rotacion con stock" rows={data?.baja_rotacion || []} columns={["nombre", "stock", "ventas"]} />
        <Table title="Redes sociales configurables" rows={data?.redes || []} columns={["nombre", "enlace", "usuario", "estado"]} />
        <Table title="Calendario" rows={data?.calendario || []} columns={["tipo", "titulo", "canal", "programado_at", "estado"]} />
      </section>

      <section className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
        <h3 className="font-semibold">Sugerencias</h3>
        <div className="mt-3 space-y-2">
          {(data?.sugerencias || []).map((item, index) => (
            <div key={index} className="rounded border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm">
              <strong>{item.producto}</strong>: {item.motivo}
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}

function Table({ title, rows, columns }) {
  return (
    <div className="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
      <div className="border-b border-zinc-200 px-4 py-3 font-semibold">{title}</div>
      <table className="w-full text-sm">
        <tbody>
          {rows.map((row, index) => (
            <tr key={row.id || index} className="border-b border-zinc-100 last:border-0">
              {columns.map((column) => <td key={column} className="px-4 py-3">{String(row[column] ?? "-")}</td>)}
            </tr>
          ))}
          {rows.length === 0 && <tr><td className="px-4 py-4 text-zinc-500">Sin registros.</td></tr>}
        </tbody>
      </table>
    </div>
  );
}
