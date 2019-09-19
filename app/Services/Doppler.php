<?php

namespace RZP\Services;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Terminal\Entity as TerminalEntity;


class Doppler
{
    const PAYMENT_SUCCESS_EVENT    = 'success';

    const PAYMENT_FAILURE_EVENT    = 'failure';

    protected $app;

    protected $mode;

    protected $sns;

    protected $trace;

    const SNS_CLIENT = 'doppler';

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->sns = $app['sns'];

        $this->mode = $this->app['rzp.mode'];
    }

    // sends event to doppler's topic
    public function sendFeedback(Payment\Entity $payment, string $paymentStatus)
    {
        // We do not want to publish events in case for test mode payments
        if ($this->mode === Mode::TEST)
        {
            return;
        }

        // publishing event to doppler's topic if payment method is card/upi
        if (($payment->getMethod() === Method::CARD) or
            ($payment->getMethod() === Method::UPI))
        {

            $eventData = $this->prepareEventForDoppler($payment, $paymentStatus);

            $this->sendDopplerEventRequest($eventData);
        }
    }

    /**
     * Dispatch event data to doppler service via SNS.
     *
     * @param array $eventData
     */
    protected function sendDopplerEventRequest(array $eventData)
    {
        try
        {
            $this->sns->publish(json_encode($eventData), self::SNS_CLIENT);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::DOPPLER_SERVICE_SNS_PUBLISH_FAILED, $eventData);
        }
    }

    protected  function prepareEventForDoppler(Payment\Entity $payment, string $paymentStatus)
    {
        // associated terminal from payment entity
        $terminal = $payment->terminal;

        $gateway = $terminal->getGateway();

        $terminalType = $terminal->isShared() ? TerminalEntity::SHARED : TerminalEntity::DIRECT;

        $card = [];

        $upi = [];

        $device = null;

        $browser = null;

        $os = null;

        $paymentAnalytics = $payment->getMetadata("payment_analytics");

        if (is_null($paymentAnalytics) === false)
        {
            $device = $paymentAnalytics->getDevice();

            $browser = $paymentAnalytics->getBrowser();

            $os = $paymentAnalytics->getOs();
        }

        switch ($payment->getMethod())
        {
            case Method::CARD :
                $card['card_iin'] = $payment->card->getIin();
                $card['card_network'] = $payment->card->getNetwork();
                $card['card_type'] = $payment->card->getType();
                $card['card_issuer'] = $payment->card->getIssuer();
                $upi['vpa'] = null;
                $upi['bank'] = null;
                break;

            case Method::UPI:
                $card['card_iin'] = null;
                $card['card_network'] = null;
                $card['card_type'] = null;
                $card['card_issuer'] = null;
                $upi['vpa'] = $payment->getVpa();
                $upi['bank'] = $payment->getBankName();
                break;
        }

        $data = [
            'payment_id'    => $payment->getId(),
            'method'        => $payment->getMethod(),
            'authorized'    => $paymentStatus,
            'card'          => $card,
            'upi'           => $upi,
            'terminal'      => $payment->getTerminalId(),
            'gateway'       => $gateway,
            'terminalType'  => $terminalType,
            'device'        => $device,
            'os'            => $os,
            'browser'       => $browser,
            'created_at'    => $payment->getCreatedAt(),
            'authorized_at' => Carbon::now()->getTimestamp(),
        ];

        return $data;
    }

}
