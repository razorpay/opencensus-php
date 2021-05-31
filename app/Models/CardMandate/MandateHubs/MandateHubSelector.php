<?php

namespace RZP\Models\CardMandate\MandateHubs;

use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\CardMandate;
use RZP\Models\Payment;

class MandateHubSelector extends Base\Core
{
    public function GetMandateHubForPayment(Payment\Entity $payment): BaseHub
    {
        return (new MandateHQ\MandateHQ);
    }

    public function GetMandateHubForCardMandate(CardMandate\Entity $cardMandate): BaseHub
    {
        switch ($cardMandate->getMandateHub())
        {
            case MandateHubs::MANDATE_HQ:
                return (new MandateHQ\MandateHQ);
            default:
                throw new LogicException('should not have reached here');
        }
    }
}
