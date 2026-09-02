import { useState } from "react";
import { Eye, EyeOff, KeyRound, Loader2, LogOut } from "lucide-react";
import api from "./api/axios";
import toast from "react-hot-toast";

const empty = {
  current_password: "",
  password: "",
  password_confirmation: "",
};

export default function ChangePassword({ onDone, onLogout }) {
  const [form, setForm] = useState(empty);
  const [visible, setVisible] = useState(false);
  const [loading, setLoading] = useState(false);
  const user = JSON.parse(localStorage.getItem("user") || "null");

  const submit = async (e) => {
    e.preventDefault();

    try {
      setLoading(true);
      const res = await api.post("/auth/change-password", form);
      toast.success("Contrasena actualizada");
      onDone(res.data.user);
    } catch (error) {
      const firstError = Object.values(error.response?.data?.errors || {})?.flat()?.[0];
      toast.error(firstError || error.response?.data?.message || "No se pudo cambiar la contrasena");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-zinc-100 px-4 py-8 text-zinc-900">
      <div className="mx-auto max-w-lg rounded-lg border border-zinc-200 bg-zinc-50 shadow-xl">
        <div className="flex items-center justify-between border-b border-zinc-200 px-6 py-5">
          <div className="flex items-center gap-3">
            <div className="rounded bg-zinc-900 p-2 text-white">
              <KeyRound size={22} />
            </div>
            <div>
              <h1 className="text-xl font-semibold">Cambiar contrasena</h1>
              <p className="text-sm text-zinc-500">{user?.name || "Usuario"}</p>
            </div>
          </div>
          <button
            type="button"
            onClick={onLogout}
            className="rounded p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900"
            title="Cerrar sesion"
          >
            <LogOut size={19} />
          </button>
        </div>

        <form onSubmit={submit} className="space-y-4 px-6 py-6">
          {[
            ["current_password", "Contrasena actual"],
            ["password", "Nueva contrasena"],
            ["password_confirmation", "Confirmar nueva contrasena"],
          ].map(([name, label]) => (
            <label key={name} className="block">
              <span className="text-sm font-medium text-zinc-700">{label}</span>
              <div className="mt-2 flex items-center gap-2 rounded border border-zinc-300 bg-white px-3 focus-within:ring-2 focus-within:ring-zinc-500">
                <input
                  type={visible ? "text" : "password"}
                  value={form[name]}
                  onChange={(e) => setForm({ ...form, [name]: e.target.value })}
                  className="w-full bg-transparent py-3 outline-none"
                  autoComplete={name === "current_password" ? "current-password" : "new-password"}
                />
                <button
                  type="button"
                  onClick={() => setVisible((value) => !value)}
                  className="rounded p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900"
                  title={visible ? "Ocultar contrasenas" : "Mostrar contrasenas"}
                >
                  {visible ? <EyeOff size={18} /> : <Eye size={18} />}
                </button>
              </div>
            </label>
          ))}

          <div className="rounded border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-600">
            Minimo 8 caracteres, una mayuscula, una minuscula, un numero y un caracter especial.
          </div>

          <button
            type="submit"
            disabled={loading}
            className="flex w-full items-center justify-center gap-2 rounded bg-zinc-900 px-4 py-3 font-semibold text-white transition hover:bg-zinc-700 disabled:opacity-70"
          >
            {loading && <Loader2 size={18} className="animate-spin" />}
            Guardar nueva contrasena
          </button>
        </form>
      </div>
    </div>
  );
}
