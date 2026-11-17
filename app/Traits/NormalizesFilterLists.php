<?php

namespace App\Traits;

trait NormalizesFilterLists
{
    /** @param list<string> $fields */
    protected function normalizeFilterLists(array $fields): void
    {
        foreach ($fields as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $decoded = json_decode($value);

                if (is_array($decoded)) {
                    $this->merge([$field => $decoded]);
                }
            }
        }
    }
}
