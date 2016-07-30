<?php

namespace RZP\Models\Settlement\Details;

use RZP\Models\Base;
use RZP\Models\Settlement;

class Service extends Base\Service
{
    public function getSettlementDetails($id)
    {
        Settlement\Entity::verifyIdAndStripSign($id);

        $merchant = $this->merchant;

        $setlDetails = $this->repo->settlement_details->getSettlementDetails($id, $merchant);

        return $setlDetails->toArrayPublic();
    }

    public function postSettlementDetailsForOldTxns($input)
    {
        $data = (new Core)->addSettlementDetailsForOldTxns($input);

        return $data;
    }
}