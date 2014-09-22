<?php

namespace Models\Settlement;

use EE\Error\ErrorCode;
use EE\Exception;
use Illuminate\Database\Eloquent\Collection;
use Models\Transaction;

class Settler
{
    protected $setlRepo;

    protected $lgrRepo;

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

        $lgrs = $lgrRepo->fetchTransactionsExpectedToSettle($t);

        $settled = true;

        $this->setlRepo->beginTransaction();

        try
        {
            $settlements = $this->process($lgrs);

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

    protected function process($lgrs)
    {
        $merchantId = $lgrs->first()->getMerchantId();
        $merchant = $this->merchRepo->findOrFail($merchantId);

        $amount = 0;

        foreach ($lgrs->all() as $lgr)
        {
            if ($lgr->getMerchantId() !== $merchantId)
            {
                $merchantId = $lgr->getMerchantId();
                $merchant = $this->merchRepo->findOrFail($merchantId);
                $amount = 0;
            }

            $amount += $lgr->getCredit() - $lgr->getDebit();
        }

        $lgrRepo->settled($lgrs, $t);
    }

    protected function createMerchantSettlement()
    {
        $setlLedger = $this->createSettlementLedger($merchant, $amount);

        $input = array(
            Settlement\Entity::AMOUNT       => $amount,
            Settlement\Entity::MERCHANT_ID  => $merchant->getKey(),
            Settlement\Entity::LEDGER_ID    => $setlLedger->getKey());

        $setl = (new Settlement\Entity)->build($input);

        $merchantBalance = $this->merchRepo->getBalanceLockForUpdate($merchant->getKey());
        $merchantBalance->subAmount($ledger['debit']);

        $setlLedger->setAttribute(Ledger\Entity::ENTITY_ID, $setl->getKey());
        $setlLedger->setAttribute(Ledger\Entity::BALANCE, $merchantBalance->getBalance());

        $lgrRepo->save($setlLedger);
        $setlRepo->save($setl);
        $merchantRepo->save($merchantBalance);
    }

    protected function createSettlementLedger($merchant, $amount)
    {
        $lgr = new Ledger\Entity;

        $values = array(
            Ledger\Entity::MERCHANT_ID => $merchant->getKey(),
            Ledger\Entity::DEBIT => $amount,
            Ledger\Entity::FEE => 0,
            Ledger\Entity::AMOUNT => $amount,
            Ledger\Entity::ENTITY_TYPE => 'settlement',
        );

        $lgr->build($values);

        return $lgr;
    }

    protected function initRepos()
    {
        $this->setlRepo = new Settlement\Repository;
        $this->lgrRepo = new Ledger\Repository;
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