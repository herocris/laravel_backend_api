<?php

namespace App\Http\Requests\Confiscation;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
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
            'date' => 'required',
            'observation' => 'required',
            'direction' => 'required',
            'department' => 'required',
            'municipality' => 'required',
            'latitude' => 'required',
            'length' => 'required',
        ];
    }
}
