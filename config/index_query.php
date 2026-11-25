<?php

return [
    'allow_only_trashed' => false,
    'allow_legacy_filters' => true,
    'literal_search' => true,
    'qualify_columns' => true,
    'preserve_computed_fields' => false,
    'sort_parameter' => 'sort_by',
    'direction_parameter' => 'type',
    'sortable_definition_key' => 'fields',
    'default_sort' => null,
    'tie_break_direction' => 'asc',
    'default_per_page' => 10,
    'use_validated_page' => true,
    'append_query_string' => false,
];
