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

        $row = $action->toArrayPublic();

        $this->logUnknownData($row);
        s($row);
        return $row;
    }

    protected function logUnknownData($row)
    {
        $invalidData = [];

        foreach ($row as $key => $value) {

            if (Metadata::isInvalidValue($value))
            {
                $invalidData[$key] = $value;
            }
        }

        $this->trace->error(TraceCode::PAYMENT_ANALYTICS_UNRECOGNIZED_DATA,
            ['invalid_data' => $invalidData]);
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
}