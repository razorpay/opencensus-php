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
    const PAYMENT_AUTHORIZATION_SUCCESS_EVENT = 'success';

    const PAYMENT_FAILURE_EVENT    = 'failure';

    protected $app;

    protected $mode;

    protected $sns;

    protected $trace;

    protected $sns_topic;

    public function __construct($app, $dopplerTopic)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->sns = $app['sns'];

        $this->mode = $this->app['rzp.mode'];

        $this->sns_topic = $dopplerTopic;
    }

    // sends event to doppler's topic
    public function sendFeedback(Payment\Entity $payment, string $authorizeStatus, string $errorCode, string $internalErrorCode)
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

            $eventData = $this->prepareEventForDoppler($payment, $authorizeStatus, $errorCode, $internalErrorCode);

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
            $this->sns->publish(json_encode($eventData), $this->sns_topic);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::DOPPLER_SERVICE_SNS_PUBLISH_FAILED, $eventData);
        }
    }

    protected  function prepareEventForDoppler(Payment\Entity $payment, string $authorizeStatus, string $errorCode, string $internalErrorCode)
    {
        // associated terminal from payment entity
        $terminal = $payment->terminal;

        if($terminal != null)
        {
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

            if($payment->hasCard() === true)
            {
                $card['card_iin'] = $payment->card->getIin();
                $card['card_network'] = $payment->card->getNetwork();
                $card['card_type'] = $payment->card->getType();
                $card['card_issuer'] = $payment->card->getIssuer();
                $upi['vpa'] = null;
                $upi['psp'] = null;
                $upi['bank'] = null;
            }

            if($payment->isUPI() === true)
            {
                $card['card_iin'] = null;
                $card['card_network'] = null;
                $card['card_type'] = null;
                $card['card_issuer'] = null;
                $upi['vpa'] = $payment->getVpa();
                $upi['psp'] = $payment->getPspFromVpa();
                $upi['bank'] = $payment->getBankName();
            }

            $data = [
                'payment_id'            => $payment->getId(),
                'method'                => $payment->getMethod(),
                'authorized'            => $authorizeStatus,
                'card'                  => $card,
                'upi'                   => $upi,
                'terminal'              => $payment->getTerminalId(),
                'gateway'               => $gateway,
                'terminalType'          => $terminalType,
                'device'                => $device,
                'os'                    => $os,
                'browser'               => $browser,
                'created_at'            => $payment->getCreatedAt(),
                'authorized_at'         => Carbon::now()->getTimestamp(),
                'error_code'            => $errorCode ?? null,
                'internal_error_code'   => "",
            ];

            return $data;
        }
    }
}
