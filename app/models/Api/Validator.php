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
        'status'        => 'sometimes'
    );
}
