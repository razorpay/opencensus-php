<?php

namespace RZP\Models\Payout;

use App;
use ArrayObject;

use RZP\Error\Error;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\Status as PayoutStatus;

class PayoutError extends Error
{
    const INTERNAL_PAYOUT_ERROR = 'internal_payout_error';

    const DEFAULT_CODE_KEY = 'DEFAULT';

    protected $payout = null;

    public function __construct(Entity $payout)
    {
        $this->payout = $payout;

        parent::__construct($payout->getStatusCode());
    }

    public function fill($code,  $desc = null, $field = null, $data = null, $internalDesc = null)
    {
        $this->setPublicErrorCode($code);

        $errorDetails = $this->payout->isStatusProcessed() ? null : $this->getErrorDetails();

        $this->setSource($errorDetails[self::SOURCE] ?? null);

        $this->setReason($errorDetails[self::REASON] ?? null);

        $this->setAttribute(self::DESCRIPTION, $errorDetails[self::DESCRIPTION] ?? null);
    }

    /**
     * Checks
     * 1. if status_code is null error details will be null
     * 2. If status code not null and in error_reason_payout file:
     *    a. If error code is present and is internal_payout_error
     *    b. If not internal_payout_error but has initiated or failed error details
     *    c. If error code map is not present in json file trace log and add default details
     *
     * @return mixed|null
     */
    public function getErrorDetails()
    {
        $statusCode = $this->getPublicErrorCode();

        $errorDetails = null;

        if (is_null($statusCode) === false)
        {
            $payoutErrorCodeMapping = $this->readMappingFromJsonFile(Error::BANKING_ERROR_CODE_FILE_PATH,'payout');

            $errorDetails = $payoutErrorCodeMapping[self::INTERNAL_PAYOUT_ERROR][$statusCode] ?? null;

            if ((is_null($errorDetails) === true) and
                (isset($payoutErrorCodeMapping[$statusCode]) === true))
            {
                $statusCodeErrorDetail = $payoutErrorCodeMapping[$statusCode];

                $payoutStatus = $this->payout->getStatus();

                $failureStatus = PayoutStatus::getErrorStatus($payoutStatus);

                $errorDetails = $statusCodeErrorDetail[$failureStatus] ?? null;

                if (isset(ErrorCodeMapping::$alternateFailureReasonMapping[$statusCode]) === true)
                {
                    $alternate = $this->payout->merchant->isFeatureEnabled(Feature\Constants::ALTERNATE_PAYOUT_FR);

                    if ($alternate === true)
                    {
                        $errorDetails[self::DESCRIPTION] = ErrorCodeMapping::$alternateFailureReasonMapping[$statusCode];
                    }
                }
            }

            if (is_null($errorDetails) === true)
            {
                $errorDetails = $payoutErrorCodeMapping[self::DEFAULT_CODE_KEY] ?? null;

                $this->trace->error(TraceCode::PAYOUT_STATUS_CODE_MAPPING_REQUIRED,
                    [
                        'payout_id'         => $this->payout->getId(),
                        'bank_status_code'  => $statusCode
                    ]);
            }
        }

        return $errorDetails;
    }

    public function toPublicErrorResponse()
    {
        return [
            self::SOURCE            => $this->getAttribute(self::SOURCE),
            self::REASON            => $this->getAttribute(self::REASON),
            self::DESCRIPTION       => $this->getAttribute(self::DESCRIPTION),
            self::PUBLIC_ERROR_CODE => 'NA',
            self::STEP              => 'NA',
            self::METADATA          => new \ArrayObject([], ArrayObject::STD_PROP_LIST|ArrayObject::ARRAY_AS_PROPS)
        ];
    }
}
