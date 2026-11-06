import { useState } from "react";
import Login from "./Login";
import Dashboard from "./Dashboard";
import AccessibilityPanel from "./Accessibility";

function App() {
  const [isAuth, setIsAuth] = useState(!!localStorage.getItem("token"));

  return (
    <>
      {isAuth ? (
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
