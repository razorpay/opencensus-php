<?php

namespace RZP\Models\Merchant\Credits;

use Carbon\Carbon;
use Mail;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Credits;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function grantCreditsForMerchant($mid, array $input)
    {
        $campaign = $input['campaign'];
        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        Credits\Validator::validateNewCreditLog($campaign, $merchant);

        $creditsLog = (new Credits\Core)->create($merchant, $input);

        return $creditsLog->toArray();
    }

    public function fetchCreditsLog($mid, $id)
    {
        // Raises Exception if record does not exist.
        $creditsLog = $this->repo->credits->findByIdAndMerchantId($mid, $id);

        return $creditsLog->toArrayPublic();
    }

    /*
     * Update the CreditsLog, Presently We support update of credits only.
     *
     * @return array
     */
    public function updateCreditsLog($mid, $id, $input)
    {
        $credits = $input['value'];
        $creditsLog = $this->repo->credits->findByIdAndMerchantId($id, $mid);

        // Add more credits
        if ($credits >= 0)
        {
            $creditsLog = (new Credits\Core)->grantCredits($creditsLog, $credits);
        } // Deduct credits
        else if ($credits < 0)
        {
            $creditsLog = (new Credits\Core)->deductCredits($creditsLog, abs($credits));
        }

        return $creditsLog->toArray();
    }

    /**
     * Fetches multiple free credit logs based on query params.
     *
     * @return array
     */
    public function fetchMultiple($input)
    {
        $creditsLogs = $this->repo->credits->fetch($input, $this->merchant->getId());

        return $creditsLogs->toArrayPublic();
    }
}
