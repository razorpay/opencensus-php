<?php

namespace RZP\Services\FavService;

use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;

class Update extends Base
{
    const FAV_SERVICE_UPDATE     = 'fav_service_update';

    const BANK_WEBHOOK_URI = '/internal/validations/webhook/{bank}';

    /**
     * Handle webhook from bank for fund account validation
     *
     * @param array $input Webhook payload from bank
     * @param string $bank Bank identifier (e.g. 'citi')
     * @return array
     */
    public function handleBankWebhook(array $input, string $bank): array
    {
        $this->trace->info(TraceCode::FAV_SERVICE_BANK_WEBHOOK_UPDATE_REQUEST, [
            'bank' => $bank,
            'input' => $input
        ]);

        $uri = str_replace('{bank}', $bank, self::BANK_WEBHOOK_URI);

        $headers = $this->getHeadersWithJwt();

        $response = $this->makeRequestAndGetContent(
            $input,
            $uri,
            Requests::POST,
            $headers
        );

        $this->trace->info(TraceCode::FAV_SERVICE_BANK_WEBHOOK_UPDATE_RESPONSE, [
            'bank' => $bank,
            'response' => $response
        ]);

        return $response;
    }
}
