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
use Trace\TraceCode;

class Settler
{
    protected $setlRepo;

    protected $txnRepo;

    protected $merchantRepo;

    protected $settlements;

    protected $input;

    /**
     * Used for testing purposes. Default should
     * be null.
     * @var integer
     */
    public static $settlementTimestamp = null;

    public function __construct()
    {
        $this->initRepos();

        $this->trace = \Trace::getFacadeRoot();
    }

    public function settle($input = array(), $channel = null)
    {
        $this->input = $input;

        $force = false;
        if ((isset($input['force']) and
            ($input['force'] === '1')))
        {
            $force = true;
        }

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
            $dailySettlement = $this->dailySetlRepo->getSettlementForToday('kotak');

            if (($dailySettlement !== null) and
                ($force === false))
            {
                return [];
            }

            $this->dailySettlement = new Settlement\Daily\Entity;
            $this->dailySettlement->setTodayTimestamp();

            $settleForChannelVar = 'settleFor' . ucfirst($channel);

            $this->traceSetlInitiated($channel);

            $data[$channel] = $this->$settleForChannelVar($txns);
        }

        return $data;
    }

    protected function settleForKotak($txns)
    {
        $this->setlRepo->beginTransaction();

        try
        {
            list($settlements, $txns, $amounts) = $this->process($txns, Channel::KOTAK);

            list($urlText, $urlExcel) = $this->createSettlementFile($settlements, $txns);

            $urls = array();
            $urls['kotak_settlement_txt'] = $urlText;
            $urls['kotak_settlement_excel'] = $urlExcel;

            $this->dailySettlement->setUrls($urls);
            $this->dailySettlement->initiated_at = time();
            $this->dailySettlement->saveOrFail();

            $this->setlRepo->commit();
        }
        catch (\Exception $e)
        {
            $this->setlRepo->rollback();

            $this->settlementFailure('kotak', $e);
        }

        $this->successNotification($settlements, 'kotak');

        return ['setlFile' => $urlText];
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

        $this->trace->info(TraceCode::SETTLEMENT_ATOM_INITIATED_RECONCILED);

        $this->successNotification($settlements, 'atom');

        return $file;
    }

    protected function settlementFailure($channel, $e)
    {
        $e = new SettlementFailureException($channel, null, $e);

        $this->failureNotification($e);

        $this->trace->critical(TraceCode::SETTLEMENT_INITIATE_FAILED);

        throw $e;
    }

    protected function successNotification($settlements, $channel)
    {
        $data = array(
            'channel' => $channel,
            'setl_count' => $settlements->count());

        (new SlackNotification)->queueOperationSuccess('setl_initiate', $data);

        Dashboard::send('settlement', $settlements);
    }

    protected function failureNotification($exception)
    {
        (new SlackNotification)->queueOperationFailure('setl_initiate', $exception);
    }

    protected function process($txns, $channel)
    {
        list($settlements, $amounts) = $this->createSettlements($txns, $channel);

        $this->txnRepo->settled($txns, self::$settlementTimestamp);

        return array($settlements, $txns, $amounts);
    }

    protected function createSettlements($txns, $channel)
    {
        $this->dailySettlement->channel = $channel;

        $settlements = new Base\PublicCollection;

        $i = 0;
        $count = $txns->count();
        $totalSetlAmount = 0;
        $totalSetlGatewayFee = 0;
        $totalSetlApiFee = 0;

        while ($i < $count)
        {
            // Settlement amount
            $setlAmount = $setlGatewayFee = $setlApiFee = 0;
            $setlTxns = new Base\PublicCollection;

            // Get merchant
            $merchantId = $txns[$i]->getMerchantId();
            $merchant = $this->merchantRepo->findOrFail($merchantId);

            while (($i < $count) and
                   ($txns[$i]->getMerchantId() === $merchantId))
            {
                $txn = $txns[$i];

                if ($this->shouldSettle($txn, $channel) === false)
                {
                    $i++;
                    continue;
                }

                $setlAmount += $txn->getCredit() - $txn->getDebit();
                $setlGatewayFee += $txn->getGatewayFee();
                $setlApiFee += $txn->getApiFee();

                $setlTxns->push($txn);
                $i++;
            }

            if ($setlAmount < 0)
            {
                $setlAmount = 0;
                continue;
            }

            $setl = (new Settlement\Merchant($merchant, $channel))->settle(
                                        $setlTxns, $setlAmount, $setlApiFee, $setlGatewayFee);

            $settlements->push($setl);

            $totalSetlAmount += $setlAmount;
            $totalSetlApiFee += $setlApiFee;
            $totalSetlGatewayFee += $setlGatewayFee;
        }

        if (($totalSetlApiFee !== 0) and
            ($channel === Settlement\Channel::KOTAK))
        {
            $setl = $this->collectApiFees($totalSetlApiFee, $channel);
            $settlements->push($setl);

            $totalSetlAmount += $totalSetlApiFee;
        }

        $this->dailySettlement->amount = $totalSetlAmount;
        $this->dailySettlement->api_fee = $totalSetlApiFee;
        $this->dailySettlement->gateway_fee = $totalSetlGatewayFee;

        $amounts = array(
            'amount' => $totalSetlAmount,
            'api_fee' => $totalSetlApiFee,
            'gateway_fee' => $totalSetlGatewayFee,
        );

        return [$settlements, $amounts];
    }

    protected function shouldSettle(Transaction\Entity $txn, $channel)
    {
        return ($txn->getChannel() === $channel);
    }

    protected function createSettlementFile($settlements, $txns)
    {
        $urls = (new Kotak\NodalAccount)->generateSettlementFile($settlements, $txns);

        $this->trace->info(TraceCode::SETTLEMENT_FILE_GENERATED_KOTAK);

        return $urls;
    }

    protected function collectApiFees($apiFee, $channel)
    {
        if ($channel !== Settlement\Channel::KOTAK)
        {
            throw new Exception\LogicException('Not valid channel: ' . $channel);
        }

        $feeAccount = $this->merchantRepo->findOrFail(Merchant\Account::API_FEE_ACCOUNT);

        $setl = (new Settlement\Merchant($feeAccount, $channel))->collectApiFees($apiFee);

        return $setl;
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
                $payment = $txn->entity;

            }
            else if ($txn->getType() === Transaction\Type::REFUND)
            {
                $payment = $txn->entity->payment;
            }
        }

        return $txns;
    }

    protected function initRepos()
    {
        $this->setlRepo = new Settlement\Repository;
        $this->txnRepo = new Transaction\Repository;
        $this->merchantRepo = new Merchant\Repository;
        $this->dailySetlRepo = new Settlement\Daily\Repository;
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

    protected function traceSetlInitiated($channel)
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y H:i:s');

        $this->trace->info(
            TraceCode::SETTLEMENT_INITIATED,
            [
                'channel' => $channel,
                'timestmap' => self::$settlementTimestamp,
                'time' => $time,
            ]);
    }
}