import { useEffect, useMemo, useState } from "react";
import api from "./api/axios";
import toast from "react-hot-toast";
import { Pencil, Trash2, Plus, Search, ChevronDown, ChevronUp } from "lucide-react";

const emptyForm = {
  nombre: "",
  apodo: "",
  dni: "",
  telefono: "",
  email: "",
  direccion: "",
  credito: "",
  saldo: "",
  tipo_cliente: "regular",
  activo: true,
  dni_imagen: null,
  firma_imagen: null,
};

export default function Clientes() {
  const [clientes, setClientes] = useState([]);
  const [busqueda, setBusqueda] = useState("");
  const [showModal, setShowModal] = useState(false);
  const [editando, setEditando] = useState(null);
  const [abiertos, setAbiertos] = useState({});

  const [form, setForm] = useState(emptyForm);
  const [previewDni, setPreviewDni] = useState("");
  const [previewFirma, setPreviewFirma] = useState("");

  const cargar = async () => {
    try {
      const res = await api.get("/clientes");
      setClientes(res.data || []);
    } catch {
      toast.error("No se pudo cargar clientes");
    }
  };

  useEffect(() => {
    cargar();
  }, []);

  const filtrados = useMemo(() => {
    const q = busqueda.toLowerCase();
    return clientes.filter(
      (c) =>
        c.nombre?.toLowerCase().includes(q) ||
        c.apodo?.toLowerCase().includes(q) ||
        c.dni?.toLowerCase().includes(q)
    );
  }, [clientes, busqueda]);

  const reset = () => {
    setForm(emptyForm);
    setPreviewDni("");
    setPreviewFirma("");
    setEditando(null);
  };

  const abrirNuevo = () => {
    reset();
    setShowModal(true);
  };

  const editar = (cliente) => {
    setEditando(cliente);
    setForm({
      nombre: cliente.nombre || "",
      apodo: cliente.apodo || "",
      dni: cliente.dni || "",
      telefono: cliente.telefono || "",
      email: cliente.email || "",
      direccion: cliente.direccion || "",
      credito: cliente.credito ?? "",
      saldo: cliente.saldo ?? "",
      tipo_cliente: cliente.tipo_cliente || "regular",
      activo: !!cliente.activo,
      dni_imagen: null,
      firma_imagen: null,
    });

    setPreviewDni(cliente.dni_imagen ? `http://127.0.0.1:8000/storage/${cliente.dni_imagen}` : "");
    setPreviewFirma(cliente.firma_imagen ? `http://127.0.0.1:8000/storage/${cliente.firma_imagen}` : "");
    setShowModal(true);
  };

  const guardar = async () => {
    try {
      if (!form.nombre.trim()) {
        toast.error("Ingrese nombre");
        return;
      }

      const fd = new FormData();
      Object.entries(form).forEach(([key, value]) => {
        if (value !== null && value !== undefined) {
          fd.append(key, value);
        }
      });

      if (editando) {
        await api.post(`/clientes/${editando.id}?_method=PUT`, fd, {
          headers: { "Content-Type": "multipart/form-data" },
        });
        toast.success("Cliente actualizado");
      } else {
        await api.post("/clientes", fd, {
          headers: { "Content-Type": "multipart/form-data" },
        });
        toast.success("Cliente creado");
      }

      setShowModal(false);
      reset();
      cargar();
    } catch (error) {
      const msg = error.response?.data?.message || "No se pudo guardar";
      toast.error(msg);
    }
  };

  const eliminar = async (id) => {
    if (!confirm("¿Eliminar cliente?")) return;

    try {
      await api.delete(`/clientes/${id}`);
      toast.success("Cliente eliminado");
      cargar();
    } catch {
      toast.error("No se pudo eliminar");
    }
  };

  const toggle = (id) => {
    setAbiertos((prev) => ({ ...prev, [id]: !prev[id] }));
  };

  const cambiarArchivo = (campo, file) => {
    if (!file) return;
    setForm((prev) => ({ ...prev, [campo]: file }));

    const url = URL.createObjectURL(file);
    if (campo === "dni_imagen") setPreviewDni(url);
    if (campo === "firma_imagen") setPreviewFirma(url);
  };

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-xl shadow p-5">
        <div className="flex justify-between items-center gap-4">
          <div>
            <h2 className="text-xl font-bold">Clientes</h2>
            <p className="text-sm text-gray-500">Registro, búsqueda e información legal.</p>
          </div>

          <button
            onClick={abrirNuevo}
            className="bg-blue-400 hover:bg-blue-500 text-white px-4 py-2 rounded-lg flex items-center gap-2"
          >
            <Plus size={18} /> Nuevo cliente
          </button>
        </div>

        <div className="mt-4 relative">
          <Search className="absolute left-3 top-3 text-gray-400" size={16} />
          <input
            value={busqueda}
            onChange={(e) => setBusqueda(e.target.value)}
            placeholder="Buscar por nombre, apodo o DNI"
            className="w-full border rounded-lg pl-10 pr-3 py-2"
          />
        </div>
      </div>

      <div className="space-y-3">
        {filtrados.map((c) => {
          const abierto = !!abiertos[c.id];

          return (
            <div key={c.id} className="bg-white rounded-xl shadow overflow-hidden">
              <button
                onClick={() => toggle(c.id)}
                className="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50"
              >
                <div>
                  <h3 className="font-semibold">{c.nombre}</h3>
                  <p className="text-sm text-gray-500">
                    {c.apodo ? `${c.apodo} · ` : ""}
                    {c.dni || "Sin DNI"}
                  </p>
                </div>

                {abierto ? <ChevronUp size={18} /> : <ChevronDown size={18} />}
              </button>

              {abierto && (
                <div className="px-5 pb-5">
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div className="bg-gray-50 p-4 rounded-lg">
                      <p className="text-gray-500">Teléfono</p>
                      <p className="font-medium">{c.telefono || "-"}</p>
                    </div>
                    <div className="bg-gray-50 p-4 rounded-lg">
                      <p className="text-gray-500">Email</p>
                      <p className="font-medium">{c.email || "-"}</p>
                    </div>
                    <div className="bg-gray-50 p-4 rounded-lg">
                      <p className="text-gray-500">Dirección</p>
                      <p className="font-medium">{c.direccion || "-"}</p>
                    </div>
                    <div className="bg-gray-50 p-4 rounded-lg">
                      <p className="text-gray-500">Crédito</p>
                      <p className="font-medium">S/ {Number(c.credito || 0).toFixed(2)}</p>
                    </div>
                    <div className="bg-gray-50 p-4 rounded-lg">
                      <p className="text-gray-500">Saldo</p>
                      <p className="font-medium">S/ {Number(c.saldo || 0).toFixed(2)}</p>
                    </div>
                    <div className="bg-gray-50 p-4 rounded-lg">
                      <p className="text-gray-500">Tipo</p>
                      <p className="font-medium">{c.tipo_cliente || "regular"}</p>
                    </div>
                  </div>

                  <div className="mt-4 flex gap-3 flex-wrap">
                    {c.dni_imagen && (
                      <a className="text-blue-600 underline" href={`http://127.0.0.1:8000/storage/${c.dni_imagen}`} target="_blank" rel="noreferrer">
                        Ver DNI
                      </a>
                    )}
                    {c.firma_imagen && (
                      <a className="text-blue-600 underline" href={`http://127.0.0.1:8000/storage/${c.firma_imagen}`} target="_blank" rel="noreferrer">
                        Ver firma
                      </a>
                    )}
                  </div>

                  <div className="mt-4 flex gap-2">
                    <button
                      onClick={() => editar(c)}
                      className="bg-yellow-400 hover:bg-yellow-500 px-3 py-2 rounded-lg flex items-center gap-2"
                    >
                      <Pencil size={16} /> Editar
                    </button>
                    <button
                      onClick={() => eliminar(c.id)}
                      className="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg flex items-center gap-2"
                    >
                      <Trash2 size={16} /> Eliminar
                    </button>
                  </div>
                </div>
              )}
            </div>
          );
        })}
      </div>

      {showModal && (
        <div className="fixed inset-0 bg-black/40 flex justify-center items-center z-50">
          <div className="bg-white w-full max-w-3xl rounded-2xl shadow-xl p-6 max-h-[90vh] overflow-y-auto">
            <h3 className="text-xl font-bold mb-4">
              {editando ? "Editar cliente" : "Nuevo cliente"}
            </h3>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <input
                className="border rounded-lg px-3 py-2"
                placeholder="Nombre"
                value={form.nombre}
                onChange={(e) => setForm({ ...form, nombre: e.target.value })}
              />
              <input
                className="border rounded-lg px-3 py-2"
                placeholder="Apodo"
                value={form.apodo}
                onChange={(e) => setForm({ ...form, apodo: e.target.value })}
              />
              <input
                className="border rounded-lg px-3 py-2"
                placeholder="DNI"
                value={form.dni}
                onChange={(e) => setForm({ ...form, dni: e.target.value })}
              />
              <input
                className="border rounded-lg px-3 py-2"
                placeholder="Teléfono"
                value={form.telefono}
                onChange={(e) => setForm({ ...form, telefono: e.target.value })}
              />
              <input
                className="border rounded-lg px-3 py-2"
                placeholder="Email"
                value={form.email}
                onChange={(e) => setForm({ ...form, email: e.target.value })}
              />
              <input
                className="border rounded-lg px-3 py-2"
                placeholder="Dirección"
                value={form.direccion}
                onChange={(e) => setForm({ ...form, direccion: e.target.value })}
              />
              <input
                type="number"
                min="0"
                step="0.01"
                className="border rounded-lg px-3 py-2"
                placeholder="Crédito"
                value={form.credito}
                onChange={(e) => setForm({ ...form, credito: e.target.value })}
              />
              <input
                type="number"
                min="0"
                step="0.01"
                className="border rounded-lg px-3 py-2"
                placeholder="Saldo"
                value={form.saldo}
                onChange={(e) => setForm({ ...form, saldo: e.target.value })}
              />
              <select
                className="border rounded-lg px-3 py-2"
                value={form.tipo_cliente}
                onChange={(e) => setForm({ ...form, tipo_cliente: e.target.value })}
              >
                <option value="regular">Regular</option>
                <option value="credito">Crédito</option>
                <option value="cuenta">Cuenta</option>
                <option value="anonimo">Anónimo</option>
              </select>

              <label className="border rounded-lg px-3 py-2 flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={form.activo}
                  onChange={(e) => setForm({ ...form, activo: e.target.checked })}
                />
                Activo
              </label>

              <div>
                <label className="block text-sm font-medium mb-2">DNI imagen</label>
                <input
                  type="file"
                  accept="image/*"
                  onChange={(e) => cambiarArchivo("dni_imagen", e.target.files?.[0])}
                />
                {previewDni && <img src={previewDni} alt="dni" className="mt-2 w-40 rounded-lg border" />}
              </div>

              <div>
                <label className="block text-sm font-medium mb-2">Firma escaneada</label>
                <input
                  type="file"
                  accept="image/*"
                  onChange={(e) => cambiarArchivo("firma_imagen", e.target.files?.[0])}
                />
                {previewFirma && <img src={previewFirma} alt="firma" className="mt-2 w-40 rounded-lg border" />}
              </div>
            </div>

            <div className="flex justify-end gap-2 mt-6">
              <button
                onClick={() => {
                  setShowModal(false);
                  reset();
                }}
                className="bg-gray-300 px-4 py-2 rounded-lg"
              >
                Cancelar
              </button>
              <button
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