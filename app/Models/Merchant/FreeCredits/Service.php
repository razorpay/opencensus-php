<?php

namespace RZP\Models\Merchant\FreeCredits;

use Carbon\Carbon;
use Mail;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\FreeCredits;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function grantFreeCreditsForMerchantInCampaign($mid, array $input)
    {
        $campaign = $input['campaign'];
        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        // Check if the log already exists, API is meant to use for creation only.
        // TODO: Consult @shk to replace it with getOrCreate if it is too much hassle.
        $freeCreditsLogExists = (new FreeCredits\Core)->checkIfFreeCreditsLogExists(
            $merchant, $campaign);
        if ($freeCreditsLogExists)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The record already exists for given campaign and merchant.');
        }

        $freeCreditsLog = (new FreeCredits\Core)->create($merchant, $input);

        return $freeCreditsLog->toArray();
    }

    public function fetchFreeCreditsLog($mid, $id)
    {
        // Raises Exception if record does not exist.
        $freeCreditsLog = $this->repo->free_credits->findOrFailPublic($id);

        return $freeCreditsLog->toArrayPublic();
    }

    /*
     * Update the FreeCreditsLog, Presently We support update of credits only.
     *
     * @return array
     */
    public function updateFreeCreditsLog($mid, $id, $input)
    {
        $credits = $input['credits'];
        $freeCreditsLog = $this->repo->free_credits->findByIdAndMerchantId($id, $mid);
        // Add more credits
        if ($credits >= 0)
        {
            $freeCreditsLog = (new FreeCredits\Core)->grantFreeCredits($freeCreditsLog, $credits);
        } // Deduct credits
        else if ($credits < 0)
        {
            $freeCreditsLog = (new FreeCredits\Core)->deductFreeCredits($freeCreditsLog, abs($credits));
        }

        return $freeCreditsLog->toArrayPublic();
    }

    /**
     * Fetches multiple free credit logs based on query params.
     *
     * @return array
     */
    public function fetchMultiple($input)
    {
        $freeCreditsLogs = $this->repo->free_credits->fetch($input, $this->merchant->getId());

        return $freeCreditsLogs->toArrayPublic();
    }
}
