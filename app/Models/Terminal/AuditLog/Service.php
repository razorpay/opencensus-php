<?php

namespace RZP\Models\Terminal\AuditLog;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Terminal\Action;

class Service extends Base\Service
{
    public function createAuditLog($id, $input)
    {
        $action = (new Action\Core)->create($input, $id);

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
