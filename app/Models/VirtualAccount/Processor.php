<?php

namespace RZP\Models\VirtualAccount;

use App;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\VirtualAccount;

abstract class Processor extends Base\Core
{
    protected $virtualAccount;
    protected $provider;
    protected $merchant;
    protected $validator;
    protected $receiver;

    public function __construct(string $provider = null)
    {
        parent::__construct();

        $this->validator = new Validator;

        //
        // These flows are initiated by the provider bank hitting
        // our APIs. Provider banks are currently authenticated by
        // registering them as apps, and using AppAuth.
        //
        // For manual insertion of a bank transfer, it
        // is also possible to give provider as input
        //
        if ($provider === null)
        {
            $provider = $this->app['basicauth']->getInternalApp();
        }

        $this->provider = $provider;
    }

    /**
     * Entry point for  virtual account  process flow.
     * Check if the payment was an expected one.
     * - payment was expected?
     *   - Yes
     *     - unique merchant reference?
     *       - Yes
     *         - Process the payment towards the owner of the VA
     *       - No
     *         - Duplicate payment, save entity and ignore
     *   - No
     *     - Process payment toward demo merchant, auto-refund it later.
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

        $entity = $this->processReceiver($entity);

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

    abstract protected function processReceiver(Base\PublicEntity $entity);

    abstract protected function getVirtualAccountFromEntity(Base\PublicEntity $entity);

    abstract protected function getPaymentArray(Base\PublicEntity $entity);

    abstract protected function getReceiver();

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
    protected function checkPaymentExpectedAndSetVirtualAccount(Base\PublicEntity $entity): bool
    {
        $this->setVirtualAccount($entity);

        if ($this->virtualAccount === null)
        {
            $this->trace->info(
                TraceCode::VIRTUAL_ACCOUNT_UNEXPECTED_PAYMENT,
                [
                    'entity' => $entity->toArray(),
                ]);

            $this->virtualAccount = (new VirtualAccount\Core)->createOrFetchSharedVirtualAccount();

            return false;
        }

        return true;
    }

    protected function getFinalPaymentArray(array $paymentArray)
    {
        if ($this->virtualAccount->hasCustomer() === true)
        {
            $customer = $this->virtualAccount->customer;

            $paymentArray[Payment\Entity::CUSTOMER_ID] = $customer->getPublicId();
            $paymentArray[Payment\Entity::CONTACT]     = $customer->getContact();
            $paymentArray[Payment\Entity::EMAIL]       = $customer->getEmail();
        }

        $paymentArray = $this->addReceiverDataInPaymentArray($paymentArray);

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

    protected function addReceiverDataInPaymentArray(array $paymentArray)
    {
        $receiver = $this->getReceiver();

        $receiverData = [
            'id'   => $receiver->getPublicId(),
            'type' => $receiver->getEntity(),
        ];

        $paymentArray[Payment\Entity::RECEIVER] = $receiverData;

        return $paymentArray;
    }

    protected function setMerchant()
    {
        $this->merchant = $this->virtualAccount->merchant;
    }
}
