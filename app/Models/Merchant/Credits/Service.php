<?php

namespace RZP\Models\Merchant\Credits;

use Mail;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Credits;

class Service extends Base\Service
{
    public function grantCreditsForMerchant($mid, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $creditsLog = (new Credits\Core)->create($merchant, $input);

        return $creditsLog->toArrayPublic();
    }

    public function fetchCreditsLog($id)
    {
        // Raises Exception if record does not exist.
        $creditsLog = $this->repo->credits->findByPublicIdAndMerchant($id, $this->merchant);

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

        return $creditsLog->toArrayPublic();
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
