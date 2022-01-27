<?php

namespace RZP\Models\Admin;

use Mail;
use Carbon\Carbon;

use RZP\Models;
use RZP\Models\Base;
use RZP\Constants\Timezone;
use RZP\Mail\Admin\BankingScorecard as BankingScorecardMail;

class BankingScorecard extends Base\Core
{
    /**
     * Generate data for payout analysis for:
     * 1. Total TPV for the month
     * 2. Total txn count for the month
     * 3. Total fee collected for the month
     * 4. Yesterday's TPV
     * 5. Yesterday's txn count
     * 6. Total fee collection from yesterday payouts
     * 7. Merchant level yesterday's TPV and txn count
     * here tpv --> Total Payout Value
     *
     * @param $input
     *
     * @return array
     */
    public function generateBankingScorecardData($input)
    {
        (new Validator)->validateInput('bankingScorecard', $input);

        $limit = $input['count'];

        // get yesterday's total payout amount, fees and tax count
        $yesterdayPayoutAmountFeeAndTaxCount       = $this->repo->payout->getPayoutAmountFeeAndTaxCountForYesterday();

        // get total payout amount, fee and tax count for month
        $payoutAmountFeeAndTaxCountForMonth        = $this->getPayoutAmountFeeAndTaxCountForMonth();

        // get yesterday's total payout amount and tax count for merchants
        $yesterdayMerchantsPayoutAmountAndTaxCount = $this->repo->payout->getYesterdayMerchantsPayoutAmountAndTaxCountGroupByMerchant($limit);

        return [
            'yesterdayPayoutAmountFeeAndTaxCount'       => $yesterdayPayoutAmountFeeAndTaxCount,
            'payoutAmountFeeAndTaxCountForMonth'        => $payoutAmountFeeAndTaxCountForMonth,
            'yesterdayMerchantsPayoutAmountAndTaxCount' => $yesterdayMerchantsPayoutAmountAndTaxCount,
        ];
    }

    /**
     * Send mail function for Banking Scorecard
     *
     * @param $data
     *
     */
    public function sendMail($data)
    {
        $bankingScorecardMail = new BankingScorecardMail($data);

        //
        // This mail is huge (more than 256KB) and breaches SQS message payload.
        // Think twice before cleverly changing it to queue, as was attempted
        // previously check this https://razorpay.slack.com/archives/CR3K6S6C8/p1594860881228700
        //
        Mail::send($bankingScorecardMail);
    }

    /**
     * get total payout amount, fee and tax count for month
     * Run for 5 day interval of month and sum it up for the data
     * till the time the scorecard has been created.
     *
     */
    public function getPayoutAmountFeeAndTaxCountForMonth()
    {
        $startTimeOfMonth = Carbon::yesterday(Timezone::IST)->startOfMonth()->startOfDay();
        $endTimeOfMonthTillNow = Carbon::today(Timezone::IST)->startOfDay();

        $from = $startTimeOfMonth;

        $noOfDaysToCalculate = $from->diffInDays($endTimeOfMonthTillNow);

        $daysToAdd = 3;

        if (($noOfDaysToCalculate % 3) !== 0)
        {
            $daysToAdd = $noOfDaysToCalculate % 3;
        }

        $to = $from->copy()->addDay($daysToAdd)->startOfDay();

        $finalResult = null;

        while ($endTimeOfMonthTillNow->greaterThanOrEqualTo($to) === true)
        {
            $result = $this->repo->payout->getPayoutAmountFeeAndTaxCountBetweenTimestamp(
                $from->getTimestamp(),
                $to->getTimestamp());

            if (is_null($finalResult) === true)
            {
                $finalResult = $result;
            }
            else
            {
                $payoutCount = $result->getAttribute('payout_count') + $finalResult->getAttribute('payout_count');
                $payoutAmountCr = $result->getAttribute('payout_amount_cr') + $finalResult->getAttribute('payout_amount_cr');
                $payoutFeeCollected = $result->getAttribute('payout_fee_collected') + $finalResult->getAttribute('payout_fee_collected');


                $finalResult->setAttribute('payout_count', $payoutCount);

                $finalResult->setAttribute('payout_amount_cr', $payoutAmountCr);

                $finalResult->setAttribute('payout_fee_collected', $payoutFeeCollected);
            }

            $from = $to->addSecond(1);
            $to = $from->copy()->addDay(3)->startOfDay();
        }

        return $finalResult;
    }
}
