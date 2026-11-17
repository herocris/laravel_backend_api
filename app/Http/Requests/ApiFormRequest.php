<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * Normaliza exclusivamente los nombres públicos de las escrituras de módulos.
 * Las claves desconocidas se descartan; los nombres internos alternativos se rechazan.
 */
abstract class ApiFormRequest extends FormRequest
{
    /** @var array<string, list<string>> */
    private array $inputNameErrors = [];

    /** @return array<string, string> Nombre público => nombre interno. */
    abstract protected function inputAliases(): array;

    protected function prepareForValidation(): void
    {
        $input = $this->all();
        $files = $this->allFiles();
        $normalizedInput = [];
        $normalizedFiles = [];

        foreach ($this->inputAliases() as $external => $internal) {
            if ($external !== $internal && array_key_exists($internal, $input)) {
                $this->inputNameErrors[$external] = ["Utilice el campo {$external}; no se admite su nombre interno."];
            }

            if (array_key_exists($external, $files)) {
                $normalizedFiles[$internal] = $files[$external];
            } elseif (array_key_exists($external, $input)) {
                $normalizedInput[$internal] = $input[$external];
            }
        }

        $this->query->replace([]);
        $this->replace($normalizedInput);
        $this->files->replace($normalizedFiles);
        $this->convertedFiles = null;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->inputNameErrors as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        });
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->inputAliases() as $external => $internal) {
            $attributes[$internal] = $external;
            $value = $this->input($internal);

            if (is_array($value)) {
                foreach (array_keys($value) as $index) {
                    $attributes[$internal.'.'.$index] = $external.'.'.$index;
                }
            }
        }

        return $attributes;
    }

    protected function failedValidation(Validator $validator): void
    {
        $externalNames = array_flip($this->inputAliases());
        $errors = [];

        foreach ($validator->errors()->messages() as $field => $messages) {
            [$attribute, $suffix] = array_pad(explode('.', $field, 2), 2, null);
            $external = $externalNames[$attribute] ?? $attribute;
            $key = $suffix === null ? $external : $external.'.'.$suffix;
            $errors[$key] = array_merge($errors[$key] ?? [], $messages);
        }

        throw ValidationException::withMessages($errors);
    }
}
