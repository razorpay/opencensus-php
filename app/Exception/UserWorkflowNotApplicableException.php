<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;

class UserWorkflowNotApplicableException extends RecoverableException
{
    public function __construct($roleCode)
    {
        $code = ErrorCode::BAD_REQUEST_USER_ROLE_NOT_SUPPORTED_FOR_WORKFLOW;

        $errorDesc = PublicErrorDescription::BAD_REQUEST_USER_ROLE_NOT_SUPPORTED_FOR_WORKFLOW;

        $this->error = new Error($code, $errorDesc, null, $roleCode);

        $message = json_encode(['error_description' => $errorDesc]);

        parent::__construct($message, $code);
    }
}
