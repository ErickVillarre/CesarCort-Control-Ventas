import { useEffect, useState } from "react";
import api from "./api/axios";
import toast from "react-hot-toast";
import { Pencil, Trash2, Plus } from "lucide-react";

const initialForm = {
  nombre: "",
  precio: "",
  stock: "",
  tipo: "",
  espesor: "18mm",
  canto_tipo: "",
  canto_ancho: "",
  color: "",
};

const detectarTipo = (producto = {}) => {
  if (producto.tipo) return producto.tipo;

  const nombre = (producto.nombre || "").toLowerCase();

  if (producto.espesor) return "melamina";
  if (producto.canto_tipo || producto.canto_ancho) return "canto";
  if (nombre.startsWith("melamina ")) return "melamina";
  if (nombre.startsWith("canto ") || nombre.includes("tapacanto")) return "canto";
  if (nombre.startsWith("servicio ")) return "servicio";
  if (nombre.startsWith("accesorio ")) return "accesorio";
  if (nombre.startsWith("medelack ")) return "medelack";
  if (producto.color) return "medelack";

  return "";
};

const prepararEdicion = (producto) => {
  const tipo = detectarTipo(producto);
  const nombre = producto.nombre || "";

  const base = {
    ...initialForm,
    tipo,
    precio: producto.precio ?? "",
    stock: producto.stock ?? "",
    espesor: producto.espesor || "18mm",
    canto_tipo: producto.canto_tipo || "",
    canto_ancho: producto.canto_ancho || "",
    color: producto.color || "",
    nombre: "",
  };

  if (tipo === "melamina") {
    base.nombre = nombre
      .replace(/^Melamina\s+/i, "")
      .replace(/\s+(18\s*mm|15\s*mm|18mm|15mm)\s*$/i, "")
      .trim();
    base.espesor = producto.espesor || "18mm";
  }

  if (tipo === "canto") {
    const sinPrefijo = nombre.replace(/^Canto\s+/i, "").trim();
    const partes = sinPrefijo.split(/\s+/);

    if (partes.length >= 3) {
      base.canto_ancho = partes.pop();
      base.canto_tipo = partes.pop();
      base.nombre = partes.join(" ");
    } else {
      base.nombre = sinPrefijo;
    }
  }

  if (tipo === "accesorio") {
    base.nombre = nombre.replace(/^Accesorio\s+/i, "").trim();
  }

  if (tipo === "servicio") {
    base.nombre = nombre.replace(/^Servicio\s+/i, "").trim();
  }

  if (tipo === "medelack") {
    base.color = producto.color || nombre.replace(/^Medelack\s+/i, "").trim();
  }

  if (!tipo) {
    base.nombre = nombre;
  }

  return base;
};

