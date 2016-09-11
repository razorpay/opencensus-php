<?php

namespace RZP\Models\Payment\Processor;

use App;
use BasicAuth;

use RZP\Constants\Mode;
use RZP\Dashboard\Dashboard;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Models\Order;
use RZP\Models\Payment\Status;
use RZP\Models\Pricing;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Customer;
use RZP\Models\Base\Lock;

class Processor
{
    use Authorize;
    use Callback;
    use Capture;
    use Refund;
    use Verify;
    use OtpResend;
    use Topup;

    /**
     * Callback urls can be hit multiple times by customers.
     * WIthin certain duration x minutes, we will return payment
     * success or failed when the url is hit again.
     * After that duration, we will simply throw
     * BAD_REQUEST_PAYMENT_ALREADY_PROCESSED payment_processed error.
     */
    const CALLBACK_PROCESS_AGAIN_DURATION = 20;

    /**
     * Number of days after which authorized payments
     * are auto-refunded
     */
    const AUTO_REFUND_TIME_PERIOD = 5;

    /**
     * If payment fails on gateway then we may retry it with a different terminal/gateway.
     */
    const MAX_RETRY_ATTEMPTS = 5;

    /**
     * If a payment gets converted to authorized from failed after 15 minutes of creation of payment,
     * we do not send a notification to the customer.
     */
    const FAILED_TO_AUTHORIZED_NOTIFY_DURATION = 900;

    /**
     * Payment can be cancelled in multiple ways, one of which being
     * by closing the payment pop-up that.
     * However, we only allow payment to be cancelled within a certain duration.
     * A payment created today can only be cancelled within few minutes and
     * not on next day.
     */
    const PAYMENT_CANCEL_TIME_DURATION = 1800;  // 30 min * 60 sec

    protected $merchant;
    protected $trace;
    protected $payment;
    protected $terminal;
    protected $mode;
    protected $repo;
    protected $orderRepo;
    protected $paymentRepo;
    protected $app;
    protected $lock;
    protected $request;
    protected $methods;
    protected $refund;

    protected $verifyRefundStatus;

    public function __construct(Merchant\Entity $merchant)
    {
        $this->app  = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
        $this->mode = $this->app['rzp.mode'];

        $this->merchant = $merchant;
        $this->methods = $merchant->methods;

        $this->checkMerchantPermissions();

        $this->repo = $this->app['repo'];

        $this->paymentRepo = $this->repo->payment;

        $this->orderRepo = $this->repo->order;

        $this->request = $this->app['request'];

        $this->lock = $this->app['api.lock'];

        // Only used in hdfc verify refund flow
        $this->verifyRefundStatus = null;
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
    }

    protected function verifySignature($input, $payment)
    {
        $data = array(
            'amount'            => $payment->getAmount(),
            'currency'          => $payment->getCurrency(),
            'merchant_order_id' => $payment->getNotes()['merchant_order_id'],
        );

        $signature = $this->getSignature($data);

        // use hash_equals to prevent timing attacks
        if (! hash_equals($signature, $input['signature']))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Signature does not match', 'signature');
        }

