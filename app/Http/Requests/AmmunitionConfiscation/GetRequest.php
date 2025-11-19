<?php

namespace App\Http\Requests\AmmunitionConfiscation;

use Illuminate\Foundation\Http\FormRequest;

class GetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['required', 'string', 'in:day,month,quarter,semester,year,total'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d'],
            'ammunitions' => ['required'],
            'ammunitions.*' => ['integer', 'exists:ammunitions,id'],
            'typeGraph'=>['required', 'string', 'in:bar,line,pie']
        ];
    }
}
