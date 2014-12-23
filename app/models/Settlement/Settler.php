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
use Dashboard\Dashboard;

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
            list($settlements, $txns) = $this->process($input);

            $file = $this->createSettlementFile($settlements, $txns);

            $this->transferFileToNodalBank($file);

            $this->setlRepo->commit();
        }
        catch (\Exception $e)
        {
            $this->setlRepo->rollback();

            (new SlackNotification)->queueOperationFailure('settlements', $e);

            throw $e;
        }

        (new SlackNotification)->queueOperationSuccess('settlements', $settlements->count());

        Dashboard::send('settlement', $settlements);

        return $settlements;
    }

    protected function process($input)
    {
        $txns = $this->fetchTransactionsToSettle($input);

        $settlements = $this->createSettlements($txns);

        $this->txnRepo->settled($txns, self::$settlementTimestamp);

        return array($settlements, $txns);
    }

    protected function createSettlements($txns)
    {
        $settlements = new Base\PublicCollection;

        $i = 0;
        $count = $txns->count();

        while ($i < $count)
        {
            // Settlement amount
            $setlAmount = 0;

            // Get merchant
            $merchantId = $txns[$i]->getMerchantId();
            $merchant = $this->merchantRepo->findOrFail($merchantId);

            while (($i < $count) and
                   ($txns[$i]->getMerchantId() === $merchantId))
            {
                $setlAmount += $txns[$i]->getCredit() - $txns[$i]->getDebit();
                $i++;
            }

            $setl = (new Settlement\Merchant($merchant, $setlAmount))->settle();
            $settlements->push($setl);
        }

        return $settlements;
    }

    protected function createSettlementFile($settlements, $txns)
    {
        $filename = (new Kotak\Settlement)->generateSettlementFile($settlements, $txns);

        return $filename;
    }

    protected function transferFileToNodalBank($file)
    {
        ;
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