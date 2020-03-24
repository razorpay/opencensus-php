<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\FundTransfer;
use RZP\Models\Payout\Entity;
use RZP\Services\FTS\Constants;
use RZP\Models\Settlement\Holidays;
use RZP\Error\PublicErrorDescription;

class MerchantPayout extends Base
{
    protected function setChannel(Entity $payout)
    {

        $channel = $payout->merchant->getChannel();

        //
        // For Early Settlements On demand, merchant can create payouts at any time of the day.
        // If they create it during banking hours their default channel may suffice for NEFT transactions.
        // However if a request comes during non banking hours or holidays, NEFT transaction will
        // get scheduled to next working day.
        // So, for on demand payouts we force the channel to YESBANK(ICICI w.e.f 6th March 2020) during out of banking hours
        // to use IMPS with restriction on amount to be less than 2L.
        // Once any channel(s) becomes available to serve
        // 24x7 NEFT. Those channel will be used to force set the payout channel.
        //
        if ($payout->getPayoutType() === Entity::ON_DEMAND)
        {
            if ($this->isOutsideBankingHours() === true)
            {
                if ($payout->getAmount() <= FundTransfer\Base\Initiator\NodalAccount::MAX_IMPS_AMOUNT * 100)
                {
                    $channel = Settlement\Channel::ICICI;

                    // During non baking hour mode to be set to IMPS so payouts can route to FTS.
                    $payout->setMode(FundTransfer\Mode::IMPS);
                }
                else
                {
                    $this->trace->info(TraceCode::ES_ON_DEMAND_IMPS_AMOUNT_LIMIT_EXCEEDED);

                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ES_ON_DEMAND_IMPS_AMOUNT_LIMIT_EXCEEDED,
                        null,
                        [
                            'amount' => $payout->getAmount(),
                        ],
                        PublicErrorDescription::BAD_REQUEST_ES_ON_DEMAND_IMPS_AMOUNT_LIMIT_EXCEEDED);
                }
            }
            elseif ($channel === Settlement\Channel::ICICI)
            {
                // In case of banking hour and channel(ICICI) we want payouts to go via NEFT mode FTA PG integration.
                $payout->setMode(FundTransfer\Mode::NEFT);
            }
        }

        $payout->setChannel($channel);
    }

    protected function postTransactionCreationProcessing(Transaction\Entity $txn, Entity $payout)
    {
        //
        // In an on-demand payout, whatever payout amount the merchant asks for, we DO NOT create
        // a payout for that amount. Instead, we deduct some fees from that amount and create the
        // payout with the REMAINING amount. For example: If a merchant wants a payout of 100rs,
        // we create a payout of 98rs only and keep the remaining 2rs as fees.
        //
        // In case of a normal payout, we add extra fees to the actual payout amount and deduct
        // that much amount of money from the merchant's balance. For example, if a merchant wants
        // to do a payout of 100rs, we create a payout of 100rs and then deduct 102rs from his balance.
        // The 2rs extra is our fees. The reason we don't deduct from the actual payout amount here is
        // because in most cases normal payout is used to payout some money to a customer (of the merchant).
        // The customer would always expect a certain amount. (we can have customer fee bearer concept later).
        //
        // In case of on-demand, it's basically a customer fee bearer kind of concept, where in the customer
        // is the actual merchant himself. He bears the fees for the payout to his account. Hence, the payout
        // happens after deducting the razorpay fees from the actual payout amount. For this reason, we also
        // reset the payout amount here.
        //
        // In both the above cases, we need to ensure that the merchant has enough balance in his account.
        // The validation for the balance would always be payout's amount + our fees.
        //

        if ($payout->getPayoutType() === Entity::ON_DEMAND)
        {
            // Here, payout amount is the amount requested by merchant for payout and fees is
            // levied over it. Also, this fees is deducted from merchant balance. This happens for
            // merchants who do not have 'es_on_demand' feature enabled. In case of 'es_on_demand'
            // merchants, payout fees will be deducted from payout amount requested by the merchant.
            // This is done to allow a merchant to do a payout on requested amount, rather than
            // calculating fees over it and failing a transaction if merchant does not have enough balance.
            $payout->setAmount($txn->getAmount());
        }
    }

    protected function isOutsideBankingHours(): bool
    {
        // Current time during processing.
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        // Checks for holidays.
        if (Holidays::isWorkingDay(Carbon::today(Timezone::IST)) === false)
        {
            $this->trace->info(TraceCode::ES_ON_DEMAND_INITIATED_ON_NON_WORKING_DAY,
                [
                    'current_timestamp' => $currentTime,
                ]);

            return true;
        }

        // Banking hours start time.
        $startTime = Carbon::today(Timezone::IST)->hour(Constants::NEFT_CUTOFF_HOUR_MIN )->getTimestamp();

        // Banking hours end time.
        $endTime = Carbon::today(Timezone::IST)->hour(Constants::NEFT_CUTOFF_HOUR_MAX)->minute(Constants::NEFT_CUTOFF_MINUTE_MAX)->getTimestamp();

        // Checks for non Banking hours on working days.
        if (($currentTime < $startTime) or
            ($currentTime > $endTime))
        {
            $this->trace->info(TraceCode::ES_ON_DEMAND_INITIATED_ON_NON_BANKING_HOUR,
            [
                'current_timestamp'          => $currentTime,
                'banking_start_timestamp'    => $startTime,
                'banking_end_timestamp'      => $endTime,
            ]);

            return true;
        }

        return false;
    }
}
