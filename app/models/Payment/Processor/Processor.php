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
use Models\Customer;

class Processor
{
    use Authorize;
    use Capture;
    use Callback;
    use Refund;
    use Verify;
    use OtpResend;
    use Topup;

    protected $merchant;

    protected $core;

    protected $trace;

    protected $payment;

    protected $terminal;

    protected $mode;

    protected $repo;

    protected $verifyRefundStatus;

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

        // Only used in hdfc verify refund flow
        $this->verifyRefundStatus = null;
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

        // Creates a payment entity in DB with the input values given.
        // Also takes care of fee-bearer customer flow.
        $payment = $this->createPaymentEntity($input);

        // This flow is being used for only hosted (Shopify).
        $this->checkSignature($input, $payment);

        // The first step in talking to the respective gateway.
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

        //
        // We only create a dummy payment entity for purpose
        // of pre-calculating fees and returning it.
        // It's not going to be saved in the database.
        //
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

        // Converts all the amounts to rupees
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
     * @return $status Payment\Status
     */
    public function cancel($id, $input)
    {
        return $this->repo->transaction(function() use ($id, $input)
        {
            $status = null;

            $payment = $this->retrieve($id);

            $createdAt = $payment->getCreatedAt();

            $diff = time() - $createdAt;

            if ($diff > 30 * 60)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Payment created long back and cannot be cancelled now');
            }

            if (($payment->isAuthorized()) or
                ($payment->isCaptured()))
            {
                return Payment\Status::AUTHORIZED;
            }

            $payment = $this->repo->lockForUpdate($payment->getKey());

            return $this->cancelPayment($payment, $input);
        });
    }

    protected function cancelPayment($payment)
    {
        $errorCode = null;

        (new Payment\Validator)->cancelValidate($payment);

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

        return Payment\Status::FAILED;
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
     * @throws Exception\LogicException
     */
    protected function callGatewayFunction($action, array $gatewayData)
    {
        $terminal = $this->payment->terminal;

        if ($terminal === null)
        {
            throw new Exception\LogicException(
                'Terminal should not be null here',
                ['payment_id' => $this->payment->getId()]);
        }

        $gateway = $this->payment->getGateway();

        $gatewayData['terminal'] = $terminal;
        $gatewayData['merchant'] = $this->payment->merchant;

        if ($gateway === Payment\Gateway::KOTAK)
        {
            $gatewayData['bank_account'] = $this->getMerchantBankAccount($terminal->merchant);
        }

        return Gateway::call($gateway, $action, $gatewayData, $this->mode, $terminal);
    }

    protected function createPaymentEntity($input)
    {
        $this->tracePaymentNewRequest($input);

        $payment = new Payment\Entity;

        $payment->merchant()->associate($this->merchant);

        $payment->build($input);

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

    /**
     * When customer is fee-bearer, the amount received from checkout is
     * inclusive of fees. (Fees is not received from checkout when
     * merchant is the fee bearer.
     * For a robust verification, we re-calculate the fees from the base
     * amount and verify that it's the same as received from checkout.
     *
     * @param Payment\Entity $payment
     * @param $input
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function verifyProvidedFee($payment, $input)
    {
        // Set the amount back to the base amount (without our fee and tax).
        $input['amount'] = $payment->getAmount() - $payment->getFee();

        // Re-calculates fees on the amount, using a dummy payment creation flow.
        // Also sets re-calculated fee and amount value (in paise) in $input.
        $feesArray = $this->processAndReturnFees($input);

        // The difference between the fees received from checkout and
        // and the fees re-calculated again. Ideally, this should be 0.
        $feeDifference = $input['fee'] - $payment->getFee();

        if (abs($feeDifference) > 5)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_PAYMENT_FEES_OR_SERVICE_TAX_TAMPERED);
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

        // If the merchant is a customer-fee-bearer client, use the adjusted amount to
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
                                                 $payment);

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

    protected function retrieveToken($input)
    {
        $this->token = (new Customer\Token\Repository)
                        ->getByWalletTerminalAndCustomerId(
                            $input['payment']['wallet'],
                            $input['payment']['terminal_id'],
                            $input['customer']->getId());

        return $this->token;
    }

    protected function retrieve($id)
    {
        $this->payment = $this->core->retrieveByIdAndMerchantId(
                                    $id, $this->merchant->getKey());

        $card = $this->payment->card()->first();

        return $this->payment;
    }

    protected function lockForUpdateAndRetrievePayment(& $payment)
    {
        $payment = $this->repo->lockForUpdate($payment->getKey());

        $this->payment = $payment;

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
            'ifsc_code'             => BankAccount\Entity::SPECIAL_IFSC_CODE,
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

    protected function createOrUpdateToken($input, $data)
    {
        $token = $this->retrieveToken($input);

        if ($token === null)
        {
            $token = (new Customer\Token\Core)
                        ->create($input['customer'], $data['token']);
        }
        else
        {
            $token->fill($data['token']);
            $token->saveOrFail();
        }

        return $token;
    }

    protected function getFormattedContact($contact)
    {
        return substr($contact, -10);
    }
}
