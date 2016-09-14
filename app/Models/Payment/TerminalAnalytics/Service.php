<?php

namespace RZP\Models\Payment\TerminalAnalytics;

use RZP\Models\Base;
use RZP\Models\Payment\TerminalAnalytics;

class Service extends Base\Service
{
    public function createAuditLog($input)
    {
        $action = (new TerminalAnalytics\Core)->create($input);

        return $action->toArrayPublic();
    }

    public function getAuditsForTerminal($id)
    {
        $audits = $this->repo->terminal_analytics->findForTerminal($id);

        return $audits->toArrayPublic();
    }

    public function getAuditsForPayment($id)
    {
        $audits = $this->repo->terminal_analytics->findForPayment($id);

        return $audits->toArrayPublic();
    }

    public function getAuditsForPaymentAndTerminal($paymentId, $terminalId)
    {
        $audits = $this->repo->terminal_analytics->findForPayment($paymentId, $terminalId);

        return $audits->toArrayPublic();
    }
}