<?php

namespace RZP\Models\BharatQr;

use Config;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function processPayment(array $gatewayResponse, $terminal)
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

            $this->mutex->acquireAndRelease(
                $input[Entity::MERCHANT_REFERENCE],
                function() use ($bharatQr, $gatewayResponse, $terminal)
                {
                    $bharatQr = (new Processor($gatewayResponse, $terminal))->process($bharatQr);

                    // This will be null in case it's a duplicate notification
                    return $bharatQr;
                });

            $valid = true;
        }
        catch (\Throwable $ex)
        {
            $this->alertException($ex, $input);

            $valid = false;
        }

        return $valid;
    }

    /**
     * Trace and send an alert to Slack.
     *
     * @param  \Throwable $ex
     * @param  array      $input
     */
    protected function alertException(\Throwable $ex, array $input)
    {
        // Any exception is critical, as bharatqr payments
        // are never supposed to fail. Trace accordingly.
        $this->trace->traceException(
            $ex, Trace::CRITICAL, TraceCode::BHARAT_QR_PAYMENT_PROCESSING_FAILED, $input);

        // Skip slack alerts in test mode
        if ($this->isTestMode() === true)
        {
            return;
        }

        $this->app['slack']->queue(
            TraceCode::BHARAT_QR_PAYMENT_PROCESSING_FAILED,
            array_merge($input, ['message' => $ex->getMessage()]),
            [
                'channel'  => Config::get('slack.channels.bharatqr_logs'),
                'username' => 'Bharat Mata',
                'icon'     => ':flag-in:'
            ]
        );
    }

    protected function getBharatQrInputParams(array $gatewayInputQrData)
    {
        return [
            Entity::PROVIDER_REFERENCE_ID => $gatewayInputQrData[Entity::PROVIDER_REFERENCE_ID],
            Entity::MERCHANT_REFERENCE    => $gatewayInputQrData[Entity::MERCHANT_REFERENCE],
            Entity::METHOD                => $gatewayInputQrData[Entity::METHOD],
            Entity::AMOUNT                => $gatewayInputQrData[Entity::AMOUNT],
        ];
    }
}
