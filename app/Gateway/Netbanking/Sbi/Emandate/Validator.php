<?php

namespace RZP\Gateway\Netbanking\Sbi\Emandate;

use RZP\Models\Batch\Limit;
use RZP\Models\Batch\Header;
use RZP\Models\Merchant\Entity as ME;
use RZP\Exception\BadRequestException;
use RZP\Error\ErrorCode as ApiErrorCode;
use RZP\Models\Batch\Validator as BatchValidatorBase;

class Validator extends BatchValidatorBase
{
    public function validateEntries(array & $entries, array $params, ME $merchant)
    {
        $rules = $this->getRuleNames();

        // Limit validations
        Limit::validate($rules['limit_rule'], count($entries));

        // Header validations
        self::validate($rules['header_rule'], array_keys(current($entries)));

        // Data validations
        $validatorMethodName = $rules['validator_method'];

        if (method_exists($this, $validatorMethodName) === true)
        {
            $this->$validatorMethodName($entries, $params, $merchant);
        }
    }

    /*
     * For Sbi Emandate register response:
     * We get different files - register file containing successful registrations and another file
     * containing failure registrations. Both the files have different headers
     */
    public static function validate(string $type, array $actualHeaders)
    {
        $expectedHeadersSuccess = Header::HEADER_MAP[$type][Header::INPUT]['success'];
        $expectedHeadersReject  = Header::HEADER_MAP[$type][Header::INPUT]['reject'];

        $valid = ((Header::areTwoHeadersSame($expectedHeadersSuccess, $actualHeaders)) or
                  (Header::areTwoHeadersSame($expectedHeadersReject, $actualHeaders)));

        if ($valid === false)
        {
            throw new BadRequestException(
                ApiErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
                null,
                [
                    'expected_headers_success'  => $expectedHeadersSuccess,
                    'expected_headers_reject'   => $expectedHeadersReject,
                    'input_headers'             => $actualHeaders,
                ]);
        }
    }
}
