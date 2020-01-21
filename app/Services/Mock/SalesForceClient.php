<?php

namespace RZP\Services\Mock;

use RZP\Services\SalesForceClient as BaseSalesForceClient;

class SalesForceClient extends BaseSalesForceClient
{
    public function fetchAccountDetails()
    {
        $data = [
            'totalSize' => 1,
            'done'      => true,
            'records'   => [
                [
                    'attributes'                    => [
                        'type' => 'Account',
                        'url'  => '/services/data/v34.0/sobjects/Account/0010k00000wZtrrAAC'
                    ],
                    'Merchant_ID__c'                => '10000000000003',
                    'Owner'                         => [
                        'attributes' => [
                            'type' => 'User',
                            'url'  => '/services/data/v34.0/sobjects/User/0056F00000AkfoEQAR'
                        ],
                        'Email'      => 'abc@rzp.com'
                    ],
                    'Owner_Role__c'                 => 'SME Sales',
                    'Managers_In_Role_Hierarchy__c' => 'abc@rzp.com,rst@rzp.com,xyz@rzp.com'
                ]
            ]
        ];

        return $data;
    }
}
