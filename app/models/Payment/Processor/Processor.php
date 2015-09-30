<?php

namespace Models\Payment\Processor;

use App;
use Constants\Mode;
use BasicAuth;
use Dashboard\Dashboard;
use EE\Exception;
use EE\Error\ErrorCode;
use Http\Route;
use Models\Gateway;
use Models\Merchant;
use Models\Merchant\BankAccount;
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
    use Verify;

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
        $this->methods = $merchant->methods;
        $this->core = $core;
        $this->trace = $trace;
        $this->mode = $mode;
        $this->app  = App::getFacadeRoot();

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

        return $this->authorize($payment, $input);
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

        $this->trace->info(
            TraceCode::MISC_TRACE_CODE,
            ['merchant_id' => $merchant->getId(),
             'live' => $merchant->isLive()]);
    }

    protected function verifyMerchantIsLiveForLiveRequest()
    {
        // On live request, ensure that merchant isn't blocked temporarily
        if (($this->mode === Mode::LIVE) and
            ($this->merchant->isLive() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED);
        }
    }

    /**
     * Cancels a previously created payment
     *
     * @param  string  $id      Id of payment to be captured
     * @param  integer $amount  Amount to capture
     *
     * @return Payment\Entity   Payment\Entity object
     */
    public function cancel($id)
    {
        $payment = $this->retrieve($id);

        (new Payment\Validator)->cancelValidate($payment);

        return $this->cancelPayment($payment);
    }

    protected function cancelPayment($payment)
    {
        $e = new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER);

        $this->updatePaymentFailed($e->getError(), TraceCode::PAYMENT_CANCELLED);

        return [];
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

        $internalCode = $error->getInternalErrorCode();

        $payment = $this->payment;

        $payment->setStatus(Payment\Status::FAILED);

        $payment->setError($code, $desc, $internalCode);

        $payment->saveOrFail();

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

        if ($terminal === null)
        {
            return;
        }

        $input['terminal'] = $terminal;
        $input['merchant'] = $terminal->merchant;

        if ($gateway === Payment\Gateway::KOTAK)
        {
            $input['bank_account'] = $this->getMerchantBankAccount($terminal->merchant);
        }

        return Gateway::call($gateway, $action, $input, $this->mode, $terminal);
    }

    protected function createPaymentEntity($input)
    {
        $this->tracePaymentNewRequest($input);

        $payment = (new Payment\Entity)->build($input);

        $payment->merchant()->associate($this->merchant);

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

    protected function setPayment($payment)
    {
        $this->payment = $payment;

        $card = $this->payment->card()->first();
    }

    protected function tracePaymentNewRequest($input)
    {
        // @note: please keep this line here. It unsets card input in case
        // it's present
        unset($input['card']);
        $this->trace->debug(TraceCode::PAYMENT_NEW_REQUEST, $input);
    }

    protected function notifyDashboard($type, $entity)
    {
        Dashboard::send($type, $entity);
    }

    protected function getMerchantBankAccount($merchant)
    {
        $ba = $merchant->bankAccount;

        if ($ba !== null)
        {
            return $ba;
        }

        assert ($this->mode === Mode::TEST);

        $attributes = array(
            'merchant_id'           => $merchant->getId(),
            'ifsc_code'             => 'RZPB0000000',
            'beneficiary_name'      => $merchant->getAttribute('name'),
            'beneficiary_code'      => strtoupper(random_alpha_string(4)),
            'account_number'        => random_integer(11),
            'beneficiary_city'      => 'Mumbai',
            'beneficiary_state'     => 'MH',
            'beneficiary_country'   => 'IN',
            'beneficiary_pin'       => '400069',
            'beneficiary_mobile'    => '9393993939',
        );

        $ba = (new BankAccount\Entity)->newInstance($attributes, true);

        $ba->merchant()->associate($merchant);

        $merchant->setRelation('bankAccount', $ba);

        return $ba;
    }
}
