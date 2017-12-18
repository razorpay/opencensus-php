<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Gateway\File\Processor\Refund;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;


class Base extends Refund\Base
{
    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();
        $refunds = $this->repo->refund->fetchFailedRefundsForGatewayBetweenTimestamps(
                    $begin,
                    $end,
                    static::GATEWAY
                );
        return $refunds;
    }

    public function checkIfValidDataAvailable(PublicCollection $refunds)
    {
        if ($refunds->isEmpty() === true)
        {
            throw new GatewayFileException(
                    ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
        }
    }

    public function generateData(PublicCollection $refunds)
    {
    }

    public function createFile($data)
    {
    }

    public function sendFile($data)
    {

    }

    protected function shouldNotReportFailure(string $code): bool
    {
        return ($code === ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
    }
}
