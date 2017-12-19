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

    public function processPayment(array $input)
    {
        $this->trace->info(
            TraceCode::BHARAT_QR_PAYMENT_PROCESS_REQUEST,
            $input
        );

        try
        {
            $bharatQr = (new Entity)->build($input);

            $this->determineAndSetMode($bharatQr);

            $this->mutex->acquireAndRelease(
                $input[Entity::MERCHANT_REFERENCE],
                function() use ($bharatQr)
                {
                    (new Processor)->process($bharatQr);
                },
                Constants::MUTEX_TIMEOUT,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

            $valid = true;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex, Trace::ERROR, TraceCode::BHARAT_QR_PAYMENT_PROCESSING_FAILED, $input);

            $valid = false;
        }

        return $valid;
    }

    protected function determineAndSetMode(Entity $bharatQr)
    {
        $merchantReference = $bharatQr->getMerchantReference();

        (new QrCode\Entity)->stripSignWithoutValidation($merchantReference);

        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($merchantReference, 'qr_code');

        if ($mode === null)
        {
            $mode = Mode::LIVE;
        }

        \Database\DefaultConnection::set($mode);

        $this->app['basicauth']->setMode($mode);
    }
}
