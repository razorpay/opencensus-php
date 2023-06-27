<?php

return [
    'testBatchUpload'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'banking_account_activation_comments',
                'config'   => [
                    'added_at' => 1593567500,
                    'source_team_type' => 'external',
                    'source_team'   => 'bank',
                    'channel'       => 'rbl'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'status'        => 'CREATED'
            ],
        ],
    ],

    'testBatchUploadIcici'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'icici_lead_account_activation_comments',
                'config'   => [
                    'added_at' => 1593567500,
                    'source_team_type' => 'external',
                    'source_team'   => 'bank',
                    'channel'       => 'icici'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'status'        => 'CREATED'
            ],
        ],
    ],

    'testBatchUploadIciciStpMis'     => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'icici_stp_mis',
                'config'   => [
                    'added_at' => 1593567500,
                ]
            ],
        ],
        'response' => [
            'content' => [
                'status'        => 'CREATED'
            ],
        ],
    ],

    'testBatchUploadForRblBulkUploadComments'   => [
          'request' => [
              'url'  => '/admin/batches',
              'method'  => 'post',
              'content' =>  [
                  'type'    => 'rbl_bulk_upload_comments',
              ]
          ],
          'response'  =>    [
              'content' =>  [
                  'status'      =>     'CREATED'
              ],
          ],
    ],

    'testBatchUploadForIciciBulkUploadComments'   => [
        'request' => [
            'url'  => '/admin/batches',
            'method'  => 'post',
            'content' =>  [
                'type'    => 'icici_bulk_upload_comments',
            ]
        ],
        'response'  =>    [
            'content' =>  [
                'status'      =>     'CREATED'
            ],
        ],
    ],

    'testBatchUploadForIciciVideoKycBulkUpload'   => [
        'request' => [
            'url'  => '/admin/batches',
            'method'  => 'post',
            'content' =>  [
                'type'    => 'icici_video_kyc_bulk_upload',
            ]
        ],
        'response'  =>    [
            'content' =>  [
                'status'      =>     'CREATED'
            ],
        ],
    ],
];
