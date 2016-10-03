<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Http\RequestHeader;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment\Analytics;
use RZP\Models\Payment\Analytics\Metadata;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function createAuditLog($log, $rawData)
    {
        (new Analytics\Parser)->recordPaymentRequestData($rawData, $log);

        $action = (new Analytics\Core)->create($log);

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

    public function getAuditsForPaymentAndTerminal($paymentId, $terminalId)
    {
        $audits = $this->repo->payment_analytics->findForPayment($paymentId, $terminalId);

        return $audits->toArrayPublic();
    }

    public function getAuditsForTerminalBetween($from, $to, $id)
    {
        $audits = $this->repo->payment_analytics->findBetweenTimestampsForTerminal($from, $to, $id);

        return $audits->toArrayPublic();
    }

    // set the payment request as s2s for analytics
    public function setMetadataForS2SPayment($input)
    {
        $input['_'] = isset($input['_']) ? $input['_'] : [];

        if (isset($input['_'][Entity::LIBRARY]) === false)
        {
            $input['_'][Entity::LIBRARY] = Metadata::DIRECT;
        }

        return $input;
    }
}