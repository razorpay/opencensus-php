<?php

namespace RZP\Models\Merchant\FreeCredits;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\FreeCredits;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function checkIfFreeCreditsLogExists($merchant_id, $campaign)
    {
        return $this->repo->free_credits->recordExists($merchant_id, $campaign);
    }

    public function create($mid, $input)
    {
        $input['merchant_id'] = $mid;
        $freeCreditLog = (new FreeCredits\Entity)->build($input);
        $this->repo->saveOrFail($freeCreditLog);
        return $freeCreditLog;
    }

    public function retrieveById($id)
    {
        return $this->repo->free_credits->findOrFailPublic($id);
    }

    /*
     * Add Free Credits to the merchant for a campaign
     */
    public function grantFreeCredits($id, $credits)
    {
        $free_credits_log = $this->repo->free_credits->findOrFailPublic($id);
        $free_credits_log->credits += $credits;
        $free_credits_log->saveOrFail();
        $merchant = $free_credits_log->merchant;
        // Update the merchant balance credit.
        $merchantBalance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);
        $mBalance = $merchantBalance->credits + $credits;
        (new Merchant\Balance\Repository)->editMerchantFreeCredits(
            $free_credits_log->merchant, $mBalance);
    }

    /*
     *  Deduct Free Credits from the merchant for a campaign
     *  @return array
     */
    public function deductFreeCredits($id, $credits)
    {
        $free_credits_log = $this->repo->free_credits->findOrFailPublic($id);
        $merchant = $free_credits_log->merchant;
        $merchantBalance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);

        if ($merchantBalance->credits < $credits)
        {
            throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_MERCHANT_TOTAL_CREDITS_LESSER_THAN_CREDITS_TO_SUBTRACT);
        }
        else if ($free_credits_log->credits < $credits)
        {
            throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_MERCHANT_CAMPAIGN_CREDITS_LESSER_THAN_CREDITS_TO_SUBTRACT);
        }
        $free_credits_log->credits -= abs($credits);
        $free_credits_log->saveOrFail();
        $mBalance = $merchantBalance->credits - abs($credits);
        (new merchant\balance\repository)->editmerchantfreecredits($merchant, $mBalance);
        var_dump($free_credits_log);
    }
}
