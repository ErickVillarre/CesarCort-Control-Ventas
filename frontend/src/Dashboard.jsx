import { useState } from "react";
import {
  Menu,
  LayoutDashboard,
  Users,
  Package,
  ShoppingCart,
  History,
  LogOut,
} from "lucide-react";
import Productos from "./Productos";

function Dashboard({ setIsAuth }) {
  const [open, setOpen] = useState(false);
  const [menu, setMenu] = useState("principal");
  const [showLogout, setShowLogout] = useState(false);

  const user = JSON.parse(localStorage.getItem("user"));

  const menus = [
    { name: "principal", label: "Principal", icon: <LayoutDashboard size={20} /> },
    { name: "productos", label: "Productos", icon: <Package size={20} /> },
    { name: "clientes", label: "Clientes", icon: <Users size={20} /> },
    { name: "ventas", label: "Ventas", icon: <ShoppingCart size={20} /> },
    { name: "historial", label: "Historial", icon: <History size={20} /> },
  ];

  const logout = () => {
    localStorage.clear();
    setIsAuth(false);
  };

  return (
    <div className="flex h-screen bg-gray-100">

      {/* SIDEBAR */}
      <div className={`bg-slate-900 text-white ${open ? "w-64" : "w-16"} transition-all flex flex-col`}>
        <div className="flex justify-between p-4 border-b border-slate-700">
          {open && <span>Sistema</span>}
          <Menu onClick={() => setOpen(!open)} className="cursor-pointer" />
        </div>

        <div className="flex-1 mt-4">
          {menus.map((item) => (
            <div
              key={item.name}
              onClick={() => setMenu(item.name)}
              className={`flex items-center gap-3 px-4 py-3 cursor-pointer ${
                menu === item.name ? "bg-blue-600" : "hover:bg-slate-700"
              }`}
            >
              {item.icon}
              {open && item.label}
            </div>
          ))}
        </div>

        <div className="p-4">
          <button onClick={() => setShowLogout(true)} className="bg-red-600 p-3 w-full rounded">
            {open ? "Cerrar sesión" : <LogOut />}
          </button>
        </div>
      </div>

      {/* CONTENIDO */}
      <div className="flex-1 p-6">

        <h1 className="text-2xl font-bold mb-6">
          {menu === "principal"
            ? `Bienvenido ${user?.name}`
            : menu.charAt(0).toUpperCase() + menu.slice(1)}
        </h1>

        {/* PRINCIPAL */}
        {menu === "principal" && (
          <div className="bg-white p-6 rounded shadow">
            <h2 className="font-bold text-lg mb-4">Dashboard</h2>
          </div>
        )}

        {/* PRODUCTOS */}
        {menu === "productos" && <Productos />}

      </div>

      {/* LOGOUT */}
      {showLogout && (
        <div className="fixed inset-0 bg-black/40 flex justify-center items-center">
          <div className="bg-white p-6 rounded text-center">
            <p className="mb-4">¿Cerrar sesión?</p>
            <div className="flex gap-4 justify-center">
              <button onClick={() => setShowLogout(false)} className="bg-gray-300 px-4 py-2 rounded">
                Cancelar
              </button>
              <button onClick={logout} className="bg-red-600 text-white px-4 py-2 rounded">
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