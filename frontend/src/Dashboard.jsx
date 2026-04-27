import { useEffect, useState } from "react";
import {
  Menu,
  LayoutDashboard,
  Package,
  ShoppingCart,
  History,
  Users,
  BadgeDollarSign,
  LogOut,
} from "lucide-react";
import api from "./api/axios";
import Productos from "./Productos";
import Ventas from "./Ventas";
import Clientes from "./Clientes";
import Historial from "./Historial";
import Creditos from "./Creditos";
import toast from "react-hot-toast";

function Dashboard({ setIsAuth }) {
  const [open, setOpen] = useState(false);
  const [menu, setMenu] = useState("principal");
  const [showLogout, setShowLogout] = useState(false);
  const [stats, setStats] = useState({
    productos: 0,
    clientes: 0,
    ventas: 0,
    creditos: 0,
  });

  const user = JSON.parse(localStorage.getItem("user") || "null");

  const isAdmin = user?.rol === "admin";

  const menus = [
    { name: "principal", label: "Principal", icon: <LayoutDashboard size={20} /> },
    { name: "productos", label: "Productos", icon: <Package size={20} /> },
    { name: "ventas", label: "Ventas", icon: <ShoppingCart size={20} /> },
    { name: "clientes", label: "Clientes", icon: <Users size={20} /> },
    { name: "historial", label: "Historial", icon: <History size={20} /> },
    ...(isAdmin
      ? [{ name: "creditos", label: "Créditos", icon: <BadgeDollarSign size={20} /> }]
      : []),
  ];

  useEffect(() => {
    const cargarStats = async () => {
      try {
        const [productos, clientes, ventas, creditos] = await Promise.all([
          api.get("/productos"),
          api.get("/clientes"),
          api.get("/ventas"),
          isAdmin ? api.get("/creditos") : Promise.resolve({ data: [] }),
        ]);

        setStats({
          productos: productos.data?.length || 0,
          clientes: clientes.data?.length || 0,
          ventas: ventas.data?.length || 0,
          creditos: creditos.data?.length || 0,
        });
      } catch {
        toast.error("No se pudieron cargar los indicadores");
      }
    };

    cargarStats();
  }, [isAdmin]);

  const logout = () => {
    localStorage.clear();
    setIsAuth(false);
  };

  const renderView = () => {
    switch (menu) {
      case "productos":
        return <Productos />;
      case "ventas":
        return <Ventas />;
      case "clientes":
        return <Clientes />;
      case "historial":
        return <Historial />;
      case "creditos":
        return isAdmin ? <Creditos /> : null;
      default:
        return (
          <div className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
              <div className="bg-white rounded-xl shadow p-5">
                <p className="text-sm text-gray-500">Productos</p>
                <h3 className="text-3xl font-bold mt-2">{stats.productos}</h3>
              </div>
              <div className="bg-white rounded-xl shadow p-5">
                <p className="text-sm text-gray-500">Clientes</p>
                <h3 className="text-3xl font-bold mt-2">{stats.clientes}</h3>
              </div>
              <div className="bg-white rounded-xl shadow p-5">
                <p className="text-sm text-gray-500">Ventas</p>
                <h3 className="text-3xl font-bold mt-2">{stats.ventas}</h3>
              </div>
              <div className="bg-white rounded-xl shadow p-5">
                <p className="text-sm text-gray-500">Créditos</p>
                <h3 className="text-3xl font-bold mt-2">{stats.creditos}</h3>
              </div>
            </div>

            <div className="bg-white rounded-xl shadow p-6">
              <h2 className="font-bold text-lg mb-2">Bienvenido {user?.name}</h2>
              <p className="text-gray-600">
                Desde aquí puedes navegar entre ventas, clientes, historial y créditos.
              </p>
            </div>
          </div>
        );
    }
  };

  const title =
    menu === "principal"
      ? "Principal"
      : menu.charAt(0).toUpperCase() + menu.slice(1);

  return (
    <div className="flex h-screen bg-gray-100">
      <aside
        className={`bg-slate-900 text-white transition-all duration-300 ${
          open ? "w-64" : "w-16"
        } flex flex-col`}
      >
        <div className="flex items-center justify-between p-4 border-b border-slate-700">
          {open && <span className="font-semibold">Sistema</span>}
          <Menu className="cursor-pointer" onClick={() => setOpen(!open)} />
        </div>

        <div className="flex-1 mt-4 space-y-1">
          {menus.map((item) => (
            <button
              key={item.name}
              onClick={() => setMenu(item.name)}
              className={`w-full flex items-center gap-3 px-4 py-3 text-left transition-colors ${
                menu === item.name ? "bg-blue-400 text-white" : "hover:bg-slate-700"
              }`}
            >
              {item.icon}
              {open && <span>{item.label}</span>}
            </button>
          ))}
        </div>

        <div className="p-4">
          <button
            onClick={() => setShowLogout(true)}
            className="w-full flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 p-2 rounded-lg transition"
            title="Cerrar sesión"
          >
            <LogOut size={18} />
            {open && <span>Cerrar sesión</span>}
          </button>
        </div>
      </aside>

      <main className="flex-1 p-6 overflow-y-auto">
        <h1 className="text-2xl font-bold mb-6">{title}</h1>
        {renderView()}
      </main>

      {showLogout && (
        <div className="fixed inset-0 bg-black/40 flex justify-center items-center">
          <div className="bg-white p-6 rounded text-center w-80">
            <p className="mb-4">¿Cerrar sesión?</p>
            <div className="flex gap-4 justify-center">
              <button
                onClick={() => setShowLogout(false)}
                className="bg-gray-300 px-4 py-2 rounded"
              >
                Cancelar
              </button>
              <button
                onClick={logout}
                className="bg-red-600 text-white px-4 py-2 rounded"
              >
                Salir
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default Dashboard;