export default function Productos() {
  const user = JSON.parse(localStorage.getItem("user") || "null");

  const [productos, setProductos] = useState([]);
  const [busqueda, setBusqueda] = useState("");
  const [showModal, setShowModal] = useState(false);
  const [editando, setEditando] = useState(null);
  const [showDelete, setShowDelete] = useState(false);
  const [productoEliminar, setProductoEliminar] = useState(null);

  const [form, setForm] = useState(initialForm);

  const obtenerProductos = async () => {
    const res = await api.get("/productos");
    setProductos(res.data);
  };

  useEffect(() => {
    obtenerProductos();
  }, []);

  const resetForm = () => {
    setForm(initialForm);
  };

  const abrirNuevo = () => {
    if (user?.rol !== "admin") {
      toast.error("Solo el administrador puede crear");
      return;
    }

    setEditando(null);
    resetForm();
    setShowModal(true);
  };

  const handleTipoChange = (tipo) => {
    setForm((prev) => ({
      ...initialForm,
      tipo,
      precio: prev.precio,
      stock: tipo === "servicio" ? "" : prev.stock,
      espesor: tipo === "melamina" ? "18mm" : "18mm",
    }));
  };

  const handleEnter = (e) => {
    if (e.key !== "Enter") return;

    e.preventDefault();

    const formEl = e.currentTarget.form;
    if (!formEl) return;

    const fields = Array.from(
      formEl.querySelectorAll("input, select, textarea")
    ).filter((el) => !el.disabled && el.offsetParent !== null);

    const currentIndex = fields.indexOf(e.currentTarget);

    if (currentIndex >= 0 && currentIndex < fields.length - 1) {
      fields[currentIndex + 1].focus();
    } else {
      formEl.requestSubmit();
    }
  };

  const validarLocal = () => {
    if (!form.tipo) return "Seleccione tipo";

    if (form.tipo !== "medelack" && !form.nombre.trim()) {
      return "Ingrese el nombre";
    }

    if (form.tipo === "melamina" && !form.espesor) {
      return "Seleccione el espesor";
    }

    if (form.tipo === "canto" && (!form.canto_tipo || !form.canto_ancho)) {
      return "Complete tipo y medida del canto";
    }

    if (form.tipo === "medelack" && !form.color.trim()) {
      return "Ingrese color";
    }

    if (!form.precio && form.precio !== 0) {
      return "Ingrese precio";
    }

    if (form.tipo !== "servicio" && !form.stock && form.stock !== 0) {
      return "Ingrese stock";
    }

    return null;
  };

  const guardar = async () => {
    const error = validarLocal();
    if (error) {
      toast.error(error);
      return;
    }

    const data = {
      ...form,
      stock: form.tipo === "servicio" ? 0 : form.stock,
    };

    try {
      if (editando) {
        await api.put(`/productos/${editando.id}`, data);
        toast.success("Producto actualizado");
      } else {
        await api.post("/productos", data);
        toast.success("Producto creado");
      }

      setShowModal(false);
      setEditando(null);
      resetForm();
      obtenerProductos();
    } catch (error) {
      if (error.response?.status === 403) {
        toast.error("Solo el administrador puede realizar esta acción");
        return;
      }

      if (error.response?.status === 422) {
        const firstError =
          Object.values(error.response.data?.errors || {})?.flat()?.[0] ||
          error.response.data?.message ||
          "Error de validación";

        toast.error(firstError);
        return;
      }

      toast.error("Error al guardar");
    }
  };

  const editar = (p) => {
    if (user?.rol !== "admin") {
      toast.error("Solo el administrador puede editar");
      return;
    }

    setEditando(p);
    setForm(prepararEdicion(p));
    setShowModal(true);
  };

  const eliminar = async () => {
    try {
      await api.delete(`/productos/${productoEliminar.id}`);
      toast.success("Producto eliminado");
      setShowDelete(false);
      setProductoEliminar(null);
      obtenerProductos();
    } catch (error) {
      toast.error("Solo el administrador puede eliminar");
    }
  };

  const filtrados = productos.filter((p) =>
    p.nombre.toLowerCase().includes(busqueda.toLowerCase())
  );

  const mostrarStockEnFormulario = editando ? true : form.tipo !== "servicio";

  const placeholderNombre =
    form.tipo === "melamina"
      ? "Color"
      : form.tipo === "canto"
      ? "Color"
      : form.tipo === "servicio"
      ? "Nombre del servicio"
      : form.tipo === "accesorio"
      ? "Nombre"
      : "Nombre";

  return (
    <div>
      <h2 className="text-2xl font-bold mb-4">Productos</h2>

      <div className="flex justify-between mb-4">
        <input
          placeholder="Buscar..."
          className="border px-3 py-2 rounded w-1/3"
          value={busqueda}
          onChange={(e) => setBusqueda(e.target.value)}
        />

        <button
          onClick={abrirNuevo}
          className="bg-blue-400 hover:bg-blue-500 text-white px-4 py-2 rounded"
          title="Nuevo producto"
        >
          <Plus />
        </button>
      </div>

      <div className="bg-white rounded shadow max-h-[500px] overflow-y-auto">
        <table className="w-full">
          <thead className="bg-gray-100 text-left">
            <tr>
              <th className="p-2">Nombre</th>
              <th className="p-2">Precio</th>
              <th className="p-2">Stock</th>
              <th className="p-2">Acciones</th>
            </tr>
          </thead>

          <tbody>
            {filtrados.map((p) => (
              <tr key={p.id} className="border-t">
                <td className="p-2">{p.nombre}</td>
                <td className="p-2">S/ {p.precio}</td>
                <td className="p-2">{p.stock}</td>
                <td className="p-2 flex gap-2">
                  <button type="button" onClick={() => editar(p)} title="Editar">
                    <Pencil size={18} color="#eab308" />
                  </button>

                  <button
                    type="button"
                    onClick={() => {
                      if (user?.rol !== "admin") {
                        toast.error("Solo el administrador puede eliminar");
                        return;
                      }
                      setProductoEliminar(p);
                      setShowDelete(true);
                    }}
                    title="Eliminar"
                  >
                    <Trash2 size={18} color="red" />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {showDelete && (
        <div className="fixed inset-0 bg-black/40 flex justify-center items-center">
          <div className="bg-white p-6 rounded text-center w-80">
            <p className="mb-4">¿Eliminar producto?</p>

            <div className="flex gap-4 justify-center">
              <button
                type="button"
                onClick={() => {
                  setShowDelete(false);
                  setProductoEliminar(null);
                }}
                className="bg-gray-300 px-4 py-2 rounded"
              >
                Cancelar
              </button>

              <button
                type="button"
                onClick={eliminar}
                className="bg-red-600 text-white px-4 py-2 rounded"
              >
                Eliminar
              </button>
            </div>
          </div>
        </div>
      )}

      {showModal && (
        <div className="fixed inset-0 bg-black/40 flex justify-center items-center">
          <form
            className="bg-white p-6 rounded w-96"
            onSubmit={(e) => {
              e.preventDefault();
              guardar();
            }}
          >
            <h3 className="text-xl mb-4">
              {editando ? "Editar" : "Nuevo"} producto
            </h3>

            {!editando ? (
              <select
                className="border w-full mb-2 p-2"
                value={form.tipo}
                onChange={(e) => handleTipoChange(e.target.value)}
                onKeyDown={handleEnter}
              >
                <option value="">Seleccione tipo</option>
                <option value="melamina">Melamina</option>
                <option value="canto">Canto</option>
                <option value="accesorio">Accesorio</option>
                <option value="servicio">Servicio</option>
                <option value="medelack">Medelack</option>
              </select>
            ) : (
              <div className="text-sm text-gray-500 mb-2">
                Tipo: {form.tipo || "No detectado"}
              </div>
            )}

            {form.tipo !== "medelack" && (
              <input
                type="text"
                placeholder={placeholderNombre}
                className="border w-full mb-2 p-2"
                value={form.nombre}
                onChange={(e) =>
                  setForm({ ...form, nombre: e.target.value })
                }
                onKeyDown={handleEnter}
              />
            )}

            {form.tipo === "melamina" && (
              <select
                className="border w-full mb-2 p-2"
                value={form.espesor}
                onChange={(e) =>
                  setForm({ ...form, espesor: e.target.value })
                }
                onKeyDown={handleEnter}
              >
                <option value="18mm">18mm</option>
                <option value="15mm">15mm</option>
              </select>
            )}

            {form.tipo === "canto" && (
              <>
                <select
                  className="border w-full mb-2 p-2"
                  value={form.canto_tipo}
                  onChange={(e) =>
                    setForm({ ...form, canto_tipo: e.target.value })
                  }
                  onKeyDown={handleEnter}
                >
                  <option value="">Tipo</option>
                  <option value="grueso">Grueso</option>
                  <option value="delgado">Delgado</option>
                </select>

                <select
                  className="border w-full mb-2 p-2"
                  value={form.canto_ancho}
                  onChange={(e) =>
                    setForm({ ...form, canto_ancho: e.target.value })
                  }
                  onKeyDown={handleEnter}
                >
                  <option value="">Tipo de medida</option>
                  <option value="normal">Normal</option>
                  <option value="ancho">Ancho</option>
                </select>
              </>
            )}

            {form.tipo === "medelack" && (
              <input
                type="text"
                placeholder="Color"
                className="border w-full mb-2 p-2"
                value={form.color}
                onChange={(e) =>
                  setForm({ ...form, color: e.target.value })
                }
                onKeyDown={handleEnter}
              />
            )}

            <input
              type="number"
              step="0.01"
              min="0"
              placeholder="Precio"
              className="border w-full mb-2 p-2"
              value={form.precio}
              onChange={(e) =>
                setForm({ ...form, precio: e.target.value })
              }
              onKeyDown={handleEnter}
            />

            {mostrarStockEnFormulario && (
              <input
                type="number"
                step="1"
                min="0"
                placeholder="Stock"
                className="border w-full mb-2 p-2"
                value={form.stock}
                onChange={(e) =>
                  setForm({ ...form, stock: e.target.value })
                }
                onKeyDown={handleEnter}
              />
            )}

            <div className="flex justify-end gap-2 mt-3">
              <button
                type="button"
                onClick={() => {
                  setShowModal(false);
                  setEditando(null);
                }}
                className="bg-gray-300 px-3 py-1 rounded"
              >
                Cancelar
              </button>

              <button
                type="submit"
                className="bg-blue-400 hover:bg-blue-500 text-white px-3 py-1 rounded"
              >
                Guardar
              </button>
            </div>
          </form>
        </div>
      )}
    </div>
  );
}