<?php

namespace RZP\Models\CardMandate\MandateHubs;

use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\CardMandate;
use RZP\Models\Payment;

class MandateHubSelector extends Base\Core
{
    public function GetMandateHubForCardMandate(CardMandate\Entity $cardMandate): BaseHub
    {
        return $this->getHubInstance($cardMandate->getMandateHub());
    }

    public function getHubInstance($mandateHub): BaseHub
    {
        switch ($mandateHub)
        {
            default:
                return (new MandateHQ\MandateHQ);
        }
    }
}
