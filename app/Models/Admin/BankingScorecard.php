<?php

namespace RZP\Models\Admin;

use Mail;

use RZP\Models;
use RZP\Models\Base;
use RZP\Mail\Admin\BankingScorecard as BankingScorecardMail;

class BankingScorecard extends Base\Core
{
    /**
     * Generate data for payout analysis for:
     * 1. Total TPV for the month
     * 2. Total txn count for the month
     * 3. Yesterday's TPV
     * 4. Yesterday's txn count
     * 5. Merchant level yesterday's TPV and txn count
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

        // get yesterday's total payout amount and tax count
        $yesterdayPayoutAmountAndTaxCount          = $this->repo->payout->getPayoutAmountAndTaxCountForYesterday();

        // get total payout amount and tax count for month
        $payoutAmountAndTaxCountForMonth           = $this->repo->payout->getPayoutAmountAndTaxCountForMonth();

        // get yesterday's total payout amount and tax count for merchants
        $yesterdayMerchantsPayoutAmountAndTaxCount = $this->repo->payout->getYesterdayMerchantsPayoutAmountAndTaxCountGroupByMerchant($limit);

        return [
            'yesterdayPayoutAmountAndTaxCount'          => $yesterdayPayoutAmountAndTaxCount,
            'payoutAmountAndTaxCountForMonth'           => $payoutAmountAndTaxCountForMonth,
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

        Mail::send($bankingScorecardMail);
    }
}
