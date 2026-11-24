<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    abstract protected function definition(): array;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $definition = $this->definition();
        $filters = $definition['filters'] ?? [];
        $rules = self::rulesFor($definition['fields']) + [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter' => ['sometimes', 'array:'.implode(',', array_keys($filters))],
            'fields' => ['sometimes', 'array', 'list', 'min:1'],
            'fields.*' => ['required', 'string', 'distinct', Rule::in(array_keys($definition['selectable'] ?? $definition['fields']))],
            'include' => ['sometimes', 'array', 'list'],
            'include.*' => ['required', 'string', 'distinct', Rule::in(array_keys($definition['includes'] ?? []))],
        ];

        foreach ($filters as $field => $filter) {
            $rules['filter.'.$field] = $filter['rules'];
        }

        $values = $this->query('filter', []);
        if (isset($filters['fecha_desde'], $filters['fecha_hasta']) && is_array($values) && isset($values['fecha_desde'])) {
            $rules['filter.fecha_hasta'][] = 'after_or_equal:filter.fecha_desde';
        }

        return $rules;
    }

    /**
     * @param  array<string, string>  $fields
     * @return array<string, list<mixed>>
     */
    public static function rulesFor(array $fields): array
    {
        $rules = [
            'per_page' => ['sometimes', 'required', 'integer', 'min:2', 'max:100'],
            'page' => ['sometimes', 'required', 'integer', 'min:1'],
            'sort_by' => ['sometimes', 'required', 'string', Rule::in(array_keys($fields))],
            'type' => ['sometimes', 'required', 'string', Rule::in(['asc', 'desc'])],
        ];

        foreach (array_keys($fields) as $field) {
            $rules[$field] = ['sometimes', 'nullable', 'string'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        foreach (['fields', 'include'] as $parameter) {
            if (! $this->query->has($parameter)) {
                continue;
            }

            $value = $this->query($parameter);
            if (is_string($value) || $value === null) {
                $this->query->set($parameter, array_values(array_filter(
                    array_map(trim(...), explode(',', $value ?? '')),
                    fn (string $field): bool => $field !== '',
                )));
            }
        }
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return $this->query();
    }
}
