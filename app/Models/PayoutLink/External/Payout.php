<?php

namespace RZP\Models\PayoutLink\External;

use App;
use RZP\Models\Payout\Purpose;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\PayoutLink\Entity as PayoutLinkEntity;

/**
 * Class Payout
 * This class will be used to interact with the Payouts Module.
 * Ideally these should be API calls, but as they are in the same repo, we will be making direct function calls
 * When this module moves out, we will replace function calls with API calls
 */

class Payout
{
    protected $trace;

    protected $repo;

    public function __construct()
    {
        $this->trace = App::getFacadeRoot()['trace'];

        $this->repo = App::getFacadeRoot()['repo'];
    }

    public function processPayout(PayoutLinkEntity $payoutLink, MerchantEntity $merchant, string $mode): PayoutEntity
    {
        $input = [
            PayoutEntity::NARRATION            => $payoutLink->getDescription(),
            PayoutEntity::PURPOSE              => Purpose::PAYOUT,
            PayoutEntity::AMOUNT               => $payoutLink->getAmount(),
            PayoutEntity::CURRENCY             => $payoutLink->getCurrency(),
            PayoutEntity::NOTES                => $payoutLink->getNotes()->toArray(),
            PayoutEntity::BALANCE_ID           => $payoutLink->getBalanceId(),
            PayoutEntity::FUND_ACCOUNT_ID      => $payoutLink->fundAccount->getPublicId(),
            PayoutEntity::MODE                 => $mode,
            PayoutEntity::REFERENCE_ID         => $payoutLink->getReceipt(),
            PayoutEntity::PAYOUT_LINK_ID       => $payoutLink->getId(),
            PayoutEntity::QUEUE_IF_LOW_BALANCE => true
        ];

        $payout = (new PayoutCore())->createPayoutToFundAccount($input, $merchant);

        return $payout;
    }
}
