import { useEffect, useState } from "react";
import api from "./api/axios";
import { CalendarDays, ChevronDown, ChevronUp } from "lucide-react";
import toast from "react-hot-toast";

export default function Historial() {
  const [fecha, setFecha] = useState(new Date().toISOString().split("T")[0]);
  const [ventas, setVentas] = useState([]);
  const [totalDia, setTotalDia] = useState(0);
  const [cantidadVentas, setCantidadVentas] = useState(0);
  const [abiertos, setAbiertos] = useState({});

  const cargar = async (f = fecha) => {
    try {
      const res = await api.get("/ventas/historial", { params: { fecha: f } });
      setVentas(res.data.ventas || []);
      setTotalDia(res.data.total_dia || 0);
      setCantidadVentas(res.data.cantidad_ventas || 0);
    } catch {
      toast.error("No se pudo cargar el historial");
    }
  };

  useEffect(() => {
    cargar(fecha);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const toggle = (id) => {
    setAbiertos((prev) => ({ ...prev, [id]: !prev[id] }));
  };

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-xl shadow p-5">
        <h2 className="text-xl font-bold mb-4">Historial de ventas</h2>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="bg-gray-50 rounded-lg p-4">
            <p className="text-sm text-gray-500">Fecha</p>
            <input
              type="date"
              value={fecha}
              min={new Date().toISOString().split("T")[0]}
              onChange={(e) => {
                setFecha(e.target.value);
                cargar(e.target.value);
              }}
              className="mt-2 w-full border rounded-lg px-3 py-2"
            />
          </div>

          <div className="bg-gray-50 rounded-lg p-4">
            <p className="text-sm text-gray-500">Ventas del día</p>
            <p className="text-2xl font-bold mt-2">{cantidadVentas}</p>
          </div>

          <div className="bg-gray-50 rounded-lg p-4">
            <p className="text-sm text-gray-500">Total vendido</p>
            <p className="text-2xl font-bold mt-2">S/ {Number(totalDia).toFixed(2)}</p>
          </div>
        </div>
      </div>

      <div className="space-y-3">
        {ventas.map((v) => {
          const abierto = !!abiertos[v.id];

          return (
            <div key={v.id} className="bg-white rounded-xl shadow overflow-hidden">
              <button
                onClick={() => toggle(v.id)}
                className="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50"
              >
                <div>
                  <h3 className="font-semibold">
                    {v.cliente?.nombre || "Cliente Anónimo"}
                  </h3>
                  <p className="text-sm text-gray-500">
                    {new Date(v.created_at).toLocaleString("es-PE")}
                  </p>
                </div>

                <div className="flex items-center gap-4">
                  <span className="font-bold">S/ {Number(v.total).toFixed(2)}</span>
                  {abierto ? <ChevronUp size={18} /> : <ChevronDown size={18} />}
                </div>
              </button>

              {abierto && (
                <div className="px-5 pb-5">
                  <div className="overflow-x-auto border rounded-lg">
                    <table className="w-full text-sm">
                      <thead className="bg-gray-100">
                        <tr>
                          <th className="p-2 text-left">Producto</th>
                          <th className="p-2 text-left">Cantidad</th>
                          <th className="p-2 text-left">Precio</th>
                          <th className="p-2 text-left">Subtotal</th>
                        </tr>
                      </thead>
                      <tbody>
                        {v.detalles?.map((d) => (
                          <tr key={d.id} className="border-t">
                            <td className="p-2">{d.producto?.nombre}</td>
                            <td className="p-2">{d.cantidad}</td>
                            <td className="p-2">S/ {Number(d.precio).toFixed(2)}</td>
                            <td className="p-2">S/ {Number(d.subtotal).toFixed(2)}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>

                  <div className="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                    <div className="bg-gray-50 p-3 rounded-lg">
                      <p className="text-gray-500">Subtotal</p>
                      <p className="font-semibold">S/ {Number(v.subtotal).toFixed(2)}</p>
                    </div>
                    <div className="bg-gray-50 p-3 rounded-lg">
                      <p className="text-gray-500">IGV</p>
                      <p className="font-semibold">S/ {Number(v.igv).toFixed(2)}</p>
                    </div>
                    <div className="bg-gray-50 p-3 rounded-lg">
                      <p className="text-gray-500">Método</p>
                      <p className="font-semibold">{v.metodo_pago}</p>
                    </div>
                    <div className="bg-gray-50 p-3 rounded-lg">
                      <p className="text-gray-500">Operación</p>
                      <p className="font-semibold">{v.tipo_operacion}</p>
                    </div>
                  </div>
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}