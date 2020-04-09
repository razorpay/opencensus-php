<?php

namespace RZP\Jobs;

use App;
use Jitendra\Lqext\TransactionAware;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Services\SalesForceClient;


class SalesforceRequestJob extends RequestJob
{
    use TransactionAware;

    const STATUS = 'Status';

    /** @var $salesforceClient SalesForceClient */
    protected $salesforceClient;

    public function __construct(array $request,
                                string $traceCodeRequest,
                                string $traceCodeResponse,
                                string $traceCodeError)
    {
        parent::__construct($request);

        $this->traceCodeRequest = $traceCodeRequest;

        $this->traceCodeResponse = $traceCodeResponse;

        $this->traceCodeError = $traceCodeError;
    }

    private function getAccessToken()
    {
        return $this->salesforceClient->fetchAccessToken();
    }

    protected function handleRequest()
    {
        $app = App::getFacadeRoot();

        $this->salesforceClient = $app->salesforce;

        $accessToken = $this->getAccessToken();

        $this->request['headers'][RequestHeader::AUTHORIZATION] = RequestHeader::BEARER . ' ' . $accessToken;

        $response = parent::handleRequest();

        if (($response[self::STATUS_CODE] != 200) or
            ($response[self::BODY][self::STATUS] != "SUCCESS"))
        {
            throw new Exception\IntegrationException(
                'Failed to push event to Salesforce',
                ErrorCode::SERVER_ERROR_SALESFORCE_SERVICE_ERROR,
                [
                    'request' => $this->salesforceClient->getTraceableRequest($this->request),
                    'response' => $this->response->body
                ]);
        }
    }
}
