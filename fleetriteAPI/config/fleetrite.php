<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Core table names in legacy GPS schema
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'users'             => 'gs_users',
        'objects'           => 'gs_objects',
        'user_objects'      => 'gs_user_objects',
        'user_zones'        => 'gs_user_zones',
        'user_markers'      => 'gs_user_markers',
        'user_events'       => 'gs_user_events_data',
        'object_services'   => 'gs_object_services',
        'object_speed_limit'=> 'gs_object_speed_limit',
        'object_tasks'      => 'gs_object_tasks',
        'object_images'     => 'gs_object_img',
        'user_groups'       => 'gs_user_object_groups',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dynamic position table pattern
    |--------------------------------------------------------------------------
    |
    | Per-device positions are stored in tables named `gs_object_data_IMEI`.
    | Services working with history can build table names using this pattern.
    |
    */
    'position_table_prefix' => 'gs_object_data_',
];

