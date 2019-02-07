<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Models\Payout as PayoutModel;
use RZP\Models\FundAccount as FundAccountModel;

class Payout extends Base
{
    /**
     * @var PayoutModel\Core
     */
    protected $payoutCore;

    /**
     * @var FundAccount
     */
    protected $fundAccountProcessor;

    public function __construct(Batch\Entity $batch)
    {
        parent::__construct($batch);

        $this->payoutCore = new PayoutModel\Core;

        $this->fundAccountProcessor = new FundAccount($batch);

        // Repository method at getBalanceByAccountNumberOrFail() uses context's merchant.
        app()->basicauth->setMerchant($this->merchant);
    }

    /**
     * {@inheritDoc}
     */
    protected function processEntry(array & $entry)
    {
        $this->repo->transaction(function () use (& $entry)
        {
            $fundAccount = $this->processEntryForFundAccount($entry);

            $payout = $this->processEntryForPayoutForFundAccount($entry, $fundAccount);

            $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
            $entry[Batch\Header::PAYOUT_ID] = $payout->getPublicId();
        });
    }

    protected function processEntryForFundAccount(array & $entry): FundAccountModel\Entity
    {
        if (empty($entry[Batch\Header::FUND_ACCOUNT_ID]) === false)
        {
            return $this->repo->fund_account->findByPublicIdAndMerchant(
                $entry[Batch\Header::FUND_ACCOUNT_ID],
                $this->merchant);
        }
        else
        {
            return $this->fundAccountProcessor->processEntryAndGetEntity($entry);
        }
    }

    protected function processEntryForPayoutForFundAccount(
        array & $entry,
        FundAccountModel\Entity $fundAccount): PayoutModel\Entity
    {
        $input = Batch\Helpers\Payout::getPayoutInput($entry, $fundAccount, $this->merchant);

        return $this->payoutCore->createPayoutToFundAccount($input, $this->merchant);
    }
}
