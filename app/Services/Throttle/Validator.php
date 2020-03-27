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

    protected static $createConfigRules = [
        'type'                    => 'required|in:'. K::CONFIGURATION_TYPE_MERCHANT . ',' . K::CONFIGURATION_TYPE_ROUTE,
        'merchant_id'             => 'required_if:type,'. K::CONFIGURATION_TYPE_MERCHANT .'|alpha_num|size:14',
        'route'                   => 'required|string',
        'throttle_type'           => 'required_if:type,' . K::CONFIGURATION_TYPE_ROUTE . '|string|in:' . K::THROTTLE_TYPE_ORG . ',' . K::THROTTLE_TYPE_MERCHANT . ',' . K::THROTTLE_TYPE_IP,
        'request_count'           => 'required|integer',
        'request_count_window'    => 'required|integer',
    ];

    protected static $deleteConfigRules = [
        'merchant_id'             => 'sometimes|alpha_num|size:14',
        'route'                   => 'sometimes|string',
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
