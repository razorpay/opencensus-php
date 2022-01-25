<?php

namespace RZP\Jobs;

use App;
use Jitendra\Lqext\TransactionAware;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
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


        // Mode is unset when request comes directly to API. (Not via dashboard)
        // This throws an error in Job.php tracing
        // Refer: https://razorpay.slack.com/archives/C01UAT4ULJJ/p1641301823198300?thread_ts=1641301364.198200&cid=C01UAT4ULJJ
        if ($this->mode === null)
        {
            $app = App::getFacadeRoot();

            $app['trace']->info(TraceCode::FORCE_SET_SALESFORCE_REQUEST_JOB_MODE, ['new_mode' => Mode::LIVE]);

            $this->mode = Mode::LIVE;
        }
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
            ($response[self::BODY][self::STATUS] != "SUCCESS")
        )
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
