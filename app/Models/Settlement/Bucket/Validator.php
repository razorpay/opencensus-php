<?php

namespace RZP\Models\Settlement\Bucket;

use RZP\Base;
use RZP\Models\Settlement\Details;

class Validator extends Base\Validator
{
    protected static $fillRules = [
        'start' => 'filled|epoch|date_format:U',
        'end'   => 'filled|epoch|date_format:U',
    ];

    protected static $removeCompletedRules = [
        'timestamp' => 'filled|epoch|date_format:U',
    ];
}
