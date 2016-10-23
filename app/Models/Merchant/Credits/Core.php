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
        if (isset($input[Credits\Entity::TYPE]) === false)
        {
            $input[Credits\Entity::TYPE] = Credits\Type::AMOUNT;
        }

        $creditsLog = (new Credits\Entity)->build($input);

        $creditsLog->merchant()->associate($merchant);

        $this->repo->credits->validateCampaignCreditsNotAssigned(
            $creditsLog->getCampaign(), $merchant, $creditsLog->getType());

        return $this->repo->transaction(function() use ($merchant, $creditsLog)
        {
            $this->repo->saveOrFail($creditsLog);

            $type = $creditsLog->getType();

            $this->updateCreditsInMerchantAccount($merchant, $creditsLog->getValue(), $type);

            return $creditsLog;
        });
    }

    public function updateCreditsInMerchantAccount($merchant, $credits, $type = Credits\Type::AMOUNT)
    {
        if ($type === Credits\Type::AMOUNT)
        {
            // Add the credits to merchant's main balance
            $merchantBalance = $merchant->balance->getCredits();

            $newCredits = $merchantBalance + $credits;

            $this->repo->balance->editMerchantFreeCredits($merchant, $newCredits);
        }
        else if ($type === Credits\Type::FEE)
        {
            $merchantFeeCredits = $merchant->balance->getFeeCredits();

            $newCredits = $merchantFeeCredits + $credits;

            $this->repo->balance->editMerchantFeeCredits($merchant, $newCredits);
        }
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

            $type = $creditsLog->getType();

            $this->updateCreditsInMerchantAccount($creditsLog->merchant, $creditsDifference, $type);

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
            $type = $creditsLog->getType();

            // Since we are deleting, value should be negative
            $credits = -1 * $creditsLog->getValue();
            $this->repo->deleteOrFail($creditsLog);

            $this->updateCreditsInMerchantAccount($creditsLog->merchant, $credits, $type);

            return $creditsLog;
        });
    }
}
