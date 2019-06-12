<?php

namespace RZP\Services\Throttle;

use RZP\Base;
use RZP\Constants\Mode;
use RZP\Http\Throttle\Constant as K;

class Validator extends Base\Validator
{
    protected static $createRules = [
        'id'     => 'sometimes|string',
        'mode'   => 'filled|string|required_with:auth,proxy,route|in:'. Mode::TEST . ',' . Mode::LIVE,
        'auth'   => 'filled|string|required_with:route,proxy',
        'proxy'  => 'filled|boolean|required_with:auth,route',
        'route'  => 'filled|string',
        'rules'  => 'required|associative_array',
    ];

    protected static $rulesRules = [
        K::MOCK                => 'filled|boolean',
        K::SKIP                => 'filled|boolean',
        K::BLOCK               => 'filled|boolean',
        K::LEAK_RATE_VALUE     => 'filled|integer|required_with:lrd,mbs',
        K::MAX_BUCKET_SIZE     => 'filled|integer|required_with:lrd,lrv',
        K::LEAK_RATE_DURATION  => 'filled|integer|required_with:lrv,mbs',
        K::BLOCKED_IPS         => 'filled|string',
        K::BLOCKED_USER_AGENTS => 'filled|string',
    ];

    protected static $fetchRules = [
        'id'     => 'sometimes|string',
    ];
}
