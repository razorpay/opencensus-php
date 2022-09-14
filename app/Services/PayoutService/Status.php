<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;

class Status extends Base
{

    const UPDATE_PAYOUT_STATUS_WITH_FTS = '/payouts/update_payouts_with_fts';

    // payout status service name for singleton class
    const PAYOUT_SERVICE_STATUS = 'payout_service_status';

    public function updatePayoutStatusViaFTS($payoutId,
                                             string $status,
                                             string $failureReason = null,
                                             string $bankStatusCode = null,
                                             array $ftsInfo = [])
    {
        $request = [
            'source_id'        => $payoutId,
            'status'           => $status,
            'failure_reason'   => $failureReason,
            'bank_status_code' => $bankStatusCode
        ];

        // FTS info is required at PS end for ledger integration. Ledger service expects fts info
        // In case of processed & reversed cases.
        if (array_key_exists(Entity::FTS_FUND_ACCOUNT_ID, $ftsInfo)) {
            $request += [
                Entity::FTS_FUND_ACCOUNT_ID => strval($ftsInfo[Entity::FTS_FUND_ACCOUNT_ID]),
            ];
        }

        if (array_key_exists(Entity::FTS_ACCOUNT_TYPE, $ftsInfo)) {
            $request += [
                Entity::FTS_ACCOUNT_TYPE => $ftsInfo[Entity::FTS_ACCOUNT_TYPE],
            ];
        }

        if (array_key_exists(Entity::FTS_STATUS, $ftsInfo)) {
            $request += [
                Entity::FTS_STATUS => $ftsInfo[Entity::FTS_STATUS],
            ];
        }

        $this->trace->info(TraceCode::PAYOUT_STATUS_UPDATE_FROM_FTS_REQUEST,
            $request);

        $response = $this->makeRequestAndGetContent(
            $request,
            self::UPDATE_PAYOUT_STATUS_WITH_FTS,
            Requests::PATCH);

        return $response;
    }
}
