<?php

namespace Gateway\Amex\Mock;

use Gateway\AxisMigs;

class Validator extends AxisMigs\Mock\Validator
{
    // protected static $authRulesModified = array(
    //     'vpc_Card'      => 'required|in:Amex',
    // );

    // public function __construct()
    // {
    //     parent::__construct();

    //     static::$authRules = array_merge(static::$authRules, self::$authRulesModified);
    // }
}