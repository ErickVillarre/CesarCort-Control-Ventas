<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MantenimientoController extends Controller
{
    public function dashboard(Request $request)
    {
        $from = $this->dateFrom($request->get('periodo', 'mes'), $request->get('desde'));
        $to = $request->filled('hasta') ? $request->date('hasta')->endOfDay() : now()->endOfDay();

        $fallas = DB::table('mantenimiento_fallas')->whereBetween('reportada_at', [$from, $to]);
        $mantenimientos = DB::table('mantenimiento_registros')->whereBetween('inicio_at', [$from, $to]);

        return response()->json([
            'metrics' => [
                'maquinarias_operativas' => DB::table('mantenimiento_maquinarias')->where('estado', 'operativa')->count(),
                'maquinarias_detenidas' => DB::table('mantenimiento_maquinarias')->where('estado', 'detenida')->count(),
                'maquinarias_mantenimiento' => DB::table('mantenimiento_maquinarias')->where('estado', 'mantenimiento')->count(),
                'fallas_abiertas' => (clone $fallas)->whereIn('estado', ['abierta', 'en_revision'])->count(),
                'fallas_criticas' => (clone $fallas)->where('criticidad', 'critica')->whereIn('estado', ['abierta', 'en_revision'])->count(),
                'mantenimientos_pendientes' => DB::table('mantenimiento_maquinarias')->whereDate('proximo_mantenimiento', '>=', now())->count(),
                'mantenimientos_vencidos' => DB::table('mantenimiento_maquinarias')->whereDate('proximo_mantenimiento', '<', now())->count(),
                'proximos_mantenimientos' => DB::table('mantenimiento_maquinarias')->whereBetween('proximo_mantenimiento', [now(), now()->addDays(15)])->count(),
                'ordenes_pendientes' => (clone $mantenimientos)->whereNull('fin_at')->count(),
                'pedidos_repuestos_pendientes' => DB::table('mantenimiento_pedidos_repuestos')->whereIn('estado', ['solicitado', 'en_revision', 'aprobado', 'pedido'])->count(),
                'repuestos_stock_bajo' => DB::table('mantenimiento_repuestos')->whereColumn('cantidad_disponible', '<=', 'stock_minimo')->count(),
                'tiempo_inactividad' => (clone $fallas)->sum('tiempo_inactividad_horas'),
                'mttr' => round((clone $fallas)->whereNotNull('resuelta_at')->avg('tiempo_inactividad_horas') ?? 0, 2),
                'costo_mes' => DB::table('mantenimiento_registros')->whereMonth('inicio_at', now()->month)->sum(DB::raw('costo_mano_obra + costo_repuestos')),
                'cortes_energia' => DB::table('mantenimiento_cortes_energia')->whereBetween('fecha', [$from->toDateString(), $to->toDateString()])->count(),
            ],
            'maquinarias' => DB::table('mantenimiento_maquinarias')->orderBy('nombre')->get(),
            'incidencias_recientes' => DB::table('mantenimiento_fallas')->orderByDesc('reportada_at')->limit(8)->get(),
            'repuestos_bajos' => DB::table('mantenimiento_repuestos')->whereColumn('cantidad_disponible', '<=', 'stock_minimo')->orderBy('nombre')->limit(8)->get(),
            'pedidos' => DB::table('mantenimiento_pedidos_repuestos')->orderByDesc('created_at')->limit(8)->get(),
        ]);
    }

    public function maquinas()
    {
        return response()->json(
            DB::table('mantenimiento_maquinarias')->orderBy('nombre')->get()
        );
    }

    public function maquina(int $id)
    {
        return response()->json([
            'maquinaria' => DB::table('mantenimiento_maquinarias')->find($id),
            'mantenimientos' => DB::table('mantenimiento_registros')->where('maquinaria_id', $id)->orderByDesc('inicio_at')->get(),
            'fallas' => DB::table('mantenimiento_fallas')->where('maquinaria_id', $id)->orderByDesc('reportada_at')->get(),
            'repuestos' => DB::table('mantenimiento_repuestos')->where('maquinaria_id', $id)->orderBy('nombre')->get(),
            'pedidos' => DB::table('mantenimiento_pedidos_repuestos')->where('maquinaria_id', $id)->orderByDesc('created_at')->get(),
            'cortes' => DB::table('mantenimiento_cortes_energia')->orderByDesc('fecha')->limit(20)->get(),
            'cambios' => DB::table('mantenimiento_cambios_piezas')->where('maquinaria_id', $id)->orderByDesc('instalado_at')->get(),
        ]);
    }

    public function registrarFalla(Request $request)
    {
        $data = $request->validate([
            'maquinaria_id' => ['required', 'exists:mantenimiento_maquinarias,id'],
            'criticidad' => ['required', 'in:baja,media,alta,critica'],
            'descripcion' => ['required', 'string'],
            'tiempo_inactividad_horas' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['reportado_por'] = $request->user()->id;
        $data['reportada_at'] = now();
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('mantenimiento_fallas')->insertGetId($data);

        return response()->json(DB::table('mantenimiento_fallas')->find($id), 201);
    }

    public function registrarMantenimiento(Request $request)
    {
        $data = $request->validate([
            'maquinaria_id' => ['required', 'exists:mantenimiento_maquinarias,id'],
            'tipo' => ['required', 'in:preventivo,correctivo'],
            'inicio_at' => ['nullable', 'date'],
            'fin_at' => ['nullable', 'date'],
            'problema_encontrado' => ['nullable', 'string'],
            'actividad_realizada' => ['required', 'string'],
            'costo_mano_obra' => ['nullable', 'numeric', 'min:0'],
            'costo_repuestos' => ['nullable', 'numeric', 'min:0'],
            'estado_final' => ['required', 'in:operativa,observada,detenida'],
            'recomendacion' => ['nullable', 'string'],
            'proxima_fecha' => ['nullable', 'date'],
        ]);

        $data['inicio_at'] = $data['inicio_at'] ?? now();
        $data['duracion_horas'] = isset($data['fin_at'])
            ? max(0, Carbon::parse($data['inicio_at'])->diffInMinutes(Carbon::parse($data['fin_at'])) / 60)
            : 0;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('mantenimiento_registros')->insertGetId($data);

        DB::table('mantenimiento_maquinarias')->where('id', $data['maquinaria_id'])->update([
            'estado' => $data['estado_final'] === 'detenida' ? 'detenida' : 'operativa',
            'ultimo_mantenimiento' => now()->toDateString(),
            'proximo_mantenimiento' => $data['proxima_fecha'] ?? null,
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('mantenimiento_registros')->find($id), 201);
    }

    public function solicitarRepuesto(Request $request)
    {
        $data = $request->validate([
            'repuesto_id' => ['nullable', 'exists:mantenimiento_repuestos,id'],
            'maquinaria_id' => ['required', 'exists:mantenimiento_maquinarias,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'urgencia' => ['required', 'in:baja,media,alta,critica'],
            'motivo' => ['required', 'string'],
        ]);

        $data['solicitado_por'] = $request->user()->id;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('mantenimiento_pedidos_repuestos')->insertGetId($data);

        return response()->json(DB::table('mantenimiento_pedidos_repuestos')->find($id), 201);
    }

    public function registrarCorte(Request $request)
    {
        $data = $request->validate([
            'fecha' => ['required', 'date'],
            'hora_inicio' => ['required'],
            'hora_retorno' => ['nullable'],
            'duracion_horas' => ['nullable', 'numeric', 'min:0'],
            'area_afectada' => ['required', 'string'],
            'maquinarias_afectadas' => ['nullable', 'array'],
            'trabajo_interrumpido' => ['nullable', 'string'],
            'posible_causa' => ['nullable', 'string'],
            'dano_encontrado' => ['nullable', 'string'],
            'accion_realizada' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $data['maquinarias_afectadas'] = json_encode($data['maquinarias_afectadas'] ?? []);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('mantenimiento_cortes_energia')->insertGetId($data);

        return response()->json(DB::table('mantenimiento_cortes_energia')->find($id), 201);
    }

    private function dateFrom(string $period, ?string $custom)
    {
        if ($period === 'hoy') return now()->startOfDay();
        if ($period === 'semana') return now()->startOfWeek();
        if ($period === 'trimestre') return now()->subMonths(3)->startOfDay();
        if ($period === 'rango' && $custom) return Carbon::parse($custom)->startOfDay();

        return now()->startOfMonth();
    }
}
