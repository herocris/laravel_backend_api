<?php

namespace App\Http\Requests\DrugConfiscation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdatePutRequest extends FormRequest
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
            'amount' => 'required|integer',
            'weight' => 'required|numeric',
            'photo' => [
                'image',
                'max:2048',
                File::types(['png'])
            ],
            'confiscation_id' => 'required|exists:confiscations,id',
            'drug_id' => 'required|exists:drugs,id',
            'drug_presentation_id' => 'required|exists:drug_presentations,id',
        ];
    }
}
