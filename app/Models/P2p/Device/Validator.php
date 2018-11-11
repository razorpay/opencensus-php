<?php

namespace Rzp\Models\P2p\Device;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected $createRules = [
        Entity::ID           => 'required|string|regex:^(.*)$',
        Entity::IMEI         => 'required|string|regex:^(.*)$',
        Entity::OS           => 'required|string|regex:^(.*)$',
        Entity::OS_VERSION   => 'required|string|regex:^(.*)$',
        Entity::APP_NAME     => 'required|string|regex:^(.*)$',
        Entity::HANDLE       => 'required|string|regex:^(.*)$',
        Entity::CL_TOKEN     => 'required|string',
        Entity::CL_PAYLOAD   => 'required|string',
        Entity::CREATED_AT   => 'required|string',
    ];

    protected $fetchRules = [
    ];

    protected $refreshClTokenRules = [
    ];

    protected $deleteRules = [
    ];
}