        return true;
    }

    protected function getSignature(array $data)
    {
        ksort($data);

        $str = implode('|', $data);

        return $this->app['basicauth']->sign($str);
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
        $status = null;

        $payment = $this->retrieve($id);

        $diff = time() - $payment->getCreatedAt();

        if ($diff > self::PAYMENT_CANCEL_TIME_DURATION)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_BE_CANCELLED);
        }

        // If payment is not in created state, then that means
        // it's already been processed. It's possible that payment
        // may have succeeded. In such cases, we need to send back
        // exact same response as we would have if the payment succeeded
        if ($payment->isCreated() === false)
        {
            return $this->processPaymentCallbackSecondTime($payment);
        }

        $errorCode = $this->repo->transaction(function() use ($payment)
        {
            $this->lockForUpdateAndReload($payment);

            $errorCode = $this->cancelPayment($payment);

            return $errorCode;
        });

        throw new Exception\BadRequestException($errorCode);
    }

    protected function cancelPayment($payment)
    {
        $errorCode = null;

        $payment->getValidator()->cancelValidate($payment);

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

        return $errorCode;
    }

    public function redirect($id)
    {
        $payment = $this->retrieve($id);

        if ($payment->isCreated() === false)
        {
            return $this->processPaymentCallbackSecondTime($payment);
        }

        throw new Exception\LogicException('Should not have been hit.');
    }

    public function callGatewayFunctionCaptureViaQueue($data, $payment)
    {
        $this->payment = $payment;

        $this->callGatewayFunction(Payment\Action::CAPTURE, $data);
    }

    protected function tracePaymentInfo($traceCode, $level = Trace::INFO)
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

        $status = $payment->getStatus();

        if (($status !== Status::CREATED) and ($status !== Status::AUTHORIZED))
        {
            throw new Exception\LogicException(
                'Payment not in the appropriate status to be marked as failed.',
                null,
                [
                    'payment_id'    => $payment->getId(),
                    'status'        => $status
                ]);
        }

        $payment->setStatus(Payment\Status::FAILED);

        $payment->setError($code, $desc, $internalCode);

        $this->repo->saveOrFail($payment);

        $this->tracePaymentFailed($error, $traceCode);

        $this->eventPaymentFailed();
    }

    protected function eventPaymentFailed()
    {
        $this->app['events']->fire('api.payment.failed', array($this->payment));
    }

    protected function setPaymentError($error)
    {
        $internalCode = $error->getInternalErrorCode();

        $payment = $this->payment;

        $payment->setInternalErrorCode($internalCode);

        $this->repo->saveOrFail($payment);
    }

    /**
     * Responsible for calling the gateway function
     *
     * @param  string $action      refund/capture etc.
     * @param  array  $gatewayData Relevant input for the corresponding
     *                             action
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
                null,
                ['payment_id' => $this->payment->getId()]);
        }

        $gateway = $this->payment->getGateway();

        $gatewayData['terminal'] = $terminal;
        $gatewayData['merchant'] = $this->payment->merchant;

        if ($gateway === Payment\Gateway::KOTAK)
        {
            $gatewayData['bank_account'] = $this->getMerchantBankAccount($terminal->merchant);
        }

        return $this->app['gateway']->call($gateway, $action, $gatewayData, $this->mode, $terminal);
    }

    protected function createPaymentEntity($input)
    {
        $this->tracePaymentNewRequest($input);

        $payment = new Payment\Entity;

        $payment->generateId();

        $payment->merchant()->associate($this->merchant);

        $payment->build($input);

        if ($this->merchant->isFeeBearerCustomer())
        {
            $this->verifyProvidedFee($payment, $input);
        }

        $this->setOrderDetails($payment, $input);

        $metadata = isset($input['_']) ? $input['_'] : null;

        $payment->setMetadata($metadata);

        $this->trace->info(
            TraceCode::PAYMENT_METADATA,
            ['metadata' => $metadata, 'payment_id' => $payment->getId()]);

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
        $token = $this->repo->token->getByWalletTerminalAndCustomerId(
                            $input['payment']['wallet'],
                            $input['payment']['terminal_id'],
                            $input['customer']->getId());

        return $token;
    }

    protected function retrieve($id)
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $this->payment = $this->repo->payment->findByIdAndMerchantId(
                                                $id, $this->merchant->getKey());

        return $this->payment;
    }

    /**
     * Sets both, the instance payment object and the passed
     * payment object, to the new payment object which is locked
     * for update.
     *
     * setRawAttributes is being used because of the way php
     * handles pass by reference for objects. If the passed object
     * is ASSIGNED to another object/value, the original object
     * from the calling function remains unaffected.
     * Any change ON the passed object will affect the original
     * object too.
     *
     * @param $payment
     */
    protected function lockForUpdateAndReload($payment)
    {
        $lockedPayment = $this->paymentRepo->lockForUpdate($payment->getKey());

        //
        // When $this->payment is being passed in the argument,
        // $this->payment will be the same object as $payment.
        // When $this->payment and $payment are two different objects,
        // we update both of them.
        //

        $this->payment->setRawAttributes($lockedPayment->getAttributes(), true);

        $payment->setRawAttributes($lockedPayment->getAttributes(), true);
    }

    protected function setPayment($payment)
    {
        $this->payment = $payment;
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

    protected function shouldAutoCapture($payment)
    {
        // If payment is not authorized or if it's late authorized,
        // do not auto capture it, irrespective of it being a signed
        // payment or marked for auto capture.
        if (($payment->isAuthorized() === false) or
            ($payment->isLateAuthorized() === true))
        {
            return false;
        }

        // If payment is signed
        if ($payment->isSigned() === true)
        {
            return true;
        }

        // If payment order was marked as auto capture
        if (($payment->order !== null) and
            ($payment->order->getPaymentCapture() === true))
        {
            return true;
        }

        return false;
    }

    protected function acquireLockOnPayment($payment)
    {
        $resource = $payment->getId();

        if ($this->lock->acquire($resource) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }
    }

    protected function releaseLockOnPayment($payment)
    {
        $this->lock->release($this->payment->getId());
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
