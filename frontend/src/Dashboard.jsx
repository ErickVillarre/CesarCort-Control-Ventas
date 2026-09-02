/* eslint-disable react-hooks/set-state-in-effect */
import { useEffect, useMemo, useState } from "react";
import {
  BadgeDollarSign,
  BarChart3,
  BriefcaseBusiness,
  CalendarDays,
  ChevronLeft,
  ChevronRight,
  ClipboardList,
  History,
  LayoutDashboard,
  LogOut,
  Megaphone,
  Menu,
  Package,
  Settings,
  ShoppingCart,
  UserCog,
  Users,
  Wrench,
} from "lucide-react";
import api from "./api/axios";
import Productos from "./Productos";
import Ventas from "./Ventas";
import Clientes from "./Clientes";
import Historial from "./Historial";
import Creditos from "./Creditos";
import AdminAccess from "./AdminAccess";
import OperationalModule from "./OperationalModule";
import Mantenimiento from "./Mantenimiento";
import Caja from "./Caja";
import Marketing from "./Marketing";
import Reportes from "./Reportes";
import toast from "react-hot-toast";

const hasPermission = (user, permission) => {
  const permissions = user?.permissions || [];
  return permissions.includes("*") || permissions.includes(permission);
};

const menuGroups = [
  {
    category: "Principal",
    items: [
      { name: "principal", label: "Principal", icon: LayoutDashboard, permission: "dashboard.ver" },
    ],
  },
  {
    category: "Comercial",
    items: [
      { name: "ventas", label: "Ventas", icon: ShoppingCart, permission: "ventas.crear" },
      { name: "clientes", label: "Clientes", icon: Users, permission: "clientes.ver" },
      { name: "historial", label: "Historial", icon: History, permission: "ventas.ver" },
    ],
  },
  {
    category: "Inventario",
    items: [
      { name: "productos", label: "Productos", icon: Package, permission: "productos.ver" },
    ],
  },
  {
    category: "Finanzas",
    items: [
      { name: "caja", label: "Caja", icon: BadgeDollarSign, permission: "caja.ver" },
      { name: "creditos", label: "Creditos", icon: BadgeDollarSign, permission: "creditos.ver" },
    ],
  },
  {
    category: "Administracion",
    items: [
      { name: "empleados", label: "Empleados y accesos", icon: UserCog, permission: "empleados.ver" },
      { name: "configuracion", label: "Configuracion", icon: Settings, permission: "usuarios.gestionar" },
    ],
  },
  {
    category: "Operaciones",
    items: [
      { name: "mantenimiento", label: "Mantenimiento", icon: Wrench, permission: "mantenimiento.ver" },
    ],
  },
  {
    category: "Marketing",
    items: [
      { name: "marketing", label: "Campanas y eventos", icon: Megaphone, permission: "marketing.ver" },
    ],
  },
  {
    category: "Reportes",
    items: [
      { name: "reportes", label: "Reportes", icon: BarChart3, permission: "reportes.ver" },
    ],
  },
  {
    category: "Personal",
    items: [
      { name: "asistencia", label: "Asistencia", icon: ClipboardList, permission: "asistencia.ver" },
    ],
  },
];

const formatMoney = (value) => `S/ ${Number(value || 0).toFixed(2)}`;

