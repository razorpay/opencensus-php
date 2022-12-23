<?php

return [
    'bvs_validation_job' => [
        'write' => [
            'shadow' => [
                'enabled' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_WRITE_SHADOW_ENABLED', false),
                'full_enabled' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_WRITE_SHADOW_FULL_ENABLED', false),
                'splitz_experiment_id' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_WRITE_SHADOW_SPLITZ_EXPERIMENT_ID', '')
            ],
            'reverse_shadow' => [
                'enabled' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_WRITE_REVERSE_SHADOW_ENABLED', false),
                'full_enabled' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_WRITE_REVERSE_SHADOW_FULL_ENABLED', false),
                'splitz_experiment_id' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_WRITE_REVERSE_SHADOW_SPLITZ_EXPERIMENT_ID', '')
            ]
        ],
        'read' => [
            'shadow' => [
                'enabled' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_READ_SHADOW_ENABLED', false),
                'full_enabled' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_READ_SHADOW_FULL_ENABLED', false),
                'splitz_experiment_id' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_READ_SHADOW_SPLITZ_EXPERIMENT_ID', '')
            ],
            'reverse_shadow' => [
                'enabled' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_READ_REVERSE_SHADOW_ENABLED', false),
                'full_enabled' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_READ_REVERSE_SHADOW_FULL_ENABLED', false),
                'splitz_experiment_id' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_READ_REVERSE_SHADOW_SPLITZ_EXPERIMENT_ID', '')
            ]
        ]
    ],

    'account_service_details_fetch_reverse_map' => [
        'read' => [
            'shadow' => [
                'enabled' => env('ASV_MIGRATION_ASV_REVERSE_MAP_READ_SHADOW_ENABLED', false),
                'full_enabled' => env('ASV_MIGRATION_ASV_REVERSE_MAP_READ_SHADOW_FULL_ENABLED', false),
                'splitz_experiment_id' => env('ASV_MIGRATION_ASV_REVERSE_MAP_READ_SHADOW_SPLITZ_EXPERIMENT_ID', '')
            ],
            'reverse_shadow' => [
                'enabled' => env('ASV_MIGRATION_ASV_REVERSE_MAP_READ_REVERSE_SHADOW_ENABLED', false),
                'full_enabled' => env('ASV_MIGRATION_ASV_REVERSE_MAP_READ_REVERSE_SHADOW_FULL_ENABLED', false),
                'splitz_experiment_id' => env('ASV_MIGRATION_ASV_REVERSE_MAP_READ_REVERSE_SHADOW_SPLITZ_EXPERIMENT_ID', '')
            ]
        ]
    ],

    'all_route_or_job' => [
        'write' => [
            'shadow' => [
                'enabled' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_WRITE_SHADOW_ENABLED', false),
                'full_enabled' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_WRITE_SHADOW_FULL_ENABLED', false),
                'splitz_experiment_id' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_WRITE_SHADOW_SPLITZ_EXPERIMENT_ID', '')
            ],
            'reverse_shadow' => [
                'enabled' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_WRITE_REVERSE_SHADOW_ENABLED', false),
                'full_enabled' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_WRITE_REVERSE_SHADOW_FULL_ENABLED', false),
                'splitz_experiment_id' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_WRITE_REVERSE_SHADOW_SPLITZ_EXPERIMENT_ID', '')
            ]

        ],
        'read' => [
            'shadow' => [
                'enabled' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_READ_SHADOW_ENABLED', false),
                'full_enabled' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_READ_SHADOW_FULL_ENABLED', false),
                'splitz_experiment_id' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_READ_SHADOW_SPLITZ_EXPERIMENT_ID', '')
            ],
            'reverse_shadow' => [
                'enabled' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_READ_REVERSE_SHADOW_ENABLED', false),
                'full_enabled' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_READ_REVERSE_SHADOW_FULL_ENABLED', false),
                'splitz_experiment_id' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_READ_REVERSE_SHADOW_SPLITZ_EXPERIMENT_ID', '')
            ]
        ]
    ],
];
