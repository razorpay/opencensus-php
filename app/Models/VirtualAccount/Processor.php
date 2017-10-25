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
     *@todo need to make this generic
     */
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

        $virtualAccount = (new VirtualAccount\Core)->createWithoutReceivers($data, $this->merchant);

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
     * Throwaway VAs for unexpected payments
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
     * Set default merchant for future processing.
     * Use default merchant for this env.
     */
    protected function setDefaultMerchant()
    {
        $defaultMerchantId = self::getDefaultMerchantId();

        $this->merchant = $this->repo->merchant->findByPublicId($defaultMerchantId);
    }

    /**
     * For unexpected payments, we use the demo page merchant. This merchant only
     * exists on prod. For other envs, we use the test merchant, i.e. '10000000000000'.
     */
    public static function getDefaultMerchantId()
    {
        $defaultMerchantId = Merchant\Account::DEMO_PAGE_ACCOUNT;

        $env = App::getFacadeRoot()->environment();

        if ($env !== 'production')
        {
            $defaultMerchantId = Merchant\Account::TEST_ACCOUNT;
        }

        return $defaultMerchantId;
    }


    abstract protected function getVirtualAccountFromEntity($entity);
}
