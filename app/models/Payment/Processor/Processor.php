<?php

namespace Models\Payment\Processor;

use Constants\Mode;
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

        $this->checkSignature($input, $payment);

        $data = $this->authorize($payment, $input);

        //
        // The returned value could be either Payment
        // model or an array containing callback data.
        // We convert payment model to array
        // if it's a payment model
        //
        if ($data instanceof Payment\Entity)
        {
            // This is a payment instance
            $payment = $data;

            if ($payment->isSigned())
            {
                $data = $this->captureSignedPayment($payment);
            }
            else
            {
                // Return array with fields after authorized
                $data = ['razorpay_payment_id' => $payment->getPublicId()];
            }
        }

        return $data;
    }

    protected function checkSignature($input, $payment)
    {
        if (isset($input['signature']) === false)
        {
            return;
        }

        $payment->setSigned(true);

        if (isset($input['notes']['merchant_order_id']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'merchant_roder_id field is required',
                'merchant_order_id');
        }

        $this->verifySignature($input, $payment);

        return true;
    }

    protected function captureSignedPayment($payment)
    {
        $amount = $payment->getAmount();

        $payment = $this->capturePayment($payment, $amount);

        $data = array(
            'razorpay_payment_id'   => $payment->getPublicId(),
            'amount'                => $payment->getAmount(),
            'currency'              => $payment->getCurrency(),
            'merchant_order_id'     => $payment->getNotes()['merchant_order_id'],
        );

        $sortedData = $data;
        ksort($sortedData);

        $str = implode('|', $sortedData);

        $data['signature'] = $this->getSignature($str);

        return $data;
    }

    protected function verifySignature($input, $payment)
    {
        $data = array(
            'amount'            => $payment->getAmount(),
            'currency'          => $payment->getCurrency(),
            'merchant_order_id' => $payment->getNotes()['merchant_order_id'],
        );

        $str = implode('|', $data);

        $signature = $this->getSignature($str);

        if ($signature !== $input['signature'])
        {
            throw new Exception\BadRequestValidationFailureException(
                'Signature does not match', 'signature');
        }

        return true;
    }

    protected function getSignature($str)
    {
        return \BasicAuth::sign($str);
    }

    protected function checkMerchantPermissions()
    {
        $merchant = $this->merchant;

        $mode = $this->mode;

        if ($mode === Mode::TEST)
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

    public function verify($id)
    {
        $payment = $this->retrieve($id);

        $data = array('payment' => $payment->toArray());

        $payment = $this->callGatewayFunction(Payment\Action::VERIFY, $data);
    }

    protected function createPaymentEntity($input)
    {
        $this->tracePaymentNewRequest($input);

        $payment = (new Payment\Entity)->build($input);

        $payment->merchant()->associate($this->merchant);

        (new TerminalPicker)->selectTerminal($payment);

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
