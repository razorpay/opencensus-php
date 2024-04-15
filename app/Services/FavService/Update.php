<?php

namespace RZP\Services\FavService;

use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;

class Update extends Base
{
    const UPDATE_FAV_VPA_SERVICE_URI = '/fund_accounts/validations/update/vpa';

    const UPDATE_FAV_BA_SERVICE_URI = '/fund_accounts/validations/update/fts';

    const FAV_SERVICE_UPDATE     = 'fav_service_update';

    public function updateFavInMicroservice(string $favId, array $input, string $type)
    {
        $request = $this->createRequestBody($favId, $input);

        $this->trace->info(TraceCode::FAV_UPDATE_IN_MIRCROSERVICE_REQUEST,
            [
                'request' => $request,
            ]);

        $headers = $this->getHeadersWithJwt();

        $uri = ($type === 'vpa') ? self::UPDATE_FAV_VPA_SERVICE_URI : self::UPDATE_FAV_BA_SERVICE_URI ;

        return $this->makeRequestAndGetContent(
            $request,
            $uri,
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
