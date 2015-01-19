<?php

namespace Models\Settlement;

use Carbon\Carbon;
use EE\Error\ErrorCode;
use EE\Exception;
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
    }

    public function settle($input = array(), $channel = null)
    {
        $this->input = $input;

        $txns = $this->fetchTransactionsToSettle($input);

        if ($channel === null)
        {
            $channels = Channel::getChannels();
        }
        else
        {
            $channels = [$channel];
        }

        foreach ($channels as $channel)
        {
            $settleForChannelVar = 'settleFor' . ucfirst($channel);

            $data[$channel] = $this->$settleForChannelVar($txns);
        }

        return $data;
    }

    protected function settleForKotak($txns)
    {
        $this->setlRepo->beginTransaction();

        try
        {
            list($settlements, $txns) = $this->process($txns, Channel::KOTAK);

            $file = $this->createSettlementFile($settlements, $txns);

            $this->transferFileToNodalBank($file);

            $this->setlRepo->commit();
        }
        catch (\Exception $e)
        {
            $this->setlRepo->rollback();

            $this->settlementFailure('kotak', $e);
        }

        $this->successNotification($settlements);

        return ['setlFile' => $file];
    }

    protected function settleForAtom($input = array())
    {
        $this->setlRepo->beginTransaction();

        try
        {
            list($settlements, $txns) = $this->process($txns, Channel::ATOM);

            $this->setlRepo->commit();
        }
        catch (\Exception $e)
        {
            $this->setlRepo->rollback();

            $this->settlementFailure('atom', $e);
        }

        $this->successNotification($settlements);

        return $file;
    }

    protected function settlementFailure($channel, $e)
    {
        $e = new SettlementFailureException($channel, null, $e);

        $this->failureNotification($e);

        throw $e;
    }

    protected function successNotification($settlements)
    {
        (new Mpr\SlackNotification)->queueOperationSuccess('settlements', $settlements->count());

        Dashboard::send('settlement', $settlements);
    }

    protected function failureNotification($exception)
    {
        (new Mpr\SlackNotification)->queueOperationFailure('settlements', $exception);
    }

    protected function process($txns, $channel)
    {
        $settlements = $this->createSettlements($txns, $channel);

        $this->txnRepo->settled($txns, self::$settlementTimestamp);

        return array($settlements, $txns);
    }

    protected function createSettlements($txns, $channel)
    {
        $gateways = Channel::getGateways($channel);

        $settlements = new Base\PublicCollection;

        $i = 0;
        $count = $txns->count();

        while ($i < $count)
        {
            // Settlement amount
            $setlAmount = 0;
            $setlTxns = new Base\PublicCollection;

            // Get merchant
            $merchantId = $txns[$i]->getMerchantId();
            $merchant = $this->merchantRepo->findOrFail($merchantId);

            while (($i < $count) and
                   ($txns[$i]->getMerchantId() === $merchantId))
            {
                $txn = $txns[$i];

                if ($this->shouldSettle($txn, $gateways) === false)
                {
                    $i++;
                    continue;
                }

                $setlAmount += $txn->getCredit() - $txn->getDebit();
                $setlTxns->push($txn);
                $i++;
            }

            $setl = (new Settlement\Merchant($merchant, $setlAmount))->settle($setlTxns);
            $settlements->push($setl);
        }

        return $settlements;
    }

    protected function shouldSettle($txn, $gateways)
    {
        return (in_array($txn->getGateway(), $gateways));
    }

    protected function createSettlementFile($settlements, $txns)
    {
        $filename = (new Kotak\NodalAccount)->generateSettlementFile($settlements, $txns);

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

        foreach ($txns as $txn)
        {
            if ($txn->isTypePayment())
            {
                $payment = $transaction->entity();
            }
            else if ($txn->getType() === Transaction\Type::REFUND)
            {
                $payment = $transaction->entity()->payment();
            }
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