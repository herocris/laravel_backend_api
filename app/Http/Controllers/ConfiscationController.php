<?php

namespace App\Http\Controllers;

use App\Http\Requests\Confiscation\StorePostRequest;
use App\Http\Requests\Confiscation\UpdatePutRequest;
use App\Http\Requests\Confiscation\GetRequest;
use App\Http\Resources\Confiscation\ConfiscationResource;
use App\Models\Ammunition;
use App\Models\Confiscation;
use App\Models\Drug;
use App\Models\Weapon;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
class ConfiscationController extends ApiController implements HasMiddleware
{
    /**
     * Middlewares para el controlador de decomisos.
     * `transformInput` asegura estructura uniforme de entrada con `ConfiscationResource`.
     *
     * @return array<Middleware> Lista de middlewares activos.
     */
    public static function middleware(): array
    {
        return [
            new Middleware("transformInput:" . ConfiscationResource::class . "", only: ['store', 'update']),
        ];
    }

    /**
     * Lista todos los decomisos (sin paginación). A futuro puede incluir filtros
     * por fecha o tipo para reducir carga.
     *
     * @return \Illuminate\Http\JsonResponse Colección de decomisos.
     */
    public function index()
    {
        $confiscations = Confiscation::all();
        return $this->showAll($confiscations);
    }

    /**
     * Construye respuesta de mapa con decomisos y sus ítems filtrados por rango
     * de fechas y subconjuntos de droga/arma/munición. Optimiza la carga usando
     * relaciones condicionales y `whereIn`. Filtra decomisos sin contenido útil.
     *
     * @param GetRequest $request Parámetros: start_date, end_date, drugs[], weapons[], ammunitions[].
     * @return \Illuminate\Http\JsonResponse Arreglo 'mapItems' listo para representación en mapa.
     */
    public function mapConfiscations(GetRequest $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $drugs = json_decode($request->input('drugs') ?? '[]');
        $weapons = json_decode($request->input('weapons') ?? '[]');
        $ammunitions = json_decode($request->input('ammunitions') ?? '[]');

        $confiscations = Confiscation::select('id', 'date as fecha', 'latitude as latitud', 'observation as observacion', 'length as longitud')
            ->with([
                'drugConfiscations' => function ($query) use ($drugs) {
                    $query->select('id', 'confiscation_id', 'confiscation_id as decomiso', 'photo as foto', 'amount as cantidad', 'weight as peso', 'drug_id', 'drug_id as droga')
                        ->addSelect([
                            'droga_nombre' => Drug::select('description')
                                ->whereColumn('drugs.id', 'drug_confiscations.drug_id')
                                ->limit(1),
                        ])
                        ->whereIn('drug_id', $drugs);
                },
                'weaponConfiscations' => function ($query) use ($weapons) {
                    $query->select('id', 'confiscation_id', 'confiscation_id as decomiso', 'photo as foto', 'amount as cantidad', 'weapon_id', 'weapon_id as arma')
                        ->addSelect([
                            'arma_nombre' => Weapon::select('description')
                                ->whereColumn('weapons.id', 'weapon_confiscations.weapon_id')
                                ->limit(1),
                        ])
                        ->whereIn('weapon_id', $weapons);
                },
                'ammunitionConfiscations' => function ($query) use ($ammunitions) {
                    $query->select('id', 'confiscation_id', 'confiscation_id as decomiso', 'photo as foto', 'amount as cantidad', 'ammunition_id', 'ammunition_id as municion')
                        ->addSelect([
                            'municion_nombre' => Ammunition::select('description')
                                ->whereColumn('ammunitions.id', 'ammunition_confiscations.ammunition_id')
                                ->limit(1),
                        ])
                        ->whereIn('ammunition_id', $ammunitions);
                },
            ])
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $confiscations = $confiscations->filter(function ($c) {
            return $c->drugConfiscations->isNotEmpty() ||
                $c->weaponConfiscations->isNotEmpty() ||
                $c->ammunitionConfiscations->isNotEmpty();
        })->values()->toArray();

        return response()->json(['mapItems' => $confiscations]);
    }

    /**
     * Crea un nuevo decomiso con datos validados.
     *
     * @param StorePostRequest $request Datos validados (fecha, coordenadas, etc.).
     * @return \Illuminate\Http\JsonResponse Recurso creado.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        $confiscation = Confiscation::create($validated);
        return $this->showOne($confiscation);
    }

    /**
     * Muestra un decomiso específico.
     *
     * @param Confiscation $confiscation Instancia objetivo.
     * @return \Illuminate\Http\JsonResponse Recurso solicitado.
     */
    public function show(Confiscation $confiscation)
    {
        return $this->showOne($confiscation);
    }

    /**
     * Actualiza datos del decomiso.
     *
     * @param UpdatePutRequest $request Datos validados.
     * @param Confiscation $confiscation Recurso a actualizar.
     * @return \Illuminate\Http\JsonResponse Recurso actualizado.
     */
    public function update(UpdatePutRequest $request, Confiscation $confiscation)
    {
        $validated = $request->validated();
        $confiscation->update($validated);
        return $this->showOne($confiscation);
    }

    /**
     * Soft delete del decomiso.
     *
     * @param Confiscation $confiscation Recurso a eliminar lógicamente.
     * @return \Illuminate\Http\JsonResponse Recurso eliminado.
     */
    public function destroy(Confiscation $confiscation)
    {
        $confiscation->delete();
        return $this->showOne($confiscation);
    }

    /**
     * Lista decomisos soft-deleted para recuperación.
     *
     * @return \Illuminate\Http\JsonResponse Colección en papelera.
     */
    public function indexDeleted()
    {
        $confiscations = Confiscation::onlyTrashed()->get();
        return $this->showAll($confiscations);
    }

    /**
     * Restaura un decomiso eliminado previamente.
     *
     * @param Confiscation $confiscation Recurso a restaurar.
     * @return \Illuminate\Http\JsonResponse Recurso restaurado.
     */
    public function restore(Confiscation $confiscation)
    {
        $confiscation->restore();
        return $this->showOne($confiscation);
    }
}
