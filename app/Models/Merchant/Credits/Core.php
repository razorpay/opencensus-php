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
        $creditsLog = (new Credits\Entity)->build($input);

        $creditsLog->merchant()->associate($merchant);

        # Merchant is not set if it is an admin
        $isAdmin = $this->merchant === null ? true : false;

        $creditsLog->setIsAdmin($isAdmin);

        $this->repo->credits->validateCampaignCreditsNotAssigned(
                                $creditsLog->getCampaign(), $merchant);

        return $this->repo->transaction(function() use ($merchant, $creditsLog)
        {
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
        //
        // When we update the credits, We need to subsequently add/subtract credits
        // from merchant balance.
        // Transaction is rolled back if merchant credit balance is less than zero.
        //

        Credits\Validator::validateNewCreditsValue($creditsLog, (int) $credits);

        return $this->repo->transaction(function() use ($creditsLog, $credits)
        {
            $creditsDifference = $credits - $creditsLog->getValue();
            $creditsLog->setValue($credits);
            $this->repo->saveOrFail($creditsLog);

            $this->updateCreditsInMerchantAccount($creditsLog->merchant, $creditsDifference);

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
            // Since we are deleting, value should be negative
            $credits = -1 * $creditsLog->getValue();
            $this->repo->deleteOrFail($creditsLog);

            $this->updateCreditsInMerchantAccount($creditsLog->merchant, $credits);

            return $creditsLog;
        });
    }
}
