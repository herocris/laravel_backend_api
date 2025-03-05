<?php

namespace App\Traits;

use App\Http\Requests\DrugConfiscation\GetRequest;
use App\Models\Drug;
use App\Models\DrugPresentation;
use Illuminate\Support\Collection;
use Carbon\Carbon;

trait ApiResponser
{
    protected function queryPeriod($period)
    {
        $validPeriods = [
            'day' => "DATE(full_date)",
            'month' => "DATE_FORMAT(full_date, '%Y-%m')",
            'quarter' => "CONCAT(YEAR(full_date), '-Q', QUARTER(full_date))",
            'semester' => "CONCAT(YEAR(full_date), '-S', IF(MONTH(full_date) <= 6, 1, 2))",
            'year' => "YEAR(full_date)",
            'total' => "DATE(full_date)",
        ];

        return $validPeriods[$period];
    }

    protected function queryFormater(Collection $queryResult, GetRequest $request)
    {

        $drugs = json_decode($request->input('drugs') ?? '[]');
        $presentations =json_decode($request->input('presentations') ?? '[]');
        $periodType = request()->input('period');
        $criteria=request()->input('criteria');
        $magnitude=request()->input('magnitude');
        $periodos = collect();
        $currentDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        // Ajuste inicial según el tipo de periodo
        if ($periodType !== 'total') {
            switch ($periodType) {
                case 'day':
                    $format = 'Y-m-d';
                    break;
                case 'month':
                    $format = 'Y-m';
                    $currentDate->startOfMonth();
                    $endDate->endOfMonth();
                    break;
                case 'quarter':
                    $format = 'Y-Q';
                    $currentDate->startOfQuarter();
                    $endDate->endOfQuarter();
                    break;
                case 'year':
                    $format = 'Y';
                    $currentDate->startOfYear();
                    $endDate->endOfYear();
                    break;
            }

            while ($currentDate <= $endDate) {
                $periodos->push($currentDate->format($format));
                match ($periodType) {
                    'day' => $currentDate->addDay(),
                    'month' => $currentDate->addMonth(),
                    'quarter' => $currentDate->addQuarter(),
                    'year' => $currentDate->addYear(),
                };
            }
        } else {
            // Si el periodo es "total", agregamos un solo valor en "periodos"
            $periodos->push("$currentDate a $endDate");
        }

        $criterias = $criteria == 'drugs' ? $drugs : $presentations;
        // 🟢 Agrupar por drug_id o por drug_presentation_id segun $criteria y asegurar que solo estén los `drug_id` o `drug_presentation_id` pasados en la consulta

        $grouped = collect($criterias)->mapWithKeys(function ($criteria_id) use ($queryResult, $periodos, $periodType,$criteria,$magnitude) {
            $filtered = $queryResult->where($criteria == 'drugs' ? 'drug_id' : 'drug_presentation_id', $criteria_id);

            if ($periodType === 'total') {
                // Si el periodo es "total", sumamos todas las cantidades o pesos
                $totalValue = $magnitude == 'amount'
                    ? $filtered->sum('total_amount')
                    : $filtered->sum('total_weight');

                $data = [$totalValue]; // Solo un valor
            } else {
                // Si es otro periodo, asignar los valores normales
                $data = $magnitude == 'amount'
                    ? $periodos->map(fn($period) => optional($filtered->firstWhere('period', $period))->total_amount ?? 0)->toArray()
                    : $periodos->map(fn($period) => optional($filtered->firstWhere('period', $period))->total_weight ?? 0)->toArray();
            }

            $criteriaName = request()->input('criteria') == 'drugs' ? Drug::find($criteria_id)->description : DrugPresentation::find($criteria_id)->description;

            return [$criteria_id => [
                "atributo cualquiera 1" => "contenido de atributo1",
                "label" => $criteriaName,
                "data" => $data
            ]];
        });

        // 🟢 Formatear la respuesta final
        $response = [
            "datasets" => $grouped->values()->toArray(), // Convertir a array y asegurar que solo están los drug_id pasados
            "periodos" => $periodos->toArray()
        ];

        // 🟢 Devolver JSON
        return $response;
    }
}
