<?php

namespace RZP\Services\FavService;

use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;

class Fetch extends Base
{
    const FAV_SERVICE_FETCH = 'fav_service_fetch';

    const FETCH_FAV_URI = '/internal/validations';

    /**
     * Fetch fund account validation by ID
     *
     * @param string $id Fund account validation ID
     * @return array
     */
    public function fetchById(string $id): array
    {
        $this->trace->info(TraceCode::FAV_SERVICE_FETCH_REQUEST, [
            'id' => $id
        ]);

        $uri = self::FETCH_FAV_URI . '/' . $id;

        $headers = $this->getHeadersWithJwt();

        $response = $this->makeRequestAndGetContent(
            [],
            $uri,
            Requests::GET,
            $headers
        );

        $this->trace->info(TraceCode::FAV_SERVICE_FETCH_RESPONSE, [
            'id' => $id,
            'response' => $response
        ]);

        return $response;
    }
}
