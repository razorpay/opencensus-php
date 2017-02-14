<?php

namespace RZP\Models\Customer\Transaction;

use Carbon\Carbon;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Payment;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    /**
     * Creates a customer_transaction record and am amount debit on the wallet balance
     * Called at payment authorize, for a openwallet payment.
     *
     * @param  Payment\Entity   $payment
     * @return Customer\Transaction\Entity
     */
    public function createForCustomerDebit(array $input, Merchant\Entity $merchant) : Entity
    {
        $amount = $input['payment']['amount'];

        $customerId = $input['payment']['customer_id'];

        $customerTxn = $this->createEntityForType(Entity::DEBIT, $merchant, $amount, $customerId);

        $customerTxn->setEntityType(Constants\Entity::PAYMENT);

        $customerTxn->setEntityId($input['payment']['id']);

        $balance = (new Customer\Balance\Service)->debit($customerId, $amount);

        $customerTxn->setBalance($balance->getBalance());

        $this->repo->saveOrFail($customerTxn);

        return $customerTxn;
    }

    /**
     * Create customer_transaction on payment transfer.
     *
     * @param  Payment\Entity   $payment
     * @param  int              $amount
     * @param  Customer\Entity  $customer
     * @return Entity
     */
    public function createForCustomerCredit($transfer, int $amount, string $customerId, Merchant\Entity $merchant) : Entity
    {
        $customerTxn = $this->createEntityForType(Entity::CREDIT, $this->merchant, $amount, $customerId);

        $customerTxn->setEntityType(Constants\Entity::TRANSFER);

        $customerTxn->setEntityId($transfer->getId());

        $customerTxn->entity()->associate($transfer);

        $balance = $this->repo
                        ->customer_balance
                        ->findByIdAndMerchant(
                            $customerId, $merchant);

        $customerTxn->setBalance($balance->getBalance());

        return $customerTxn;
    }

    /**
     * Create entry for a refund transaction, and credits customer wallet
     *
     * @param  string $customerId
     * @param  int    $amount
     * @return Entity
     */
    public function createForCustomerRefund(array $input, Merchant\Entity $merchant) : Entity
    {
        $amount = $input['amount'];

        $customerId = $input['payment']['customer_id'];

        $refundId = $input['refund']['id'];

        $customerTxn = $this->createEntityForType(Entity::CREDIT, $merchant, $amount, $customerId);

        $customerTxn->setType(Type::REFUND);

        $customerTxn->setEntityType(Constants\Entity::REFUND);

        $customerTxn->setEntityId($refundId);

        $balance = (new Customer\Balance\Core)->refund($customerId, $amount);

        $customerTxn->setBalance($balance->getBalance());

        $this->repo->saveOrFail($customerTxn);

        return $customerTxn;
    }

    protected function createEntityForType(string $type, Merchant\Entity $merchant, int $amount, string $customerId) : Entity
    {
        $customerTxn = new Entity;

        $txnData = [
            Entity::TYPE                => Type::TRANSFER,
            Entity::STATUS              => 'complete', // @todo - change this to something useful
            Entity::AMOUNT              => $amount,
            Entity::CURRENCY            => 'INR',
            Entity::DESCRIPTION         => 'NA', // @todo - change this to something useful
            Entity::CUSTOMER_ID         => $customerId
        ];

        if ($type === Entity::DEBIT)
        {
            $customerTxn->setDebit($amount);

            $customerTxn->setCredit(0);
        }
        else if ($type === Entity::CREDIT)
        {
            $customerTxn->setCredit($amount);

            $customerTxn->setDebit(0);
        }
        else
        {
            assert (false);
        }

        $customerTxn->merchant()->associate($merchant);

        $customerTxn->fillAndGenerateId($txnData);

        return $customerTxn;
    }

    public function getStatement(Customer\Balance\Entity $customerBalance, Merchant\Entity $merchant, array $input = [])
    {
        $input[Entity::CUSTOMER_ID] = $customerBalance->getCustomerId();

        $entities = $this->repo
                         ->customer_transaction
                         ->fetch($input, $merchant->getId());

        return $entities;
    }
}
