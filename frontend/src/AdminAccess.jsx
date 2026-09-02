/* eslint-disable react-hooks/set-state-in-effect, react-hooks/exhaustive-deps */
import { useEffect, useMemo, useState } from "react";
import { KeyRound, Plus, Save, Search, ShieldOff, UserPlus } from "lucide-react";
import api from "./api/axios";
import toast from "react-hot-toast";

const emptyEmployee = {
  nombre: "",
  cargo: "",
  email: "",
  telefono: "",
  activo: true,
};

const can = (permission) => {
  const user = JSON.parse(localStorage.getItem("user") || "null");
  const permissions = user?.permissions || [];
  return permissions.includes("*") || permissions.includes(permission);
};

export default function AdminAccess() {
  const [empleados, setEmpleados] = useState([]);
  const [roles, setRoles] = useState([]);
  const [busqueda, setBusqueda] = useState("");
  const [form, setForm] = useState(emptyEmployee);
  const [roleByEmployee, setRoleByEmployee] = useState({});
  const [emailByEmployee, setEmailByEmployee] = useState({});
  const canManageUsers = can("usuarios.gestionar");
  const canManageEmployees = can("empleados.crear") || can("empleados.editar");

  const cargar = async () => {
    try {
      const requests = [api.get("/empleados")];
      if (canManageUsers) requests.push(api.get("/roles"));
      const [employeesRes, rolesRes] = await Promise.all(requests);

      setEmpleados(employeesRes.data || []);
      setRoles(rolesRes?.data || []);
    } catch (error) {
      toast.error(error.response?.data?.message || "No se pudieron cargar empleados");
    }
  };

  useEffect(() => {
    cargar();
  }, []);

  useEffect(() => {
    const roleMap = {};
    const emailMap = {};
    empleados.forEach((empleado) => {
      roleMap[empleado.id] = empleado.user?.role_id || "";
      emailMap[empleado.id] = empleado.user?.email || empleado.email || "";
    });
    setRoleByEmployee(roleMap);
    setEmailByEmployee(emailMap);
  }, [empleados]);

  const filtrados = useMemo(() => {
    const q = busqueda.toLowerCase();
    return empleados.filter((empleado) =>
      [empleado.nombre, empleado.cargo, empleado.email, empleado.user?.email]
        .filter(Boolean)
        .some((value) => value.toLowerCase().includes(q))
    );
  }, [empleados, busqueda]);

  const crearEmpleado = async (e) => {
    e.preventDefault();

    if (!canManageEmployees) {
      toast.error("No tienes permiso para crear empleados");
      return;
    }

    try {
      await api.post("/empleados", form);
      toast.success("Empleado creado");
      setForm(emptyEmployee);
      cargar();
    } catch (error) {
      const firstError = Object.values(error.response?.data?.errors || {})?.flat()?.[0];
      toast.error(firstError || "No se pudo guardar el empleado");
    }
  };

  const habilitar = async (empleado) => {
    try {
      await api.post(`/empleados/${empleado.id}/acceso`, {
        email: emailByEmployee[empleado.id],
        role_id: roleByEmployee[empleado.id],
      });
      toast.success("Acceso actualizado");
      cargar();
    } catch (error) {
      const firstError = Object.values(error.response?.data?.errors || {})?.flat()?.[0];
      toast.error(firstError || error.response?.data?.message || "No se pudo actualizar el acceso");
    }
  };

  const desactivar = async (empleado) => {
    try {
      await api.post(`/empleados/${empleado.id}/desactivar-acceso`);
      toast.success("Acceso desactivado");
      cargar();
    } catch (error) {
      toast.error(error.response?.data?.message || "No se pudo desactivar");
    }
  };

  return (
    <div className="space-y-5">
      <section className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
        <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
          <div>
            <h2 className="text-xl font-semibold">Empleados y accesos</h2>
            <p className="text-sm text-zinc-500">Alta de empleados, usuarios, roles y desactivacion de accesos.</p>
          </div>
          <div className="relative md:w-80">
            <Search className="absolute left-3 top-3 text-zinc-400" size={16} />
            <input
              value={busqueda}
              onChange={(e) => setBusqueda(e.target.value)}
              className="w-full rounded border border-zinc-300 bg-white py-2 pl-10 pr-3 outline-none focus:ring-2 focus:ring-zinc-500"
            />
          </div>
        </div>
      </section>

      {canManageEmployees && (
        <form onSubmit={crearEmpleado} className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
          <h3 className="mb-4 flex items-center gap-2 font-semibold">
            <Plus size={18} /> Nuevo empleado
          </h3>
          <div className="grid gap-3 md:grid-cols-5">
            <input className="rounded border border-zinc-300 px-3 py-2" placeholder="Nombre" value={form.nombre} onChange={(e) => setForm({ ...form, nombre: e.target.value })} />
            <input className="rounded border border-zinc-300 px-3 py-2" placeholder="Cargo" value={form.cargo} onChange={(e) => setForm({ ...form, cargo: e.target.value })} />
            <input className="rounded border border-zinc-300 px-3 py-2" placeholder="Email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
            <input className="rounded border border-zinc-300 px-3 py-2" placeholder="Telefono" value={form.telefono} onChange={(e) => setForm({ ...form, telefono: e.target.value })} />
            <button className="flex items-center justify-center gap-2 rounded bg-zinc-900 px-4 py-2 font-semibold text-white hover:bg-zinc-700">
              <Save size={17} /> Guardar
            </button>
          </div>
        </form>
      )}

      <section className="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
        <table className="w-full text-sm">
          <thead className="bg-zinc-100 text-left text-zinc-600">
            <tr>
              <th className="px-4 py-3">Empleado</th>
              <th className="px-4 py-3">Cargo</th>
              <th className="px-4 py-3">Usuario</th>
              <th className="px-4 py-3">Rol</th>
              <th className="px-4 py-3">Estado</th>
              {canManageUsers && <th className="px-4 py-3">Acciones</th>}
            </tr>
          </thead>
          <tbody>
            {filtrados.map((empleado) => (
              <tr key={empleado.id} className="border-t border-zinc-100 align-top">
                <td className="px-4 py-3 font-medium">{empleado.nombre}</td>
                <td className="px-4 py-3 text-zinc-500">{empleado.cargo}</td>
                <td className="px-4 py-3">
                  {canManageUsers ? (
                    <input
                      className="w-56 rounded border border-zinc-300 px-3 py-2"
                      value={emailByEmployee[empleado.id] || ""}
                      onChange={(e) => setEmailByEmployee({ ...emailByEmployee, [empleado.id]: e.target.value })}
                    />
                  ) : (
                    empleado.user?.email || empleado.email || "-"
                  )}
                </td>
                <td className="px-4 py-3">
                  {canManageUsers ? (
                    <select
                      className="w-48 rounded border border-zinc-300 px-3 py-2"
                      value={roleByEmployee[empleado.id] || ""}
                      onChange={(e) => setRoleByEmployee({ ...roleByEmployee, [empleado.id]: e.target.value })}
                    >
                      <option value="">Seleccione rol</option>
                      {roles.map((role) => (
                        <option key={role.id} value={role.id}>{role.label}</option>
                      ))}
                    </select>
                  ) : (
                    empleado.user?.role?.label || "-"
                  )}
                </td>
                <td className="px-4 py-3">
                  <span className={`rounded px-2 py-1 text-xs font-semibold ${empleado.user?.is_active === false ? "bg-red-50 text-red-700" : "bg-zinc-100 text-zinc-700"}`}>
                    {empleado.user ? (empleado.user.is_active ? "Activo" : "Desactivado") : "Sin usuario"}
                  </span>
                </td>
                {canManageUsers && (
                  <td className="px-4 py-3">
                    <div className="flex gap-2">
                      <button onClick={() => habilitar(empleado)} className="rounded bg-zinc-900 p-2 text-white hover:bg-zinc-700" title="Crear o actualizar acceso">
                        <UserPlus size={17} />
                      </button>
                      <button onClick={() => desactivar(empleado)} className="rounded border border-zinc-300 p-2 text-zinc-700 hover:bg-zinc-100" title="Desactivar acceso">
                        <ShieldOff size={17} />
                      </button>
                      {empleado.user?.must_change_password && (
                        <span className="flex items-center gap-1 rounded bg-amber-50 px-2 py-1 text-xs text-amber-700">
                          <KeyRound size={14} /> Cambio pendiente
                        </span>
                      )}
                    </div>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </section>
    </div>
  );
}
