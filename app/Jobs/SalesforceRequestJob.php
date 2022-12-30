<?php

namespace RZP\Jobs;

use App;
use Jitendra\Lqext\TransactionAware;
use Config;
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
    const MAX_RETRY_COUNT = 1;

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

    private function getAccessToken(bool $skipCache = false)
    {
        return $this->salesforceClient->fetchAccessToken($skipCache);
    }

    private function notifySFDrop(array $input)
    {
        $app = App::getFacadeRoot();
        $message = '*ALERT*: Salesforce Call Dropped for below mentioned mid';
        $channel = Config::get('slack.channels.platform_growth_alerts');
        $app['slack']->queue($message, $input, ['channel' => $channel]);
    }

    protected function handleRequest()
    {
        $app = App::getFacadeRoot();

        $this->salesforceClient = $app->salesforce;

        $this->handleRequestToSalesforce();
    }

    private function handleRequestToSalesforce(int $attemptCount = 1, bool $skipCache = false)
    {
        $accessToken = $this->getAccessToken($skipCache);

        $this->request['headers'][RequestHeader::AUTHORIZATION] = RequestHeader::BEARER . ' ' . $accessToken;

        $response = $this->salesforceClient->sendRequest($this->request);

        $responseBody = json_decode($response->body, true);

        if (($response->status_code == 400) and
            ($attemptCount <= self::MAX_RETRY_COUNT))
        {
            app('trace')->info(TraceCode::SALESFORCE_SERVICE_AUTH_ERROR, $responseBody);

            $this->handleRequestToSalesforce(++$attemptCount, true);

            return;
        }

        if (($response->status_code != 200) or
            ($responseBody[self::STATUS] != "SUCCESS")
        )
        {
            $this->notifySFDrop($this->request);
            throw new Exception\IntegrationException(
                'Failed to push event to Salesforce',
                ErrorCode::SERVER_ERROR_SALESFORCE_SERVICE_ERROR,
                [
                    'request' => $this->salesforceClient->getTraceableRequest($this->request),
                    'response' => $responseBody
                ]);
        }
    }
}
