import { useEffect, useMemo, useState } from "react";
import api from "./api/axios";
import toast from "react-hot-toast";
import { Plus, Pencil, Trash2 } from "lucide-react";

const empty = {
  cliente_id: "",
  tipo: "credito",
  limite: "",
  saldo_actual: "",
  fecha_vencimiento: "",
  estado: "activo",
  observacion: "",
};

export default function Creditos() {
  const user = JSON.parse(localStorage.getItem("user") || "null");
  const isAdmin = user?.rol === "admin";

  const [clientes, setClientes] = useState([]);
  const [creditos, setCreditos] = useState([]);
  const [busqueda, setBusqueda] = useState("");
  const [showModal, setShowModal] = useState(false);
  const [editando, setEditando] = useState(null);
  const [form, setForm] = useState(empty);

  useEffect(() => {
    cargar();
  }, []);

  const cargar = async () => {
    try {
      const [c, cr] = await Promise.all([api.get("/clientes"), api.get("/creditos")]);
      setClientes(c.data || []);
      setCreditos(cr.data || []);
    } catch {
      toast.error("No se pudo cargar créditos");
    }
  };

  const filtrados = useMemo(() => {
    const q = busqueda.toLowerCase();
    return creditos.filter(
      (c) =>
        c.cliente?.nombre?.toLowerCase().includes(q) ||
        c.tipo?.toLowerCase().includes(q) ||
        String(c.limite).includes(q)
    );
  }, [creditos, busqueda]);

  if (!isAdmin) {
    return (
      <div className="bg-white rounded-xl shadow p-6">
        <h2 className="text-xl font-bold">Créditos</h2>
        <p className="text-gray-600 mt-2">Solo el administrador puede acceder a esta vista.</p>
      </div>
    );
  }

  const reset = () => {
    setForm(empty);
    setEditando(null);
  };

  const guardar = async () => {
    try {
      if (!form.cliente_id) {
        toast.error("Seleccione cliente");
        return;
      }

      const payload = {
        ...form,
        limite: Number(form.limite || 0),
        saldo_actual: Number(form.saldo_actual || 0),
      };

      if (editando) {
        await api.put(`/creditos/${editando.id}`, payload);
        toast.success("Crédito actualizado");
      } else {
        await api.post("/creditos", payload);
        toast.success("Crédito creado");
      }

      setShowModal(false);
      reset();
      cargar();
    } catch (error) {
      toast.error(error.response?.data?.message || "No se pudo guardar");
    }
  };

  const editar = (credito) => {
    setEditando(credito);
    setForm({
      cliente_id: credito.cliente_id,
      tipo: credito.tipo,
      limite: credito.limite,
      saldo_actual: credito.saldo_actual,
      fecha_vencimiento: credito.fecha_vencimiento?.split("T")[0] || credito.fecha_vencimiento || "",
      estado: credito.estado,
      observacion: credito.observacion || "",
    });
    setShowModal(true);
  };

  const eliminar = async (id) => {
    if (!confirm("¿Eliminar crédito?")) return;

    try {
      await api.delete(`/creditos/${id}`);
      toast.success("Crédito eliminado");
      cargar();
    } catch {
      toast.error("No se pudo eliminar");
    }
  };

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-xl shadow p-5 flex items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-bold">Créditos</h2>
          <p className="text-sm text-gray-500">Gestión de préstamos y cuentas.</p>
        </div>

        <button
          onClick={() => {
            reset();
            setShowModal(true);
          }}
          className="bg-blue-400 hover:bg-blue-500 text-white px-4 py-2 rounded-lg flex items-center gap-2"
        >
          <Plus size={18} /> Nuevo crédito
        </button>
      </div>

      <div className="bg-white rounded-xl shadow p-5">
        <input
          value={busqueda}
          onChange={(e) => setBusqueda(e.target.value)}
          placeholder="Buscar por cliente o tipo"
          className="w-full border rounded-lg px-3 py-2"
        />
      </div>

      <div className="space-y-3">
        {filtrados.map((c) => (
          <div key={c.id} className="bg-white rounded-xl shadow p-5">
            <div className="flex justify-between items-start gap-4">
              <div>
                <h3 className="font-semibold text-lg">{c.cliente?.nombre}</h3>
                <p className="text-sm text-gray-500">
                  Tipo: {c.tipo} · Estado: {c.estado}
                </p>
                <p className="text-sm text-gray-500">
                  Límite: S/ {Number(c.limite).toFixed(2)} · Saldo actual: S/ {Number(c.saldo_actual).toFixed(2)}
                </p>
                <p className="text-sm text-gray-500">
                  Vencimiento: {c.fecha_vencimiento || "Sin fecha"}
                </p>
                {c.observacion && <p className="text-sm mt-2">{c.observacion}</p>}
              </div>

              <div className="flex gap-2">
                <button onClick={() => editar(c)} className="bg-yellow-400 hover:bg-yellow-500 px-3 py-2 rounded-lg flex items-center gap-2">
                  <Pencil size={16} /> Editar
                </button>
                <button onClick={() => eliminar(c.id)} className="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg flex items-center gap-2">
                  <Trash2 size={16} /> Eliminar
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {showModal && (
        <div className="fixed inset-0 bg-black/40 flex justify-center items-center z-50">
          <div className="bg-white w-full max-w-2xl rounded-2xl shadow-xl p-6">
            <h3 className="text-xl font-bold mb-4">
              {editando ? "Editar crédito" : "Nuevo crédito"}
            </h3>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <select
                className="border rounded-lg px-3 py-2"
                value={form.cliente_id}
                onChange={(e) => setForm({ ...form, cliente_id: e.target.value })}
              >
                <option value="">Seleccione cliente</option>
                {clientes.map((cl) => (
                  <option key={cl.id} value={cl.id}>
                    {cl.nombre}
                  </option>
                ))}
              </select>

              <select
                className="border rounded-lg px-3 py-2"
                value={form.tipo}
                onChange={(e) => setForm({ ...form, tipo: e.target.value })}
              >
                <option value="credito">Crédito (préstamo)</option>
                <option value="cuenta">A cuenta</option>
              </select>

              <input
                type="number"
                step="0.01"
                min="0"
                className="border rounded-lg px-3 py-2"
                placeholder="Límite"
                value={form.limite}
                onChange={(e) => setForm({ ...form, limite: e.target.value })}
              />

              <input
                type="number"
                step="0.01"
                min="0"
                className="border rounded-lg px-3 py-2"
                placeholder="Saldo actual"
                value={form.saldo_actual}
                onChange={(e) => setForm({ ...form, saldo_actual: e.target.value })}
              />

              <input
                type="date"
                className="border rounded-lg px-3 py-2"
                value={form.fecha_vencimiento}
                onChange={(e) => setForm({ ...form, fecha_vencimiento: e.target.value })}
              />

              <select
                className="border rounded-lg px-3 py-2"
                value={form.estado}
                onChange={(e) => setForm({ ...form, estado: e.target.value })}
              >
                <option value="activo">Activo</option>
                <option value="cerrado">Cerrado</option>
                <option value="vencido">Vencido</option>
              </select>

              <textarea
                className="border rounded-lg px-3 py-2 md:col-span-2"
                placeholder="Observación"
                value={form.observacion}
                onChange={(e) => setForm({ ...form, observacion: e.target.value })}
              />
            </div>

            <div className="flex justify-end gap-2 mt-5">
              <button
                type="button"
                onClick={() => {
                  setShowModal(false);
                  reset();
                }}
                className="bg-gray-300 px-4 py-2 rounded-lg"
              >
                Cancelar
              </button>
              <button
                type="button"
                onClick={guardar}
                className="bg-blue-400 hover:bg-blue-500 text-white px-4 py-2 rounded-lg"
              >
                Guardar
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}