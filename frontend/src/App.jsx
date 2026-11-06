import { useEffect, useState } from "react";
import Login from "./Login";
import Dashboard from "./Dashboard";
import AccessibilityPanel from "./Accessibility";
import api from "./api/axios";

function App() {
  const [isAuth, setIsAuth] = useState(false);
  const [checkingSession, setCheckingSession] = useState(!!localStorage.getItem("token"));

  useEffect(() => {
    if (!localStorage.getItem("token")) return;
    let active = true;
    api.get("/auth/me").then(() => {
      if (active) setIsAuth(true);
    }).catch(() => {
      localStorage.removeItem("token");
      localStorage.removeItem("user");
      localStorage.removeItem("remember_session");
      localStorage.removeItem("active_module");
      if (active) setIsAuth(false);
    }).finally(() => {
      if (active) setCheckingSession(false);
    });
    return () => { active = false; };
  }, []);

  return (
    <>
      {checkingSession ? (
        <div className="min-h-screen flex items-center justify-center" role="status">Verificando sesión…</div>
      ) : isAuth ? (
        <Dashboard setIsAuth={setIsAuth} onLogout={() => {
          localStorage.removeItem("token");
          localStorage.removeItem("user");
          localStorage.removeItem("remember_session");
          localStorage.removeItem("active_module");
          window.history.replaceState({}, "", "/login");
        }} />
      ) : (
        <Login onLogin={() => setIsAuth(true)} />
      )}
      <AccessibilityPanel />
    </>
  );
}

export default App;
