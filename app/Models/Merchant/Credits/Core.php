<?php

namespace RZP\Models\Merchant\Credits;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Credits;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{

    public function create($merchant, $input)
    {
        return $this->repo->transaction(function() use ($merchant, $input)
        {
            $creditsLog = (new Credits\Entity)->build($input);
            $creditsLog->merchant()->associate($merchant);
            $this->repo->saveOrFail($creditsLog);

            $this->updateCreditsInMerchantAccount($merchant, $creditsLog->getValue());

            return $creditsLog;
        });
    }

    public function updateCreditsInMerchantAccount($merchant, $credits, $operation = 'grant')
    {
        // Add the credits to merchant's main balance
        $merchantBalance = $merchant->balance->getCredits();
        if ($operation === 'grant')
        {
            $newCredits = $merchantBalance + $credits;
        }
        else if ($operation === 'deduct')
        {
            $newCredits = $merchantBalance - $credits;
        }
        $this->repo->balance->editMerchantFreeCredits($merchant, $newCredits);
    }

    /*
     * Add Credits to the merchant for a campaign
     */
    public function grantCredits($creditsLog, $credits)
    {
        return $this->repo->transaction(function() use ($creditsLog, $credits)
        {
            // Update the creditsLog
            $creditsLog->addCredits($credits);
            $this->repo->saveOrFail($creditsLog);
            $merchant = $creditsLog->merchant;

            $this->updateCreditsInMerchantAccount($merchant, $credits);

            return $creditsLog;
        });
    }

    /*
     *  Deduct Credits from the merchant for a campaign
     *  @return array
     */
    public function deductCredits($creditsLog, $credits)
    {
        // Make it to absolute value to make cmp easier. Dev may not send abs values everytime.
        $credits = abs($credits);
        $merchant = $creditsLog->merchant;
        $merchantBalance = $this->repo->balance->getMerchantBalance($merchant);

        Credits\Validator::validateCreditsForDeduction($creditsLog, $merchantBalance, $credits);

        return $this->repo->transaction(function() use ($merchant, $creditsLog, $credits)
        {

            $creditsLog->deductCredits($credits);
            $this->repo->saveOrFail($creditsLog);
            $this->updateCreditsInMerchantAccount($merchant, $credits, 'deduct');

            return $creditsLog;
        });

    }
}
