<?php

namespace RZP\Models\Merchant\FreeCredits;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\FreeCredits;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function checkIfFreeCreditsLogExists($id)
    {
        return $this->repo->recordExists($id);
    }

    public function create($input)
    {
        $freeCreditLog = (new FreeCredits\Entity)->build($input);
        $this->repo->saveOrFail($freeCreditLog);
    }

    public function retrieveById($id)
    {
        return $this->repo->findOrFailPublic($id);
    }

    /*
     * Add Free Credits to the merchant for a campaign
     */
    public function addMoreFreeCredits($id, $credits)
    {
        $free_credits_log = $this->repo->findOrFailPublic($id);
        $free_credits_log->credits += $credits;
        $this->repo->saveOrFail($free_credits_log);
    }

    /*
     *  Deduct Free Credits from the merchant for a campaign
     *  @return array
     */
    public function deductFreeCredits($id, $credits)
    {
        $free_credits_log = $this->repo->findOrFailPublic($id);
        $merchant = $free_credits_log->merchant;
        $merchantBalance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);
        if ($merchantBalance < $credits)
        {
            throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_MERCHANT_TOTAL_CREDITS_LESSER_THAN_CREDITS_TO_SUBTRACT);
        }
        else if ($free_credits_log->credits < $credits)
        {
            throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_MERCHANT_CAMPAIGN_CREDITS_LESSER_THAN_CREDITS_TO_SUBTRACT);
        }
        $free_credits_log->credits -= $credits;
        $this->repo->saveOrFail($free_credits_log);
        (new Merchant\Balance\Repository)->editMerchantFreeCredits($merchant, $credits);
    }
}
