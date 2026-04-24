import { useState } from "react";
import api from "./api/axios";
import toast from "react-hot-toast";

function Login({ onLogin }) {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  const login = async () => {
    if (!email.trim() || !password.trim()) {
      return toast.error("Complete los campos");
    }

    try {
      const res = await api.post("/login", { email, password });

      localStorage.setItem("token", res.data.token);
      localStorage.setItem("user", JSON.stringify(res.data.user));

      onLogin();
    } catch {
      toast.error("Credenciales incorrectas");
    }
  };

  const handleEnter = (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      login();
    }
  };

  return (
    <div className="h-screen flex items-center justify-center bg-white">

      {/* CARD PRINCIPAL */}
      <div className="w-[900px] h-[500px] bg-white rounded-2xl shadow-xl flex overflow-hidden">

        {/* LADO IZQUIERDO */}
        <div className="w-1/2 bg-gradient-to-br from-blue-400 to-blue-400 text-white flex flex-col justify-center items-center p-10">

          <img
            src="/logo.png"
            alt="logo"
            className="w-48 mb-6"
          />

          <h1 className="text-2xl font-semibold mb-3">
            Bienvenido
          </h1>

          <p className="text-sm text-center opacity-90">
            Sistema de gestión para ventas, productos y clientes
            de la empresa <b>Cescort SAC</b>.
          </p>

        </div>

        {/* LADO DERECHO */}
        <div className="w-1/2 flex flex-col justify-center px-10">

          <h2 className="text-2xl font-semibold text-gray-800 mb-6">
            Iniciar sesión
          </h2>

          <input
            type="email"
            placeholder="Correo"
            className="w-full mb-4 p-3 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-400 outline-none"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            onKeyDown={handleEnter}
            autoComplete="email"
          />

          <input
            type="password"
            placeholder="Contraseña"
            className="w-full mb-6 p-3 border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-400 outline-none"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            onKeyDown={handleEnter}
            autoComplete="current-password"
          />

          <button
            onClick={login}
            className="w-full bg-blue-400 hover:bg-blue-200 text-white p-3 rounded-lg transition"
          >
            Ingresar
          </button>

        </div>

      </div>

    </div>
  );
}

export default Login;