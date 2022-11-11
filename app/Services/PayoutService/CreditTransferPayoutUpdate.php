<?php

namespace RZP\Services\PayoutService;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Models\CreditTransfer;
use RZP\Http\Request\Requests;

class CreditTransferPayoutUpdate extends Base
{
    const UPDATE_CREDIT_TRANSFER_PAYOUT = '/payouts/update_credit_transfer_payout';

    // credit transfer payout service update name for singleton class
    const CREDIT_TRANSFER_PAYOUT_SERVICE_UPDATE = 'credit_transfer_payout_service_update';

    public function UpdateCreditTransferPayoutOnPayoutsService(CreditTransfer\Entity $creditTransfer)
    {
        $creditTransferStatus = $creditTransfer->getStatus();

        $payload = [
            "source_id"            => $creditTransfer->getSourceEntityId(),
            Payout\Entity::STATUS  => $creditTransferStatus,
            Payout\Entity::CHANNEL => $creditTransfer->getChannel(),
            Payout\Entity::MODE    => $creditTransfer->getMode(),
        ];

        switch ($creditTransferStatus)
        {
            case CreditTransfer\Status::PROCESSED:
                $payload = $payload + [
                        Payout\Entity::UTR => $creditTransfer->getUtr()
                    ];
                break;
        }

        $this->trace->info(TraceCode::CREDIT_TRANSFER_PAYOUT_SERVICE_UPDATE_INITIATE,
            [
                'payout_id' => $creditTransfer->getSourceEntityId(),
                'payload'   => $payload,
            ]);

        $response = $this->makeRequestAndGetContent(
            $payload,
            self::UPDATE_CREDIT_TRANSFER_PAYOUT,
            Requests::POST);

        $this->trace->info(TraceCode::CREDIT_TRANSFER_PAYOUT_SERVICE_UPDATE_RESPONSE,
            [
                'payout_id' => $creditTransfer->getSourceEntityId(),
                'response'  => $response,
            ]);

        return $response;
    }
}
