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
use Dashboard\Dashboard as DashboardNotification;

class Settler
{
    protected $setlRepo;

    protected $txnRepo;

    protected $merchantRepo;

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

        DashboardNotification::send('settlement', $settlements);

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
        $merchant = $this->merchantRepo->findOrFail($merchantId);
        $amount = 0;

        foreach ($txns->all() as $txn)
        {
            if ($txn->getMerchantId() !== $merchantId)
            {
                $setl = $this->createMerchantSettlement($merchant, $amount);
                $settlements->push($setl);

                $merchantId = $txn->getMerchantId();
                $merchant = $this->merchantRepo->findOrFail($merchantId);
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

        $setl = $this->createSettlementEntity($merchant, $amount, $setlTransaction);

        $this->updateBalances($merchant, $setlTransaction);

        $this->txnRepo->save($setlTransaction);
        $this->setlRepo->save($setl);

        return $setl;
    }

    protected function updateBalances($merchant, $setlTransaction)
    {
        $nodalBalance = $this->merchantRepo->getEscrowBalanceLockForUpdate();
        $merchantBalance = $this->merchantRepo->getBalanceLockForUpdate(
                                                    $merchant->getKey());

        $merchantBalance->subAmount($setlTransaction['debit']);
        $this->merchantRepo->save($merchantBalance);

        $nodalBalance->subAmount($setlTransaction['debit']);
        $this->merchantRepo->save($nodalBalance);

        $attributes = array(
            Transaction\Entity::BALANCE => $merchantBalance->getBalance(),
            Transaction\Entity::ESCROW_BALANCE => $nodalBalance->getBalance());

        $setlTransaction->fill($attributes);
    }

    protected function createSettlementEntity($merchant, $amount, $setlTransaction)
    {
        $attributes = array(
            Settlement\Entity::AMOUNT           => $amount,
            Settlement\Entity::MERCHANT_ID      => $merchant->getKey(),
            Settlement\Entity::TRANSACTION_ID   => $setlTransaction->getKey(),
            Settlement\Entity::STATUS           => 'abc');

        $setl = (new Settlement\Entity)->fill($attributes);
        $setl->generateId();

        $setlTransaction->entity()->associate($setl);

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
            Transaction\Entity::SETTLED_AT => time(),
            Transaction\Entity::FEE => 0,
            Transaction\Entity::AMOUNT => $amount,
            Transaction\Entity::TYPE => Transaction\Type::SETTLEMENT,
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
        $this->merchantRepo = new Merchant\Repository;
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