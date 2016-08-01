<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment\Analytics;

class Service extends Base\Service
{
    public function createAuditLog($input)
    {
        $action = (new Analytics\Core)->create($input);

        return $action->toArrayPublic();
    }

    public function getAuditsForTerminal($id)
    {
        $audits = $this->repo->payment_analytics->findForTerminal($id);

        return $audits->toArrayPublic();
    }

    public function getAuditsForPayment($id)
    {
        $audits = $this->repo->payment_analytics->findForPayment($id);

        return $audits->toArrayPublic();
    }

    public function getAuditsForPaymentAndTerminal($payment_id, $terminal_id)
    {
        $audits = $this->repo->payment_analytics->findForPayment($payment_id, $terminal_id);

        return $audits->toArrayPublic();
    }

    public function getAuditsForTerminalBetween($from, $to, $id)
    {
        $audits = $this->repo->payment_analytics->findBetweenTimestampsForTerminal($from, $to, $id);

        return $audits->toArrayPublic();
    }
}
