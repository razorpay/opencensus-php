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

    public function processPayment(array $gatewayResponse)
    {
        $input = $this->getBharatQrInputParams($gatewayResponse['qr_data']);
        
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
                function() use ($bharatQr, $gatewayResponse)
                {
                    $bharatQr = (new Processor($gatewayResponse))->process($bharatQr);

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

    protected function getBharatQrInputParams(array $gatewayInput)
    {
        return [
            Entity::PROVIDER_REFERENCE_ID => $gatewayInput[Entity::PROVIDER_REFERENCE_ID],
            Entity::MERCHANT_REFERENCE    => $gatewayInput[Entity::MERCHANT_REFERENCE],
            Entity::METHOD                => $gatewayInput[Entity::METHOD],
            Entity::AMOUNT                => $gatewayInput[Entity::AMOUNT],
        ];
    }
}
