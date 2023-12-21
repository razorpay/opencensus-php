<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

return [
  'testCreateVKYCAdminForMerchant' => [
      'request'  => [
          'method'  => 'POST',
          'url'     => '/merchant/{id}/vkyc'
      ],
      'response' => [
          'content' => [
              'data' => [
                  'id'      => '100000Razorpay',
                  'status'  => 'initiated',
                  'details' => [
                      'weblink'         => 'https://capture.kyc.idfy.com/captures?t=6QSH24fkYekx',
                      'weblink_expiry'  => '1703358232',
                      'created_by'      => 'rzptest@razorpay.com',
                  ]
              ]
          ]
      ]
  ],
    'testGetVKYCAdminForMerchant' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchant/{id}/vkyc'
        ],
        'response' => [
            'content' => [
                'data' => []
            ]
        ]
    ]
];
