<?php

namespace RZP\Services\FavService;

use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;

class Update extends Base
{
    const UPDATE_FAV_SERVICE_URI = '/fund_accounts/validations/update';

    const FAV_SERVICE_UPDATE     = 'fav_service_update';

    public function updateFavInMicroservice(string $favId, array $input)
    {
        $request = $this->createRequestBody($favId, $input);

        $this->trace->info(TraceCode::FAV_UPDATE_IN_MIRCROSERVICE_REQUEST,
            [
                'request' => $request,
            ]);

        $headers = $this->getHeadersWithJwt();

        return $this->makeRequestAndGetContent(
            $request,
            self::UPDATE_FAV_SERVICE_URI,
            Requests::POST,
            $headers
        );
    }

    public function createRequestBody(string $favId, array $input)
    {
        $requestBody = [
            'fav_id' => $favId,
            'result' => $input,
        ];

        return $requestBody;
    }
}
