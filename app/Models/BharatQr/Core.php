<?php

namespace RZP\Models\BharatQr;

use Config;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\VirtualAccount;
use RZP\Models\QrPaymentRequest;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function processPayment(array $gatewayResponse, $terminal, $qrPaymentRequest)
    {
        $input = $this->getBharatQrInputParams($gatewayResponse['qr_data']);

        $inputTrace = $input;

        if (is_array($inputTrace) === true)
        {
            unset($inputTrace['mpan'], $inputTrace['customer_name'], $inputTrace['MERCHANT_PAN']);
        }

        $this->trace->info(
            TraceCode::BHARAT_QR_PAYMENT_PROCESS_REQUEST,
            $inputTrace
        );

        $bharatQr = null;
        $errorMessage = null;

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
                },
                // Avg response time of this whole route is about 300ms,
                // so 10x of that should be quite safe
                $ttl = 30,
                $errorCode = ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS,
                // A process will generally not need to do multiple retries at
                // all, since the retry times are adequate for the previous
                // process to complete. Still setting to 3 for freak occurrences.
                $retryCount = 3,
                // 2x and 4x of avg response time for this entire route
                // (not just the process within the lock)
                $minRetryDelay = 600,
                $maxRetryDelay = 1200);

            $valid = true;
        }
        catch (\Throwable $ex)
        {
            $this->alertException($ex, $input);

            $errorMessage = $ex->getMessage();

            switch ($errorMessage)
            {
                case TraceCode::BHARAT_QR_PAYMENT_DUPLICATE_NOTIFICATION:
                    $valid = true;
                    break;

                default:
                    $valid = false;
            }
        }
        finally
        {
            $isExpected = $bharatQr === null ? null : $bharatQr->isExpected();

            (new QrPaymentRequest\Service())->update($qrPaymentRequest, $isExpected, $bharatQr, $errorMessage, QrPaymentRequest\Type::BHARAT_QR);

            $methodDimension = ['bqr_method' => $gatewayResponse['qr_data']['method']];

            (new VirtualAccount\Metric())->pushPaymentMetrics(Constants\Entity::BHARAT_QR, $isExpected, $valid,
                                                              $terminal->getGateway(), $errorMessage, $methodDimension);
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

        // Slack alerts are only for prod
        if ($this->isEnvironmentProduction() === false)
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
