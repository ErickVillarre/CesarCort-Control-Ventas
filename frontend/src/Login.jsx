import { useState } from "react";
import { Eye, EyeOff, LockKeyhole, Loader2, Mail } from "lucide-react";
import api from "./api/axios";
import { apiConfigError } from "./api/axios";
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
      setError("Completa el usuario y la contraseña.");
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
      const msg = err.response?.data?.message || err.message || "Credenciales incorrectas";
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
    <div className="login-screen min-h-screen grid lg:grid-cols-[minmax(0,1.1fr)_minmax(420px,0.9fr)]">
      <section className="login-side hidden lg:flex flex-col justify-between">
        <div className="login-wordmark">
          <span className="login-wordmark-mark" aria-hidden="true">C</span>
          <div>
            <p className="font-semibold tracking-wide">CESCORT <span className="font-normal">Control</span></p>
            <p className="text-xs">Gestión empresarial</p>
          </div>
        </div>

        <div className="login-intro">
          <p className="login-eyebrow">CRM / ERP · MELAMINA</p>
          <h2>Tu operación, en orden.</h2>
          <p>Ventas, inventario, clientes y solicitudes en un espacio claro para trabajar mejor.</p>
          <div className="login-capabilities" aria-label="Áreas del sistema">
            <span>Ventas</span>
            <span>Inventario</span>
            <span>Servicio</span>
          </div>
        </div>

        <p className="login-side-footer">CESCORT · Control para cada decisión.</p>
      </section>

      <main className="login-main">
        <div className="login-panel w-full max-w-md">
          <div className="login-panel-heading">
            <img src="/logo.png" alt="Logo CESCORT" className="login-brand-image h-16 w-16 object-contain p-1" />
            <div>
              <p className="login-form-eyebrow">ACCESO AL SISTEMA</p>
              <h1>Bienvenido</h1>
              <p>Ingresa a tu espacio de trabajo.</p>
            </div>
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
                <span className="text-sm font-medium text-zinc-700">Contraseña</span>
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
                    title={showPassword ? "Ocultar contraseña" : "Mostrar contraseña"}
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

            {(error || apiConfigError) && (
              <div className="mt-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">
                <strong>Error: </strong>{error || apiConfigError}
              </div>
            )}

            <button
              onClick={login}
              data-voice="Iniciar sesión"
              disabled={loading || !!apiConfigError}
              className="app-primary login-submit mt-5 w-full disabled:cursor-not-allowed"
            >
              {loading && <Loader2 size={18} className="animate-spin" />}
              Iniciar sesión
            </button>
          </div>
        </div>
      </main>
    </div>
  );
}

export default Login;
