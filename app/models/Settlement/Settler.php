<?php

namespace Models\Settlement;

use EE\Error\ErrorCode;
use EE\Exception;
use Illuminate\Database\Eloquent\Collection;
use Models\Payment;

class Settler
{
    protected $setlRepo;

    protected $txnRepo;

    protected $merchRepo;

    protected $settlements;

    /**
     * Used for testing purposes. Default should
     * be null.
     * @var integer
     */
    public static $settlementTimestamp = null;

    public function __construct()
    {
        $this->initRepos();

        $this->initSettlementTimestamp();

        $this->queue = \Queue::getFacadeRoot();

        $this->settlements = new Collection;
    }

    public function settle()
    {
        $t = self::$settlementTimestamp;

        $txns = $txnRepo->fetchPaymentsExpectedToSettle($t);

        $settled = true;

        $this->setlRepo->beginPayment();

        try
        {
            $settlements = $this->process($txns);

            $this->setlRepo->commit();
        }
        catch (\Exception $e)
        {
            $this->setlRepo->rollback();

            (new SlackNotification)->queueOperationFailure('settlements', $e);

            $settled = false;

            throw $e;
        }

        (new SlackNotification)->queueOperationSuccess('settlements', $count);

        return $settlements->toArray();
    }

    protected function process($txns)
    {
        $merchantId = $txns->first()->getMerchantId();
        $merchant = $this->merchRepo->findOrFail($merchantId);

        $amount = 0;

        foreach ($txns->all() as $txn)
        {
            if ($txn->getMerchantId() !== $merchantId)
            {
                $merchantId = $txn->getMerchantId();
                $merchant = $this->merchRepo->findOrFail($merchantId);
                $amount = 0;
            }

            $amount += $txn->getCredit() - $txn->getDebit();
        }

        $txnRepo->settled($txns, $t);
    }

    protected function createMerchantSettlement()
    {
        $setlTransaction = $this->createSettlementTransaction($merchant, $amount);

        $input = array(
            Settlement\Entity::AMOUNT       => $amount,
            Settlement\Entity::MERCHANT_ID  => $merchant->getKey(),
            Settlement\Entity::TRANSACTION_ID    => $setlTransaction->getKey());

        $setl = (new Settlement\Entity)->build($input);

        $merchantBalance = $this->merchRepo->getBalanceLockForUpdate($merchant->getKey());
        $merchantBalance->subAmount($transaction['debit']);

        $setlTransaction->setAttribute(Transaction\Entity::ENTITY_ID, $setl->getKey());
        $setlTransaction->setAttribute(Transaction\Entity::BALANCE, $merchantBalance->getBalance());

        $txnRepo->save($setlTransaction);
        $setlRepo->save($setl);
        $merchantRepo->save($merchantBalance);
    }

    protected function createSettlementTransaction($merchant, $amount)
    {
        $txn = new Transaction\Entity;

        $values = array(
            Transaction\Entity::MERCHANT_ID => $merchant->getKey(),
            Transaction\Entity::DEBIT => $amount,
            Transaction\Entity::FEE => 0,
            Transaction\Entity::AMOUNT => $amount,
            Transaction\Entity::ENTITY_TYPE => 'settlement',
        );

        $txn->build($values);

        return $txn;
    }

    protected function initRepos()
    {
        $this->setlRepo = new Settlement\Repository;
        $this->txnRepo = new Transaction\Repository;
        $this->merchRepo = new Merchant\Repository;
    }

    protected function initSettlementTimestamp()
    {
        if (self::$settlementTimestamp === null)
        {
            // Get the timestamp today at 12 am
            $timestamp = Carbon::today('Asia/Kolkata')->timestamp;
            self::$settlementTimestamp = $timestamp;
        }
    }
}