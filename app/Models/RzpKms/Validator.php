<?php

namespace RZP\Models\RzpKms;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createl1workflowRules = [
        Constants::ORG_ID => 'required|string',
    ];

    protected static $createl2workflowRules = [
        Constants::ORG_ID => 'required|string',
    ];

}
