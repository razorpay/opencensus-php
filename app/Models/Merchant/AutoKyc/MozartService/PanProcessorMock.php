<?php

namespace RZP\Models\Merchant\AutoKyc\MozartService;

use \WpOrg\Requests\Response;
use \WpOrg\Requests\Exception as Requests_Exception;

use RZP\Models\Merchant\Detail\Constants;

class PanProcessorMock extends PanProcessor
{
    protected $mockStatus = 'success';

    public function setMockStatus(string $status)
    {
        $this->mockStatus = $status;
    }

    protected function getResponse(array $request)
    {
        $response = new \WpOrg\Requests\Response();

        $response->headers     = ['Content-Type' => 'application/json'];
        $response->status_code = 200;

        $body = null;

        switch ($this->mockStatus)
        {
            case Constants::INCORRECT_DETAILS:
                $body = [
                    'result'      => [],
                    'request_id'  => 'deff5ed8-0460-11e9-a082-4742912ca12a',
                    'status-code' => 102,
                ];

                break;

            case Constants::SUCCESS:
                $body = [
                    'result'      => [
                        "name" => "Test123",
                    ],
                    'request_id'  => 'deff5ed8-0460-11e9-a082-4742912ca12a',
                    'status-code' => 101,
                ];

                break;

            case Constants::FAILURE:
                throw new Requests_Exception('Error when fetching pan data', 'timeout/downtime');

                break;
        }

        $responseStructure = [
            'data' => [
                'content' => [
                    'response' => $body
                ]
            ]
        ];

        $response->body = json_encode($responseStructure);

        return $response;
    }
}
