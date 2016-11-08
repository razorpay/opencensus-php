<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;
use RZP\Error\ErrorCode;

class EnforceInitialPasswordChange extends Base
{
    protected $enforceInitialPasswordChange;

    public function __construct($enforceInitialPasswordChange)
    {
        $this->enforceInitialPasswordChange = $enforceInitialPasswordChange;
    }

    public function validate($admin)
    {
        if (($admin->isFirstLogin() === true) and
            ($this->enforceInitialPasswordChange === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INITIAL_PASSWORD_SHOULD_BE_CHANGED);
        }
    }
}