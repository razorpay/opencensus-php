<?php

return [
    'bvs_validation_job' => [
        'write' => [
            'enabled' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_WRITE_ENABLED', false),
            'full_enabled' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_WRITE_FULL_ENABLED', false),
            'splitz_experiment_id' => env('ASV_MIGRATION_BVS_VALIDATION_JOB_WRITE_SPLITZ_EXPERIMENT_ID', '')
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

    'all_route_or_job' => [
        'write' => [
            'enabled' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_WRITE_ENABLED', false),
            'full_enabled' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_WRITE_FULL_ENABLED', false),
            'splitz_experiment_id' => env('ASV_MIGRATION_ALL_ROUTE_OR_JOB_WRITE_SPLITZ_EXPERIMENT_ID', '')
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
