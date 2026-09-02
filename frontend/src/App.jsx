import { useEffect, useState } from "react";
import { Toaster } from "react-hot-toast";
import Login from "./Login";
import Dashboard from "./Dashboard";
import ChangePassword from "./ChangePassword";
import AccessDenied from "./AccessDenied";

function App() {
  const [isAuth, setIsAuth] = useState(!!localStorage.getItem("token"));
  const [mustChangePassword, setMustChangePassword] = useState(
    JSON.parse(localStorage.getItem("user") || "null")?.must_change_password || false
  );
  const [accessDenied, setAccessDenied] = useState(false);

  useEffect(() => {
    const handler = () => setAccessDenied(true);
    window.addEventListener("access-denied", handler);

    return () => window.removeEventListener("access-denied", handler);
  }, []);

  const handleLogin = (user) => {
    setIsAuth(true);
    setMustChangePassword(!!user?.must_change_password);
  };

  const handleLogout = () => {
    localStorage.clear();
    setIsAuth(false);
    setMustChangePassword(false);
  };

  return (
    <>
      <Toaster position="top-right" />
      {isAuth && accessDenied ? (
        <AccessDenied onBack={() => setAccessDenied(false)} />
      ) : isAuth && mustChangePassword ? (
        <ChangePassword
          onDone={(user) => {
            localStorage.setItem("user", JSON.stringify(user));
            setMustChangePassword(false);
          }}
          onLogout={handleLogout}
        />
      ) : isAuth ? (
        <Dashboard setIsAuth={setIsAuth} onLogout={handleLogout} />
      ) : (
        <Login onLogin={handleLogin} />
      )}
    </>
  );
}

export default App;
