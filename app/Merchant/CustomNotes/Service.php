<?php

namespace App\Merchant\CustomNotes;

use App\Base;

class Service extends Base\Service
{

    /**
     * To get the list of notifications to be shown to a user.
     *
     * @param array $user User details.
     *
     * @return array List of notifications for a user.
     */
    public function getNotesForPaymentLinksForMerchant(string $currentMerchantId): array
    {
        $customNotes = Constants::getNotesForPaymentLinksByMID($currentMerchantId);

        return $customNotes;
    }
}
