<?php

namespace RZP\Jobs;

use App;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class TurboPayeeCallbackExecutor extends Job
{
    protected $input;
    protected $startTime;
    protected $gatewayDriver;
    protected $mode;
    protected $app;
    protected $paymentId;

    protected $queueConfigKey = 'turbo_upi_payee_callback_executor';

    public function __construct(array $input, string $mode, float $startTime, string $gatewayDriver)
    {
        parent::__construct();

        $this->input         = $input;
        $this->mode          = $mode;
        $this->startTime     = $startTime;
        $this->gatewayDriver = $gatewayDriver;
    }

    /**
     * This handle will resume the Turbo payee callback execution and update the payment status in DB.
    */
    public function handle()
    {
        parent::handle();
        $this->app = App::getFacadeRoot();

        try
        {
            $gateway = $this->app['gateway']->gateway($this->gatewayDriver);

            $this->paymentId = $gateway->getPaymentIdFromServerCallback($this->input, $this->gatewayDriver);
            $this->paymentId = Payment\Entity::getSignedId($this->paymentId);

            $traceInfo = ['gateway' => $gateway, 'payment_id'=> $this->paymentId];
            $this->trace->info(
                TraceCode::TURBO_PAYEE_CALLBACK_ASYNC_EXEC,
                $traceInfo
            );

            (new Payment\Service)->s2sCallback($this->paymentId, $this->input);
            $this->logCallbackResponseTime($this->startTime, $this->gatewayDriver);

            $this->trace->info(
                TraceCode::TURBO_PAYEE_CALLBACK_ASYNC_EXEC_SUCCESSFUL,
                $traceInfo
            );

            $this->delete();
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::TURBO_PAYEE_CALLBACK_ASYNC_EXEC_FAILED,
                [
                    'gateway'      => $this->gatewayDriver,
                    'payment_id'   => $this->paymentId,
                    'message'      => $e->getMessage(),
                ]
            );
            $this->delete();
        }
    }

    /**
     * pushes the time taken for callback processing
     *
     * @param float $startTime
     * @param string $gateway
     * @return void
     */
    private function logCallbackResponseTime(float $startTime, string $gateway, bool $rearch = false, bool $unexpected = false)
    {
        try
        {
            $responseTime = get_diff_in_millisecond($startTime);

            $dimensions = [
                "payment_gateway"                       => $gateway,
                Payment\Metric::PAYMENT_REQUEST_ROUTE   => "gateway_payment_callback_post",
                "payment_rearch"                        => $rearch,
                "unexpected"                            => $unexpected,
            ];

            $this->trace->histogram(Payment\Metric::PAYMENT_UPI_CALLBACK_REQUEST_TIME, $responseTime, $dimensions);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::UPI_CALLBACK_ERROR_LOGGING_RESPONSE_TIME_METRIC
            );
        }
    }
}
