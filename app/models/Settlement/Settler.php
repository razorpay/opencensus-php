<?php

namespace Models\Settlement;

use Carbon\Carbon;
use EE\Error\ErrorCode;
use EE\Exception;
use Illuminate\Database\Eloquent\Collection;
use Models\Base;
use Models\Merchant;
use Models\Settlement;
use Models\Transaction;

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

        $this->queue = \Queue::getFacadeRoot();

        $this->settlements = new Collection;
    }

    public function settle($input = array())
    {
        $this->setlRepo->beginTransaction();

        try
        {
            $settlements = $this->process($input);

            $this->setlRepo->commit();
        }
        catch (\Exception $e)
        {
            $this->setlRepo->rollback();

            (new SlackNotification)->queueOperationFailure('settlements', $e);

            throw $e;
        }

        (new SlackNotification)->queueOperationSuccess('settlements', $settlements->count());

        return $settlements;
    }

    protected function process($input)
    {
        $txns = $this->fetchTransactionsToSettle($input);

        $settlements = new Base\PublicCollection;

        if ($txns->count() === 0)
        {
            return $settlements;
        }

        $merchantId = $txns->first()->getMerchantId();
        $merchant = $this->merchRepo->findOrFail($merchantId);
        $amount = 0;

        foreach ($txns->all() as $txn)
        {
            if ($txn->getMerchantId() !== $merchantId)
            {
                $setl = $this->createMerchantSettlement($merchant, $amount);
                $settlements->push($setl);

                $merchantId = $txn->getMerchantId();
                $merchant = $this->merchRepo->findOrFail($merchantId);
                $amount = 0;
            }

            $amount += $txn->getCredit() - $txn->getDebit();
        }

        $setl = $this->createMerchantSettlement($merchant, $amount);
        $settlements->push($setl);

        $this->txnRepo->settled($txns, self::$settlementTimestamp);

        return $settlements;
    }

    protected function createMerchantSettlement($merchant, $amount)
    {
        $setlTransaction = $this->createSettlementTransaction($merchant, $amount);

        $attributes = array(
            Settlement\Entity::AMOUNT           => $amount,
            Settlement\Entity::MERCHANT_ID      => $merchant->getKey(),
            Settlement\Entity::TRANSACTION_ID   => $setlTransaction->getKey(),
            Settlement\Entity::STATUS           => 'abc');

        $setl = (new Settlement\Entity)->fill($attributes);
        $setl->generateId();

        $merchantBalance = $this->merchRepo->getBalanceLockForUpdate($merchant->getKey());
        $merchantBalance->subAmount($setlTransaction['debit']);

        $setlTransaction->setAttribute(Transaction\Entity::ENTITY_ID, $setl->getKey());
        $setlTransaction->setAttribute(Transaction\Entity::BALANCE, $merchantBalance->getBalance());

        $this->txnRepo->save($setlTransaction);
        $this->setlRepo->save($setl);
        $this->merchRepo->save($merchantBalance);

        return $setl;
    }

    protected function createSettlementTransaction($merchant, $amount)
    {
        $txn = new Transaction\Entity;

        $values = array(
            Transaction\Entity::MERCHANT_ID => $merchant->getKey(),
            Transaction\Entity::DEBIT => $amount,
            Transaction\Entity::CREDIT => 0,
            Transaction\Entity::CURRENCY => 'INR',
            Transaction\Entity::GATEWAY_FEE => 0,
            Transaction\Entity::API_FEE => 0,
            Transaction\Entity::ESCROW_BALANCE => 0,
            Transaction\Entity::SETTLED_AT => time(),
            Transaction\Entity::FEE => 0,
            Transaction\Entity::AMOUNT => $amount,
            Transaction\Entity::ENTITY_TYPE => 'settlement',
        );

        $txn->fill($values);
        $txn->generateId();

        return $txn;
    }

    protected function fetchTransactionsToSettle($input)
    {
        $ts = $this->initSettlementTimestamp($input);

        if ((isset($input['all'])) and
            ($input['all'] === '1'))
        {
            $txns = $this->txnRepo->fetchUnsettledTransactions();
        }
        else
        {
            $txns = $this->txnRepo->fetchTxnsExpectedToSettle($ts);
        }

        return $txns;
    }

    protected function initRepos()
    {
        $this->setlRepo = new Settlement\Repository;
        $this->txnRepo = new Transaction\Repository;
        $this->merchRepo = new Merchant\Repository;
    }

    protected function initSettlementTimestamp($input)
    {
        if (self::$settlementTimestamp === null)
        {
            // Get the timestamp today at 12 am
            $timestamp = Carbon::today('Asia/Kolkata')->timestamp;
            self::$settlementTimestamp = $timestamp;
        }

        return self::$settlementTimestamp;
    }
}