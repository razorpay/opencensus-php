<?php

namespace RZP\Models\Admin\Org;

class Constants
{
    const RAZORPAY    = 'Razorpay';

    const RZP         = '100000razorpay';

    const DEVSERVE_HOST_URL = 'dashboard.dev.razorpay.in';

    const DEVSERVE_CURLEC_HOST_URL = 'dashboard-curlec.dev.razorpay.in';

    const ALLOW_TO_BUSINESS_BANKING = [
        self::RZP
    ];

    // list of possible 2FA auth modes
    const EMAIL         = 'email';
    const SMS           = 'sms';
    const SMS_AND_EMAIL = 'sms_and_email';

    const DEFAULT_MAX_WRONG_2FA_ATTEMPTS = 9;

    const ORG_CUSTOM_CONFIG = [
        Entity::AXIS_ORG_ID => [
            [
                "id" => 'field1',
                "name" => 'RM Name',
                "type" => 'string',
            ],
            [
                "id" => 'field2',
                "name" => 'RM Contact Number',
                "type" => 'number',
            ],
            [
                "id" => 'field3',
                "name" => 'RM Email ID',
                "type" => 'email',
            ],
            [
                "id" => 'field4',
                "name" => 'RM Department',
                "type" => 'string',
            ],
            [
                "id" => 'field5',
                "name" => 'Zonal Head Name',
                "type" => 'string',
            ],
            [
                "id" => 'field6',
                "name" => 'Region',
                "type" => 'string',
            ],
            [
                "id" => 'field7',
                "name" => 'Department Subventing',
                "type" => 'string',
            ],
            [
                "id" => 'field8',
                "name" => 'Committed Throughput',
                "type" => 'number',
            ],
            [
                "id" => 'field9',
                "name" => 'Committed Transaction count',
                "type" => 'number',
            ],
            [
                "id" => 'field10',
                "name" => 'Committed Account Balance',
                "type" => 'number',
            ],
            [
                "id" => 'field11',
                "name" => 'Subvented',
                "type" => 'bool',
            ],
        ],
        Entity::AXIS_EASYPAY_ORG_ID => [
            [
                "id" => 'field1',
                "name" => 'RM Emp No',
                "type" => 'string',
            ],
            [
                "id" => 'field2',
                "name" => 'RM Name',
                "type" => 'string',
            ],
            [
                "id" => 'field3',
                "name" => 'PSM Emp No.',
                "type" => 'string',
            ],
            [
                "id" => 'field4',
                "name" => 'PSM Name',
                "type" => 'string',
            ],
            [
                "id" => 'field5',
                "name" => 'Business Segment',
                "type" => 'string',
            ],
            [
                "id" => 'field6',
                "name" => 'Regional/Segment Head',
                "type" => 'string',
            ]
        ]
    ];
}


