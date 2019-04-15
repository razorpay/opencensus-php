<?php

namespace RZP\Models\VirtualAccount;

use App;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\VirtualAccount;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

abstract class Processor extends Base\Core
{
    protected $virtualAccount;

    protected $validator;

    protected $receiver;

    protected $paymentProcessor;

    protected $useSharedVirtualAccount;

    const METHOD_NOT_ENABLED_CODE_REGEX = '/BAD_REQUEST_PAYMENT_(%s)_NOT_ENABLED_FOR_MERCHANT/';

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;
    }

    protected function getPaymentProcessor()
    {
        if (isset($this->paymentProcessor) === false)
        {
            $this->paymentProcessor = new PaymentProcessor($this->merchant);
        }

        return $this->paymentProcessor;
    }

    /**
     * Entry point for  virtual account  process flow.
     * Check if the payment was a duplicate.
     * - payment was duplicate?
     *   - Yes
     *    - Do nothing.
     *   - No
     *    - payment is expected?
     *     - Yes
     *      - Set VA to the recognized VA
     *     - No
     *      - Set VA to demo merchant's static VA
     *
     * @param Base\PublicEntity $entity
     *
     * @return Base\PublicEntity
     */
    public function process(Base\PublicEntity $entity)
    {
        if ($this->isDuplicate($entity) === true)
        {
            //
            // The payment is an expected one, i.e. it is made to a valid account
            // but the UTR is a duplicate, indicating that a payment is being processed
            // for a second time. In this case, we do nothing.
            //

            return null;
        }

        $paymentExpected = $this->checkPaymentExpectedAndSetVirtualAccount($entity);

        $entity->setExpected($paymentExpected);

        $this->setMerchant();

        $entity = $this->processPayment($entity);

        // This will be null in case of
        // bank transfer payments if the payment
        // is made to reserved account
        if ($entity === null)
        {
            return null;
        }

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_PAYMENT_SUCCESSFUL,
            $entity->toArray());

        return $entity;
    }

    abstract protected function isDuplicate(Base\PublicEntity $entity);

    abstract protected function processPayment(Base\PublicEntity $entity);

    abstract protected function getVirtualAccountFromEntity(Base\PublicEntity $entity);

    abstract protected function getReceiver();

    protected function shouldRefundOrderPayment(Base\PublicEntity $entity)
    {
        if ($entity->virtualAccount->hasOrder() === false)
        {
            return false;
        }

        // If Virtual Account has an Order but Bank Transfer/BharatQR Payment
        // doesn't have an order then this is probably because
        // Validations on Order are failing and Payment is created
        // without Order to refund that while further processing.
        if ($entity->payment->hasOrder() === false)
        {
            return true;
        }

        return false;
    }

    protected function refundOrCapturePayment(Base\PublicEntity $entity)
    {
        // For business banking flow there exists no payment, hence no refund/capture.
        if ($this->virtualAccount->isBalanceTypeBanking() === true)
        {
            return;
        }

        $paymentProcessor = $this->getPaymentProcessor();

        if ($entity->isExpected() === true)
        {
            if ($this->shouldRefundOrderPayment($entity) === true)
            {
                $paymentProcessor->refundAuthorizedPayment($paymentProcessor->getPayment());
            }
            else
            {
                $paymentProcessor->autoCapturePayment($paymentProcessor->getPayment());
            }
        }
    }

    protected function createPaymentWithoutOrder(array $input, array $gatewayData = [])
    {
        if (isset($input[Payment\Entity::ORDER_ID]) === true)
        {
            $paymentInput = array_except($input, [Payment\Entity::ORDER_ID]);
        }

        $this->getPaymentProcessor()->process($paymentInput, $gatewayData);
    }

    protected function createPaymentToSharedMerchant(array $input, array $gatewayData = [])
    {
        $this->useSharedVirtualAccount = true;

        $this->getPaymentProcessor()->process($input, $gatewayData);
    }

    protected function createPayment(array $input, array $gatewayData = [])
    {
        try
        {
            $this->getPaymentProcessor()->process($input, $gatewayData);
        }
        catch (\Exception $e)
        {
            /*
             * Exception might have been because of Validation Failure on Order.
             * In this case we will make the payment without Order and refund
             * it in later flow.
             */
            if (isset($input[Payment\Entity::ORDER_ID]) === true)
            {
                $this->trace->traceException($e, Trace::INFO,
                    TraceCode::VIRTUAL_ACCOUNT_FAILED_FOR_ORDER, ['input' => $input]);

                $this->createPaymentWithoutOrder($input, $gatewayData);

                return;
            }

            $code = $e->getError()->getInternalErrorCode();

            /*
             * It's also possible that the payment failed because of the payment
             * method not being enabled anymore. This happens when a method is
             * revoked from a merchant after he's created the VA but before the
             * payment is made. In this case, we retry the payment towards the
             * test account, and let it get refunded.
             */
            if ($this->isMethodNotEnabledError($code) === true)
            {
                $this->trace->traceException($e, Trace::INFO,
                    TraceCode::VIRTUAL_ACCOUNT_METHOD_DISABLED_PAYMENT_REROUTED, [
                        'input' => $input
                    ]);

                $this->createPaymentToSharedMerchant($input, $gatewayData);

                return;
            }

            throw $e;
        }
    }

    /**
     * A receiver is expected if there exists an active VA
     * to receive it. If such a VA does not exist, or exists but
     * has been closed/paid, the payment is to be refunded.
     *
     * @param Base\PublicEntity $entity This is the receiver entity:
     *                                  bank_transfer, qr_code
     *
     * @return bool
     */
    abstract protected function checkPaymentExpectedAndSetVirtualAccount(Base\PublicEntity $entity);

    protected function isMethodNotEnabledError(string $code)
    {
        $methods = strtoupper(implode('|', Receiver::METHODS));

        $regex = sprintf(self::METHOD_NOT_ENABLED_CODE_REGEX, $methods);

        if (preg_match($regex, $code) != 1)
        {
            return false;
        }

        return true;
    }

    protected function getDefaultPaymentArray(): array
    {
        $paymentArray = $this->getReceiverPaymentArray();

        if ($this->virtualAccount->hasCustomer() === true)
        {
            $customer = $this->virtualAccount->customer;

            $paymentArray[Payment\Entity::CUSTOMER_ID] = $customer->getPublicId();
            $paymentArray[Payment\Entity::CONTACT]     = $customer->getContact();
            $paymentArray[Payment\Entity::EMAIL]       = $customer->getEmail();
        }

        return $paymentArray;
    }

    protected function getReceiverPaymentArray(): array
    {
        $receiver = $this->getReceiver();

        $paymentArray = [
            Payment\Entity::RECEIVER => [
                'id'   => $receiver->getPublicId(),
                'type' => $receiver->getEntity(),
            ],
        ];

        return $paymentArray;
    }

    /**
     * Set the VA for future processing.
     *
     * @param Base\PublicEntity $entity
     */
    protected function setVirtualAccount(Base\PublicEntity $entity)
    {
        $this->virtualAccount = $this->getVirtualAccountFromEntity($entity);
    }

    /**
     * Post-processing, VA amount fields are to be updated.
     * Status change is done inside incrementAmountPaid.
     *
     * @param Base\PublicEntity $entity
     */
    protected function updateVirtualAccount(Base\PublicEntity $entity)
    {
        $this->virtualAccount->incrementAmountPaid($entity->getAmount());

        $this->virtualAccount->incrementAmountReceived($entity->getAmount());

        $this->repo->saveOrFail($this->virtualAccount);
    }

    protected function setMerchant()
    {
        $this->merchant = $this->virtualAccount->merchant;
    }
}
