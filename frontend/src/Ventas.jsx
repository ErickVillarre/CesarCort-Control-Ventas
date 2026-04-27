import { useEffect, useMemo, useState } from "react";
import api from "./api/axios";
import toast from "react-hot-toast";
import {
  Search,
  Plus,
  Minus,
  Trash2,
  Pencil,
  ShieldCheck,
  UserRound,
  ShoppingCart,
} from "lucide-react";

const emptyAuth = { email: "", password: "" };

export default function Ventas() {
  const [productos, setProductos] = useState([]);
  const [clientes, setClientes] = useState([]);
  const [creditos, setCreditos] = useState([]);

  const [buscarProducto, setBuscarProducto] = useState("");
  const [buscarCliente, setBuscarCliente] = useState("");
  const [clienteSeleccionado, setClienteSeleccionado] = useState(null);

  const [carrito, setCarrito] = useState([]);
  const [metodoPago, setMetodoPago] = useState("efectivo");
  const [tipoOperacion, setTipoOperacion] = useState("contado");
  const [montoRecibido, setMontoRecibido] = useState("");

  const [showAuth, setShowAuth] = useState(false);
  const [auth, setAuth] = useState(emptyAuth);
  const [precioEditable, setPrecioEditable] = useState(false);

  useEffect(() => {
    cargarDatos();
  }, []);

  useEffect(() => {
    if (clienteSeleccionado && metodoPago === "credito") {
      const creditoActivo = creditos.find(
        (c) =>
          c.cliente_id === clienteSeleccionado.id && c.estado === "activo"
      );

      if (creditoActivo) {
        const hoy = new Date();
        const venc = creditoActivo.fecha_vencimiento
          ? new Date(creditoActivo.fecha_vencimiento)
          : null;

        if (venc) {
          const diff = Math.ceil((venc - hoy) / (1000 * 60 * 60 * 24));
          if (diff <= 5 && diff >= 0) {
            toast.error(
              `Crédito próximo a vencer para ${clienteSeleccionado.nombre}`
            );
          }
        }
      }
    }
  }, [clienteSeleccionado, metodoPago, creditos]);

  const cargarDatos = async () => {
    try {
      const [p, c, cr] = await Promise.all([
        api.get("/productos"),
        api.get("/clientes"),
        api.get("/creditos"),
      ]);
      setProductos(p.data || []);
      setClientes(c.data || []);
      setCreditos(cr.data || []);
    } catch {
      toast.error("No se pudo cargar la información de ventas");
    }
  };

  const subtotal = useMemo(
    () =>
      carrito.reduce(
        (acc, item) => acc + Number(item.precio || 0) * Number(item.cantidad || 0),
        0
      ),
    [carrito]
  );

  const igv = useMemo(() => subtotal * 0.18, [subtotal]);
  const total = useMemo(() => subtotal + igv, [subtotal, igv]);

  const vuelto = useMemo(() => {
    if (metodoPago !== "efectivo") return 0;
    const pago = Number(montoRecibido || 0);
    return pago - total > 0 ? pago - total : 0;
  }, [metodoPago, montoRecibido, total]);

  const productosFiltrados = productos.filter((p) =>
    p.nombre.toLowerCase().includes(buscarProducto.toLowerCase())
  );

  const clientesFiltrados = clientes.filter((c) => {
    const q = buscarCliente.toLowerCase();
    return (
      c.nombre?.toLowerCase().includes(q) ||
      c.apodo?.toLowerCase().includes(q) ||
      c.dni?.toLowerCase().includes(q)
    );
  });

  const creditoActivo = useMemo(() => {
    if (!clienteSeleccionado) return null;
    return creditos.find(
      (c) => c.cliente_id === clienteSeleccionado.id && c.estado === "activo"
    );
  }, [clienteSeleccionado, creditos]);

  const agregarProducto = (producto) => {
    setCarrito((prev) => {
      const yaExiste = prev.find((x) => x.producto_id === producto.id);

      if (yaExiste) {
        return prev.map((x) =>
          x.producto_id === producto.id
            ? { ...x, cantidad: Number(x.cantidad) + 1 }
            : x
        );
      }

      return [
        ...prev,
        {
          producto_id: producto.id,
          nombre: producto.nombre,
          precio: Number(producto.precio),
          stock: Number(producto.stock),
          cantidad: 1,
        },
      ];
    });
  };

  const actualizarCantidad = (index, cantidad) => {
    if (cantidad < 1) return;

    setCarrito((prev) =>
      prev.map((item, i) =>
        i === index ? { ...item, cantidad: Number(cantidad) } : item
      )
    );
  };

  const actualizarPrecio = (index, precio) => {
    if (Number(precio) < 0) return;

    setCarrito((prev) =>
      prev.map((item, i) =>
        i === index ? { ...item, precio: Number(precio) } : item
      )
    );
  };

  const eliminarItem = (index) => {
    setCarrito((prev) => prev.filter((_, i) => i !== index));
  };

  const pedirAutorizacionPrecio = () => {
    setAuth(emptyAuth);
    setShowAuth(true);
  };

  const validarAuth = async () => {
    try {
      await api.post("/login", {
        email: auth.email,
        password: auth.password,
      });

      setPrecioEditable(true);
      setShowAuth(false);
      toast.success("Autorización concedida");
    } catch {
      toast.error("Credenciales incorrectas");
    }
  };

  const realizarVenta = async () => {
    if (!clienteSeleccionado) {
      toast.error("Seleccione un cliente o cliente anónimo");
      return;
    }

    if (carrito.length === 0) {
      toast.error("Agregue al menos un producto");
      return;
    }

    if (metodoPago === "credito" && clienteSeleccionado.tipo_cliente === "anonimo") {
      toast.error("El crédito requiere un cliente registrado");
      return;
    }

    if (metodoPago === "efectivo" && Number(montoRecibido || 0) < total) {
      toast.error("El monto recibido es insuficiente");
      return;
    }

    const payload = {
      cliente_id: clienteSeleccionado.id,
      productos: carrito.map((item) => ({
        producto_id: item.producto_id,
        cantidad: item.cantidad,
        precio: item.precio,
      })),
      metodo_pago: metodoPago,
      tipo_operacion: tipoOperacion,
      monto_recibido: metodoPago === "efectivo" ? Number(montoRecibido || 0) : 0,
    };

    try {
      const res = await api.post("/ventas", payload);
      toast.success("Venta realizada correctamente");

      const ventaId = res.data?.id;
      setCarrito([]);
      setMontoRecibido("");
      setMetodoPago("efectivo");
      setTipoOperacion("contado");
      setPrecioEditable(false);

      if (ventaId) {
        window.open(`http://127.0.0.1:8000/api/ventas/${ventaId}/boleta`, "_blank");
      }

      await cargarDatos();
    } catch (error) {
      const msg = error.response?.data?.message || "No se pudo completar la venta";
      toast.error(msg);
    }
  };

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-xl shadow p-5">
        <h2 className="text-xl font-bold mb-4">Nueva venta</h2>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium mb-2">Buscar producto</label>
              <div className="relative">
                <Search className="absolute left-3 top-3 text-gray-400" size={16} />
                <input
                  value={buscarProducto}
                  onChange={(e) => setBuscarProducto(e.target.value)}
                  placeholder="Escribe para buscar..."
                  className="w-full border rounded-lg pl-10 pr-3 py-2"
                />
              </div>

              <div className="mt-3 max-h-56 overflow-y-auto border rounded-lg">
                {productosFiltrados.map((p) => (
                  <button
                    key={p.id}
                    type="button"
                    onClick={() => agregarProducto(p)}
                    className="w-full text-left px-3 py-2 hover:bg-gray-100 flex justify-between"
                  >
                    <span>{p.nombre}</span>
                    <span className="text-sm text-gray-500">S/ {p.precio} · Stock {p.stock}</span>
                  </button>
                ))}
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium mb-2">Buscar cliente</label>
              <input
                value={buscarCliente}
                onChange={(e) => setBuscarCliente(e.target.value)}
                placeholder="Nombre, apodo o DNI"
                className="w-full border rounded-lg px-3 py-2"
              />

              <div className="mt-3 max-h-56 overflow-y-auto border rounded-lg">
                <button
                  type="button"
                  onClick={() =>
                    setClienteSeleccionado({
                      id: clientes.find((c) => c.tipo_cliente === "anonimo")?.id || null,
                      nombre: "Cliente Anónimo",
                      tipo_cliente: "anonimo",
                    })
                  }
                  className={`w-full text-left px-3 py-2 hover:bg-gray-100 flex justify-between ${
                    clienteSeleccionado?.tipo_cliente === "anonimo" ? "bg-gray-100" : ""
                  }`}
                >
                  <span>Cliente Anónimo</span>
                  <span className="text-sm text-gray-500">Combo box</span>
                </button>

                {clientesFiltrados.map((c) => (
                  <button
                    key={c.id}
                    type="button"
                    onClick={() => setClienteSeleccionado(c)}
                    className={`w-full text-left px-3 py-2 hover:bg-gray-100 flex justify-between ${
                      clienteSeleccionado?.id === c.id ? "bg-gray-100" : ""
                    }`}
                  >
                    <span>{c.nombre}{c.apodo ? ` · ${c.apodo}` : ""}</span>
                    <span className="text-sm text-gray-500">{c.dni || "Sin DNI"}</span>
                  </button>
                ))}
              </div>

              {clienteSeleccionado && (
                <div className="mt-3 rounded-lg border p-3 bg-gray-50">
                  <p className="font-semibold">{clienteSeleccionado.nombre}</p>
                  {creditoActivo && (
                    <div className="text-sm text-gray-600 mt-1">
                      <p>Tipo: {creditoActivo.tipo}</p>
                      <p>
                        Límite: S/ {creditoActivo.limite} | Saldo actual: S/ {creditoActivo.saldo_actual}
                      </p>
                      <p>
                        Vence: {creditoActivo.fecha_vencimiento || "Sin fecha"}
                      </p>
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>

          <div className="space-y-4">
            <div className="grid grid-cols-3 gap-3 text-sm">
              <div className="bg-gray-50 p-3 rounded-lg">
                <p className="text-gray-500">Subtotal</p>
                <p className="font-bold">S/ {subtotal.toFixed(2)}</p>
              </div>
              <div className="bg-gray-50 p-3 rounded-lg">
                <p className="text-gray-500">IGV 18%</p>
                <p className="font-bold">S/ {igv.toFixed(2)}</p>
              </div>
              <div className="bg-gray-50 p-3 rounded-lg">
                <p className="text-gray-500">Total</p>
                <p className="font-bold">S/ {total.toFixed(2)}</p>
              </div>
            </div>

            <div className="flex items-center justify-between">
              <h3 className="font-semibold flex items-center gap-2">
                <ShoppingCart size={18} /> Carrito
              </h3>
              <button
                type="button"
                onClick={pedirAutorizacionPrecio}
                className="text-sm bg-yellow-400 hover:bg-yellow-500 px-3 py-2 rounded-lg flex items-center gap-2"
              >
                <ShieldCheck size={16} /> Autorizar precio
              </button>
            </div>

            <div className="max-h-[420px] overflow-y-auto border rounded-lg">
              <table className="w-full text-sm">
                <thead className="bg-gray-100 sticky top-0">
                  <tr>
                    <th className="p-2 text-left">#</th>
                    <th className="p-2 text-left">Producto</th>
                    <th className="p-2 text-left">Precio unitario</th>
                    <th className="p-2 text-left">Cantidad</th>
                    <th className="p-2 text-left">Total</th>
                    <th className="p-2 text-left">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  {carrito.map((item, index) => (
                    <tr key={`${item.producto_id}-${index}`} className="border-t">
                      <td className="p-2">{index + 1}</td>
                      <td className="p-2">{item.nombre}</td>
                      <td className="p-2">
                        <div className="flex items-center gap-2">
                          {precioEditable ? (
                            <input
                              type="number"
                              step="0.01"
                              min="0"
                              value={item.precio}
                              onChange={(e) => actualizarPrecio(index, e.target.value)}
                              className="border rounded px-2 py-1 w-28"
                            />
                          ) : (
                            <span>S/ {Number(item.precio).toFixed(2)}</span>
                          )}
                        </div>
                      </td>
                      <td className="p-2">
                        <div className="flex items-center gap-2">
                          <button
                            type="button"
                            onClick={() => actualizarCantidad(index, item.cantidad - 1)}
                            className="p-1 rounded bg-gray-200"
                          >
                            <Minus size={14} />
                          </button>
                          <input
                            type="number"
                            min="1"
                            value={item.cantidad}
                            onChange={(e) => actualizarCantidad(index, e.target.value)}
                            className="border rounded px-2 py-1 w-20 text-center"
                          />
                          <button
                            type="button"
                            onClick={() => actualizarCantidad(index, item.cantidad + 1)}
                            className="p-1 rounded bg-gray-200"
                          >
                            <Plus size={14} />
                          </button>
                        </div>
                      </td>
                      <td className="p-2">S/ {(item.precio * item.cantidad).toFixed(2)}</td>
                      <td className="p-2">
                        <button
                          type="button"
                          onClick={() => eliminarItem(index)}
                          className="text-red-500"
                          title="Eliminar"
                        >
                          <Trash2 size={16} />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div>
                <label className="block text-sm font-medium mb-2">Método de pago</label>
                <select
                  value={metodoPago}
                  onChange={(e) => {
                    setMetodoPago(e.target.value);
                    setTipoOperacion(
                      e.target.value === "efectivo"
                        ? "contado"
                        : e.target.value === "tarjeta"
                        ? "tarjeta"
                        : "prestamo"
                    );
                  }}
                  className="w-full border rounded-lg px-3 py-2"
                >
                  <option value="efectivo">Efectivo</option>
                  <option value="tarjeta">Tarjeta</option>
                  <option value="credito">Crédito</option>
                </select>
              </div>

              {metodoPago === "tarjeta" && (
                <div>
                  <label className="block text-sm font-medium mb-2">Tipo de operación</label>
                  <select
                    value={tipoOperacion}
                    onChange={(e) => setTipoOperacion(e.target.value)}
                    className="w-full border rounded-lg px-3 py-2"
                  >
                    <option value="tarjeta">Tarjeta</option>
                    <option value="yape">Yape</option>
                    <option value="transferencia">Transferencia</option>
                  </select>
                </div>
              )}

              {metodoPago === "credito" && (
                <div>
                  <label className="block text-sm font-medium mb-2">Tipo de crédito</label>
                  <select
                    value={tipoOperacion}
                    onChange={(e) => setTipoOperacion(e.target.value)}
                    className="w-full border rounded-lg px-3 py-2"
                  >
                    <option value="prestamo">Crédito (préstamo)</option>
                    <option value="cuenta">A su cuenta</option>
                  </select>
                </div>
              )}

              {metodoPago === "efectivo" && (
                <div>
                  <label className="block text-sm font-medium mb-2">Monto recibido</label>
                  <input
                    type="number"
                    min="0"
                    step="0.01"
                    value={montoRecibido}
                    onChange={(e) => setMontoRecibido(e.target.value)}
                    className="w-full border rounded-lg px-3 py-2"
                    placeholder="S/ 0.00"
                  />
                </div>
              )}
            </div>

            {metodoPago === "efectivo" && (
              <div className="bg-blue-50 border rounded-lg p-3">
                <p className="font-medium">Vuelto: S/ {vuelto.toFixed(2)}</p>
              </div>
            )}

            <div className="flex justify-end">
              <button
                type="button"
                onClick={realizarVenta}
                className="bg-blue-400 hover:bg-blue-500 text-white px-5 py-3 rounded-lg font-semibold"
              >
                Realizar venta
              </button>
            </div>
          </div>
        </div>
      </div>

      {showAuth && (
        <div className="fixed inset-0 bg-black/40 flex justify-center items-center z-50">
          <div className="bg-white p-6 rounded-xl w-full max-w-sm shadow-xl">
            <h3 className="font-bold text-lg mb-4">Autorizar modificación de precio</h3>

            <input
              type="email"
              placeholder="Correo"
              value={auth.email}
              onChange={(e) => setAuth({ ...auth, email: e.target.value })}
              className="w-full border rounded-lg px-3 py-2 mb-3"
            />

            <input
              type="password"
              placeholder="Contraseña"
              value={auth.password}
              onChange={(e) => setAuth({ ...auth, password: e.target.value })}
              className="w-full border rounded-lg px-3 py-2 mb-4"
            />

            <div className="flex justify-end gap-2">
              <button
                type="button"
                onClick={() => setShowAuth(false)}
                className="bg-gray-300 px-4 py-2 rounded-lg"
              >
                Cancelar
              </button>
              <button
                type="button"
                onClick={validarAuth}
                className="bg-blue-400 hover:bg-blue-500 text-white px-4 py-2 rounded-lg"
              >
                Validar
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}