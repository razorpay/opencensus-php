<?php
    return [
        'testTerminalServiceExecuteTerminalTestRun' => [
            'request' => [
                'method'  => 'POST',
                'url'     => '/terminals/proxy/terminal_test_run',
                'content' => [
                    'terminal_id' => '1000000000000t',
                ],
            ],
            'response' => [
                'content' => [
                    'data' => [
                        'id' => 'trmnlTestRunId',
                        'terminal_id' => 'GzGbeCf6yWzenn',
                        'created_by'    =>  'admin@razorpay.com',
                        'payment_test_summary'  =>  [
                            'success'     => 0,
                            'failed'      => 0,
                            'pending'     => 0,
                            'in_progress' => 0,
                            'timed_out'   => 0,
                        ],
                    ]
                ],
            ],
        ],
    ];