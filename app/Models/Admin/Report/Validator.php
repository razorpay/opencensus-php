<?php

namespace RZP\Models\Admin\Report;

use RZP\Models\Admin\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Org\AuthPolicy;
use RZP\Models\Admin\Action;

class Validator extends Base\Validator
{
    protected static $createRules = [

    ];

    protected static $editRules = [

    ];

    public function validateType($type)
    {
        if (Type::isValidReportType($type) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid report type.');
        }

        return false;
    }
}
