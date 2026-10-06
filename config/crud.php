<?php

return [
    'default_page_size' => 10,
    'locale' => env('CRUD_LOCALE'),
    'default_filters_open' => true,
    'pagination' => [
        'driver' => env('CRUD_PAGINATION_DRIVER', 'length_aware'),
    ],
    'default_form_mode' => 'page',
    'default_page_width' => 'standard',
    'default_form_width' => 'standard',
];
