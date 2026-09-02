import { useState } from "react";
import { Eye, EyeOff, LockKeyhole, Loader2, Mail } from "lucide-react";
import api from "./api/axios";
import toast from "react-hot-toast";

function Login({ onLogin }) {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [remember, setRemember] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const login = async () => {
    setError("");

    if (!email.trim() || !password.trim()) {
      setError("Completa el usuario y la contrasena.");
      return;
    }

    try {
      setLoading(true);
      const res = await api.post("/login", { email: email.trim(), password: password.trim(), remember });
      const user = res.data.user;

      localStorage.setItem("token", res.data.token);
      localStorage.setItem("user", JSON.stringify(user));
      localStorage.setItem("remember_session", remember ? "1" : "0");

      onLogin(user);
    } catch (err) {
      const msg = err.response?.data?.message || "Credenciales incorrectas";
      setError(msg);
      toast.error(msg);
    } finally {
      setLoading(false);
    }
  };

  const handleEnter = (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      login();
    }
  };

  return (
    <div className="min-h-screen bg-zinc-100 text-zinc-900 grid lg:grid-cols-[1fr_480px]">
      <section className="hidden lg:flex bg-zinc-900 text-zinc-100 px-12 py-10 flex-col justify-between">
        <div className="flex items-center gap-3">
          <img src="/logo.png" alt="Logo CesarControl" className="h-14 w-14 object-contain rounded bg-zinc-100 p-1" />
          <div>
            <h1 className="text-2xl font-semibold">CesarControl</h1>
            <p className="text-sm text-zinc-400">Sistema de Gestion Empresarial</p>
          </div>
        </div>

        <div className="max-w-xl">
          <p className="text-sm uppercase tracking-widest text-zinc-400">Control operativo</p>
          <h2 className="mt-4 text-5xl font-semibold leading-tight">Ventas, inventario y accesos en un solo panel.</h2>
          <div className="mt-8 grid grid-cols-3 gap-3 text-sm">
            <div className="rounded border border-zinc-700 bg-zinc-800/70 p-4">
              <p className="text-zinc-400">Estado</p>
              <p className="mt-1 font-semibold">Seguro</p>
            </div>
            <div className="rounded border border-zinc-700 bg-zinc-800/70 p-4">
              <p className="text-zinc-400">Roles</p>
              <p className="mt-1 font-semibold">Activos</p>
            </div>
            <div className="rounded border border-zinc-700 bg-zinc-800/70 p-4">
              <p className="text-zinc-400">Datos</p>
              <p className="mt-1 font-semibold">MySQL</p>
            </div>
          </div>
        </div>

        <p className="text-xs text-zinc-500">Gestion segura para ventas, inventario y operaciones.</p>
      </section>

      <main className="flex items-center justify-center px-5 py-10">
        <div className="w-full max-w-md rounded-lg border border-zinc-200 bg-zinc-50 shadow-xl">
          <div className="border-b border-zinc-200 px-7 py-6 text-center">
            <img src="/logo.png" alt="Logo CesarControl" className="mx-auto h-16 w-16 object-contain" />
            <h1 className="mt-4 text-2xl font-semibold">CesarControl</h1>
            <p className="mt-1 text-sm text-zinc-500">Sistema de Gestion Empresarial</p>
          </div>

          <div className="px-7 py-6">
            <div className="space-y-4">
              <label className="block">
                <span className="text-sm font-medium text-zinc-700">Correo o usuario</span>
                <div className="mt-2 flex items-center gap-2 rounded border border-zinc-300 bg-white px-3 focus-within:ring-2 focus-within:ring-zinc-500">
                  <Mail size={18} className="text-zinc-400" />
                  <input
                    type="email"
                    className="w-full bg-transparent py-3 outline-none"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    onKeyDown={handleEnter}
                    autoComplete="email"
                  />
                </div>
              </label>

              <label className="block">
                <span className="text-sm font-medium text-zinc-700">Contrasena</span>
                <div className="mt-2 flex items-center gap-2 rounded border border-zinc-300 bg-white px-3 focus-within:ring-2 focus-within:ring-zinc-500">
                  <LockKeyhole size={18} className="text-zinc-400" />
                  <input
                    type={showPassword ? "text" : "password"}
                    className="w-full bg-transparent py-3 outline-none"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    onKeyDown={handleEnter}
                    autoComplete="current-password"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword((value) => !value)}
                    className="rounded p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900"
                    title={showPassword ? "Ocultar contrasena" : "Mostrar contrasena"}
                  >
                    {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                  </button>
                </div>
              </label>
            </div>

            <label className="mt-4 flex items-center gap-2 text-sm text-zinc-600">
              <input
                type="checkbox"
                checked={remember}
                onChange={(e) => setRemember(e.target.checked)}
                className="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500"
              />
              Recordarme
            </label>

            {error && (
              <div className="mt-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {error}
              </div>
            )}

            <button
              onClick={login}
              disabled={loading}
              className="mt-5 flex w-full items-center justify-center gap-2 rounded bg-zinc-900 px-4 py-3 font-semibold text-white transition hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-70"
            >
              {loading && <Loader2 size={18} className="animate-spin" />}
              Iniciar sesion
            </button>
          </div>
        </div>
      </main>
    </div>
  );
}

export default Login;
