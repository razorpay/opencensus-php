<?php

namespace RZP\Tests\Functional\Splitz;

return [

    'testSplitzResponse' => [
        'request'  => [
            'url'     => '/splitz/evaluate',
            'method'  => 'post',
            'content' => [
                'experiment_id' => 'randomExperiment'
            ]
        ],
        'response' => [
            'content' => [
                "experiment" => [
                    "id" => "randomExperiment123"
                ]
            ],
        ],
    ],

];
