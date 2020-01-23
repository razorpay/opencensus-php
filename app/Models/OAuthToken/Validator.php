<?php

namespace RZP\Models\OAuthToken;

use RZP\Base;

use RZP\Models\Batch\Header;
use RZP\Models\Batch\Helpers\OauthMigration as H;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Header::MERCHANT_ID    => 'required|string|size:14',
        H::CLIENT_ID           => 'required|string|size:14',
        H::USER_ID             => 'required|string|size:14',
        H::REDIRECT_URI        => 'required|url',
    ];
}
