<?php


namespace RZP\Models\Merchant\Detail\Report;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Constants::REPORT         => 'required|string',
    ];

    public function validateReportInput($orgId, array $input)
    {
        if(empty($orgId) === true or isset(Constants::REPORT_TYPES[$orgId]) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ORG_ID);
        }

        $this->validateInput('create', $input);

        $report = $input[Constants::REPORT];

        if(in_array($report, Constants::REPORT_TYPES[$orgId], true) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INPUT_VALIDATION_FAILURE);
        }
    }
}
