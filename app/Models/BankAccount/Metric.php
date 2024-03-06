<?php

namespace RZP\Models\BankAccount;

use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Base;


class Metric extends Base\Core
{

    // Labels
    const LABEL_TYPE                            = "type";
    const LABEL_TRACE_CODE                      = 'code';
    const LABEL_TRACE_FIELD                     = 'field';
    const LABEL_TRACE_SOURCE                    = 'source';
    const LABEL_TRACE_EXCEPTION_CLASS           = 'exception_class';

    // Counters
    const BENEFICIARY_VERIFY_API_RESPONSE   = 'beneficiary_verify_api_response';
    const BENEFICIARY_REGISTER_API_RESPONSE = 'beneficiary_register_api_response';

    // Metrics
    const BANK_ACCOUNT_CREATION_FAILED = 'bank_account_creation_failed';

    public function pushBankAccountCreationFailedMetrics($bankAccount, \Throwable $error): void
    {
        $dimensions = $this->getDefaultDimensions($bankAccount);

        $extraDimensions = $this->getBankAccountCreationFailedDimensions($error);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $this->trace->count(self::BANK_ACCOUNT_CREATION_FAILED, $dimensions);
    }

    public function getDefaultDimensions(Entity $bankAccount) : array
    {
        $dimensions = [
            self::LABEL_TYPE => (isset($bankAccount) === true) ? $bankAccount->getType() : null,
        ];

        return $dimensions;
    }

    public function getBankAccountCreationFailedDimensions(\Throwable $e): array
    {
        $errorAttributes = [];

        if ($e instanceof Exception\BaseException)
        {
            if (($e->getError() !== null) and ($e->getError() instanceof Error))
            {
                $errorAttributes = $e->getError()->getAttributes();
            }
        }
        else
        {
            $errorAttributes = [
                Metric::LABEL_TRACE_CODE            => $e->getCode(),
            ];
        }

        $dimensions = [
            Metric::LABEL_TRACE_CODE                => array_get($errorAttributes, Error::INTERNAL_ERROR_CODE),
            Metric::LABEL_TRACE_FIELD               => array_get($errorAttributes, Error::FIELD),
            Metric::LABEL_TRACE_SOURCE              => array_get($errorAttributes, Error::ERROR_CLASS),
            Metric::LABEL_TRACE_EXCEPTION_CLASS     => get_class($e),
        ];

        return $dimensions;
    }
}
