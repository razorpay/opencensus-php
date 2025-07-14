<?php

namespace RZP\Models\BankingAccount\Gateway\Yesbank;

use RZP\Base;
use RZP\Models\BankingAccount\Gateway\Fields as BaseFields;

class Validator extends Base\Validator
{
    protected static $yesbankCredentialsRules = [
        BaseFields::ID                                              => 'required|string',
        BaseFields::CORP_ID                                         => 'present|nullable|string',
        BaseFields::USER_ID                                         => 'present|nullable|string',
        BaseFields::URN                                             => 'present|nullable|string',
        BaseFields::CREDENTIALS                                     => 'required|array',
        BaseFields::CREDENTIALS . '.' . Fields::AES_KEY             => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::CUSTOMER_ID         => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::APP_ID              => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::CLIENT_ID           => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::CLIENT_SECRET       => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::AUTH_USERNAME       => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::AUTH_PASSWORD       => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::GATEWAY_MERCHANT_ID => 'required|string',
    ];

    protected static $yesbankFtxCredentialsRules = [
        BaseFields::ID                                                      => 'required|string',
        BaseFields::CREDENTIALS                                             => 'required|array',
        BaseFields::CREDENTIALS . '.' . Fields::FTX_ID                      => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::CUST_ID                     => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::X_IBM_CLIENT_ID_TOKEN       => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::X_IBM_CLIENT_SECRET_TOKEN   => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::AUTHORIZATION_TOKEN         => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::VERSION                     => 'required|string',
    ];
}
