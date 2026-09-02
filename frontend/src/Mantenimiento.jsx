/* eslint-disable react-hooks/set-state-in-effect, react-hooks/exhaustive-deps */
import { useEffect, useState } from "react";
import { AlertTriangle, CalendarDays, PackagePlus, Save, Wrench } from "lucide-react";
import api from "./api/axios";
import toast from "react-hot-toast";

const periods = [
  ["hoy", "Hoy"],
  ["semana", "Semana"],
  ["mes", "Mes"],
  ["trimestre", "3 meses"],
  ["rango", "Rango"],
];

export default function Mantenimiento() {
  const [data, setData] = useState(null);
  const [periodo, setPeriodo] = useState("mes");
  const [desde, setDesde] = useState("");
  const [hasta, setHasta] = useState("");
  const [selected, setSelected] = useState(null);
  const [machineDetail, setMachineDetail] = useState(null);
  const [machineTab, setMachineTab] = useState("resumen");
  const [loadingMachine, setLoadingMachine] = useState(false);
  const [falla, setFalla] = useState({ maquinaria_id: "", criticidad: "media", descripcion: "", tiempo_inactividad_horas: 0 });
  const [mantenimiento, setMantenimiento] = useState({
    maquinaria_id: "",
    tipo: "preventivo",
    inicio_at: "",
    fin_at: "",
    actividad_realizada: "",
    problema_encontrado: "",
    costo_mano_obra: 0,
    costo_repuestos: 0,
    estado_final: "operativa",
    recomendacion: "",
    proxima_fecha: "",
  });
  const [pedido, setPedido] = useState({ maquinaria_id: "", cantidad: 1, urgencia: "media", motivo: "" });
  const [corte, setCorte] = useState({ fecha: new Date().toISOString().split("T")[0], hora_inicio: "", hora_retorno: "", area_afectada: "Produccion" });

  const cargar = async () => {
    const res = await api.get("/mantenimiento/dashboard", { params: { periodo, desde, hasta } });
    setData(res.data);
  };

  useEffect(() => {
    cargar().catch(() => toast.error("No se pudo cargar mantenimiento"));
  }, [periodo]);

  const submit = async (type) => {
    try {
      if (type === "falla") await api.post("/mantenimiento/fallas", falla);
      if (type === "mantenimiento") await api.post("/mantenimiento/registros", mantenimiento);
      if (type === "pedido") await api.post("/mantenimiento/pedidos-repuestos", pedido);
      if (type === "corte") await api.post("/mantenimiento/cortes-energia", corte);
      toast.success("Registro guardado");
      cargar();
    } catch (error) {
      const first = Object.values(error.response?.data?.errors || {})?.flat()?.[0];
      toast.error(first || error.response?.data?.message || "No se pudo guardar");
    }
  };

  const abrirMaquina = async (id) => {
    if (machineDetail?.maquinaria?.id === id) return;

    try {
      setLoadingMachine(true);
      const res = await api.get(`/mantenimiento/maquinas/${id}`);
      setMachineDetail(res.data);
      setMachineTab("resumen");
    } catch {
      toast.error("No se pudo cargar la ficha de maquinaria");
    } finally {
      setLoadingMachine(false);
    }
  };

  const metrics = data?.metrics || {};
  const machineTabs = ["resumen", "mantenimientos", "fallas", "ordenes", "cambios", "repuestos", "pedidos", "cortes", "fotografias", "documentos"];

  return (
    <div className="space-y-5">
      <section className="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
        <div className="flex flex-col justify-between gap-3 md:flex-row md:items-center">
          <div className="flex items-center gap-3">
            <div className="rounded bg-zinc-900 p-2 text-white"><Wrench size={22} /></div>
            <div>
              <h2 className="text-xl font-semibold">Mantenimiento</h2>
              <p className="text-sm text-zinc-500">Maquinarias, fallas, repuestos y cortes de energia.</p>
            </div>
          </div>
          <div className="flex flex-wrap gap-2">
            {periods.map(([value, label]) => (
              <button key={value} onClick={() => setPeriodo(value)} className={`rounded px-3 py-2 text-sm ${periodo === value ? "bg-zinc-900 text-white" : "border border-zinc-300 bg-white"}`}>
                {label}
              </button>
            ))}
          </div>
        </div>
        {periodo === "rango" && (
          <div className="mt-4 grid gap-3 md:grid-cols-3">
            <input type="date" max={new Date().toISOString().split("T")[0]} value={desde} onChange={(e) => setDesde(e.target.value)} className="rounded border border-zinc-300 px-3 py-2" />
            <input type="date" max={new Date().toISOString().split("T")[0]} value={hasta} onChange={(e) => setHasta(e.target.value)} className="rounded border border-zinc-300 px-3 py-2" />
            <button onClick={cargar} className="rounded bg-zinc-900 px-4 py-2 text-white">Aplicar</button>
          </div>
        )}
      </section>

      <section className="grid gap-3 md:grid-cols-3 xl:grid-cols-5">
        {Object.entries(metrics).map(([key, value]) => (
          <button key={key} onClick={() => setSelected(key)} className="rounded-lg border border-zinc-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <p className="text-xs uppercase text-zinc-500">{key.replaceAll("_", " ")}</p>
            <p className="mt-2 text-2xl font-semibold">{Number(value || 0).toFixed(key.includes("costo") ? 2 : 0)}</p>
          </button>
        ))}
      </section>

      {selected && (
        <section className="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
          <p className="font-semibold">Detalle: {selected.replaceAll("_", " ")}</p>
          <p className="text-sm text-zinc-500">La informacion se filtra desde el backend segun el periodo seleccionado.</p>
        </section>
      )}

      <section className="grid gap-4 xl:grid-cols-[1fr_360px]">
        <div className="rounded-lg border border-zinc-200 bg-white shadow-sm">
          <div className="border-b border-zinc-200 px-4 py-3 font-semibold">Maquinarias esenciales</div>
          <div className="divide-y divide-zinc-100">
            {(data?.maquinarias || []).map((m) => (
              <details key={m.id} className="p-4" onToggle={(event) => event.currentTarget.open && abrirMaquina(m.id)}>
                <summary className="cursor-pointer font-semibold">{m.codigo} - {m.nombre}</summary>
                <div className="mt-3 grid gap-3 text-sm md:grid-cols-3">
                  {[
                    "tipo",
                    "marca",
                    "modelo",
                    "numero_serie",
                    "foto",
                    "fecha_compra",
                    "fecha_instalacion",
                    "ubicacion",
                    "responsable",
                    "estado",
                    "criticidad",
                    "horas_acumuladas",
                    "frecuencia_mantenimiento",
                    "ultimo_mantenimiento",
                    "proximo_mantenimiento",
                    "tiempo_promedio_mantenimiento",
                    "tiempo_inactividad_acumulado",
                    "costo_acumulado",
                    "garantia",
                    "manuales",
                    "observaciones",
                  ].map((field) => (
                    <div key={field} className="rounded border border-zinc-200 bg-zinc-50 p-3">
                      <p className="text-zinc-500">{field.replaceAll("_", " ")}</p>
                      <p className="font-medium">{m[field] || "Por registrar"}</p>
                    </div>
                  ))}
                </div>
                {machineDetail?.maquinaria?.id === m.id && (
                  <div className="mt-4 rounded border border-zinc-200 bg-white p-3">
                    <div className="flex flex-wrap gap-2">
                      {machineTabs.map((tab) => (
                        <button key={tab} type="button" onClick={() => setMachineTab(tab)} className={`rounded px-3 py-1.5 text-xs font-semibold ${machineTab === tab ? "bg-zinc-900 text-white" : "bg-zinc-100 text-zinc-700"}`}>
                          {tab.replaceAll("_", " ")}
                        </button>
                      ))}
                    </div>
                    <MachineTabContent tab={machineTab} detail={machineDetail} />
                  </div>
                )}
                {loadingMachine && machineDetail?.maquinaria?.id !== m.id && <p className="mt-3 text-sm text-zinc-500">Cargando ficha...</p>}
              </details>
            ))}
          </div>
        </div>

        <div className="space-y-4">
          <Panel title="Registrar falla" icon={AlertTriangle}>
            <select value={falla.maquinaria_id} onChange={(e) => setFalla({ ...falla, maquinaria_id: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2">
              <option value="">Maquinaria</option>
              {(data?.maquinarias || []).map((m) => <option key={m.id} value={m.id}>{m.nombre}</option>)}
            </select>
            <select value={falla.criticidad} onChange={(e) => setFalla({ ...falla, criticidad: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2">
              <option value="media">Media</option><option value="alta">Alta</option><option value="critica">Critica</option>
            </select>
            <textarea value={falla.descripcion} onChange={(e) => setFalla({ ...falla, descripcion: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" placeholder="Descripcion" />
            <button onClick={() => submit("falla")} className="flex w-full items-center justify-center gap-2 rounded bg-zinc-900 px-4 py-2 text-white"><Save size={16} /> Guardar</button>
          </Panel>

          <Panel title="Registrar mantenimiento" icon={Wrench}>
            <select value={mantenimiento.maquinaria_id} onChange={(e) => setMantenimiento({ ...mantenimiento, maquinaria_id: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2">
              <option value="">Maquinaria</option>
              {(data?.maquinarias || []).map((m) => <option key={m.id} value={m.id}>{m.nombre}</option>)}
            </select>
            <select value={mantenimiento.tipo} onChange={(e) => setMantenimiento({ ...mantenimiento, tipo: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2">
              <option value="preventivo">Preventivo</option><option value="correctivo">Correctivo</option>
            </select>
            <div className="grid gap-2 sm:grid-cols-2">
              <input type="datetime-local" value={mantenimiento.inicio_at} onChange={(e) => setMantenimiento({ ...mantenimiento, inicio_at: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" />
              <input type="datetime-local" value={mantenimiento.fin_at} onChange={(e) => setMantenimiento({ ...mantenimiento, fin_at: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" />
            </div>
            <textarea value={mantenimiento.problema_encontrado} onChange={(e) => setMantenimiento({ ...mantenimiento, problema_encontrado: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" placeholder="Problema encontrado" />
            <textarea value={mantenimiento.actividad_realizada} onChange={(e) => setMantenimiento({ ...mantenimiento, actividad_realizada: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" placeholder="Actividad realizada" />
            <div className="grid gap-2 sm:grid-cols-2">
              <input type="number" min="0" step="0.01" value={mantenimiento.costo_mano_obra} onChange={(e) => setMantenimiento({ ...mantenimiento, costo_mano_obra: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" placeholder="Mano de obra" />
              <input type="number" min="0" step="0.01" value={mantenimiento.costo_repuestos} onChange={(e) => setMantenimiento({ ...mantenimiento, costo_repuestos: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" placeholder="Repuestos" />
            </div>
            <select value={mantenimiento.estado_final} onChange={(e) => setMantenimiento({ ...mantenimiento, estado_final: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2">
              <option value="operativa">Operativa</option><option value="observada">Observada</option><option value="detenida">Detenida</option>
            </select>
            <input type="date" min={new Date().toISOString().split("T")[0]} value={mantenimiento.proxima_fecha} onChange={(e) => setMantenimiento({ ...mantenimiento, proxima_fecha: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" />
            <button onClick={() => submit("mantenimiento")} className="flex w-full items-center justify-center gap-2 rounded bg-zinc-900 px-4 py-2 text-white"><Save size={16} /> Registrar</button>
          </Panel>

          <Panel title="Solicitar repuesto" icon={PackagePlus}>
            <select value={pedido.maquinaria_id} onChange={(e) => setPedido({ ...pedido, maquinaria_id: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2">
              <option value="">Maquinaria</option>
              {(data?.maquinarias || []).map((m) => <option key={m.id} value={m.id}>{m.nombre}</option>)}
            </select>
            <input type="number" min="1" value={pedido.cantidad} onChange={(e) => setPedido({ ...pedido, cantidad: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" />
            <textarea value={pedido.motivo} onChange={(e) => setPedido({ ...pedido, motivo: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" placeholder="Motivo" />
            <button onClick={() => submit("pedido")} className="flex w-full items-center justify-center gap-2 rounded bg-zinc-900 px-4 py-2 text-white"><Save size={16} /> Solicitar</button>
          </Panel>

          <Panel title="Corte de energia" icon={CalendarDays}>
            <input type="date" max={new Date().toISOString().split("T")[0]} value={corte.fecha} onChange={(e) => setCorte({ ...corte, fecha: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" />
            <input type="time" value={corte.hora_inicio} onChange={(e) => setCorte({ ...corte, hora_inicio: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" />
            <input value={corte.area_afectada} onChange={(e) => setCorte({ ...corte, area_afectada: e.target.value })} className="w-full rounded border border-zinc-300 px-3 py-2" />
            <button onClick={() => submit("corte")} className="flex w-full items-center justify-center gap-2 rounded bg-zinc-900 px-4 py-2 text-white"><Save size={16} /> Registrar</button>
          </Panel>
        </div>
      </section>
    </div>
  );
}

function MachineTabContent({ tab, detail }) {
  const machine = detail.maquinaria || {};
  const rows = {
    mantenimientos: detail.mantenimientos || [],
    fallas: detail.fallas || [],
    ordenes: (detail.mantenimientos || []).filter((item) => !item.fin_at),
    cambios: detail.cambios || [],
    repuestos: detail.repuestos || [],
    pedidos: detail.pedidos || [],
    cortes: detail.cortes || [],
    fotografias: [],
    documentos: [],
  };

  if (tab === "resumen") {
    return (
      <div className="mt-3 grid gap-3 text-sm sm:grid-cols-2">
        {[
          ["Estado", machine.estado],
          ["Criticidad", machine.criticidad],
          ["Ultimo mantenimiento", machine.ultimo_mantenimiento],
          ["Proximo mantenimiento", machine.proximo_mantenimiento],
          ["Horas acumuladas", machine.horas_acumuladas],
          ["Costo acumulado", machine.costo_acumulado],
        ].map(([label, value]) => (
          <div key={label} className="rounded border border-zinc-200 bg-zinc-50 p-3">
            <p className="text-zinc-500">{label}</p>
            <p className="font-medium">{value || "Por registrar"}</p>
          </div>
        ))}
      </div>
    );
  }

  const list = rows[tab] || [];

  if (!list.length) {
    return <p className="mt-3 rounded bg-zinc-50 p-3 text-sm text-zinc-500">Sin registros para esta seccion.</p>;
  }

  return (
    <div className="mt-3 max-h-72 space-y-2 overflow-auto">
      {list.map((item) => (
        <div key={`${tab}-${item.id}`} className="rounded border border-zinc-200 bg-zinc-50 p-3 text-sm">
          <p className="font-semibold">{item.tipo || item.nombre || item.estado || item.criticidad || `Registro #${item.id}`}</p>
          <p className="text-zinc-500">{item.descripcion || item.actividad_realizada || item.motivo || item.observaciones || item.fecha || item.inicio_at || "Detalle registrado"}</p>
        </div>
      ))}
    </div>
  );
}

function Panel({ title, icon, children }) {
  const PanelIcon = icon;

  return (
    <div className="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
      <h3 className="mb-3 flex items-center gap-2 font-semibold"><PanelIcon size={18} /> {title}</h3>
      <div className="space-y-3">{children}</div>
    </div>
  );
}
