<?php

namespace Models\Api;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $fetchRules = array(
        'id'            => 'alpha_dash|max:30',
        'from'          => 'numeric',
        'to'            => 'numeric',
        'count'         => 'numeric|max:100',
        'skip'          => 'numeric',
        'status'        => 'sometimes',
        'email'         => 'sometimes|email',
        'contact'       => 'sometimes|digits_between:10,12'
    );

    protected static $generateReportRules = array(
        'month'         => 'integer|in:1,2,3,4,5,6,7,8,9,10,11,12',
        'year'         => 'integer|in:2015,2016',
    );
}
