<?php

namespace RZP\Models\Payment\PaymentSupportingDocuments;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function createPaymentSupportingDocuments(array $input): Entity
    {
        return (new Core())->createPaymentSupportingDocuments($input);
    }
}
