<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\register;

use Requests_Response;
use Requests_Exception;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\Detail\Constants;

class RegistrationProcessorMock extends RegistrationProcessor
{

    protected $mockStatus = 'success';

    public function setMockStatus(string $status)
    {
        $this->mockStatus = $status;
    }

    protected function getResponse(array $request)
    {
        $response = new Requests_Response();

        $response->headers = ['Content-Type' => 'application/json'];

        $response->status_code = 201;

        $body = null;

        switch ($this->mockStatus)
        {
            case Constants::SUCCESS:
                $body = [
                    'request_id'  => 'deff5ed8-0460-11e9-a082-4742912ca12a',
                    'kyc_id'      => 'DqSqt9iTs0JDXW',
                    'status-code' => 101,
                ];

                break;

            case Constants::FAILURE:
                throw new Requests_Exception('Error when fetching pan data', 'timeout/downtime');

                break;
        }

        $responseStructure = [
            'data' => $body
        ];

        $response->body = json_encode($responseStructure);

        return $response;
    }

    public function process(): Response
    {
        $response = $this->getResponse([]);

        return new RegistrationResponse($response);
    }
}
