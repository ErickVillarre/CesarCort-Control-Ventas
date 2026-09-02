/* eslint-disable react-hooks/set-state-in-effect */
import { useEffect, useMemo, useState } from "react";
import { AlertCircle, CheckCircle, Minus, PackageSearch, Plus, Send, Trash2, UserPlus } from "lucide-react";
import api from "./api/axios";
import toast from "react-hot-toast";

const can = (permission) => {
  const user = JSON.parse(localStorage.getItem("user") || "null");
  const permissions = user?.permissions || [];
  return permissions.includes("*") || permissions.includes(permission);
};

export default function Ventas() {
  const [products, setProducts] = useState([]);
  const [clients, setClients] = useState([]);
  const [query, setQuery] = useState("");
  const [clientQuery, setClientQuery] = useState("");
  const [client, setClient] = useState(null);
  const [cart, setCart] = useState([]);
  const [section, setSection] = useState("cliente");
  const [voucher, setVoucher] = useState("interno");
  const [paymentMethod, setPaymentMethod] = useState("efectivo");
  const [received, setReceived] = useState("");
  const [operationNumber, setOperationNumber] = useState("");
  const [discount, setDiscount] = useState(0);
  const [authRequest, setAuthRequest] = useState({ producto_id: "", precio_solicitado: "", motivo: "" });
  const [quickClient, setQuickClient] = useState({ nombre: "", dni: "", ruc: "", razon_social: "", telefono: "" });

  const canCharge = can("caja.cobrar");

  const load = async () => {
    const [p, c] = await Promise.all([api.get("/productos"), api.get("/clientes")]);
    setProducts(p.data || []);
    setClients(c.data || []);
    setClient((c.data || []).find((item) => item.tipo_cliente === "anonimo") || null);
  };

  useEffect(() => {
    load().catch(() => toast.error("No se pudo cargar ventas"));
  }, []);

  const filteredProducts = useMemo(() => {
    const q = query.toLowerCase();
    return products.filter((p) =>
      [p.codigo, p.codigo_barras, p.nombre, p.tipo, p.color, p.espesor]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(q))
    ).slice(0, 12);
  }, [products, query]);

  const filteredClients = useMemo(() => {
    const q = clientQuery.toLowerCase();
    return clients.filter((c) =>
      [c.nombre, c.apodo, c.dni, c.ruc, c.telefono, c.codigo_cliente]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(q))
    ).slice(0, 10);
  }, [clients, clientQuery]);

  const subtotal = cart.reduce((sum, item) => sum + Number(item.precio) * Number(item.cantidad), 0);
  const discounted = Math.max(0, subtotal - Number(discount || 0));
  const igv = discounted * 0.18;
  const total = discounted + igv;
  const change = paymentMethod === "efectivo" ? Math.max(0, Number(received || 0) - total) : 0;

  const addProduct = (product) => {
    if (Number(product.stock) <= 0) {
      toast.error("Producto agotado");
      return;
    }

    setCart((current) => {
      const found = current.find((item) => item.producto_id === product.id);
      if (found) {
        if (found.cantidad + 1 > Number(product.stock)) {
          toast.error("No puedes superar el stock disponible");
          return current;
        }
        return current.map((item) => item.producto_id === product.id ? { ...item, cantidad: item.cantidad + 1 } : item);
      }

      return [...current, { producto_id: product.id, nombre: product.nombre, codigo: product.codigo, precio: Number(product.precio), stock: Number(product.stock), cantidad: 1 }];
    });
  };

  const updateQty = (index, value) => {
    const qty = Number(value);
    setCart((current) => current.map((item, i) => {
      if (i !== index) return item;
      if (qty > item.stock) {
        toast.error("Cantidad mayor al stock");
        return item;
      }
      return { ...item, cantidad: Math.max(1, qty) };
    }));
  };

  const createQuickClient = async () => {
    try {
      const res = await api.post("/clientes", { ...quickClient, tipo_cliente: "regular" });
      toast.success("Cliente registrado");
      setClient(res.data);
      setQuickClient({ nombre: "", dni: "", ruc: "", razon_social: "", telefono: "" });
      load();
    } catch (error) {
      const first = Object.values(error.response?.data?.errors || {})?.flat()?.[0];
      toast.error(first || "No se pudo crear el cliente");
    }
  };

  const sendStockAlert = async (product, tipo = "stock_bajo") => {
    try {
      await api.post("/stock-alertas", {
        producto_id: product.id,
        tipo,
        cantidad_aproximada: 1,
        observacion: `Aviso desde punto de venta para ${product.nombre}`,
      });
      toast.success("Aviso enviado");
    } catch {
      toast.error("No se pudo enviar el aviso");
    }
  };

  const requestPriceAuth = async () => {
    try {
      await api.post("/ventas/autorizaciones-precio", authRequest);
      toast.success("Solicitud enviada a gerencia");
      setAuthRequest({ producto_id: "", precio_solicitado: "", motivo: "" });
    } catch (error) {
      const first = Object.values(error.response?.data?.errors || {})?.flat()?.[0];
      toast.error(first || "No se pudo solicitar autorizacion");
    }
  };

  const validateSale = () => {
    if (!client) return "Seleccione un cliente";
    if (cart.length === 0) return "Agregue productos al carrito";
    if (voucher === "boleta" && client.dni && client.dni.length !== 8) return "El DNI debe tener 8 digitos";
    if (voucher === "factura" && (!client.ruc || client.ruc.length !== 11 || !client.razon_social)) return "La factura requiere RUC y razon social";
    if (canCharge && paymentMethod === "efectivo" && Number(received || 0) < total) return "El monto recibido es insuficiente";
    return null;
  };

  const submitSale = async () => {
    const error = validateSale();
    if (error) {
      toast.error(error);
      return;
    }

    try {
      const res = await api.post("/ventas", {
        cliente_id: client.id,
        productos: cart.map((item) => ({ producto_id: item.producto_id, cantidad: item.cantidad, precio: item.precio })),
        metodo_pago: paymentMethod,
        tipo_operacion: paymentMethod === "credito" ? "prestamo" : paymentMethod === "dinero_cuenta" ? "cuenta" : paymentMethod,
        comprobante_tipo: voucher,
        monto_recibido: received,
        descuento: Number(discount || 0),
        finalizar: canCharge,
      });

      toast.success(canCharge ? "Venta finalizada" : "Preventa enviada a caja");
      setCart([]);
      setReceived("");
      setDiscount(0);

      if (canCharge && res.data?.id) {
        const pdf = await api.get(`/ventas/${res.data.id}/boleta`, { responseType: "blob" });
        window.open(URL.createObjectURL(new Blob([pdf.data], { type: "application/pdf" })), "_blank");
      }
    } catch (error) {
      toast.error(error.response?.data?.message || "No se pudo registrar la venta");
    }
  };

  return (
    <div className="space-y-5">
      <section className="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
        <div className="relative">
          <PackageSearch className="absolute left-3 top-3 text-zinc-400" size={18} />
          <input
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            className="w-full rounded border border-zinc-300 bg-zinc-50 py-3 pl-10 pr-3 outline-none focus:ring-2 focus:ring-zinc-500"
            placeholder="Buscar por codigo, nombre, tipo, color, espesor o codigo de barras"
          />
        </div>
        <div className="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-4">
          {filteredProducts.map((product) => (
            <button key={product.id} onClick={() => addProduct(product)} className="rounded border border-zinc-200 bg-zinc-50 p-3 text-left transition hover:bg-white hover:shadow">
              <p className="font-semibold">{product.nombre}</p>
              <p className="text-xs text-zinc-500">{product.codigo || "Sin codigo"} - Stock {product.stock}</p>
              <p className="mt-1 text-sm font-semibold">S/ {Number(product.precio).toFixed(2)}</p>
            </button>
          ))}
        </div>
      </section>

      <section className="grid gap-5 xl:grid-cols-[1fr_380px]">
        <div className="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
          <div className="border-b border-zinc-200 px-4 py-3 font-semibold">Carrito</div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-zinc-100 text-left text-zinc-600">
                <tr>
                  <th className="px-4 py-3">Producto</th>
                  <th className="px-4 py-3">Precio</th>
                  <th className="px-4 py-3">Cantidad</th>
                  <th className="px-4 py-3">Subtotal</th>
                  <th className="px-4 py-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {cart.map((item, index) => (
                  <tr key={item.producto_id} className="border-t border-zinc-100">
                    <td className="px-4 py-3">
                      <p className="font-medium">{item.nombre}</p>
                      <p className="text-xs text-zinc-500">Stock disponible: {item.stock}</p>
                    </td>
                    <td className="px-4 py-3">S/ {Number(item.precio).toFixed(2)}</td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        <button onClick={() => updateQty(index, item.cantidad - 1)} className="rounded border border-zinc-300 p-1"><Minus size={14} /></button>
                        <input type="number" min="1" max={item.stock} value={item.cantidad} onChange={(e) => updateQty(index, e.target.value)} className="w-16 rounded border border-zinc-300 px-2 py-1 text-center" />
                        <button onClick={() => updateQty(index, item.cantidad + 1)} className="rounded border border-zinc-300 p-1"><Plus size={14} /></button>
                      </div>
                    </td>
                    <td className="px-4 py-3">S/ {(item.precio * item.cantidad).toFixed(2)}</td>
                    <td className="px-4 py-3">
                      <div className="flex gap-2">
                        <button onClick={() => setCart(cart.filter((_, i) => i !== index))} className="rounded border border-zinc-300 p-2" title="Quitar"><Trash2 size={16} /></button>
                        <button onClick={() => sendStockAlert(item, item.stock === 0 ? "agotado" : "stock_bajo")} className="rounded border border-zinc-300 p-2" title="Avisar stock"><AlertCircle size={16} /></button>
                      </div>
                    </td>
                  </tr>
                ))}
                {cart.length === 0 && <tr><td colSpan="5" className="px-4 py-8 text-center text-zinc-500">Busca y agrega productos para empezar.</td></tr>}
              </tbody>
            </table>
          </div>
        </div>

        <aside className="space-y-3">
          <Accordion title="Cliente" active={section === "cliente"} onClick={() => setSection(section === "cliente" ? "" : "cliente")}>
            <input value={clientQuery} onChange={(e) => setClientQuery(e.target.value)} className="w-full rounded border border-zinc-300 px-3 py-2" placeholder="Nombre, DNI, RUC, telefono o codigo" />
            <div className="max-h-44 overflow-y-auto">
              {filteredClients.map((item) => (
                <button key={item.id} onClick={() => setClient(item)} className={`w-full rounded px-3 py-2 text-left text-sm hover:bg-zinc-100 ${client?.id === item.id ? "bg-zinc-100" : ""}`}>
                  {item.nombre} <span className="text-zinc-500">{item.dni || item.ruc || ""}</span>
                </button>
              ))}
            </div>
            {client && <div className="rounded border border-zinc-200 bg-zinc-50 p-3 text-sm">Seleccionado: <strong>{client.nombre}</strong><br />Credito: S/ {Number(client.credito || 0).toFixed(2)} - Cuenta: S/ {Number(client.saldo || 0).toFixed(2)}</div>}
            <details className="rounded border border-zinc-200 p-3">
              <summary className="cursor-pointer text-sm font-semibold"><UserPlus size={15} className="inline" /> Registrar cliente rapido</summary>
              <div className="mt-3 space-y-2">
                {["nombre", "dni", "ruc", "razon_social", "telefono"].map((field) => (
                  <input key={field} value={quickClient[field]} onChange={(e) => setQuickClient({ ...quickClient, [field]: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" placeholder={field.replaceAll("_", " ")} />
                ))}
                <button onClick={createQuickClient} className="rounded bg-zinc-900 px-3 py-2 text-sm text-white">Crear cliente</button>
              </div>
            </details>
          </Accordion>

          <Accordion title="Comprobante" active={section === "comprobante"} onClick={() => setSection(section === "comprobante" ? "" : "comprobante")}>
            <select value={voucher} onChange={(e) => setVoucher(e.target.value)} className="w-full rounded border border-zinc-300 px-3 py-2">
              <option value="boleta">Boleta</option>
              <option value="factura">Factura</option>
              <option value="interno">Comprobante interno</option>
            </select>
            <p className="text-xs text-zinc-500">La factura queda como pendiente de emision electronica si no existe integracion SUNAT.</p>
          </Accordion>

          <Accordion title="Pago" active={section === "pago"} onClick={() => setSection(section === "pago" ? "" : "pago")}>
            <select value={paymentMethod} onChange={(e) => setPaymentMethod(e.target.value)} className="w-full rounded border border-zinc-300 px-3 py-2">
              {["efectivo", "yape", "transferencia", "tarjeta", "credito", "dinero_cuenta", "mixto"].map((method) => <option key={method} value={method}>{method}</option>)}
            </select>
            {paymentMethod === "efectivo" && (
              <div className="grid grid-cols-2 gap-2">
                <input type="number" value={received} onChange={(e) => setReceived(e.target.value)} className="rounded border border-zinc-300 px-3 py-2" placeholder="Monto recibido" />
                <div className="rounded border border-zinc-200 bg-zinc-50 px-3 py-2">Vuelto: S/ {change.toFixed(2)}</div>
              </div>
            )}
            {paymentMethod === "yape" && (
              <div className="rounded border border-zinc-200 bg-zinc-50 p-3 text-sm">
                {import.meta.env.VITE_YAPE_QR_URL ? <img src={import.meta.env.VITE_YAPE_QR_URL} alt="QR Yape configurado" className="mx-auto max-h-40" /> : "QR pendiente de configurar."}
              </div>
            )}
            {["yape", "transferencia", "tarjeta"].includes(paymentMethod) && <input value={operationNumber} onChange={(e) => setOperationNumber(e.target.value)} className="w-full rounded border border-zinc-300 px-3 py-2" placeholder="Numero de operacion" />}
          </Accordion>

          <Accordion title="Autorizacion de precio" active={section === "autorizacion"} onClick={() => setSection(section === "autorizacion" ? "" : "autorizacion")}>
            <select value={authRequest.producto_id} onChange={(e) => setAuthRequest({ ...authRequest, producto_id: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2">
              <option value="">Producto</option>
              {cart.map((item) => <option key={item.producto_id} value={item.producto_id}>{item.nombre}</option>)}
            </select>
            <input type="number" step="0.01" value={authRequest.precio_solicitado} onChange={(e) => setAuthRequest({ ...authRequest, precio_solicitado: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" placeholder="Precio solicitado" />
            <textarea value={authRequest.motivo} onChange={(e) => setAuthRequest({ ...authRequest, motivo: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" placeholder="Motivo" />
            <button onClick={requestPriceAuth} className="rounded border border-zinc-300 px-3 py-2">Enviar solicitud</button>
          </Accordion>
        </aside>
      </section>

      <section className="sticky bottom-0 rounded-lg border border-zinc-200 bg-white p-4 shadow-xl">
        <div className="flex flex-col justify-between gap-3 md:flex-row md:items-center">
          <div className="grid grid-cols-3 gap-3 text-sm md:w-[520px]">
            <div><p className="text-zinc-500">Subtotal</p><strong>S/ {subtotal.toFixed(2)}</strong></div>
            <div><p className="text-zinc-500">Descuento</p><input type="number" min="0" step="0.01" value={discount} onChange={(e) => setDiscount(e.target.value)} className="mt-1 w-full rounded border border-zinc-300 px-2 py-1" /></div>
            <div><p className="text-zinc-500">Total</p><strong>S/ {total.toFixed(2)}</strong></div>
          </div>
          <button onClick={submitSale} className="flex items-center justify-center gap-2 rounded bg-zinc-900 px-5 py-3 font-semibold text-white hover:bg-zinc-700">
            {canCharge ? <CheckCircle size={18} /> : <Send size={18} />}
            {canCharge ? "Finalizar y cobrar" : "Enviar a caja"}
          </button>
        </div>
      </section>
    </div>
  );
}

function Accordion({ title, active, onClick, children }) {
  return (
    <div className="rounded-lg border border-zinc-200 bg-white shadow-sm">
      <button onClick={onClick} className="w-full px-4 py-3 text-left font-semibold hover:bg-zinc-50">{title}</button>
      {active && <div className="space-y-3 border-t border-zinc-100 p-4">{children}</div>}
    </div>
  );
}
