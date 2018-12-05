<?php

namespace RZP\Gateway\Upi\Mindgate\ErrorCodes;

use RZP\Gateway\Upi\Mindgate\Status;
use RZP\Gateway\Base\ErrorCodes\Upi;

class ErrorCodeDescriptions extends Upi\ErrorCodeDescriptions
{
    public static $statusDescriptionMap = [
        Status::FAILURE           => 'Payment Failed because of Gateway Error',
        Status::FAILED            => 'Payment Failed because of Gateway Error',
        Status::VPA_NOT_AVAILABLE => 'Vpa not available',
        Status::PENDING           => 'Transaction pending',
        Status::TIMEOUT           => 'Transaction timed out',
    ];
}
