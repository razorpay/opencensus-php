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
        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $creditsLog = (new Credits\Core)->create($merchant, $input);

        return $creditsLog->toArray();
    }

    public function fetchCreditsLog($mid, $id)
    {
        // Raises Exception if record does not exist.
        $creditsLog = $this->repo->credits->findByIdAndMerchantId($id, $mid);

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

        $creditsLog = (new Credits\Core)->updateCredits($creditsLog, $credits);

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

    public function deleteCreditsLog($mid, $id)
    {
        $creditsLog = $this->repo->credits->findByIdAndMerchantId($id, $mid);

        (new Credits\Core)->deleteCredits($creditsLog);

        return ['success' => true];
    }
}