function Dashboard({ setIsAuth, onLogout }) {
  const [open, setOpen] = useState(localStorage.getItem("sidebar_open") === "1");
  const [menu, setMenu] = useState(localStorage.getItem("active_module") || "principal");
  const [showLogout, setShowLogout] = useState(false);
  const [dashboard, setDashboard] = useState(null);
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState(JSON.parse(localStorage.getItem("user") || "null"));

  const visibleGroups = useMemo(
    () =>
      menuGroups
        .map((group) => ({
          ...group,
          items: group.items.filter((item) => hasPermission(user, item.permission)),
        }))
        .filter((group) => group.items.length > 0),
    [user]
  );

  const flatMenus = visibleGroups.flatMap((group) => group.items);

  useEffect(() => {
    localStorage.setItem("sidebar_open", open ? "1" : "0");
  }, [open]);

  useEffect(() => {
    if (!flatMenus.some((item) => item.name === menu) && flatMenus[0]) {
      setMenu(flatMenus[0].name);
    }
  }, [flatMenus, menu]);

  useEffect(() => {
    localStorage.setItem("active_module", menu);
  }, [menu]);

  useEffect(() => {
    const boot = async () => {
      try {
        const [profile, data] = await Promise.all([api.get("/auth/me"), api.get("/dashboard")]);
        setUser(profile.data.user);
        localStorage.setItem("user", JSON.stringify(profile.data.user));
        setDashboard(data.data);
      } catch (error) {
        if (error.response?.status === 401) {
          localStorage.clear();
          setIsAuth(false);
          return;
        }

        toast.error(error.response?.data?.message || "No se pudo cargar el panel");
      } finally {
        setLoading(false);
      }
    };

    boot();
  }, [setIsAuth]);

  const logout = async () => {
    try {
      await api.post("/logout");
    } catch {
      // La salida local debe ocurrir aunque el token ya no exista en el backend.
    } finally {
      onLogout?.();
      setIsAuth(false);
    }
  };

  const today = new Date().toLocaleDateString("es-PE", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  const current = flatMenus.find((item) => item.name === menu) || flatMenus[0];

  const renderView = () => {
    switch (current?.name) {
      case "productos":
        return <Productos />;
      case "ventas":
        return <Ventas />;
      case "clientes":
        return <Clientes />;
      case "historial":
        return <Historial />;
      case "caja":
        return <Caja />;
      case "creditos":
        return <Creditos />;
      case "empleados":
        return <AdminAccess />;
      case "mantenimiento":
        return <Mantenimiento />;
      case "marketing":
        return <Marketing />;
      case "reportes":
        return <Reportes />;
      case "asistencia":
        return <OperationalModule title="Asistencia" subtitle="Control del personal" icon={ClipboardList} rows={["Empleados activos", "Asistencia", "Observaciones"]} />;
      case "configuracion":
        return <OperationalModule title="Configuracion" subtitle="Roles, permisos y accesos" icon={Settings} rows={["Roles", "Permisos", "Usuarios activos"]} />;
      default:
        return <DashboardHome dashboard={dashboard} user={user} today={today} loading={loading} setMenu={setMenu} />;
    }
  };

  return (
    <div className="min-h-screen bg-zinc-100 text-zinc-900 lg:flex">
      <aside className={`fixed inset-y-0 left-0 z-30 flex flex-col bg-zinc-900 text-zinc-100 shadow-2xl transition-all duration-300 lg:sticky ${open ? "w-72" : "w-20"}`}>
        <div className="flex h-20 items-center justify-between border-b border-zinc-800 px-4">
          <div className={`flex items-center gap-3 overflow-hidden ${open ? "opacity-100" : "opacity-0 w-0"}`}>
            <img src="/logo.png" alt="Logo" className="h-11 w-11 rounded bg-zinc-100 object-contain p-1" />
            <div className="min-w-0">
              <p className="truncate font-semibold">CesarControl</p>
              <p className="truncate text-xs text-zinc-400">Gestion empresarial</p>
            </div>
          </div>
          {!open && <img src="/logo.png" alt="Logo" className="mx-auto h-10 w-10 rounded bg-zinc-100 object-contain p-1" />}
          <button
            type="button"
            className="rounded p-2 text-zinc-300 hover:bg-zinc-800 hover:text-white"
            onClick={() => setOpen((value) => !value)}
            title={open ? "Contraer menu" : "Expandir menu"}
          >
            {open ? <ChevronLeft size={20} /> : <ChevronRight size={20} />}
          </button>
        </div>

        <nav className="flex-1 space-y-4 overflow-y-auto px-3 py-5">
          {visibleGroups.map((group) => (
            <div key={group.category}>
              {open && <p className="mb-2 px-3 text-xs font-semibold uppercase text-zinc-500">{group.category}</p>}
              <div className="space-y-1">
                {group.items.map((item) => {
                  const Icon = item.icon;
                  const active = current?.name === item.name;

                  return (
                    <button
                      key={item.name}
                      onClick={() => setMenu(item.name)}
                      className={`flex h-11 w-full items-center gap-3 rounded px-3 text-left text-sm transition ${active ? "bg-zinc-100 text-zinc-950 shadow" : "text-zinc-300 hover:bg-zinc-800 hover:text-white"}`}
                      title={item.label}
                    >
                      <Icon size={20} />
                      {open && <span className="truncate">{item.label}</span>}
                    </button>
                  );
                })}
              </div>
            </div>
          ))}
        </nav>

        <div className="border-t border-zinc-800 p-3">
          <button
            onClick={() => setShowLogout(true)}
            className="flex h-11 w-full items-center justify-center gap-3 rounded bg-zinc-800 text-zinc-100 transition hover:bg-zinc-700"
            title="Cerrar sesion"
          >
            <LogOut size={18} />
            {open && <span>Cerrar sesion</span>}
          </button>
        </div>
      </aside>

      <main className="min-h-screen flex-1 pl-20 lg:pl-0">
        <header className="sticky top-0 z-20 flex min-h-20 items-center justify-between border-b border-zinc-200 bg-zinc-50/95 px-4 py-4 backdrop-blur md:px-6">
          <div className="flex items-center gap-3">
            <button
              type="button"
              className="rounded p-2 text-zinc-600 hover:bg-zinc-200 lg:hidden"
              onClick={() => setOpen((value) => !value)}
              title="Menu"
            >
              <Menu size={20} />
            </button>
            <div>
              <h1 className="text-xl font-semibold md:text-2xl">{current?.label || "Principal"}</h1>
              <p className="text-sm text-zinc-500">{today}</p>
            </div>
          </div>

          <div className="hidden items-center gap-3 rounded border border-zinc-200 bg-white px-3 py-2 md:flex">
            <BriefcaseBusiness size={18} className="text-zinc-500" />
            <div className="text-right">
              <p className="text-sm font-semibold">{user?.name || "Usuario"}</p>
              <p className="text-xs text-zinc-500">{user?.role?.label || user?.role_name || user?.rol}</p>
            </div>
          </div>
        </header>

        <div className="p-4 md:p-6">{renderView()}</div>
      </main>

      {showLogout && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
          <div className="w-full max-w-sm rounded-lg border border-zinc-200 bg-zinc-50 p-6 text-center shadow-2xl">
            <h2 className="text-lg font-semibold">Cerrar sesion</h2>
            <p className="mt-2 text-sm text-zinc-500">Se cerrara tu sesion actual.</p>
            <div className="mt-5 flex justify-center gap-3">
              <button onClick={() => setShowLogout(false)} className="rounded border border-zinc-300 px-4 py-2 hover:bg-zinc-100">
                Cancelar
              </button>
              <button onClick={logout} className="rounded bg-zinc-900 px-4 py-2 text-white hover:bg-zinc-700">
                Salir
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

function DashboardHome({ dashboard, user, today, loading, setMenu }) {
  const metrics = dashboard?.metrics || {};
  const max = Math.max(...(dashboard?.chart || []).map((item) => Number(item.total)), 1);
  const canOpenSales = hasPermission(user, "ventas.crear");
  const canOpenClients = hasPermission(user, "clientes.ver");

  const cardsByScope = {
    vendedor: [
      ["Ventas hoy", formatMoney(metrics.ventas_hoy), "Tus operaciones del dia"],
      ["Ventas mes", formatMoney(metrics.ventas_mes), `Meta: ${formatMoney(metrics.meta_ventas)}`],
      ["Avance meta", `${metrics.avance_meta || 0}%`, "Seguimiento comercial"],
      ["Pendientes de cobro", metrics.ventas_pendientes_cobro, "En cola de caja"],
    ],
    caja: [
      ["Caja abierta", metrics.caja_abierta ? "Si" : "No", "Estado actual"],
      ["Preventas", metrics.preventas_pendientes, "Pendientes de cobro"],
      ["Efectivo", formatMoney(metrics.efectivo), "No mezcla medios digitales"],
      ["Validaciones", metrics.pagos_pendientes_validacion, "Yape o transferencias"],
    ],
    mantenimiento: [
      ["Operativas", metrics.maquinarias_operativas, "Maquinas disponibles"],
      ["Detenidas", metrics.maquinarias_detenidas, "Atencion requerida"],
      ["Fallas abiertas", metrics.fallas_abiertas, "Incidencias activas"],
      ["Repuestos bajos", metrics.repuestos_stock_bajo, "Inventario interno"],
    ],
    marketing: [
      ["Mas vendidos", metrics.productos_mas_vendidos, "Tendencia comercial"],
      ["Baja rotacion", metrics.productos_baja_rotacion, "Oportunidad de campana"],
      ["Clientes nuevos", metrics.clientes_nuevos, "Este mes"],
      ["Publicaciones", metrics.publicaciones_pendientes, "Pendientes"],
    ],
    recursos_humanos: [
      ["Empleados activos", metrics.empleados_activos, "Personal vigente"],
      ["Faltas mes", metrics.faltas_mes, "Asistencia"],
      ["Tardanzas mes", metrics.tardanzas_mes, "Control horario"],
      ["Permisos pendientes", metrics.permisos_pendientes, "Revision"],
    ],
    gerencia: [
      ["Ventas hoy", formatMoney(metrics.ventas_hoy), "Comercial"],
      ["Ventas mes", formatMoney(metrics.ventas_mes), "Comparativo operativo"],
      ["Pendientes caja", metrics.ventas_pendientes_caja, "Cobro por confirmar"],
      ["Fallas criticas", metrics.fallas_criticas, "Mantenimiento"],
    ],
  };

  const cards = cardsByScope[dashboard?.scope] || cardsByScope.gerencia;

  if (loading) {
    return (
      <div className="grid gap-4 md:grid-cols-4">
        {cards.map(([label]) => (
          <div key={label} className="h-28 animate-pulse rounded-lg border border-zinc-200 bg-zinc-200" />
        ))}
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <section className="rounded-lg border border-zinc-200 bg-zinc-50 p-5 shadow-sm">
        <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
          <div>
            <p className="text-sm text-zinc-500">{today}</p>
            <h2 className="mt-1 text-2xl font-semibold">Bienvenido, {user?.name || "usuario"}</h2>
            <p className="mt-1 text-sm text-zinc-500">{user?.role?.label || user?.role_name || "Rol asignado"}</p>
          </div>
          {(canOpenSales || canOpenClients) && (
            <div className="flex flex-wrap gap-2">
              {canOpenSales && (
                <button onClick={() => setMenu("ventas")} className="rounded bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700">
                  Nueva venta
                </button>
              )}
              {canOpenClients && (
                <button onClick={() => setMenu("clientes")} className="rounded border border-zinc-300 px-4 py-2 text-sm font-semibold hover:bg-white">
                  Clientes
                </button>
              )}
            </div>
          )}
        </div>
      </section>

      <section className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        {cards.map(([label, value, caption]) => (
          <div key={label} className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <p className="text-sm text-zinc-500">{label}</p>
            <h3 className="mt-2 text-3xl font-semibold">{value ?? 0}</h3>
            <p className="mt-1 text-sm text-zinc-400">{caption}</p>
          </div>
        ))}
      </section>

      <section className="grid grid-cols-1 gap-4 xl:grid-cols-[1.2fr_0.8fr]">
        <div className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
          <div className="mb-4 flex items-center justify-between">
            <h3 className="font-semibold">Ventas ultimos 7 dias</h3>
            <CalendarDays size={18} className="text-zinc-500" />
          </div>
          <div className="flex h-56 items-end gap-3">
            {(dashboard?.chart || []).map((item) => (
              <div key={item.fecha} className="flex h-full flex-1 flex-col justify-end gap-2">
                <div
                  className="min-h-2 rounded-t bg-zinc-700 transition hover:bg-zinc-900"
                  style={{ height: `${Math.max((Number(item.total) / max) * 100, 4)}%` }}
                  title={`${item.fecha}: ${formatMoney(item.total)}`}
                />
                <span className="text-center text-[11px] text-zinc-500">{item.fecha.slice(5)}</span>
              </div>
            ))}
          </div>
        </div>

        <div className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
          <h3 className="font-semibold">Actividad reciente</h3>
          <div className="mt-4 space-y-3">
            {(dashboard?.recent_sales || []).map((venta) => (
              <div key={venta.id} className="flex items-center justify-between border-b border-zinc-100 pb-3 last:border-0">
                <div>
                  <p className="font-medium">{venta.cliente?.nombre || venta.cliente || "Registro"}</p>
                  <p className="text-xs text-zinc-500">{new Date(venta.fecha || venta.created_at).toLocaleString("es-PE")}</p>
                </div>
                <span className="text-sm font-semibold">{formatMoney(venta.total)}</span>
              </div>
            ))}
            {(dashboard?.recent_sales || []).length === 0 && <p className="text-sm text-zinc-500">Sin ventas recientes.</p>}
          </div>
        </div>
      </section>

      <section className="grid gap-3 md:grid-cols-2">
        {(dashboard?.alerts || []).map((alert) => (
          <div key={alert.message} className="rounded border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700">
            {alert.message}
          </div>
        ))}
      </section>
    </div>
  );
}

export default Dashboard;
