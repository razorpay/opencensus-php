<?php

namespace RZP\Models\CardMandate\MandateHubs;

use RZP\Models\Base;
use RZP\Models\CardMandate;

class MandateHubSelector extends Base\Core
{
    /**
     * @param CardMandate\Entity $cardMandate
     * @return BaseHub
     */
    public function GetMandateHubForCardMandate(CardMandate\Entity $cardMandate): BaseHub
    {
        return $this->getHubInstance($cardMandate->getMandateHub());
    }

    /**
     * @param $mandateHub
     * @return BaseHub
     */
    public function getHubInstance($mandateHub) : BaseHub
    {
        switch ($mandateHub)
        {
            case MandateHubs::RUPAY_SIHUB:
                return (new RupaySIHub\RupaySIHub);
            case MandateHubs::MANDATE_HQ:
                return (new MandateHQ\MandateHQ);
            case MandateHubs::BILLDESK_SIHUB:
                return (new BillDeskSIHub\BillDeskSIHub);
            case MandateHubs::PAYU_HUB:
                return (new PayuHub\PayuHub);
            default:
                return (new MandateHQ\MandateHQ);
        }
    }
}
