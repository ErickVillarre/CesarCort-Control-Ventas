import { useState } from "react";
import Login from "./Login";
import Dashboard from "./Dashboard";

function App() {
  const [isAuth, setIsAuth] = useState(
    !!localStorage.getItem("token")
  );

  return (
    <>
      {isAuth ? (
        <Dashboard setIsAuth={setIsAuth} />
      ) : (
        <Login onLogin={() => setIsAuth(true)} />
      )}
    </>
  );
}

export default App;