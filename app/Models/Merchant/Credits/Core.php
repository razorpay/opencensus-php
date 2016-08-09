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
            $creditsLog = new Credits\Entity;
            $creditsLog = (new Credits\Entity)->build($input);
            $creditsLog->generateId();
            $creditsLog->merchant()->associate($merchant);
            $this->repo->saveOrFail($creditsLog);

            $this->updateCreditsInMerchantAccount($merchant, $creditsLog->getValue());

            return $creditsLog;
        });
    }

    public function updateCreditsInMerchantAccount($merchant, $credits)
    {
        // Add the credits to merchant's main balance
        $merchantBalance = $merchant->balance->getCredits();
        $newCredits = $merchantBalance + $credits;
        $this->repo->balance->editMerchantFreeCredits($merchant, $newCredits);
    }

    /*
     * Update credits in the credits Log and merchant credits.
     */
    public function updateCredits($creditsLog, $credits)
    {
        // When we update the credits, We need to subsequently add/subtract credits from merchant balance.
        // Transaction is rolled back if merchant credit balance is less than zero.
        Credits\Validator::validateNewCreditsValue($creditsLog, $credits);
        return $this->repo->transaction(function() use ($creditsLog, $credits)
        {
            $creditsDifference = $credits - $creditsLog->getValue();
            $creditsLog->setValue($credits);
            $this->repo->saveOrFail($creditsLog);
            $merchant = $creditsLog->merchant;
            $this->updateCreditsInMerchantAccount($merchant, $creditsDifference);

            return $creditsLog;
        });
    }

    /*
     * Deletes Credit log for a merchant in a campaign
     */
    public function deleteCredits($creditsLog)
    {
        return $this->repo->transaction(function() use ($creditsLog)
        {
            $credits = $creditsLog->getValue();
            $this->repo->deleteOrFail($creditsLog);
            $merchant = $creditsLog->merchant;
            $this->updateCreditsInMerchantAccount($merchant, -$credits);

            return $creditsLog;
        });
    }
}
