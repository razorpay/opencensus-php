<?php

namespace RZP\Models\Customer\Transaction;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Transfer;

class Core extends Base\Core
{
    /**
     * Creates a customer_transaction record and am amount debit on the wallet balance
     * Called at payment authorize, for a openwallet payment.
     * Called at wallet balance withdrawal.
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param string          $source
     *
     * @return Entity
     */
    public function createForCustomerDebit(array $input, Merchant\Entity $merchant, string $source) : Entity
    {
        $amount = $input['amount'];

        $customerId = $input['customer_id'];

        $txnType = Type::TRANSFER;

        if ($source === Constants\Entity::PAYOUT)
        {
            $txnType = Type::WITHDRAWAL;
        }

        $customerTxn = $this->createEntityForType(Entity::DEBIT, $merchant, $amount, $customerId, $txnType);

        $customerTxn->setEntityType($source);

        $customerTxn->setEntityId($input['id']);

        $customerTxn->setDescription($input['description'] ?? 'No description');

        return $this->repo->transaction(function () use ($amount, $customerId, $customerTxn, $source)
        {
            $balance = (new Customer\Balance\Core)->debit($customerId, $amount, $source);

            $customerTxn->setBalance($balance->getBalance());

            $this->repo->saveOrFail($customerTxn);

            return $customerTxn;
        });
    }

    /**
     * Create customer_transaction on payment transfer.
     *
     * @param Base\PublicEntity $source
     * @param  int              $amount
     * @param string            $customerId
     * @param Merchant\Entity   $merchant
     *
     * @return Entity
     */
    public function createForCustomerCredit(
        Base\PublicEntity $source,
        int $amount,
        string $customerId,
        Merchant\Entity $merchant) : Entity
    {
        $customerTxn = $this->createEntityForType(Entity::CREDIT, $merchant, $amount, $customerId);

        $customerTxn->entity()->associate($source);

        $customerTxn->setType($source->getEntityName());

        return $this->repo->transaction(function() use ($customerId, $customerTxn)
        {
            $customerBalance = (new Customer\Balance\Core)->credit($customerId, $customerTxn->getAmount());

            $customerTxn->setBalance($customerBalance->getBalance());

            $this->repo->saveOrFail($customerTxn);

            return $customerTxn;
        });
    }

    /**
     * Create entry for a refund transaction, and credits customer wallet
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
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

        return $this->repo->transaction(function () use ($amount, $customerId, $customerTxn)
        {
            $balance = (new Customer\Balance\Core)->refund($customerId, $amount);

            $customerTxn->setBalance($balance->getBalance());

            $this->repo->saveOrFail($customerTxn);

            return $customerTxn;
        });
    }

    /**
     * Fetches a collection of customer_transactions
     *
     * @param  Customer\Balance\Entity $customerBalance
     * @param  Merchant\Entity         $merchant
     * @param  array                   $input
     * @return Base\PublicCollection
     */
    public function getStatement(Customer\Balance\Entity $customerBalance, Merchant\Entity $merchant, array $input = [])
    {
        $input[Entity::CUSTOMER_ID] = $customerBalance->getCustomerId();

        $entities = $this->repo
                         ->customer_transaction
                         ->fetch($input, $merchant->getId());

        return $entities;
    }

    /**
     * @param string          $type
     * @param Merchant\Entity $merchant
     * @param int             $amount
     * @param string          $customerId
     * @param string          $txnType
     *
     * @return Entity
     */
    protected function createEntityForType(
        string $type,
        Merchant\Entity $merchant,
        int $amount,
        string $customerId,
        string $txnType = Type::TRANSFER) : Entity
    {
        $customerTxn = new Entity;

        $txnData = [
            Entity::TYPE                => $txnType,
            Entity::STATUS              => 'complete', // @todo - change this to something useful
            Entity::AMOUNT              => $amount,
            Entity::CURRENCY            => 'INR',
            Entity::DESCRIPTION         => 'NA', // @todo - change this to something useful
        ];

        $this->setAmounts($type, $amount, $customerTxn);

        $customerTxn->merchant()->associate($merchant);

        $customer = $this->repo->customer->findOrFailPublic($customerId);

        $customerTxn->customer()->associate($customer);

        $customerTxn->fillAndGenerateId($txnData);

        return $customerTxn;
    }

    protected function setAmounts(string $type, int $amount, Entity $customerTxn)
    {
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
            throw new Exception\LogicException('Openwallet: Invalid Txn Type - ' . $type);
        }
    }
}
