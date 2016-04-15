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
use Models\Order;
use Models\Pricing;
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

        $this->orderRepo = new Order\Repository;

        $this->app = App::getFacadeRoot();
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
        else if (empty($input['method']))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Please provide appropriate payment method',
                Payment\Entity::METHOD);
        }

        $payment = $this->createPaymentEntity($input);

        $this->checkSignature($input, $payment);

        return $this->authorize($payment, $input);
    }

    public function processAndReturnFees(array & $input)
    {
        if (isset($input['method']) === false)
        {
            $input['method'] = Payment\Method::CARD;
        }
        else if (empty($input['method']))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Please provide appropriate payment method',
                Payment\Entity::METHOD);
        }

        $payment = $this->createDummyPaymentEntity($input);

        // Performing dummy set of processing for the same
        $this->dummyPrePaymentAuthorizeProcessing($payment, $input);

        $preCalculationOfFees = true;

        list($fee, $serviceTax, $ruleKey) =
                            (new Pricing\Fee)->calculateMerchantFees($payment, $preCalculationOfFees);

        $data = array(
            'originalAmount'    => $input['amount'],
            'fees'              => $fee,
            'razorpay_fee'      => $fee - $serviceTax,
            'serviceTax'        => $serviceTax,
            'amount'            => $input['amount'] + $fee
        );

        foreach ($data as $key => $value)
        {
            $data[$key] = $value / 100;
        }

        // Set new input amount and fees
        $input['amount'] = $input['amount'] + $fee;

        $input['fee'] = $fee;

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
                'merchant_order_id field is required',
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

        // use hash_equals to prevent timing attacks
        if (! hash_equals($signature, $input['signature']))
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
     * @param  string   $id      Id of payment to be captured
     * @param  array    $input
     *
     * @return Payment\Entity   Payment\Entity object
     */
    public function cancel($id, $input)
    {
        return $this->repo->transaction(function() use ($id, $input)
        {
            $payment = $this->retrieve($id);

            $payment = $this->repo->lockForUpdate($payment->getKey());

            (new Payment\Validator)->cancelValidate($payment);

            return $this->cancelPayment($payment, $input);
        });
    }

    protected function cancelPayment($payment)
    {
        $errorCode = null;

        if ((isset($input['platform'])) and
            ($input['platform'] === 'android_sdk'))
        {
            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_PRESSING_BACK_ON_ANDROID;
        }
        else
        {
            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER;
        }

        $e = new Exception\BadRequestException($errorCode);

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

        if ($terminal === null)
        {
            throw new Exception\LogicException(
                'Terminal should not be null here',
                ['payment_id' => $payment->getId()]);
        }

        $gateway = $this->payment->getGateway();

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

        $payment = new Payment\Entity;

        $payment->merchant()->associate($this->merchant);

        $payment->build($input);

        // Verify if the provided fee is within 5 p of our original fee
        if ($this->merchant->isFeeBearerCustomer())
        {
            $this->verifyProvidedFee($payment, $input);
        }

        $this->setOrderDetails($payment, $input);

        $this->payment = $payment;

        return $payment;
    }

    protected function createDummyPaymentEntity($input)
    {
        $payment = new Payment\Entity;

        $payment->merchant()->associate($this->merchant);

        $payment->build($input);

        $this->payment = $payment;

        return $payment;
    }

    protected function verifyProvidedFee($payment, $input)
    {
        // Get to original state and get back fee and tax
        // modifying input to be from old state
        $input['amount'] = $payment->getAmount() - $payment->getFee();

        $feesArray = $this->processAndReturnFees($input);

        $feeDifference = $input['fee'] - $payment->getFee();

        // $serviceTax = (new Pricing\Fee)->calculateServiceTaxFromFees($payment->getFee());

        // $serviceTaxDifference = $feesArray['serviceTax'] - $serviceTax;

        if ($this->getModValue($feeDifference) > 5)
            // or ($this->getModValue($serviceTaxDifference) > 5))
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_PAYMENT_FEES_OR_SERVICE_TAX_TAMPERED);
        }

    }

    protected function getModValue($val)
    {
        if ($val > 0)
        {
            return $val;
        }
        else
        {
            return (-1 * $val);
        }
    }

    protected function fetchOrderFromInput($input)
    {
        $orderId = (new Order\Entity)->verifyIdAndStripSign($input['order_id']);

        $order = $this->orderRepo->find($orderId);

        if ($order === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Order id provided not found.',
                'order_id');
        }

        if ($order->getMerchantId() !== $this->merchant->id)
        {
            // Merchant mismatch
            throw new Exception\BadRequestValidationFailureException(
                'Order id not found');
        }

        return $order;
    }

    protected function setOrderDetails($payment, $input)
    {
        if (empty($input['order_id']) === true)
        {
            if ($this->merchant->isTPVRequired())
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED,
                    Payment\Entity::ORDER_ID);
            }

            return;
        }

        $this->order = $this->fetchOrderFromInput($input);

        $amount = $payment->getAmount();

        // If the merchant is a tdr client, use the adjusted amount to
        // match order amount.
        if ($this->merchant->isFeeBearerCustomer())
        {
            $amount = $amount - $payment->getFee();
        }

        // Move this to a common validate function.
        $validator = new Order\Validator;

        $validator->validateOrderAmount($this->order, $amount);

        $validator->validateOrderNotPaid($this->order);

        $validator->validateMerchantSpecificData($this->order,
                                                    $this->merchant);

        $this->order->setStatus(Order\Status::ATTEMPTED);

        $this->order->incrementAttempts();

        $this->order->saveOrFail();

        $payment->order()->associate($this->order);
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
