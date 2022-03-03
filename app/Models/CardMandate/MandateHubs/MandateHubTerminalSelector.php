<?php

namespace RZP\Models\CardMandate\MandateHubs;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\CardMandate;
use RZP\Models\Terminal\Entity;
use RZP\Exception\DbQueryException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Payment\Processor\TerminalProcessor;

class MandateHubTerminalSelector extends Base\Core
{

    public function GetTerminalForPayment(Payment\Entity $payment, CardMandate\Entity $cardMandate)
    {
        $terminals = (new TerminalProcessor)->getTerminalsForPayment($payment, null, $cardMandate);

        if (empty($terminals))
        {
            throw new ServerErrorException(null,
                ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND,
                null);

        }

        $selectedTerminalIds = array_pluck($terminals, 'id');

        $terminal_id = $selectedTerminalIds[0];

        return $this->repo->terminal->findOrFail($terminal_id);
    }
}
