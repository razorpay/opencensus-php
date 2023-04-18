<?php

namespace RZP\Jobs;

use App;
use RZP\Diag\EventCode;
use RZP\Gateway\Base\Metric;
use Slack;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Capture extends Job
{
    const MAX_JOB_ATTEMPTS = 11;
    const JOB_RELEASE_WAIT = 5;

    // Make sure that this is below 900 (seconds) because SQS doesn't support
    // delay over 15 minutes.
    public $delay = 100;

    protected $trace;

    protected $data;

    protected $traceData;

    protected $slack;

    protected $queueConfigKey = 'capture';

    public function __construct(array $data)
    {
        parent::__construct($data['mode']);

        $this->data = $data;

        $this->traceData = [];
    }

    public function handle()
    {
        parent::handle();

        $this->slack = Slack::getFacadeRoot();

        $this->trace->info(
            TraceCode::PAYMENT_QUEUE_CAPTURE_REQUEST,
            $this->data
        );

        $payment = $this->fetchPayment();

        try
        {
            $this->runCaptureFlowForQueue($payment);

            $this->traceData['state'] = 'success';

            $this->traceData();

            $this->trace->info(
                TraceCode::PAYMENT_QUEUE_CAPTURE_SUCCESS,
                $this->data
            );

            app('diag')->trackPaymentEventV2(EventCode::PAYMENT_CAPTURE_QUEUE, $payment, null, [], $this->fetchProperties($payment));

            (new Payment\Metric)->pushCaptureQueueMetrics($payment, Metric::SUCCESS, $this->getDimensions());

            $this->delete();
        }
        catch (Exception\GatewayTimeoutException $ex)
        {
            app('diag')->trackPaymentEventV2(EventCode::PAYMENT_CAPTURE_QUEUE, $payment, $ex, [], $this->fetchProperties($payment));

            (new Payment\Metric)->pushCaptureQueueMetrics($payment, Metric::FAILED, $this->getDimensions(), $ex);

            $traceCode = TraceCode::PAYMENT_QUEUE_CAPTURE_FAILURE;

            $this->handleCaptureException($traceCode, $ex);
        }
        catch (\Exception $ex)
        {
            app('diag')->trackPaymentEventV2(EventCode::PAYMENT_CAPTURE_QUEUE, $payment, $ex, [], $this->fetchProperties($payment));

            (new Payment\Metric)->pushCaptureQueueMetrics($payment, Metric::FAILED, $this->getDimensions(), $ex);

            $traceCode = TraceCode::PAYMENT_CAPTURE_FAILURE_EXCEPTION;

            $this->handleCaptureException($traceCode, $ex);
        }
    }

    protected function runCaptureFlowForQueue($payment)
    {
        $basicAuth = App::getFacadeRoot()['basicauth'];

        $basicAuth->setMode($this->mode);

        $this->addTraceData($payment);

        // return if payment is already gateway captured or (status is refunded and  gateway is not paysecure and  captured_at is null)
        // there can be cases where refund is created state so we need to capture the payment to process the refund
        // paysecure does not support the reverse api so we need to gateway capture the payment and in auto refund case sometimes refund get initiated before capture call
        if (($payment->isGatewayCaptured() === true) or
            (($payment->getStatus() === Payment\Status::REFUNDED) and
             ($payment->hasBeenCaptured() === false) and
             ($payment->getGateway() !== Payment\Gateway::PAYSECURE)))
        {
            return;
        }

        $merchant = $payment->merchant;

        $paymentProcessor = new Payment\Processor\Processor($merchant);

        $paymentProcessor->callGatewayFunctionCaptureViaQueue($this->data, $payment);
    }

    protected function addTraceData($payment)
    {
        $data = [];

        $data['payment_id']             = $payment->getId();
        $data['merchant_id']            = $payment->getMerchantId();
        $data['gateway']                = $payment->getGateway();
        $data['status']                 = $payment->getStatus();
        $data['gateway_captured']       = $payment->getGatewayCaptured();
        $data['amount']                 = $payment->getAmount();
        $data['base_amount']            = $payment->getBaseAmount();
        $data['attempts']               = $this->attempts();

        $card = $payment->card;

        if (($card !== null) and
            ($card->inRelation !== null))
        {
            $iin = $card->inRelation;

            $data['network']  = $iin->getNetwork();

            $data['message_type'] = $iin->getMessageType();
        }

        $data['state'] = 'initiated';

        $this->traceData = $data;

        $this->traceData();
    }

    protected function traceData()
    {
        $this->trace->info(
            TraceCode::PAYMENT_QUEUE_CAPTURE,
            $this->traceData
        );
    }

    protected function handleCaptureException($traceCode, $ex)
    {
        $this->data['job_attempts'] = $this->attempts();

        $this->trace->error(
            $traceCode,
            $this->data
        );

        $this->trace->traceException($ex);

        $this->handleCaptureJobRelease();
    }

    protected function handleCaptureJobRelease()
    {
        if ($this->attempts() > self::MAX_JOB_ATTEMPTS)
        {
            $this->raiseAlerts();

            $this->delete();

            $this->traceData['state'] = 'deleted';
        }
        else
        {
            // When queue_driver is sync, there's no release and
            // hence it's as good as deleting the job.
            $this->release($this->getRetryTime());

            $this->traceData['state'] = 'reattempt';
        }

        $this->traceData();
    }

    protected function getRetryTime()
    {
        return (pow(2,$this->attempts() - 1)) * self::JOB_RELEASE_WAIT;
    }

    protected function raiseAlerts()
    {
        $this->trace->error(TraceCode::PAYMENT_QUEUE_CAPTURE_DELETE, [
            'data'         => $this->data,
            'job_attempts' => $this->attempts(),
            'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
        ]);
    }

    public function getData()
    {
        return $this->data;
    }

    protected function fetchProperties($payment)
    {
        return [
            'attempts' => $this->attempts(),
            'payment'  => [
                'status'            => $payment->getStatus(),
                'gateway_captured'  => $payment->getGatewayCaptured(),
            ],
        ];
    }

    protected function getDimensions()
    {
        return $dimensions = [
            'attempts'   => $this->attempts()
        ];
    }

    protected function fetchPayment()
    {
        $basicAuth = App::getFacadeRoot()['basicauth'];

        $basicAuth->setMode($this->mode);

        $payment = $this->repoManager->payment->findOrFail($this->data['payment']['id']);

        return $payment;
    }
}
