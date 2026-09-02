import { ShieldAlert } from "lucide-react";

export default function AccessDenied({ onBack }) {
  return (
    <div className="min-h-screen bg-zinc-100 px-4 py-10 text-zinc-900">
      <div className="mx-auto max-w-md rounded-lg border border-zinc-200 bg-white p-6 text-center shadow-xl">
        <div className="mx-auto flex h-14 w-14 items-center justify-center rounded bg-zinc-900 text-white">
          <ShieldAlert size={28} />
        </div>
        <h1 className="mt-5 text-2xl font-semibold">Acceso denegado</h1>
        <p className="mt-2 text-sm text-zinc-500">Tu rol no tiene permiso para abrir esta ruta o ejecutar esta accion.</p>
        <button onClick={onBack} className="mt-6 rounded bg-zinc-900 px-4 py-2 font-semibold text-white hover:bg-zinc-700">
          Volver al panel
        </button>
      </div>
    </div>
  );
}
