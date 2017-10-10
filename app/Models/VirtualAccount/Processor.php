<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\Base;
use RZP\Constants\Mode as RzpMode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\VirtualAccount;
use RZP\Models\Currency\Currency;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

abstract class Processor extends Base\Core
{
    protected $virtualAccount;
    protected $provider;
    protected $merchant;
    protected $validator;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;

        // These flows are initiated by the provider bank hitting
        // our APIs. Provider banks are currently authenticated by
        // registering them as apps, and using AppAuth.
        $this->provider = $this->app['basicauth']->getInternalApp();
    }

    abstract public function process($entity);

     /**
     * A receiver is expected if there exists an active VA
     * to receive it. If such a VA does not exist, or exists but
     * has been closed/paid, the payment is to be refunded.
     *
     * @param Entity
     *
     * @return bool
     */
    protected function isPaymentExpected($entity): bool
    {
        $this->setVirtualAccount($entity);

        if ($this->virtualAccount === null)
        {
            $this->trace->info(
                TraceCode::VIRTUAL_ACCOUNT_UNEXPECTED_PAYMENT,
                [
                    'message'      => 'Unexpected Payment',
                    'entity'       => $entity->toArray(),
                ]
            );

            return false;
        }

        return true;
    }

     /**
     * For unexpected virtual account payment, we set the merchant to
     * the demo merchant. A new VA is created specifically
     * for this payment, to be closed immediately afterwards.
     *
     * @param Entity $bankTransfer
     */
    protected function preProcessUnexpectedPayment($entity)
    {
        $entity->setExpected(false);

        $this->setDefaultMerchant();

        $this->createAndSetVirtualAccount($entity->getAmount());
    }


    /**
     * A throwaway VA is to be created for the default merchant. Create it use the amount
     * being paid as the expected amount, so that it is closed after the payment is processed.
     *
     * @param int $amount
     */
    protected function createAndSetVirtualAccount(int $amount)
    {
        $data = $this->virtualAccountCreationArray($amount);

        $virtualAccount = (new VirtualAccount\Core)->create($data, $this->merchant);

        $this->virtualAccount = $virtualAccount;
    }

    /**
     * Set the VA for future processing.
     *
     * @param Entity
     */
    protected function setVirtualAccount($entity)
    {
        $this->virtualAccount = $this->getVirtualAccountFromEntity($entity);
    }

    /**
     * Throwaway VAs for unexpected bank transfers don't need much to be created.
     *
     * @param int $amount
     *
     * @return array
     */
    protected function virtualAccountCreationArray(int $amount): array
    {
        return [
            VirtualAccount\Entity::AMOUNT_EXPECTED => $amount,
        ];
    }

    /**
     * Post-processing, VA amount fields are to be updated.
     * Status change is done inside incrementAmountPaid.
     *
     * @param Entity
     */
    protected function updateVirtualAccount($entity)
    {
        $this->virtualAccount->incrementAmountPaid($entity->getAmount());

        $this->virtualAccount->incrementAmountReceived($entity->getAmount());

        $this->repo->saveOrFail($this->virtualAccount);
    }

    /**
     * For unexpected payments, we use the demo page merchant. This merchant only
     * exists on prod. For other envs, we use the test merchant, i.e. '10000000000000'.
     */
    protected function setDefaultMerchant()
    {
        $defaultMerchantId = Merchant\Account::DEMO_PAGE_ACCOUNT;

        if ($this->env !== 'production')
        {
            $defaultMerchantId = Merchant\Account::TEST_ACCOUNT;
        }

        $this->merchant = $this->repo->merchant->findByPublicId($defaultMerchantId);
    }


    abstract protected function getVirtualAccountFromEntity($entity);
}
