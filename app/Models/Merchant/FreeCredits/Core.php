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
    public function checkIfFreeCreditsLogExists($merchant, $campaign)
    {
        return $this->repo->free_credits->findByCampaignAndMerchantId($campaign, $merchant);
    }

    public function create($merchant, $input)
    {
        $freeCreditsLog = (new FreeCredits\Entity)->build($input);
        $freeCreditsLog->merchant()->associate($merchant);
        $this->repo->saveOrFail($freeCreditsLog);

        // Add the free credits to merchant's main balance
        // TODO: Check if we can do it asynchronously.
        $merchant = $freeCreditsLog->merchant;
        $merchantBalance = $this->repo->balance->getMerchantBalance($merchant);
        $mBalance = $merchantBalance->getCredits() + $freeCreditsLog->getCredits();
        $this->repo->balance->editMerchantFreeCredits($merchant, $mBalance);

        return $freeCreditsLog;
    }

    /*
     * Add Free Credits to the merchant for a campaign
     */
    public function grantFreeCredits($freeCreditsLog, $credits)
    {
        // Update the freeCreditLog
        $freeCreditsLog->addCredits($credits);
        $this->repo->saveOrFail($freeCreditsLog);
        $merchant = $freeCreditsLog->merchant;

        // Update the merchant balance credit.
        $merchantBalance = $this->repo->balance->getMerchantBalance($merchant);
        $mBalance = $merchantBalance->getCredits() + $credits;
        $this->repo->balance->editMerchantFreeCredits($merchant, $mBalance);

        return $freeCreditsLog;
    }

    /*
     *  Deduct Free Credits from the merchant for a campaign
     *  @return array
     */
    public function deductFreeCredits($freeCreditsLog, $credits)
    {
        // Make it to absolute value to make cmp easier. Dev may not send abs values everytime.
        $credits = abs($credits);
        $merchant = $freeCreditsLog->merchant;
        $merchantBalance = $this->repo->balance->getMerchantBalance($merchant);

        if ($merchantBalance->getCredits() < $credits)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Credits to deduct is more than total credits available');
        }
        else if ($freeCreditsLog->getCredits() < $credits)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Credits to deduct is more than credits assigned to merchant in campaign');
        }

        $freeCreditsLog->deductCredits($credits);
        $freeCreditsLog->saveOrFail();
        //$this->repo->saveOrFail($freeCreditsLog);
        $mBalance = $merchantBalance->getCredits() - $credits;
        $this->repo->balance->editMerchantFreeCredits($merchant, $mBalance);

        return $freeCreditsLog;
    }
}
