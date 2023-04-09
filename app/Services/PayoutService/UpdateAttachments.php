<?php

namespace RZP\Services\PayoutService;

use Requests;

use Razorpay\Edge\Passport\Passport;

use RZP\Trace\TraceCode;
use RZP\Models\Payout\Entity as PayoutEntity;

class UpdateAttachments extends Base
{
    const UPDATE_ATTACHMENTS_URI = '/payouts/%s/attachments';

    const BULK_UPDATE_ATTACHMENTS_URI = '/payouts/attachments';

    // payout update attachments service name for singleton class
    const PAYOUT_SERVICE_UPDATE_ATTACHMENTS = 'payout_service_update_attachments';

    public function updateAttachments(string $payoutId, array $input)
    {
        $this->trace->info(TraceCode::UPDATE_ATTACHMENTS_FOR_PAYOUT_VIA_MICROSERVICE_REQUEST,
                           [
                               'payout_id' => $payoutId,
                               'input'     => $input,
                           ]);

        $url = sprintf(self::UPDATE_ATTACHMENTS_URI, PayoutEntity::getSignedId($payoutId));

        return $this->sendRequestAndGetContent($input, $url);
    }

    protected function sendRequestAndGetContent(array $input, $url)
    {
        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        $response = $this->makeRequestAndGetContent(
            $input,
            $url,
            Requests::PATCH,
            $headers
        );

        return $response;
    }

    public function bulkUpdateAttachments(array $payoutIds, array $updateRequest)
    {
        $this->trace->info(TraceCode::BULK_UPDATE_ATTACHMENTS_FOR_PAYOUT_VIA_MICROSERVICE_REQUEST,
                           [
                               'payout_ids' => $payoutIds,
                               'input'      => $updateRequest,
                           ]);

        $input = [
            'payout_ids'     => $payoutIds,
            'update_request' => $updateRequest,
        ];

        $url = self::BULK_UPDATE_ATTACHMENTS_URI;

        return $this->sendRequestAndGetContent($input, $url);
    }
}
