import { useState } from "react";
import { Toaster } from "react-hot-toast";
import Login from "./Login";
import Dashboard from "./Dashboard";

function App() {
  const [isAuth, setIsAuth] = useState(!!localStorage.getItem("token"));

  return (
    <>
      <Toaster position="top-right" />
      {isAuth ? (
        <Dashboard setIsAuth={setIsAuth} />
      ) : (
        <Login onLogin={() => setIsAuth(true)} />
      )}
    </>
  );
}

export default App;