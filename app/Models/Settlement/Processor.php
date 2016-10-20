<?php

namespace RZP\Models\Settlement;

use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

use RZP\Base\RuntimeManager;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Transaction;
use RZP\Models\Settlement\Kotak;
use RZP\Models\Settlement\Channel;
use RZP\Models\Settlement\Daily\Entity as DailySettlement;

class Processor extends Base\Core
{
    protected $setlTime;

    protected $input;

    public function process(array $input, $channel)
    {
        $this->increaseAllowedSystemLimits();

        $this->preSettlementProcessing($input, $channel);

        list($settlements, $txnCount) = $this->createSettlements($channel);

        $dailySettlement = $this->createDailySetlEntity($settlements, $txnCount, $channel);

        $data = $this->generateSettlementFile($settlements);

        return $data;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(300);
    }

    protected function preSettlementProcessing(array $input, & $channel)
    {
        $this->setlTime = Carbon::today('Asia/Kolkata')->timestamp;

        $this->input = $input;

        //set channel
        if ($channel === null)
        {
            $channel = Channel::KOTAK
        }
    }

    protected function traceSetlInitiating($channel)
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y H:i:s');

        $this->trace->info(
            TraceCode::SETTLEMENT_INITIATING,
            [
                'channel'   => $this->channel,
                'timestamp' => $this->setlTime,
                'time'      => $time,
            ]);
    }

    protected function createSettlements($channel)
    {
        $txns = $this->repo->transaction->fetchUnsettledTransactions($this->setlTime);

        list($settlements, $txnCount) = $this->createSettlementsFromTxns($txns, $channel);

        $this->repo->transaction(function(){
            foreach ($settlements as $settlement)
            {
                $this->repo->saveOrFail($settlement);
            }
        });

        return [$settlements, $txnCount];
    }

    protected function createSettlementsFromTxns($txns, $channel)
    {
        $settlements = new Base\PublicCollection;
        $settledTxnCount = 0

        $i = 0;
        $count = $txns->count();

        while ($i < $count)
        {
            // Settlement amount
            $setlAmount = $setlGatewayFee = $setlApiFee = 0;
            $setlFee = $serviceTax = 0;

            $setlTxns = new Base\PublicCollection;

            // Get merchant
            $merchantId = $txns[$i]->getMerchantId();
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            while (($i < $count) and
                   ($txns[$i]->getMerchantId() === $merchantId))
            {
                $txn = $txns[$i];

                if ($this->shouldSettle($txn, $channel, $merchant) === false)
                {
                    $i++;
                    continue;
                }

                if (($txn->getBalance() === 0) and
                    ($txn->isTypeRefund()))
                {
                    $payment = $txn->source->payment;

                    if ($payment->hasBeenCaptured() === false)
                    {
                        $this->trace->info(
                            TraceCode::TRANSACTION_REFUND_TRACE,
                            [
                                'id' => $txn->getId()
                            ]);

                        $txn[Transaction\Entity::SETTLED_AT] = null;
                        $txn->saveOrFail();
                        $i++;
                        continue;
                    }
                }

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

            $setl = (new Settlement\Merchant($merchant, $channel, $this->repo))->settle(
                                        $setlTxns,
                                        $setlAmount,
                                        $setlFee,
                                        $setlApiFee,
                                        $setlGatewayFee,
                                        $serviceTax);

            $settlements->push($setl);
            $settledTxnCount += $setlTxns->count();
        }

        return [$settlements, $settledTxnCount];
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
        );

        $dailySettlement->fill($input);

        $this->repo->saveOrFail($dailySetl);

        return $dailySettlement;
    }

    protected function generateSettlementFile($settlements, $channel)
    {
        $data = null;

        if ($channel === Channel::KOTAK)
        {
            $data = (new Kotak\NodalAccount)->generateSettlementFile($settlements);
        }

        return $data
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
}
