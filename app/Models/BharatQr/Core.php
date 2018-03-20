<?php

namespace RZP\Models\BharatQr;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\VirtualAccount\Provider;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function processPayment(array $input, array $gatewayInput)
    {
        $this->trace->info(
            TraceCode::BHARAT_QR_PAYMENT_PROCESS_REQUEST,
            $input
        );

        $bharatQr = null;

        try
        {
            $bharatQr = (new Entity)->build($input);

            $bharatQr = $this->mutex->acquireAndRelease(
                $input[Entity::MERCHANT_REFERENCE],
                function() use ($bharatQr, $gatewayInput)
                {
                    $bharatQr = (new Processor($gatewayInput))->process($bharatQr);

                    // This will be null in case it's a duplicate notification
                    return $bharatQr;
                });

            $valid = true;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex, Trace::ERROR, TraceCode::BHARAT_QR_PAYMENT_PROCESSING_FAILED, $input);

            $valid = false;
        }

        return [$valid, $bharatQr];
    }
}
