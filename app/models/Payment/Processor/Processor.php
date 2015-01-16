<?php

namespace Models\Payment\Processor;

use BasicAuth;
use Dashboard\Dashboard;
use EE\Exception;
use EE\Error\ErrorCode;
use Http\Route;
use Models\Gateway;
use Models\Merchant;
use Models\Terminal;
use Models\Payment;
use Request;
use Trace\Trace;
use Trace\TraceCode;

class Processor
{
    use Authorize;
    use Capture;
    use Callback;
    use Refund;

    protected $merchant;

    protected $core;

    protected $trace;

    protected $payment;

    protected $terminal;

    protected $mode;

    protected $repo;

    public function __construct(
        Merchant\Entity $merchant,
        Payment\Core $core,
        Trace $trace,
        $mode)
    {
        $this->merchant = $merchant;
        $this->core = $core;
        $this->trace = $trace;
        $this->mode = $mode;

        $this->checkMerchantPermissions();

        $this->repo = new Payment\Repository;
    }

    public static function create($bindings)
    {
        return new self(
            $bindings['merchant'],
            $bindings['core'],
            $bindings['trace'],
            $bindings['mode']);
    }

    public function process($input)
    {
        if (isset($input['method']) === false)
        {
            $input['method'] = Payment\Method::CARD;
        }

        $payment = $this->createPaymentEntity($input);

        if ($input['method'] === Payment\Method::CARD)
        {
            return $this->authorize($payment, $input);
        }
        else if ($input['method'] === Payment\Method::NET_BANKING)
        {
            return $this->captureNetBanking($payment);
        }
    }

    protected function checkMerchantPermissions()
    {
        $merchant = $this->merchant;

        $mode = $this->mode;

        if ($mode === 'test')
        {
            return;
        }

        // On live request, ensure that merchant is activated
        if ($merchant->isActivated() === false)
        {
            throw new Exception\LogicException(
                'A non-activated merchant is making live request. Blasphemy!');
        }

        // On live request, ensure that merchant isn't blocked temporarily
        if ($merchant->isLive() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED);
        }
    }

    protected function trace($traceCode, $level = Trace::INFO)
    {
        $data = $this->payment->toArrayTraceRelevant();

        $this->trace->addRecord($level, $traceCode, $data);
    }

    protected function updatePaymentFailed($error, $traceCode)
    {
        $code = $error->getPublicErrorCode();

        $desc = $error->getDescription();

        $payment = $this->payment;

        $payment->setStatus(Payment\Status::FAILED);

        $payment->setError($code, $desc);

        $payment->save();

        $this->tracePaymentFailed($error, $traceCode);
    }

    /**
     * Responsible for calling the gateway function
     *
     * @param  string $action refund/capture etc.
     * @param  array  $input  Relevant input for the corresponding
     *                        action
     *
     * @return array or null
     */
    protected function callGatewayFunction($action, array $input)
    {
        $terminal = $this->payment->terminal;

        $gateway = $this->payment->getGateway();

        return Gateway::call($gateway, $action, $input, $this->mode, $terminal);
    }

    protected function setTerminalForPayment($payment)
    {
        if ($this->terminal !== null)
        {
            return $this->terminal;
        }

        $gateway = $payment->getGateway();

        $terminal = (new Terminal\Repository)->getByMerchantIdAndGateway(
                                                    $this->merchant->getKey(), $gateway);

        if (($terminal === null) or
            ($terminal->trashed()))
        {
            $method = $payment->getAttribute(Payment\Entity::METHOD);
            if ($method === Payment\Method::NET_BANKING)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_NET_BANKING_NOT_ENABLED);
            }

            throw new \LogicException(
                'No terminal found for merchant: ' . $this->merchant->getKey());
        }

        $this->terminal = $terminal;

        $payment->terminal()->associate($terminal);

        return $terminal;
    }

    protected function setGatewayForPayment($payment)
    {
        $gateway = '';

        $method = $payment['method'];

        if ($method === Payment\Method::CARD)
        {
            $gateway = Payment\Gateway::HDFC;
        }
        else if ($method === Payment\Method::NET_BANKING)
        {
            $gateway = Payment\Gateway::ATOM;
        }
        else
        {
            throw new Exception\LogicException(
                'Unrecognized payment method ' . $method);
        }

        $payment->setGateway($gateway);

        return $gateway;
    }

    protected function getCallbackUrl()
    {
        $url = \URL::route('payment_callback', ['id' => $this->payment->getPublicId()], false);

        $scheme = Request::getScheme().'://';
        $host = Request::getHost();
        $key = BasicAuth::getPublicKey();

        $callbackUrl = $scheme . $key . '@' . $host . $url;

        return $callbackUrl;
    }

    protected function createPaymentEntity($input)
    {
        $this->tracePaymentNewRequest($input);

        $payment = (new Payment\Entity)->build($input);

        $payment->merchant()->associate($this->merchant);

        $this->setGatewayForPayment($payment);

        $this->setTerminalForPayment($payment);

        $this->payment = $payment;

        return $payment;
    }

    protected function tracePaymentFailed($error, $traceCode)
    {
        $traceData = array_merge(
                        $this->payment->toArrayTraceRelevant(),
                        ['error' => $error->getAttributes()]);

        $level = 'info';

        if ($error->isGatewayError())
        {
            $level = 'critical';
        }

        // Tracing
        $this->trace->$level(
            $traceCode,
            $traceData);
    }

    protected function retrieve($id)
    {
        $this->payment = $this->core->retrieveByIdAndMerchantId(
                                    $id, $this->merchant->getKey());

        $card = $this->payment->card()->first();
        return $this->payment;
    }

    protected function tracePaymentNewRequest($input)
    {
        $this->trace->debug(TraceCode::PAYMENT_NEW_REQUEST, $input);
    }

    protected function notifyDashboard($type, $entity)
    {
        Dashboard::send($type, $entity);
    }
}
