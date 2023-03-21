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

        $setlDetails = (new Core)->getSettlementDetails($id, $merchant);

        $setlDetails['setl_details']['has_aggregated_fee_tax'] = $setlDetails['has_aggregated_fee_tax'];

        return $setlDetails['setl_details'];
    }

    public function postSettlementDetailsForOldTxns($input)
    {
        $data = (new Core)->addSettlementDetailsForOldTxns($input);

        return $data;
    }
}
