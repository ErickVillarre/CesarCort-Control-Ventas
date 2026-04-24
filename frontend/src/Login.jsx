import { useState } from "react";
import api from "./api/axios";

function Login({ onLogin }) {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  const login = async () => {
    try {
      const res = await api.post("/login", {
        email,
        password,
      });

      localStorage.setItem("token", res.data.token);
      localStorage.setItem("user", JSON.stringify(res.data.user));

      onLogin(); // cambia a dashboard
    } catch (error) {
      alert("Credenciales incorrectas");
    }
  };

  return (
    <div className="h-screen flex items-center justify-center bg-gray-100">
      
      <div className="bg-white p-8 rounded-2xl shadow-xl w-96">

        {/* LOGO / NOMBRE */}
        <h1 className="text-2xl font-bold text-center mb-6 text-gray-700">
          Bienvenido
        </h1>

        {/* INPUT EMAIL */}
        <input
          type="email"
          placeholder="Correo"
          className="w-full mb-4 p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
          onChange={(e) => setEmail(e.target.value)}
        />

        {/* INPUT PASSWORD */}
        <input
          type="password"
          placeholder="Contraseña"
          className="w-full mb-6 p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
          onChange={(e) => setPassword(e.target.value)}
        />

        {/* BOTÓN */}
        <button
          onClick={login}
          className="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition"
        >
          Ingresar
        </button>
      </div>

    </div>
  );
}

export default Login;