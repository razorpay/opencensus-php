<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use RZP\Base\RuntimeManager;
use RZP\Constants\Mode;
use RZP\Dashboard\Dashboard;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Settlement;
use RZP\Models\Settlement\Daily\Entity as DailySettlement;
use RZP\Models\Settlement\Kotak;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class Processor extends Base\Core
{
    protected $setlTime;

    protected $input;

    protected $mutex;

    const MUTEX_RESOURCE        = 'SETTLEMENT_PROCESSING';

    const MUTEX_LOCK_TIMEOUT    = 900;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function process(array $input, $channel, $schedule = true)
    {
        $this->increaseAllowedSystemLimits();

        $this->preSettlementProcessing($input, $channel);

        list($shouldProcess, $message) = $this->shouldProcessSettlements();

        if ($shouldProcess === false)
        {
            return $message;
        }

        $data = $this->mutex->acquireAndRelease(self::MUTEX_RESOURCE, function () use ($input, $channel, $schedule)
        {
            return $this->processSettlements($input, $channel, $schedule);
        }, self::MUTEX_LOCK_TIMEOUT, ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);

        return $data;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(300);
    }

    protected function preSettlementProcessing(array $input, & $channel)
    {
        $this->setlTime = Carbon::now('Asia/Kolkata')->timestamp;

        $this->input = $input;

        //set channel
        if ($channel === null)
        {
            $channel = Channel::KOTAK;
        }
    }

    protected function shouldProcessSettlements()
    {
        $today = Carbon::today('Asia/Kolkata');

        if (($this->mode === Mode::LIVE) and
            (Holidays::isWorkingDay($today) === false))
        {
            return [false, Holidays::HOLIDAY_MESSAGE];
        }

        if ($this->checkInvalidSettlementTime() === true)
        {
            return [false, ['message' => 'settlements cannot be processed now']];
        }

        return [true, null];
    }

    protected function checkInvalidSettlementTime()
    {
        // NEFT can be processed between 8am and 6 pm only, while batch file can
        // be uploaded anytime

        $sevenAm = Carbon::today('Asia/Kolkata')->hour(7)->timestamp;

        // Cron runs at 5.01pm.
        $fivePm = Carbon::today('Asia/Kolkata')->hour(17)->minute(10)->timestamp;

        if (($this->mode === Mode::LIVE) and
            (($this->setlTime <= $sevenAm) or
             ($this->setlTime >= $fivePm)))
        {
            return true;
        }

        return false;
    }

    protected function processSettlements($input, $channel, $schedule)
    {
        try
        {
            list($settlements, $txnCount) = $this->createSettlements($channel, $schedule);

            $data = [
                'channel'               => $channel,
                'count'                 => $settlements->count(),
                'transaction_count'     => $txnCount,
            ];

            if ($settlements->count() > 0)
            {
                $this->dailySettlement = $this->createDailySetlEntity($settlements, $txnCount, $channel);

                list($urlText, $urlExcel) = $this->generateSettlementFile($settlements, $channel);

                $this->updateDailySettlementEntity($urlText, $urlExcel);

                $data['settlement_text_file']  = $urlText;
                $data['settlement_excel_file'] = $urlExcel;

                $this->successNotification($data, $settlements);
            }
            else
            {
                $data['message'] = 'No settlements found!';
            }
        }
        catch (\Exception $e)
        {
            $this->settlementFailure($channel, $e);
        }

        return $data;
    }

    protected function createSettlements($channel, $schedule)
    {
        $txns = new Base\PublicCollection;

        if ($schedule === false)
        {
            $txns = $this->repo->transaction->fetchUnsettledTransactions($this->setlTime);
        }
        else
        {
            list($txns, $schedules) = $this->repo->transaction->fetchUnsettledTxnsAndSchedules($this->setlTime);

            $schedules->callOnEveryItem('updateNextRun');

            $this->trace->info(TraceCode::SCHEDULE_NEXT_RUN_UPDATED, $schedules->getIds());
        }

        $this->trace->info(TraceCode::SCHEDULE_UNSETTLED_TXNS, [$txns]);

        $txns = $this->filterTransactionsForSettlement($txns, $channel);

        return $this->repo->transaction(function() use ($txns, $channel)
        {
            $settlements = $this->createSettlementsFromTxns($txns, $channel);

            $this->repo->transaction->settled($txns, $this->setlTime);

            return [$settlements, $txns->count()];
        });
    }

    protected function filterTransactionsForSettlement($txns, $channel)
    {
        $filteredTxns = new Base\PublicCollection;

        foreach ($txns as $txn)
        {
            // skip if txn not to be settled
            if ($this->shouldSettle($txn, $channel, $txn->merchant) === false)
            {
                continue;
            }

            // skip if txn is refund of authorized txn and update the txn
            if (($txn->getBalance() === 0) and
                ($txn->isTypeRefund()))
            {
                $payment = $txn->source->payment;

                if ($payment->hasBeenCaptured() === false)
                {
                    $txn[Transaction\Entity::SETTLED_AT] = null;

                    $this->repo->saveOrFail($txn);

                    continue;
                }
            }

            $filteredTxns->push($txn);
        }

        return $filteredTxns;
    }

    protected function createSettlementsFromTxns($txns, $channel)
    {
        $settlements = new Base\PublicCollection;
        $settledTxnCount = 0;

        $i = 0;
        $count = $txns->count();

        while ($i < $count)
        {
            // Settlement amount
            $setlAmount = $setlGatewayFee = $setlApiFee = 0;
            $setlFee = $serviceTax = 0;

            $setlTxns = new Base\PublicCollection;

            // Get merchant
            assert($txns[$i]->merchant !== null);
            $merchant = $txns[$i]->merchant;
            $merchantId = $txns[$i]->getMerchantId();

            while (($i < $count) and
                   ($txns[$i]->getMerchantId() === $merchantId))
            {
                $txn = $txns[$i];

                $setlAmount += $txn->getCredit() - $txn->getDebit();
                $setlGatewayFee += $txn->getGatewayFee();
                $setlApiFee += $txn->getApiFee();
                $setlFee += $txn->getFee();
                $serviceTax += $txn->getServiceTax();

                $setlTxns->push($txn);
                $i++;
            }

            //settle only if settlement amount is more than INR 1
            if ($setlAmount <= 100)
            {
                $setlAmount = 0;
                continue;
            }

            $merchantSettler = new Settlement\Merchant($merchant, $channel, $this->repo);

            $setl = $merchantSettler->settle(
                                        $setlTxns,
                                        $setlAmount,
                                        $setlFee,
                                        $setlApiFee,
                                        $serviceTax);

            $settlements->push($setl);
        }

        return $settlements;
    }

    protected function createDailySetlEntity($settlements, $txnsCount, $channel)
    {
        $dailySettlement = DailySettlement::newForToday();

        $totalAmount = $totalFees = $totalServiceTax = 0;

        foreach ($settlements as $settlement)
        {
            $totalAmount += $settlement->getAmount();

            $totalFees += $settlement->getFees();

            $totalServiceTax += $settlement->getServiceTax();
        }

        $input = array(
            DailySettlement::FEES              => $totalFees,
            DailySettlement::AMOUNT            => $totalAmount,
            DailySettlement::CHANNEL           => $channel,
            DailySettlement::SERVICE_TAX       => $totalServiceTax,
            DailySettlement::SETTLEMENT_COUNT  => $settlements->count(),
            DailySettlement::TRANSACTION_COUNT => $txnsCount,
            DailySettlement::INITIATED_AT      => time(),
            DailySettlement::API_FEE           => 0,
            DailySettlement::GATEWAY_FEE       => 0,
            DailySettlement::URLS              => null,
        );

        $dailySettlement->fill($input);

        $this->repo->saveOrFail($dailySettlement);

        return $dailySettlement;
    }

    protected function updateDailySettlementEntity($urlText, $urlExcel)
    {
        $dailySettlement = $this->dailySettlement;

        $urls = [
            'kotak_settlement_txt'   => $urlText,
            'kotak_settlement_excel' => $urlExcel
        ];

        $dailySettlement->setUrls($urls);

        $this->repo->saveOrFail($dailySettlement);
    }

    protected function generateSettlementFile($settlements, $channel)
    {
        $data = null;

        if ($channel === Channel::KOTAK)
        {
            $data = (new Kotak\NodalAccount)->generateSettlementFile($settlements);
        }

        return $data;
    }

    protected function shouldSettle(Transaction\Entity $txn, $channel, $merchant)
    {
        $today = Carbon::today('Asia/Kolkata');

        $lastWorkingDay = Holidays::getPreviousWorkingDay($today);

        $shouldSettle = (($txn->getChannel() === $channel) and
                         ($merchant->holdFunds() === false));

        assert ($merchant->bankAccount !== null);

        if (($this->mode !== Mode::TEST) and
            ($merchant->bankAccount->getCreatedAt() > $lastWorkingDay->timestamp))
        {
            $shouldSettle = false;
        }

        return $shouldSettle;
    }

    protected function settlementFailure($channel, $e)
    {
        $e = new SettlementFailureException($channel, null, $e);

        $this->failureNotification($e);

        $this->trace->critical(TraceCode::SETTLEMENT_INITIATE_FAILED);

        throw $e;
    }

    protected function successNotification($data, $settlements)
    {
        $this->trace->info(TraceCode::SETTLEMENT_INITIATED, $data);

        (new SlackNotification)->success('setl_initiate', $data);

        Dashboard::send('settlement', $settlements);
    }

    protected function failureNotification($exception)
    {
        (new SlackNotification)->failure('setl_initiate', $exception);
    }

}
