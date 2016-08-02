<?php

namespace RZP\Models\Merchant\FreeCredits;


use RZP\Error\ErrorCode;
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
        $this->repo->free_credits->saveOrFail($freeCreditLog);
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
        $freeCreditsLog = $this->retrieveById($id);
        $freeCreditsLog->credits += $credits;
        $freeCreditsLog->saveOrFail();
        $merchant = $freeCreditsLog->merchant;
        // Update the merchant balance credit.
        $merchantBalance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);
        $mBalance = $merchantBalance->credits + $credits;
        (new Merchant\Balance\Repository)->editMerchantFreeCredits(
            $freeCreditsLog->merchant, $mBalance);
    }

    /*
     *  Deduct Free Credits from the merchant for a campaign
     *  @return array
     */
    public function deductFreeCredits($id, $credits)
    {
        $freeCreditsLog = $this->repo->free_credits->findOrFailPublic($id);
        $merchant = $freeCreditsLog->merchant;
        $merchantBalance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);

        if ($merchantBalance->credits < $credits)
        {
            throw new Exception\BadRequestValidationFailureException(
            'Credits to deduct is more than total credits available');
        }
        else if ($freeCreditsLog->credits < $credits)
        {
            throw new Exception\BadRequestValidationFailureException(
            'Credits to deduct is more than credits assigned to merchant in campaign');
        }
        $freeCreditsLog->credits -= abs($credits);
        $freeCreditsLog->saveOrFail();
        $mBalance = $merchantBalance->credits - abs($credits);
        (new merchant\balance\repository)->editMerchantFreeCredits($merchant, $mBalance);
    }
}
