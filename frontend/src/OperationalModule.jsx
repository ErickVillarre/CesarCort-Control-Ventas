export default function OperationalModule({ title, subtitle, icon, rows = [], dashboard }) {
  const Icon = icon;

  return (
    <div className="space-y-5">
      <section className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
        <div className="flex items-center gap-3">
          <div className="rounded bg-zinc-900 p-2 text-white">
            <Icon size={22} />
          </div>
          <div>
            <h2 className="text-xl font-semibold">{title}</h2>
            <p className="text-sm text-zinc-500">{subtitle}</p>
          </div>
        </div>
      </section>

      <section className="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
        <table className="w-full text-sm">
          <thead className="bg-zinc-100 text-left text-zinc-600">
            <tr>
              <th className="px-4 py-3">Area</th>
              <th className="px-4 py-3">Estado</th>
              <th className="px-4 py-3">Indicador</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((row) => (
              <tr key={row} className="border-t border-zinc-100">
                <td className="px-4 py-3 font-medium">{row}</td>
                <td className="px-4 py-3 text-zinc-500">Disponible por permiso</td>
                <td className="px-4 py-3 text-zinc-500">
                  {dashboard?.metrics?.empleados_activos ? `${dashboard.metrics.empleados_activos} empleados activos` : "Sin registros"}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>
    </div>
  );
}
