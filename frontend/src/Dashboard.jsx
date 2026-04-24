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

function Dashboard({ setIsAuth }) {
  const [open, setOpen] = useState(false);
  const [menu, setMenu] = useState("inicio");
  const [showLogout, setShowLogout] = useState(false);

  const user = JSON.parse(localStorage.getItem("user"));

  const menus = [
    { name: "inicio", label: "Dashboard", icon: <LayoutDashboard size={20} /> },
    { name: "clientes", label: "Clientes", icon: <Users size={20} /> },
    { name: "productos", label: "Productos", icon: <Package size={20} /> },
    { name: "ventas", label: "Ventas", icon: <ShoppingCart size={20} /> },
    { name: "historial", label: "Historial", icon: <History size={20} /> },
  ];

  const logout = () => {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    setIsAuth(false);
  };

  return (
    <div className="flex h-screen bg-gray-100">

      {/* SIDEBAR */}
      <div
        className={`bg-slate-900 text-white transition-all duration-300 ${
          open ? "w-64" : "w-16"
        } flex flex-col`}
      >
        {/* TOP */}
        <div className="flex items-center justify-between p-4 border-b border-slate-700">
          {open && <span className="font-bold text-lg">Sistema</span>}
          <Menu
            className="cursor-pointer"
            onClick={() => setOpen(!open)}
          />
        </div>

        {/* MENU */}
        <div className="flex-1 mt-4 space-y-2">
          {menus.map((item) => (
            <div
              key={item.name}
              onClick={() => setMenu(item.name)}
              className={`flex items-center gap-3 px-4 py-3 cursor-pointer transition-all
                ${
                  menu === item.name
                    ? "bg-blue-600"
                    : "hover:bg-slate-700"
                }`}
            >
              {item.icon}
              {open && <span>{item.label}</span>}
            </div>
          ))}
        </div>

        {/* LOGOUT */}
        <div className="p-4 border-t border-slate-700">
          <button
            onClick={() => setShowLogout(true)}
            className="flex items-center gap-3 w-full bg-red-600 hover:bg-red-700 p-3 rounded"
          >
            <LogOut size={20} />
            {open && "Cerrar sesión"}
          </button>
        </div>
      </div>

      {/* CONTENIDO */}
      <div className="flex-1 p-6">

        {/* HEADER */}
        <div className="mb-6">
          <h1 className="text-2xl font-bold">
            Bienvenido {user?.name || "Usuario"}
          </h1>
        </div>

        {/* VISTAS */}
        {menu === "inicio" && (
          <div className="bg-white p-6 rounded-xl shadow">
            Dashboard principal
          </div>
        )}

        {menu === "clientes" && (
          <div className="bg-white p-6 rounded-xl shadow">
            Clientes
          </div>
        )}

        {menu === "productos" && (
          <div className="bg-white p-6 rounded-xl shadow">
            Productos
          </div>
        )}

        {menu === "ventas" && (
          <div className="bg-white p-6 rounded-xl shadow">
            Ventas
          </div>
        )}

        {menu === "historial" && (
          <div className="bg-white p-6 rounded-xl shadow">
            Historial
          </div>
        )}
      </div>

      {/* MODAL LOGOUT */}
      {showLogout && (
        <div className="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center">
          <div className="bg-white p-6 rounded-xl shadow-xl w-80 text-center">
            <h2 className="text-lg font-semibold mb-4">
              ¿Cerrar sesión?
            </h2>
            <div className="flex justify-center gap-4">
              <button
                onClick={() => setShowLogout(false)}
                className="px-4 py-2 bg-gray-300 rounded"
              >
                Cancelar
              </button>
              <button
                onClick={logout}
                className="px-4 py-2 bg-red-600 text-white rounded"
              >
                Sí, salir
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default Dashboard;