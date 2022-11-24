<?php

namespace RZP\Services;

use RZP\Http\Request\Requests;
use RZP\Error;
use RZP\Exception;
use Carbon\Carbon;
use \WpOrg\Requests\Session as Requests_Session;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Environment;
use RZP\Models\Payment\Method;
use RZP\Gateway\Upi\Base\Repository;
use RZP\Gateway\Upi\Base\ProviderCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Terminal\Entity as TerminalEntity;


class Doppler
{
    const PAYMENT_AUTHORIZATION_SUCCESS_EVENT = 'success';

    const PAYMENT_AUTHORIZATION_FAILURE_EVENT = 'failure';

    const SESSION_ID = 'rzp_api_session';

    const CONTENT_TYPE_HEADER      = 'Content-Type';

    const ACCEPT_HEADER            = 'Accept';

    const APPLICATION_JSON         = 'application/json';

    const X_RAZORPAY_TASKID_HEADER = 'X-Razorpay-TaskId';

    const X_RAZORPAY_APP_HEADER    = 'X-Razorpay-App';

    const CONNECT_TIMEOUT = 1;

    const REQUEST_TIMEOUT = 5;

    const MAX_RETRY_COUNT = 1;

    const ERROR = 'error';

    const ERROR_MESSAGE = 'error_message';

    const ERROR_CODE = 'error_code';

    protected $app;

    protected $mode;

    protected $sns;

    protected $trace;

    protected $repo;

    protected $config;

    protected $request;

    protected $sns_topic;

    protected $sendDopplerFeedback = true;

    public function __construct($app, $dopplerTopic)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->sns = $app['sns'];

        $this->mode = $this->app['rzp.mode'];

        $this->sns_topic = $dopplerTopic;

        $this->repo = $app['repo'];

        $this->config = $app['config']->get('applications.doppler');

        if ($this->request === null)
        {
            $this->request = $this->initRequestObject();
        }
    }

    protected function initRequestObject()
    {
        $baseUrl = $this->getUrl();

        $defaultHeaders = $this->getDefaultHeaders();

        $defaultOptions = $this->getDefaultOptions();

        $request = new Requests_Session($baseUrl, $defaultHeaders, [], $defaultOptions);

        return $request;
    }

    // sends event to doppler's topic
    public function sendFeedback(Payment\Entity $payment, string $authorizeStatus, $errorCode = null, array $internalErrorDetails = [], $paymentRetryAttempt = null)
    {
        // We do not want to publish events in case for test mode payments
        if ($this->mode === Mode::TEST)
        {
            return;
        }

        // publishing event to doppler's topic if payment method is card/upi/netbanking
        if (($payment->getMethod() === Method::CARD) or
            ($payment->getMethod() === Method::UPI)  or
            ($payment->getMethod() === Method::NETBANKING))
        {

            $eventData = $this->prepareEventForDoppler($payment, $authorizeStatus, $errorCode, $internalErrorDetails, $paymentRetryAttempt);

            if ($this->sendDopplerFeedback === true)
            {
                $this->sendDopplerEventRequest($eventData);
            }
            else
            {
                $this->trace->info(TraceCode::DOPPLER_SERVICE_SNS_PUBLISH_CANCEL, [
                    'payment_id' => $payment->getId(),
                    'payment_method' => $payment->getMethod(),
                ]);
            }

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

    protected  function prepareEventForDoppler(Payment\Entity $payment, string $authorizeStatus, $errorCode = null, array $internalErrorDetails = [], $paymentRetryAttempt = null)
    {

        $card = [];

        $upi = [];

        $netbanking = [];

        $terminalType = null;

        $gateway = null;

        $device = null;

        $browser = null;

        $os = null;

        // associated terminal from payment entity
        $terminal = $payment->terminal;

        if($terminal != null)
        {
            $gateway = $terminal->getGateway();

            $terminalType = $terminal->isShared() ? TerminalEntity::SHARED : TerminalEntity::DIRECT;
        }

        $paymentAnalytics = $payment->getMetadata('payment_analytics');

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
            $upi['vpa_handle'] = null;
            $upi['psp'] = null;
            $upi['bank'] = null;
            $upi['type'] = null;
            $upi['tpv'] = null;
            $netbanking['bank'] = null;
        }

        if($payment->isUPI() === true)
        {
            $upiEntity = $this->repo->upi_metadata->fetchByPaymentId($payment->getId());
            if (isset($upiEntity) === true)
            {
                $type = $upiEntity->getFlow();
            }
            else
            {
                $this->sendDopplerFeedback = false;
                $type = null;
            }


            $vpaHandle = $payment->getVpaHandleFromVpa();
            if (strlen($vpaHandle) == 0)
            {
                $vpaHandle = null;
            }
            $card['card_iin'] = null;
            $card['card_network'] = null;
            $card['card_type'] = null;
            $card['card_issuer'] = null;
            $upi['vpa'] = $payment->getVpa();
            $upi['vpa_handle'] = $vpaHandle;
            $upi['psp'] = ProviderCode::getPsp($vpaHandle) ?? null;
            $upi['bank'] = $payment->getBankName() ?? null;
            $upi['type'] = $type;
            $upi['tpv'] = $payment->merchant->isTPVRequired();
            $netbanking['bank'] = null;
        }

        if($payment->isNetbanking() === true)
        {
            $card['card_iin'] = null;
            $card['card_network'] = null;
            $card['card_type'] = null;
            $card['card_issuer'] = null;
            $upi['vpa'] = null;
            $upi['vpa_handle'] = null;
            $upi['psp'] = null;
            $upi['bank'] = null;
            $upi['type'] = null;
            $upi['tpv'] = null;
            $netbanking['bank'] = $payment->getBankName();
        }

        $reqObj = [
            'payment_id'            => $payment->getId(),
            'method'                => $payment->getMethod(),
            'merchant_id'           => $payment->getMerchantId(),
            'authorized'            => $authorizeStatus,
            'card'                  => $card,
            'upi'                   => $upi,
            'netbanking'            => $netbanking,
            'terminal'              => $payment->getTerminalId(),
            'gateway'               => $gateway,
            'terminal_type'         => $terminalType,
            'device'                => $device,
            'os'                    => $os,
            'browser'               => $browser,
            'created_at'            => $payment->getCreatedAt(),
            'authorized_at'         => Carbon::now()->getTimestamp(),
            'error_code'            => $errorCode ?? null,
            'step'                  => $internalErrorDetails[Error\Error::STEP] ?? null,
            'source'                => $internalErrorDetails[Error\Error::SOURCE] ?? null,
            'reason'                => $internalErrorDetails[Error\Error::REASON] ?? null,
            'internal_error_code'   => $internalErrorDetails[Error\Error::INTERNAL_ERROR_CODE] ?? null,
            'attempt'               => $paymentRetryAttempt ?? null,
        ];


        $data = [
            'session_id' => self::SESSION_ID,
            'reqObj'     => $reqObj,
        ];

        return $data;
    }

    protected function getUrl(): string
    {
        return $this->config['url'];
    }

    protected function getDefaultHeaders(): array
    {
        $headers = [
            self::CONTENT_TYPE_HEADER      => self::APPLICATION_JSON,
            self::ACCEPT_HEADER            => self::APPLICATION_JSON,
            self::X_RAZORPAY_APP_HEADER    => 'api',
        ];

        return $headers;
    }

    protected function getDefaultOptions(): array
    {
        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
        ];

        return $options;
    }
}
