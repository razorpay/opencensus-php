<?php

namespace RZP\Models\Terminal\AuditLog;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Terminal\AuditLog;

class Service extends Base\Service
{
    public function createAuditLog($input)
    {
        $action = (new AuditLog\Core)->create($input);

        return $action->toArrayPublic();
    }

    public function getAuditsForTerminal($id)
    {
        $audits = $this->repo->terminal_auditlog->findForTerminal($id);

        return $audits->toArrayPublic();
    }

    public function getAuditsForPayment($id)
    {
        $audits = $this->repo->terminal_auditlog->findForPayment($id);

        return $audits->toArrayPublic();
    }

    public function getAuditsForPaymentAndTerminal($payment_id, $terminal_id)
    {
        $audits = $this->repo->terminal_auditlog->findForPayment($payment_id, $terminal_id);

        return $audits->toArrayPublic();
    }

    public function getAuditsForTerminalBetween($from, $to, $id)
    {
        $audits = $this->repo->terminal_auditlog->findBetweenTimestampsForTerminal($from, $to, $id);

        return $audits->toArrayPublic();
    }
}
